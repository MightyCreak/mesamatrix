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

use Mesamatrix\Git\Commit;
use Mesamatrix\Mesamatrix;

class Extension
{
    /**
     * Extension constructor.
     *
     * @param string $name The extension name.
     * @param string $status The extension status (@see Constants::STATUS_...).
     * @param string $hint The extension hint.
     * @param Hints $hints The hint manager.
     * @param string[] $supportedDrivers The drivers supporting the extension.
     * @param string[] $apiDrivers All the possible drivers for the API.
     */
    public function __construct(
        string $name,
        string $status,
        string $hint,
        Hints $hints,
        array $supportedDrivers = array(),
        array $apiDrivers = array()
    ) {
        $this->hints = $hints;
        $this->subextensions = array();
        $this->setName($name);
        $this->setStatus($status);
        $this->setHint($hint);
        $this->setModifiedAt(null);

        $this->supportedDrivers = array();
        foreach ($supportedDrivers as $driverNameAndHint) {
            list($driverName, $driverHint) = self::splitDriverNameAndHint($driverNameAndHint, $apiDrivers);
            if ($driverName === null) {
                Mesamatrix::$logger->error("Unrecognized driver: '$driverNameAndHint'");
            }

            $supportedDriver = new SupportedDriver($driverName, $this->hints);
            $supportedDriver->setHint($driverHint);
            $this->addSupportedDriver($supportedDriver);
        }
    }

    /**
     * Split the given string between an official API driver and its hint.
     *
     * @param string $driverNameAndHint The whole string containing the driver and its hint.
     * @param string[] $apiDrivers All the possible drivers for the API.
     *
     * @return string[] An array of two string; 0: the driver name, 1: its hint.
     */
    private static function splitDriverNameAndHint($driverNameAndHint, array $apiDrivers)
    {
        $driverName = null;
        $driverHint = "";
        $i = 0;
        $numApiDrivers = count($apiDrivers);
        while ($i < $numApiDrivers && $driverName === null) {
            $apiDriverName = $apiDrivers[$i];
            $apiDriverLen = strlen($apiDriverName);
            if (strncmp($driverNameAndHint, $apiDriverName, $apiDriverLen) === 0) {
                $driverName = $apiDriverName;
                $driverHint = substr($driverNameAndHint, $apiDriverLen + 1);
            }

            ++$i;
        }

        return array($driverName, $driverHint);
    }

    // name
    public function setName($name)
    {
        $this->name = $name;
    }
    public function getName()
    {
        return $this->name;
    }

    // status
    public function setStatus($status)
    {
        $this->status = $status;
    }
    public function getStatus()
    {
        return $this->status;
    }

    // hint
    public function setHint($hint)
    {
        $this->hintIdx = $this->hints->addToHints($hint);
    }
    public function getHintIdx()
    {
        return $this->hintIdx;
    }
    public function getHint()
    {
        if ($this->hintIdx === -1) {
            return null;
        }

        return $this->hints->allHints[$this->hintIdx];
    }

    // supported drivers
    public function addSupportedDriver(SupportedDriver $driver)
    {
        $existingDriver = $this->getSupportedDriverByName($driver->getName());
        if ($existingDriver === null) {
            $this->supportedDrivers[] = $driver;
        }
    }
    public function getSupportedDrivers()
    {
        return $this->supportedDrivers;
    }
    public function getSupportedDriverByName($driverName)
    {
        foreach ($this->supportedDrivers as $supportedDriver) {
            $matchName = $supportedDriver->getName();
            if (strncmp($driverName, $matchName, strlen($matchName)) === 0) {
                return $supportedDriver;
            }
        }

        return null;
    }

    // modified at
    public function setModifiedAt(?Commit $commit)
    {
        $this->modifiedAt = $commit;
    }
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Add a sub-extension.
     *
     * @param Extension $extension The extension to add.
     */
    public function addSubExtension(Extension $extension)
    {
        $this->subextensions[] = $extension;
    }

    /**
     * Detects if the current extension depends on another one and merge the drivers.
     *
     * @param Matrix $matrix The entire matrix.
     */
    public function solveExtensionDependencies($matrix)
    {
        $hint = $this->getHint();
        if ($hint === null) {
            return;
        }

        foreach (Constants::RE_DEP_DRIVERS_HINTS as $reDepDriversHint) {
            if (preg_match($reDepDriversHint[0], $hint, $matches) === 1) {
                switch ($reDepDriversHint[2]) {
                    case DependsOn::EXTENSION:
                    {
                        $depExt = $matrix->getExtensionBySubstr($matches[$reDepDriversHint[3]]);
                        if ($depExt !== null) {
                            foreach ($depExt->supportedDrivers as $supportedDriver) {
                                $this->addSupportedDriver($supportedDriver);
                            }
                        }
                    }
                    break;

                    case DependsOn::GLES_VERSION:
                    {
                        $supportedDriverNames = $matrix->getDriversSupportingGlesVersion($matches[$reDepDriversHint[3]]);
                        if ($supportedDriverNames !== null) {
                            foreach ($supportedDriverNames as $supportedDriverName) {
                                $supportedDriver = new SupportedDriver($supportedDriverName, $this->hints);
                                if ($reDepDriversHint[2]) {
                                    $supportedDriver->setHint($hint);
                                }

                                $this->addSupportedDriver($supportedDriver);
                            }
                        }
                    }
                    break;
                }
            }
        }
    }

    public function loadXml(\SimpleXMLElement $xmlExt)
    {
        $xmlSubExts = $xmlExt->xpath('./subextensions/subextension');

        // Add new sub-extensions.
        foreach ($xmlSubExts as $xmlSubExt) {
            $subExtName = (string) $xmlSubExt['name'];
            $subExtStatus = (string) $xmlSubExt->mesa['status'];
            $subExtHint = (string) $xmlSubExt->mesa['hint'];

            $newSubExtension = new Extension($subExtName, $subExtStatus, $subExtHint, $this->hints, array());
            $xmlSupportedDrivers = $xmlSubExt->xpath("./supported-drivers/driver");
            foreach ($xmlSupportedDrivers as $xmlSupportedDriver) {
                // Create new supported driver.
                $driverName = (string) $xmlSupportedDriver['name'];
                $driverHint = (string) $xmlSupportedDriver['hint'];

                $driver = new SupportedDriver($driverName, $this->hints);
                $driver->setHint($driverHint);
                $newSubExtension->addSupportedDriver($driver);
            }

            // Add the sub-extension.
            $this->addSubExtension($newSubExtension);
        }
    }

    /**
     * Get the list of all sub-extensions.
     *
     * @return Extension[] All the sub-extensions.
     */
    public function getSubExtensions()
    {
        return $this->subextensions;
    }

    /**
     * Find the extensions with the given name.
     *
     * @param string $name The name of the extension to find.
     *
     * @return Extension The extension or null if not found.
     */
    public function findSubExtensionByName($name)
    {
        foreach ($this->subextensions as $subext) {
            if ($subext->getName() === $name) {
                return $subext;
            }
        }

        return null;
    }

    private string $name;
    private string $status;
    private Hints $hints;
    private int $hintIdx;
    private array $supportedDrivers;
    private ?Commit $modifiedAt;
    private array $subextensions;
}
