<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014 Romain "Creak" Failliot.
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

namespace Mesamatrix\Parser;

class OglParser
{
    private $hints;
    private $matrix;

    public function __construct($hints, $matrix) {
        $this->hints = $hints;
        $this->matrix = $matrix;
    }

    public function parse($filename, $commit = null) {
        $handle = fopen($filename, "r");
        if ($handle === FALSE) {
            return NULL;
        }

        $this->parseStream($handle, $commit);
        fclose($handle);
    }

    public function parseContent($content, $commit = null) {
        $handle = fopen("php://memory", "r+");
        fwrite($handle, $content);
        rewind($handle);
        $this->parseStream($handle, $commit);
        fclose($handle);
    }

    public function parseStream($handle, $commit = null) {
        // Regexp patterns.
        $reTableHeader = "/^Feature([ ]+)Status/";
        $reVersion = "/^(GL(ES)?) ?([[:digit:]]+\.[[:digit:]]+), (GLSL( ES)?) ([[:digit:]]+\.[[:digit:]]+)/";
        $reAllDone = "/ --- all DONE: (.*)/";
        $reExtension = "/^[^.]+$/";
        $reNote = "/^(\(.+\)) (.*)$/";

        $ignoreHints = array("all drivers");

        $line = fgets($handle);
        while ($line !== FALSE) {
            if (preg_match($reTableHeader, $line, $matches) === 1) {
                // Should be $lineWidth-2, but the file has a variable length in column 1
                $lineWidth = strlen("Feature") + strlen($matches[1]);
                $reExtension = "/^  (.{1,".$lineWidth."})[ ]+([^\(]+)(\((.*)\))?$/";
                $line = fgets($handle);
                continue;
            }

            if (preg_match($reVersion, $line, $matches) === 1) {
                $glVersion = $this->matrix->getGlVersionByName('Open'.$matches[1], $matches[3]);
                if (!$glVersion) {
                    $glVersion = new OglVersion('Open'.$matches[1], $matches[3], $matches[4], $matches[6], $this->hints);
                    $this->matrix->addGlVersion($glVersion);
                }

                $allSupportedDrivers = array();
                if (preg_match($reAllDone, $line, $matches) === 1) {
                    $this->mergeDrivers($allSupportedDrivers, explode(", ", $matches[1]));
                }

                $line = $this->skipEmptyLines(fgets($handle), $handle);

                $lastExt = null;
                $parentDrivers = NULL;
                while ($line !== FALSE && $line !== "\n") {
                    if (preg_match($reExtension, $line, $matches) === 1) {
                        $supportedDrivers = $allSupportedDrivers;
                        $matches[1] = trim($matches[1]);
                        $isSubExt = $matches[1][0] === "-" && $lastExt !== null;
                        if ($isSubExt) {
                            $this->mergeDrivers($supportedDrivers, $parentDrivers);
                        }

                        $matches[2] = trim($matches[2]);
                        $isDone = strncmp($matches[2], "DONE", strlen("DONE")) === 0;
                        if ($isDone && !isset($matches[3])) {
                            $this->mergeDrivers($supportedDrivers, Constants::$allDrivers);
                        }
                        elseif ($isDone && isset($matches[4])) {
                            $driverFound = FALSE;
                            $driversList = explode(", ", $matches[4]);
                            foreach ($driversList as $currentDriver) {
                                if ($this->isInDriversArray($currentDriver)) {
                                    $this->mergeDrivers($supportedDrivers, [$currentDriver]);
                                    $driverFound = TRUE;
                                }
                            }
                            if (!$driverFound && !empty($matches[4])) {
                                if (!in_array($matches[4], $ignoreHints)) {
                                    $matches[2] = $matches[2]." ".$matches[4]."";
                                }
                                $this->mergeDrivers($supportedDrivers, Constants::$allDrivers);
                            }
                        }
                        elseif (isset($matches[4]) && !empty($matches[4])) {
                            $matches[2] = $matches[2]." (".$matches[4].")";
                        }

                        if (!$isSubExt) {
                            // Set supported drivers for future sub-extensions.
                            $parentDrivers = $supportedDrivers;

                            // Add the extension.
                            $newExtension = new OglExtension($matches[1], $matches[2], $this->hints, $supportedDrivers);
                            $lastExt = $glVersion->addExtension($newExtension, $commit);
                        }
                        else {
                            // Add the sub-extension.
                            $newSubExtension = new OglExtension($matches[1], $matches[2], $this->hints, $supportedDrivers);
                            $lastExt->addSubExtension($newSubExtension, $commit);
                        }
                    }

                    $line = fgets($handle);
                }

                $line = $this->skipEmptyLines($line, $handle);

                while ($line !== FALSE && preg_match($reNote, $line, $matches) === 1) {
                    $idx = array_search($matches[1], $this->hints->allHints);
                    if ($idx !== FALSE) {
                        $this->hints->allHints[$idx] = $matches[2];
                    }

                    $line = fgets($handle);
                }
            }
            else {
                $line = fgets($handle);
            }
        }
    }

    /**
     * Parse a pre-parsed, XML formatted commit.
     *
     * @param \SimpleXMLElement $mesa The root element of the XML file.
     * @param \Mesamatrix\Git\Commit $commit The commit used by the parser.
     */
    public function parseXmlCommit(\SimpleXMLElement $mesa, \Mesamatrix\Git\Commit $commit) {
        foreach ($mesa->gl as $gl) {
            $glName = (string) $gl['name'];
            $glVersion = (string) $gl['version'];

            $glSection = $this->matrix->getGlVersionByName($glName, $glVersion);
            if (!$glSection) {
                $glslName = (string) $gl->glsl['name'];
                $glslVersion = (string) $gl->glsl['version'];

                $glSection = new OglVersion($glName, $glVersion, $glslName, $glslVersion, $this->hints);
                $this->matrix->addGlVersion($glSection);
            }

            // Add/merge new items in the matrix.
            foreach ($gl->extension as $extension) {
                // Create new extension.
                $extName = (string) $extension['name'];
                $extStatus = (string) $extension->mesa['status'];
                $extHint = (string) $extension->mesa['hint'];
                $newExtension = new OglExtension($extName, '', $this->hints, array());
                $newExtension->setStatus($extStatus);
                $newExtension->setHint($extHint);
                foreach ($extension->supported->children() as $driver) {
                    // Create new supported driver.
                    $driverName = $driver->getName();
                    $driverHint = (string) $driver['hint'];
                    $driver = new OglSupportedDriver($driverName, $this->hints);
                    $driver->setHint($driverHint);
                    $newExtension->addSupportedDriver($driver);
                }

                // Add the extension.
                $glExt = $glSection->addExtension($newExtension, $commit);
                unset($extName, $extStatus, $extHint, $extDrivers, $newExtension);

                foreach ($extension->subextension as $subextension) {
                    // Create new sub-extension.
                    $subExtName = (string) $subextension['name'];
                    $subExtStatus = (string) $subextension->mesa['status'];
                    $subExtHint = (string) $subextension->mesa['hint'];
                    $newSubExtension = new OglExtension($subExtName, '', $this->hints, array());
                    $newSubExtension->setStatus($subExtStatus);
                    $newSubExtension->setHint($subExtHint);
                    foreach ($subextension->supported->children() as $driver) {
                        // Create new supported driver.
                        $driverName = $driver->getName();
                        $driverHint = (string) $driver['hint'];
                        $driver = new OglSupportedDriver($driverName, $this->hints);
                        $driver->setHint($driverHint);
                        $newSubExtension->addSupportedDriver($driver);
                    }

                    // Add the sub-extension.
                    $glSubExt = $glExt->addSubExtension($newSubExtension, $commit);
                }
            }
        }
    }

    private function skipEmptyLines($curLine, $handle) {
        while ($curLine !== FALSE && $curLine === "\n") {
            $curLine = fgets($handle);
        }

        return $curLine;
    }

    private function isInDriversArray($name) {
        foreach (Constants::$allDrivers as $driverName) {
            if (strncmp($name, $driverName, strlen($driverName)) === 0) {
                return TRUE;
            }
        }

        return FALSE;
    }

    private function getDriverName($name) {
        foreach (Constants::$allDrivers as $driver) {
            $driverLen = strlen($driver);
            if (strncmp($name, $driver, $driverLen) === 0) {
                return $driver;
            }
        }

        return NULL;
    }

    private function mergeDrivers(array &$dst, array $src) {
        foreach ($src as $srcDriver) {
            $driverName = $this->getDriverName($srcDriver);

            $i = 0;
            $numDstDrivers = count($dst);
            while ($i < $numDstDrivers && strncmp($dst[$i], $driverName, strlen($driverName)) !== 0) {
                $i++;
            }

            if ($i < $numDstDrivers) {
                $dst[$i] = $srcDriver;
            }
            else {
                $dst[] = $srcDriver;
            }
        }

        return $dst;
    }
};
