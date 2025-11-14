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

namespace Mesamatrix\Tests;

use Mesamatrix\Config;
use Mesamatrix\Mesamatrix;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testCanInstantiate(): Config
    {
        Mesamatrix::init();

        $configDir = Mesamatrix::path(join(DIRECTORY_SEPARATOR, [ "tests", "resources", "config" ]));
        $config = new Config($configDir);

        $this->assertInstanceOf('Mesamatrix\\Config', $config);

        return $config;
    }

    #[Depends('testCanInstantiate')]
    public function testCanGetDefaultValue(Config $config): void
    {
        $this->assertEquals('test_value', $config->getValue('test_section1', 'key1'));
    }

    #[Depends('testCanInstantiate')]
    public function testCanGetOverriddenValue(Config $config): void
    {
        $this->assertEquals('overridden_value', $config->getValue('test_section1', 'key2'));
    }

    #[Depends('testCanInstantiate')]
    public function testCanReturnValueForUnknownKeyWithDefaultValue(Config $config): void
    {
        $this->assertEquals('not_found', $config->getValue('test_section1', 'key_unknown', 'not_found'));
    }

    #[Depends('testCanInstantiate')]
    public function testCanReturnNullForUnknownKeyWithoutDefaultValue(Config $config): void
    {
        $this->assertNull($config->getValue('test_section1', 'key_unknown'));
    }

    #[Depends('testCanInstantiate')]
    public function testCanReturnArray(Config $config): void
    {
        $expected = [ 'item1', 'item2' ];

        $result = $config->getValue('test_section2', 'key1');
        $this->assertEquals($expected, $config->getValue('test_section2', 'key1'));

        $this->assertIsArray($result);
        $this->assertEquals(count($expected), count($result));
        for ($i = 0; $i < count($expected); ++$i) {
            $this->assertEquals($expected[$i], $result[$i]);
        }
    }

    #[Depends('testCanInstantiate')]
    public function testCanReturnArrayWithUnknownKeyAndDefaultValue(Config $config): void
    {
        $expected = [ 'item1', 'item2' ];

        $result = $config->getValue('test_section2', 'key_unknown', $expected);

        $this->assertIsArray($result);
        $this->assertEquals(count($expected), count($result));
        for ($i = 0; $i < count($expected); ++$i) {
            $this->assertEquals($expected[$i], $result[$i]);
        }
    }
}
