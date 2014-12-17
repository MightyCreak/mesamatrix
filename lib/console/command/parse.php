<?php
/*
 * Copyright (C) 2014 Romain "Creak" Failliot.
 * Copyright (C) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * This file is part of mesamatrix.
 *
 * mesamatrix is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * mesamatrix is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mesamatrix. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Mesamatrix\Console\Command;

use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

class Parse extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setName('parse')
             ->setDescription('Parse data and generate XML')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new \Symfony\Component\Console\Logger\ConsoleLogger($output);

        //$gitLog = exec_git("log --pretty=format:%H --reverse " .
        //    Mesamatrix::$config->getValue("git", "oldest_commit").".. -- ".Mesamatrix::$config->getValue("git", "gl3"), $log);
        //$initialCommit = rtrim(fgets($log));
        $initialCommit = "master";

        $cat = new \Mesamatrix\Git\ProcessBuilder(array(
          'show', $initialCommit.':'.\Mesamatrix::$config->getValue('git', 'gl3')
        ));
        $proc = $cat->getProcess();
        $this->getHelper('process')->mustRun($output, $proc);

        $hints = new \Mesamatrix\Parser\Hints();
        $parser = new \Mesamatrix\Parser\OglParser($hints);
        $matrix = $parser->parse_content($proc->getOutput());

        $xml = new \SimpleXMLElement("<mesa></mesa>");

        // driver definitions
        $drivers = $xml->addChild("drivers");
        foreach (\Mesamatrix\Parser\Constants::$allDriversVendors as $glVendor => $glDrivers) {
            $vendor = $drivers->addChild("vendor");
            $vendor->addAttribute("name", $glVendor);
            foreach ($glDrivers as $glDriver) {
                $driver = $vendor->addChild("driver");
                $driver->addAttribute("name", $glDriver);
            }
        }

        // commits log
        $gitLogFormat = "%H%n  timestamp: %ct%n  author: %an%n  subject: %s%n";
        $gitCommits = new \Mesamatrix\Git\ProcessBuilder(array(
          'log', '-n', \Mesamatrix::$config->getValue('git', 'commitparser_depth'),
          '--pretty=format:'.$gitLogFormat, '--', \Mesamatrix::$config->getValue('git', 'gl3')
        ));
        $proc = $gitCommits->getProcess();
        $this->getHelper('process')->mustRun($output, $proc);

        $commitsParser = new \Mesamatrix\CommitsParser();
        $commits = $commitsParser->parse_content($proc->getOutput());

        $xmlCommits = $xml->addChild("commits");
        foreach ($commits as $gitCommit) {
            $commit = $xmlCommits->addChild("commit");
            $commit->addAttribute("hash", $gitCommit["hash"]);
            $commit->addAttribute("timestamp", $gitCommit["timestamp"]);
            $commit->addAttribute("subject", $gitCommit["subject"]);
        }

        // status definitions
        $statuses = $xml->addChild("statuses");

        $complete = $statuses->addChild("complete");
        $complete->addChild("match", "DONE*");

        $incomplete = $statuses->addChild("incomplete");
        $incomplete->addChild("match", "not started*");

        $inProgressStatus = $statuses->addChild("started");
        $inProgressStatus->addChild("match", "*");

        // Need URL cache?
        $urlCache = NULL;
        if(\Mesamatrix::$config->getValue("opengl_links", "enabled", false)) {
            // Load URL cache.
            $urlCache = new \Mesamatrix\Parser\UrlCache();
            $urlCache->load();
        }

        // extensions
        foreach ($matrix->getGlVersions() as $glVersion) {
            $gl = $xml->addChild("gl");
            $gl->addAttribute("name", $glVersion->getGlName());
            $gl->addAttribute("version", $glVersion->getGlVersion());
            $glsl = $gl->addChild("glsl");
            $glsl->addAttribute("name", $glVersion->getGlslName());
            $glsl->addAttribute("version", $glVersion->getGlslVersion());

            foreach ($glVersion->getExtensions() as $glExt) {
                $ext = $gl->addChild("extension");
                $ext->addAttribute("name", $glExt->getName());

                if ($urlCache) {
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

                        if ($urlCache->isValid($openglUrl)) {
                            $linkNode = $ext->addChild("link", $matches[0]);
                            $linkNode->addAttribute("href", $openglUrl);
                        }
                    }
                }

                $mesaStatus = $ext->addChild("mesa");
                $statusLength = 0;
                foreach ($statuses as $matchStatus) {
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

                $supported = $ext->addChild("supported");
                foreach ($glExt->getSupportedDrivers() as $glDriver) {
                    $driver = $supported->addChild($glDriver->getName());
                    $hintId = $glDriver->getHintIdx();
                    if ($hintId !== -1) {
                        $driver->addAttribute("hint", $hints->allHints[$hintId]);
                    }
                }
            }
        }
        
        if ($urlCache) {
            $urlCache->save();
        }

        $xmlPath = \Mesamatrix::path(\Mesamatrix::$config->getValue("info", "xml_file"));

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;

        file_put_contents($xmlPath, $dom->saveXML());
        //$xml->asXML($xmlPath);
    }
}
