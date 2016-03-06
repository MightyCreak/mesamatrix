<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014 Romain "Creak" Failliot.
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

namespace Mesamatrix\Parser;

abstract class Constants
{
    // Extension statuses.
    const STATUS_NOT_STARTED = "incomplete";
    const STATUS_IN_PROGRESS = "started";
    const STATUS_DONE = "complete";

    // List of all the drivers.
    public static $allDrivers = array(
        "softpipe",
        "llvmpipe",
        "i965",
        "nv50",
        "nvc0",
        "r600",
        "radeonsi"
    );

    public static $allDriversVendors = array(
        "Software"  => array("softpipe", "llvmpipe"),
        "Intel"     => array("i965"),
        "nVidia"    => array("nv50", "nvc0"),
        "AMD"       => array("r600", "radeonsi"),
    );
}
