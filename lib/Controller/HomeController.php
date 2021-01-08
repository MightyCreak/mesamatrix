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

class HomeController extends BaseController
{
    private $commits = array();
    private $apiControllers = array();
    private $lastUpdatedTime = 0;

    public function __construct() {
        parent::__construct();

        $this->setPage('Home');
        $this->apiControllers[] = new ApiSubController('OpenGL', true);
        $this->apiControllers[] = new ApiSubController('OpenGL ES', true);
        $this->apiControllers[] = new ApiSubController(\Mesamatrix\Parser\Constants::GL_OR_ES_EXTRA_NAME, false);
        $this->apiControllers[] = new ApiSubController('Vulkan', true);
        $this->apiControllers[] = new ApiSubController(\Mesamatrix\Parser\Constants::VK_EXTRA_NAME, false);

        $this->addCssScript('css/tipsy.css');

        $this->addJsScript('js/jquery.tipsy.js');
        $this->addJsScript('js/script.js');
    }

    protected function computeRendering() {
        $xml = $this->loadMesamatrixXml();

        $this->createCommitsModel($xml);

        foreach ($this->apiControllers as $apiController) {
            $apiController->prepare();
        }

        $this->lastUpdatedTime = $this->apiControllers[0]->getLastUpdatedTime();
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
                'url' => \Mesamatrix::$config->getValue('git', 'mesa_commit_url').$xmlCommit['hash'],
                'timestamp' => (int) $xmlCommit['timestamp'],
                'subject' => $xmlCommit['subject']
            );
        }
    }

    private function createLeaderboard(\SimpleXMLElement $xml, array $apis) {
        $leaderboard = new \Mesamatrix\Leaderboard\Leaderboard();
        $leaderboard->load($xml, $apis);
        return $leaderboard;
    }

    protected function writeHtmlPage() {
?>
    <p>
        This page is a graphical representation of the text file <a href="<?= \Mesamatrix::$config->getValue('git', 'mesa_web') ?>/blob/master/docs/features.txt" target="_blank">docs/features.txt</a> from the Mesa repository.
    </p>
    <p>
        Although this text file is updated by the Mesa developers themselves, it might not contain an exhaustive list of all the drivers features and subtleties. So, for more information, it is advised to look at the <a href="<?= \Mesamatrix::$config->getValue('git', 'mesa_web') ?>" target="_blank">source code</a>, or ask the developers on their <a href="https://mesa3d.org/lists.html" target="_blank">mailing-list</a>.
    </p>
    <p>
        Feel free to open an issue or create a PR on <a href="<?= \Mesamatrix::$config->getValue('info', 'project_url') ?>" target="_blank">GitHub</a>, or join the Matrix room <a href="https://matrix.to/#/#mesamatrix:matrix.org" target="_blank">#mesamatrix:matrix.org</a>.
    </p>

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
                    <a href="<?= \Mesamatrix::$config->getValue("git", "mesa_web")."/-/commits/master/docs/features.txt" ?>">More...</a>
                </td>
            </tr>
        </tbody>
    </table>
<?php
        // APIs matrices.
        foreach ($this->apiControllers as $apiController) {
            $apiController->writeMatrix();
        }
?>
    <p>Last time changes were detected in <a href="<?= \Mesamatrix::$config->getValue('git', 'mesa_web') ?>/blob/master/docs/features.txt" target="_blank">features.txt</a>: <span class="toLocalDate" data-timestamp="<?= date(DATE_RFC2822, $this->lastUpdatedTime) ?>"><?= date('Y-m-d H:i O', $this->lastUpdatedTime) ?></span>.</p>
<?php
    }
};
