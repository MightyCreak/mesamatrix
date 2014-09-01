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

$configFile = "config.inc.php";
if(!file_exists($configFile))
{
    exit("The configuration file \"config.inc.php\" doesn't exist.
          Please copy \"example.config.inc.php\", rename it, and
          fill it with the proper information.");
}

include($configFile);

function debug_print($line)
{
    print("DEBUG: ".$line."<br />\n");
}

// List of all the drivers.
$allDrivers = array(
    "softpipe",
    "swrast",
    "llvmpipe",
    "i965",
    "nv50",
    "nvc0",
    "r300",
    "r600",
    "radeonsi");

function array_union(array $a, array $b)
{
    $res = $a;
    foreach($b as $i)
    {
        if(!in_array($i, $res))
        {
            $res[] = $i;
        }
    }

    return $res;
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
        $this->glName = $glName;
        $this->glVersion = $glVersion;
        $this->glslName = $glslName;
        $this->glslVersion = $glslVersion;
        $this->extensions = array();
    }

    /// GL name and version.
    public function setGlName($name)       { $this->glName = $name; }
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
        global $allDrivers, $allHints;

        $handle = fopen($filename, "r");
        if($handle === FALSE)
        {
            return NULL;
        }

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
                    $allSupportedDrivers = array_union($allSupportedDrivers, explode(", ", $matches[1]));
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
                            $supportedDrivers = array_union($supportedDrivers, $parentDrivers);
                        }

                        $matches[2] = trim($matches[2]);
                        $isDone = strncmp($matches[2], "DONE", strlen("DONE")) === 0;
                        if($isDone && !isset($matches[3]))
                        {
                            $supportedDrivers = array_union($supportedDrivers, $allDrivers);
                        }
                        else if($isDone && isset($matches[4]))
                        {
                            $driverFound = FALSE;
                            $driversList = explode(", ", $matches[4]);
                            foreach($driversList as $currentDriver)
                            {
                                if($this->isInDriversArray($currentDriver))
                                {
                                    $supportedDrivers[] = $currentDriver;
                                    $driverFound = TRUE;
                                }
                            }
                            if (!$driverFound && !empty($matches[4]))
                            {
                                if (!in_array($matches[4], $ignoreHints))
                                {
                                    $matches[2] = $matches[2]." ".$matches[4]."";
                                }
                                $supportedDrivers = array_union($supportedDrivers, $allDrivers);
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

        fclose($handle);

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

$gl3Filename = "GL3.txt";
$gl3PlainUrl = "http://cgit.freedesktop.org/mesa/mesa/plain/docs/GL3.txt";
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
    $distantContent = file_get_contents($gl3PlainUrl);
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

// Parse the local file.
$parser = new OglParser();
$oglMatrix = $parser->parse($gl3Filename);
if(!$oglMatrix)
{
    exit("Can't read \"${gl3Filename}\"");
}

$gl3TreeUrl = "http://cgit.freedesktop.org/mesa/mesa/tree/docs/GL3.txt";
$gl3LogUrl = "http://cgit.freedesktop.org/mesa/mesa/log/docs/GL3.txt";

// Write the HTML code.
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="description" content="<?= $config["page"]["description"] ?>"/>

        <title><?= $config["page"]["title"] ?></title>

        <link rel="stylesheet" type="text/css" href="style.css"/>
    </head>
    <body>
    <p><b>This page is generated from:</b> <a href="<?= $gl3TreeUrl ?>"><?= $gl3TreeUrl ?></a> (<a href="<?= $gl3LogUrl ?>">log</a>)</br>
    <b>Last get date:</b> <?= date(DATE_RFC2822, $lastUpdate) ?></p>
<?php
foreach($oglMatrix->getGlVersions() as $glVersion)
{
    $text = $glVersion->getGlName()." ".$glVersion->getGlVersion()." - ".$glVersion->getGlslName()." ".$glVersion->getGlslVersion();
    $urlText = urlencode(str_replace(" ", "", $text));

?>
        <h1 id="Version_<?= $urlText ?>">
            <?= $text ?> <a href="#Version_<?= $urlText ?>" class="permalink">&para;</a>
        </h1>
        <table class="tableNoSpace">
            <tr class="tableHeaderLine">
                <th class="tableHeaderCell-extension">Extension</th>
                <th class="tableHeaderCell">mesa</th>
<?php
    foreach($allDrivers as &$driver)
    {
?>
                <th class="tableHeaderCell"><?= $driver ?></th>
<?php
    }
?>
            </tr>
<?php
    $numExtensions = count($glVersion->getExtensions());
    $doneByDriver = array("mesa" => 0);
    $doneByDriver = array_merge($doneByDriver, array_combine($allDrivers, array_fill(0, count($allDrivers), 0)));

    foreach($glVersion->getExtensions() as $ext)
    {
        if(strncmp($ext->getStatus(), "DONE", strlen("DONE")) === 0)
        {
            $mesa = "isDone";
            ++$doneByDriver["mesa"];
        }
        else if(strncmp($ext->getStatus(), "not started", strlen("not started")) === 0)
        {
            $mesa = "isNotStarted";
        }
        else
        {
            $mesa = "isInProgress";
        }

        $extName = $ext->getName();
        $extUrlName = urlencode(str_replace(" ", "", $ext->getName()));
?>
            <tr class="extension">
                <td id="Extension_<?= $extUrlName ?>"<?php if($extName[0] === "-") { echo "class=\"extension-child\""; } ?>>
                    <?= $extName ?> <a href="#Extension_<?= $extUrlName ?>" class="permalink">&para;</a>
                </td>
<?php
        $extHintIdx = $ext->getHintIdx();
        if($extHintIdx === -1)
        {
?>
                <td class="task <?= $mesa ?>"></td>
<?php
        }
        else
        {
?>
                <td class="task <?= $mesa ?>">
                    <a href="#Footnotes_<?= $extHintIdx + 1 ?>" title="<?= $allHints[$extHintIdx] ?>"><?= $extHintIdx + 1 ?></a>
                </td>
<?php
        }

        foreach($allDrivers as &$driver)
        {
            // Search for the driver in the supported drivers.
            $i = 0;
            $supportedDrivers = $ext->getSupportedDrivers();
            $numSupportedDrivers = count($supportedDrivers);
            while($i < $numSupportedDrivers && strcmp($supportedDrivers[$i]->getName(), $driver) !== 0)
            {
                ++$i;
            }

            $class = "isNotStarted";
            $driverHintIdx = -1;
            if($i < $numSupportedDrivers)
            {
                // Driver found.
                $class = "isDone";
                $driverHintIdx = $supportedDrivers[$i]->getHintIdx();
                ++$doneByDriver[$driver];
            }

            if($driverHintIdx === -1)
            {
?>
                <td class="task <?= $class ?>"></td>
<?php
            }
            else
            {
?>
                <td class="task <?= $class ?>">
                    <a href="#Footnotes_<?= $driverHintIdx + 1 ?>" title="<?= $allHints[$driverHintIdx] ?>"><?= $driverHintIdx + 1 ?></a>
                </td>
<?php
            }
        }
?>
            </tr>
<?php
    }
?>
            <tr class="extension">
                <td><b>Total:</b></td>
                <td class="task"><?= $doneByDriver["mesa"]."/".$numExtensions ?></td>
<?php
    foreach($allDrivers as &$driver)
    {
?>
                <td class="task"><?= $doneByDriver[$driver]."/".$numExtensions ?></td>
<?php
    }
?>
            </tr>
        </table>
<?php
}
?>
        <h1>Footnotes</h1>
        <ol>
<?php
for($i = 0; $i < count($allHints); $i++)
{
?>
            <li id="Footnotes_<?= $i + 1 ?>"><?= $allHints[$i] ?></li>
<?php
}
?>
        </ol>
        <h1>How to help</h1>
        <p>If you find this page useful and want to help, you can report issues, or <a href="https://github.com/MightyCreak/mesamatrix">grab the code</a> and add whatever feature you want.</p>
<?php
if($config["flattr"]["enabled"])
{
?>
        <p>You can also Flattr me so that I can continue to do all that!</p>
        <p><script id='fb5dona'>(function(i){var f,s=document.getElementById(i);f=document.createElement('iframe');f.src='//api.flattr.com/button/view/?uid=<?= $config["flattr"]["id"] ?>&url='+encodeURIComponent(document.URL)+'&title='+encodeURIComponent('<?= $config["page"]["title"] ?>')+'&description='+encodeURIComponent('<?= $config["page"]["description"] ?>');f.title='Flattr';f.height=62;f.width=55;f.style.borderWidth=0;s.parentNode.insertBefore(f,s);})('fb5dona');</script></p>
<?php
}
?>
        <h1>License</h1>
        <p><a href="http://www.gnu.org/licenses/"><img src="https://www.gnu.org/graphics/gplv3-127x51.png" /></a></p>
        <h1>Sources</h1>
        <p>If you want to report a bug or simply to participate in the project, feel free to get the sources on GitHub:
        <a href="https://github.com/MightyCreak/mesamatrix">https://github.com/MightyCreak/mesamatrix</a></p>
        <h1>Authors</h1>
        <ul>
            <li>Romain "Creak" Failliot</li>
            <li>Tobias Droste</li>
        </ul>
    </body>
</html>

