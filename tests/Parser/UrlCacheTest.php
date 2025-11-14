<?php

declare(strict_types=1);

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

namespace Mesamatrix\Tests\Parser;

use Mesamatrix\Mesamatrix;
use Mesamatrix\Parser\UrlCache;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

final class UrlCacheTest extends TestCase
{
    public function testCanInstantiate(): UrlCache
    {
        $parser = new UrlCache();

        $this->assertInstanceOf('Mesamatrix\Parser\UrlCache', $parser);

        return $parser;
    }

    #[Depends('testCanInstantiate')]
    public function testReturnsFalseWhenUrlIsEmpty(UrlCache $urlCache): void
    {
        $this->assertFalse($urlCache->isValidResponse(""));
    }

    #[Depends('testCanInstantiate')]
    public function testReturnsFalseWhenUrlIsInvalid(UrlCache $urlCache): void
    {
        $this->assertFalse($urlCache->isValidResponse("aslkjdf"));
    }

    #[Depends('testCanInstantiate')]
    public function testReturnsFalseWhenStatusCodeIs404(UrlCache $urlCache): void
    {
        $this->assertFalse($urlCache->isValidResponse("HTTP/1.1 404 Not Found"));
    }

    #[Depends('testCanInstantiate')]
    public function testReturnsTrueWhenStatusCodeIs200(UrlCache $urlCache): void
    {
        $this->assertTrue($urlCache->isValidResponse("HTTP/1.1 200 OK"));
    }

    #[Depends('testCanInstantiate')]
    public function testReturnsTrueWhenStatusCodeIs202(UrlCache $urlCache): void
    {
        $this->assertTrue($urlCache->isValidResponse("HTTP/1.1 202 Accepted"));
    }
}
