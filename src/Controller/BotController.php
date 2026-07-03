<?php

/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014-2022 Romain "Creak" Failliot.
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

class BotController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->setShowMenu(false);
    }

    protected function computeRendering(): void
    {
    }

    protected function writeHtmlPage(): void
    {
        echo <<<'HTML'
    <h1>What is this bot?</h1>
    <p>If you got to this page, it's probably because you encountered an unknown User Agent with a
    link that led you here.</p>
    <h2><code>Mesamatrix-LinkChecker</code></h2>
    </li>
    <p>This bot is part of the script that updates this website. Based on the API extension names
    (e.g. <code>VK_KHR_index_type_uint8</code>), it tries to find a URL that would link to a
    documentation page (e.g.
    <a href="https://docs.vulkan.org/refpages/latest/refpages/source/VK_KHR_index_type_uint8.html">https://docs.vulkan.org/refpages/latest/refpages/source/VK_KHR_index_type_uint8.html</a>).</p>
    <p>The first time Mesamatrix is set up it tries a URL for all the extensions (~700 URLs) and
    cache the result for 3 months (to prevent spamming). When updating, if a new API extension is
    found, it tries just the one URL and cache the result.</p>
HTML;
    }
}
