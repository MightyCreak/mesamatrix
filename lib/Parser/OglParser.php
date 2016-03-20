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
    public function parse($filename, $commit = null) {
        $handle = fopen($filename, "r");
        if ($handle === FALSE) {
            return NULL;
        }

        $matrix = $this->parseStream($handle, $commit);
        fclose($handle);
        return $matrix;
    }

    public function parseContent($content, $commit = null) {
        $handle = fopen("php://memory", "r+");
        fwrite($handle, $content);
        rewind($handle);
        $matrix = $this->parseStream($handle, $commit);
        fclose($handle);
        return $matrix;
    }

    /**
     * Parse a stream of GL3.txt.
     *
     * @param $handle The stream handle.
     * @param \Mesamatrix\Git\Commit $commit The commit for the stream.
     * @return A \Mesamatrix\Parser\OglMatrix.
     */
    public function parseStream($handle, $commit = null) {
        $matrix = new OglMatrix();

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
                // Remove 2 because of the first two spaces on each lines.
                $lineWidth = strlen("Feature") + strlen($matches[1]) - 2;
                $reExtension = "/^  (.{1,".$lineWidth."})[ ]+([^\(]+)(\((.*)\))?$/";
                $line = fgets($handle);
                continue;
            }

            if (preg_match($reVersion, $line, $matches) === 1) {
                $glName = $matches[1] === 'GL' ? 'OpenGL' : 'OpenGL ES';
                $glVersion = $matrix->getGlVersionByName($glName, $matches[3]);
                if (!$glVersion) {
                    $glVersion = new OglVersion($glName, $matches[3], $matches[4],
                                                $matches[6], $matrix->getHints());
                    $matrix->addGlVersion($glVersion);
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
                            $newExtension = new OglExtension($matches[1], $matches[2], $matrix->getHints(), $supportedDrivers);
                            $lastExt = $glVersion->addExtension($newExtension, $commit);
                        }
                        else {
                            // Add the sub-extension.
                            $newSubExtension = new OglExtension($matches[1], $matches[2], $matrix->getHints(), $supportedDrivers);
                            $lastExt->addSubExtension($newSubExtension, $commit);
                        }
                    }

                    $line = fgets($handle);
                }

                $line = $this->skipEmptyLines($line, $handle);

                while ($line !== FALSE && preg_match($reNote, $line, $matches) === 1) {
                    $idx = array_search($matches[1], $matrix->getHints()->allHints);
                    if ($idx !== FALSE) {
                        $matrix->getHints()->allHints[$idx] = $matches[2];
                    }

                    $line = fgets($handle);
                }
            }
            else {
                $line = fgets($handle);
            }
        }

        return $matrix;
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
