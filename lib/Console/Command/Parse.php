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
    protected $urlCache;
    protected $statuses;

    protected function configure() {
        $this->setName('parse')
             ->setDescription('Parse data and generate XML');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $gl3Path = \Mesamatrix::$config->getValue('git', 'gl3', 'docs/GL3.txt');

        $commits = $this->fetchCommits($gl3Path, $output);
        if ($commits === NULL) {
            return 1;
        }

        $lastCommitFilename = \Mesamatrix::$config->getValue('info', 'private_dir', 'private')
                            . '/last_commit_parsed';

        // Get last commit parsed.
        $lastCommitParsed = "";
        if (file_exists($lastCommitFilename)) {
            $h = fopen($lastCommitFilename, "r");
            if ($h !== false) {
                $lastCommitParsed = fgets($h);
                fclose($h);
            }
        }

        // Get last commit fetched.
        $lastCommitFetched = $commits[count($commits) - 1]->getHash();

        // Compare last parsed and fetched commits.
        \Mesamatrix::$logger->debug("Last commit fetched: ${lastCommitFetched}");
        \Mesamatrix::$logger->debug("Last commit parsed:  ${lastCommitParsed}");
        if ($lastCommitFetched === $lastCommitParsed) {
            \Mesamatrix::$logger->info("No new commit, no need to parse.");
            return 0;
        }

        \Mesamatrix::$logger->info("New commit found, let's parse!");

        $hints = new Hints();
        $matrix = new OglMatrix();
        $parser = new OglParser($hints, $matrix);
        foreach ($commits as $commit) {
            \Mesamatrix::$logger->info('Parsing GL3.txt for commit '.$commit->getHash());
            $cat = new \Mesamatrix\Git\ProcessBuilder(array(
              'show', $commit->getHash().':'.$gl3Path
            ));
            $proc = $cat->getProcess();
            $this->getHelper('process')->mustRun($output, $proc);

            $parser->parse_content($proc->getOutput(), $commit);
        }

        $xml = new \SimpleXMLElement("<mesa></mesa>");

        $gitDir = \Mesamatrix::path(\Mesamatrix::$config->getValue('info', 'private_dir')).'/';
        $gitDir .= \Mesamatrix::$config->getValue('git', 'mesa_dir', 'mesa.git');
        $updated = filemtime($gitDir . '/FETCH_HEAD');
        $xml->addAttribute('updated', $updated);

        $drivers = $xml->addChild("drivers");
        $this->populateDrivers($drivers);

        $this->statuses = $xml->addChild("statuses");
        $this->populateStatuses($this->statuses);

        $xmlCommits = $xml->addChild('commits');
        $this->generateCommitsLog($xmlCommits, $commits);

        $this->generateGlSections($xml, $matrix, $hints);

        $xmlPath = \Mesamatrix::path(\Mesamatrix::$config->getValue("info", "xml_file"));

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;

        file_put_contents($xmlPath, $dom->saveXML());
        \Mesamatrix::$logger->notice('XML saved to '.$xmlPath);

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
     * @param string $gl3Path The path of the GL3.txt file.
     * @param OutputInterface $output The output to write to.
     * @return array|null.
     */
    protected function fetchCommits($gl3Path, OutputInterface $output) {
        $gitLog = new \Mesamatrix\Git\ProcessBuilder(array(
            'log', '--pretty=format:%H|%at|%aN|%cN|%ct|%s', '--reverse',
            \Mesamatrix::$config->getValue('git', 'oldest_commit').'..', '--',
            $gl3Path
        ));
        $proc = $gitLog->getProcess();
        $this->getHelper('process')->mustRun($output, $proc);

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

    protected function populateStatuses(\SimpleXMLElement $statuses) {
        $complete = $statuses->addChild("complete");
        $complete->addChild("match", "DONE*");

        $incomplete = $statuses->addChild("incomplete");
        $incomplete->addChild("match", "not started*");

        $inProgressStatus = $statuses->addChild("started");
        $inProgressStatus->addChild("match", "*");
    }

    protected function generateCommitsLog(\SimpleXMLElement $xml, array $commits) {
        foreach (array_reverse($commits) as $commit) {
            \Mesamatrix::$logger->debug('Processing commit '.$commit->getHash());
            $commitNode = $xml->addChild("commit");
            $commitNode->addAttribute("hash", $commit->getHash());
            $commitNode->addAttribute("timestamp", $commit->getCommitterDate()->getTimestamp());
            $commitNode->addAttribute("subject", $commit->getSubject());
        }
    }

    protected function generateGlSections(\SimpleXMLElement $xml, OglMatrix $matrix, Hints $hints) {
        $this->loadUrlCache();

        foreach ($matrix->getGlVersions() as $glVersion) {
            $gl = $xml->addChild('gl');
            $this->generateGlSection($gl, $glVersion, $hints);
        }

        $this->saveUrlCache();
    }

    protected function generateGlSection(\SimpleXMLElement $gl, OglVersion $glVersion, Hints $hints) {
        $gl->addAttribute("name", $glVersion->getGlName());
        $gl->addAttribute("version", $glVersion->getGlVersion());
        $glsl = $gl->addChild("glsl");
        $glsl->addAttribute("name", $glVersion->getGlslName());
        $glsl->addAttribute("version", $glVersion->getGlslVersion());

        foreach ($glVersion->getExtensions() as $glExt) {
            \Mesamatrix::$logger->debug('Processing extension '.$glExt->getName());
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
        $statusLength = 0;
        foreach ($this->statuses as $matchStatus) {
            foreach ($matchStatus as $match) {
                if (fnmatch($match, $glExt->getStatus()) && strlen($match) >= $statusLength) {
                    $mesaStatus->addAttribute("status", $matchStatus->getName());
                    $statusLength = strlen($match);
                }
            }
        }
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
