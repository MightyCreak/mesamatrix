<?php
/*
 * Copyright (C) 2014 Romain "Creak" Failliot.
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
    public function load($xml) {
        foreach($xml->gl as $glVersion) {
            $lbGlVersion = $this->createGlVersion($glVersion["name"].$glVersion["version"]);
            $lbGlVersion->setNumExts(count($glVersion->extension));

            $numDoneExts = 0;
            foreach ($glVersion->extension as $ext) {
                if ($ext->mesa["status"] == "complete")
                {
                    $numDoneExts += 1;
                }
            }

            $lbGlVersion->addDriver("mesa", $numDoneExts);

            foreach ($xml->drivers->vendor as $vendor) {
                foreach ($vendor->driver as $driver) {
                    $driverName = (string) $driver["name"];
                    $numDoneExts = 0;
                    foreach ($glVersion->extension as $ext) {
                        if ($ext->supported->{$driverName}) {
                            $numDoneExts += 1;
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
     * Create a new LbGlVersion and add it to the $glVersions array.
     *
     * @param string $glid OpenGL ID (format example: GL4.5, GL4.4, GLES3.1, ...).
     * @return LbGlVersion The new item.
     */
    private function createGlVersion($glid) {
        $glVersion = new Leaderboard\LbGlVersion($glid);
        $this->glVersions[] = $glVersion;
        return $glVersion;
    }

    private $glVersions;
}
