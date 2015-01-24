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
     * @param string $glid OpenGL ID (format example: GL4.5, GL4.4, GLES3.1, ...).
     */
    public function __construct($glid) {
        $this->glid = $glid;
        $this->numExts = 0;
        $this->driversExtsDone = array();
    }

    /**
     * Get the OpenGL ID for this version.
     */
    public function getGlId() {
        return $this->glid;
    }

    /**
     * Replace or add a new driver and set it's number of extensions done.
     *
     * @param string $drivername Name of the driver.
     * @param integer $numExtsDone Number of extensions done.
     */
    public function addDriver($drivername, $numExtsDone) {
        $this->driversExtsDone[$drivername] = $numExtsDone;
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
     * @return integer The number of extensions done for the given driver.
     */
    public function getNumDriverExtsDone($drivername) {
        return $this->driversExtsDone[$drivername];
    }

    /**
     * Get all the drivers results for this OpenGL verison.
     *
     * @return mixed[] An associative array: the key is the driver name, the
     *                 value is the number of extensions done.
     */
    public function getAllDrivers() {
        return $this->driversExtsDone;
    }

    private $glid;
    private $numExts;
    private $driversExtsDone;
}

