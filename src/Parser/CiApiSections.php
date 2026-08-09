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

namespace Mesamatrix\Parser;

/**
 * Maps raw CI driver aggregates onto Home-page API sections.
 */
class CiApiSections
{
    /**
     * Extra CI tree names → Mesamatrix driver id, per API section.
     *
     * @var array<string, array<string, string>>
     */
    private const array ALIASES = [
        'Vulkan' => [
            'venus' => 'vn',
            'lavapipe' => 'lvp',
            'freedreno' => 'tu',
            'broadcom' => 'v3dv',
            'asahi' => 'hk',
            'warp' => 'dzn',
            'axe' => 'pvr',
            'bxs' => 'pvr',
        ],
        'OpenGL' => [
            'broadcom' => 'v3d',
        ],
        'OpenCL' => [],
    ];

    /**
     * @param array<string, array{driver: string, fails: int, flakes: int, skips: int, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}> $byCiName
     * @return list<array{id: string, name: string, drivers: list<array{driver: string, fails: int, flakes: int, skips: int, has_ci: bool, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}>}>
     */
    public function build(array $byCiName): array
    {
        $sections = [
            $this->section('Vulkan', Constants::VK_NAME, Constants::VK_ALL_DRIVERS, $byCiName),
            $this->section(
                'OpenGL',
                Constants::GL_NAME . ' / ' . Constants::GLES_NAME,
                Constants::GL_ALL_DRIVERS,
                $byCiName
            ),
            $this->section(
                'OpenCL',
                Constants::RUSTICL_OPENCL_NAME,
                Constants::RUSTICL_OPENCL_ALL_DRIVERS,
                $byCiName
            ),
            [
                'id' => 'Other',
                'name' => 'Other / layered CI configs',
                'drivers' => $this->sortDrivers($this->unmapped($byCiName)),
            ],
        ];

        return array_values(array_filter(
            $sections,
            static fn (array $section): bool => $section['drivers'] !== []
        ));
    }

    /**
     * @param string[] $homeDrivers
     * @param array<string, array{driver: string, fails: int, flakes: int, skips: int, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}> $byCiName
     * @return array{id: string, name: string, drivers: list<array{driver: string, fails: int, flakes: int, skips: int, has_ci: bool, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}>}
     */
    private function section(string $id, string $name, array $homeDrivers, array $byCiName): array
    {
        $aliases = self::ALIASES[$id];
        $sourceMap = array_combine($homeDrivers, $homeDrivers) + $aliases;

        $aggregated = [];
        foreach ($homeDrivers as $driver) {
            $aggregated[$driver] = $this->emptyDriver($driver);
        }

        foreach ($byCiName as $ciName => $entry) {
            if ($ciName === 'nouveau' && $id === 'OpenGL') {
                $this->mergeNouveau($aggregated, $entry);
                continue;
            }

            $homeName = $sourceMap[$ciName] ?? null;
            if ($homeName === null || !isset($aggregated[$homeName])) {
                continue;
            }

            $this->merge($aggregated[$homeName], $entry);
        }

        return [
            'id' => $id,
            'name' => $name,
            'drivers' => $this->sortDrivers(array_values($aggregated)),
        ];
    }

    /**
     * @param array<string, array{driver: string, fails: int, flakes: int, skips: int, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}> $byCiName
     * @return list<array{driver: string, fails: int, flakes: int, skips: int, has_ci: bool, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}>
     */
    private function unmapped(array $byCiName): array
    {
        $mapped = ['nouveau' => true];
        foreach (self::ALIASES as $aliases) {
            foreach ($aliases as $ciName => $_home) {
                $mapped[$ciName] = true;
            }
        }
        foreach (
            [
                ...Constants::VK_ALL_DRIVERS,
                ...Constants::GL_ALL_DRIVERS,
                ...Constants::RUSTICL_OPENCL_ALL_DRIVERS,
            ] as $driver
        ) {
            $mapped[$driver] = true;
        }

        $other = [];
        foreach ($byCiName as $name => $entry) {
            if (!isset($mapped[$name])) {
                $other[] = $entry + ['has_ci' => true];
            }
        }

        return $other;
    }

    /**
     * @return array{driver: string, fails: int, flakes: int, skips: int, has_ci: bool, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}
     */
    private function emptyDriver(string $driver): array
    {
        return [
            'driver' => $driver,
            'fails' => 0,
            'flakes' => 0,
            'skips' => 0,
            'has_ci' => false,
            'targets' => [],
        ];
    }

    /**
     * @param array{driver: string, fails: int, flakes: int, skips: int, has_ci: bool, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>} $dest
     * @param array{driver: string, fails: int, flakes: int, skips: int, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>} $src
     */
    private function merge(array &$dest, array $src): void
    {
        $dest['fails'] += $src['fails'];
        $dest['flakes'] += $src['flakes'];
        $dest['skips'] += $src['skips'];
        $dest['has_ci'] = true;
        array_push($dest['targets'], ...$src['targets']);
    }

    /**
     * @param array<string, array{driver: string, fails: int, flakes: int, skips: int, has_ci: bool, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}> $aggregated
     * @param array{driver: string, fails: int, flakes: int, skips: int, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>} $entry
     */
    private function mergeNouveau(array &$aggregated, array $entry): void
    {
        foreach ($entry['targets'] as $target) {
            $home = preg_match('/^(nv[0-9]|gt[0-9])/', $target['target']) ? 'nv50' : 'nvc0';
            if (!isset($aggregated[$home])) {
                continue;
            }
            $this->merge($aggregated[$home], [
                'driver' => $home,
                'fails' => $target['fails'],
                'flakes' => $target['flakes'],
                'skips' => $target['skips'],
                'targets' => [$target],
            ]);
        }
    }

    /**
     * @param list<array{driver: string, fails: int, flakes: int, skips: int, has_ci: bool, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}> $drivers
     * @return list<array{driver: string, fails: int, flakes: int, skips: int, has_ci: bool, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}>
     */
    private function sortDrivers(array $drivers): array
    {
        foreach ($drivers as &$driver) {
            usort(
                $driver['targets'],
                static fn (array $a, array $b): int =>
                    self::total($a) <=> self::total($b) ?: strcmp($a['target'], $b['target'])
            );
        }
        unset($driver);

        usort(
            $drivers,
            static function (array $a, array $b): int {
                if ($a['has_ci'] !== $b['has_ci']) {
                    return $a['has_ci'] ? -1 : 1;
                }

                return self::total($a) <=> self::total($b) ?: strcmp($a['driver'], $b['driver']);
            }
        );

        return $drivers;
    }

    /** @param array{fails: int, flakes: int, skips: int} $counts */
    public static function total(array $counts): int
    {
        return $counts['fails'] + $counts['flakes'] + $counts['skips'];
    }
}
