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
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="description" content="<?= Mesamatrix::$config->getValue("info", "description") ?>"/>

        <title><?= Mesamatrix::$config->getValue("info", "title") ?></title>

        <link rel="shortcut icon" href="images/gears.png" />
        <link rel="alternate" type="application/rss+xml" title="rss feed" href="rss.php" />
        <link rel="stylesheet" type="text/css" href="css/style.css?v=<?= Mesamatrix::$config->getValue("info", "version") ?>" media="all"/>

        <script src="js/jquery-1.11.3.min.js"></script>
        <script src="js/drivers.js"></script>
    </head>
    <body>
        <div id="main">
            <header>
                <img src="images/banner.svg" class="banner" alt="Mesamatrix banner" />
                <div class="header-icons">
                    <a href="rss.php"><img class="rss" src="images/feed.svg" alt="RSS feed" /></a>
                </div>
            </header>

            <div class="menu menu-horizontal">
                <ul class="menu-list">
                    <li class="menu-item"><a href="." class="menu-link">Home</a></li>
                    <li class="menu-item menu-selected"><a href="drivers.php" class="menu-link">Drivers decoder ring</a></li>
                    <li class="menu-item"><a href="about.php" class="menu-link">About</a></li>
                </ul>
            </div>

            <h1>Easy decoder ring</h1>
            <p>Note: this is a beta, it only works for AMD graphics cards (for now).</p>
            <p>Enter the commercial name of your GPU (e.g. HD 6870):</p>
            <form id="driverform" action="#">
                <input type="text" value="" />
                <input type="submit" value="Find my driver" />
            </form>
            <p id="result"></p>
            <p>
                All the data are from the
                <a href="http://xorg.freedesktop.org/wiki/RadeonFeature/#index5h2">FreeDesktop decoder ring</a>.
            </p>
        </div>
        <footer>
            <a href="<?= Mesamatrix::$config->getValue("info", "project_url") ?>"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/652c5b9acfaddf3a9c326fa6bde407b87f7be0f4/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6f72616e67655f6666373630302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_orange_ff7600.png" /></a>
        </footer>
    </body>
</html>
