<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2021 Romain "Creak" Failliot.
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

namespace Mesamatrix;

use Monolog\Logger;
use Monolog\ErrorHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Symfony\Component\HttpFoundation\Request as HTTPRequest;

class Mesamatrix
{
    public static $serverRoot; // Path to root of installation
    public static $configDir; // Path to configuration directory
    public static $config; // Config object
    public static $logger; // Logger
    public static $request; // HTTP request object

    public static function init() {
        date_default_timezone_set('UTC');

        self::$serverRoot = dirname(__DIR__);

        self::$logger = new Logger('logger');
        self::$logger->pushHandler(new ErrorLogHandler(
            ErrorLogHandler::OPERATING_SYSTEM,
            Logger::NOTICE
        ));
        ErrorHandler::register(self::$logger);

        self::$configDir = self::path('config');
        self::$config = new Config(self::$configDir);

        // attempt to create the private dir
        $privateDir = self::path(self::$config->getValue('info', 'private_dir'));
        if (!is_dir($privateDir)) {
            mkdir($privateDir);
        }

        // register the log file
        $logLevel = self::$config->getValue('info', 'log_level', Logger::WARNING);
        $logPath = $privateDir.'/mesamatrix.log';
        if (!file_exists($logPath) && is_dir($privateDir)) {
            touch($logPath);
        }
        if (is_writable($logPath)) {
            self::$logger->popHandler();
            self::$logger->pushHandler(new StreamHandler($logPath, $logLevel));
        }
        else {
            self::$logger->error('Error log '.$logPath.' is not writable!');
        }

        if ($logLevel < Logger::INFO) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        }

        // Check extensions dependencies
        if (!extension_loaded('xml')) {
            self::$logger->error('Could not find XML extension');
            exit(1);
        }

        // Initialize request
        self::$request = HTTPRequest::createFromGlobals();

        self::$logger->debug('Base initialization complete');

        self::$logger->debug('Log level: '.self::$logger->getLevelName($logLevel));
        self::$logger->debug('PHP error_reporting: 0x'.dechex(ini_get('error_reporting')));
        self::$logger->debug('PHP display_errors: '.ini_get('display_errors'));
    }

    public static function path($path) {
        return self::$serverRoot.'/'.$path;
    }
}
