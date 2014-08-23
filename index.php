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
        switch($this->status)
        {
        case "DONE":
            $mesa = "isDone";
            break;

        case "started":
        case "in progress":
            $mesa = "isInProgress";
            break;

        case "not started":
        default:
            $mesa = "isNotStarted";
            break;
        }

        $softpipe = in_array("softpipe", $this->supportedDrivers) ? "isDone" : "isNotStarted";
        $swrast = in_array("swrast", $this->supportedDrivers) ? "isDone" : "isNotStarted";
        $llvmpipe = in_array("llvmpipe", $this->supportedDrivers) ? "isDone" : "isNotStarted";
        $i965 = in_array("i965", $this->supportedDrivers) ? "isDone" : "isNotStarted";
        $nv50 = in_array("nv50", $this->supportedDrivers) ? "isDone" : "isNotStarted";
        $nvc0 = in_array("nvc0", $this->supportedDrivers) ? "isDone" : "isNotStarted";
        $r300 = in_array("r300", $this->supportedDrivers) ? "isDone" : "isNotStarted";
        $r600 = in_array("r600", $this->supportedDrivers) ? "isDone" : "isNotStarted";
        $radeonsi = in_array("radeonsi", $this->supportedDrivers) ? "isDone" : "isNotStarted";
        print("<tr>\n");
        print("<td>".$this->name."</td>\n");
        print("<td class=\"task ".$mesa."\"></td>\n");
        print("<td class=\"task ".$softpipe."\"></td>\n");
        print("<td class=\"task ".$swrast."\"></td>\n");
        print("<td class=\"task ".$llvmpipe."\"></td>\n");
        print("<td class=\"task ".$i965."\"></td>\n");
        print("<td class=\"task ".$nv50."\"></td>\n");
        print("<td class=\"task ".$nvc0."\"></td>\n");
        print("<td class=\"task ".$r300."\"></td>\n");
        print("<td class=\"task ".$r600."\"></td>\n");
        print("<td class=\"task ".$radeonsi."\"></td>\n");
        print("</tr>\n");
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
        print("<h1>".$this->glName." ".$this->glVersion." - ".$this->glslName." ".$this->glslVersion."</h1>\n");
        print("<table class=\"tableNoSpace\">");
        print("<tr class=\"tableHeaderLine\">\n");
        print("<th class=\"tableHeaderCell-extension\">Extension</th>\n");
        print("<th class=\"tableHeaderCell\">mesa</th>\n");
        print("<th class=\"tableHeaderCell\">softpipe</th>\n");
        print("<th class=\"tableHeaderCell\">swrast</th>\n");
        print("<th class=\"tableHeaderCell\">llvmpipe</th>\n");
        print("<th class=\"tableHeaderCell\">i965</th>\n");
        print("<th class=\"tableHeaderCell\">nv50</th>\n");
        print("<th class=\"tableHeaderCell\">nvc0</th>\n");
        print("<th class=\"tableHeaderCell\">r300</th>\n");
        print("<th class=\"tableHeaderCell\">r600</th>\n");
        print("<th class=\"tableHeaderCell\">radeonsi</th>\n");
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

$flags = array(
    "not started",
    "started",
    "in progress",
    "DONE");

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

$reVersion = "/^(GL(ES)?) ?([[:digit:]]+\.[[:digit:]]+), (GLSL( ES)?) ([[:digit:]]+\.[[:digit:]]+)/";
$reAllDone = "/ --- all DONE: ((([[:alnum:]]+), )*([[:alnum:]]+))/";
$reExtension = "/^  (.+) [ ]+(DONE|not started|started|in progress)( \((.+)\))?/";

$oglMatrix = new OglMatrix();

$line = fgets($handle);
while($line !== FALSE)
{
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

        while($line !== FALSE && $line !== "\n")
        {
            if(preg_match($reExtension, $line, $matches) === 1)
            {
                $supportedDrivers = $allSupportedDrivers;
                if(isset($matches[4]))
                {
                    if(strncmp($matches[4], "all drivers", count("all drivers")) === 0)
                    {
                        $supportedDrivers = array_merge($supportedDrivers, $drivers);
                    }
                    else
                    {
                        $supportedDrivers = array_merge($supportedDrivers, explode(", ", $matches[4]));
                    }
                }

                $glVersion->addExtension(trim($matches[1]), $matches[2], $supportedDrivers);
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
        <h1>Source</h1>
        <p><a href="<?php print($gl3Url); ?>"><?php print($gl3Url); ?></a></p>
        <p>Last get: <?php print(date(DATE_RFC2822, $lastUpdate)); ?></p>
        <h1>Authors</h1>
        <p>Romain "Creak" Failliot</p>
        <h1>License</h1>
        <p></p>
        <p><a href="http://www.gnu.org/licenses/"><img src="https://www.gnu.org/graphics/gplv3-127x51.png" /></a></p>
    </body>
</html>

