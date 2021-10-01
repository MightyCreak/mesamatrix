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

class ApiVersion
{
    public function __construct(
            string $name,
            ?string $version,
            ?string $glslName,
            ?string $glslVersion,
            Hints $hints) {
        $this->setName($name);
        $this->setVersion($version);
        $this->setGlslName($glslName);
        $this->setGlslVersion($glslVersion);
        $this->hints = $hints;
        $this->extensions = array();
    }

    // API name
    public function setName(string $name) {
        $this->name = $name;
    }
    public function getName() {
        return $this->name;
    }

    // API version
    public function setVersion(?string $version) {
        $this->version = $version;
    }
    public function getVersion() {
        return $this->version;
    }

    // GLSL name
    public function setGlslName(?string $name) {
        $this->glslName = $name;
    }
    public function getGlslName() {
        return $this->glslName;
    }

    // GLSL version
    public function setGlslVersion(?string $version) {
        $this->glslVersion = $version;
    }
    public function getGlslVersion() {
        return $this->glslVersion;
    }

    /**
     * Add an extension.
     *
     * @param OglExtension $extension The extension to add.
     */
    public function addExtension(OglExtension $extension) {
        $this->extensions[] = $extension;
    }

    /**
     * Get all the drivers for this API.
     *
     * @return string[] The list of supported drivers names; null if API not recognized.
     */
    public function getAllApiDrivers() {
        $apiName = $this->getName();
        switch ($apiName) {
        case Constants::GL_NAME:
        case Constants::GLES_NAME:
        case Constants::GL_OR_ES_EXTRA_NAME:
            return Constants::GL_ALL_DRIVERS;

        case Constants::VK_NAME:
        case Constants::VK_EXTRA_NAME:
            return Constants::VK_ALL_DRIVERS;

        case Constants::OPENCL_NAME:
        case Constants::OPENCL_EXTRA_NAME:
        case Constants::OPENCL_VENDOR_SPECIFIC_NAME:
            return Constants::OPENCL_ALL_DRIVERS;
        }

        return null;
    }

    /**
     * Get the drivers supporting this version.
     *
     * @return string[] The list of supported drivers names.
     */
    public function getSupportedDrivers() {
        $supportedDrivers = [];

        $apiDrivers = $this->getAllApiDrivers();
        foreach ($apiDrivers as $driverName) {
            $driver = NULL;
            foreach ($this->getExtensions() as $glExt) {
                $driver = $glExt->getSupportedDriverByName($driverName);
                if ($driver === NULL) {
                    break;
                }
            }

            if ($driver !== NULL) {
                $supportedDrivers[] = $driverName;
            }
        }

        return $supportedDrivers;
    }

    /**
     * Parse all the extensions in the version and solve them.
     *
     * @param \Mesamatrix\Parser\Matrix $matrix The entire matrix.
     */
    public function solveExtensionDependencies($matrix) {
        foreach ($this->getExtensions() as $glExt) {
            $glExt->solveExtensionDependencies($matrix);
        }
    }

    public function loadXml(\SimpleXMLElement $xmlSection) {
        $xmlExts = $xmlSection->extensions->extension;

        // Add new extensions.
        $apiDrivers = $this->getAllApiDrivers();
        foreach ($xmlExts as $xmlExt) {
            $extName = (string) $xmlExt['name'];
            $extStatus = (string) $xmlExt->mesa['status'];
            $extHint = (string) $xmlExt->mesa['hint'];

            $newExtension = new OglExtension($extName, $extStatus, $extHint, $this->hints, array());

            $xmlSupportedDrivers = $xmlExt->xpath("./supported-drivers/driver");
            foreach ($xmlSupportedDrivers as $xmlSupportedDriver) {
                // Get driver name and verify it's valid.
                $driverName = (string) $xmlSupportedDriver['name'];
                if (!in_array($driverName, $apiDrivers)) {
                    \Mesamatrix::$logger->error('Unrecognized driver: '.$driverName);
                    continue;
                }

                // Create new supported driver.
                $driverHint = (string) $xmlSupportedDriver['hint'];

                $driver = new OglSupportedDriver($driverName, $this->hints);
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
     * @return OglExtension[] All the extensions.
     */
    public function getExtensions() {
        return $this->extensions;
    }

    /**
     * Get the number of extensions.
     *
     * @return int The number of extensions.
     */
    public function getNumExtensions() {
        return count($this->extensions);
    }

    /**
     * Find the extensions with the given name.
     *
     * @param string $name The name of the extension to find.
     *
     * @return OglExtension The extension or null if not found.
     */
    public function getExtensionByName($name) {
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
     * @param string $str The substring to find in the extension name.
     *
     * @return OglExtension The extension or null if not found.
     */
    public function getExtensionBySubstr($substr) {
        foreach ($this->extensions as $extension) {
            if (strstr($extension->getName(), $substr) !== FALSE) {
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
    private array $extensions;
};
