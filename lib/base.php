<?php
/*
 * Copyright (C) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
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
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mesamatrix. If not, see <http://www.gnu.org/licenses/>.
 */

class MesaMatrix
{
    public static $serverRoot; // Path to root of installation
    public static $configDir; // Path to configuration directory
    public static $config; // \Config object

    public static function init()
    {
        self::$serverRoot = str_replace("\\", '/', substr(__DIR__, 0, -4));

        set_include_path(
          self::$serverRoot.'/lib' . PATH_SEPARATOR .
          get_include_path()
        );

        require_once 'config.php';
        self::$configDir = self::$serverRoot.'/config';
        self::$config = new \Config(self::$configDir);

        date_default_timezone_set('UTC');
    }

    public static function debug_print($line)
    {
        if(self::$config->debug())
        {
            print("DEBUG: ".$line."<br />\n");
        }
    }

    public static function path($path)
    {
        return self::$serverRoot.'/'.$path;
    }
}

MesaMatrix::init();
