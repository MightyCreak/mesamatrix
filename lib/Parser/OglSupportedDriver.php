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

class OglSupportedDriver
{
    public function __construct($name, $hints) {
        $this->setName("<undefined>");
        $this->hints = $hints;
        $this->hintIdx = -1;
        $this->lastModified = null;

        foreach (Constants::$allDrivers as $driver) {
            $driverLen = strlen($driver);
            if (strncmp($name, $driver, $driverLen) === 0) {
                $this->name = $driver;
                $this->setHint(substr($name, $driverLen + 1));
            }
        }
    }

    // hints
    public function setName($name) {
        $this->name = $name;
    }
    public function getName() {
        return $this->name;
    }

    // hints
    public function setHint($hint) {
        $this->hintIdx = $this->hints->addToHints($hint);
    }
    public function getHintIdx() {
        return $this->hintIdx;
    }

    // last modified
    public function setLastModified($time) {
        $this->lastModified = $time;
    }
    public function getLastModified() {
        return $this->lastModified;
    }

    // merge
    public function incorporate($other, $time) {
        if ($this->name !== $other->name) {
            \Mesamatrix::$logger->error('Merging supported drivers with different names');
        }
        if ($this->hintIdx !== $other->hintIdx) {
            $this->hintIdx = $other->hintIdx;
            $this->setLastModified($time);
        }
    }

    private $name;
    private $hints;
    private $hintIdx;
    private $lastModified;
};