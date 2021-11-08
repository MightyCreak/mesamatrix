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
    public const STATUS_NOT_STARTED = "incomplete";
    public const STATUS_IN_PROGRESS = "started";
    public const STATUS_DONE = "complete";

    // OpenGL and OpenGL ES.
    public const GL_NAME = "OpenGL";
    public const GLES_NAME = "OpenGL ES";
    public const GL_OR_ES_EXTRA_NAME = "Extensions that are not part of any OpenGL or OpenGL ES version";

    public const GL_ALL_DRIVERS = [
        "softpipe",
        "llvmpipe",
        "i965",
        "nv50",
        "nvc0",
        "r600",
        "radeonsi",
        "swr",
        "freedreno",
        "virgl",
        "etnaviv",
        "vc4",
        "v3d",
        "zink",
        "lima",
        "panfrost",
        "d3d12",
    ];

    public const GL_ALL_DRIVERS_VENDORS = [
        "Software"  => [ "llvmpipe", "softpipe", "swr" ],
        "Intel"     => [ "i965" ],
        "Nvidia"    => [ "nv50", "nvc0" ],
        "AMD"       => [ "r600", "radeonsi" ],
        "Qualcomm"  => [ "freedreno" ],
        "Vivante"   => [ "etnaviv" ],
        "Broadcom"  => [ "vc4", "v3d" ],
        "Arm"       => [ "lima", "panfrost" ],
        "Emulation" => [ "d3d12", "virgl", "zink" ],
    ];

    // Vulkan.
    public const VK_NAME = "Vulkan";
    public const VK_EXTRA_NAME = "Extensions that are not part of any Vulkan version";

    public const VK_ALL_DRIVERS = [
        "anv",
        "lvp",
        "radv",
        "tu",
        "v3dv",
        "vn",
    ];

    public const VK_ALL_DRIVERS_VENDORS = [
        "Intel"     => [ "anv" ],
        "Software"  => [ "lvp" ],
        "AMD"       => [ "radv" ],
        "Qualcomm"  => [ "tu" ],
        "Broadcom"  => [ "v3dv" ],
        "Emulation" => [ "vn" ],
    ];

    // OpenCL.
    public const OPENCL_NAME = "OpenCL";
    public const OPENCL_EXTRA_NAME = "Extensions that are not part of any OpenCL version";
    public const OPENCL_VENDOR_SPECIFIC_NAME = "Vendor-specific extensions that are not part of any OpenCL version";

    public const OPENCL_ALL_DRIVERS = [
        "nvc0",
        "r600",
        "radeonsi",
    ];

    public const OPENCL_ALL_DRIVERS_VENDORS = [
        "Nvidia"    => [ "nvc0" ],
        "AMD"       => [ "r600", "radeonsi" ],
    ];

    // Hints enabling for all drivers.
    // 0: regexp
    // 1: use hint?
    public const RE_ALL_DRIVERS_HINTS = [
        [ "/^all drivers$/i", false ],
        [ "/^all drivers that support GLSL( \d+\.\d+\+?)?$/i", true ],
        [ "/^all - but needs GLX\/EGL extension to be useful$/i", true ],
    ];

    // Hints depending on another feature.
    // 0: regexp
    // 1: use hint?
    // 2: dependency type
    // 3: dependency match index
    public const RE_DEP_DRIVERS_HINTS = [
        [ "/^all drivers that support (GL_[_[:alnum:]]+)$/i", true, DependsOn::EXTENSION, 1 ],
        [ "/^all drivers that support GLES ?(\d+\.\d+\+?)?$/i", true, DependsOn::GLES_VERSION, 1 ],
    ];
}
