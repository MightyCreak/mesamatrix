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

function fillZeros(number, size) {
    var absNum = String(Math.abs(number));
    var numZeros = size - absNum.length;
    var res = "";
    if(number < 0)
        res = "-";
    res += Array(numZeros + 1).join("0") + absNum;
    return res;
}

function getLocalDate(text) {
    var eventDate = new Date(text);
    var year = eventDate.getFullYear();
    var month = fillZeros(eventDate.getMonth() + 1, 2);
    var day = fillZeros(eventDate.getDate(), 2);
    var hours = fillZeros(eventDate.getHours(), 2);
    var minutes = fillZeros(eventDate.getMinutes(), 2);
    var seconds = fillZeros(eventDate.getSeconds(), 2);
    var timezoneOffset = -eventDate.getTimezoneOffset();
    var timezoneMinutes = Math.abs(timezoneOffset);
    var timezoneHours = Math.floor(timezoneMinutes / 60);
    timezoneMinutes -= timezoneHours * 60;
    timezoneHours = fillZeros(timezoneHours, 2);
    timezoneMinutes = fillZeros(timezoneMinutes, 2);
    if(timezoneMinutes >= 0)
        timezoneHours = "+" + timezoneHours;
    return year + "-" + month + "-" + day + " " + hours + ":" + minutes + ":" + seconds + " (GMT " + timezoneHours + timezoneMinutes + ")";
}

function getRelativeDate(text) {
    const MINS_IN_HOUR = 60;
    const MINS_IN_DAY = 1440;       // 60 * 24
    const MINS_IN_WEEK = 10080;     // 60 * 24 * 7
    const MINS_IN_MONTH = 43200;    // 60 * 24 * 30

    var eventDate = new Date(text);
    var relTimeInMin = (Date.now() - eventDate.getTime()) / (1000 * 60);

    if(relTimeInMin < 5)               // Less than 5 minutes ago
        return "just now";

    if(relTimeInMin < MINS_IN_HOUR) {
        return Math.round(((relTimeInMin / MINS_IN_HOUR) / 5) * 5) + " minutes";
    }

    if(relTimeInMin < MINS_IN_DAY) {
        var numHours = Math.round(relTimeInMin / MINS_IN_HOUR);
        return numHours + " " + (numHours == 1 ? "hour" : "hours");
    }

    if(relTimeInMin < MINS_IN_WEEK) {
        var numDays = Math.round(relTimeInMin / MINS_IN_DAY);
        return numDays + " " + (numDays == 1 ? "day" : "days");
    }

    if(relTimeInMin < MINS_IN_MONTH) {
        var numWeeks = Math.round(relTimeInMin / MINS_IN_WEEK);
        return numWeeks + " " + (numWeeks == 1 ? "week" : "weeks");
    }

    if(relTimeInMin < MINS_IN_MONTH * 3) {
        var numMonths = Math.round(relTimeInMin / MINS_IN_MONTH);
        return numMonths + " " + (numMonths == 1 ? "month" : "months");
    }

    var year = eventDate.getFullYear();
    var month = fillZeros(eventDate.getMonth() + 1, 2);
    var day = fillZeros(eventDate.getDate(), 2);
    return year + "-" + month + "-" + day;
}

function gaussian(x, a, b, c) {
    return a * Math.exp(- Math.pow(x - b, 2) / (2 * Math.pow(c, 2)));
}

$(document).ready(function() {
    // Convert to relative date.
    $('.toRelativeDate').each(function() {
        $(this).text(getRelativeDate($(this).data('timestamp')));
    });

    // Convert to local date.
    $('.toLocalDate').each(function() {
        $(this).text(getLocalDate($(this).data('timestamp')));
    });

    // Add tipsy for the footnote.
    $('.footnote').tipsy({gravity: 'w', fade: true});

    // adjust the opacity of the 'modified' text based on age
    var timeConst = 1.2e7;
    $('.task span').each(function() {
        var timeDiff = (Date.now()/1000) - $(this).data('timestamp');
        $(this).css('opacity', gaussian(timeDiff, 1, 0, timeConst));
    });

    // Change mesa score color based on completion
    $('.hCellDriverScore').each(function() {
        var blend = Math.round($(this).data('score'));
        if (blend == 100) {
            $(this).addClass('hCellDriverScore-done');
        }
        else if (blend < 75) {
            $(this).addClass('hCellDriverScore-notyet');
        }
        else {
            $(this).addClass('hCellDriverScore-almost');
        }
    });

    $('.matrix').delegate('td', 'mouseover mouseleave', function(e) {
        // Get cell, row and table elements
        var cell = $(this);
        var row = cell.closest('tr');

        // Should highlight row
        var highlightRow = row.hasClass('extension');

        // Should highlight column
        var columnIdx = cell.index();
        var highlightCol = $('.matrix colgroup').eq(columnIdx).hasClass('hl');
        if (highlightCol) {
            highlightCol = cell.hasClass('task');
            if (!highlightCol)
                highlightCol = cell.hasClass('hCellHeader') && !cell.attr('colspan');
        }

        if (e.type == 'mouseover') {
            if (highlightRow) {
                row.find('td').each(function() {
                    $(this).addClass('hover');
                });
            }
            if (highlightCol) {
                var table = row.closest('table');
                table.find('.extension td:nth-child(' + (columnIdx + 1) + ')').each(function() {
                    $(this).addClass('hover');
                });
            }
        }
        else {
            if (highlightRow) {
                row.find('td').each(function() {
                    $(this).removeClass('hover');
                });
            }
            if (highlightCol) {
                var table = row.closest('table');
                table.find('.extension td:nth-child(' + (columnIdx + 1) + ')').each(function() {
                    $(this).removeClass("hover");
                });
            }
        }
    });
});
