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

require_once "../lib/base.php";

/////////////////////////////////////////////////
// Load XML.

$gl3Path = Mesamatrix::path(Mesamatrix::$config->getValue("info", "xml_file"));

// Read "xml_file".
$xml = simplexml_load_file($gl3Path);
if (!$xml) {
    exit("Can't read '".$gl3Path."'");
}

// Set all the versions in an array so that it can be sorted out.
$glVersions = array();
foreach ($xml->gl as $glVersion) {
    $glVersions[] = $glVersion;
}

// Sort the versions.
usort($glVersions, function($a, $b) {
        // Sort OpenGL before OpenGLES and higher versions before lower ones.
        if ((string) $a["name"] === (string) $b["name"]) {
            $diff = (float) $b["version"] - (float) $a["version"];
            if ($diff === 0)
                return 0;
            else
                return $diff < 0 ? -1 : 1;
        }
        elseif ((string) $a["name"] === "OpenGL") {
            return 1;
        }
        else {
            return 1;
        }
    });

/////////////////////////////////////////////////
// Hints.
//
$hints = new Mesamatrix\Hints();
foreach ($glVersions as $glVersion) {
    foreach ($glVersion->extension as $ext) {
        if ($ext->mesa["hint"]) {
            $hints->addHint((string) $ext->mesa["hint"]);
        }

        foreach ($xml->drivers->vendor as $vendor) {
            foreach ($vendor->driver as $driver) {
                $driverNode = $ext->supported->{$driver["name"]};
                if ($driverNode) {
                    // Driver found.
                    if ($driverNode["hint"]) {
                        $hints->addHint((string) $driverNode["hint"]);
                    }
                }
            }
        }
    }
}

/////////////////////////////////////////////////
// Leaderboard.
//
$leaderboard = new Mesamatrix\Leaderboard();
$leaderboard->load($xml);
$driversExtsDone = $leaderboard->getDriversSortedByExtsDone();
$numTotalExts = $leaderboard->getNumTotalExts();

/////////////////////////////////////////////////
// Update times.
//
$updateTime = filemtime($gl3Path);
$lastGitUpdate = "No commit found";
if (count($xml->commits->commit) > 0) {
    $lastGitUpdate = date(DATE_RFC2822, (int) $xml->commits->commit[0]["timestamp"]);
}

/////////////////////////////////////////////////
// Drivers CSS classes.
//
foreach ($xml->drivers->vendor as $vendor) {
    switch($vendor["name"]) {
    case "Software": $vendor->addAttribute("class", "hCellVendor-soft"); break;
    case "Intel":    $vendor->addAttribute("class", "hCellVendor-intel"); break;
    case "nVidia":   $vendor->addAttribute("class", "hCellVendor-nvidia"); break;
    case "AMD":      $vendor->addAttribute("class", "hCellVendor-amd"); break;
    default:         $vendor->addAttribute("class", "hCellVendor-default"); break;
    }
}

/////////////////////////////////////////////////
// Write the HTML code.
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="description" content="<?= Mesamatrix::$config->getValue("info", "description") ?>"/>

        <title><?= Mesamatrix::$config->getValue("info", "title") ?></title>

        <link rel="shortcut icon" href="images/gears.png" />
        <link href="css/style.css?v=<?= Mesamatrix::$config->getValue("info", "version") ?>" rel="stylesheet" type="text/css" media="all"/>
        <link href="css/tipsy.css" rel="stylesheet" type="text/css" media="all" />
        <script src="js/jquery-1.11.1.min.js"></script>
        <script src="js/jquery.tipsy.js"></script>
        <script src="js/script.js"></script>
    </head>
    <body>
        <h1>Last commits</h1>
        <p><b>Last git update:</b> <script>document.write(getLocalDate("<?= $lastGitUpdate ?>"));</script><noscript><?= $lastGitUpdate ?></noscript> (<a href="<?= Mesamatrix::$config->getValue("git", "web")."/log/docs/GL3.txt" ?>">see the log</a>)</p>
        <table class="commits">
            <thead>
                <tr>
                    <th>Age</th>
                    <th>Commit message</th>
                </tr>
            </thead>
           <tbody>
<?php
foreach ($xml->commits->commit as $commit) {
    $commitDate = date(DATE_RFC2822, (int) $commit["timestamp"]);
    $commitUrl = Mesamatrix::$config->getValue("git", "web")."/commit/".Mesamatrix::$config->getValue("git", "gl3")."?id=".$commit["hash"];
?>
                <tr>
                    <td class="commitsAge"><script>document.write(getRelativeDate('<?= $commitDate ?>'));</script><noscript><?= $commitDate ?></noscript></td>
                    <td><a href="<?= $commitUrl ?>"><?= $commit["subject"] ?></a></td>
                </tr>
<?php
}
?>
            </tbody>
        </table>
        <h1>Leaderboard</h1>
        <table class="lb">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Driver</th>
                    <th>Score</th>
                    <th>Completion</th>
                </tr>
            </thead>
            <tbody>
<?php
$rank = 1;
foreach($driversExtsDone as $drivername => $numExtsDone) {
?>
                <tr>
                    <th class="lbCol-rank"><?= $rank ?></td>
                    <td class="lbCol-driver"><?= $drivername ?></td>
                    <td class="lbCol-score"><?= $numExtsDone." / ".$numTotalExts ?></td>
                    <td class="lbCol-score"><?php printf("%.1f%%", ($numExtsDone / $numTotalExts * 100)) ?></td>
                </tr>
<?php
    $rank++;
}
?>
            </tbody>
        </table>
<?php

foreach ($glVersions as $glVersion) {
    $text = $glVersion["name"]." ".$glVersion["version"]." - ".$glVersion->glsl["name"]." ".$glVersion->glsl["version"];
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
    foreach ($xml->drivers->vendor as $vendor) {
?>
                    <th></th>
                    <th class="<?= $vendor["class"] ?>" colspan="<?= count($vendor->driver) ?>"><?= $vendor["name"] ?></th>
<?php
    }
?>
                </tr>
                <tr>
                    <th class="hCellVendor-default hCell-ext">Extension</th>
                    <th class="hCellVendor-default hCell-driver">mesa</th>
<?php
    foreach ($xml->drivers->vendor as $vendor) {
?>
                    <th class="hCell-sep"></th>
<?php
        foreach ($vendor->driver as $driver) {
?>
                    <th class="<?= $vendor["class"] ?> hCell-driver"><?= $driver["name"] ?></th>
<?php
        }
    }
?>
                </tr>
            </thead>
            <tbody>
<?php
    foreach ($glVersion->extension as $ext) {
        $taskClasses = "task";
        if ($ext->mesa["status"] == "complete") {
            $taskClasses .= " isDone";
        }
        elseif ($ext->mesa["status"] == "incomplete") {
            $taskClasses .= " isNotStarted";
        }
        else {
            $taskClasses .= " isInProgress";
        }

        $extUrlId = $glUrlId."_Extension_".urlencode(str_replace(" ", "", $ext["name"]));
        $extHintIdx = $hints->findHint($ext->mesa["hint"]);
        if ($extHintIdx !== -1) {
            $taskClasses .= " footnote";
        }

        $extNameText = $ext["name"];
        if (isset($ext->link)) {
            $extNameText = str_replace($ext->link, "<a href=\"".$ext->link["href"]."\">".$ext->link."</a>", $extNameText);
        }
?>
                <tr class="extension">
                    <td id="<?= $extUrlId ?>"<?php if (strncmp($ext["name"], "-", 1) === 0) { ?> class="extension-child"<?php } ?>>
                        <?= $extNameText ?> <a href="#<?= $extUrlId ?>" class="permalink">&para;</a>
                    </td>
                    <td class="<?= $taskClasses ?>"><?php if ($extHintIdx !== -1) { ?><a href="#Footnotes_<?= $extHintIdx + 1 ?>" title="<?= ($extHintIdx + 1).". ".$ext->mesa["hint"] ?>">&nbsp;</a><?php } ?></td>
<?php

        foreach ($xml->drivers->vendor as $vendor) {
?>
                    <td></td>
<?php
            foreach ($vendor->driver as $driver) {
                $driverNode = $ext->supported->{$driver["name"]};
                $extHintIdx = -1;
                $taskClasses = "task";
                if ($driverNode) {
                    // Driver found.
                    $taskClasses .= " isDone";
                    $driver["done"] += 1;
                    $extHintIdx = $hints->findHint($driverNode["hint"]);
                    if ($extHintIdx !== -1) {
                        $taskClasses .= " footnote";
                    }
                }
                else {
                    $taskClasses .= " isNotStarted";
                }
?>
                    <td class="<?= $taskClasses ?>"><?php if ($extHintIdx !== -1) { ?><a href="#Footnotes_<?= $extHintIdx + 1 ?>" title="<?= ($extHintIdx + 1).". ".$driverNode["hint"] ?>">&nbsp;</a><?php } ?></td>
<?php
            }
        }
?>
                </tr>
<?php
    }

    $lbGlVersion = $leaderboard->findGlVersion($glVersion["name"].$glVersion["version"]);
    if ($lbGlVersion !== NULL) {
        $numGlVersionExts = $lbGlVersion->getNumExts();
?>
            </tbody>
            <tfoot>
                <tr class="extension">
                    <td><b>Total:</b></td>
                    <td class="hCellVendor-default task"><?= $lbGlVersion->getNumDriverExtsDone("mesa")."/".$numGlVersionExts ?></td>
<?php
        foreach ($xml->drivers->vendor as $vendor) {
?>
                    <td></td>
<?php
            foreach ($vendor->driver as $driver) {
	        $driverName = (string) $driver["name"];
?>
                    <td class="<?= $vendor["class"] ?> task"><?= $lbGlVersion->getNumDriverExtsDone($driverName)."/".$numGlVersionExts ?></td>
<?php
            }
        }
?>
                </tr>
            </tfoot>
        </table>
<?php
    }
}
?>
        <h1>Footnotes</h1>
        <ol>
<?php
$numHints = $hints->getNumHints();
for($i = 0; $i < $numHints; $i++) {
?>
            <li id="Footnotes_<?= $i + 1 ?>"><?= $hints->getHint($i) ?></li>
<?php
}
?>
        </ol>
        <h1>How to help</h1>
        <p>If you find this page useful and want to help, you can report issues, or <a href="https://github.com/MightyCreak/mesamatrix">grab the code</a> and add whatever feature you want.</p>
<?php
if (Mesamatrix::$config->getValue("flattr", "enabled")) {
?>
        <p>You can click here too, if you want to Flattr me:</p>
        <p><script id='fb5dona'>(function(i){var f,s=document.getElementById(i);f=document.createElement('iframe');f.src='//api.flattr.com/button/view/?uid=<?= Mesamatrix::$config->getValue("flattr", "id") ?>&url='+encodeURIComponent(document.URL)+'&title='+encodeURIComponent('<?= Mesamatrix::$config->getValue("info", "title") ?>')+'&description='+encodeURIComponent('<?= Mesamatrix::$config->getValue("info", "description") ?>')+'&language='+encodeURIComponent('<?= Mesamatrix::$config->getValue("flattr", "language") ?>')+'&tags=<?= Mesamatrix::$config->getValue("flattr", "tags") ?>';f.title='Flattr';f.height=62;f.width=55;f.style.borderWidth=0;s.parentNode.insertBefore(f,s);})('fb5dona');</script></p>
<?php
}
?>
        <h1>Learn more</h1>
        <p>Here are few links to learn more about the Linux graphics drivers:</p>
        <ul>
            <li>Freedesktop.org: <a href="http://xorg.freedesktop.org/wiki/RadeonFeature/#index5h2">Radeon GPUs</a></li>
            <li>Wikipedia (en): <a href="https://en.wikipedia.org/wiki/Mesa_%28computer_graphics%29" title="Mesa (computer graphics)">Mesa (computer graphics)</a></li>
            <li>Wikipedia (en): <a href="https://en.wikipedia.org/wiki/Radeon" title="Radeon">Radeon</a></li>
            <li>Wikipedia (en): <a href="https://en.wikipedia.org/wiki/Nouveau_%28software%29" title="Nouveau (software)">Nouveau (software)</a></li>
        </ul>
        <h1>Sources</h1>
        <p><b>This page is generated from:</b> <a href="<?= Mesamatrix::$config->getValue("git", "web")."/tree/".Mesamatrix::$config->getValue("git", "gl3") ?>"><?= Mesamatrix::$config->getValue("git", "web")."/tree/".Mesamatrix::$config->getValue("git", "gl3") ?></a></p>
        <p>If you want to report a bug or simply to participate in the project, feel free to get the sources:
        <a href="<?= Mesamatrix::$config->getValue("info", "project_url") ?>"><?= Mesamatrix::$config->getValue("info", "project_url") ?></a></p>
        <p><a href="http://www.gnu.org/licenses/"><img src="https://www.gnu.org/graphics/gplv3-127x51.png" alt="Logo GPLv3" /></a></p>
        <h1>Authors</h1>
        <ul>
<?php
foreach (Mesamatrix::$config->getValue("info", "authors") as $k => $v) {
    if (is_string($k)) {
?>
            <li><a href="<?= $v ?>"><?= $k ?></a></li>
<?php
    }
    else {
?>
            <li><?= $v ?></li>
<?php
    }
}
?>
        </ul>
    </body>
</html>

