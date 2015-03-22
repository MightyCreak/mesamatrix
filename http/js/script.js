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
    var eventDate = new Date(text);
    var relTimeInMin = (Date.now() - eventDate.getTime()) / (1000 * 60);
    if(relTimeInMin <= 5)               // Less than 5 minutes ago
        return "just now";
    if(relTimeInMin <= 60)              // Less than an hour ago
        return Math.round(((relTimeInMin / 60) / 5) * 5) + " minutes ago";
    if(relTimeInMin <= 119)             // Less (strictly) than two hours ago
        return "an hour ago";
    if(relTimeInMin <= 1440)            // Less than a day ago (60 * 24)
        return Math.round(relTimeInMin / 60) + " hours ago";
    if(relTimeInMin <= 2160)            // Less than a day and a half ago (60 * 24 * 1.5)
        return "a day ago";
    if(relTimeInMin <= 10080)           // Less than a week ago (60 * 24 * 7)
        return Math.round(relTimeInMin / 1440) + " days";
    if(relTimeInMin <= 15120)           // Less than a week and a half ago (60 * 24 * 7 * 1.5)
        return "a week ago";
    if(relTimeInMin <= 43200)           // Less than a month ago (60 * 24 * 30)
        return Math.round(relTimeInMin / 10080) + " weeks ago";
    if(relTimeInMin <= 64800)           // Less than a month and a half ago (60 * 24 * 30 * 1.5)
        return "a month ago";
    if(relTimeInMin <= 259200)          // Less than six months ago (60 * 24 * 30 * 6)
        return Math.round(relTimeInMin / 43200) + " months ago";
    var year = eventDate.getFullYear();
    var month = fillZeros(eventDate.getMonth() + 1, 2);
    var day = fillZeros(eventDate.getDate(), 2);
    return year + "-" + month + "-" + day;
}

function gaussian(x, a, b, c) {
    return a * Math.exp(- Math.pow(x - b, 2) / (2 * Math.pow(c, 2)));
}

$(document).ready(function() {
    $(".footnote a").tipsy({gravity: "w"});
//    $(".footnote a").on("click", function(e) {
//        if ($(".tipsy").length === 0) {
//            $(this).tipsy("show");
//        }
//        else {
//            $(this).tipsy("hide");
//        }
//    });

    // adjust the opacity of the 'modified' text based on age
    var timeConst = 1.2e7;
    $('.task span').each(function() {
        var timeDiff = (Date.now()/1000) - $(this).data('timestamp');
        $(this).css('opacity', gaussian(timeDiff, 1, 0, timeConst));
    });
});
