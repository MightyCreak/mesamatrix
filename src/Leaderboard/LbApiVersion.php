<?php

declare(strict_types=1);

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

    /** @var array<string, LbDriverScore> */
    private array $driverScores;

    /**
     * LbApiVersion constructor.
     *
     * @param string $name API name (e.g. OpenGL, Vulkan, ...).
     * @param string $version API version (e.g. 4.5, 1.1, ...).
     * @param int $numExts Total number of extensions.
     * @remark Versions are identified by `id` which is the concatenation of
     *         the name and the version (examples: OpenGL4.5, Vulkan1.1, ...).
     */
    public function __construct(string $name, string $version, int $numExts)
    {
        $this->id = $name . $version;
        $this->name = $name;
        $this->version = $version;
        $this->numExts = $numExts;
    }

    /**
     * Get the API ID for this version.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the API name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the API version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Replace or add a new driver and set it's number of extensions done.
     *
     * @param string $driverName Name of the driver.
     * @param int $numExtsDone Number of extensions done.
     */
    public function addDriver(string $driverName, int $numExtsDone): void
    {
        $this->driverScores[$driverName] = new LbDriverScore(
            $numExtsDone,
            $this->getNumExts(),
            $this->getVersion()
        );
    }

    /**
     * Set the number of extensions for this API version.
     *
     * @param int $num Number of extensions.
     */
    public function setNumExts(int $num): void
    {
        $this->numExts = $num;
    }

    /**
     * Get the number of extensions for this API version.
     */
    public function getNumExts(): int
    {
        return $this->numExts;
    }

    /**
     * Get the number of extension done for a given driver.
     *
     * @param string $driverName Name of the driver.
     * @return LbDriverScore The driver score.
     */
    public function getDriverScore($driverName): LbDriverScore
    {
        return $this->driverScores[$driverName];
    }

    /**
     * Get all the drivers scores for this API version.
     *
     * @return LbDriverScore[] An associative array: the key is the driver name, the
     *                         value is an LbDriverScore.
     */
    public function getDriverScores(): array
    {
        return $this->driverScores;
    }
}
