<?php

/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014-2021 Romain "Creak" Failliot.
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

namespace Mesamatrix\Leaderboard;

use Mesamatrix\Parser\Constants;

class Leaderboard
{
    /**
     * Leaderboard default constructor.
     */
    public function __construct(bool $useVersions)
    {
        $this->apiVersions = array();
        $this->useVersions = $useVersions;
    }

    /**
     * Load leaderboard from data.
     *
     * @param SimpleXMLElement $xmlApi The XML element of the API.
     */
    public function load(\SimpleXMLElement $xmlApi)
    {
        $this->loadApi($xmlApi);

        // Sort by API versions descending.
        usort($this->apiVersions, function ($a, $b) {
            // Sort by API name, then API version descending.
            if ($a->getName() === $b->getName()) {
                $diff = (float) $b->getVersion() - (float) $a->getVersion();
                if ($diff === 0) {
                    return 0;
                } else {
                    return $diff < 0 ? -1 : 1;
                }
            } elseif (
                $a->getName() === Constants::GL_NAME ||
                $a->getName() === Constants::VK_NAME ||
                $a->getName() === Constants::CLOVER_OPENCL_NAME ||
                $a->getName() === Constants::RUSTICL_OPENCL_NAME
            ) {
                return -1;
            } else {
                return 1;
            }
        });
    }

    /**
     * Load all the versions from a given API.
     *
     * @param SimpleXMLElement $xmlApi The XML tag for the wanted API.
     */
    private function loadApi(\SimpleXMLElement $xmlApi)
    {
        foreach ($xmlApi->versions->version as $xmlVersion) {
            // Count total extensions and sub-extensions.
            $numTotalExts = count($xmlVersion->extensions->extension);
            foreach ($xmlVersion->extensions->extension as $xmlExt) {
                $xmlSubExts = $xmlExt->xpath('./subextensions/subextension');
                $numTotalExts += count($xmlSubExts);
            }

            $lbApiVersion = $this->createApiVersion(
                (string) $xmlVersion["name"],
                (string) $xmlVersion["version"],
                $numTotalExts
            );

            // Count done mesa extensions and sub-extensions.
            $numDoneExts = 0;
            foreach ($xmlVersion->extensions->extension as $xmlExt) {
                // Extension.
                if ($xmlExt->mesa["status"] == Constants::STATUS_DONE) {
                    $numDoneExts += 1;
                }

                // Sub-extensions.
                $xmlSubExts = $xmlExt->xpath('./subextensions/subextension');
                foreach ($xmlSubExts as $xmlSubExt) {
                    if ($xmlSubExt->mesa["status"] == Constants::STATUS_DONE) {
                        $numDoneExts += 1;
                    }
                }
            }

            $lbApiVersion->addDriver("mesa", $numDoneExts);

            // Count done extensions and sub-extensions for each drivers.
            foreach ($xmlApi->vendors->vendor as $vendor) {
                foreach ($vendor->drivers->driver as $driver) {
                    $driverName = (string) $driver["name"];

                    // Count done extensions and sub-extensions for $driverName.
                    $numDoneExts = 0;
                    foreach ($xmlVersion->extensions->extension as $xmlExt) {
                        // Extension.
                        $xmlSupportedDrivers = $xmlExt->xpath("./supported-drivers/driver[@name='{$driverName}']");
                        $xmlSupportedDriver = !empty($xmlSupportedDrivers) ? $xmlSupportedDrivers[0] : null;
                        if ($xmlSupportedDriver) {
                            $numDoneExts += 1;
                        }

                        // Sub-extensions.
                        $xmlSubExts = $xmlExt->xpath('./subextensions/subextension');
                        foreach ($xmlSubExts as $xmlSubExt) {
                            $xmlSupportedDrivers = $xmlSubExt->xpath("./supported-drivers/driver[@name='{$driverName}']");
                            $xmlSupportedDriver = !empty($xmlSupportedDrivers) ? $xmlSupportedDrivers[0] : null;
                            if ($xmlSupportedDriver) {
                                $numDoneExts += 1;
                            }
                        }
                    }

                    $lbApiVersion->addDriver($driverName, $numDoneExts);
                }
            }
        }
    }

    /**
     * Find the leaderboard for a specific API version.
     *
     * @param string $id API ID (format example: OpenGL4.5, Vulkan1.1, ...).
     * @return LbApiVersion The leaderboard for the given API version.
     */
    public function findApiVersion($id)
    {
        $i = 0;
        $numApiVersions = count($this->apiVersions);
        while ($i < $numApiVersions && $this->apiVersions[$i]->getId() !== $id) {
            $i++;
        }

        return $i < $numApiVersions ? $this->apiVersions[$i] : null;
    }

    /**
     * Get the total number of extensions.
     */
    public function getNumTotalExts()
    {
        $numExts = 0;
        foreach ($this->apiVersions as &$apiVersion) {
            $numExts += $apiVersion->getNumExts();
        }

        return $numExts;
    }

    /**
     * Get an array of driver with their number of extensions done for all the
     * API versions. The array is sorted by the drivers score (descending).
     *
     * @param string $api Name of the API (OpenGL, OpenGL ES, Vulkan, ...).
     * @return LbDriverScore[] An associative array: the key is the driver name, the
     *                         value is an LbDriverScore.
     */
    public function getDriversSortedByExtsDone(string $api)
    {
        $sortedDriversScores = array();
        $numTotalExts = $this->getNumTotalExts();
        foreach ($this->apiVersions as &$apiVersion) {
            $apiVersionDrivers = $apiVersion->getDriverScores();
            foreach ($apiVersionDrivers as $drivername => $driverScore) {
                if (!array_key_exists($drivername, $sortedDriversScores)) {
                    // Add new driver.
                    $sortedDriversScores[$drivername] = new LbDriverScore(0, $numTotalExts, 0);
                }

                // Add up the number of extensions done for this driver.
                $numExtsDone = $sortedDriversScores[$drivername]->getNumExtensionsDone() +
                    $driverScore->getNumExtensionsDone();
                $sortedDriversScores[$drivername]->setNumExtensionsDone($numExtsDone);
            }
        }

        // Keep last max API version fully implemented.
        foreach (array_keys($sortedDriversScores) as $drivername) {
            $sortedDriversScores[$drivername]->setApiVersion($this->getDriverApiVersion($api, $drivername));
        }

        // Sort by number of extensions and then by API version.
        uasort($sortedDriversScores, function ($a, $b) {
            $diff = $b->getNumExtensionsDone() - $a->getNumExtensionsDone();
            if ($diff === 0 && $this->useVersions) {
                $versionDiff = $b->getApiVersion() - $a->getApiVersion();
                if ($versionDiff !== 0) {
                    $diff = $versionDiff < 0 ? -1 : 1;
                }
            }

            return $diff;
        });

        return $sortedDriversScores;
    }

    /**
     * Get latest valid API version for a driver.
     *
     * @param string $api Name of the API (OpenGL, OpenGL ES, Vulkan, ...).
     * @param string $drivername Name of the driver (mesa, r600, ...).
     * @return string The API version string; NULL otherwise.
     */
    public function getDriverApiVersion(string $api, string $drivername)
    {
        $apiVersionNbr = null;

        // Parse from first to latest API version.
        // Continue as long as all the extensions are done for this driver in
        // this version, and remember the version.
        $i = count($this->apiVersions);
        while ($i > 0) {
            $apiVersion = $this->apiVersions[--$i];
            if ($apiVersion->getName() === $api) {
                if ($apiVersion->getDriverScore($drivername)->getNumExtensionsDone() !== $apiVersion->getNumExts()) {
                    break;
                }

                $apiVersionNbr = $apiVersion->getVersion();
            }
        }

        return $apiVersionNbr;
    }

    /**
     * Create a new LbApiVersion and add it to the $apiVersions array.
     *
     * @param string $name API name.
     * @param string $version API version.
     * @param integer $numExts Total number of extensions.
     * @return LbApiVersion The new item.
     */
    private function createApiVersion(string $name, string $version, int $numExts)
    {
        $apiVersion = new LbApiVersion($name, $version, $numExts);
        $this->apiVersions[] = $apiVersion;
        return $apiVersion;
    }

    private array $apiVersions;
    private bool $useVersions;
}
