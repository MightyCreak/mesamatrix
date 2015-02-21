<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
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

class Mesamatrix
{
    public static $serverRoot; // Path to root of installation
    public static $configDir; // Path to configuration directory
    public static $config; // Config object
    public static $autoloader; // Autoloader

    public static function init() {
        $dir = str_replace("\\", '/', __DIR__);
        self::$serverRoot = implode('/', array_slice(explode('/', $dir), 0, -1));

        self::$autoloader = (require self::$serverRoot.'/vendor/autoload.php');

        self::$configDir = self::$serverRoot.'/config';
        self::$config = new \Mesamatrix\Config(self::$configDir);

        date_default_timezone_set('UTC');
    }

    public static function debug_print($line) {
        if (self::$config->debug()) {
            print("DEBUG: ".$line."<br />\n");
        }
    }

    public static function path($path) {
        return self::$serverRoot.'/'.$path;
    }
}

\Mesamatrix::init();

