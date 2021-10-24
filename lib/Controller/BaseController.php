<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014-2017 Romain "Creak" Failliot.
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

namespace Mesamatrix\Controller;

abstract class BaseController
{
    public $pages = array(
        "Home" => ".",
        "Drivers decoder ring" => "drivers.php",
        "About" => "about.php");

    private $pageIdx = -1;
    private $cssScripts = [];
    private $jsScripts = [];

    public function __construct() {
        $this->addCssScript('css/style.css?v='.(\Mesamatrix::$config->getValue("info", "version")));

        $this->addJsScript('js/jquery-1.11.3.min.js');
    }

    /**
     * Set the page to highlight in the menu.
     *
     * The page must be one of the page in $this->pages.
     * @param string $page The name of the page.
     */
    final protected function setPage($page) {
        $this->pageIdx = array_search($page, array_keys($this->pages));
    }

    /**
     * Add a CSS script to include in the HTML page header.
     * @param string $script The script path.
     */
    final public function addCssScript($script) {
        $this->cssScripts[] = $script;
    }

    /**
     * Add a JS script to include in the HTML page header.
     * @param string $script The script path.
     */
    final public function addJsScript($script) {
        $this->jsScripts[] = $script;
    }

    /**
     * Write the HTML code.
     */
    final public function writeHtml() {
        $this->computeRendering();

        $this->writeHtmlHeader();
        $this->writeHtmlPage();
        $this->writeHtmlFooter();
    }

    /**
     * Compute the data needed for rendering.
     */
    abstract protected function computeRendering();

    /**
     * Write the content of the HTML page.
     */
    abstract protected function writeHtmlPage();

    /**
     * Write the HTML page header.
     */
    final protected function writeHtmlHeader() {
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8"/>
        <meta name="description" content="<?= \Mesamatrix::$config->getValue("info", "description") ?>"/>

        <title><?= \Mesamatrix::$config->getValue("info", "title") ?></title>

        <meta property="og:title" content="Mesamatrix: <?= \Mesamatrix::$config->getValue("info", "title") ?>" />
        <meta property="og:type" content="website" />
        <meta property="og:image" content="//mesamatrix.net/images/mesamatrix-logo.png" />

        <link rel="shortcut icon" href="images/gears.png" />
        <link rel="alternate" type="application/rss+xml" title="rss feed" href="rss.php" />

<?php
        foreach ($this->cssScripts as $script):
?>
        <link href="<?= $script ?>" rel="stylesheet" type="text/css" media="all" />
<?php
        endforeach;
?>

<?php
        foreach ($this->jsScripts as $script):
?>
        <script src="<?= $script ?>"></script>
<?php
        endforeach;
?>
    </head>
    <body>
        <header>
            <a href="."><img src="images/banner.svg" class="banner" alt="Mesamatrix banner" /></a>

            <div class="menu">
                <ul class="menu-list">
<?php
        $i = 0;
        foreach ($this->pages as $page => $link):
            $item = "<a href=\"".$link."\">".$page."</a>";
            if ($i === $this->pageIdx):
?>
                    <li class="menu-item menu-selected"><?= $item ?></li>
<?php
            else:
?>
                    <li class="menu-item"><a href="<?= $link ?>"><?= $page ?></a></li>
<?php
            endif;
            ++$i;
        endforeach;
?>
                    <li class="menu-item"><a href="rss.php" class="rss"><img class="rss" src="images/feed.svg" alt="RSS feed" /></a></li>
                </ul>
            </div>
        </header>
        <main>
<?php
    }

    /**
     * Write the HTML page footer.
     */
    final protected function writeHtmlFooter() {
?>
        </main>
        <footer>
            <a id="github-ribbon" href="<?= \Mesamatrix::$config->getValue("info", "project_url") ?>"><img src="https://camo.githubusercontent.com/652c5b9acfaddf3a9c326fa6bde407b87f7be0f4/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6f72616e67655f6666373630302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_orange_ff7600.png" /></a>
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider round"></div>
                </label>
            </div>
        </footer>
    </body>
</html>
<?php
    }
};
