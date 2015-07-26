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
        $this->hints = $hints;
        $this->subextensions = array();
        $this->setName($name);
        $this->parseStatus($status);
        $this->setModifiedAt(null);

        $this->supportedDrivers = array();
        foreach ($supportedDrivers as $driverName) {
            $driver = new OglSupportedDriver($driverName, $this->hints);
            $this->addSupportedDriver($driver);
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
    }
    public function getStatus() {
        return $this->status;
    }
    public function parseStatus($status) {
        $hint = "";
        if (strncmp($status, "DONE", strlen("DONE")) === 0) {
            $this->status = 'complete';
            $hint = substr($status, strlen("DONE") + 1);
        }
        elseif (strncmp($status, "not started", strlen("not started")) === 0) {
            $this->status = 'incomplete';
            $hint = substr($status, strlen("not started") + 1);
        }
        else {
            $this->status = 'started';
            $hint = $status;
        }

        $this->setHint($hint);
    }

    // hint
    public function setHint($hint) {
        $this->hintIdx = $this->hints->addToHints($hint);
    }
    public function getHintIdx() {
        return $this->hintIdx;
    }

    // supported drivers
    public function addSupportedDriver(OglSupportedDriver $driver, \Mesamatrix\Git\Commit $commit = null) {
        if ($existingDriver = $this->getSupportedDriverByName($driver->getName())) {
            $existingDriver->incorporate($driver, $commit);
            return $existingDriver;
        }
        else {
            $driver->setModifiedAt($commit);
            $this->supportedDrivers[] = $driver;
            return $driver;
        }
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
            $this->addSupportedDriver($supportedDriver, $commit);
        }
    }

    /**
     * Add a sub-extension, or merge it if it already exists.
     *
     * @param string $name Name of the extension.
     * @param string $status Status of the extension.
     * @param array $supportedDrivers List of drivers supported for this extension.
     * @param \Mesamatrix\Git\Commit $commit The commit used by the parser.
     */
    public function addSubExtension($name, $status, $supportedDrivers = array(), $commit = null) {
        $newExtension = new OglExtension($name, $status, $this->hints, $supportedDrivers);
        return $this->addSubExtension2($newExtension, $commit);
    }
    public function addSubExtension2(OglExtension $extension, \Mesamatrix\Git\Commit $commit) {
        $retSubExt = null;
        $existingSubExt = $this->findSubExtensionByName($extension->getName());
        if($existingSubExt !== null) {
            $existingSubExt->incorporate($extension, $commit);
            $retSubExt = $existingSubExt;
        }
        else {
            $this->subextensions[] = $extension;
            $retSubExt = $extension;
        }

        return $retSubExt;
    }

    /**
     * Get the list of all sub-extensions.
     *
     * @return OglExtension[] All the sub-extensions.
     */
    public function getSubExtensions() {
        return $this->subextensions;
    }

    /**
     * Find the extensions with the given name.
     *
     * @param string $name The name of the extension to find.
     *
     * @return OglExtension The extension or null if not found.
     */
    private function findSubExtensionByName($name) {
        foreach ($this->subextensions as $subext) {
            if($subext->getName() === $name) {
                return $subext;
            }
        }

        return null;
    }

    private $name;
    private $status;
    private $hints;
    private $hintIdx;
    private $supportedDrivers;
    private $modifiedAt;
    private $subextensions;
};
