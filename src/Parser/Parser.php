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

use Mesamatrix\Mesamatrix;

class Parser
{
    public function parseFile($filename): Matrix
    {
        $handle = fopen($filename, "r");
        if ($handle === false) {
            return null;
        }

        $matrix = $this->parseStream($handle);
        fclose($handle);

        return $matrix;
    }

    public function parseContent(string $content): Matrix
    {
        $handle = fopen("php://memory", "r+");
        fwrite($handle, $content);
        rewind($handle);
        $matrix = $this->parseStream($handle);
        fclose($handle);

        return $matrix;
    }

    /**
     * Parse a stream of features.txt.
     *
     * @param $handle The stream handle.
     * @return Matrix The matrix.
     */
    public function parseStream($handle): Matrix
    {
        $matrix = new Matrix();

        // Regexp patterns.
        $reTableHeader = "/^(Feature[ ]+)Status/";
        $reGlVersion = "/^(GL(ES)?) ?([[:digit:]]+\.[[:digit:]]+), (GLSL( ES)?) ([[:digit:]]+\.[[:digit:]]+)/";
        $reVkVersion = "/^Vulkan ([[:digit:]]+\.[[:digit:]]+)/";
        $reOpenClVersion = "/^OpenCL ([[:digit:]]+\.[[:digit:]]+)/";

        // Skip header lines.
        $line = fgets($handle);
        while ($line !== false && preg_match($reTableHeader, $line, $matches) !== 1) {
            $line = fgets($handle);
        }

        // Get extension line regexp.
        if ($line !== false) {
            // Remove 2 because of the first two spaces on each lines.
            $lineWidth = strlen($matches[1]) - 2;
            $this->reExtension = "/^  (.{1," . $lineWidth . "})[ ]+([^\(]+)(\((.*)\))?$/";

            // Go to next line and start parsing.
            $line = fgets($handle);
            while ($line !== false) {
                // Find version line (i.e. "GL 3.0, GLSL 1.30 ...").
                $apiVersion = null;
                if (preg_match($reGlVersion, $line, $matches) === 1) {
                    // Get or create new OpenGL version.
                    $glName = $matches[1] === 'GL' ? Constants::GL_NAME : Constants::GLES_NAME;
                    $apiVersion = $matrix->getApiVersionByName($glName, $matches[3]);
                    if (!$apiVersion) {
                        $apiVersion = new ApiVersion($glName, $matches[3], $matches[4], $matches[6], $matrix->getHints());
                        $matrix->addApiVersion($apiVersion);
                    }
                } elseif ($line === self::OTHER_OFFICIAL_GL_EXTENSIONS) {
                    $glName = Constants::GL_OR_ES_EXTRA_NAME;
                    $apiVersion = $matrix->getApiVersionByName($glName, null);
                    if (!$apiVersion) {
                        $apiVersion = new ApiVersion($glName, null, null, null, $matrix->getHints());
                        $matrix->addApiVersion($apiVersion);
                    }
                } elseif (preg_match($reVkVersion, $line, $matches) === 1) {
                    $vkName = Constants::VK_NAME;
                    $apiVersion = $matrix->getApiVersionByName($vkName, $matches[1]);
                    if (!$apiVersion) {
                        $apiVersion = new ApiVersion($vkName, $matches[1], null, null, $matrix->getHints());
                        $matrix->addApiVersion($apiVersion);
                    }
                } elseif ($line === self::OTHER_OFFICIAL_VK_EXTENSIONS) {
                    $vkName = Constants::VK_EXTRA_NAME;
                    $apiVersion = $matrix->getApiVersionByName($vkName, null);
                    if (!$apiVersion) {
                        $apiVersion = new ApiVersion($vkName, null, null, null, $matrix->getHints());
                        $matrix->addApiVersion($apiVersion);
                    }
                } elseif (preg_match($reOpenClVersion, $line, $matches) === 1) {
                    $openClName = Constants::OPENCL_NAME;
                    $apiVersion = $matrix->getApiVersionByName($openClName, $matches[1]);
                    if (!$apiVersion) {
                        $apiVersion = new ApiVersion($openClName, $matches[1], null, null, $matrix->getHints());
                        $matrix->addApiVersion($apiVersion);
                    }
                } elseif ($line === self::OTHER_OFFICIAL_OPENCL_EXTENSIONS) {
                    $openClName = Constants::OPENCL_EXTRA_NAME;
                    $apiVersion = $matrix->getApiVersionByName($openClName, null);
                    if (!$apiVersion) {
                        $apiVersion = new ApiVersion($openClName, null, null, null, $matrix->getHints());
                        $matrix->addApiVersion($apiVersion);
                    }
                } elseif ($line === self::OTHER_VENDOR_SPECIFIC_OPENCL_EXTENSIONS) {
                    $openClName = Constants::OPENCL_VENDOR_SPECIFIC_NAME;
                    $apiVersion = $matrix->getApiVersionByName($openClName, null);
                    if (!$apiVersion) {
                        $apiVersion = new ApiVersion($openClName, null, null, null, $matrix->getHints());
                        $matrix->addApiVersion($apiVersion);
                    }
                } else {
                    //print("Unrecognized line: ".$line);
                    $line = fgets($handle);
                    continue;
                }

                if ($apiVersion) {
                    // Get all the drivers for this API.
                    $this->apiDrivers = $apiVersion->getAllApiDrivers();

                    // Set "all DONE" drivers.
                    $allSupportedDrivers = array();
                    if (preg_match(self::RE_ALL_DONE, $line, $matches) === 1) {
                        $this->mergeDrivers($allSupportedDrivers, explode(", ", $matches[1]));
                    }

                    // Parse section.
                    $line = $this->parseSection($apiVersion, $matrix, $handle, $allSupportedDrivers);
                }
            }
        }

        // Parsing is done, now solve potential dependencies.
        $matrix->solveExtensionDependencies();

        return $matrix;
    }

    /**
     * Parse an API section.
     *
     * Special case for section without any extension: add a fake one called
     * "All extensions". This is needed for Vulkan 1.0 (until there is a better
     * parser).
     *
     * @param ApiVersion $apiVersion The version section to feed during parsing.
     * @param Matrix $matrix The matrix to feed during parsing.
     * @param $handle The file handle.
     * @param array() $allSupportedDrivers Drivers that already support all the extension in the section.
     *
     * @return The next line unparsed.
     */
    private function parseSection(
        ApiVersion $apiVersion,
        Matrix $matrix,
        $handle,
        $allSupportedDrivers = array()
    ) {
        $line = $this->skipEmptyLines(fgets($handle), $handle);

        // Verify the line is indented.
        if (preg_match("/^  [^ ]/", $line) === 0) {
            // Special case: no indentation means no extension, add a fake one.
            if ($apiVersion->getNumExtensions() === 0) {
                $fakeExtension = new Extension("All extensions", Constants::STATUS_DONE, "", $matrix->getHints(), $allSupportedDrivers, $this->apiDrivers);
                $apiVersion->addExtension($fakeExtension);
            }

            return $line;
        }

        // Parse API version extensions.
        $lastExt = null;
        $parentDrivers = null;
        while ($line !== false && $line !== "\n") {
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
                } elseif (strncmp($matches[2], "not started", strlen("not started")) === 0) {
                    $status = Constants::STATUS_NOT_STARTED;
                    $preHint = substr($matches[2], strlen("not started") + 1);
                } else {
                    $status = Constants::STATUS_IN_PROGRESS;
                    $preHint = $matches[2];
                }

                $inHint = "";
                if ($status === Constants::STATUS_DONE) {
                    if (!isset($matches[3])) {
                        // Done and nothing else precised, it's done for all drivers.
                        $this->mergeDrivers($supportedDrivers, $this->apiDrivers);
                    } elseif (isset($matches[4])) {
                        // Done but there are parenthesis after.
                        $useHint = null;
                        $hintDrivers = $this->getDriversFromHint($matches[4], $useHint);
                        if ($hintDrivers !== null) {
                            $this->mergeDrivers($supportedDrivers, $hintDrivers);
                        }

                        if ($useHint !== null) {
                            $inHint = $useHint;
                        }
                    }
                } elseif ($status === Constants::STATUS_IN_PROGRESS) {
                    // In progress.
                    if (!empty($matches[4])) {
                        // There's something precised in the parenthesis.
                        $inHint = $matches[4];
                    }
                } else /*if ($status === Constants::STATUS_NOT_STARTED)*/ {
                    if (!empty($matches[4])) {
                        // Not done, but something is precised in the parenthesis.
                        $inHint = $matches[4];
                    }
                }

                // Get hint.
                if (!empty($preHint) && !empty($inHint)) {
                    $hint = $preHint . " (" . $inHint . ")";
                } elseif (!empty($preHint)) {
                    $hint = $preHint;
                } else {
                    $hint = $inHint;
                }

                if (!$isSubExt) {
                    // Set supported drivers for future sub-extensions.
                    $parentDrivers = $supportedDrivers;

                    // Add the extension.
                    $newExtension = new Extension($matches[1], $status, $hint, $matrix->getHints(), $supportedDrivers, $this->apiDrivers);
                    $apiVersion->addExtension($newExtension);
                    $lastExt = $newExtension;
                } else {
                    // Add the sub-extension.
                    $newSubExtension = new Extension($matches[1], $status, $hint, $matrix->getHints(), $supportedDrivers, $this->apiDrivers);
                    $lastExt->addSubExtension($newSubExtension);
                }
            }

            // Get next line.
            $line = fgets($handle);
        }

        $line = $this->skipEmptyLines($line, $handle);

        // Parse notes (i.e. "(*) note").
        while ($line !== false && preg_match(self::RE_NOTE, $line, $matches) === 1) {
            $idx = array_search($matches[1], $matrix->getHints()->allHints);
            if ($idx !== false) {
                $matrix->getHints()->allHints[$idx] = $matches[2];
            }

            // Get next line.
            $line = fgets($handle);
        }

        return $line;
    }

    private function skipEmptyLines($curLine, $handle)
    {
        while ($curLine !== false && $curLine === "\n") {
            $curLine = fgets($handle);
        }

        return $curLine;
    }

    private function isInDriversArray(string $name): bool
    {
        foreach ($this->apiDrivers as $driverName) {
            if (strncmp($name, $driverName, strlen($driverName)) === 0) {
                return true;
            }
        }

        return false;
    }

    private function getDriverName(string $name): ?string
    {
        foreach ($this->apiDrivers as $driver) {
            $driverLen = strlen($driver);
            if (strncmp($name, $driver, $driverLen) === 0) {
                return $driver;
            }
        }

        return null;
    }

    private function mergeDrivers(array &$dst, array $src): array
    {
        foreach ($src as $srcDriver) {
            $driverName = $this->getDriverName($srcDriver);

            $i = 0;
            $numDstDrivers = count($dst);
            while ($i < $numDstDrivers && strncmp($dst[$i], $driverName, strlen($driverName)) !== 0) {
                $i++;
            }

            if ($i < $numDstDrivers) {
                $dst[$i] = $srcDriver;
            } else {
                $dst[] = $srcDriver;
            }
        }

        return $dst;
    }

    /**
     * Parse the hint and extract the drivers from it.
     *
     * @param string $hint The hint to test.
     * @param string $useHint[out] Hint to use; null otherwise.
     *
     * @return array|null The drivers list, or null.
     */
    private function getDriversFromHint($hintStr, &$useHint): ?array
    {
        if (empty($hintStr)) {
            return null;
        }

        $useHint = null;

        // Is the hint saying it's supporting all drivers?
        foreach (Constants::RE_ALL_DRIVERS_HINTS as $reAllDriversHint) {
            if (preg_match($reAllDriversHint[0], $hintStr) === 1) {
                if ($reAllDriversHint[1] === true) {
                    $useHint = $hintStr;
                }

                return $this->apiDrivers;
            }
        }

        // Find drivers considering the hint as a list of drivers.
        $drivers = array();
        $hintsList = explode(", ", $hintStr);
        foreach ($hintsList as $hintItem) {
            if ($this->isInDriversArray($hintItem)) {
                $drivers[] = $hintItem;
            } else {
                // Is the hint saying it depends on something else?
                foreach (Constants::RE_DEP_DRIVERS_HINTS as $reDepDriversHint) {
                    if (preg_match($reDepDriversHint[0], $hintItem) === 1) {
                        if ($reAllDriversHint[1] === true) {
                            if ($useHint !== null) {
                                Mesamatrix::$logger->warning('Unhandled situation: more than one extension dependency');
                            }

                            $useHint = $hintItem;
                        }
                    }
                }
            }
        }

        return !empty($drivers) ? $drivers : null;
    }

    private $reExtension = "";
    private $apiDrivers = null;

    private const OTHER_OFFICIAL_GL_EXTENSIONS =
        "Khronos, ARB, and OES extensions that are not part of any OpenGL or OpenGL ES version:\n";
    private const OTHER_OFFICIAL_VK_EXTENSIONS =
        "Khronos extensions that are not part of any Vulkan version:\n";
    private const OTHER_OFFICIAL_OPENCL_EXTENSIONS =
        "Khronos, and EXT extensions that are not part of any OpenCL version:\n";
    private const OTHER_VENDOR_SPECIFIC_OPENCL_EXTENSIONS =
        "Vendor specific extensions that are not part of any OpenCL version:\n";

    private const RE_ALL_DONE = "/ -+ all DONE: (.*)/i";
    private const RE_NOTE = "/^(\(.+\)) (.*)$/";
}
