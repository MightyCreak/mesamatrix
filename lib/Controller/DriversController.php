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

class DriversController extends BaseController
{
    public function __construct() {
        parent::__construct();

        $this->setPage('Drivers decoder ring');
        $this->addJsScript('js/drivers.js');
    }

    protected function computeRendering() {
    }

    protected function writeHtmlPage() {
?>
            <h1>Easy decoder ring</h1>
            <p>Note: this is a beta, it only works for AMD graphics cards (for now).</p>
            <p>Enter the commercial name of your <abbr title="Graphics Processing Unit">GPU</abbr> (e.g. HD 6870):</p>
            <form id="driverform" action="#">
                <input type="text" value="" required="" />
                <input type="submit" value="Find my driver" />
            </form>
            <p id="result"></p>
            <p>
                All the data are from the
                <a href="http://xorg.freedesktop.org/wiki/RadeonFeature/#index5h2">FreeDesktop decoder ring</a>.
            </p>
<?php
    }
};
