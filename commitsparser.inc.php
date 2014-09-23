<?php
/*
 * Copyright (C) 2014 Romain "Creak" Failliot.
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
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with libbench. If not, see <http://www.gnu.org/licenses/>.
 */

class CommitsParser
{
    public function parse($filename)
    {
        $handle = fopen($filename, "r");
        if($handle === FALSE)
        {
            return NULL;
        }

        $commits = array();

        // Regexp patterns.
        $reCommitHash = "/^[[:alnum:]]{40}$/";
        $reCommitInfo = "/^  ([[:alnum:]]+): (.+)$/";

        $line = fgets($handle);
        while($line !== FALSE)
        {
            if(preg_match($reCommitHash, $line, $matches) === 1)
            {
                $hash = $line;
                $timestamp = 0;
                $author = "";
                $subject = "";

                $line = fgets($handle);
                while($line !== FALSE && preg_match($reCommitInfo, $line, $matches) === 1)
                {
                    switch($matches[1])
                    {
                    case "timestamp":   $timestamp = $matches[2]; break;
                    case "author":      $author = $matches[2]; break;
                    case "subject":     $subject = $matches[2]; break;
                    }

                    $line = fgets($handle);
                }

                $commits[] = array(
                    "hash" => $hash,
                    "timestamp" => $timestamp,
                    "author" => $author,
                    "subject" => $subject,
                );
            }

            $line = fgets($handle);
        }

        fclose($handle);

        return $commits;
    }
};
?>

