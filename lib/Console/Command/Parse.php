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
use \Mesamatrix\Parser\OglVersion;
use \Mesamatrix\Parser\OglExtension;
use \Mesamatrix\Parser\UrlCache;
use \Mesamatrix\Parser\Hints;

class Parse extends \Symfony\Component\Console\Command\Command
{
    protected $urlCache;
    protected $statuses;

    protected function configure() {
        $this->setName('parse')
             ->setDescription('Parse data and generate XML')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $gl3Path = \Mesamatrix::$config->getValue('git', 'gl3', 'docs/GL3.txt');
        $gitLog = new \Mesamatrix\Git\ProcessBuilder(array(
            'log', '--pretty=format:%H|%at|%aN|%cN', '--reverse',
            \Mesamatrix::$config->getValue('git', 'oldest_commit').'..', '--',
            $gl3Path
        ));
        $gitLogProc = $gitLog->getProcess();
        $this->getHelper('process')->mustRun($output, $gitLogProc);

        $commitLines = explode(PHP_EOL, $gitLogProc->getOutput());
        $hints = new Hints();
        $matrix = new \Mesamatrix\Parser\OglMatrix();
        $parser = new \Mesamatrix\Parser\OglParser($hints, $matrix);
        foreach ($commitLines as $commitLine) {
            list($commitHash, $time, $author, $committer) = explode('|', $commitLine);
            $commit = new \Mesamatrix\Git\Commit($commitHash, $time, $author, $committer);
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

        $xmlCommits = $xml->addChild('commits');
        $this->generateCommitsLog($xmlCommits, $gl3Path, $output);

        $this->statuses = $xml->addChild("statuses");
        $this->populateStatuses($this->statuses);

        // Need URL cache?
        $this->urlCache = NULL;
        if(\Mesamatrix::$config->getValue("opengl_links", "enabled", false)) {
            // Load URL cache.
            $this->urlCache = new UrlCache();
            $this->urlCache->load();
        }

        // extensions
        foreach ($matrix->getGlVersions() as $glVersion) {
            $gl = $xml->addChild('gl');
            $this->generateGlSection($gl, $glVersion, $hints);
        }

        if ($this->urlCache) {
            $this->urlCache->save();
        }

        $xmlPath = \Mesamatrix::path(\Mesamatrix::$config->getValue("info", "xml_file"));

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;

        file_put_contents($xmlPath, $dom->saveXML());
        //$xml->asXML($xmlPath);
        \Mesamatrix::$logger->notice('XML saved to '.$xmlPath);
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

    protected function generateCommitsLog(\SimpleXMLElement $xml, $gl3Path, OutputInterface $output) {
        $gitLogFormat = "%H%n  timestamp: %ct%n  author: %an%n  subject: %s%n";
        $gitCommits = new \Mesamatrix\Git\ProcessBuilder(array(
          'log', '-n', \Mesamatrix::$config->getValue('git', 'commitparser_depth', 10),
          '--pretty=format:'.$gitLogFormat, '--',
          $gl3Path
        ));
        $proc = $gitCommits->getProcess();
        $this->getHelper('process')->mustRun($output, $proc);

        $commitsParser = new \Mesamatrix\CommitsParser();
        $commits = $commitsParser->parse_content($proc->getOutput());

        foreach ($commits as $gitCommit) {
            \Mesamatrix::$logger->debug('Processing commit '.$gitCommit["hash"]);
            $commit = $xml->addChild("commit");
            $commit->addAttribute("hash", $gitCommit["hash"]);
            $commit->addAttribute("timestamp", $gitCommit["timestamp"]);
            $commit->addAttribute("subject", $gitCommit["subject"]);
        }
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
            $modified->addChild("date", $commit->getDate()->getTimestamp());
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
                $modified->addChild("date", $commit->getDate()->getTimestamp());
                $modified->addChild("author", $commit->getAuthor());
            }
        }

        foreach ($glExt->getSubExtensions() as $glSubExt) {
            $xmlSubExt = $xmlExt->addChild("subextension");
            $this->generateExtension($xmlSubExt, $glSubExt, $hints);
        }
    }
}
