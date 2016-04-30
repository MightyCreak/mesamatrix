<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014-2015 Romain "Creak" Failliot.
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

require_once "../lib/base.php";

/////////////////////////////////////////////////
// Hints functions.
//
function registerExtensionHints(SimpleXMLElement $glExt, SimpleXMLElement $xml, Mesamatrix\Hints $hints) {
    if ($glExt->mesa["hint"]) {
        $hints->addHint((string) $glExt->mesa["hint"]);
    }

    foreach ($xml->drivers->vendor as $vendor) {
        foreach ($vendor->driver as $driver) {
            $driverNode = $glExt->supported->{$driver["name"]};
            if ($driverNode) {
                // Driver found.
                if ($driverNode["hint"]) {
                    $hints->addHint((string) $driverNode["hint"]);
                }
            }
        }
    }
}

function registerHints(array $glVersions, SimpleXMLElement $xml) {
    $hints = new Mesamatrix\Hints();
    foreach ($glVersions as $glVersion) {
        foreach ($glVersion->extension as $glExt) {
            registerExtensionHints($glExt, $xml, $hints);

            foreach ($glExt->subextension as $glSubExt) {
                registerExtensionHints($glSubExt, $xml, $hints);
            }
        }
    }

    return $hints;
}

/////////////////////////////////////////////////
// Write HTML functions.
//
function writeExtension(SimpleXMLElement $glExt, $glUrlId, SimpleXMLElement $xml, Mesamatrix\Hints $hints) {
    $taskClasses = "task";
    if ($glExt->mesa["status"] == \Mesamatrix\Parser\Constants::STATUS_DONE) {
        $taskClasses .= " isDone";
    }
    elseif ($glExt->mesa["status"] == \Mesamatrix\Parser\Constants::STATUS_NOT_STARTED) {
        $taskClasses .= " isNotStarted";
    }
    else {
        $taskClasses .= " isInProgress";
    }

    $cellText = '';
    if (!empty($glExt->mesa->modified)) {
        $cellText = '<span data-timestamp="' . $glExt->mesa->modified->date . '">'.date('Y-m-d', (int) $glExt->mesa->modified->date).'</span>';
    }

    $extUrlId = str_replace(" ", "_", $glExt["name"]);
    $extUrlId = preg_replace('/[^A-Za-z0-9_]/', '', $extUrlId);
    $extUrlId = $glUrlId."_Extension_".$extUrlId;
    $extHintIdx = $hints->findHint($glExt->mesa["hint"]);
    if ($extHintIdx !== -1) {
        $taskClasses .= " footnote";
    }

    $extNameText = $glExt["name"];
    if (isset($glExt->link)) {
        $extNameText = str_replace($glExt->link, '<a href="'.$glExt->link['href'].'">'.$glExt->link.'</a>', $extNameText);
    }

    $isSubExt = strncmp($glExt["name"], "-", 1) === 0;
?>
                <tr class="extension">
                    <td id="<?= $extUrlId ?>"<?php if ($isSubExt) { ?> class="extension-child"<?php } ?>>
                        <?= $extNameText ?><a href="#<?= $extUrlId ?>" class="permalink">&para;</a>
                    </td>
                    <td class="<?= $taskClasses ?>"<?php if ($extHintIdx !== -1) { ?> title="<?= $glExt->mesa['hint']; ?>"<?php } ?>><?= $cellText ?></td>
<?php

    foreach ($xml->drivers->vendor as $vendor) {
?>
                    <td></td>
<?php
        foreach ($vendor->driver as $driver) {
            $driverNode = $glExt->supported->{$driver["name"]};
            $extHintIdx = -1;
            $taskClasses = "task";
            $cellText = '';
            if ($driverNode) {
                // Driver found.
                $taskClasses .= " isDone";
                $driver["done"] += 1;
                $extHintIdx = $hints->findHint($driverNode["hint"]);
                if ($extHintIdx !== -1) {
                    $taskClasses .= " footnote";
                }
                if (!empty($driverNode->modified)) {
                    $cellText = '<span data-timestamp="' . $driverNode->modified->date . '">'.date('Y-m-d', (int) $driverNode->modified->date).'</span>';
                }
            }
            else {
                $taskClasses .= " isNotStarted";
            }
?>
                    <td class="<?= $taskClasses ?>"<?php if ($extHintIdx !== -1) { ?> title="<?= $driverNode['hint']; ?>"<?php } ?>><?= $cellText ?></td>
<?php
        }
    }
?>
                </tr>
<?php
}

function writeExtensionList(SimpleXMLElement $glVersion, $glUrlId, SimpleXMLElement $xml, Mesamatrix\Hints $hints) {
    foreach ($glVersion->extension as $glExt) {
        writeExtension($glExt, $glUrlId, $xml, $hints);

        foreach ($glExt->subextension as $glSubExt) {
            writeExtension($glSubExt, $glUrlId, $xml, $hints);
        }
    }
}

function writeVersions(array $glVersions, SimpleXMLElement $xml, Mesamatrix\Hints $hints, Mesamatrix\Leaderboard $leaderboard) {
    foreach ($glVersions as $glVersion) {
        $text = $glVersion["name"]." ".$glVersion["version"]." - ".$glVersion->glsl["name"]." ".$glVersion->glsl["version"];
        $glUrlId = "Version_".urlencode(str_replace(" ", "", $text));
        $lbGlVersion = $leaderboard->findGlVersion($glVersion["name"].$glVersion["version"]);

        $numGlVersionExts = 0;
        $numGlVersionExtsDone = 0;
        $driverExtsDone = array();
        $mesaScore = 0.0;
        if ($lbGlVersion !== NULL) {
            $numGlVersionExts = $lbGlVersion->getNumExts();

            $numGlVersionExtsDone = $lbGlVersion->getNumDriverExtsDone("mesa");
            $driverExtsDone["mesa"] = $numGlVersionExtsDone;
            foreach ($xml->drivers->vendor as $vendor) {
                foreach ($vendor->driver as $driver) {
                    $driverName = (string) $driver["name"];
                    $extsDone = $lbGlVersion->getNumDriverExtsDone($driverName);
                    $numGlVersionExtsDone += $extsDone;
                    $driverExtsDone[$driverName] = $extsDone;
                }
            }

            $mesaScore = sprintf("%.1f", $driverExtsDone["mesa"] / $numGlVersionExts * 100.0);
        }

        // Write OpenGL version header.
?>
            <h2 id="<?= $glUrlId ?>">
                <?= $text ?> <span class="mesaScore" data-score="<?= $mesaScore ?>"><?= $mesaScore ?>%</span><a href="#<?= $glUrlId ?>" class="permalink">&para;</a>
            </h2>
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
        // Write OpenGL version extensions.
        writeExtensionList($glVersion, $glUrlId, $xml, $hints);
?>
                </tbody>
<?php

        // Write OpenGL version footer.
        if ($lbGlVersion !== NULL) {
?>
                <tfoot>
                    <tr class="extension">
                        <td><b>Total:</b></td>
                        <td class="hCellVendor-default task"><?= $driverExtsDone["mesa"]."/".$numGlVersionExts ?></td>
<?php
            foreach ($xml->drivers->vendor as $vendor) {
?>
                        <td></td>
<?php
                foreach ($vendor->driver as $driver) {
                    $driverName = (string) $driver["name"];
?>
                        <td class="<?= $vendor["class"] ?> task"><?= $driverExtsDone[$driverName]."/".$numGlVersionExts ?></td>
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
}

function writeMatrix(array $allVersions, SimpleXMLElement $xml, Mesamatrix\Hints $hints, Mesamatrix\Leaderboard $leaderboard) {
    $glVersions = array_filter($allVersions, function($v) {
            return (string) $v["name"] === "OpenGL";
        });
?>
        <h1>OpenGL <a href="#Version_OpenGL" class="permalink">&para;</a></h1>
<?php
    writeVersions($glVersions, $xml, $hints, $leaderboard);

    $glVersions = array_filter($allVersions, function($v) {
            return (string) $v["name"] === "OpenGL ES";
        });
?>
        <h1>OpenGL ES<a href="#Version_OpenGLES" class="permalink">&para;</a></h1>
<?php
    writeVersions($glVersions, $xml, $hints, $leaderboard);
}

/////////////////////////////////////////////////
// Load XML.
//
$gl3Path = Mesamatrix::path(Mesamatrix::$config->getValue("info", "xml_file"));

// Read "xml_file".
$xml = simplexml_load_file($gl3Path);
if (!$xml) {
    \Mesamatrix::$logger->critical("Can't read ".$gl3Path);
    exit();
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
            return -1;
        }
        else {
            return 1;
        }
    });

// Register hints.
$hints = registerHints($glVersions, $xml);

/////////////////////////////////////////////////
// Leaderboard.
//
$leaderboard = new Mesamatrix\Leaderboard();
$leaderboard->load($xml);
$driversExtsDone = $leaderboard->getDriversSortedByExtsDone();
$numTotalExts = $leaderboard->getNumTotalExts();

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
// HTML code.
//
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="description" content="<?= Mesamatrix::$config->getValue("info", "description") ?>"/>

        <title><?= Mesamatrix::$config->getValue("info", "title") ?></title>

        <link rel="shortcut icon" href="images/gears.png" />
        <link rel="alternate" type="application/rss+xml" title="rss feed" href="rss.php" />
        <link href="css/style.css?v=<?= Mesamatrix::$config->getValue("info", "version") ?>" rel="stylesheet" type="text/css" media="all"/>
        <link href="css/tipsy.css" rel="stylesheet" type="text/css" media="all" />
        <script src="js/jquery-1.11.3.min.js"></script>
        <script src="js/jquery.tipsy.js"></script>
        <script src="js/script.js"></script>
    </head>
    <body>
        <div id="main">
            <header>
                <a href="."><img src="images/banner.svg" class="banner" alt="Mesamatrix banner" /></a>
                <div class="header-icons">
                    <a href="rss.php"><img class="rss" src="images/feed.svg" alt="RSS feed" /></a>
                </div>
            </header>

            <div class="menu">
                <ul class="menu-list">
                    <li class="menu-item menu-selected"><a href=".">Home</a></li>
                    <li class="menu-item"><a href="drivers.php">Drivers decoder ring</a></li>
                    <li class="menu-item"><a href="about.php">About</a></li>
                </ul>
            </div>

            <p>
                Mesamatrix is a mere graphical representation of a text file from the Mesa git repository
                (<a href="http://cgit.freedesktop.org/mesa/mesa/tree/docs/GL3.txt">GL3.txt</a>).
                Some subtleties may lie in the source code, so if you want the most accurate information, you can subscribe to the mailing-list.
            </p>
            <div class="stats">
                <div class="stats-commits">
                    <h1>Last commits</h1>
                    <table class="commits">
                        <thead>
                            <tr>
                                <th>Age</th>
                                <th>Commit message</th>
                            </tr>
                        </thead>
                        <tbody>
<?php
$numCommits = \Mesamatrix::$config->getValue('info', 'commitlog_length', 10);
$numCommits = min($numCommits, $xml->commits->commit->count());
for ($i = 0; $i < $numCommits; ++$i) {
    $commit = $xml->commits->commit[$i];
    $commitUrl = Mesamatrix::$config->getValue("git", "mesa_web")."/commit/".Mesamatrix::$config->getValue("git", "gl3")."?id=".$commit["hash"];
?>
                            <tr>
                                <td class="commitsAge toRelativeDate" data-timestamp="<?= date(DATE_RFC2822, (int) $commit['timestamp']) ?>"><?= date('Y-m-d H:i', (int) $commit['timestamp']) ?></td>
                                <td><a href="<?= $commitUrl ?>"><?= $commit["subject"] ?></a></td>
                            </tr>
<?php
}
?>
                            <tr>
                                <td colspan="2">
                                    <noscript>(Dates are UTC)<br/></noscript>
                                    <a href="<?= Mesamatrix::$config->getValue("git", "mesa_web")."/log/docs/GL3.txt" ?>">More...</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="stats-lb">
                    <h1>Leaderboard</h1>
                    <p>There is a total of <strong><?= $numTotalExts ?></strong> extensions to implement.
                    The ranking is based on the number of extensions done by driver. </p>
                    <table class="lb">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Driver</th>
                                <th>Extensions</th>
                                <th>OpenGL</th>
                                <th>OpenGL ES</th>
                            </tr>
                        </thead>
                        <tbody>
<?php
$index = 1;
$rank = 1;
$prevNumExtsDone = -1;
foreach($driversExtsDone as $drivername => $numExtsDone) {
    $sameRank = $prevNumExtsDone === $numExtsDone;
    if (!$sameRank) {
        $rank = $index;
    }
    switch ($rank) {
    case 1: $rankClass = "lbCol-1st"; break;
    case 2: $rankClass = "lbCol-2nd"; break;
    case 3: $rankClass = "lbCol-3rd"; break;
    default: $rankClass = "";
    }
    $pctScore = sprintf("%.1f%%", $numExtsDone / $numTotalExts * 100);
    $openglVersion = $leaderboard->getDriverGlVersion($drivername);
    if ($openglVersion === NULL) {
        $openglVersion = "N/A";
    }
    $openglesVersion = $leaderboard->getDriverGlesVersion($drivername);
    if ($openglesVersion === NULL) {
        $openglesVersion = "N/A";
    }
?>
                            <tr class="<?= $rankClass ?>">
                                <th class="lbCol-rank"><?= !$sameRank ? $rank : "" ?></th>
                                <td class="lbCol-driver"><?= $drivername ?></td>
                                <td class="lbCol-score"><span class="lbCol-pctScore">(<?= $pctScore ?>)</span> <?= $numExtsDone ?></td>
                                <td class="lbCol-version"><?= $openglVersion ?></td>
                                <td class="lbCol-version"><?= $openglesVersion ?></td>
                            </tr>
<?php
    $prevNumExtsDone = $numExtsDone;
    $index++;
}
?>
                        </tbody>
                    </table>
                </div>
            </div>
<?php
// Write the OpenGL matrix.
writeMatrix($glVersions, $xml, $hints, $leaderboard);
?>
            <p><b>Last time GL3.txt was parsed:</b> <span class="toLocalDate" data-timestamp="<?= date(DATE_RFC2822, (int) $xml['updated']) ?>"><?= date('Y-m-d H:i O', (int) $xml['updated']) ?></span>.</p>

            <footer>
                <a id="github-ribbon" href="<?= Mesamatrix::$config->getValue("info", "project_url") ?>"><img style="position: fixed; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/652c5b9acfaddf3a9c326fa6bde407b87f7be0f4/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6f72616e67655f6666373630302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_orange_ff7600.png" /></a>
            </footer>
        </div>
    </body>
</html>

