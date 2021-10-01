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

class OglMatrix
{
    private array $glVersions;
    private Hints $hints;

    public function __construct() {
        $this->glVersions = array();
        $this->hints = new Hints();
    }

    public function addGlVersion(OglVersion $glVersion) {
        array_push($this->glVersions, $glVersion);
    }

    /**
     * Find the first extension containing a given string.
     *
     * @param string $substr The substring to find.
     *
     * @return \Mesamatrix\Parser\OglExtension The extension if found; NULL otherwise.
     */
    public function getExtensionBySubstr($substr) {
        foreach ($this->getGlVersions() as $glVersion) {
            $glExt = $glVersion->getExtensionBySubstr($substr);
            if ($glExt !== NULL) {
                return $glExt;
            }
        }

        return NULL;
    }

    /**
     * Get the list of drivers supporting a specific version of OpenGL ES.
     *
     * @param int $version The GL ES version to look for.
     *
     * @return string[] The list of drivers that supports
     *         the OpenGL ES; NULL otherwise.
     */
    public function getDriversSupportingGlesVersion($version) {
        foreach ($this->getGlVersions() as $glVersion) {
            if ($glVersion->getGlName() === Constants::GLES_NAME && $glVersion->getGlVersion() === $version) {
                return $glVersion->getSupportedDrivers();
            }
        }

        return NULL;
    }

    /**
     * Parse all the GL versions and solve their extensions.
     */
    public function solveExtensionDependencies() {
        foreach ($this->getGlVersions() as $glVersion) {
            $glVersion->solveExtensionDependencies($this);
        }
    }

    /**
     * Load an XML formatted commit.
     *
     * @param \SimpleXMLElement $mesa The root element of the XML file.
     */
    public function loadXml(\SimpleXMLElement $mesa) {
        foreach ($mesa->apis->api as $api) {
            $this->loadXmlApi($api);
        }
    }

    private function loadXmlApi(\SimpleXMLElement $api) {
        $xmlSections = $api->versions->version;

        // Add new sections.
        foreach ($xmlSections as $xmlSection) {
            $glName = (string) $xmlSection['name'];
            $glVersion = (string) $xmlSection['version'];

            $xmlShaderVersion = $xmlSection->{'shader-version'};
            $shaderName = (string) $xmlShaderVersion['name'];
            $shaderVersion = (string) $xmlShaderVersion['version'];

            $glSection = new OglVersion($glName, $glVersion, $shaderName, $shaderVersion, $this->getHints());
            $this->addGlVersion($glSection);

            $glSection->loadXml($xmlSection);
        }
    }

    /**
     * Get the GL versions.
     *
     * @return \Mesamatrix\Parser\OglVersion[] The GL versions array.
     */
    public function getGlVersions() {
        return $this->glVersions;
    }

    /**
     * Get the GL version based on its name.
     *
     * @param string $name The GL name.
     * @param string $version The GL version.
     *
     * @return \Mesamatrix\Parser\OglVersion The GL version is found; NULL otherwise.
     */
    public function getGlVersionByName($name, $version) {
        foreach ($this->glVersions as $glVersion) {
            if ($glVersion->getGlName() === $name &&
                $glVersion->getGlVersion() === $version) {
                return $glVersion;
            }
        }
        return null;
    }

    /**
     * Get the hints.
     *
     * @return \Mesamatrix\Parser\Hints The hints.
     */
    public function getHints() {
        return $this->hints;
    }
};
