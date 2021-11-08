<?php

/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014-2017 Romain "Creak" Failliot.
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

namespace Mesamatrix\Parser;

use Mesamatrix\Git\Commit;

class SupportedDriver
{
    public function __construct($name, $hints)
    {
        $this->name = $name;
        $this->hints = $hints;
        $this->hintIdx = -1;
        $this->setModifiedAt(null);
    }

    // name
    public function setName($name)
    {
        $this->name = $name;
    }
    public function getName()
    {
        return $this->name;
    }

    // hints
    public function setHint($hint)
    {
        $this->hintIdx = $this->hints->addToHints($hint);
    }
    public function getHintIdx()
    {
        return $this->hintIdx;
    }
    public function getHint()
    {
        if ($this->hintIdx === -1) {
            return null;
        }

        return $this->hints->allHints[$this->hintIdx];
    }

    // modified at
    public function setModifiedAt($commit)
    {
        $this->modifiedAt = $commit;
    }
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    private $name;
    private $hints;
    private $hintIdx;
    private $modifiedAt;
}
