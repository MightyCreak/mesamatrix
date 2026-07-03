<?php

declare(strict_types=1);

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
    private string $filepath;
    private string $hash;
    private \DateTime $date;
    private string $author;
    private string $committer;
    private \DateTime $committerDate;
    private string $subject;
    private string $data;

    public function getFilepath(): string
    {
        return $this->filepath;
    }

    public function setFilepath(string $filepath): static
    {
        $this->filepath = $filepath;
        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): static
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * Get the commit date.
     *
     * @return \DateTime The commit date.
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(int|string|\DateTime $date): static
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

    private function setDateString(string $date): static
    {
        $this->date = new \DateTime($date);
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getCommitter(): string
    {
        return $this->committer;
    }

    public function setCommitter(string $committer): static
    {
        $this->committer = $committer;
        return $this;
    }

    public function getCommitterDate(): \DateTime
    {
        return $this->committerDate;
    }

    public function setCommitterDate(string|\DateTime $date): static
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

    private function setCommitterDateString(string $date): static
    {
        $this->committerDate = new \DateTime($date);
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): static
    {
        $this->data = $data;
        return $this;
    }
}
