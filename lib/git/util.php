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

class Util
{
    public static function exec($cmd, &$pipe = false) {
        $gitDir = \Mesamatrix::path(\Mesamatrix::$config->getValue("git", "dir"));
        $cmd = str_replace('@gitDir@', escapeshellarg($gitDir), $cmd);

        $pipeArray = array(
          1 => STDOUT,
          2 => STDERR
        );
        if ($pipe !== false) {
            $pipeArray[1] = array("pipe", "w");
        }

        \Mesamatrix::debug_print("git ".$cmd);
        $process = proc_open(
            "git ".$cmd,
            $pipeArray,
            $pipes,
            $gitDir
        );
        if (!is_resource($process)) {
            die("Unable to execute git");
        }
        if ($pipe !== false) {
            $pipe = $pipes[1];
        }
        return $process;
    }
}
