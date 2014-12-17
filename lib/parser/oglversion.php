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

class OglVersion
{
    public function __construct($glName, $glVersion, $glslName, $glslVersion, $hints) {
        $this->setGlName($glName);
        $this->glVersion = $glVersion;
        $this->glslName = $glslName;
        $this->glslVersion = $glslVersion;
        $this->hints = $hints;
        $this->extensions = array();
    }

    /// GL name and version.
    public function setGlName($name)       { $this->glName = "Open".$name; }
    public function getGlName()            { return $this->glName; }
    public function setGlVersion($version) { $this->glVersion = $version; }
    public function getGlVersion()         { return $this->glVersion; }

    /// GLSL name and version.
    public function setGlslName($name)       { $this->glslName = $name; }
    public function getGlslName()            { return $this->glslName; }
    public function setGlslVersion($version) { $this->glslVersion = $version; }
    public function getGlslVersion()         { return $this->glslVersion; }

    /// GL/GLSL extensions.
    public function addExtension($name, $status, $supportedDrivers = array()) {
        $this->extensions[] = new OglExtension($name, $status, $this->hints, $supportedDrivers);
    }

    public function getExtensions() { return $this->extensions; }

    private $glName;
    private $glVersion;
    private $glslName;
    private $glslVersion;
    private $hints;
    private $extensions;
};
