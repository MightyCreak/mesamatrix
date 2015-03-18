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

namespace Mesamatrix\Parser;

class OglExtension
{
    public function __construct($name, $status, $hints, $supportedDrivers = array()) {
        $this->name = $name;
        $this->hints = $hints;
        $this->supportedDrivers = array();
        $this->modifiedAt = null;

        $this->setStatus($status);
        foreach ($supportedDrivers as $driverName) {
            $this->addSupportedDriver($driverName);
        }
    }

    // name
    public function setName($name) {
        $this->name = $name;
    }
    public function getName() {
        return $this->name;
    }

    // status
    public function setStatus($status) {
        $this->status = $status;

        // Set the hint.
        $hint = "";
        if (strncmp($status, "DONE", strlen("DONE")) === 0) {
            $hint = substr($status, strlen("DONE") + 1);
        }
        elseif (strncmp($status, "not started", strlen("not started")) === 0) {
            $hint = substr($status, strlen("not started") + 1);
        }
        else {
            $hint = $status;
        }

        $this->setHint($hint);
    }
    public function getStatus() {
        return $this->status;
    }

    // hint
    public function setHint($hint) {
        $this->hintIdx = $this->hints->addToHints($hint);
    }
    public function getHintIdx() {
        return $this->hintIdx;
    }

    // supported drivers
    public function addSupportedDriver($driverName, $commit = null) {
        $this->supportedDrivers[] = new OglSupportedDriver($driverName, $this->hints, $commit);
    }
    public function getSupportedDrivers() {
        return $this->supportedDrivers;
    }
    public function getSupportedDriverByName($driverName) {
        foreach ($this->supportedDrivers as $supportedDriver) {
            $matchName = $supportedDriver->getName();
            if (strncmp($driverName, $matchName, strlen($matchName)) === 0) {
                return $supportedDriver;
            }
        }
    }

    // modified at
    public function setModifiedAt($commit) {
        $this->modifiedAt = $commit;
    }
    public function getModifiedAt() {
        return $this->modifiedAt;
    }

    // merge
    public function incorporate($other, $commit) {
        if ($this->name !== $other->name) {
            \Mesamatrix::$logger->error('Merging extensions with different names');
        }
        if ($this->status !== $other->status) {
            $this->status = $other->status;
            $this->setModifiedAt($commit);
        }
        if ($this->hintIdx !== $other->hintIdx) {
            $this->hintIdx = $other->hintIdx;
            $this->setModifiedAt($commit);
        }
        foreach ($other->supportedDrivers as $supportedDriver) {
            if ($driver = $this->getSupportedDriverByName($supportedDriver->getName())) {
                $driver->incorporate($supportedDriver, $commit);
            }
            else {
                $supportedDriver->setModifiedAt($commit);
                $this->supportedDrivers[] = $supportedDriver;
            }
        }
    }

    private $name;
    private $status;
    private $hints;
    private $hintIdx;
    private $supportedDrivers;
    private $modifiedAt;
};
