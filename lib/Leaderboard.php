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

namespace Mesamatrix;

class Leaderboard {
    /**
     * Leaderboard default constructor.
     */
    public function __construct() {
        $this->glVersions = array();
    }

    /**
     * Load leaderboard from data.
     *
     * @param SimpleXMLElement $xml Root of the XML data.
     */
    public function load(\SimpleXMLElement $xml) {
        foreach ($xml->apis->api as $api) {
            switch ((string) $api['name']) {
            case 'OpenGL':
            case 'OpenGL ES':
            case \Mesamatrix\Parser\Constants::GL_OR_ES_EXTRA_NAME:
                $this->loadApi($api);
                break;
            }
        }

        // Sort by OpenGL versions descending.
        usort($this->glVersions, function($a, $b) {
            // Sort OpenGL before OpenGLES and higher versions before lower ones.
            if ($a->getGlName() === $b->getGlName()) {
                $diff = (float) $b->getGlVersion() - (float) $a->getGlVersion();
                if ($diff === 0)
                    return 0;
                else
                    return $diff < 0 ? -1 : 1;
            }
            elseif ($a->getGlName() === "OpenGL") {
                return -1;
            }
            else {
                return 1;
            }
        });
    }

    /**
     * Load all the versions from a given API.
     *
     * @param SimpleXMLElement $api The XML tag for the wanted API.
     */
    private function loadApi(\SimpleXMLElement $api) {
        foreach($api->version as $glVersion) {
            $lbGlVersion = $this->createGlVersion((string) $glVersion["name"],
                                                  (string) $glVersion["version"]);

            // Count total extensions and sub-extensions.
            $numTotalExts = count($glVersion->extension);
            foreach ($glVersion->extension as $glExt) {
                $numTotalExts += count($glExt->subextension);
            }

            $lbGlVersion->setNumExts($numTotalExts);

            // Count done mesa extensions and sub-extensions.
            $numDoneExts = 0;
            foreach ($glVersion->extension as $glExt) {
                // Extension.
                if ($glExt->mesa["status"] == Parser\Constants::STATUS_DONE) {
                    $numDoneExts += 1;
                }

                // Sub-extensions.
                foreach ($glExt->subextension as $glSubExt) {
                    if ($glSubExt->mesa["status"] == Parser\Constants::STATUS_DONE) {
                        $numDoneExts += 1;
                    }
                }
            }

            $lbGlVersion->addDriver("mesa", $numDoneExts);

            // Count done extensions and sub-extensions for each drivers.
            foreach ($api->drivers->vendor as $vendor) {
                foreach ($vendor->driver as $driver) {
                    $driverName = (string) $driver["name"];

                    // Count done extensions and sub-extensions for $driverName.
                    $numDoneExts = 0;
                    foreach ($glVersion->extension as $glExt) {
                        // Extension.
                        if ($glExt->supported->{$driverName}) {
                            $numDoneExts += 1;
                        }

                        // Sub-extensions.
                        foreach ($glExt->subextension as $glSubExt) {
                            if ($glSubExt->supported->{$driverName}) {
                                $numDoneExts += 1;
                            }
                        }
                    }

                    $lbGlVersion->addDriver($driverName, $numDoneExts);
                }
            }
        }
    }

    /**
     * Find the leaderboard for a specific OpenGL version.
     *
     * @param string $glid OpenGL ID (format example: GL4.5, GL4.4, GLES3.1, ...).
     * @return LbGlVersion The leaderboard for the given GL version.
     */
    public function findGlVersion($glid) {
        $i = 0;
        $numGlVersions = count($this->glVersions);
        while ($i < $numGlVersions && $this->glVersions[$i]->getGlId() !== $glid) {
            $i++;
        }

        return $i < $numGlVersions ? $this->glVersions[$i] : NULL;
     }

    /**
     * Get the number of extensions done by a specific driver.
     *
     * @param string $drivername Name of the driver.
     * @return integer Number of extensions done for the given driver.
     */
    public function getNumDriverExtsDone($drivername) {
        $numExtsDone = 0;
        foreach ($this->glVersions as &$glVersion) {
            $numExtsDone += $glVersion->getNumDriverExtsDone($drivername);
        }

        return $numExtsDone;
    }

    /**
     * Get the total number of extensions.
     */
    public function getNumTotalExts() {
        $numExts = 0;
        foreach ($this->glVersions as &$glVersion) {
            $numExts += $glVersion->getNumExts();
        }

        return $numExts;
    }

    /**
     * Get an array of driver with their number of extensions done for all the
     * OpenGL versions. The array is sorted by the drivers score (descending).
     *
     * @return mixed[] An associative array: the key is the driver name, the
     *                 value is the number of extensions done.
     */
    public function getDriversSortedByExtsDone() {
        $driversTotalExtsDone = array();
        foreach ($this->glVersions as &$glVersion) {
            $glVersionDrivers = $glVersion->getAllDrivers();
            foreach ($glVersionDrivers as $drivername => $numExtsDone) {
                if (!array_key_exists($drivername, $driversTotalExtsDone)) {
                    $driversTotalExtsDone[$drivername] = 0;
                }

                $driversTotalExtsDone[$drivername] += $numExtsDone;
            }
        }

        arsort($driversTotalExtsDone);
        return $driversTotalExtsDone;
    }

    /**
     * Get latest valid OpenGL version for a driver.
     *
     * @param string $drivername Nome of the driver (mesa, r600, ...).
     * @return string The OpenGL version string; NULL otherwise.
     */
    public function getDriverGlVersion($drivername) {
        $openglVersion = NULL;

        // Parse from first to latest OpenGL version.
        // Continue as long as all the extensions are done for this driver in
        // this version, and remember the version.
        $i = count($this->glVersions);
        while ($i > 0) {
            $glVersion = $this->glVersions[--$i];
            if ($glVersion->getGlName() === "OpenGL") {
                if ($glVersion->getNumDriverExtsDone($drivername) !== $glVersion->getNumExts()) {
                    break;
                }

                $openglVersion = $glVersion->getGlVersion();
            }
        }

        return $openglVersion;
    }

    /**
     * Get latest valid OpenGL ES version for a driver.
     *
     * @param string $drivername Nome of the driver (mesa, r600, ...).
     * @return string The OpenGL version string; NULL otherwise.
     */
    public function getDriverGlesVersion($drivername) {
        $openglVersion = NULL;

        // Parse from first to latest OpenGL ES version.
        // Continue as long as all the extensions are done for this driver in
        // this version, and remember the version.
        $i = count($this->glVersions);
        while ($i > 0) {
            $glVersion = $this->glVersions[--$i];
            if ($glVersion->getGlName() === "OpenGL ES") {
                if ($glVersion->getNumDriverExtsDone($drivername) !== $glVersion->getNumExts()) {
                    break;
                }

                $openglVersion = $glVersion->getGlVersion();
            }
        }

        return $openglVersion;
    }

    /**
     * Create a new LbGlVersion and add it to the $glVersions array.
     *
     * @param string $glname OpenGL name.
     * @param string $glversion OpenGL version.
     * @return LbGlVersion The new item.
     */
    private function createGlVersion($glname, $glversion) {
        $glVersion = new Leaderboard\LbGlVersion($glname, $glversion);
        $this->glVersions[] = $glVersion;
        return $glVersion;
    }

    private $glVersions;    ///< Leaderboard\LbGlVersion[].
}
