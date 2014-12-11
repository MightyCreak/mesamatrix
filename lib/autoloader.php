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

    public function __construct()
    {
        $this->registerPrefix('Mesamatrix', '');
    }

    public function registerClass($class, $path)
    {
        $this->classPaths[$class] = $path;
    }
    public function registerPrefix($prefix, $path)
    {
        // Ensure path ends in slash if not empty
        if (substr($path, -1) !== '/' && $path !== "")
        {
            $path .= '/';
        }
        $components = explode('\\', $prefix);

        $prefixPaths = &$this->prefixPaths;
        foreach ($components as $component)
        {
            if (!array_key_exists($component, $prefixPaths))
            {
                $prefixPaths[$component] = array();
            }
            $prefixPaths = &$prefixPaths[$component];
        }
        $prefixPaths['\\'] = $path;
    }

    public function findClass($class)
    {
        $class = trim($class, '\\');

        if (array_key_exists($class, $this->classPaths))
        {
            return $this->classPaths[$class];
        }

        $components = explode('\\', $class);
        return $this->findPrefix($components, $this->prefixPaths);
    }

    private function findPrefix($components, $prefix)
    {
        if (!empty($components) && array_key_exists($components[0], $prefix))
        {
            $prefixComponent = $prefix[$components[0]];
            // Try to get more specific prefix
            $result = $this->findPrefix(array_shift($components), $prefixComponent);
            if ($result !== false)
            {
                return $result;
            }
            elseif (array_key_exists('\\', $prefixComponent))
            {
                $suffix = implode('/', $components);
                $suffix = str_replace('_', '/', $suffix);
                return $prefixComponent['\\'] . strtolower($suffix) . '.php';
            }
        }
        return false;
    }

    public function load($class)
    {
        $path = stream_resolve_include_path($this->findClass($class));
        if ($path)
        {
            require_once $path;
        }
        return $path;
    }
}
