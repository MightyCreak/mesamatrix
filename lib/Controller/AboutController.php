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

class AboutController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->setPage('About');
    }

    protected function computeRendering()
    {
    }

    protected function writeHtmlPage()
    {
        $mesaWeb = Mesamatrix::$config->getValue('git', 'mesa_web');
        $mesaBranch = Mesamatrix::$config->getValue('git', 'branch');
        $projectUrl = Mesamatrix::$config->getValue('info', 'project_url');

        echo <<<HTML
<h1>About Mesamatrix</h1>
<h2>How it works</h2>
<p>
    The Mesa <a href="{$mesaWeb}" target="_blank">git repo</a> is frequently fetched and, if there is a new commit for
    the <a href="{$mesaWeb}/blob/{$mesaBranch}/docs/features.txt" target="_blank">features.txt</a> text file, a
    <abbr title="PHP: Hypertext Preprocessor">PHP</abbr> script will parse it and format it into
    <abbr title="Extensible Markup Language">XML</abbr>. Then another PHP script displays the data into the
    <abbr title="Hypertext Markup Language">HTML</abbr> you can see here.
</p>
<h2>Source code</h2>
<p>
    The code is free and licensed under <abbr title="Affero General Public License">AGPL</abbr> v3. If you want to
    report a bug, participate to the project or simply browse the code: <a href="{$projectUrl}">{$projectUrl}</a></p>
<p>
    <a href="https://www.gnu.org/licenses/agpl.html">
        <img src="https://www.gnu.org/graphics/agplv3-155x51.png" alt="Logo AGPLv3" height="51" />
    </a>
</p>
<h2>Contact</h2>
<p>
    Feel free to join the Matrix room
    <a href="https://matrix.to/#/#mesamatrix:matrix.org" target="_blank">#mesamatrix:matrix.org</a> and discuss.
</p>
<h3>Authors</h3>
<ul>
HTML;

        foreach (Mesamatrix::$config->getValue("info", "authors") as $k => $v) :
            if (is_string($k)) :
                echo <<<HTML
    <li><a href="{$v}">{$k}</a></li>
HTML;
            else :
                echo <<<HTML
    <li>{$v}</li>
HTML;
            endif;
        endforeach;

        echo <<<HTML
</ul>
<h2>See also</h2>
<p>Here are few links to learn more about the Linux graphics drivers:</p>
<ul>
    <li>Freedesktop.org: <a href="https://secure.freedesktop.org/~imirkin/glxinfo/glxinfo.html">Ilia Mirkin's glxinfo matrix</a></li>
    <li>Freedesktop.org: <a href="https://xorg.freedesktop.org/wiki/RadeonFeature/">Radeon Feature</a></li>
    <li>Freedesktop.org: <a href="https://nouveau.freedesktop.org/FeatureMatrix.html">Nouveau Feature Matrix</a></li>
    <li>Wikipedia (en): <a href="https://en.wikipedia.org/wiki/Mesa_%28computer_graphics%29" title="Mesa (computer graphics)">Mesa (computer graphics)</a></li>
    <li>Wikipedia (en): <a href="https://en.wikipedia.org/wiki/Radeon" title="Radeon">Radeon</a></li>
    <li>Wikipedia (en): <a href="https://en.wikipedia.org/wiki/Nouveau_%28software%29" title="Nouveau (software)">Nouveau (software)</a></li>
</ul>
HTML;
    }
}
