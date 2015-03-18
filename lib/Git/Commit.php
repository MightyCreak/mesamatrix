<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2015 Robin McCorkell <rmccorkell@karoshi.org.uk>
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

class Commit
{
    private $hash;
    private $date;
    private $author;
    private $committer;

    public function __construct($hash, $date, $author, $committer = null)
    {
        $this->setHash($hash);
        $this->setDate($date);
        $this->setAuthor($author);
        $this->setCommitter(isset($committer) ? $committer : $author);
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        if (is_numeric($date)) {
            $this->setDateString('@'.$date);
        } elseif (is_string($date)) {
            $this->setDateString($date);
        } else {
            $this->date = $date;
        }
    }

    public function setDateString($date, $timezone = null)
    {
        $this->setDate(new \DateTime($date, $timezone));
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getCommitter()
    {
        return $this->committer;
    }

    public function setCommitter($committer)
    {
        $this->committer = $committer;
    }
}
