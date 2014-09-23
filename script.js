/*
 * Copyright (C) 2014 Romain "Creak" Failliot.
 *
 * This file is part of mesamatrix.
 *
 * mesamatrix is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * mesamatrix is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with libbench. If not, see <http://www.gnu.org/licenses/>.
 */

function fillZeros(number, size)
{
    var numStrLen = number.toString().length;
    return "0".repeat(size - numStrLen) + number;
}

function getLocalDate(text)
{
    var eventDate = new Date(text);
    var year = eventDate.getFullYear();
    var month = fillZeros(eventDate.getMonth() + 1, 2);
    var day = fillZeros(eventDate.getDate(), 2);
    var hours = fillZeros(eventDate.getHours(), 2);
    var minutes = fillZeros(eventDate.getMinutes(), 2);
    var seconds = fillZeros(eventDate.getSeconds(), 2);
    var timezone = fillZeros(eventDate.getTimezoneOffset() / 60, 3);
    if(timezone >= 0)
        timezone = "+" + timezone;
    return year + "-" + month + "-" + day + " " + hours + ":" + minutes + ":" + seconds + " (GMT " + timezone + ")";
}

function getRelativeDate(text)
{
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
    if(relTimeInMin <= 2879)            // Less (strictly) than two days ago
        return "a day ago";
    if(relTimeInMin <= 10080)           // Less than a week ago (60 * 24 * 7)
        return Math.round(relTimeInMin / 1440) + " days";
    if(relTimeInMin <= 20159)           // Less (strictly) than two weeks ago (60 * 24 * 7 * 2)
        return "a week ago";
    if(relTimeInMin <= 43200)           // Less than a month ago (60 * 24 * 30)
        return Math.round(relTimeInMin / 10080) + " weeks ago";
    if(relTimeInMin <= 86399)           // Less (strictly) than two months ago (60 * 24 * 30 * 2)
        return "a month ago";
    if(relTimeInMin <= 259200)          // Less than six months ago (60 * 24 * 30 * 6)
        return Math.round(relTimeInMin / 43200) + " months ago";
    var year = eventDate.getFullYear();
    var month = fillZeros(eventDate.getMonth() + 1, 2);
    var day = fillZeros(eventDate.getDate(), 2);
    return year + "-" + month + "-" + day;
}

