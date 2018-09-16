/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2015 Romain "Creak" Failliot.
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

"use strict";

const DRIVERDB = {
    'amd': {
        'families': [
            {
                'name': 'R100',
                'driver_kernel': 'radeon',
                'driver_3d': 'radeon'
            },
            {
                'name': 'R200',
                'driver_kernel': 'radeon',
                'driver_3d': 'r200'
            },
            {
                'name': 'R300',
                'driver_kernel': 'radeon',
                'driver_3d': 'r300g'
            },
            {
                'name': 'R400',
                'driver_kernel': 'radeon',
                'driver_3d': 'r300g'
            },
            {
                'name': 'R500',
                'driver_kernel': 'radeon',
                'driver_3d': 'r300g'
            },
            {
                'name': 'R600',
                'driver_kernel': 'radeon',
                'driver_3d': 'r600g'
            },
            {
                'name': 'R700',
                'driver_kernel': 'radeon',
                'driver_3d': 'r600g'
            },
            {
                'name': 'Evergreen',
                'driver_kernel': 'radeon',
                'driver_3d': 'r600g'
            },
            {
                'name': 'Northern Islands',
                'driver_kernel': 'radeon',
                'driver_3d': 'r600g'
            },
            {
                'name': 'Southern Islands',
                'driver_kernel': 'radeon',
                'driver_3d': 'radeonsi'
            },
            {
                'name': 'Sea Islands',
                'driver_kernel': 'radeon',
                'driver_3d': 'radeonsi'
            },
            {
                'name': 'Volcanic Islands',
                'driver_kernel': 'amdgpu',
                'driver_3d': 'radeonsi'
            }
        ],

        /*
         * Info taken from http://xorg.freedesktop.org/wiki/RadeonFeature/#index5h2
         * Last edition date: Thu Oct 22 12:15:40 2015
         */
        'commercialNames': [
            [ 'R100', function (name) {
                if (name.match(/^7[0-9]{3}$/i)) {
                    return true;
                }
                var match = /^3[0-9]{2}$/i.exec(name);
                if (match && match[0] >= 320 && match[0] <= 345) {
                    return true;
                }
                return false;
            }],
            [ 'R200', function (name) {
                var match = /^[0-9]{4}$/i.exec(name);
                if (match && match[0] >= 8000 && match[0] <= 9250) {
                    return true;
                }
                return false;
            }],
            [ 'R300', function (name) {
                var match = /^9[0-9]{3}$/i.exec(name);
                if (match && match[0] >= 9500 && match[0] <= 9800) {
                    return true;
                }
                var match = /^X([0-9]{3})$/i.exec(name);
                if (match && match[1] >= 300 && match[1] <= 600) {
                    return true;
                }
                var match = /^X([0-9]{4})$/i.exec(name);
                if (match && match[1] >= 1050 && match[1] <= 1150) {
                    return true;
                }
                if (name == '200M') {
                    return true;
                }
                return false;
            }],
            [ 'R400', function (name) {
                var match = /^X([0-9]{3})$/i.exec(name);
                if (match && match[1] >= 750 && match[1] <= 850) {
                    return true;
                }
                if (name.match(/^X12([0-9]{2})$/i)) {
                    return true;
                }
                if (name == '1200') {
                    return true;
                }
                return false;
            }],
            [ 'R500', function (name) {
                var match = /^X([0-9]{4})$/i.exec(name);
                if (match && match[1] >= 1300 && match[1] <= 2300) {
                    return true;
                }
                if (name.match(/^HD ?2300$/i)) {
                    return true;
                }
                return false;
            }],
            [ 'R600', function (name) {
                var match = /^HD ?([0-9]{4})$/i.exec(name);
                if (match && match[1] >= 2400 && match[1] <= 4290) {
                    return true;
                }
                return false;
            }],
            [ 'R700', function (name) {
                var match = /^HD ?([0-9]{4})$/i.exec(name);
                if (match && match[1] >= 4330 && match[1] <= 5165) {
                    return true;
                }
                if (name.match(/^HD ?5[0-9]{2}V$/i)) {
                    return true;
                }
                return false;
            }],
            [ 'Evergreen', function (name) {
                var match = /^HD ?([0-9]{4})$/i.exec(name);
                if (match) {
                    if ((match[1] >= 5430 && match[1] <= 5970) ||
                        (match[1] >= 6000 && match[1] < 7000) ||
                        match[1] == 7350) {
                        return true;
                    }
                }
                return false;
            }],
            [ 'Northern Islands', function (name) {
                var match = /^HD ?([0-9]{4})$/i.exec(name);
                if (match) {
                    if (match[1] == 6450 || match[1] == 6570 || match[1] == 6670 ||
                        (match[1] >= 6790 && match[1] <= 6990) ||
                        (match[1] >= 7450 && match[1] <= 7670)) {
                        return true;
                    }
                }
                return false;
            }],
            [ 'Southern Islands', function (name) {
                var match = /^HD ?([0-9]{4})$/i.exec(name);
                if (match && match[1] >= 7750 && match[1] <= 7970) {
                    return true;
                }
                if (name.match(/^R9 ?(270|280)$/i)) {
                    return true;
                }
                if (name.match(/^R7 ?(240|250)$/i)) {
                    return true;
                }
                return false;
            }],
            [ 'Sea Islands', function (name) {
                if (name.match(/^HD ?7790$/i)) {
                    return true;
                }
                if (name.match(/^R7 ?260$/i)) {
                    return true;
                }
                if (name.match(/^R9 ?290$/i)) {
                    return true;
                }
                return false;
            }],
            [ 'Volcanic Islands', function (name) {
                if (name.match(/^R9 ?285$/i)) {
                    return true;
                }
                return false;
            }]
        ]
    }
};

function findInAmdDb(name) {
    var familyName = null;
    for (var i = DRIVERDB.amd.commercialNames.length; i > 0; i--) {
        var commercialName = DRIVERDB.amd.commercialNames[i - 1];
        if (commercialName[1](name)) {
            familyName = commercialName[0];
            break;
        }
    }

    if (familyName) {
        for (var i = 0; i < DRIVERDB.amd.families.length; i++) {
            var family = DRIVERDB.amd.families[i];
            if (family.name == familyName) {
                return family;
            }
        }
    }

    return null;
}

$(document).ready(function() {
    $('#driverform').submit(function(event) {
        var commercialName = $('#driverform > input:text').val();
        var family = findInAmdDb(commercialName);
        if (family) {
            $('#result').html(
                "Your GPU family is: " + family.name + ".<br/>" +
                "Your kernel driver is: " + family.driver_kernel + ".<br/>" +
                "Your 3D driver is: " + family.driver_3d + ".");
        }
        else {
            $('#result').html("Graphics card '" + commercialName + "' not found.");
        }
        event.preventDefault();
    });
});
