<?php

declare(strict_types=1);

/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2026 Mesamatrix contributors.
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

use Mesamatrix\Parser\CiExpectationsParser;
use PHPUnit\Framework\TestCase;

final class CiExpectationsParserTest extends TestCase
{
    public function testParsesRadeonsiPath(): void
    {
        $parser = new CiExpectationsParser();
        $meta = $parser->parsePath('src/amd/ci/radeonsi-raven-fails.txt');

        $this->assertNotNull($meta);
        $this->assertSame('radeonsi', $meta['driver']);
        $this->assertSame('raven', $meta['target']);
        $this->assertSame('fails', $meta['kind']);
    }

    public function testParsesZinkPathFromDriversDir(): void
    {
        $parser = new CiExpectationsParser();
        $meta = $parser->parsePath('src/gallium/drivers/zink/ci/zink-radv-navi31-skips.txt');

        $this->assertNotNull($meta);
        $this->assertSame('zink', $meta['driver']);
        $this->assertSame('radv-navi31', $meta['target']);
        $this->assertSame('skips', $meta['kind']);
    }

    public function testParsesCsvFailSuffix(): void
    {
        $parser = new CiExpectationsParser();
        $meta = $parser->parsePath('src/gallium/drivers/radeonsi/ci/gfx11-navi31-fail.csv');

        $this->assertNotNull($meta);
        $this->assertSame('radeonsi', $meta['driver']);
        $this->assertSame('gfx11-navi31', $meta['target']);
        $this->assertSame('fails', $meta['kind']);
    }

    public function testCountsEntriesIgnoringComments(): void
    {
        $parser = new CiExpectationsParser();
        $content = <<<'TXT'
# comment
glx@test,Fail

dEQP-VK.a.b

# another
TXT;

        $this->assertSame(2, $parser->countEntries($content));
    }

    public function testAggregatesMultipleKinds(): void
    {
        $parser = new CiExpectationsParser();
        $files = [
            'src/intel/ci/anv-tgl-fails.txt' => "a\nb\n",
            'src/intel/ci/anv-tgl-flakes.txt' => "c\n",
            'src/intel/ci/anv-tgl-skips.txt' => "# no\n\nd\ne\n",
        ];

        $entries = $parser->parseFiles(array_keys($files), static fn (string $path): string => $files[$path]);

        $this->assertCount(1, $entries);
        $this->assertSame('anv', $entries[0]->getDriver());
        $this->assertSame('tgl', $entries[0]->getTarget());
        $this->assertSame(2, $entries[0]->getFails());
        $this->assertSame(1, $entries[0]->getFlakes());
        $this->assertSame(2, $entries[0]->getSkips());
    }
}
