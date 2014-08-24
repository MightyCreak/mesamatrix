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
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with libbench. If not, see <http://www.gnu.org/licenses/>.
 */

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
date_default_timezone_set('UTC');

function debug_print($line)
{
    print("DEBUG: ".$line."<br />\n");
}

function isInDriversArray($name)
{
    global $drivers;

    foreach($drivers as $driverName)
    {
        if (strncmp($name, $driverName, strlen($driverName)) === 0)
        { 
            return TRUE;
        }
    }

    return FALSE;
}

class OglExtension
{
    public function __construct($name, $status, $supportedDrivers = array())
    {
        $this->name = $name;
        $this->status = $status;
        $this->supportedDrivers = $supportedDrivers;
    }

    public function setName($name) { $this->name = $name; }
    public function getName()      { return $this->name; }

    public function write()
    {
        global $hints, $drivers;

        $hint = null;

        if (strncmp($this->status, "DONE", strlen("DONE")) === 0)
        {
            if (strlen(trim($this->status))>strlen("DONE"))
            {
                $hints[] = substr(trim($this->status), strlen("DONE"));
                $hint = count($hints);
            }
            $mesa = "isDone";
        }
        else if (strncmp($this->status, "not started", strlen("not started")) === 0)
        {
            $mesa = "isNotStarted";
        }
        else
        {
            $hints[] = trim($this->status);
            $hint = count($hints);
            $mesa = "isInProgress";
        }

        print("<tr class=\"extension\">\n");
        print("<td id=\"Extension_".str_replace(" ", "", $this->name)."\">".$this->name." <a href=\"#Extension_".str_replace(" ", "", $this->name)."\" class=\"topicFrag\">&para;</a></td>\n");
        if (empty($hint))
        {
            print("<td class=\"task ".$mesa."\"></td>\n");
        }
        else
        {
            print("<td class=\"task ".$mesa."\"><a href=\"#Footnotes_".$hint."\">".$hint."</a></td>\n");
        }
        foreach($drivers as $driver) 
        {
            $this->writeDriverCell($driver);
        }
        print("</tr>\n");
    }

    private function writeDriverCell($driverName)
    {
        global $hints ;

        $class = "isNotStarted";
        $hint = null;
        foreach($this->supportedDrivers as $supportedDriver)
        {
            if (strncmp($supportedDriver, $driverName, strlen($driverName)) === 0)
            { 
                if (strlen(trim($supportedDriver))>strlen($driverName))
                {
                    $hints[] = substr(trim($supportedDriver), strlen($driverName)+1);
                    $hint = count($hints);
                }
                $class = "isDone";
            }
        }

        if (empty($hint))
        {
            print("<td class=\"task ".$class."\"></td>\n");
        }
        else
        {
            print("<td class=\"task ".$class."\"><a href=\"#Footnotes_".$hint."\">".$hint."</a></td>\n");
        }
    }

    private $name;
    private $status;
    private $supportedDrivers;
};

class OglVersion
{
    public function __construct($glName, $glVersion, $glslName, $glslVersion)
    {
        $this->glName = $glName;
        $this->glVersion = $glVersion;
        $this->glslName = $glslName;
        $this->glslVersion = $glslVersion;
        $this->extensions = array();
    }

    public function setGlVersion($version) { $this->glVersion = $version; }
    public function getGlVersion()         { return $this->glVersion; }

    public function setGlslVersion($version) { $this->glslVersion = $version; }
    public function getGlslVersion()         { return $this->glslVersion; }

    public function addExtension($name, $status, $supportedDrivers = array())
    {
        array_push($this->extensions, new OglExtension($name, $status, $supportedDrivers));
    }

    public function write()
    {
        global $drivers;

        $text = $this->glName." ".$this->glVersion." - ".$this->glslName." ".$this->glslVersion;
        print("<h1 id=\"Version_".str_replace(" ", "", $text)."\">".$text." <a href=\"#Version_".str_replace(" ", "", $text)."\" class=\"topicFrag\">&para;</a></h1>\n");
        print("<table class=\"tableNoSpace\">");
        print("<tr class=\"tableHeaderLine\">\n");
        print("<th class=\"tableHeaderCell-extension\">Extension</th>\n");
        print("<th class=\"tableHeaderCell\">mesa</th>\n");
        foreach($drivers as $driver) 
        {
            print("<th class=\"tableHeaderCell\">".$driver."</th>\n");
        }
        print("</tr>\n");
        foreach($this->extensions as &$ext)
        {
            $ext->write();
        }
        print("</table>\n");
    }

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

    public function write()
    {
        foreach($this->glVersions as &$version)
        {
            $version->write();
        }
    }

    private $glVersions;
};

$drivers = array(
    "softpipe",
    "swrast",
    "llvmpipe",
    "i965",
    "nv50",
    "nvc0",
    "r300",
    "r600",
    "radeonsi");
$hints = array();

$gl3Filename = "GL3.txt";
$gl3Url = "http://cgit.freedesktop.org/mesa/mesa/plain/docs/GL3.txt";
$lastUpdate = 0;

$getLatestFileVersion = TRUE;
if(file_exists($gl3Filename))
{
    $mtime = filemtime($gl3Filename);
    if($mtime + 3600 > time())
    {
        $getLatestFileVersion = FALSE;
        $lastUpdate = $mtime;
    }
}

if($getLatestFileVersion)
{
    $distantContent = file_get_contents($gl3Url);
    if($distantContent !== FALSE)
    {
        $cacheHandle = fopen($gl3Filename, "w");
        if($cacheHandle !== FALSE)
        {
            fwrite($cacheHandle, $distantContent);
            fclose($cacheHandle);
            $lastUpdate = time();
        }

        unset($distantContent);
    }
}

$handle = fopen($gl3Filename, "r")
    or exit("Can't read \"${gl3Filename}\"");

$reTableHeader = "/^Feature([ ]+)Status/";
$reVersion = "/^(GL(ES)?) ?([[:digit:]]+\.[[:digit:]]+), (GLSL( ES)?) ([[:digit:]]+\.[[:digit:]]+)/";
$reAllDone = "/ --- all DONE: ((([[:alnum:]]+), )*([[:alnum:]]+))/";
$reExtension = "/^[^.]+$/";

$ignoreHints = array("all drivers");
$oglMatrix = new OglMatrix();

$line = fgets($handle);
while($line !== FALSE)
{
    if(preg_match($reTableHeader, $line, $matches) === 1)
    {
        // Should be $lineWidth-2, but the file has a variable length in column 1
        $lineWidth = strlen("Feature")+strlen($matches[1]);
        $reExtension = "/^  (.{1,".$lineWidth."})[ ]+([^\(]+)(\((.*)\))?/";
        $line = fgets($handle);
        continue;
    }

    if(preg_match($reVersion, $line, $matches) === 1)
    {
        $glVersion = new OglVersion($matches[1], $matches[3], $matches[4], $matches[6]);

        $allSupportedDrivers = array();
        if(preg_match($reAllDone, $line, $matches) === 1)
        {
            $allSupportedDrivers = array_merge($allSupportedDrivers, explode(", ", $matches[1]));
        }

        do
        {
            $line = fgets($handle);
        } while($line !== FALSE && $line === "\n");

        $parentDrivers = NULL;
        while($line !== FALSE && $line !== "\n")
        {
            if(preg_match($reExtension, $line, $matches) === 1)
            {
                $supportedDrivers = $allSupportedDrivers;
                if($matches[1][0] === "-")
                {
                    $supportedDrivers = array_merge($supportedDrivers, $parentDrivers);
                }

                if(strncmp($matches[2], "DONE", strlen("DONE")) === 0 && !isset($matches[3]))
                {
                    $supportedDrivers = array_merge($supportedDrivers, $drivers);
                }
                else if(strncmp($matches[2], "DONE", strlen("DONE")) === 0 && isset($matches[4]))
                {
                    $driverFound = FALSE;
                    $driversList = explode(", ", $matches[4]);
                    foreach($driversList as $currentDriver)
                    {
                        if(isInDriversArray($currentDriver))
                        {
                            $supportedDrivers[] = $currentDriver;
                            $driverFound = TRUE;
                        }
                    }
                    if (!$driverFound && !empty($matches[4]))
                    {
                        if (!in_array(trim($matches[4]), $ignoreHints))
                        {
                            $matches[2] = $matches[2]." ".$matches[4]."";
                        }
                        $supportedDrivers = array_merge($supportedDrivers, $drivers);
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
    }
    
    if($line !== FALSE)
    {
        $line = fgets($handle);
    }
}

fclose($handle);
?>

<html>
    <head>
        <title>The OpenGL vs Mesa matrix</title>
        <link rel="stylesheet" type="text/css" href="style.css" />
    </head>
    <body>
<?php
$oglMatrix->write();
?>
        <h1>Footnotes</h1>
        <p>
<?php
for($i=0; $i<count($hints); $i++) 
{
    print("<sup id=\"Footnotes_".($i+1)."\">".($i+1)."</sup> ".$hints[$i]."<br/>");
}
?>
        </p>
        <h1>License</h1>
        <p><a href="http://www.gnu.org/licenses/"><img src="https://www.gnu.org/graphics/gplv3-127x51.png" /></a></p>
        <h1>Sources</h1>
        <p>GitHub: <a href="https://github.com/MightyCreak/mesamatrix">https://github.com/MightyCreak/mesamatrix</a></p>
        <p>Mesa document used to generate this page: <a href="<?php print($gl3Url); ?>"><?php print($gl3Url); ?></a></p>
        <p>Last get: <?php print(date(DATE_RFC2822, $lastUpdate)); ?></p>
        <h1>Authors</h1>
        <p>Romain "Creak" Failliot</p>
    </body>
</html>

