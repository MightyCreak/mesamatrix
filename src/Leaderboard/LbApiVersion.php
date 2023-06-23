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

class LbApiVersion
{
    private string $id;
    private string $name;
    private string $version;
    private int $numExts;
    private array $drivers;
    private array $driverScores;

    /**
     * LbApiVersion constructor.
     *
     * @param string $name API name (e.g. OpenGL, Vulkan, ...).
     * @param string $version API version (e.g. 4.5, 1.1, ...).
     * @param integer $numExts Total number of extensions.
     * @remark Versions are identified by `id` which is the concatenation of
     *         the name and the version (examples: OpenGL4.5, Vulkan1.1, ...).
     */
    public function __construct(string $name, string $version, int $numExts)
    {
        $this->id = $name . $version;
        $this->name = $name;
        $this->version = $version;
        $this->numExts = $numExts;
        $this->drivers = array();
    }

    /**
     * Get the API ID for this version.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the API name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the API version.
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Replace or add a new driver and set it's number of extensions done.
     *
     * @param string $drivername Name of the driver.
     * @param integer $numExtsDone Number of extensions done.
     */
    public function addDriver(string $drivername, int $numExtsDone)
    {
        $this->driverScores[$drivername] = new LbDriverScore(
            $numExtsDone,
            $this->getNumExts(),
            (float) $this->getVersion()
        );
    }

    /**
     * Set the number of extensions for this API version.
     *
     * @param integer $num Number of extensions.
     */
    public function setNumExts($num)
    {
        $this->numExts = $num;
    }

    /**
     * Get the number of extensions for this API version.
     */
    public function getNumExts()
    {
        return $this->numExts;
    }

    /**
     * Get the number of extension done for a given driver.
     *
     * @param string $drivername Name of the driver.
     * @return LbDriverScore The driver score.
     */
    public function getDriverScore($drivername)
    {
        return $this->driverScores[$drivername];
    }

    /**
     * Get all the drivers scores for this API version.
     *
     * @return mixed[] An associative array: the key is the driver name, the
     *                 value is an LbDriverScore.
     */
    public function getDriverScores()
    {
        return $this->driverScores;
    }
}
