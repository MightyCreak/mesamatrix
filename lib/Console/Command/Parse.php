<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014-2017 Romain "Creak" Failliot.
 * Copyright (C) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Mesamatrix\Console\Command;

use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \Mesamatrix\Parser\Constants;
use \Mesamatrix\Parser\OglParser;
use \Mesamatrix\Parser\OglMatrix;
use \Mesamatrix\Parser\OglVersion;
use \Mesamatrix\Parser\OglExtension;
use \Mesamatrix\Parser\UrlCache;
use \Mesamatrix\Parser\Hints;

/**
 * Class that handles the 'parse' command.
 *
 * It reads all the commits of the file GL3.txt and transform it to an XML
 * file. The entry point for this class is the `execute()` method.
 */
class Parse extends \Symfony\Component\Console\Command\Command
{
    protected $output;
    protected $statuses;
    protected $urlCache;

    protected function configure() {
        $this->setName('parse')
             ->setDescription('Parse data and generate XML')
             ->addOption('force', '-f',
                         InputOption::VALUE_NONE,
                         'Force to parse all the commits again')
             ->addOption('regenerate-xml', '-r',
                         InputOption::VALUE_NONE,
                         'Regenerate the XML based on the already parsed commits');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $force = $input->getOption('force');
        $regenXml = $input->getOption('regenerate-xml');

        $this->output = $output;
        $this->statuses = array(
            'complete' => 'DONE*',
            'incomplete' => 'not started*',
            'started' => '*');

        // Get commits for each files in gl_filepaths.
        $commits = array();
        $glFilepaths = \Mesamatrix::$config->getValue('git', 'gl_filepaths', array());
        foreach ($glFilepaths as $filepath) {
            $fileCommits = $this->fetchCommits($filepath);
            if ($fileCommits !== NULL) {
                foreach ($fileCommits as $commit) {
                    $commits[] = $commit;
                }
            }
        }

        if (empty($commits)) {
            // No commit found, exit.
            return 1;
        }

        // Remove first commit in successive duplicates
        // (it means the file has been renamed).
        $i = 1;
        $num = count($commits);
        while ($i < $num) {
            if ($commits[$i - 1]->getHash() === $commits[$i]->getHash()) {
                array_splice($commits, $i - 1, 1);
                $num--;
            }
            else {
                $i++;
            }
        }
        unset($i, $num);

        $lastCommitFilename = \Mesamatrix::$config->getValue('info', 'private_dir', 'private')
                            . '/last_commit_parsed';

        // Get last commit parsed.
        $lastCommitParsed = "";
        if (!$force) {
            if (file_exists($lastCommitFilename)) {
                $h = fopen($lastCommitFilename, "r");
                if ($h !== false) {
                    $lastCommitParsed = fgets($h);
                    fclose($h);
                }
            }
        }
        else {
            \Mesamatrix::$logger->info('Commits parsing forced.');
        }

        // Get last commit fetched.
        $numCommits = count($commits);
        $lastCommitFetched = $commits[$numCommits - 1]->getHash();

        // Compare last parsed and fetched commits.
        \Mesamatrix::$logger->debug("Last commit fetched: ${lastCommitFetched}");
        \Mesamatrix::$logger->debug("Last commit parsed:  ${lastCommitParsed}");
        if ($lastCommitFetched === $lastCommitParsed) {
            \Mesamatrix::$logger->info("No new commit, no need to parse.");
            if (!$regenXml)
                return 0;
        }

        // Ensure existence of the commits directory.
        $commitsDir = \Mesamatrix::path(\Mesamatrix::$config->getValue('info', 'private_dir'))
                   . '/commits';
        if (!is_dir($commitsDir)) {
            if (!mkdir($commitsDir)) {
                \Mesamatrix::$logger->critical('Couldn\'t create directory `'.$commitsDir.'`.');
                return 1;
            }
        }

        // Get list of new commits to parse.
        $firstNewCommitIdx = 0;
        if (!empty($lastCommitParsed)) {
            // Find index of the last parsed commit in the commit list.
            // It can't be the last one since we verified they are different.
            $i = 0;
            while ($i < $numCommits - 1 && $commits[$i]->getHash() !== $lastCommitParsed) {
                ++$i;
            }

            $firstNewCommitIdx = $i + 1;
        }

        $newCommits = array_slice($commits, $firstNewCommitIdx);
        \Mesamatrix::$logger->info("New commit(s) found: ".count($newCommits).".");

        $this->loadUrlCache();

        // Parse each new commit.
        foreach ($newCommits as $commit) {
            $this->parseCommit($commit);
        }

        $this->saveUrlCache();

        // Merge all the commits.
        $this->generateMergedXml($commits);

        // Save last commit parsed.
        $h = fopen($lastCommitFilename, "w");
        if ($h !== false) {
            fwrite($h, $lastCommitFetched);
            fclose($h);
        }
    }

    /**
     * Fetch commits from mesa's git.
     *
     * @param string $filepath GL text file path.
     * @return \Mesamatrix\Git\Commit[]|null Array of commits.
     */
    protected function fetchCommits($filepath) {
        $gitCommitGet = new \Mesamatrix\Git\ProcessBuilder(array(
            'rev-list', 'master', '--reverse', '--', $filepath
        ));
        $proc = $gitCommitGet->getProcess();
        $proc->mustRun();
        $oldestCommit = strtok($proc->getOutput(), "\n");
        \Mesamatrix::$logger->info('Oldest commit: '.$oldestCommit);

        $logSeparator = uniqid('mesamatrix_separator_');
        $logFormat = implode(PHP_EOL, [$logSeparator, '%H', '%at', '%aN', '%cN', '%ct', '%s']);
        $gitLog = new \Mesamatrix\Git\ProcessBuilder(array(
            'log', '--pretty=format:'.$logFormat, '--reverse', '-p',
            $oldestCommit.'^..', '--', $filepath
        ));
        $proc = $gitLog->getProcess();
        $this->getHelper('process')->mustRun($this->output, $proc);

        $commitSections = explode($logSeparator . PHP_EOL, $proc->getOutput());
        if (empty($commitSections)) {
            // No commit? There must be a problem.
            \Mesamatrix::$logger->error("No commit found.");
            return NULL;
        }

        // Create commit list.
        $commits = array();
        foreach ($commitSections as $commitSection) {
            $commitData = explode(PHP_EOL, $commitSection, 7);
            if ($commitData !== FALSE && isset($commitData[1])) {
                $commit = new \Mesamatrix\Git\Commit();
                $commit->setFilepath($filepath)
                       ->setHash($commitData[0])
                       ->setDate($commitData[1])
                       ->setAuthor($commitData[2])
                       ->setCommitter($commitData[3])
                       ->setCommitterDate($commitData[4])
                       ->setSubject($commitData[5])
                       ->setData('<pre>'.$commitData[6].'</pre>');
                $commits[] = $commit;
            }
        }

        return $commits;
    }

    /**
     * Parse a commit.
     *
     * @param \Mesamatrix\Git\Commit $commit The commit to parse.
     */
    protected function parseCommit(\Mesamatrix\Git\Commit $commit) {
        // Show content for this commit.
        $filepath = $commit->getFilepath();
        $hash = $commit->getHash();
        $cat = new \Mesamatrix\Git\ProcessBuilder(array('show', $hash.':'.$filepath));
        $proc = $cat->getProcess();
        $this->getHelper('process')->mustRun($this->output, $proc);

        // Parse the content.
        \Mesamatrix::$logger->info('Parsing '.(basename($filepath)).' for commit '.$hash);
        $parser = new OglParser();
        $matrix = $parser->parseContent($proc->getOutput(), $commit);

        // Create the XML.
        $xml = new \SimpleXMLElement("<mesa></mesa>");

        // Write commit info.
        $xmlCommit = $xml->addChild('commit');
        $xmlCommit->addChild('filepath', $filepath);
        $xmlCommit->addChild('hash', $hash);
        //$xmlCommit->addChild('subject', $commit->getSubject());
        $xmlCommit->addChild('author', $commit->getAuthor());
        $xmlCommit->addChild('committer-date', $commit->getCommitterDate()->format(\DateTime::W3C));
        $xmlCommit->addChild('committer-name', $commit->getCommitter());

        // Write APIs.
        $apis = $xml->addChild('apis');

        // Write OpenGL API.
        $api = $apis->addChild('api');
        $api->addAttribute('name', 'OpenGL');
        $this->generateApi($api, $matrix, 'OpenGL');

        // Write OpenGL ES API.
        $api = $apis->addChild('api');
        $api->addAttribute('name', 'OpenGL ES');
        $this->generateApi($api, $matrix, 'OpenGL ES');

        // Write OpenGL(ES) extra API.
        $api = $apis->addChild('api');
        $api->addAttribute('name', Constants::GL_OR_ES_EXTRA_NAME);
        $this->generateApi($api, $matrix, Constants::GL_OR_ES_EXTRA_NAME);

        // Write Vulkan API.
        $api = $apis->addChild('api');
        $api->addAttribute('name', 'Vulkan');
        $this->generateApi($api, $matrix, 'Vulkan');

        // Write Vulkan extra API.
        $api = $apis->addChild('api');
        $api->addAttribute('name', Constants::VK_EXTRA_NAME);
        $this->generateApi($api, $matrix, Constants::VK_EXTRA_NAME);

        // Write file.
        $xmlPath = \Mesamatrix::path(\Mesamatrix::$config->getValue('info', 'private_dir'))
                   . '/commits/commit_'.$hash.'.xml';
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;

        file_put_contents($xmlPath, $dom->saveXML());
        \Mesamatrix::$logger->info('XML saved to '.$xmlPath);
    }

    /**
     * Take all the parsed commits and merged them to generate the final XML.
     *
     * @param array \Mesamatrix\Git\Commit $commits The commits to merge.
     */
    protected function generateMergedXml(array $commits) {
        \Mesamatrix::$logger->info('Merge all the commits.');

        $matrix = new OglMatrix();
        foreach ($commits as $commit) {
            $hash = $commit->getHash();
            $xmlPath = \Mesamatrix::path(\Mesamatrix::$config->getValue('info', 'private_dir'))
                     . '/commits/commit_'.$hash.'.xml';

            $mesa = simplexml_load_file($xmlPath);
            $matrix->merge($mesa, $commit);
        }

        \Mesamatrix::$logger->info('Generating XML file');
        $xml = new \SimpleXMLElement("<mesa></mesa>");

        // Get time of last fetch.
        $gitDir = \Mesamatrix::path(\Mesamatrix::$config->getValue('info', 'private_dir')).'/';
        $gitDir .= \Mesamatrix::$config->getValue('git', 'mesa_dir', 'mesa.git');
        $fetchHeadPath = $gitDir . '/FETCH_HEAD';
        if (!file_exists($fetchHeadPath)) {
            // If FETCH_HEAD doesn't exists, fallback on HEAD.
            $fetchHeadPath = $gitDir . '/HEAD';
        }
        $updated = filemtime($fetchHeadPath);
        $xml->addAttribute('updated', $updated);

        $statuses = $xml->addChild("statuses");
        $this->populateStatuses($statuses);

        $xmlCommits = $xml->addChild('commits');
        $this->generateCommitsLog($xmlCommits, $commits);

        // Generate APIs.
        $apis = $xml->addChild('apis');

        // Generate for OpenGL.
        $api = $apis->addChild('api');
        $api->addAttribute('name', 'OpenGL');
        $this->populateGlDrivers($api);
        $this->generateApi($api, $matrix, 'OpenGL');

        // Generate for OpenGL ES.
        $api = $apis->addChild('api');
        $api->addAttribute('name', 'OpenGL ES');
        $this->populateGlDrivers($api); // Uses the same drivers as OpenGL.
        $this->generateApi($api, $matrix, 'OpenGL ES');

        // Generate for OpenGL(ES) extra.
        $api = $apis->addChild('api');
        $api->addAttribute('name', Constants::GL_OR_ES_EXTRA_NAME);
        $this->populateGlDrivers($api); // Uses the same drivers as OpenGL.
        $this->generateApi($api, $matrix, Constants::GL_OR_ES_EXTRA_NAME);

        // Generate for Vulkan.
        $api = $apis->addChild('api');
        $api->addAttribute('name', 'Vulkan');
        $this->populateVulkanDrivers($api);
        $this->generateApi($api, $matrix, 'Vulkan');

        // Generate for Vulkan (extra).
        $api = $apis->addChild('api');
        $api->addAttribute('name', Constants::VK_EXTRA_NAME);
        $this->populateVulkanDrivers($api);
        $this->generateApi($api, $matrix, Constants::VK_EXTRA_NAME);

        $xmlPath = \Mesamatrix::path(\Mesamatrix::$config->getValue("info", "xml_file"));

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        file_put_contents($xmlPath, $dom->saveXML());

        \Mesamatrix::$logger->info('XML saved to '.$xmlPath);
    }

    protected function populateStatuses(\SimpleXMLElement $xmlStatuses) {
        foreach ($this->statuses as $status => $match) {
            $xmlStatus = $xmlStatuses->addChild($status);
            $xmlStatus->addChild('match', $match);
        }
    }

    protected function generateCommitsLog(\SimpleXMLElement $xml, array $commits) {
        foreach (array_reverse($commits) as $commit) {
            $commitNode = $xml->addChild("commit", htmlspecialchars($commit->getData()));
            $commitNode->addAttribute("hash", $commit->getHash());
            $commitNode->addAttribute("timestamp", $commit->getCommitterDate()->getTimestamp());
            $commitNode->addAttribute("subject", $commit->getSubject());
        }
    }

    protected function populateGlDrivers(\SimpleXMLElement $xmlParent) {
        $xmlDrivers = $xmlParent->addChild("drivers");
        $this->populateDrivers($xmlDrivers, Constants::GL_ALL_DRIVERS_VENDORS);
    }

    protected function populateVulkanDrivers(\SimpleXMLElement $xmlParent) {
        $xmlDrivers = $xmlParent->addChild("drivers");
        $this->populateDrivers($xmlDrivers, Constants::VK_ALL_DRIVERS_VENDORS);
    }

    protected function populateDrivers(\SimpleXMLElement $xmlDrivers, array $vendors) {
        foreach ($vendors as $vendor => $drivers) {
            $xmlVendor = $xmlDrivers->addChild("vendor");
            $xmlVendor->addAttribute("name", $vendor);
            foreach ($drivers as $driver) {
                $xmlDriver = $xmlVendor->addChild("driver");
                $xmlDriver->addAttribute("name", $driver);
            }
        }
    }

    protected function generateApi(\SimpleXMLElement $api, OglMatrix $matrix, $name) {
        foreach ($matrix->getGlVersions() as $glVersion) {
            if ($glVersion->getGlName() === $name) {
                $version = $api->addChild('version');
                $this->generateGlVersion($version, $glVersion, $matrix->getHints());
            }
        }
    }

    protected function generateGlVersion(\SimpleXMLElement $version, OglVersion $glVersion, Hints $hints) {
        $version->addAttribute("name", $glVersion->getGlName());
        $version->addAttribute("version", $glVersion->getGlVersion());
        $glsl = $version->addChild("glsl");
        $glsl->addAttribute("name", $glVersion->getGlslName());
        $glsl->addAttribute("version", $glVersion->getGlslVersion());

        foreach ($glVersion->getExtensions() as $glExt) {
            $ext = $version->addChild("extension");
            $this->generateExtension($ext, $glExt, $hints);
        }
    }

    protected function generateExtension(\SimpleXMLElement $xmlExt, OglExtension $glExt, Hints $hints) {
        $xmlExt->addAttribute("name", $glExt->getName());

        if ($this->urlCache) {
            if (preg_match("/(GLX?)_([^_]+)_([a-zA-Z0-9_]+)/", $glExt->getName(), $matches) === 1) {
                $openglUrl = \Mesamatrix::$config->getValue("opengl_links", "url_gl").urlencode($matches[2])."/";
                if ($matches[1] === "GLX") {
                    // Found a GLX_TYPE_Extension.
                    $openglUrl .= "GLX_";
                }

                $openglUrl .= urlencode($matches[2]."_".$matches[3]).".txt";

                if ($this->urlCache->isValid($openglUrl)) {
                    $linkNode = $xmlExt->addChild("link", $matches[0]);
                    $linkNode->addAttribute("href", $openglUrl);
                }
            }
        }

        $mesaStatus = $xmlExt->addChild("mesa");
        $mesaStatus->addAttribute("status", $glExt->getStatus());
        $mesaHintId = $glExt->getHintIdx();
        if ($mesaHintId !== -1) {
            $mesaStatus->addAttribute("hint", $hints->allHints[$mesaHintId]);
        }
        if ($commit = $glExt->getModifiedAt()) {
            $modified = $mesaStatus->addChild("modified");
            $modified->addChild("commit", $commit->getHash());
            $modified->addChild("date", $commit->getCommitterDate()->getTimestamp());
            $modified->addChild("author", $commit->getAuthor());
        }

        $supported = $xmlExt->addChild("supported");
        foreach ($glExt->getSupportedDrivers() as $glDriver) {
            $driver = $supported->addChild($glDriver->getName());
            $hintId = $glDriver->getHintIdx();
            if ($hintId !== -1) {
                $driver->addAttribute("hint", $hints->allHints[$hintId]);
            }
            if ($commit = $glDriver->getModifiedAt()) {
                $modified = $driver->addChild("modified");
                $modified->addChild("commit", $commit->getHash());
                $modified->addChild("date", $commit->getCommitterDate()->getTimestamp());
                $modified->addChild("author", $commit->getAuthor());
            }
        }

        foreach ($glExt->getSubExtensions() as $glSubExt) {
            $xmlSubExt = $xmlExt->addChild("subextension");
            $this->generateExtension($xmlSubExt, $glSubExt, $hints);
        }
    }

    protected function loadUrlCache() {
        $this->urlCache = NULL;
        if(\Mesamatrix::$config->getValue("opengl_links", "enabled", false)) {
            // Load URL cache.
            $this->urlCache = new UrlCache();
            $this->urlCache->load();
        }
    }

    protected function saveUrlCache() {
        if ($this->urlCache) {
            $this->urlCache->save();
        }
    }
}
