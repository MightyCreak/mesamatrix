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
 * Aggregated CI expectation counts for one driver/hardware configuration.
 */
class CiExpectationEntry
{
    /** @var string[] */
    private array $sources = [];

    public function __construct(
        private string $driver,
        private string $target,
        private int $fails = 0,
        private int $flakes = 0,
        private int $skips = 0,
    ) {
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getFails(): int
    {
        return $this->fails;
    }

    public function getFlakes(): int
    {
        return $this->flakes;
    }

    public function getSkips(): int
    {
        return $this->skips;
    }

    /** @return string[] */
    public function getSources(): array
    {
        return $this->sources;
    }

    public function addCounts(string $kind, int $count, string $sourcePath): void
    {
        match ($kind) {
            'fails' => $this->fails += $count,
            'flakes' => $this->flakes += $count,
            'skips' => $this->skips += $count,
            default => null,
        };

        if (!in_array($sourcePath, $this->sources, true)) {
            $this->sources[] = $sourcePath;
        }
    }
}
