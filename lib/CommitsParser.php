<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014 Romain "Creak" Failliot.
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

class CommitsParser
{
    public function parse($filename) {
        $handle = fopen($filename, "r");
        if ($handle === FALSE) {
            return NULL;
        }

        $ret = parse_stream($handle);

        fclose($handle);
        return $ret;
    }

    public function parse_content($content) {
        $handle = fopen("php://memory", "r+");
        fwrite($handle, $content);
        rewind($handle);
        $ret = $this->parse_stream($handle);
        fclose($handle);
        return $ret;
    }

    public function parse_stream($handle) {
        $commits = array();

        // Regexp patterns.
        $reCommitHash = "/^([[:alnum:]]{40})$/";
        $reCommitInfo = "/^  ([[:alnum:]]+): (.+)$/";

        $line = fgets($handle);
        while ($line !== FALSE) {
            if (preg_match($reCommitHash, $line, $matches) === 1) {
                $hash = $matches[1];
                $timestamp = 0;
                $author = "";
                $subject = "";

                $line = fgets($handle);
                while ($line !== FALSE && preg_match($reCommitInfo, $line, $matches) === 1) {
                    switch($matches[1]) {
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

        return $commits;
    }
};
