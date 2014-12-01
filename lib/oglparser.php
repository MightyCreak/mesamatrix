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

// List of all the drivers.
$allDrivers = array(
    "swrast",
    "softpipe",
    "llvmpipe",
    "i965",
    "nv50",
    "nvc0",
    "r300",
    "r600",
    "radeonsi");

$allDriversVendors = array(
    "Software"  => array($allDrivers[0], $allDrivers[1], $allDrivers[2]),
    "Intel"     => array($allDrivers[3]),
    "nVidia"    => array($allDrivers[4], $allDrivers[5]),
    "AMD"       => array($allDrivers[6], $allDrivers[7], $allDrivers[8]),
);

// Hints gathered during the parsing.
$allHints = array();
function addToHints($hint)
{
    global $allHints;

    $idx = -1;
    if(!empty($hint))
    {
        $idx = array_search($hint, $allHints);
        if($idx === FALSE)
        {
            $allHints[] = $hint;
            $idx = count($allHints) - 1;
        }
    }

    return $idx;
}

function getDriverName($name)
{
    global $allDrivers;

    foreach($allDrivers as $driver)
    {
        $driverLen = strlen($driver);
        if(strncmp($name, $driver, $driverLen) === 0)
        {
            return $driver;
        }
    }

    return NULL;
}

function mergeDrivers(array &$dst, array $src)
{
    foreach($src as $srcDriver)
    {
        $driverName = getDriverName($srcDriver);

        $i = 0;
        $numDstDrivers = count($dst);
        while($i < $numDstDrivers && strncmp($dst[$i], $driverName, strlen($driverName)) !== 0)
        {
            $i++;
        }

        if($i < $numDstDrivers)
        {
            $dst[$i] = $srcDriver;
        }
        else
        {
            $dst[] = $srcDriver;
        }
    }

    return $dst;
}

class OglSupportedDriver
{
    public function __construct($name)
    {
        global $allDrivers;

        $this->name = "<undefined>";
        $this->hintIdx = -1;
        foreach($allDrivers as $driver)
        {
            $driverLen = strlen($driver);
            if(strncmp($name, $driver, $driverLen) === 0)
            {
                $this->name = $driver;
                $this->setHint(substr($name, $driverLen + 1));
            }
        }
    }

    public function setName($name) { $this->name = $name; }
    public function getName()      { return $this->name; }

    public function setHint($hint) { $this->hintIdx = addToHints($hint); }
    public function getHintIdx()   { return $this->hintIdx; }

    private $name;
    private $hintIdx;
};

class OglExtension
{
    public function __construct($name, $status, $supportedDrivers = array())
    {
        global $allDrivers;

        $this->name = $name;
        $this->status = $status;

        // Set the hint.
        $hint = "";
        if(strncmp($status, "DONE", strlen("DONE")) === 0)
        {
            $hint = substr($status, strlen("DONE") + 1);
        }
        else if(strncmp($status, "not started", strlen("not started")) === 0)
        {
            $hint = substr($status, strlen("not started") + 1);
        }
        else
        {
            $hint = $status;
        }

        $this->setHint($hint);

        // Set the supported drivers list.
        $this->supportedDrivers = array();
        foreach($supportedDrivers as &$driverName)
        {
            $this->supportedDrivers[] = new OglSupportedDriver($driverName);
        }
    }

    public function setName($name) { $this->name = $name; }
    public function getName()      { return $this->name; }

    public function setStatus($status) { $this->status = $status; }
    public function getStatus()        { return $this->status; }

    public function setHint($hint) { $this->hintIdx = addToHints($hint); }
    public function getHintIdx()   { return $this->hintIdx; }

    public function addSupportedDriver($supportedDriver) { $this->supportedDrivers[] = $supportedDriver; }
    public function getSupportedDrivers()                { return $this->supportedDrivers; }

    private $name;
    private $status;
    private $hintIdx;
    private $supportedDrivers;
};

class OglVersion
{
    public function __construct($glName, $glVersion, $glslName, $glslVersion)
    {
        $this->setGlName($glName);
        $this->glVersion = $glVersion;
        $this->glslName = $glslName;
        $this->glslVersion = $glslVersion;
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
    public function addExtension($name, $status, $supportedDrivers = array())
    {
        $this->extensions[] = new OglExtension($name, $status, $supportedDrivers);
    }

    public function getExtensions() { return $this->extensions; }

    private $glName;
    private $glVersion;
    private $glslName;
    private $glslVersion;
    private $extensions;
};

class OglMatrix
{
    public function __construct()
    {
        $this->glVersions = array();
    }

    public function addGlVersion($glVersion)
    {
        array_push($this->glVersions, $glVersion);
    }

    public function getGlVersions()
    {
        return $this->glVersions;
    }

    private $glVersions;
};

class OglParser
{
    public function parse($filename)
    {
        $handle = fopen($filename, "r");
        if($handle === FALSE)
        {
            return NULL;
        }

        $ret = $this->parse_stream($handle);
        fclose($handle);
        return $ret;
    }

    public function parse_content($content)
    {
        $handle = fopen("php://memory", "r+");
        fwrite($handle, $content);
        rewind($handle);
        $ret = $this->parse_stream($handle);
        fclose($handle);
        return $ret;
    }

    public function parse_stream($handle)
    {
        global $allDrivers, $allHints;

        // Regexp patterns.
        $reTableHeader = "/^Feature([ ]+)Status/";
        $reVersion = "/^(GL(ES)?) ?([[:digit:]]+\.[[:digit:]]+), (GLSL( ES)?) ([[:digit:]]+\.[[:digit:]]+)/";
        $reAllDone = "/ --- all DONE: (.*)/";
        $reExtension = "/^[^.]+$/";
        $reNote = "/^(\(.+\)) (.*)$/";

        $ignoreHints = array("all drivers");
        $oglMatrix = new OglMatrix();

        $line = fgets($handle);
        while($line !== FALSE)
        {
            if(preg_match($reTableHeader, $line, $matches) === 1)
            {
                // Should be $lineWidth-2, but the file has a variable length in column 1
                $lineWidth = strlen("Feature") + strlen($matches[1]);
                $reExtension = "/^  (.{1,".$lineWidth."})[ ]+([^\(]+)(\((.*)\))?$/";
                $line = fgets($handle);
                continue;
            }

            if(preg_match($reVersion, $line, $matches) === 1)
            {
                $glVersion = new OglVersion($matches[1], $matches[3], $matches[4], $matches[6]);

                $allSupportedDrivers = array();
                if(preg_match($reAllDone, $line, $matches) === 1)
                {
                    mergeDrivers($allSupportedDrivers, explode(", ", $matches[1]));
                }

                $line = $this->skipEmptyLines(fgets($handle), $handle);

                $parentDrivers = NULL;
                while($line !== FALSE && $line !== "\n")
                {
                    if(preg_match($reExtension, $line, $matches) === 1)
                    {
                        $supportedDrivers = $allSupportedDrivers;
                        $matches[1] = trim($matches[1]);
                        if($matches[1][0] === "-")
                        {
                            mergeDrivers($supportedDrivers, $parentDrivers);
                        }

                        $matches[2] = trim($matches[2]);
                        $isDone = strncmp($matches[2], "DONE", strlen("DONE")) === 0;
                        if($isDone && !isset($matches[3]))
                        {
                            mergeDrivers($supportedDrivers, $allDrivers);
                        }
                        else if($isDone && isset($matches[4]))
                        {
                            $driverFound = FALSE;
                            $driversList = explode(", ", $matches[4]);
                            foreach($driversList as $currentDriver)
                            {
                                if($this->isInDriversArray($currentDriver))
                                {
                                    mergeDrivers($supportedDrivers, [$currentDriver]);
                                    $driverFound = TRUE;
                                }
                            }
                            if (!$driverFound && !empty($matches[4]))
                            {
                                if (!in_array($matches[4], $ignoreHints))
                                {
                                    $matches[2] = $matches[2]." ".$matches[4]."";
                                }
                                mergeDrivers($supportedDrivers, $allDrivers);
                            }
                        }
                        else if (isset($matches[4]) && !empty($matches[4]))
                        {
                            $matches[2] = $matches[2]." (".$matches[4].")";
                        }

                        if($matches[1][0] !== "-")
                        {
                            $parentDrivers = $supportedDrivers;
                        }

                        $glVersion->addExtension($matches[1], $matches[2], $supportedDrivers);
                    }

                    $line = fgets($handle);
                }

                $oglMatrix->addGlVersion($glVersion);

                $line = $this->skipEmptyLines($line, $handle);

                while($line !== FALSE && preg_match($reNote, $line, $matches) === 1)
                {
                    $idx = array_search($matches[1], $allHints);
                    if($idx !== FALSE)
                    {
                        $allHints[$idx] = $matches[2];
                    }

                    $line = fgets($handle);
                }
            }
            else
            {
                $line = fgets($handle);
            }
        }

        return $oglMatrix;
    }

    private function skipEmptyLines($curLine, $handle)
    {
        while($curLine !== FALSE && $curLine === "\n")
        {
            $curLine = fgets($handle);
        }

        return $curLine;
    }

    private function isInDriversArray($name)
    {
        global $allDrivers;

        foreach($allDrivers as $driverName)
        {
            if (strncmp($name, $driverName, strlen($driverName)) === 0)
            {
                return TRUE;
            }
        }

        return FALSE;
    }
};
