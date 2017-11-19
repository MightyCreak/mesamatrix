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

require_once '../lib/base.php';

/////////////////////////////////////////////////
// Create commits model.
//

function createCommitsModel(SimpleXMLElement $xml) {
    $commits = array();

    $numCommits = \Mesamatrix::$config->getValue('info', 'commitlog_length', 10);
    $numCommits = min($numCommits, $xml->commits->commit->count());
    for ($i = 0; $i < $numCommits; ++$i) {
        $xmlCommit = $xml->commits->commit[$i];
        $commits[] = array(
            'url' => Mesamatrix::$config->getValue('git', 'mesa_web').'/commit/'.Mesamatrix::$config->getValue('git', 'gl3').'?id='.$xmlCommit['hash'],
            'timestamp' => (int) $xmlCommit['timestamp'],
            'subject' => $xmlCommit['subject']
        );
    }

    return $commits;
}

/////////////////////////////////////////////////
// Create matrix model.
//
function createColumns(array &$matrix, array $apis) {
    $matrix['column_groups'] = array();
    $matrix['columns'] = array();

    $columnIdx = 0;

    // Add "extension" columon.
    $matrix['column_groups'][] = array(
        'name' => '',
        'vendor_class' => 'default',
        'columns' => array($columnIdx++)
    );
    $matrix['columns'][] = array(
        'name' => 'Extension',
        'type' => 'extension',
        'vendor_class' => 'default'
    );

    // Add "mesa" column.
    $matrix['column_groups'][] = array(
        'name' => '',
        'vendor_class' => 'default',
        'columns' => array($columnIdx++)
    );
    $matrix['columns'][] = array(
        'name' => 'mesa',
        'type' => 'driver',
        'vendor_class' => 'default'

    );

    // Get all the vendors and all their drivers.
    $vendors = array();
    foreach ($apis as $api) {
        foreach ($api->drivers->vendor as $vendor) {
            $vendorName = (string) $vendor['name'];
            if (!array_key_exists($vendorName, $vendors)) {
                $vendors[$vendorName] = array();
            }

            $driverNames = &$vendors[$vendorName];
            foreach ($vendor->driver as $driver) {
                $driverName = (string) $driver['name'];
                if (!in_array($driverName, $driverNames)) {
                    $driverNames[] = $driverName;
                }
            }
        }
    }

    unset($driverNames);

    foreach ($vendors as $vendorName => $driverNames) {
        // Add separator before each vendor.
        $matrix['column_groups'][] = array(
            'name' => '',
            'columns' => array($columnIdx++)
        );
        $matrix['columns'][] = array(
            'name' => '',
            'type' => 'separator',
        );

        // Add vendor drivers columns.
        $colgroup = array(
            'name' => $vendorName,
            'vendor_class' => strtolower($vendorName),
            'columns' => array()
        );
        foreach ($driverNames as $driverName) {
            $colgroup['columns'][] = $columnIdx++;
            $matrix['columns'][] = array(
                'name' => $driverName,
                'type' => 'driver',
                'vendor_class' => $colgroup['vendor_class']
            );
        }

        $matrix['column_groups'][] = $colgroup;
    }
}

function addExtension(array &$subsection, SimpleXMLElement $glExt, $isSubExt, SimpleXMLElement $drivers) {
    global $xml;

    $extension = array(
        'name' => $glExt['name'],
    );

    $extUrlId = str_replace(' ', '_', $glExt['name']);
    $extUrlId = preg_replace('/[^A-Za-z0-9_]/', '', $extUrlId);
    $extUrlId = $subsection['target'].'_Extension_'.$extUrlId;
    $extension['target'] = $extUrlId;
    $extension['is_subext'] = $isSubExt;

    if (isset($glExt->link)) {
        $extension['url_href'] = $glExt->link['href'];
        $extension['url_text'] = $glExt->link;
    }

    $extTask = array();
    if ($glExt->mesa['status'] == \Mesamatrix\Parser\Constants::STATUS_DONE)
        $extTask['class'] = 'isDone';
    elseif ($glExt->mesa['status'] == \Mesamatrix\Parser\Constants::STATUS_NOT_STARTED)
        $extTask['class'] = 'isNotStarted';
    else
        $extTask['class'] = 'isInProgress';
    if (!empty($glExt->mesa->modified))
        $extTask['timestamp'] = (int) $glExt->mesa->modified->date;
    $extTask['hint'] = $glExt->mesa['hint'];
    $extension['tasks']['mesa'] = $extTask;

    foreach ($drivers->vendor as $vendor) {
        foreach ($vendor->driver as $driver) {
            $driverName = (string) $driver['name'];

            $extTask = array();
            $driverNode = $glExt->supported->{$driverName};
            if ($driverNode) {
                $extTask['class'] = 'isDone';
                $extTask['timestamp'] = (int) $driverNode->modified->date;
            }
            else {
                $extTask['class'] = 'isNotStarted';
            }
            $extTask['hint'] = $driverNode['hint'];
            $extension['tasks'][$driverName] = $extTask;
        }
    }

    $subsection['extensions'][] = $extension;
}

function addSection(array &$section, SimpleXMLElement $glSection, SimpleXMLElement $drivers) {
    global $leaderboard, $xml;

    $text = "";
    if (!empty($glSection['version'])) {
        $text = $glSection['name'];
        if (!empty((string) $glSection['version'])) {
            $text .= ' '.$glSection['version'];
        }
        if (!empty((string) $glSection->glsl['name'])) {
            $text .= ' - '.$glSection->glsl['name'].' '.$glSection->glsl['version'];
        }
    }

    $subsection = array(
        'name' => $text,
        'target' => !empty($text) ? 'Version_'.urlencode(str_replace(' ', '', $text)) : '',
        'scores' => array(),
        'extensions' => array()
    );

    $lbGlVersion = $leaderboard->findGlVersion($glSection['name'].$glSection['version']);
    if ($lbGlVersion !== NULL) {
        $numGlVersionExts = $lbGlVersion->getNumExts();

        $driverScores = array();
        $driverScores['mesa'] = $lbGlVersion->getNumDriverExtsDone('mesa') / $numGlVersionExts;
        foreach ($drivers->vendor as $vendor) {
            foreach ($vendor->driver as $driver) {
                $driverName = (string) $driver['name'];
                $driverScores[$driverName] = $lbGlVersion->getNumDriverExtsDone($driverName) / $numGlVersionExts;
            }
        }

        $subsection['scores'] = $driverScores;

        foreach ($glSection->extension as $glExt) {
            addExtension($subsection, $glExt, false, $drivers);
            foreach ($glExt->subextension as $glSubExt)
                addExtension($subsection, $glSubExt, true, $drivers);
        }
    }

    $section['subsections'][] = $subsection;
}

function addApi(array &$matrix, \SimpleXmlElement $xmlApi) {
    $glVersions = array();
    foreach ($xmlApi->version as $glVersion) {
        $glVersions[] = $glVersion;
    }

    if (empty($glVersions))
        return;

    // Sort the versions descending.
    usort($glVersions, function($a, $b) {
        $diff = (float) $b['version'] - (float) $a['version'];
        if ($diff === 0)
            return 0;
        else
            return $diff < 0 ? -1 : 1;
    });

    $api = array(
        'name' => $xmlApi['name'],
        'target' => 'Version_'.urlencode(str_replace(' ', '', $xmlApi['name'])),
        'subsections' => array()
    );

    foreach ($glVersions as $glVersion) {
        addSection($api, $glVersion, $xmlApi->drivers);
    }

    $matrix['sections'][] = $api;
}

function createMatrixModel(SimpleXMLElement $xml) {
    $openglApis = array();
    foreach ($xml->apis->api as $api) {
        switch ((string) $api['name']) {
        case 'OpenGL':
        case 'OpenGL ES':
        case \Mesamatrix\Parser\Constants::GL_OR_ES_EXTRA_NAME:
            $openglApis[] = $api;
            break;
        }
    }

    $matrix = array();
    createColumns($matrix, $openglApis);

    $matrix['sections'] = array();
    foreach ($openglApis as $api) {
        addApi($matrix, $api);
    }

    $matrix['last_updated'] = (int) $xml['updated'];

    return $matrix;
}

/////////////////////////////////////////////////
// Load XML.
//
$gl3Path = Mesamatrix::path(Mesamatrix::$config->getValue('info', 'xml_file'));
$xml = simplexml_load_file($gl3Path);
if (!$xml) {
    \Mesamatrix::$logger->critical('Can\'t read '.$gl3Path);
    exit();
}

/////////////////////////////////////////////////
// Leaderboard.
//
$leaderboard = new Mesamatrix\Leaderboard();
$leaderboard->load($xml, [ 'OpenGL', 'OpenGL ES', \Mesamatrix\Parser\Constants::GL_OR_ES_EXTRA_NAME ]);
$driversExtsDone = $leaderboard->getDriversSortedByExtsDone();
$numTotalExts = $leaderboard->getNumTotalExts();

// Create models from XML.
$commits = createCommitsModel($xml);
$matrix = createMatrixModel($xml);

/////////////////////////////////////////////////
// Unset non-HTML variables.
//
unset($xml);

/////////////////////////////////////////////////
// HTML code.
//
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8"/>
        <meta name="description" content="<?= Mesamatrix::$config->getValue("info", "description") ?>"/>

        <title><?= Mesamatrix::$config->getValue("info", "title") ?></title>

        <meta property="og:title" content="Mesamatrix: <?= Mesamatrix::$config->getValue("info", "title") ?>" />
        <meta property="og:type" content="website" />
        <meta property="og:image" content="//mesamatrix.net/images/mesamatrix-logo.png" />

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
                (<a href="https://cgit.freedesktop.org/mesa/mesa/tree/docs/features.txt">features.txt</a>).
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
// Commit list.
foreach ($commits as $commit):
?>
                            <tr>
                                <td class="commitsAge toRelativeDate" data-timestamp="<?= date(DATE_RFC2822, $commit['timestamp']) ?>"><?= date('Y-m-d H:i', $commit['timestamp']) ?></td>
                                <td><a href="<?= $commit['url'] ?>"><?= $commit['subject'] ?></a></td>
                            </tr>
<?php
endforeach;
?>
                            <tr>
                                <td colspan="2">
                                    <noscript>(Dates are UTC)<br/></noscript>
                                    <a href="<?= Mesamatrix::$config->getValue("git", "mesa_web")."/log/docs/features.txt" ?>">More...</a>
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
            <table class="matrix">
<?php
// Colgroups.
foreach($matrix['column_groups'] as $colgroup):
    foreach($colgroup['columns'] as $colIdx):
        $col = $matrix['columns'][$colIdx];
        if ($col['type'] === 'driver'):
?>
                <colgroup class="hl">
<?php
        else:
?>
                <colgroup>
<?php
        endif;
    endforeach;
endforeach;

// Sections.
foreach($matrix['sections'] as $section):
?>
                <tr>
                    <td id="<?= $section['target'] ?>" colspan="<?= count($matrix['columns']) ?>" class="hCellGl">
                        <?= $section['name'] ?><a href="#<?= $section['target'] ?>" class="permalink">&para;</a>
                    </td>
                </tr>
<?php
    // Sub-sections.
    foreach($section['subsections'] as $subsection):
?>
                <tr>
                    <td id="<?= $subsection['target'] ?>" colspan="<?= count($matrix['columns']) ?>" class="hCellGlVersion">
<?php
        if (!empty($subsection['name'])):
?>
                        <?= $subsection['name'] ?><a href="#<?= $subsection['target'] ?>" class="permalink">&para;</a>
<?php
        endif;
?>
                    </td>
                </tr>
                <tr>
<?php
        // Header (vendors).
        foreach($matrix['column_groups'] as $colgroup):
            if (empty($colgroup['name'])):
?>
                    <td></td>
<?php
            else:
?>
                    <td colspan="<?= count($colgroup['columns']) ?>" class="hCellHeader hCellVendor-<?= $colgroup['vendor_class'] ?>"><?= $colgroup['name'] ?></td>
<?php
            endif;
        endforeach;
?>
                </tr>
                <tr>
<?php
        // Header (drivers).
        foreach($matrix['columns'] as $col):
            if ($col['type'] === 'extension'):
?>
                    <td class="hCellHeader hCellVendor-default"><?= $col['name'] ?></td>
<?php
            elseif ($col['type'] === 'driver'):
?>
                    <td class="hCellHeader hCellVendor-<?= $col['vendor_class'] ?>"><?= $col['name'] ?></td>
<?php
            elseif ($col['type'] === 'separator'):
?>
                    <td class="hCellSep"></td>
<?php
            else:
?>
                    <td><?= $col['name'] ?></td>
<?php
            endif;
        endforeach;
?>
                </tr>
                <tr>
<?php
        // Scores.
        foreach($matrix['columns'] as $col):
            if ($col['type'] === 'driver'):
                $scoreStr = sprintf('%.1f', $subsection['scores'][$col['name']] * 100);
?>
                    <td class="hCellHeader hCellDriverScore" data-score="<?= $scoreStr ?>"><?= $scoreStr ?>%</td>
<?php
            else:
?>
                    <td></td>
<?php
            endif;
        endforeach;
?>
                </tr>
<?php
        // Extensions.
        foreach ($subsection['extensions'] as $extension):
?>
                <tr class="extension">
<?php
            foreach($matrix['columns'] as $col):
                if ($col['type'] === 'extension'):
                    $extNameText = $extension['name'];
                    if (isset($extension['url_text'])):
                        $extNameText = str_replace($extension['url_text'], '<a href="'.$extension['url_href'].'">'.$extension['url_text'].'</a>', $extNameText);
                    endif;
                    $cssClass = '';
                    if ($extension['is_subext']):
                        $cssClass = ' class="extension-child"';
                    endif;
?>
                    <td id="<?= $extension['target'] ?>"<?= $cssClass ?>>
                        <?= $extNameText ?><a href="#<?= $extension['target'] ?>" class="permalink">&para;</a>
                    </td>
<?php
                elseif ($col['type'] === 'driver'):
                    $driverTask = $extension['tasks'][$col['name']];
                    $cssClasses = array($driverTask['class']);
                    $title = '';
                    if (isset($driverTask['hint'])):
                        $cssClasses[] = 'footnote';
                        $title = ' title="'.$driverTask['hint'].'"';
                    endif;
                    if (isset($driverTask['timestamp'])):
?>
                    <td class="task <?= join($cssClasses, ' ') ?>"<?= $title ?>>
                        <span data-timestamp="<?= $driverTask['timestamp'] ?>"><?= date('Y-m-d', $driverTask['timestamp']) ?></span>
                    </td>
<?php
                    else:
?>
                    <td class="task <?= $driverTask['class'] ?>"></td>
<?php
                    endif;
                else:
?>
                    <td></td>
<?php
                endif;
            endforeach;
?>
                </tr>
<?php
        endforeach;
    endforeach;
endforeach;
?>
            </table>
            <p><b>Last time features.txt was parsed:</b> <span class="toLocalDate" data-timestamp="<?= date(DATE_RFC2822, $matrix['last_updated']) ?>"><?= date('Y-m-d H:i O', $matrix['last_updated']) ?></span>.</p>

            <footer>
                <a id="github-ribbon" href="<?= Mesamatrix::$config->getValue("info", "project_url") ?>"><img style="position: fixed; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/652c5b9acfaddf3a9c326fa6bde407b87f7be0f4/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6f72616e67655f6666373630302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_orange_ff7600.png" /></a>
            </footer>
        </div>
    </body>
</html>

