<?php

/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014-2021 Romain "Creak" Failliot.
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
use Mesamatrix\Leaderboard\Leaderboard;
use Mesamatrix\Parser\Constants;
use SimpleXMLElement;

class ApiSubController
{
    private string $api;
    private bool $showLbVersion;
    private ?SimpleXMLElement $xml = null;
    private ?Leaderboard $leaderboard = null;
    private array $matrix = array();

    public function __construct(string $api, bool $showLbVersion)
    {
        $this->api = $api;
        $this->showLbVersion = $showLbVersion;
    }

    public function prepare(): void
    {
        $this->xml = $this->loadMesamatrixXml();

        $xmlApi = null;
        foreach ($this->xml->apis->api as $it) {
            if ((string) $it['name'] === $this->api) {
                $xmlApi = $it;
                break;
            }
        }

        $this->createLeaderboard($xmlApi);
        $this->createMatrixModel($xmlApi);
    }

    private function loadMesamatrixXml(): ?SimpleXMLElement
    {
        $featuresXmlFilepath = Mesamatrix::path(Mesamatrix::$config->getValue('info', 'xml_file'));
        $xml = simplexml_load_file($featuresXmlFilepath);
        if (!$xml) {
            Mesamatrix::$logger->critical('Can\'t read ' . $featuresXmlFilepath);
            exit();
        }

        return $xml;
    }

    private function createLeaderboard(SimpleXMLElement $xmlApi): void
    {
        $this->leaderboard = new Leaderboard($this->showLbVersion);
        $this->leaderboard->load($xmlApi);
    }

    public function createMatrixModel(SimpleXMLElement $xmlApi): void
    {
        $this->createColumns($this->matrix, $xmlApi);

        $this->matrix['sections'] = array();
        $this->addApiSection($this->matrix, $xmlApi);

        $this->matrix['last_updated'] = (int) $this->xml['updated'];
    }

    public function getLastUpdatedTime(): int
    {
        return $this->matrix['last_updated'];
    }

    private function createColumns(array &$matrix, SimpleXMLElement $xmlApi): void
    {
        $matrix['column_groups'] = array();
        $matrix['columns'] = array();

        $columnIdx = 0;

        // Add "extension" column.
        $matrix['column_groups'][] = array(
            'name' => '',
            'vendor_class' => 'default',
            'columns' => array($columnIdx++)
        );
        $matrix['columns'][] = array(
            'name' => 'Extension',
            'type' => 'extension',
            'vendor_class' => 'default'
        );

        // Add "mesa" column.
        $matrix['column_groups'][] = array(
            'name' => '',
            'vendor_class' => 'default',
            'columns' => array($columnIdx++)
        );
        $matrix['columns'][] = array(
            'name' => 'mesa',
            'type' => 'driver',
            'vendor_class' => 'default'

        );

        // Get all the vendors and all their drivers.
        $vendors = array();
        foreach ($xmlApi->vendors->vendor as $vendor) {
            $vendorName = (string) $vendor['name'];
            if (!array_key_exists($vendorName, $vendors)) {
                $vendors[$vendorName] = array();
            }

            $driverNames = &$vendors[$vendorName];
            foreach ($vendor->drivers->driver as $driver) {
                $driverName = (string) $driver['name'];
                if (!in_array($driverName, $driverNames)) {
                    $driverNames[] = $driverName;
                }
            }
        }

        unset($driverNames);

        foreach ($vendors as $vendorName => $driverNames) {
            // Add separator before each vendor.
            $matrix['column_groups'][] = array(
                'name' => '',
                'columns' => array($columnIdx++)
            );
            $matrix['columns'][] = array(
                'name' => '',
                'type' => 'separator',
            );

            // Add vendor drivers columns.
            $colgroup = array(
                'name' => $vendorName,
                'vendor_class' => strtolower($vendorName),
                'columns' => array()
            );
            foreach ($driverNames as $driverName) {
                $colgroup['columns'][] = $columnIdx++;
                $matrix['columns'][] = array(
                    'name' => $driverName,
                    'type' => 'driver',
                    'vendor_class' => $colgroup['vendor_class']
                );
            }

            $matrix['column_groups'][] = $colgroup;
        }
    }

    private function addApiSection(array &$matrix, SimpleXMLElement $xmlApi): void
    {
        $xmlVersions = array();
        foreach ($xmlApi->versions->version as $xmlVersion) {
            $xmlVersions[] = $xmlVersion;
        }

        if (empty($xmlVersions)) {
            return;
        }

        // Sort the versions descending.
        usort($xmlVersions, function ($a, $b) {
            $diff = (float) $b['version'] - (float) $a['version'];
            if ($diff === 0) {
                return 0;
            } else {
                return $diff < 0 ? -1 : 1;
            }
        });

        $api = array(
            'name' => $xmlApi['name'],
            'target' => urlencode(str_replace(' ', '', $xmlApi['name'])),
            'subsections' => array()
        );

        foreach ($xmlVersions as $xmlVersion) {
            $this->addSection($api, $xmlVersion, $xmlApi->vendors);
        }

        $matrix['sections'][] = $api;
    }

    private function addSection(array &$section, SimpleXMLElement $xmlVersion, SimpleXMLElement $vendors): void
    {
        $name = $xmlVersion['name'];
        if (!empty((string) $xmlVersion['version'])) {
            $name .= ' ' . $xmlVersion['version'];
        }

        $xmlShaderVersion = $xmlVersion->{'shader-version'};
        if (!empty((string) $xmlShaderVersion['name'])) {
            $name .= ' - ' . $xmlShaderVersion['name'] . ' ' . $xmlShaderVersion['version'];
        }

        $target = $xmlVersion['name'];
        $target .= !empty($xmlVersion['version']) ? $xmlVersion['version'] : '_Extensions';
        $target = urlencode(str_replace(' ', '', $target));

        $subsection = array(
            'name' => $name,
            'target' => $target,
            'scores' => array(),
            'extensions' => array()
        );

        $lbApiVersion = $this->leaderboard->findApiVersion($xmlVersion['name'] . $xmlVersion['version']);
        if ($lbApiVersion !== null) {
            $driverScores = array();
            $driverScores['mesa'] = $lbApiVersion->getDriverScore('mesa')->getScore();
            foreach ($vendors->vendor as $vendor) {
                foreach ($vendor->drivers->driver as $driver) {
                    $driverName = (string) $driver['name'];
                    $driverScores[$driverName] = $lbApiVersion->getDriverScore($driverName)->getScore();
                }
            }

            $subsection['scores'] = $driverScores;

            foreach ($xmlVersion->extensions->extension as $xmlExt) {
                $this->addExtension($subsection, $xmlExt, false, $vendors);
                $xmlSubExts = $xmlExt->xpath('./subextensions/subextension');
                foreach ($xmlSubExts as $xmlSubExt) {
                    $this->addExtension($subsection, $xmlSubExt, true, $vendors);
                }
            }
        }

        $section['subsections'][] = $subsection;
    }

    private function addExtension(
        array &$subsection,
        SimpleXMLElement $xmlExt,
        bool $isSubExt,
        SimpleXMLElement $vendors
    ): void {
        $extension = array(
            'name' => $xmlExt['name'],
        );

        $extUrlId = str_replace(' ', '_', $xmlExt['name']);
        $extUrlId = preg_replace('/[^A-Za-z0-9_]/', '', $extUrlId);
        $extUrlId = $subsection['target'] . '_Extension_' . $extUrlId;
        $extension['target'] = $extUrlId;
        $extension['is_subext'] = $isSubExt;

        if (isset($xmlExt->link)) {
            $extension['url_href'] = $xmlExt->link['href'];
            $extension['url_text'] = $xmlExt->link;
        }

        $extTask = array();
        if ($xmlExt->mesa['status'] == Constants::STATUS_DONE) {
            $extTask['class'] = 'isDone';
        } elseif ($xmlExt->mesa['status'] == Constants::STATUS_NOT_STARTED) {
            $extTask['class'] = 'isNotStarted';
        } else {
            $extTask['class'] = 'isInProgress';
        }
        if (!empty($xmlExt->mesa->modified)) {
            $extTask['timestamp'] = (int) $xmlExt->mesa->modified->date;
        }
        $extTask['hint'] = $xmlExt->mesa['hint'];
        $extension['tasks']['mesa'] = $extTask;

        foreach ($vendors->vendor as $vendor) {
            foreach ($vendor->drivers->driver as $driver) {
                $driverName = (string) $driver['name'];

                $extTask = array();
                $xmlSupportedDrivers = $xmlExt->xpath("./supported-drivers/driver[@name='${driverName}']");
                $xmlSupportedDriver = !empty($xmlSupportedDrivers) ? $xmlSupportedDrivers[0] : null;
                if ($xmlSupportedDriver) {
                    $extTask['class'] = 'isDone';
                    if (!empty($xmlSupportedDriver->modified)) {
                        $extTask['timestamp'] = (int) $xmlSupportedDriver->modified->date;
                    }
                    $extTask['hint'] = $xmlSupportedDriver['hint'];
                } else {
                    $extTask['class'] = 'isNotStarted';
                }
                $extension['tasks'][$driverName] = $extTask;
            }
        }

        $subsection['extensions'][] = $extension;
    }

    public function writeMatrix(): void
    {
        if (array_key_exists('sections', $this->matrix) == 0) {
            return;
        }

        // Sections.
        foreach ($this->matrix['sections'] as $section) :
            $sectionName = (string)$section['name'];
            $sectionId = (string)$section['target'];

            echo <<<HTML
    <h1 id="{$sectionId}">{$sectionName}<a href="#{$sectionId}" class="permalink">&para;</a></h1>
HTML;

            $this->writeLeaderboard();

            echo <<<'HTML'
    <details>
        <summary>Drivers details</summary>
        <table class="matrix">
HTML;

            // Colgroups.
            foreach ($this->matrix['column_groups'] as $colgroup) :
                foreach ($colgroup['columns'] as $colIdx) :
                    $col = $this->matrix['columns'][$colIdx];
                    if ($col['type'] === 'driver') :
                        echo <<<'HTML'
            <colgroup class="hl">
HTML;
                    else :
                        echo <<<'HTML'
            <colgroup>
HTML;
                    endif;
                endforeach;
            endforeach;

            // Sub-sections.
            $numColumns = count($this->matrix['columns']);
            foreach ($section['subsections'] as $subsection) :
                $subsectionName = (string)$subsection['name'];
                $subsectionId = (string)$subsection['target'];

                if (!empty($subsectionName)) :
                    echo <<<HTML
            <tr>
                <td colspan="{$numColumns}">
                    <h2 id="{$subsectionId}">{$subsectionName}<a href="#{$subsectionId}" class="permalink">&para;</a></h2>
                </td>
            </tr>
HTML;
                endif;

                echo <<<'HTML'
            <tr>
HTML;

                // Header (vendors).
                foreach ($this->matrix['column_groups'] as $colgroup) :
                    if (empty($colgroup['name'])) :
                        echo <<<'HTML'
                <td></td>
HTML;
                    else :
                        $colspan = count($colgroup['columns']);
                        $colgroupName = $colgroup['name'];
                        $vendorClass = "hCellVendor-" . $colgroup['vendor_class'];

                        echo <<<HTML
                <td colspan="{$colspan}" class="hCellHeader hCellVendor-{$vendorClass}">{$colgroupName}</td>
HTML;
                    endif;
                endforeach;

                echo <<<'HTML'
            </tr>
            <tr>
HTML;

                // Header (drivers).
                foreach ($this->matrix['columns'] as $col) :
                    if ($col['type'] === 'extension') :
                        echo <<<HTML
                <td class="hCellHeader hCellVendor-default">{$col['name']}</td>
HTML;
                    elseif ($col['type'] === 'driver') :
                        echo <<<HTML
                <td class="hCellHeader hCellVendor-{$col['vendor_class']}">{$col['name']}</td>
HTML;
                    elseif ($col['type'] === 'separator') :
                        echo <<<'HTML'
                <td class="hCellSep"></td>
HTML;
                    else :
                        echo <<<HTML
                <td>{$col['name']}</td>
HTML;
                    endif;
                endforeach;

                echo <<<'HTML'
            </tr>
            <tr>
HTML;

                // Scores.
                foreach ($this->matrix['columns'] as $col) :
                    if ($col['type'] === 'driver') :
                        $scoreStr = sprintf('%.1f', $subsection['scores'][$col['name']] * 100);
                        echo <<<HTML
                <td class="hCellHeader hCellDriverScore" data-score="{$scoreStr}">{$scoreStr}%</td>
HTML;
                    else :
                        echo <<<'HTML'
                <td></td>
HTML;
                    endif;
                endforeach;

                echo <<<'HTML'
            </tr>
HTML;

                // Extensions.
                foreach ($subsection['extensions'] as $extension) :
                    echo <<<'HTML'
            <tr class="extension">
HTML;
                    foreach ($this->matrix['columns'] as $col) :
                        if ($col['type'] === 'extension') :
                            $extNameText = $extension['name'];
                            if (isset($extension['url_text'])) :
                                $extNameText = str_replace($extension['url_text'], '<a href="' . $extension['url_href'] . '">' . $extension['url_text'] . '</a>', $extNameText);
                            endif;
                            $cssClass = '';
                            if ($extension['is_subext']) :
                                $cssClass = ' class="extension-child"';
                            endif;
                            echo <<<HTML
                <td id="{$extension['target']}"{$cssClass}>
                    {$extNameText}<a href="#{$extension['target']}" class="permalink">&para;</a>
                </td>
HTML;
                        elseif ($col['type'] === 'driver') :
                            $driverTask = $extension['tasks'][$col['name']];
                            $cssClasses = array('task', $driverTask['class']);
                            $title = '';
                            if (isset($driverTask['hint'])) :
                                $cssClasses[] = 'footnote';
                                $title = ' title="' . $driverTask['hint'] . '"';
                            endif;

                            $cssClassesStr = join(' ', $cssClasses);
                            echo <<<HTML
                <td class="{$cssClassesStr}"{$title}>
HTML;
                            if (isset($driverTask['timestamp'])) :
                                $date = date('Y-m-d', $driverTask['timestamp']);
                                echo <<<HTML
                    <span data-timestamp="{$driverTask['timestamp']}">{$date}</span>
HTML;
                            endif;
                            echo <<<'HTML'
                </td>
HTML;
                        else :
                            echo <<<'HTML'
                <td></td>
HTML;
                        endif;
                    endforeach;
                    echo <<<'HTML'
            </tr>
HTML;
                endforeach;
            endforeach;
        endforeach;
        echo <<<'HTML'
        </table>
    </details>
HTML;
    }

    private function writeLeaderboard(): void
    {
        $sortedDrivers = $this->leaderboard->getDriversSortedByExtsDone($this->api);
        $numTotalExts = $this->leaderboard->getNumTotalExts();
        $colNames = [ '#', 'Driver', 'Extensions' ];
        if ($this->showLbVersion) {
            $colNames[] = 'Version';
        }

        echo <<<HTML
    <p>
        There is a total of <strong>{$numTotalExts}</strong> extensions to implement.
        The ranking is based on the number of extensions done by driver.
    </p>
    <table class="lb">
        <thead>
            <tr>
HTML;

        foreach ($colNames as $name) :
            echo <<<HTML
                <th>{$name}</th>
HTML;
        endforeach;

        echo <<<'HTML'
            </tr>
        </thead>
        <tbody>
HTML;

        $index = 1;
        $rank = 1;
        $prevNumExtsDone = -1;
        foreach ($sortedDrivers as $driverName => $driverScore) {
            $numExtsDone = $driverScore->getNumExtensionsDone();
            $sameRank = $prevNumExtsDone === $numExtsDone;
            if (!$sameRank) {
                $rank = $index;
            }

            switch ($rank) {
                case 1:
                    $rankRowClass = "lbCol-1st";
                    break;
                case 2:
                    $rankRowClass = "lbCol-2nd";
                    break;
                case 3:
                    $rankRowClass = "lbCol-3rd";
                    break;
                default:
                    $rankRowClass = "";
            }

            $rankCellClasses = array('lbCol-rank');
            if ($sameRank) {
                $rankCellClasses[] = 'lbCol-rank-same';
            }

            $pctScore = sprintf("%.1f%%", $driverScore->getScore() * 100);
            $apiVersion = null;
            if ($this->showLbVersion) {
                $apiVersion = $driverScore->getApiVersion();
                if ($apiVersion === null) {
                    $apiVersion = "N/A";
                }
            }

            $cssClassesStr = join(' ', $rankCellClasses);
            echo <<<HTML
            <tr class="{$rankRowClass}">
                <th class="{$cssClassesStr}">{$rank}</th>
                <td class="lbCol-driver">{$driverName}</td>
                <td class="lbCol-score"><span class="lbCol-pctScore">({$pctScore})</span> {$numExtsDone}</td>
HTML;
            if ($this->showLbVersion) :
                echo <<<HTML
                <td class="lbCol-version">{$apiVersion}</td>
HTML;
            endif;

            echo <<<'HTML'
            </tr>
HTML;
            $prevNumExtsDone = $numExtsDone;
            $index++;
        }

        echo <<<'HTML'
        </tbody>
    </table>
HTML;
    }
}
