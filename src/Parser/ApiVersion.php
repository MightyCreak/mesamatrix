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

class ApiVersion
{
    public function __construct(
        string $name,
        ?string $version,
        ?string $glslName,
        ?string $glslVersion,
        Hints $hints
    ) {
        $this->setName($name);
        $this->setVersion($version);
        $this->setGlslName($glslName);
        $this->setGlslVersion($glslVersion);
        $this->hints = $hints;
        $this->extensions = array();
    }

    // API name
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    public function getName(): string
    {
        return $this->name;
    }

    // API version
    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }
    public function getVersion(): ?string
    {
        return $this->version;
    }

    // GLSL name
    public function setGlslName(?string $name): void
    {
        $this->glslName = $name;
    }
    public function getGlslName(): ?string
    {
        return $this->glslName;
    }

    // GLSL version
    public function setGlslVersion(?string $version): void
    {
        $this->glslVersion = $version;
    }
    public function getGlslVersion(): ?string
    {
        return $this->glslVersion;
    }

    /**
     * Add an extension.
     *
     * @param Extension $extension The extension to add.
     */
    public function addExtension(Extension $extension): void
    {
        $this->extensions[] = $extension;
    }

    /**
     * Get all the drivers for this API.
     *
     * @return string[]|null The list of supported drivers names; null if API not recognized.
     */
    public function getAllApiDrivers(): ?array
    {
        $apiName = $this->getName();
        switch ($apiName) {
            case Constants::GL_NAME:
            case Constants::GLES_NAME:
            case Constants::GL_OR_ES_EXTRA_NAME:
                return Constants::GL_ALL_DRIVERS;

            case Constants::VK_NAME:
            case Constants::VK_EXTRA_NAME:
                return Constants::VK_ALL_DRIVERS;

            case Constants::RUSTICL_OPENCL_NAME:
            case Constants::RUSTICL_OPENCL_OPTIONAL_NAME:
            case Constants::RUSTICL_OPENCL_CL2_OPTIONAL_NAME:
            case Constants::RUSTICL_OPENCL_EXTRA_NAME:
                return Constants::RUSTICL_OPENCL_ALL_DRIVERS;
        }

        return null;
    }

    /**
     * Get the drivers supporting this version.
     *
     * @return string[] The list of supported drivers names.
     */
    public function getSupportedDrivers(): array
    {
        $supportedDrivers = [];

        $apiDrivers = $this->getAllApiDrivers();
        foreach ($apiDrivers as $driverName) {
            $driver = null;
            foreach ($this->getExtensions() as $ext) {
                $driver = $ext->getSupportedDriverByName($driverName);
                if ($driver === null) {
                    break;
                }
            }

            if ($driver !== null) {
                $supportedDrivers[] = $driverName;
            }
        }

        return $supportedDrivers;
    }

    /**
     * Parse all the extensions in the version and solve them.
     *
     * @param Matrix $matrix The entire matrix.
     */
    public function solveExtensionDependencies(Matrix $matrix): void
    {
        foreach ($this->getExtensions() as $ext) {
            $ext->solveExtensionDependencies($matrix);
        }
    }

    public function loadXml(\SimpleXMLElement $xmlSection): void
    {
        $xmlExts = $xmlSection->extensions->extension;

        // Add new extensions.
        $apiDrivers = $this->getAllApiDrivers();
        foreach ($xmlExts as $xmlExt) {
            $extName = (string) $xmlExt['name'];
            $extStatus = (string) $xmlExt->mesa['status'];
            $extHint = (string) $xmlExt->mesa['hint'];

            $newExtension = new Extension($extName, $extStatus, $extHint, $this->hints, array());

            $xmlSupportedDrivers = $xmlExt->xpath("./supported-drivers/driver");
            foreach ($xmlSupportedDrivers as $xmlSupportedDriver) {
                // Get driver name.
                $driverName = (string) $xmlSupportedDriver['name'];
                if (!in_array($driverName, $apiDrivers)) {
                    // Driver unknown (may have been removed from mesa).
                    continue;
                }

                // Create new supported driver.
                $driverHint = (string) $xmlSupportedDriver['hint'];

                $driver = new SupportedDriver($driverName, $this->hints);
                $driver->setHint($driverHint);
                $newExtension->addSupportedDriver($driver);
            }

            // Add the extension.
            $this->addExtension($newExtension);

            $newExtension->loadXml($xmlExt);
        }
    }

    /**
     * Get the list of all extensions.
     *
     * @return Extension[] All the extensions.
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Get the number of extensions.
     *
     * @return int The number of extensions.
     */
    public function getNumExtensions(): int
    {
        return count($this->extensions);
    }

    /**
     * Find the extensions with the given name.
     *
     * @param string $name The name of the extension to find.
     *
     * @return Extension|null The extension or null if not found.
     */
    public function getExtensionByName(string $name): ?Extension
    {
        foreach ($this->extensions as $extension) {
            if ($extension->getName() === $name) {
                return $extension;
            }
        }
        return null;
    }

    /**
     * Find the extensions with the given substring.
     *
     * @param string $substr The substring to find in the extension name.
     *
     * @return Extension|null The extension or null if not found.
     */
    public function getExtensionBySubstr(string $substr): ?Extension
    {
        foreach ($this->extensions as $extension) {
            if (strstr($extension->getName(), $substr) !== false) {
                return $extension;
            }
        }
        return null;
    }

    private string $name;
    private ?string $version;
    private ?string $glslName;
    private ?string $glslVersion;
    private Hints $hints;

    /** @var Extension[] */
    private array $extensions;
}
