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
    private $glVersions;
    private $hints;

    public function __construct() {
        $this->glVersions = array();
        $this->hints = new Hints();
    }

    public function addGlVersion(OglVersion $glVersion) {
        array_push($this->glVersions, $glVersion);
    }

    public function removeGlVersion(OglVersion $glVersion) {
        $idx = array_search($glVersion, $this->glVersions);
        if ($idx !== false) {
            array_splice($this->glVersions, $idx, 1);
        }
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
            if ($glVersion->getGlName() === 'OpenGL ES' && $glVersion->getGlVersion() === $version) {
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
     * Merge an XML formatted commit.
     *
     * @param \SimpleXMLElement $mesa The root element of the XML file.
     * @param \Mesamatrix\Git\Commit $commit The commit used by the parser.
     * @remark The merged commit is always considered as more recent than the
     *         ones already merged.
     */
    public function merge(\SimpleXMLElement $mesa, \Mesamatrix\Git\Commit $commit) {
        foreach ($mesa->apis->api as $api) {
            $this->mergeApi($api, $commit);
        }
    }

    private function mergeApi(\SimpleXMLElement $api, \Mesamatrix\Git\Commit $commit) {
        $xmlSections = $api->version;

        // Remove old sections.
        $numXmlSections = count($xmlSections);
        foreach ($this->getGlVersions() as $glSection) {
            $glName = $glSection->getGlName();
            if ($glName !== (string) $api['name'])
                break;

            // Find section in the XML.
            $glVersion = $glSection->getGlVersion();
            $i = 0;
            while ($i < $numXmlSections) {
                $xmlSection = $xmlSections[$i];
                if ($glVersion === (string) $xmlSection['version']) {
                    break;
                }

                ++$i;
            }

            if ($i === $numXmlSections) {
                // Section not found in XML, remove it.
                \Mesamatrix::$logger->debug('Remove GL version: '.$glName.' '.$glVersion);
                $this->removeGlVersion($glSection);
            }
        }

        // Add and merge new sections.
        foreach ($xmlSections as $xmlSection) {
            $glName = (string) $xmlSection['name'];
            $glVersion = (string) $xmlSection['version'];

            $glSection = $this->getGlVersionByName($glName, $glVersion);
            if (!$glSection) {
                $glslName = (string) $xmlSection->glsl['name'];
                $glslVersion = (string) $xmlSection->glsl['version'];

                $glSection = new OglVersion($glName, $glVersion, $glslName, $glslVersion, $this->getHints());
                $this->addGlVersion($glSection);
            }

            $glSection->merge($xmlSection, $commit);
        }
    }

    public function getGlVersions() {
        return $this->glVersions;
    }

    public function getGlVersionByName($name, $version) {
        foreach ($this->glVersions as $glVersion) {
            if ($glVersion->getGlName() === $name &&
                $glVersion->getGlVersion() === $version) {
                return $glVersion;
            }
        }
        return null;
    }

    public function getHints() {
        return $this->hints;
    }
};
