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

namespace Mesamatrix\Parser;

abstract class Constants
{
    // Extension statuses.
    const STATUS_NOT_STARTED = "incomplete";
    const STATUS_IN_PROGRESS = "started";
    const STATUS_DONE = "complete";

    // List of all the drivers.
    const GL_ALL_DRIVERS = [
        "softpipe",
        "llvmpipe",
        "i965",
        "nv50",
        "nvc0",
        "r600",
        "radeonsi",
        "swr",
        "freedreno"
    ];

    const GL_ALL_DRIVERS_VENDORS = [
        "Software"  => [ "softpipe", "llvmpipe", "swr" ],
        "Intel"     => [ "i965" ],
        "Nvidia"    => [ "nv50", "nvc0" ],
        "AMD"       => [ "r600", "radeonsi" ],
        "Qualcomm"  => [ "freedreno" ],
    ];

    const VK_ALL_DRIVERS = [
        "anv",
        "radv",
    ];

    const VK_ALL_DRIVERS_VENDORS = [
        "Intel"     => [ "anv" ],
        "AMD"       => [ "radv" ],
    ];

    // Hints enabling for all drivers.
    // 0: regexp
    // 1: use hint?
    const RE_ALL_DRIVERS_HINTS = [
        [ "/^all drivers$/i", FALSE ],
        [ "/^0 binary formats$/i", TRUE ],
        [ "/^all drivers that support GLSL( \d+\.\d+\+?)?$/i", TRUE ],
        [ "/^all - but needs GLX\/EGL extension to be useful$/i", TRUE ],
    ];

    // Hints depending on another feature.
    // 0: regexp
    // 1: use hint?
    // 2: dependency type
    // 3: dependency match index
    const RE_DEP_DRIVERS_HINTS = [
        [ "/^all drivers that support (GL_[_[:alnum:]]+)$/i", TRUE, DependsOn::Extension, 1 ],
        [ "/^all drivers that support GLES ?(\d+\.\d+\+?)?$/i", TRUE, DependsOn::GlesVersion, 1 ],
    ];

    const GL_OR_ES_EXTRA_NAME = "Extensions that are not part of any OpenGL or OpenGL ES version";
    const VK_EXTRA_NAME = "Extensions that are not part of any Vulkan version";
}
