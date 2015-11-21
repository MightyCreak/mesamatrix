<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014 Romain "Creak" Failliot.
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
    protected $gl3Path;
    protected $statuses;
    protected $urlCache;

    protected function configure() {
        $this->setName('parse')
             ->setDescription('Parse data and generate XML')
             ->addOption('force', '-f',
                         InputOption::VALUE_NONE,
                         'Force to parse all the commits again');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $force = $input->getOption('force');

        $this->output = $output;
        $this->gl3Path = \Mesamatrix::$config->getValue('git', 'gl3', 'docs/GL3.txt');
        $this->statuses = array(
            'complete' => 'DONE*',
            'incomplete' => 'not started*',
            'started' => '*');

        $commits = $this->fetchCommits();
        if ($commits === NULL) {
            return 1;
        }

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
            return 0;
        }

        \Mesamatrix::$logger->info("New commit found, let's parse!");

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

            if ($i === $numCommits - 1) {
                \Mesamatrix::$logger->error('The last parsed commit ('.$lastCommitParsed.') could not be found in the list of commits.');
                return 1;
            }

            $firstNewCommitIdx = $i + 1;
        }

        $this->loadUrlCache();

        // Parse each commit.
        $newCommits = array_slice($commits, $firstNewCommitIdx);
        foreach ($newCommits as $commit) {
            $this->parseCommit($commit);
        }

        $this->saveUrlCache();

        // Merge all the commits.
        $this->generateMergedXml($commits);

        // Save last commit parsed.
        if (!$force) {
            $h = fopen($lastCommitFilename, "w");
            if ($h !== false) {
                fwrite($h, $lastCommitFetched);
                fclose($h);
            }
        }
    }

    /**
     * Fetch commits from mesa's git.
     *
     * @return array|null.
     */
    protected function fetchCommits() {
        $gitLog = new \Mesamatrix\Git\ProcessBuilder(array(
            'log', '--pretty=format:%H|%at|%aN|%cN|%ct|%s', '--reverse',
            \Mesamatrix::$config->getValue('git', 'oldest_commit').'..', '--',
            $this->gl3Path
        ));
        $proc = $gitLog->getProcess();
        $this->getHelper('process')->mustRun($this->output, $proc);

        $commitLines = explode(PHP_EOL, $proc->getOutput());
        if (empty($commitLines)) {
            // No commit? There must be a problem.
            \Mesamatrix::$logger->error("No commit found.");
            return NULL;
        }

        // Create commit list.
        $commits = array();
        foreach ($commitLines as $commitLine) {
            $commitData = explode('|', $commitLine, 6);
            if ($commitData !== FALSE) {
                $commit = new \Mesamatrix\Git\Commit();
                $commit->setHash($commitData[0])
                       ->setDate($commitData[1])
                       ->setAuthor($commitData[2])
                       ->setCommitter($commitData[3])
                       ->setCommitterDate($commitData[4])
                       ->setSubject($commitData[5]);
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
        $hash = $commit->getHash();
        $cat = new \Mesamatrix\Git\ProcessBuilder(array('show', $hash.':'.$this->gl3Path));
        $proc = $cat->getProcess();
        $this->getHelper('process')->mustRun($this->output, $proc);

        // Parse the content.
        \Mesamatrix::$logger->info('Parsing GL3.txt for commit '.$hash);
        $parser = new OglParser();
        $matrix = $parser->parseContent($proc->getOutput(), $commit);

        // Create the XML.
        $xml = new \SimpleXMLElement("<mesa></mesa>");

        // Write commit info.
        $xmlCommit = $xml->addChild('commit');
        $xmlCommit->addChild('hash', $hash);
        //$xmlCommit->addChild('subject', $commit->getSubject());
        $xmlCommit->addChild('author', $commit->getAuthor());
        $xmlCommit->addChild('committer-date', $commit->getCommitterDate()->format(\DateTime::W3C));
        $xmlCommit->addChild('committer-name', $commit->getCommitter());

        // Write GL sections.
        $this->generateGlSections($xml, $matrix);

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

        $gitDir = \Mesamatrix::path(\Mesamatrix::$config->getValue('info', 'private_dir')).'/';
        $gitDir .= \Mesamatrix::$config->getValue('git', 'mesa_dir', 'mesa.git');
        if (is_file($gitDir . '/FETCH_HEAD')) {
            $updated = filemtime($gitDir . '/FETCH_HEAD');
        } else {
            $updated = filemtime($gitDir);
        }
        $xml->addAttribute('updated', $updated);

        $drivers = $xml->addChild("drivers");
        $this->populateDrivers($drivers);

        $statuses = $xml->addChild("statuses");
        $this->populateStatuses($statuses);

        $xmlCommits = $xml->addChild('commits');
        $this->generateCommitsLog($xmlCommits, $commits);

        $this->generateGlSections($xml, $matrix);

        $xmlPath = \Mesamatrix::path(\Mesamatrix::$config->getValue("info", "xml_file"));

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        file_put_contents($xmlPath, $dom->saveXML());

        \Mesamatrix::$logger->info('XML saved to '.$xmlPath);
    }

    protected function populateDrivers(\SimpleXMLElement $drivers) {
        foreach (\Mesamatrix\Parser\Constants::$allDriversVendors as $glVendor => $glDrivers) {
            $vendor = $drivers->addChild("vendor");
            $vendor->addAttribute("name", $glVendor);
            foreach ($glDrivers as $glDriver) {
                $driver = $vendor->addChild("driver");
                $driver->addAttribute("name", $glDriver);
            }
        }
    }

    protected function populateStatuses(\SimpleXMLElement $xmlStatuses) {
        foreach ($this->statuses as $status => $match) {
            $xmlStatus = $xmlStatuses->addChild($status);
            $xmlStatus->addChild('match', $match);
        }
    }

    protected function generateCommitsLog(\SimpleXMLElement $xml, array $commits) {
        foreach (array_reverse($commits) as $commit) {
            $commitNode = $xml->addChild("commit");
            $commitNode->addAttribute("hash", $commit->getHash());
            $commitNode->addAttribute("timestamp", $commit->getCommitterDate()->getTimestamp());
            $commitNode->addAttribute("subject", $commit->getSubject());
        }
    }

    protected function generateGlSections(\SimpleXMLElement $xml, OglMatrix $matrix) {
        foreach ($matrix->getGlVersions() as $glVersion) {
            $gl = $xml->addChild('gl');
            $this->generateGlSection($gl, $glVersion, $matrix->getHints());
        }
    }

    protected function generateGlSection(\SimpleXMLElement $gl, OglVersion $glVersion, Hints $hints) {
        $gl->addAttribute("name", $glVersion->getGlName());
        $gl->addAttribute("version", $glVersion->getGlVersion());
        $glsl = $gl->addChild("glsl");
        $glsl->addAttribute("name", $glVersion->getGlslName());
        $glsl->addAttribute("version", $glVersion->getGlslVersion());

        foreach ($glVersion->getExtensions() as $glExt) {
            $ext = $gl->addChild("extension");
            $this->generateExtension($ext, $glExt, $hints);
        }
    }

    protected function generateExtension(\SimpleXMLElement $xmlExt, OglExtension $glExt, Hints $hints) {
        $xmlExt->addAttribute("name", $glExt->getName());

        if ($this->urlCache) {
            if (preg_match("/(GLX?)_([^_]+)_([a-zA-Z0-9_]+)/", $glExt->getName(), $matches) === 1) {
                $openglUrl = \Mesamatrix::$config->getValue("opengl_links", "url").urlencode($matches[2])."/";
                if ($matches[1] === "GL") {
                    // Found a GL_TYPE_extension.
                    $openglUrl .= urlencode($matches[3]).".txt";
                }
                else {
                    // Found a GLX_TYPE_Extension.
                    $openglUrl .= "glx_".urlencode($matches[3]).".txt";
                }

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
