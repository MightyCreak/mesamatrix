<?php

/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2021 Romain "Creak" Failliot.
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

declare(strict_types=1);

namespace Mesamatrix\Tests;

use Mesamatrix\Hints;
use PHPUnit\Framework\TestCase;

final class HintsTest extends TestCase
{
    public function testCanInstantiate(): Hints
    {
        $hints = new Hints();

        $this->assertInstanceOf('Mesamatrix\\Hints', $hints);

        return $hints;
    }

    /**
     * @depends testCanInstantiate
     */
    public function testCanAddHints(Hints $hints): Hints
    {
        $this->assertEquals(0, $hints->addHint('this is a hint'));
        $this->assertEquals(1, $hints->addHint('this is another hint'));

        return $hints;
    }

    /**
     * @depends testCanAddHints
     */
    public function testCanFindExistingHint(Hints $hints): void
    {
        $this->assertEquals(0, $hints->findHint('this is a hint'));
    }

    /**
     * @depends testCanAddHints
     */
    public function testCannotFindNonExistentHint(Hints $hints): void
    {
        $this->assertEquals(-1, $hints->findHint('this is not a hint'));
    }

    /**
     * @depends testCanAddHints
     */
    public function testCanGetNumHints(Hints $hints): void
    {
        $this->assertEquals(2, $hints->getNumHints());
    }

    /**
     * @depends testCanAddHints
     */
    public function testCanGetHint(Hints $hints): void
    {
        $this->assertEquals('this is another hint', $hints->getHint(1));
    }
}
