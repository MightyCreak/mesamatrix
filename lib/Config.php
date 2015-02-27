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

namespace Mesamatrix;

class Config
{
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
            \Mesamatrix::$logger->info('Loading configuration file '.$configFile);
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
