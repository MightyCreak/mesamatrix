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

class Hints
{
    public function __construct()
    {
        $this->list = array();
    }

    public function findHint($hint)
    {
        $idx = -1;
        if(!empty($hint))
        {
            $key = array_search($hint, $this->list);
            if($key !== FALSE)
            {
                $idx = $key;
            }
        }

        return $idx;
     }

    public function addHint($hint)
    {
        $idx = -1;
        if(!empty($hint))
        {
            $idx = array_search($hint, $this->list);
            if($idx === FALSE)
            {
                $this->list[] = $hint;
                $idx = count($this->list) - 1;
            }
        }

        return $idx;
    }

    public function getNumHints()
    {
        return count($this->list);
    }

    public function getHint($idx)
    {
        return $this->list[$idx];
    }

    private $list;
};

