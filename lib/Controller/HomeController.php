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
use Mesamatrix\Parser\Constants;
use Mesamatrix\Leaderboard\Leaderboard;

class HomeController extends BaseController
{
    private $commits = array();
    private $apiControllers = array();

    public function __construct()
    {
        parent::__construct();

        $this->setPage('Home');
        $this->apiControllers[] = new ApiSubController(Constants::GL_NAME, true);
        $this->apiControllers[] = new ApiSubController(Constants::GLES_NAME, true);
        $this->apiControllers[] = new ApiSubController(Constants::GL_OR_ES_EXTRA_NAME, false);
        $this->apiControllers[] = new ApiSubController(Constants::VK_NAME, true);
        $this->apiControllers[] = new ApiSubController(Constants::VK_EXTRA_NAME, false);
        $this->apiControllers[] = new ApiSubController(Constants::OPENCL_NAME, true);
        $this->apiControllers[] = new ApiSubController(Constants::OPENCL_EXTRA_NAME, false);
        $this->apiControllers[] = new ApiSubController(Constants::OPENCL_VENDOR_SPECIFIC_NAME, false);

        $this->addCssScript('css/tipsy.css');

        $this->addJsScript('js/jquery.tipsy.js');
        $this->addJsScript('js/script.js');
    }

    protected function computeRendering()
    {
        $xml = $this->loadMesamatrixXml();

        $this->createCommitsModel($xml);

        foreach ($this->apiControllers as $apiController) {
            $apiController->prepare();
        }
    }

    private function loadMesamatrixXml()
    {
        $featuresXmlFilepath = Mesamatrix::path(Mesamatrix::$config->getValue('info', 'xml_file'));
        $xml = simplexml_load_file($featuresXmlFilepath);
        if (!$xml) {
            Mesamatrix::$logger->critical('Can\'t read ' . $featuresXmlFilepath);
            exit();
        }

        return $xml;
    }

    private function createCommitsModel(\SimpleXMLElement $xml)
    {
        $this->commits = array();

        $numCommits = Mesamatrix::$config->getValue('info', 'commitlog_length', 10);
        $numCommits = min($numCommits, $xml->commits->commit->count());
        for ($i = 0; $i < $numCommits; ++$i) {
            $xmlCommit = $xml->commits->commit[$i];
            $this->commits[] = array(
                'url' => Mesamatrix::$config->getValue('git', 'mesa_commit_url') . $xmlCommit['hash'],
                'timestamp' => (int) $xmlCommit['timestamp'],
                'subject' => $xmlCommit['subject']
            );
        }
    }

    private function createLeaderboard(\SimpleXMLElement $xml, array $apis)
    {
        $leaderboard = new Leaderboard();
        $leaderboard->load($xml, $apis);
        return $leaderboard;
    }

    protected function writeHtmlPage()
    {
        $mesaWeb = Mesamatrix::$config->getValue('git', 'mesa_web');
        $mesaBranch = Mesamatrix::$config->getValue('git', 'branch');
        $projectUrl = Mesamatrix::$config->getValue('info', 'project_url');

        echo <<<HTML
    <p>
        This page is a graphical representation of the text file
        <a href="{$mesaWeb}/blob/$mesaBranch/docs/features.txt" target="_blank">docs/features.txt</a>
        from the Mesa repository.
    </p>
    <p>
        Although this text file is updated by the Mesa developers themselves, it might not contain an exhaustive list
        of all the drivers features and subtleties. So, for more information, it is advised to look at the
        <a href="{$mesaWeb}" target="_blank">source code</a>, or ask the developers on their
        <a href="https://mesa3d.org/lists.html" target="_blank">mailing-list</a>.
    </p>
    <p>
        Feel free to open an issue or create a PR on <a href="{$projectUrl}" target="_blank">GitHub</a>, or join the
        Matrix room <a href="https://matrix.to/#/%23mesamatrix:matrix.org" target="_blank">#mesamatrix:matrix.org</a>.
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
HTML;

        // Commit list.
        foreach ($this->commits as $commit) :
            $dateRfc = date(DATE_RFC2822, $commit['timestamp']);
            $dateHumanReadable = date('Y-m-d H:i', $commit['timestamp']);

            echo <<<HTML
            <tr>
                <td class="commitsAge toRelativeDate" data-timestamp="{$dateRfc}">{$dateHumanReadable}</td>
                <td><a href="{$commit['url']}">{$commit['subject']}</a></td>
            </tr>
HTML;
        endforeach;

        echo <<<HTML
            <tr>
                <td colspan="2">
                    <noscript>(Dates are UTC)<br/></noscript>
                    <a href="{$mesaWeb}/-/commits/$mesaBranch/docs/features.txt">More...</a>
                </td>
            </tr>
        </tbody>
    </table>
HTML;

        // APIs matrices.
        foreach ($this->apiControllers as $apiController) {
            $apiController->writeMatrix();
        }
    }
}
