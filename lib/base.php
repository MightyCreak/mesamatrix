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

use Monolog\Logger;
use Monolog\ErrorHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;

class Mesamatrix
{
    public static $serverRoot; // Path to root of installation
    public static $configDir; // Path to configuration directory
    public static $config; // Config object
    public static $autoloader; // Autoloader
    public static $logger; // Logger

    public static function init() {
        date_default_timezone_set('UTC');

        $dir = str_replace("\\", '/', __DIR__);
        self::$serverRoot = implode('/', array_slice(explode('/', $dir), 0, -1));

        self::$autoloader = (require self::path('vendor/autoload.php'));

        self::$logger = new Logger('logger');
        self::$logger->pushHandler(new ErrorLogHandler(
            ErrorLogHandler::OPERATING_SYSTEM,
            Logger::NOTICE
        ));
        ErrorHandler::register(self::$logger);

        self::$configDir = self::path('config');
        self::$config = new \Mesamatrix\Config(self::$configDir);

        // register the log file
        $logLevel = self::$config->getValue('info', 'log_level', Logger::WARNING);
        $logPath = self::path(self::$config->getValue('info', 'private_dir').'/mesamatrix.log');
        if (is_writable($logPath)) {
            self::$logger->popHandler();
            self::$logger->pushHandler(new StreamHandler($logPath, $logLevel));
        }
        else {
            self::$logger->error('Error log '.$logPath.' is not writable!');
        }

        if ($logLevel < Logger::INFO) {
            ini_set('error_reporting', E_ALL);
            ini_set('display_errors', 1);
        }

        self::$logger->debug('Base initialisation complete');
    }

    public static function path($path) {
        return self::$serverRoot.'/'.$path;
    }
}

\Mesamatrix::init();

