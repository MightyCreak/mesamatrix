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

require_once 'autoloader.php';

class Mesamatrix
{
    public static $serverRoot; // Path to root of installation
    public static $configDir; // Path to configuration directory
    public static $config; // Config object
    public static $autoloader; // Autoloader object

    public static function init() {
        $dir = str_replace("\\", '/', __DIR__);
        self::$serverRoot = implode('/', array_slice(explode('/', $dir), 0, -1));

        set_include_path(
          self::$serverRoot . PATH_SEPARATOR .
          get_include_path()
        );

        self::$autoloader = new \Mesamatrix\Autoloader();
        spl_autoload_register(array(self::$autoloader, 'load'));

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

require_once('3rdparty/register.php');
