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
    private $commit;

    public function __construct($hints, $matrix, $commit = null) {
        $this->hints = $hints;
        $this->matrix = $matrix;
        $this->commit = $commit;
    }

    public function parse($filename) {
        $handle = fopen($filename, "r");
        if ($handle === FALSE) {
            return NULL;
        }

        $ret = $this->parse_stream($handle);
        fclose($handle);
        return $ret;
    }

    public function parse_content($content) {
        $handle = fopen("php://memory", "r+");
        fwrite($handle, $content);
        rewind($handle);
        $ret = $this->parse_stream($handle);
        fclose($handle);
        return $ret;
    }

    public function parse_stream($handle) {
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
                $glVersion = $this->matrix->getGlVersionByName($matches[1], $matches[3]);
                if (!$glVersion) {
                    $glVersion = new OglVersion($matches[1], $matches[3], $matches[4], $matches[6], $this->hints);
                    $this->matrix->addGlVersion($glVersion);
                }

                $allSupportedDrivers = array();
                if (preg_match($reAllDone, $line, $matches) === 1) {
                    $this->mergeDrivers($allSupportedDrivers, explode(", ", $matches[1]));
                }

                $line = $this->skipEmptyLines(fgets($handle), $handle);

                $parentDrivers = NULL;
                while ($line !== FALSE && $line !== "\n") {
                    if (preg_match($reExtension, $line, $matches) === 1) {
                        $supportedDrivers = $allSupportedDrivers;
                        $matches[1] = trim($matches[1]);
                        if ($matches[1][0] === "-") {
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

                        if ($matches[1][0] !== "-") {
                            $parentDrivers = $supportedDrivers;
                        }

                        $glVersion->addExtension($matches[1], $matches[2], $supportedDrivers, $this->commit);
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

        return $this->matrix;
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
