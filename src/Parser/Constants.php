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
    public const STATUS_NOT_STARTED = 'incomplete';
    public const STATUS_IN_PROGRESS = 'started';
    public const STATUS_DONE = 'complete';

    // OpenGL and OpenGL ES.
    public const GL_NAME = 'OpenGL';
    public const GLES_NAME = 'OpenGL ES';
    public const GL_OR_ES_EXTRA_NAME = 'Extensions that are not part of any OpenGL or OpenGL ES version';

    public const GL_ALL_DRIVERS = [
        'asahi',
        'crocus',
        'd3d12',
        'etnaviv',
        'freedreno',
        'iris',
        'lima',
        'llvmpipe',
        'nv50',
        'nvc0',
        'panfrost',
        'r600',
        'radeonsi',
        'softpipe',
        'v3d',
        'vc4',
        'virgl',
        'zink',
    ];

    public const GL_ALL_DRIVERS_VENDORS = [
        'AMD'           => [ 'r600', 'radeonsi' ],
        'Apple'         => [ 'asahi' ],
        'Arm'           => [ 'lima', 'panfrost' ],
        'Broadcom'      => [ 'v3d', 'vc4' ],
        'Intel'         => [ 'iris', 'crocus' ],
        'Nvidia'        => [ 'nv50', 'nvc0' ],
        'Qualcomm'      => [ 'freedreno' ],
        'Software'      => [ 'llvmpipe', 'softpipe' ],
        'Translation'   => [ 'd3d12', 'virgl', 'zink' ],
        'Vivante'       => [ 'etnaviv' ],
    ];

    // Vulkan.
    public const VK_NAME = 'Vulkan';
    public const VK_EXTRA_NAME = 'Extensions that are not part of any Vulkan version';

    public const VK_ALL_DRIVERS = [
        'anv',
        'dzn',
        'hasvk',
        'hk',
        'lvp',
        'nvk',
        'panvk',
        'pvr',
        'radv',
        'tu',
        'v3dv',
        'vn',
    ];

    public const VK_ALL_DRIVERS_VENDORS = [
        'AMD'           => [ 'radv' ],
        'Apple'         => [ 'hk' ],
        'Arm'           => [ 'panvk' ],
        'Broadcom'      => [ 'v3dv' ],
        'Intel'         => [ 'anv', 'hasvk' ],
        'Nvidia'        => [ 'nvk' ],
        'PowerVR'       => [ 'pvr' ],
        'Qualcomm'      => [ 'tu' ],
        'Software'      => [ 'lvp' ],
        'Translation'   => [ 'dzn', 'vn' ],
    ];

    // OpenCL.
    public const CLOVER_OPENCL_NAME = 'Clover OpenCL';
    public const CLOVER_OPENCL_EXTRA_NAME = 'Clover Extensions that are not part of any OpenCL version';

    public const CLOVER_OPENCL_ALL_DRIVERS = [
        'nvc0',
        'r600',
        'radeonsi',
    ];

    public const CLOVER_OPENCL_ALL_DRIVERS_VENDORS = [
        'AMD'       => [ 'r600', 'radeonsi' ],
        'Nvidia'    => [ 'nvc0' ],
    ];

    public const RUSTICL_OPENCL_NAME = 'Rusticl OpenCL';
    public const RUSTICL_OPENCL_EXTRA_NAME = 'Rusticl Extensions that are not part of any OpenCL version';
    public const RUSTICL_OPENCL_ALL_DRIVERS = [
        'asahi',
        'freedreno',
        'iris',
        'llvmpipe',
        'nvc0',
        'panfrost',
        'radeonsi',
        'zink',
    ];
    public const RUSTICL_OPENCL_ALL_DRIVERS_VENDORS = [
        'AMD'         => [ 'radeonsi' ],
        'Apple'       => [ 'asahi' ],
        'Arm'         => [ 'panfrost' ],
        'Intel'       => [ 'iris' ],
        'Nvidia'      => [ 'nvc0' ],
        'Qualcomm'    => [ 'freedreno' ],
        'Software'    => [ 'llvmpipe' ],
        'Translation' => [ 'zink' ],
    ];

    // Hints enabling for all drivers.
    // 0: regexp
    // 1: use hint?
    public const RE_ALL_DRIVERS_HINTS = [
        [ '/^all drivers$/i', false ],
        [ '/^all drivers that support GLSL( \d+\.\d+\+?)?$/i', true ],
        [ '/^all - but needs GLX\/EGL extension to be useful$/i', true ],
    ];

    // Hints depending on another feature.
    // 0: regexp
    // 1: use hint?
    // 2: dependency type
    // 3: dependency match index
    public const RE_DEP_DRIVERS_HINTS = [
        [ '/^all drivers that support (GL_[_[:alnum:]]+)$/i', true, DependsOn::EXTENSION, 1 ],
        [ '/^all drivers that support GLES ?(\d+\.\d+\+?)?$/i', true, DependsOn::GLES_VERSION, 1 ],
    ];
}
