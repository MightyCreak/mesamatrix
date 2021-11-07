<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 * Copyright (C) 2014-2016 Romain "Creak" Failliot
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

namespace Mesamatrix\Git;

use Mesamatrix\Mesamatrix;

class Process extends \Symfony\Component\Process\Process
{
    public function __construct(array $arguments = array())
    {
        // Replace '@gitDir@' by Mesa Git directory.
        $gitDir = Mesamatrix::path(Mesamatrix::$config->getValue("info", "private_dir"))."/";
        $gitDir .= Mesamatrix::$config->getValue("git", "mesa_dir", "mesa.git");
        foreach ($arguments as &$arg) {
            $arg = str_replace('@gitDir@', $gitDir, $arg);
        }

        // Ensure existence of the Mesa Git directory.
        if (!is_dir($gitDir)) {
            if (!mkdir($gitDir)) {
                Mesamatrix::$logger->critical('Couldn\'t create directory `'.$gitDir.'`.');
                return 1;
            }
        }

        $command = array_merge([ 'git' ], $arguments);
        parent::__construct($command, $gitDir);
    }
}
