<?php
/*
 * Copyright (C) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 * Copyright (C) 2014 Romain "Creak" Failliot
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

namespace Mesamatrix\Git;

class ProcessBuilder extends \Symfony\Component\Process\ProcessBuilder
{
    public function __construct(array $arguments = array())
    {
        array_unshift($arguments, 'git');
        $gitDir = \Mesamatrix::path(\Mesamatrix::$config->getValue("git", "dir"));
        foreach ($arguments as &$arg) {
            $arg = str_replace('@gitDir@', $gitDir, $arg);
        }
        parent::__construct($arguments);
        $this->setWorkingDirectory($gitDir);
    }
}
