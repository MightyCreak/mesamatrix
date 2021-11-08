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
    private $filepath;
    private $hash;
    private $date;
    private $author;
    private $committer;
    private $committerDate;
    private $subject;
    private $data;

    public function getFilepath()
    {
        return $this->filepath;
    }

    public function setFilepath($filepath)
    {
        $this->filepath = $filepath;
        return $this;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * Get the commit date.
     *
     * @return \DateTime The commit date.
     */
    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        if (is_numeric($date)) {
            $this->setDateString('@' . $date);
        } elseif (is_string($date)) {
            $this->setDateString($date);
        } else {
            $this->date = $date;
        }

        return $this;
    }

    public function setDateString($date, $timezone = null)
    {
        $this->setDate(new \DateTime($date, $timezone));
        return $this;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
        return $this;
    }

    public function getCommitter()
    {
        return $this->committer;
    }

    public function setCommitter($committer)
    {
        $this->committer = $committer;
        return $this;
    }

    public function getCommitterDate()
    {
        return $this->committerDate;
    }

    public function setCommitterDate($date)
    {
        if (is_numeric($date)) {
            $this->setCommitterDateString('@' . $date);
        } elseif (is_string($date)) {
            $this->setCommitterDateString($date);
        } else {
            $this->committerDate = $date;
        }

        return $this;
    }

    public function setCommitterDateString($date, $timezone = null)
    {
        $this->setCommitterDate(new \DateTime($date, $timezone));
        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
}
