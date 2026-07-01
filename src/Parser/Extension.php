<?php

declare(strict_types=1);

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

class Extension
{
    private string $name;
    private string $status;
    private Hints $hints;
    private int $hintIdx;

    /** @var SupportedDriver[] */
    private array $supportedDrivers;

    private ?Commit $modifiedAt;

    /** @var Extension[] */
    private array $subextensions;

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
            $driver = self::splitDriverNameAndHint($driverNameAndHint, $apiDrivers);
            if ($driver === null) {
                // Unknown driver (may have been removed from mesa).
                continue;
            }

            $supportedDriver = new SupportedDriver($driver['name'], $this->hints);
            $supportedDriver->setHint($driver['hint']);
            $this->addSupportedDriver($supportedDriver);
        }
    }

    /**
     * Split the given string between an official API driver and its hint.
     *
     * @param string $driverNameAndHint The whole string containing the driver and its hint.
     * @param string[] $apiDrivers All the possible drivers for the API.
     *
     * @return array{name: string, hint: string}|null An array of two string: the driver name and its hint;
*                                                     NULL if driver wasn't found.
     */
    private static function splitDriverNameAndHint($driverNameAndHint, array $apiDrivers): ?array
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

        return $driverName !== null ? ['name' => $driverName, 'hint' => $driverHint] : null;
    }

    // name
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    public function getName(): string
    {
        return $this->name;
    }

    // status
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
    public function getStatus(): string
    {
        return $this->status;
    }

    // hint
    public function setHint(string $hint): void
    {
        $this->hintIdx = $this->hints->addToHints($hint);
    }
    public function getHintIdx(): int
    {
        return $this->hintIdx;
    }
    public function getHint(): ?string
    {
        if ($this->hintIdx === -1) {
            return null;
        }

        return $this->hints->allHints[$this->hintIdx];
    }

    // supported drivers
    public function addSupportedDriver(SupportedDriver $driver): void
    {
        $existingDriver = $this->getSupportedDriverByName($driver->getName());
        if ($existingDriver === null) {
            $this->supportedDrivers[] = $driver;
        }
    }

    /**
     * Gets all the supported drivers
     *
     * @return SupportedDriver[] The supported drivers.
     */
    public function getSupportedDrivers(): array
    {
        return $this->supportedDrivers;
    }

    public function getSupportedDriverByName(string $driverName): ?SupportedDriver
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
    public function setModifiedAt(?Commit $commit): void
    {
        $this->modifiedAt = $commit;
    }
    public function getModifiedAt(): ?Commit
    {
        return $this->modifiedAt;
    }

    /**
     * Add a sub-extension.
     *
     * @param Extension $extension The extension to add.
     */
    public function addSubExtension(Extension $extension): void
    {
        $this->subextensions[] = $extension;
    }

    /**
     * Detects if the current extension depends on another one and merge the drivers.
     *
     * @param Matrix $matrix The entire matrix.
     */
    public function solveExtensionDependencies(Matrix $matrix): void
    {
        $hint = $this->getHint();
        if ($hint === null) {
            return;
        }

        foreach (Constants::RE_DEP_DRIVERS_HINTS as $reDepDriversHint) {
            $re = $reDepDriversHint[0];
            $setHint = $reDepDriversHint[1];
            $dependsOn = $reDepDriversHint[2];
            $matchIdx = $reDepDriversHint[3];
            if (preg_match($re, $hint, $matches) === 1) {
                switch ($dependsOn) {
                    case DependsOn::EXTENSION:
                        $depExt = $matrix->getExtensionBySubstr($matches[$matchIdx]);
                        if ($depExt !== null) {
                            foreach ($depExt->supportedDrivers as $supportedDriver) {
                                $this->addSupportedDriver($supportedDriver);
                            }
                        }
                        break;

                    case DependsOn::GLES_VERSION:
                        $supportedDriverNames = $matrix->getDriversSupportingGlesVersion($matches[$matchIdx]);
                        if ($supportedDriverNames !== null) {
                            foreach ($supportedDriverNames as $supportedDriverName) {
                                $supportedDriver = new SupportedDriver($supportedDriverName, $this->hints);
                                // @phpstan-ignore if.alwaysTrue (could be false one day)
                                if ($setHint) {
                                    $supportedDriver->setHint($hint);
                                }

                                $this->addSupportedDriver($supportedDriver);
                            }
                        }
                        break;
                }
            }
        }
    }

    public function loadXml(\SimpleXMLElement $xmlExt): void
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
    public function getSubExtensions(): array
    {
        return $this->subextensions;
    }

    /**
     * Find the extensions with the given name.
     *
     * @param string $name The name of the extension to find.
     *
     * @return Extension|null The extension or null if not found.
     */
    public function findSubExtensionByName($name): ?Extension
    {
        foreach ($this->subextensions as $subext) {
            if ($subext->getName() === $name) {
                return $subext;
            }
        }

        return null;
    }
}
