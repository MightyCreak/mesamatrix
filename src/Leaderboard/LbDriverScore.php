<?php

declare(strict_types=1);

/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2021 Romain "Creak" Failliot.
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

class LbDriverScore
{
    private int $numExtsDone;
    private int $numExts;
    private ?string $apiVersion;

    /**
     * LbDriver constructor.
     *
     * @param int $numExtsDone Number of extensions done.
     * @param int $numExts Total number of extensions.
     * @param string|null $apiVersion API version (e.g. 3.1, 4.0).
     */
    public function __construct(int $numExtsDone, int $numExts, ?string $apiVersion)
    {
        $this->setNumExtensionsDone($numExtsDone);
        $this->setNumExtensions($numExts);
        $this->setApiVersion($apiVersion);
    }

    /**
     * Set the number of extensions done.
     */
    public function setNumExtensionsDone(int $num): void
    {
        $this->numExtsDone = $num;
    }

    /**
     * Get the number of extensions done.
     */
    public function getNumExtensionsDone(): int
    {
        return $this->numExtsDone;
    }

    /**
     * Set the total number of extensions.
     */
    public function setNumExtensions(int $num): void
    {
        $this->numExts = $num;
    }

    /**
     * Get the total number of extensions.
     */
    public function getNumExtensions(): int
    {
        return $this->numExts;
    }

    /**
     * Get the score.
     */
    public function getScore(): float
    {
        return $this->numExts !== 0 ? $this->numExtsDone / $this->numExts : 0;
    }

    /**
     * Set the API version.
     */
    public function setApiVersion(?string $apiVersion): void
    {
        $this->apiVersion = $apiVersion;
    }

    /**
     * Get the API version.
     */
    public function getApiVersion(): ?string
    {
        return $this->apiVersion;
    }
}
