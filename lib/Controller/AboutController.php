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

class AboutController extends BaseController
{
    public function __construct() {
        parent::__construct();

        $this->setPage('About');
    }

    protected function computeRendering() {
    }

    protected function writeHtmlPage() {
?>
            <h1>About Mesamatrix</h1>
            <h2>How it works</h2>
            <p>Frequently, the Mesa git is fetched and, if there is a new commit for the text file, a PHP script will parse it and format it into <abbr title="Extensible Markup Language">XML</abbr>. Then another PHP script displays the data into the HTML you can see here.</p>
            <h2>Source code</h2>
            <p>The code is free and licenced under AGPLv3. If you want to report a bug, participate to the project or simply browse the code:</p>
            <p><a href="<?= \Mesamatrix::$config->getValue("info", "project_url") ?>"><?= \Mesamatrix::$config->getValue("info", "project_url") ?></a></p>
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
foreach (\Mesamatrix::$config->getValue("info", "authors") as $k => $v):
    if (is_string($k)):
?>
                <li><a href="<?= $v ?>"><?= $k ?></a></li>
<?php
    else:
?>
                <li><?= $v ?></li>
<?php
    endif;
endforeach;
?>
            </ul>
<?php
    }
};
