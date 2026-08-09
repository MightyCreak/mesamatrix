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

namespace Mesamatrix\Controller;

use Mesamatrix\Mesamatrix;
use Mesamatrix\Parser\CiApiSections;
use SimpleXMLElement;

class CiController extends BaseController
{
    private ?SimpleXMLElement $xml = null;

    /** @var list<array{id: string, name: string, drivers: list<array{driver: string, fails: int, flakes: int, skips: int, has_ci: bool, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}>}> */
    private array $sections = [];

    public function __construct()
    {
        parent::__construct();
        $this->setPage('CI Quality');
        $this->addJsScript('js/ci-quality.js?v=' . (Mesamatrix::$config->getValue('info', 'version')));
    }

    protected function computeRendering(): void
    {
        $xmlPath = Mesamatrix::path(Mesamatrix::$config->getValue('info', 'ci_xml_file', 'public/ci-expectations.xml'));
        if (!file_exists($xmlPath)) {
            return;
        }

        $xml = simplexml_load_file($xmlPath);
        if (!($xml instanceof SimpleXMLElement)) {
            return;
        }

        $this->xml = $xml;
        $this->sections = (new CiApiSections())->build($this->indexByDriver($xml));
    }

    protected function writeHtmlPage(): void
    {
        $mesaWeb = Mesamatrix::$config->getValue('git', 'mesa_web');
        $mesaBranch = Mesamatrix::$config->getValue('git', 'branch');

        echo <<<HTML
    <h1>CI Quality</h1>
    <p>
        Counts of known test failures, flakes and skips declared in Mesa CI expectation
        files (<code>*-fails.txt</code>, <code>*-flakes.txt</code>, <code>*-skips.txt</code>
        under <code>src/*/ci/</code>). These lists come from the Mesa git tree — the same
        source Mesamatrix already mirrors for <code>features.txt</code> — and do not require
        re-running GPU tests.
    </p>
    <p>
        Sections and driver names follow the Home page. Some CI trees use sibling names
        (e.g. <code>freedreno</code> → <code>tu</code>, <code>broadcom</code> → <code>v3dv</code>);
        those are mapped here. Drivers without expectation files are listed at the bottom.
        Click a row with targets to expand the per-hardware breakdown.
    </p>
HTML;

        if ($this->xml === null) {
            echo <<<'HTML'
    <p><em>No CI expectations data yet. Run <code>./mesamatrixctl parse</code> to generate it.</em></p>
HTML;
            return;
        }

        $hash = (string) $this->xml->commit->hash;
        $timestamp = (int) (string) $this->xml->commit->timestamp;
        $commitUrl = Mesamatrix::$config->getValue('git', 'mesa_commit_url') . $hash;
        $shortHash = substr($hash, 0, 8);
        $dateRfc = date(DATE_RFC2822, $timestamp);
        $dateHuman = date('Y-m-d H:i', $timestamp);

        $fails = (string) $this->xml->totals->fails;
        $flakes = (string) $this->xml->totals->flakes;
        $skips = (string) $this->xml->totals->skips;
        $configs = (string) $this->xml->totals->configs;

        echo <<<HTML
    <p>
        Based on Mesa <a href="{$mesaWeb}/tree/{$mesaBranch}" target="_blank">{$mesaBranch}</a>
        commit <a href="{$commitUrl}" target="_blank"><code>{$shortHash}</code></a>
        (<span class="toRelativeDate" data-timestamp="{$dateRfc}">{$dateHuman}</span>).
        <strong>{$configs}</strong> HW/job configs,
        <strong>{$fails}</strong> fails,
        <strong>{$flakes}</strong> flakes,
        <strong>{$skips}</strong> skips.
    </p>
HTML;

        foreach ($this->sections as $section) {
            $sectionName = htmlspecialchars($section['name'], ENT_QUOTES);
            $sectionId = htmlspecialchars($section['id'], ENT_QUOTES);
            echo <<<HTML
    <h1 id="{$sectionId}">{$sectionName}<a href="#{$sectionId}" class="permalink">&para;</a></h1>
HTML;
            $this->writeDriverTable($section['id'], $section['drivers']);
        }
    }

    /**
     * @param list<array{driver: string, fails: int, flakes: int, skips: int, has_ci: bool, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}> $drivers
     */
    private function writeDriverTable(string $sectionId, array $drivers): void
    {
        echo <<<'HTML'
    <table class="lb ci-quality">
        <thead>
            <tr>
                <th>#</th>
                <th>Driver</th>
                <th>Targets</th>
                <th>Fails</th>
                <th>Flakes</th>
                <th>Skips</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
HTML;

        $index = 1;
        $rank = 1;
        $prevKey = null;
        foreach ($drivers as $driver) {
            $total = CiApiSections::total($driver);
            $sortKey = ($driver['has_ci'] ? '1' : '0') . ':' . $total;
            if ($prevKey !== $sortKey) {
                $rank = $index;
            }
            $prevKey = $sortKey;

            $expandable = $driver['has_ci'] && $driver['targets'] !== [];
            $driverName = htmlspecialchars($driver['driver'], ENT_QUOTES);
            $rowId = htmlspecialchars($sectionId . ':' . $driver['driver'], ENT_QUOTES);
            $numTargets = count($driver['targets']);
            $targetsLabel = $driver['has_ci']
                ? ($numTargets . ' target' . ($numTargets === 1 ? '' : 's'))
                : 'no CI lists';
            $rankClass = ($driver['has_ci'] && $rank <= 3) ? ' lbCol-' . $this->ordinalClass($rank) : '';
            $summaryClass = 'ci-summary' . $rankClass . ($expandable ? '' : ' ci-empty');
            $tabIndex = $expandable ? '0' : '-1';
            $ariaExpanded = $expandable ? ' aria-expanded="false"' : '';

            echo <<<HTML
            <tr class="{$summaryClass}" data-ci-driver="{$rowId}" tabindex="{$tabIndex}"{$ariaExpanded}>
                <td class="lbCol-rank">{$rank}</td>
                <td class="lbCol-driver"><span class="ci-expander" aria-hidden="true"></span>{$driverName}</td>
                <td>{$targetsLabel}</td>
                <td class="ci-num">{$driver['fails']}</td>
                <td class="ci-num">{$driver['flakes']}</td>
                <td class="ci-num">{$driver['skips']}</td>
                <td class="ci-num"><strong>{$total}</strong></td>
            </tr>
HTML;

            foreach ($driver['targets'] as $target) {
                $targetName = htmlspecialchars($target['target'], ENT_QUOTES);
                $targetTotal = CiApiSections::total($target);
                $titleAttr = $target['sources'] !== ''
                    ? ' title="' . htmlspecialchars($target['sources'], ENT_QUOTES) . '"'
                    : '';

                echo <<<HTML
            <tr class="ci-detail" data-ci-driver="{$rowId}" hidden{$titleAttr}>
                <td></td>
                <td class="ci-detail-target" colspan="2">{$targetName}</td>
                <td class="ci-num">{$target['fails']}</td>
                <td class="ci-num">{$target['flakes']}</td>
                <td class="ci-num">{$target['skips']}</td>
                <td class="ci-num">{$targetTotal}</td>
            </tr>
HTML;
            }

            ++$index;
        }

        echo <<<'HTML'
        </tbody>
    </table>
HTML;
    }

    /**
     * @return array<string, array{driver: string, fails: int, flakes: int, skips: int, targets: list<array{target: string, fails: int, flakes: int, skips: int, sources: string}>}>
     */
    private function indexByDriver(SimpleXMLElement $xml): array
    {
        $grouped = [];

        foreach ($xml->configs->config as $config) {
            $driver = (string) $config['driver'];
            $fails = (int) (string) $config->fails;
            $flakes = (int) (string) $config->flakes;
            $skips = (int) (string) $config->skips;

            $sourceNames = [];
            foreach ($config->sources->file as $file) {
                $sourceNames[] = basename((string) $file);
            }

            $grouped[$driver] ??= [
                'driver' => $driver,
                'fails' => 0,
                'flakes' => 0,
                'skips' => 0,
                'targets' => [],
            ];
            $grouped[$driver]['fails'] += $fails;
            $grouped[$driver]['flakes'] += $flakes;
            $grouped[$driver]['skips'] += $skips;
            $grouped[$driver]['targets'][] = [
                'target' => (string) $config['target'],
                'fails' => $fails,
                'flakes' => $flakes,
                'skips' => $skips,
                'sources' => implode(', ', $sourceNames),
            ];
        }

        return $grouped;
    }

    private function ordinalClass(int $rank): string
    {
        return match ($rank) {
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            default => '',
        };
    }
}
