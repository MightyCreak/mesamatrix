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

class Matrix
{
    private array $apiVersions;
    private Hints $hints;

    public function __construct()
    {
        $this->apiVersions = array();
        $this->hints = new Hints();
    }

    public function addApiVersion(ApiVersion $apiVersion)
    {
        array_push($this->apiVersions, $apiVersion);
    }

    /**
     * Get the API versions.
     *
     * @return ApiVersion[] The API versions array.
     */
    public function getApiVersions()
    {
        return $this->apiVersions;
    }

    /**
     * Get the API version based on its name.
     *
     * @param string $name The API name.
     * @param string $version The API version.
     *
     * @return ApiVersion The API version is found; NULL otherwise.
     */
    public function getApiVersionByName($name, $version)
    {
        foreach ($this->apiVersions as $apiVersion) {
            if (
                $apiVersion->getName() === $name &&
                $apiVersion->getVersion() === $version
            ) {
                return $apiVersion;
            }
        }
        return null;
    }

    /**
     * Find the first extension containing a given string.
     *
     * @param string $substr The substring to find.
     *
     * @return Extension The extension if found; NULL otherwise.
     */
    public function getExtensionBySubstr($substr)
    {
        foreach ($this->getApiVersions() as $apiVersion) {
            $ext = $apiVersion->getExtensionBySubstr($substr);
            if ($ext !== null) {
                return $ext;
            }
        }

        return null;
    }

    /**
     * Get the list of drivers supporting a specific version of OpenGL ES.
     *
     * @param int $version The GL ES version to look for.
     *
     * @return string[] The list of drivers that supports
     *         the OpenGL ES; NULL otherwise.
     */
    public function getDriversSupportingGlesVersion($version)
    {
        foreach ($this->getApiVersions() as $apiVersion) {
            if ($apiVersion->getName() === Constants::GLES_NAME && $apiVersion->getVersion() === $version) {
                return $apiVersion->getSupportedDrivers();
            }
        }

        return null;
    }

    /**
     * Parse all the API versions and solve their extensions.
     */
    public function solveExtensionDependencies()
    {
        foreach ($this->getApiVersions() as $apiVersion) {
            $apiVersion->solveExtensionDependencies($this);
        }
    }

    /**
     * Load an XML formatted commit.
     *
     * @param \SimpleXMLElement $mesa The root element of the XML file.
     */
    public function loadXml(\SimpleXMLElement $mesa)
    {
        foreach ($mesa->apis->api as $api) {
            $this->loadXmlApi($api);
        }
    }

    private function loadXmlApi(\SimpleXMLElement $api)
    {
        $xmlSections = $api->versions->version;

        // Add new sections.
        foreach ($xmlSections as $xmlSection) {
            $name = (string) $xmlSection['name'];
            $version = (string) $xmlSection['version'];

            $xmlShaderVersion = $xmlSection->{'shader-version'};
            $shaderName = (string) $xmlShaderVersion['name'];
            $shaderVersion = (string) $xmlShaderVersion['version'];

            $apiVersion = new ApiVersion($name, $version, $shaderName, $shaderVersion, $this->getHints());
            $this->addApiVersion($apiVersion);

            $apiVersion->loadXml($xmlSection);
        }
    }

    /**
     * Get the hints.
     *
     * @return Hints The hints.
     */
    public function getHints()
    {
        return $this->hints;
    }
}
