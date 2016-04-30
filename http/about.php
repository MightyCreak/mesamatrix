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
        <link href="css/style.css?v=<?= Mesamatrix::$config->getValue("info", "version") ?>" rel="stylesheet" type="text/css" media="all"/>
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
                    <li class="menu-item"><a href=".">Home</a></li>
                    <li class="menu-item"><a href="drivers.php">Drivers decoder ring</a></li>
                    <li class="menu-item menu-selected"><a href="about.php">About</a></li>
                </ul>
            </div>

            <h1>About Mesamatrix</h1>
            <h2>How it works</h2>
            <p>Frequently, the Mesa git is fetched and, if there is a new commit for the text file, a PHP script will parse it and format it into XML. Then another PHP script displays the data into the HTML you can see here.</p>
            <h2>Source code</h2>
            <p>The code is free and licenced under AGPLv3. If you want to report a bug, participate to the project or simply browse the code:</p>
            <p><a href="<?= Mesamatrix::$config->getValue("info", "project_url") ?>"><?= Mesamatrix::$config->getValue("info", "project_url") ?></a></p>
            <p><a href="https://www.gnu.org/licenses/agpl.html"><img src="https://www.gnu.org/graphics/agplv3-155x51.png" alt="Logo AGPLv3" /></a></p>
            <h2>See also</h2>
            <p>Here are few links to learn more about the Linux graphics drivers:</p>
            <ul>
                <li>Freedesktop.org: <a href="https://secure.freedesktop.org/~imirkin/glxinfo/glxinfo.html">Ilia Mirkin's glxinfo matrix</a>
                <li>Freedesktop.org: <a href="http://xorg.freedesktop.org/wiki/RadeonFeature/">Radeon Feature</a></li>
                <li>Wikipedia (en): <a href="https://en.wikipedia.org/wiki/Mesa_%28computer_graphics%29" title="Mesa (computer graphics)">Mesa (computer graphics)</a></li>
                <li>Wikipedia (en): <a href="https://en.wikipedia.org/wiki/Radeon" title="Radeon">Radeon</a></li>
                <li>Wikipedia (en): <a href="https://en.wikipedia.org/wiki/Nouveau_%28software%29" title="Nouveau (software)">Nouveau (software)</a></li>
            </ul>
            <h2>Authors</h2>
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

            <footer>
                <a id="github-ribbon" href="<?= Mesamatrix::$config->getValue("info", "project_url") ?>"><img style="position: fixed; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/652c5b9acfaddf3a9c326fa6bde407b87f7be0f4/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6f72616e67655f6666373630302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_orange_ff7600.png" /></a>
            </footer>
        </div>
    </body>
</html>

