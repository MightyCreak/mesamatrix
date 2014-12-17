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

namespace Mesamatrix\Parser;

class OglExtension
{
    public function __construct($name, $status, $hints, $supportedDrivers = array()) {
        $this->name = $name;
        $this->status = $status;
        $this->hints = $hints;

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

        // Set the supported drivers list.
        $this->supportedDrivers = array();
        foreach ($supportedDrivers as &$driverName) {
            $this->supportedDrivers[] = new OglSupportedDriver($driverName, $this->hints);
        }
    }

    public function setName($name) { $this->name = $name; }
    public function getName()      { return $this->name; }

    public function setStatus($status) { $this->status = $status; }
    public function getStatus()        { return $this->status; }

    public function setHint($hint) { $this->hintIdx = $this->hints->addToHints($hint); }
    public function getHintIdx()   { return $this->hintIdx; }

    public function addSupportedDriver($supportedDriver) { $this->supportedDrivers[] = $supportedDriver; }
    public function getSupportedDrivers()                { return $this->supportedDrivers; }

    private $name;
    private $status;
    private $hints;
    private $hintIdx;
    private $supportedDrivers;
};
