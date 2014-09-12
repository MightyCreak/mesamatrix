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
include("parser.inc.php");

function debug_print($line)
{
    print("DEBUG: ".$line."<br />\n");
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
$formatUpdate = date(DATE_RFC2822, $lastUpdate);

$vendors = array_keys($allDriversVendors);
$vendorsClasses = array();
foreach($vendors as &$vendor)
{
    switch($vendor)
    {
    case "Software":    $vendorsClasses[$vendor] = "hCellVendor-soft"; break;
    case "Intel":       $vendorsClasses[$vendor] = "hCellVendor-intel"; break;
    case "nVidia":      $vendorsClasses[$vendor] = "hCellVendor-nvidia"; break;
    case "AMD":         $vendorsClasses[$vendor] = "hCellVendor-amd"; break;
    default:            $vendorsClasses[$vendor] = "hCellVendor-default"; break;
    }
}

// Write the HTML code.
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="description" content="<?= $config["page"]["description"] ?>"/>

        <title><?= $config["page"]["title"] ?></title>

        <link href="style.css?v=<?= $config["page"]["version"] ?>" rel="stylesheet" type="text/css" media="all"/>
        <script src="script.js"></script>
    </head>
    <body>
        <p><b>This page is generated from:</b> <a href="<?= $gl3TreeUrl ?>"><?= $gl3TreeUrl ?></a> (<a href="<?= $gl3LogUrl ?>">log</a>)<br/>
        <b>Last get date:</b> <script>writeDate("<?= $formatUpdate ?>");</script><noscript><?= $formatUpdate ?></noscript></p>
<?php
foreach($oglMatrix->getGlVersions() as $glVersion)
{
    $text = $glVersion->getGlName()." ".$glVersion->getGlVersion()." - ".$glVersion->getGlslName()." ".$glVersion->getGlslVersion();
    $glUrlId = "Version_".urlencode(str_replace(" ", "", $text));

?>
        <h1 id="<?= $glUrlId ?>">
            <?= $text ?> <a href="#<?= $glUrlId ?>" class="permalink">&para;</a>
        </h1>
        <table class="tableNoSpace">
            <thead class="tableHeaderLine">
                <tr>
                    <th colspan="2"></th>
<?php
    foreach($vendors as &$vendor)
    {
?>
                    <th></th>
                    <th class="<?= $vendorsClasses[$vendor] ?>" colspan="<?= count($allDriversVendors[$vendor]) ?>"><?= $vendor ?></th>
<?php
    }
?>
                </tr>
                <tr>
                    <th class="hCellVendor-default hCell-ext">Extension</th>
                    <th class="hCellVendor-default hCell-driver">mesa</th>
<?php
    foreach($vendors as &$vendor)
    {
?>
                    <th class="hCell-sep"></th>
<?php
        foreach($allDriversVendors[$vendor] as &$driver)
        {
?>
                    <th class="<?= $vendorsClasses[$vendor] ?> hCell-driver"><?= $driver ?></th>
<?php
        }
    }
?>
                </tr>
            </thead>
            <tbody>
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
        $extUrlId = $glUrlId."_Extension_".urlencode(str_replace(" ", "", $ext->getName()));
        $extHintIdx = $ext->getHintIdx();
?>
                <tr class="extension">
                    <td id="<?= $extUrlId ?>"<?php if($extName[0] === "-") { ?> class="extension-child"<?php } ?>>
                        <?= $extName ?> <a href="#<?= $extUrlId ?>" class="permalink">&para;</a>
                    </td>
                    <?php if($extHintIdx !== -1) {
                        echo '<td class="task footnote '.$mesa.'" title="'.$allHints[$extHintIdx].'"><a href="#Footnotes_'.($extHintIdx+1).'">&nbsp;</a></td>';
                    } else {
                        echo '<td class="task '.$mesa.'"></td>';
                    } ?>
<?php

        foreach($vendors as &$vendor)
        {
?>
                    <td></td>
<?php
            foreach($allDriversVendors[$vendor] as &$driver)
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
?>
                <?php if($driverHintIdx !== -1) {
                    echo '<td class="task footnote '.$class.'" title="'.$allHints[$driverHintIdx].'"><a href="#Footnotes_'.($driverHintIdx+1).'">&nbsp;</a></td>';
                } else {
                    echo '<td class="task '.$class.'"></td>';
                } ?>
<?php
            }
    }
?>
                </tr>
<?php
    }
?>
            </tbody>
            <tfoot>
                <tr class="extension">
                    <td><b>Total:</b></td>
                    <td class="hCellVendor-default task"><?= $doneByDriver["mesa"]."/".$numExtensions ?></td>
<?php
    foreach($vendors as &$vendor)
    {
?>
                    <td></td>
<?php
        foreach($allDriversVendors[$vendor] as &$driver)
        {
?>
                    <td class="<?= $vendorsClasses[$vendor] ?> task"><?= $doneByDriver[$driver]."/".$numExtensions ?></td>
<?php
        }
    }
?>
                </tr>
            </tfoot>
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
        <p>You can click here too, if you want to Flattr me:</p>
        <p><script id='fb5dona'>(function(i){var f,s=document.getElementById(i);f=document.createElement('iframe');f.src='//api.flattr.com/button/view/?uid=<?= $config["flattr"]["id"] ?>&url='+encodeURIComponent(document.URL)+'&title='+encodeURIComponent('<?= $config["page"]["title"] ?>')+'&description='+encodeURIComponent('<?= $config["page"]["description"] ?>')+'&language='+encodeURIComponent('<?= $config["flattr"]["language"] ?>')+'&tags=<?= $config["flattr"]["tags"] ?>';f.title='Flattr';f.height=62;f.width=55;f.style.borderWidth=0;s.parentNode.insertBefore(f,s);})('fb5dona');</script></p>
<?php
}
?>
        <h1>License</h1>
        <p><a href="http://www.gnu.org/licenses/"><img src="https://www.gnu.org/graphics/gplv3-127x51.png" alt="Logo GPLv3" /></a></p>
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

