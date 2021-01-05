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

namespace Mesamatrix\Leaderboard;

class LbGlVersion {
    /**
     * LbGlVersion constructor.
     *
     * @param string $glname OpenGL name (e.g. OpenGL, OpenGL ES).
     * @param string $glversion OpenGL version (e.g. 3.1, 4.0).
     * @param integer $numExts Total number of extensions.
     * @remark Versions are identified by `glid` which is the concatenation of
     *         the name and the version (examples: OpenGL4.5, OpenGL ES3.1, ...).
     */
    public function __construct(string $glname, string $glversion, int $numExts) {
        $this->glid = $glname.$glversion;
        $this->glName = $glname;
        $this->glVersion = $glversion;
        $this->numExts = $numExts;
        $this->drivers = array();
    }

    /**
     * Get the OpenGL ID for this version.
     */
    public function getGlId() {
        return $this->glid;
    }

    /**
     * Get the OpenGL name.
     */
    public function getGlName() {
        return $this->glName;
    }

    /**
     * Get the OpenGL version.
     */
    public function getGlVersion() {
        return $this->glVersion;
    }

    /**
     * Replace or add a new driver and set it's number of extensions done.
     *
     * @param string $drivername Name of the driver.
     * @param integer $numExtsDone Number of extensions done.
     */
    public function addDriver(string $drivername, int $numExtsDone) {
        $this->driverScores[$drivername] = new LbDriverScore($numExtsDone, $this->getNumExts());
    }

    /**
     * Set the number of extensions for this OpenGL version.
     *
     * @param integer $num Number of extensions.
     */
    public function setNumExts($num) {
        $this->numExts = $num;
    }

    /**
     * Get the number of extensions for this OpenGL version.
     */
    public function getNumExts() {
        return $this->numExts;
    }

    /**
     * Get the number of extension done for a given driver.
     *
     * @param string $drivername Name of the driver.
     * @return LbDriverScore The driver score.
     */
    public function getDriverScore($drivername) {
        return $this->driverScores[$drivername];
    }

    /**
     * Get all the drivers scores for this OpenGL version.
     *
     * @return mixed[] An associative array: the key is the driver name, the
     *                 value is an LbDriverScore.
     */
    public function getDriverScores() {
        return $this->driverScores;
    }

    private $glid;
    private $glName;
    private $glVersion;
    private $numExts;
    private $driverScores;
}
