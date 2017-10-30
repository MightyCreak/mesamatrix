<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014-2017 Romain "Creak" Failliot.
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
    public function parseStream($handle, \Mesamatrix\Git\Commit $commit = null) {
        $matrix = new OglMatrix();

        // Regexp patterns.
        $reTableHeader = "/^(Feature[ ]+)Status/";
        $reVersion = "/^(GL(ES)?) ?([[:digit:]]+\.[[:digit:]]+), (GLSL( ES)?) ([[:digit:]]+\.[[:digit:]]+)/";

        // Skip header lines.
        $line = fgets($handle);
        while ($line !== FALSE && preg_match($reTableHeader, $line, $matches) !== 1) {
            $line = fgets($handle);
        }

        // Get extension line regexp.
        if ($line !== FALSE) {
            // Remove 2 because of the first two spaces on each lines.
            $lineWidth = strlen($matches[1]) - 2;
            $this->reExtension = "/^  (.{1,".$lineWidth."})[ ]+([^\(]+)(\((.*)\))?$/";

            // Go to next line and start parsing.
            $line = fgets($handle);
            while ($line !== FALSE) {
                // Find version line (i.e. "GL 3.0, GLSL 1.30 ...").
                if (preg_match($reVersion, $line, $matches) === 1) {
                    // Get or create new OpenGL version.
                    $glName = $matches[1] === 'GL' ? 'OpenGL' : 'OpenGL ES';
                    $glVersion = $matrix->getGlVersionByName($glName, $matches[3]);
                    if (!$glVersion) {
                        $glVersion = new OglVersion($glName, $matches[3], $matches[4],
                            $matches[6], $matrix->getHints());
                        $matrix->addGlVersion($glVersion);
                    }

                    // Set "all DONE" drivers.
                    $allSupportedDrivers = array();
                    if (preg_match(self::RE_ALL_DONE, $line, $matches) === 1) {
                        $this->mergeDrivers($allSupportedDrivers, explode(", ", $matches[1]));
                    }

                    $line = $this->parseSection($glVersion, $matrix, $commit, $handle, $allSupportedDrivers);
                }
                else if($line === self::OTHER_OFFICIAL_GL_EXTENSIONS) {
                    $glName = "Extensions that are not part of any OpenGL or OpenGL ES version";
                    $glVersion = $matrix->getGlVersionByName($glName, NULL);
                    if (!$glVersion) {
                        $glVersion = new OglVersion($glName, NULL, NULL,
                            NULL, $matrix->getHints());
                        $matrix->addGlVersion($glVersion);
                    }

                    $line = $this->parseSection($glVersion, $matrix, $commit, $handle);
                }
                else {
                    //print("Unrecognized line: ".$line);
                    $line = fgets($handle);
                }
            }
        }

        // Parsing is done, now solve potential dependencies.
        $matrix->solveExtensionDependencies();

        return $matrix;
    }

    /**
     * Parse an OpenGL section.
     *
     * @param \Mesamatrix\Parser\OglVersion $glVersion The version section to feed during parsing.
     * @param \Mesamatrix\Parser\OglMatrix $matrix The matrix to feed during parsing.
     * @param \Mesamatrix\Git\Commit $commit The commit of this file.
     * @param $handle The file handle.
     * @param array() $allSupportedDrivers Drivers that already support all the extension in the section.
     *
     * @return The next line unparsed.
     */
    private function parseSection(\Mesamatrix\Parser\OglVersion $glVersion,
                                  \Mesamatrix\Parser\OglMatrix $matrix,
                                  \Mesamatrix\Git\Commit $commit,
                                  $handle,
                                  $allSupportedDrivers = array()) {
        $line = $this->skipEmptyLines(fgets($handle), $handle);

        // Parse OpenGL version extensions.
        $lastExt = null;
        $parentDrivers = NULL;
        while ($line !== FALSE && $line !== "\n") {
            if (preg_match($this->reExtension, $line, $matches) === 1) {
                // $matches indices:
                //   [1]: extension name
                //   [2]: DONE, in progress, not started, ...
                //   [3]: Whatever is after [2], including parenthesis.
                //   [4]: What's inside the parenthesis in [3].

                // Get supported drivers (from "all DONE").
                $supportedDrivers = $allSupportedDrivers;

                // Is sub-extension?
                $matches[1] = trim($matches[1]);
                $isSubExt = $matches[1][0] === "-" && $lastExt !== null;
                if ($isSubExt) {
                    // Merge with parent extension supported drivers.
                    $this->mergeDrivers($supportedDrivers, $parentDrivers);
                }

                // Get the status and eventual hint.
                $matches[2] = trim($matches[2]);
                $preHint = "";
                if (strncmp($matches[2], "DONE", strlen("DONE")) === 0) {
                    $status = Constants::STATUS_DONE;
                    $preHint = substr($matches[2], strlen("DONE") + 1);
                }
                elseif (strncmp($matches[2], "not started", strlen("not started")) === 0) {
                    $status = Constants::STATUS_NOT_STARTED;
                    $preHint = substr($matches[2], strlen("not started") + 1);
                }
                else {
                    $status = Constants::STATUS_IN_PROGRESS;
                    $preHint = $matches[2];
                }

                $inHint = "";
                if ($status === Constants::STATUS_DONE) {
                    if (!isset($matches[3])) {
                        // Done and nothing else precised, it's done for all drivers.
                        $this->mergeDrivers($supportedDrivers, Constants::GL_ALL_DRIVERS);
                    }
                    elseif (isset($matches[4])) {
                        // Done but there are parenthesis after.
                        $useHint = FALSE;
                        $hintDrivers = $this->getDriversFromHint($matches[4], $useHint);
                        if ($hintDrivers !== NULL) {
                            $this->mergeDrivers($supportedDrivers, $hintDrivers);
                        }
                        if ($useHint) {
                            $inHint = $matches[4];
                        }
                    }
                }
                elseif ($status === Constants::STATUS_IN_PROGRESS) {
                    // In progress.
                    if (!empty($matches[4])) {
                        // There's something precised in the parenthesis.
                        $inHint = $matches[4];
                    }
                }
                else /*if ($status === Constants::STATUS_NOT_STARTED)*/ {
                    if (!empty($matches[4])) {
                        // Not done, but something is precised in the parenthesis.
                        $inHint = $matches[4];
                    }
                }

                // Get hint.
                if (!empty($preHint) && !empty($inHint)) {
                    $hint = $preHint." (".$inHint.")";
                }
                elseif (!empty($preHint)) {
                    $hint = $preHint;
                }
                else {
                    $hint = $inHint;
                }

                if (!$isSubExt) {
                    // Set supported drivers for future sub-extensions.
                    $parentDrivers = $supportedDrivers;

                    // Add the extension.
                    $newExtension = new OglExtension($matches[1], $status, $hint, $matrix->getHints(), $supportedDrivers);
                    $lastExt = $glVersion->addExtension($newExtension, $commit);
                }
                else {
                    // Add the sub-extension.
                    $newSubExtension = new OglExtension($matches[1], $status, $hint, $matrix->getHints(), $supportedDrivers);
                    $lastExt->addSubExtension($newSubExtension, $commit);
                }
            }

            // Get next line.
            $line = fgets($handle);
        }

        $line = $this->skipEmptyLines($line, $handle);

        // Parse notes (i.e. "(*) note").
        while ($line !== FALSE && preg_match(self::RE_NOTE, $line, $matches) === 1) {
            $idx = array_search($matches[1], $matrix->getHints()->allHints);
            if ($idx !== FALSE) {
                $matrix->getHints()->allHints[$idx] = $matches[2];
            }

            // Get next line.
            $line = fgets($handle);
        }

        return $line;
    }

    private function skipEmptyLines($curLine, $handle) {
        while ($curLine !== FALSE && $curLine === "\n") {
            $curLine = fgets($handle);
        }

        return $curLine;
    }

    private function isInDriversArray($name) {
        foreach (Constants::GL_ALL_DRIVERS as $driverName) {
            if (strncmp($name, $driverName, strlen($driverName)) === 0) {
                return TRUE;
            }
        }

        return FALSE;
    }

    private function getDriverName($name) {
        foreach (Constants::GL_ALL_DRIVERS as $driver) {
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

    /**
     * Parse the hint and extract the drivers from it.
     *
     * @param string $hint The hint to test.
     * @param bool $useHint[out] Should the hint be displayed?
     *
     * @return array() The drivers list, or NULL.
     */
    private function getDriversFromHint($hint, &$useHint) {
        if (empty($hint)) {
            return NULL;
        }

        $useHint = FALSE;

        // Find drivers considering the hint as a list of drivers.
        $drivers = array();
        $driversList = explode(", ", $hint);
        foreach ($driversList as $hintDriver) {
            if ($this->isInDriversArray($hintDriver)) {
                $drivers[] = $hintDriver;
            }
        }

        if (count($drivers) > 0) {
            // Driver found.
            return $drivers;
        }

        // Is the hint saying it's supporting all drivers?
        foreach (Constants::RE_ALL_DRIVERS_HINTS as $reAllDriversHint) {
            if (preg_match($reAllDriversHint[0], $hint) === 1) {
                $useHint = $reAllDriversHint[1];
                return Constants::GL_ALL_DRIVERS;
            }
        }

        // Is the hint saying it depends on something else?
        foreach (Constants::RE_DEP_DRIVERS_HINTS as $reDepDriversHint) {
            if (preg_match($reDepDriversHint[0], $hint) === 1) {
                $useHint = $reDepDriversHint[1];
                return NULL;
            }
        }

        return NULL;
    }

    private $reExtension = "";

    const OTHER_OFFICIAL_GL_EXTENSIONS =
        "Khronos, ARB, and OES extensions that are not part of any OpenGL or OpenGL ES version:\n";

    const RE_ALL_DONE = "/ -+ all DONE: (.*)/i";
    const RE_NOTE = "/^(\(.+\)) (.*)$/";
};
