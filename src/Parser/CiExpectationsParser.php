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

use SimpleXMLElement;

/**
 * Parses Mesa CI expectation files (*-fails/flakes/skips) into aggregated counts.
 */
class CiExpectationsParser
{
    private const string FILENAME_PATTERN =
        '/^(?P<label>.+)-(?P<kind>fails?|flakes?|skips?|slow-skips)\.(?:txt|csv)$/i';

    /** @var string[] */
    private array $knownDrivers;

    public function __construct()
    {
        $drivers = array_values(array_unique(array_merge(
            Constants::GL_ALL_DRIVERS,
            Constants::VK_ALL_DRIVERS,
            Constants::RUSTICL_OPENCL_ALL_DRIVERS,
            [
                'angle', 'lavapipe', 'venus', 'broadcom', 'amd', 'intel',
                'nouveau', 'imagination', 'microsoft', 'virtio', 'mesa', 'common',
            ]
        )));
        usort($drivers, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        $this->knownDrivers = $drivers;
    }

    /**
     * @param string[] $paths
     * @param callable(string): string $contentLoader
     * @return CiExpectationEntry[]
     */
    public function parseFiles(array $paths, callable $contentLoader): array
    {
        /** @var array<string, CiExpectationEntry> $entries */
        $entries = [];

        foreach ($paths as $path) {
            $meta = $this->parsePath($path);
            if ($meta === null) {
                continue;
            }

            $key = $meta['driver'] . "\0" . $meta['target'];
            $entries[$key] ??= new CiExpectationEntry($meta['driver'], $meta['target']);
            $entries[$key]->addCounts($meta['kind'], $this->countEntries($contentLoader($path)), $path);
        }

        $list = array_values($entries);
        usort(
            $list,
            static fn (CiExpectationEntry $a, CiExpectationEntry $b): int =>
                [$a->getDriver(), $a->getTarget()] <=> [$b->getDriver(), $b->getTarget()]
        );

        return $list;
    }

    /**
     * @param CiExpectationEntry[] $entries
     */
    public function buildXml(
        array $entries,
        string $commitHash,
        string $commitTimestamp,
        string $mesaWeb,
        string $branch,
    ): SimpleXMLElement {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ci-expectations></ci-expectations>');
        $xml->addAttribute('generated', date('c'));

        $commit = $xml->addChild('commit');
        $commit->addChild('hash', $commitHash);
        $commit->addChild('timestamp', $commitTimestamp);
        $commit->addChild('branch', $branch);
        $commit->addChild('mesa_web', $mesaWeb);

        $sumFails = $sumFlakes = $sumSkips = 0;
        foreach ($entries as $entry) {
            $sumFails += $entry->getFails();
            $sumFlakes += $entry->getFlakes();
            $sumSkips += $entry->getSkips();
        }

        $totals = $xml->addChild('totals');
        $totals->addChild('configs', (string) count($entries));
        $totals->addChild('fails', (string) $sumFails);
        $totals->addChild('flakes', (string) $sumFlakes);
        $totals->addChild('skips', (string) $sumSkips);

        $configs = $xml->addChild('configs');
        foreach ($entries as $entry) {
            $node = $configs->addChild('config');
            $node->addAttribute('driver', $entry->getDriver());
            $node->addAttribute('target', $entry->getTarget());
            $node->addChild('fails', (string) $entry->getFails());
            $node->addChild('flakes', (string) $entry->getFlakes());
            $node->addChild('skips', (string) $entry->getSkips());
            $sources = $node->addChild('sources');
            foreach ($entry->getSources() as $source) {
                $sources->addChild('file', $source);
            }
        }

        return $xml;
    }

    /**
     * @return array{driver: string, target: string, kind: string}|null
     */
    public function parsePath(string $path): ?array
    {
        $basename = basename($path);
        if (!preg_match(self::FILENAME_PATTERN, $basename, $matches)) {
            return null;
        }

        $label = $matches['label'];
        $driver = $this->driverFromPath($path)
            ?? $this->driverFromLabel($label)
            ?? (explode('-', $label, 2)[0] ?: 'unknown');

        $target = $label;
        $prefix = $driver . '-';
        if (str_starts_with($label, $prefix)) {
            $target = substr($label, strlen($prefix)) ?: $label;
        }

        return [
            'driver' => $driver,
            'target' => $target,
            'kind' => $this->normalizeKind($matches['kind']),
        ];
    }

    public function countEntries(string $content): int
    {
        $count = 0;
        foreach (preg_split("/\r\n|\n|\r/", $content) as $line) {
            $line = trim($line);
            if ($line !== '' && !str_starts_with($line, '#')) {
                ++$count;
            }
        }

        return $count;
    }

    public function isExpectationPath(string $path): bool
    {
        return (str_contains($path, '/ci/') || str_starts_with($path, '.gitlab-ci/'))
            && (bool) preg_match(self::FILENAME_PATTERN, basename($path));
    }

    private function normalizeKind(string $kind): string
    {
        $kind = strtolower($kind);
        return match (true) {
            str_starts_with($kind, 'fail') => 'fails',
            str_starts_with($kind, 'flake') => 'flakes',
            default => 'skips',
        };
    }

    private function driverFromPath(string $path): ?string
    {
        if (preg_match('#(?:^|/)(?:gallium/)?drivers/([^/]+)/ci/#', $path, $matches)) {
            return $matches[1];
        }
        if (str_starts_with($path, '.gitlab-ci/')) {
            return 'common';
        }

        return null;
    }

    private function driverFromLabel(string $label): ?string
    {
        $lower = strtolower($label);
        foreach ($this->knownDrivers as $driver) {
            if ($lower === $driver || str_starts_with($lower, $driver . '-')) {
                return $driver;
            }
        }

        return null;
    }
}
