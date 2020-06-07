<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014-2017 Romain "Creak" Failliot.
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

class ApiSubController
{
    private $apis = array();
    private $xml = null;
    private $leaderboard = null;
    private $matrix = array();

    public function setApis(array $apis) {
        $this->apis = $apis;
    }

    public function getLeaderboard() {
        return $this->leaderboard;
    }

    public function prepare() {
        $this->xml = $this->loadMesamatrixXml();

        $this->createLeaderboard($this->xml);
        $this->createMatrixModel($this->xml);
    }

    private function loadMesamatrixXml() {
        $gl3Path = \Mesamatrix::path(\Mesamatrix::$config->getValue('info', 'xml_file'));
        $xml = simplexml_load_file($gl3Path);
        if (!$xml) {
            \Mesamatrix::$logger->critical('Can\'t read '.$gl3Path);
            exit();
        }

        return $xml;
    }

    private function createLeaderboard(\SimpleXMLElement $xml) {
        $this->leaderboard = new \Mesamatrix\Leaderboard();
        $this->leaderboard->load($xml, $this->apis);
    }

    public function createMatrixModel(\SimpleXMLElement $xml) {
        $xmlApis = array();
        foreach ($this->apis as $api) {
            foreach ($xml->apis->api as $xmlApi) {
                if ((string) $xmlApi['name'] === $api)
                    $xmlApis[] = $xmlApi;
            }
        }

        $this->matrix = array();
        $this->createColumns($this->matrix, $xmlApis);

        $this->matrix['sections'] = array();
        foreach ($xmlApis as $xmlApi) {
            $this->addApiSection($this->matrix, $xmlApi);
        }

        $this->matrix['last_updated'] = (int) $xml['updated'];
    }

    public function getLastUpdatedTime() {
        return $this->matrix['last_updated'];
    }

    private function createColumns(array &$matrix, array $xmlApis) {
        $matrix['column_groups'] = array();
        $matrix['columns'] = array();

        $columnIdx = 0;

        // Add "extension" columon.
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
        foreach ($xmlApis as $xmlApi) {
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

    private function addApiSection(array &$matrix, \SimpleXmlElement $xmlApi) {
        $glVersions = array();
        foreach ($xmlApi->version as $glVersion) {
            $glVersions[] = $glVersion;
        }

        if (empty($glVersions))
            return;

        // Sort the versions descending.
        usort($glVersions, function($a, $b) {
            $diff = (float) $b['version'] - (float) $a['version'];
            if ($diff === 0)
                return 0;
            else
                return $diff < 0 ? -1 : 1;
        });

        $api = array(
            'name' => $xmlApi['name'],
            'target' => 'Version_'.urlencode(str_replace(' ', '', $xmlApi['name'])),
            'subsections' => array()
        );

        foreach ($glVersions as $glVersion) {
            $this->addSection($api, $glVersion, $xmlApi->vendors);
        }

        $matrix['sections'][] = $api;
    }

    private function addSection(array &$section, \SimpleXMLElement $glSection, \SimpleXMLElement $vendors) {
        $text = "";
        if (!empty($glSection['version'])) {
            $text = $glSection['name'];
            if (!empty((string) $glSection['version'])) {
                $text .= ' '.$glSection['version'];
            }
            if (!empty((string) $glSection->glsl['name'])) {
                $text .= ' - '.$glSection->glsl['name'].' '.$glSection->glsl['version'];
            }
        }

        $subsection = array(
            'name' => $text,
            'target' => !empty($text) ? 'Version_'.urlencode(str_replace(' ', '', $text)) : '',
            'scores' => array(),
            'extensions' => array()
        );

        $lbGlVersion = $this->leaderboard->findGlVersion($glSection['name'].$glSection['version']);
        if ($lbGlVersion !== NULL) {
            $numGlVersionExts = $lbGlVersion->getNumExts();

            $driverScores = array();
            $driverScores['mesa'] = $numGlVersionExts !== 0 ? $lbGlVersion->getNumDriverExtsDone('mesa') / $numGlVersionExts : 0;
            foreach ($vendors->vendor as $vendor) {
                foreach ($vendor->drivers->driver as $driver) {
                    $driverName = (string) $driver['name'];
                    $driverScores[$driverName] = $numGlVersionExts !== 0 ? $lbGlVersion->getNumDriverExtsDone($driverName) / $numGlVersionExts : 0;
                }
            }

            $subsection['scores'] = $driverScores;

            foreach ($glSection->extension as $glExt) {
                $this->addExtension($subsection, $glExt, false, $vendors);
                foreach ($glExt->subextension as $glSubExt)
                    $this->addExtension($subsection, $glSubExt, true, $vendors);
            }
        }

        $section['subsections'][] = $subsection;
    }

    private function addExtension(array &$subsection, \SimpleXMLElement $glExt, $isSubExt, \SimpleXMLElement $vendors) {
        $extension = array(
            'name' => $glExt['name'],
        );

        $extUrlId = str_replace(' ', '_', $glExt['name']);
        $extUrlId = preg_replace('/[^A-Za-z0-9_]/', '', $extUrlId);
        $extUrlId = $subsection['target'].'_Extension_'.$extUrlId;
        $extension['target'] = $extUrlId;
        $extension['is_subext'] = $isSubExt;

        if (isset($glExt->link)) {
            $extension['url_href'] = $glExt->link['href'];
            $extension['url_text'] = $glExt->link;
        }

        $extTask = array();
        if ($glExt->mesa['status'] == \Mesamatrix\Parser\Constants::STATUS_DONE)
            $extTask['class'] = 'isDone';
        elseif ($glExt->mesa['status'] == \Mesamatrix\Parser\Constants::STATUS_NOT_STARTED)
            $extTask['class'] = 'isNotStarted';
        else
            $extTask['class'] = 'isInProgress';
        if (!empty($glExt->mesa->modified))
            $extTask['timestamp'] = (int) $glExt->mesa->modified->date;
        $extTask['hint'] = $glExt->mesa['hint'];
        $extension['tasks']['mesa'] = $extTask;

        foreach ($vendors->vendor as $vendor) {
            foreach ($vendor->drivers->driver as $driver) {
                $driverName = (string) $driver['name'];

                $extTask = array();
                $driverNode = $glExt->supported->{$driverName};
                if ($driverNode) {
                    $extTask['class'] = 'isDone';
                    $extTask['timestamp'] = (int) $driverNode->modified->date;
                }
                else {
                    $extTask['class'] = 'isNotStarted';
                }
                $extTask['hint'] = $driverNode['hint'];
                $extension['tasks'][$driverName] = $extTask;
            }
        }

        $subsection['extensions'][] = $extension;
    }

    public function writeMatrix() {
?>
            <table class="matrix">
<?php
// Colgroups.
foreach($this->matrix['column_groups'] as $colgroup):
    foreach($colgroup['columns'] as $colIdx):
        $col = $this->matrix['columns'][$colIdx];
        if ($col['type'] === 'driver'):
?>
                <colgroup class="hl">
<?php
        else:
?>
                <colgroup>
<?php
        endif;
    endforeach;
endforeach;

// Sections.
foreach($this->matrix['sections'] as $section):
?>
                <tr>
                    <td id="<?= $section['target'] ?>" colspan="<?= count($this->matrix['columns']) ?>" class="hCellGl">
                        <?= $section['name'] ?><a href="#<?= $section['target'] ?>" class="permalink">&para;</a>
                    </td>
                </tr>
<?php
    // Sub-sections.
    foreach($section['subsections'] as $subsection):
?>
                <tr>
                    <td id="<?= $subsection['target'] ?>" colspan="<?= count($this->matrix['columns']) ?>" class="hCellGlVersion">
<?php
        if (!empty($subsection['name'])):
?>
                        <?= $subsection['name'] ?><a href="#<?= $subsection['target'] ?>" class="permalink">&para;</a>
<?php
        endif;
?>
                    </td>
                </tr>
                <tr>
<?php
        // Header (vendors).
        foreach($this->matrix['column_groups'] as $colgroup):
            if (empty($colgroup['name'])):
?>
                    <td></td>
<?php
            else:
?>
                    <td colspan="<?= count($colgroup['columns']) ?>" class="hCellHeader hCellVendor-<?= $colgroup['vendor_class'] ?>"><?= $colgroup['name'] ?></td>
<?php
            endif;
        endforeach;
?>
                </tr>
                <tr>
<?php
        // Header (drivers).
        foreach($this->matrix['columns'] as $col):
            if ($col['type'] === 'extension'):
?>
                    <td class="hCellHeader hCellVendor-default"><?= $col['name'] ?></td>
<?php
            elseif ($col['type'] === 'driver'):
?>
                    <td class="hCellHeader hCellVendor-<?= $col['vendor_class'] ?>"><?= $col['name'] ?></td>
<?php
            elseif ($col['type'] === 'separator'):
?>
                    <td class="hCellSep"></td>
<?php
            else:
?>
                    <td><?= $col['name'] ?></td>
<?php
            endif;
        endforeach;
?>
                </tr>
                <tr>
<?php
        // Scores.
        foreach($this->matrix['columns'] as $col):
            if ($col['type'] === 'driver'):
                $scoreStr = sprintf('%.1f', $subsection['scores'][$col['name']] * 100);
?>
                    <td class="hCellHeader hCellDriverScore" data-score="<?= $scoreStr ?>"><?= $scoreStr ?>%</td>
<?php
            else:
?>
                    <td></td>
<?php
            endif;
        endforeach;
?>
                </tr>
<?php
        // Extensions.
        foreach ($subsection['extensions'] as $extension):
?>
                <tr class="extension">
<?php
            foreach($this->matrix['columns'] as $col):
                if ($col['type'] === 'extension'):
                    $extNameText = $extension['name'];
                    if (isset($extension['url_text'])):
                        $extNameText = str_replace($extension['url_text'], '<a href="'.$extension['url_href'].'">'.$extension['url_text'].'</a>', $extNameText);
                    endif;
                    $cssClass = '';
                    if ($extension['is_subext']):
                        $cssClass = ' class="extension-child"';
                    endif;
?>
                    <td id="<?= $extension['target'] ?>"<?= $cssClass ?>>
                        <?= $extNameText ?><a href="#<?= $extension['target'] ?>" class="permalink">&para;</a>
                    </td>
<?php
                elseif ($col['type'] === 'driver'):
                    $driverTask = $extension['tasks'][$col['name']];
                    $cssClasses = array($driverTask['class']);
                    $title = '';
                    if (isset($driverTask['hint'])):
                        $cssClasses[] = 'footnote';
                        $title = ' title="'.$driverTask['hint'].'"';
                    endif;
                    if (isset($driverTask['timestamp'])):
?>
                    <td class="task <?= join(' ', $cssClasses) ?>"<?= $title ?>>
                        <span data-timestamp="<?= $driverTask['timestamp'] ?>"><?= date('Y-m-d', $driverTask['timestamp']) ?></span>
                    </td>
<?php
                    else:
?>
                    <td class="task <?= $driverTask['class'] ?>"></td>
<?php
                    endif;
                else:
?>
                    <td></td>
<?php
                endif;
            endforeach;
?>
                </tr>
<?php
        endforeach;
    endforeach;
endforeach;
?>
            </table>
<?php
    }
};
