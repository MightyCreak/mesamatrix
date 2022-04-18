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

use Mesamatrix\Mesamatrix;

abstract class BaseController
{
    public $pages = array(
        "Home" => ".",
        "Drivers decoder ring" => "drivers.php",
        "About" => "about.php",
        "Donate?" => "donate.php");

    private $pageIdx = -1;
    private $cssScripts = [];
    private $jsScripts = [];

    public function __construct()
    {
        $this->addCssScript('css/style.css?v=' . (Mesamatrix::$config->getValue("info", "version")));

        $this->addJsScript('js/jquery-1.11.3.min.js');
        $this->addJsScript('js/script.js');
    }

    /**
     * Set the page to highlight in the menu.
     *
     * The page must be one of the page in $this->pages.
     * @param string $page The name of the page.
     */
    final protected function setPage($page): void
    {
        $this->pageIdx = array_search($page, array_keys($this->pages));
    }

    /**
     * Add a CSS script to include in the HTML page header.
     * @param string $script The script path.
     */
    final public function addCssScript($script): void
    {
        $this->cssScripts[] = $script;
    }

    /**
     * Add a JS script to include in the HTML page header.
     * @param string $script The script path.
     */
    final public function addJsScript($script): void
    {
        $this->jsScripts[] = $script;
    }

    /**
     * Write the HTML code.
     */
    final public function writeHtml(): void
    {
        $this->computeRendering();

        $this->writeHtmlHeader();
        $this->writeHtmlPage();
        $this->writeHtmlFooter();
    }

    /**
     * Compute the data needed for rendering.
     */
    abstract protected function computeRendering(): void;

    /**
     * Write the content of the HTML page.
     */
    abstract protected function writeHtmlPage(): void;

    /**
     * Write the HTML page header.
     */
    final protected function writeHtmlHeader(): void
    {
        $projectTitle = Mesamatrix::$config->getValue("info", "title");
        $projectDescription = Mesamatrix::$config->getValue("info", "description");

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8"/>
        <meta name="description" content="{$projectDescription}"/>

        <title>{$projectTitle}</title>

        <meta property="og:title" content="Mesamatrix: {$projectTitle}" />
        <meta property="og:type" content="website" />
        <meta property="og:image" content="//mesamatrix.net/images/mesamatrix-logo.png" />

        <link rel="shortcut icon" href="images/gears.png" />
        <link rel="alternate" type="application/rss+xml" title="rss feed" href="rss.php" />
HTML;

        foreach ($this->cssScripts as $script) :
            echo <<<HTML
        <link href="{$script}" rel="stylesheet" type="text/css" media="all" />
HTML;
        endforeach;

        foreach ($this->jsScripts as $script) :
            echo <<<HTML
        <script src="{$script}"></script>
HTML;
        endforeach;

        echo <<<'HTML'
    </head>
    <body>
        <header>
            <a href="."><img src="images/banner.svg" class="banner" alt="Mesamatrix banner" /></a>

            <div class="menu">
                <ul class="menu-list">
HTML;
        $i = 0;
        foreach ($this->pages as $page => $link) :
            $item = "<a href=\"" . $link . "\">" . $page . "</a>";
            if ($i === $this->pageIdx) :
                echo <<<HTML
                    <li class="menu-item menu-selected">{$item}</li>
HTML;
            else :
                echo <<<HTML
                    <li class="menu-item"><a href="{$link}">{$page}</a></li>
HTML;
            endif;
            ++$i;
        endforeach;

        echo <<<'HTML'
                    <li class="menu-item"><a href="rss.php" class="rss"><img class="rss" src="images/feed.svg" alt="RSS feed" /></a></li>
                </ul>
            </div>

<!-- Matomo -->
<script>
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="https://matomo.foolstep.com/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '1']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->
        </header>
        <main>
HTML;
    }

    /**
     * Write the HTML page footer.
     */
    final protected function writeHtmlFooter(): void
    {
        $projectUrl = Mesamatrix::$config->getValue("info", "project_url");

        echo <<<HTML
        </main>
        <footer>
            <a id="github-ribbon" href="{$projectUrl}">
                <img src="https://camo.githubusercontent.com/652c5b9acfaddf3a9c326fa6bde407b87f7be0f4/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6f72616e67655f6666373630302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_orange_ff7600.png" />
            </a>
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider round"></div>
                </label>
            </div>
        </footer>
    </body>
</html>
HTML;
    }
}
