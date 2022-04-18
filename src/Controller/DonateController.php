<?php

/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014-2022 Romain "Creak" Failliot.
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

use DateTime;
use Mesamatrix\Mesamatrix;
use Mesamatrix\Donation\Contributor;
use Mesamatrix\Donation\YearContributors;

class DonateController extends BaseController
{
    private $yearsContributors = array();

    public function __construct()
    {
        parent::__construct();

        $this->setPage('Donate?');

        $this->addJsScript('js/script.js');
    }

    /**
     * Load contributors' donations.
     *
     * Sorted by year and by donation descending.
     */
    private function loadContributors()
    {
        $contribsPath = Mesamatrix::path(Mesamatrix::$config->getValue('info', 'private_dir') .
            "/contributors.json");
        if (!file_exists($contribsPath)) {
            return;
        }

        $contribsContents = file_get_contents($contribsPath);
        if ($contribsContents !== false) {
            $contribs = json_decode($contribsContents);

            foreach ($contribs as &$jsonContributor) {
                $date = new DateTime($jsonContributor->date);
                $year = $date->format("Y");

                $contributor = new Contributor();
                $contributor->name = $jsonContributor->name;
                $contributor->date = $date;
                $contributor->donation = $jsonContributor->donation;

                if (!array_key_exists($year, $this->yearsContributors)) {
                    $yearContributor = new YearContributors();
                    $yearContributor->year = $year;
                    $this->yearsContributors[$year] = $yearContributor;
                } else {
                    $yearContributor = $this->yearsContributors[$year];
                }

                $yearContributor->contributors[] = $contributor;
                $yearContributor->total += $contributor->donation;
            }

            // Add current year, if not there yet.
            $currentYear = (new DateTime())->format("Y");
            if (!array_key_exists($currentYear, $this->yearsContributors)) {
                $yearContributor = new YearContributors();
                $yearContributor->year = $currentYear;
                $this->yearsContributors[$currentYear] = $yearContributor;
            }

            // For each year, sort by donation desc.
            foreach ($this->yearsContributors as &$yearContributor) {
                $contributors = &$yearContributor->contributors;
                usort($contributors, function ($a, $b) {
                    return $b->donation - $a->donation;
                });
            }

            // Sort by year desc.
            krsort($this->yearsContributors);
        }
    }

    protected function computeRendering(): void
    {
        $this->loadContributors();
    }

    protected function writeHtmlPage(): void
    {
        echo <<<'HTML'
    <h1>Why make a donation?</h1>
    <p>I'm glad to distribute this small piece of software under the AGPL license and especially without any
    ads. Free software is all about sharing and I strongly believe it is the only way to address important
    subjects such as privacy or learning.</p>
    <p>Although software shouldn't cost a thing, hardware and services rightfully have a price tag. My
    server costs are CA$&nbsp;190/year and my goal is to cover these fees thanks to simple donations from you,
    glorious visitors.</p>
    <p>If you find the project interesting enough, please consider making a donation. Even a small one would
    mean the world to me. More than a mere financial act, donate means that you simply believe in this
    project and want it to be better.</p>

    <div class="contributor-container">
        <div class="contributor-item">
            <div class="contributor-list">
                <h2>Contributors <span class="subtitle">(PayPal)</span></h2>
HTML;

        $yearIdx = 0;
        foreach ($this->yearsContributors as $year => $yearsContributors) {
            if ($yearIdx < 3) {
                // Show year details.
                echo <<<HTML
                <h3>{$year}</h3>
HTML;

                if (empty($yearsContributors->contributors)) {
                    echo <<<'HTML'
                <p><i>No donation yet, be the first to donate!</i></p>
HTML;
                } else {
                    echo <<<'HTML'
                <table>
HTML;

                    $rank = 1;
                    foreach ($yearsContributors->contributors as $contributor) {
                        $contributorDonation = sprintf("%.2f", $contributor->donation);
                        echo <<<HTML
                    <tr>
                        <td class="contributor-rank">{$rank}.</td>
                        <td class="contributor-name">{$contributor->name}</td>
                        <td class="contributor-date">{$contributor->date->format("Y-m-d")}</td>
                        <td class="contributor-donation">{$contributorDonation}</td>
                    </tr>
HTML;

                        $rank++;
                    }

                    $totalYearDonations = sprintf("%.2f", $yearsContributors->total);

                    echo <<<HTML
                    <tr>
                        <td class="contributor-rank"></td>
                        <td class="contributor-name"></td>
                        <td class="contributor-total-label">Total:</td>
                        <td class="contributor-total-donation">CA$&nbsp;{$totalYearDonations}</td>
                    </tr>
                </table>
HTML;
                }
            } else {
                // Show year total.
                $totalYearDonations = sprintf("%.2f", $yearsContributors->total);

                echo <<<HTML
                <table>
                    <tr>
                        <td class="contributor-rank"><h3>{$year}</h3></td>
                        <td class="contributor-name"></td>
                        <td class="contributor-total-label">Total:</td>
                        <td class="contributor-total-donation">CA$&nbsp;{$totalYearDonations}</td>
                    </tr>
                </table>
HTML;
            }

            ++$yearIdx;
        }

        echo <<<'HTML'
            </div>
        </div>
        <div class="contributor-item">
            <div class="donate">
                <h2>Donate</h2>
                <p>By default your donation is anonymous, but if you like you can write your name onto the wall of fame!</p>
                <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                    <input type="hidden" name="cmd" value="_s-xclick"/>
                    <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----"/>
                    <div class="input-row">
                        <input type="hidden" name="on0" value="Name"/>
                        <label for="name">Name:</label>
                        <input type="text" name="os0" id="name" maxlength="200" placeholder="Anonymous"/>
                    </div>
                    <div class="input-row">
                        <input type="hidden" name="on1" value="Message"/>
                        <label for="msg">Message:</label>
                        <input type="text" name="os1" id="msg" maxlength="200" placeholder="You can leave me a message if you want"/>
                    </div>
                    <div class="input-row submit">
                        <input type="image" src="https://www.paypalobjects.com/webstatic/en_US/btn/btn_donate_pp_142x27.png" name="submit" alt="PayPal - The safer, easier way to pay online!"/>
                        <!--<img alt="" border="0" src="https://www.paypalobjects.com/fr_CA/i/scr/pixel.gif" width="1" height="1"/>-->
                    </div>
                </form>
                <p>If you'd like to have more choices, feel free to contact me at <i>romain.failliot [at] foolstep [dot] com</i>.</p>
            </div>
        </div>
    </div>
HTML;
    }
}
