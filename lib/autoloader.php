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

class Autoloader
{
    private $classPaths = array();
    private $prefixPaths = array();

    public function __construct() {
        $this->registerPrefix('Mesamatrix', 'lib');
    }

    public function registerClass($class, $path) {
        $this->classPaths[$class] = $path;
    }

    public function registerPrefix($prefix, $path) {
        // Ensure path ends in slash if not empty
        if (substr($path, -1) !== '/' && $path !== "") {
            $path .= '/';
        }
        $prefixPaths = &$this->prefixPaths;

        if ($prefix !== '') {
            $components = explode('\\', $prefix);
            foreach ($components as $component) {
                if (!array_key_exists($component, $prefixPaths)) {
                    $prefixPaths[$component] = array();
                }
                $prefixPaths = &$prefixPaths[$component];
            }
        }
        $prefixPaths['\\'] = $path;
    }

    public function findClass($class, $lowercase = false) {
        $class = trim($class, '\\');

        if (array_key_exists($class, $this->classPaths)) {
            return $this->classPaths[$class];
        }

        $components = explode('\\', $class);
        return $this->findPrefix($components, $this->prefixPaths, $lowercase);
    }

    private function findPrefix($components, $prefix, $lowercase) {
        $result = false;
        if (!empty($components) && array_key_exists($components[0], $prefix)) {
            // Try to get more specific prefix
            $prefixComponent = $prefix[$components[0]];
            array_shift($components);
            $result = $this->findPrefix($components, $prefixComponent, $lowercase);
        }
        if ($result === false && array_key_exists('\\', $prefix)) {
            $suffix = implode('/', $components);
            $suffix = str_replace('_', '/', $suffix);
            if ($lowercase) {
                $suffix = strtolower($suffix);
            }
            $result = $prefix['\\'] . $suffix . '.php';
        }
        return $result;
    }

    public function load($class) {
        $path = stream_resolve_include_path($this->findClass($class, false));
        if (!$path) {
            // try lowercase version (goes against PSR-0!)
            $path = stream_resolve_include_path($this->findClass($class, true));
        }
        if ($path) {
            require_once $path;
        }
        return $path;
    }
}
