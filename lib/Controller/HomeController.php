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

class HomeController extends BaseController
{
    private $commits = array();
    private $openGLController = null;
    private $vulkanController = null;
    private $lastUpdatedTime = 0;

    public function __construct() {
        parent::__construct();

        $this->setPage('Home');
        $this->openGLController = new ApiSubController();
        $this->vulkanController = new ApiSubController();

        $this->addCssScript('css/tipsy.css');

        $this->addJsScript('js/jquery.tipsy.js');
        $this->addJsScript('js/script.js');
    }

    protected function computeRendering() {
        $xml = $this->loadMesamatrixXml();

        $this->createCommitsModel($xml);

        $apis = [ 'OpenGL', 'OpenGL ES', \Mesamatrix\Parser\Constants::GL_OR_ES_EXTRA_NAME ];
        $this->openGLController->setApis($apis);
        $this->openGLController->prepare();

        $apis = [ 'Vulkan', \Mesamatrix\Parser\Constants::VK_EXTRA_NAME ];
        $this->vulkanController->setApis($apis);
        $this->vulkanController->prepare();

        $this->lastUpdatedTime = $this->openGLController->getLastUpdatedTime();
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

    private function createCommitsModel(\SimpleXMLElement $xml) {
        $this->commits = array();

        $numCommits = \Mesamatrix::$config->getValue('info', 'commitlog_length', 10);
        $numCommits = min($numCommits, $xml->commits->commit->count());
        for ($i = 0; $i < $numCommits; ++$i) {
            $xmlCommit = $xml->commits->commit[$i];
            $this->commits[] = array(
                'url' => \Mesamatrix::$config->getValue('git', 'mesa_web').'/commit/?id='.$xmlCommit['hash'],
                'timestamp' => (int) $xmlCommit['timestamp'],
                'subject' => $xmlCommit['subject']
            );
        }
    }

    private function createLeaderboard(\SimpleXMLElement $xml, array $apis) {
        $leaderboard = new \Mesamatrix\Leaderboard();
        $leaderboard->load($xml, $apis);
        return $leaderboard;
    }

    private function writeLeaderboard($api, $leaderboard) {
        $driversExtsDone = $leaderboard->getDriversSortedByExtsDone();
        $numTotalExts = $leaderboard->getNumTotalExts();
?>
            <!--<h2><?= $api ?></h2>-->
            <p>There is a total of <strong><?= $numTotalExts ?></strong> extensions to implement.
            The ranking is based on the number of extensions done by driver. </p>
            <table class="lb">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Driver</th>
                        <th>Extensions</th>
                        <th>OpenGL</th>
                        <th>OpenGL ES</th>
                    </tr>
                </thead>
                <tbody>
<?php
        $index = 1;
        $rank = 1;
        $prevNumExtsDone = -1;
        foreach($driversExtsDone as $drivername => $numExtsDone) {
            $sameRank = $prevNumExtsDone === $numExtsDone;
            if (!$sameRank) {
                $rank = $index;
            }
            switch ($rank) {
            case 1: $rankClass = "lbCol-1st"; break;
            case 2: $rankClass = "lbCol-2nd"; break;
            case 3: $rankClass = "lbCol-3rd"; break;
            default: $rankClass = "";
            }
            $pctScore = sprintf("%.1f%%", $numExtsDone / $numTotalExts * 100);
            $openglVersion = $leaderboard->getDriverGlVersion($drivername);
            if ($openglVersion === NULL) {
                $openglVersion = "N/A";
            }
            $openglesVersion = $leaderboard->getDriverGlesVersion($drivername);
            if ($openglesVersion === NULL) {
                $openglesVersion = "N/A";
            }
?>
                    <tr class="<?= $rankClass ?>">
                        <th class="lbCol-rank"><?= !$sameRank ? $rank : "" ?></th>
                        <td class="lbCol-driver"><?= $drivername ?></td>
                        <td class="lbCol-score"><span class="lbCol-pctScore">(<?= $pctScore ?>)</span> <?= $numExtsDone ?></td>
                        <td class="lbCol-version"><?= $openglVersion ?></td>
                        <td class="lbCol-version"><?= $openglesVersion ?></td>
                    </tr>
<?php
            $prevNumExtsDone = $numExtsDone;
            $index++;
        }
?>
                </tbody>
            </table>
<?php
    }

    protected function writeHtmlPage() {
?>
    <p>
        This page is a graphical representation of the text file <a href="https://cgit.freedesktop.org/mesa/mesa/tree/docs/features.txt">docs/features.txt</a> from the Mesa repository.
    </p>
    <p>
        Although this text file is updated by the Mesa developers themselves, it might not contain an exhaustive list of all the drivers features and subtleties. So, for more information, it is advised to look at the source code, or ask the developers on the mailing-list.
    </p>

    <div class="stats">
        <div class="stats-commits">
            <h1>Last commits</h1>
            <table class="commits">
                <thead>
                    <tr>
                        <th>Age</th>
                        <th>Commit message</th>
                    </tr>
                </thead>
                <tbody>
<?php
// Commit list.
foreach ($this->commits as $commit):
?>
                    <tr>
                        <td class="commitsAge toRelativeDate" data-timestamp="<?= date(DATE_RFC2822, $commit['timestamp']) ?>"><?= date('Y-m-d H:i', $commit['timestamp']) ?></td>
                        <td><a href="<?= $commit['url'] ?>"><?= $commit['subject'] ?></a></td>
                    </tr>
<?php
endforeach;
?>
                    <tr>
                        <td colspan="2">
                            <noscript>(Dates are UTC)<br/></noscript>
                            <a href="<?= \Mesamatrix::$config->getValue("git", "mesa_web")."/log/docs/features.txt" ?>">More...</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="stats-lb">
            <h1>Leaderboard</h1>
<?php
$this->writeLeaderboard('OpenGL', $this->openGLController->getLeaderboard());
//$this->writeLeaderboard('Vulkan', $this->vulkanController->getLeaderboard());
?>
        </div>

    </div>
<?php
$this->openGLController->writeMatrix();
$this->vulkanController->writeMatrix();
?>
    <p><b>Last time features.txt was parsed:</b> <span class="toLocalDate" data-timestamp="<?= date(DATE_RFC2822, $this->lastUpdatedTime) ?>"><?= date('Y-m-d H:i O', $this->lastUpdatedTime) ?></span>.</p>
<?php
    }
};
