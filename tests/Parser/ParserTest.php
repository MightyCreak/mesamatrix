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

namespace Mesamatrix\Tests\Parser;

use Mesamatrix\Mesamatrix;
use Mesamatrix\Parser\Constants;
use Mesamatrix\Parser\Matrix;
use Mesamatrix\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function testCanInstantiate(): Parser
    {
        $parser = new Parser();

        $this->assertInstanceOf('Mesamatrix\Parser\Parser', $parser);

        return $parser;
    }

    /**
     * @depends testCanInstantiate
     */
    public function testCanParseContent(Parser $parser): void
    {
        Mesamatrix::init();
        $commitFilePath = Mesamatrix::path('tests/resources/commits/features.txt');

        $contents = file_get_contents($commitFilePath);
        $this->assertNotFalse($contents);

        $matrix = $parser->parseContent($contents);

        $this->assertInstanceOf('Mesamatrix\Parser\Matrix', $matrix);
    }

    /**
     * @depends testCanInstantiate
     */
    public function testCanParseFile(Parser $parser): Matrix
    {
        Mesamatrix::init();
        $commitFilePath = Mesamatrix::path('tests/resources/commits/features.txt');

        $matrix = $parser->parseFile($commitFilePath);

        $this->assertInstanceOf('Mesamatrix\Parser\Matrix', $matrix);

        return $matrix;
    }

    /**
     * @depends testCanParseFile
     */
    public function testCanSuccessfullyParseFeaturesFile(Matrix $matrix): void
    {
        $this->assertCount(27, $matrix->getApiVersions());

        $api = $matrix->getApiVersionByName(Constants::GL_NAME, '4.6');
        $this->assertInstanceOf('Mesamatrix\Parser\ApiVersion', $api);

        $this->assertEquals(Constants::GL_NAME, $api->getName());
        $this->assertEquals('4.6', $api->getVersion());
        $this->assertEquals('GLSL', $api->getGlslName());
        $this->assertEquals('4.60', $api->getGlslVersion());
        $this->assertEquals(11, $api->getNumExtensions());

        $api = $matrix->getApiVersionByName(Constants::GL_OR_ES_EXTRA_NAME, null);
        $this->assertInstanceOf('Mesamatrix\Parser\ApiVersion', $api);
    }
}
