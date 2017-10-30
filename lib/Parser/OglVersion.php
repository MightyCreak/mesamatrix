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

class OglVersion
{
    public function __construct($glName, $glVersion, $glslName, $glslVersion, $hints) {
        $this->setGlName($glName);
        $this->setGlVersion($glVersion);
        $this->setGlslName($glslName);
        $this->setGlslVersion($glslVersion);
        $this->hints = $hints;
        $this->extensions = array();
    }

    // GL name
    public function setGlName($name) {
        $this->glName = $name;
    }
    public function getGlName() {
        return $this->glName;
    }

    // GL version
    public function setGlVersion($version) {
        $this->glVersion = $version;
    }
    public function getGlVersion() {
        return $this->glVersion;
    }

    // GLSL name
    public function setGlslName($name) {
        $this->glslName = $name;
    }
    public function getGlslName() {
        return $this->glslName;
    }

    // GLSL version
    public function setGlslVersion($version) {
        $this->glslVersion = $version;
    }
    public function getGlslVersion() {
        return $this->glslVersion;
    }

    /**
     * Add an extension, or merge it if it already exists.
     *
     * @param OglExtension $extension The extension to add/merge.
     * @param \Mesamatrix\Git\Commit $commit The commit used by the parser.
     *
     * @return OglExtension The new or existing extension.
     */
    public function addExtension(OglExtension $extension, \Mesamatrix\Git\Commit $commit) {
        $retExt = null;
        $existingExt = $this->getExtensionByName($extension->getName());
        if ($existingExt !== null) {
            $existingExt->incorporate($extension, $commit);
            $retExt = $existingExt;
        }
        else {
            $extension->setModifiedAt($commit);
            $this->extensions[] = $extension;
            $retExt = $extension;
        }

        return $retExt;
    }

    /**
     * Remove an extension.
     *
     * @param \Mesamatrix\Parser\OglExtension $extension The extension to remove.
     */
    public function removeExtension(OglExtension $extension) {
        $idx = array_search($extension, $this->extensions);
        if ($idx !== false) {
            array_splice($this->extensions, $idx, 1);
        }
    }

    /**
     * Get the drivers supporting this version.
     *
     * @return string[] The list of supported drivers names.
     */
    public function getSupportedDrivers() {
        $supportedDrivers = [];

        foreach (Constants::GL_ALL_DRIVERS as $driverName) {
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
     * @param \Mesamatrix\Parser\OglMatrix $glMatrix The entire matrix.
     */
    public function solveExtensionDependencies($glMatrix) {
        foreach ($this->getExtensions() as $glExt) {
            $glExt->solveExtensionDependencies($glMatrix);
        }
    }

    public function merge(\SimpleXMLElement $xmlSection, \Mesamatrix\Git\Commit $commit) {
        $xmlExts = $xmlSection->xpath('./extension');

        // Remove old extensions.
        $glName = $this->getGlName();
        $glVersion = $this->getGlVersion();
        $numXmlExts = count($xmlExts);
        foreach ($this->getExtensions() as $glExt) {
            $glExtName = $glExt->getName();

            // Find extension in the XML.
            $i = 0;
            while ($i < $numXmlExts && (string) $xmlExts[$i]['name'] !== $glExtName) {
                ++$i;
            }

            if ($i === $numXmlExts) {
                // Extension not found in XML, remove it.
                \Mesamatrix::$logger->debug('In '.$glName.' '.$glVersion.
                                            ', remove GL extension: '.$glExtName);
                $this->removeExtension($glExt);
            }
        }

        // Add and merge new extensions.
        foreach ($xmlExts as $xmlExt) {
            $extName = (string) $xmlExt['name'];
            $extStatus = (string) $xmlExt->mesa['status'];
            $extHint = (string) $xmlExt->mesa['hint'];

            $newExtension = new OglExtension($extName, $extStatus, $extHint, $this->hints, array());
            foreach ($xmlExt->supported->children() as $driver) {
                // Create new supported driver.
                $driverName = $driver->getName();
                $driverHint = (string) $driver['hint'];
                $driver = new OglSupportedDriver($driverName, $this->hints);
                $driver->setHint($driverHint);
                $newExtension->addSupportedDriver($driver, $commit);
            }

            // Add the extension.
            $glExt = $this->addExtension($newExtension, $commit);

            $glExt->merge($this, $xmlExt, $commit);
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

    private $glName;
    private $glVersion;
    private $glslName;
    private $glslVersion;
    private $hints;
    private $extensions;
};
