<?php
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

class LbDriverScore {
    private $numExtsDone;
    private $numExts;
    private $apiVersion;

    /**
     * LbDriver constructor.
     *
     * @param int $numExtsDone Number of extensions done.
     * @param int $numExts Total number of extensions.
     * @param float $apiVersion API version (e.g. 3.1, 4.0).
     */
    public function __construct(int $numExtsDone, int $numExts, float $apiVersion) {
        $this->setNumExtensionsDone($numExtsDone);
        $this->setNumExtensions($numExts);
        $this->setApiVersion($apiVersion);
    }

    /**
     * Set the number of extensions done.
     */
    function setNumExtensionsDone($num) {
        $this->numExtsDone = $num;
    }

    /**
     * Get the number of extensions done.
     */
    function getNumExtensionsDone() {
        return $this->numExtsDone;
    }

    /**
     * Set the total number of extensions.
     */
    function setNumExtensions($num) {
        $this->numExts = $num;
    }

    /**
     * Get the total number of extensions.
     */
    function getNumExtensions() {
        return $this->numExts;
    }

    /**
     * Get the score.
     */
    function getScore() {
        return $this->numExts !== 0 ? $this->numExtsDone / $this->numExts : 0;
    }

    /**
     * Set the API version.
     */
    function setApiVersion($apiVersion) {
        $this->apiVersion = $apiVersion;
    }

    /**
     * Get the API version.
     */
    function getApiVersion() {
        return $this->apiVersion;
    }
}
