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

namespace Mesamatrix;

class Config
{
    protected $debugMode;
    protected $cache = array();

    public function __construct($configDir) {
        $this->readData($configDir.'/config.default.php');
        $this->readData($configDir.'/config.php');

        $extraConfigs = glob($configDir.'/*.config.php');
        if (is_array($extraConfigs)) {
            natsort($extraConfigs);
            foreach ($extraConfigs as $config) {
                $this->readData($config);
            }
        }

        $this->debug($this->getValue('info', 'debug', false));
    }

    public function debug($set = null) {
        if (is_null($set)) {
            return $this->debugMode;
        }
        elseif ($set) {
            ini_set('display_errors', 1);
            ini_set('error_reporting', E_ALL);
            $this->debugMode = true;
        }
        else
        {
            ini_set('display_errors', 0);
            ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
            $this->debugMode = false;
        }
    }

    public function getValue($section, $key, $default = null) {
        if (isset($this->cache[$section])) {
            if (isset($this->cache[$section][$key])) {
                return $this->cache[$section][$key];
            }
        }
        return $default;
    }

    public function readData($configFile) {
        if (file_exists($configFile)) {
            @include $configFile;
            if (isset($CONFIG) && is_array($CONFIG)) {
                foreach ($CONFIG as $section => $sectionConfig) {
                    if (array_key_exists($section, $this->cache)) {
                        $this->cache[$section] =
                          array_merge($this->cache[$section], $sectionConfig);
                    }
                    else {
                        $this->cache[$section] = $sectionConfig;
                    }
                }
                return true;
            }
        }
        return false;
    }
}
