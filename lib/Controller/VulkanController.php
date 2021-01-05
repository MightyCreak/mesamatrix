<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014-2020 Romain "Creak" Failliot.
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

class VulkanController extends ApiSubController
{
    protected function writeLeaderboard()
    {
        $leaderboard = $this->getLeaderboard();
        $driversExtsDone = $leaderboard->getDriversSortedByExtsDone("Vulkan");
        $numTotalExts = $leaderboard->getNumTotalExts();
?>
            <p>There is a total of <strong><?= $numTotalExts ?></strong> extensions to implement.
            The ranking is based on the number of extensions done by driver. </p>
            <table class="lb">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Driver</th>
                        <th>Extensions</th>
                        <th>Vulkan</th>
                    </tr>
                </thead>
                <tbody>
<?php
        $index = 1;
        $rank = 1;
        $prevNumExtsDone = -1;
        foreach($driversExtsDone as $drivername => $driverScore) {
            $numExtsDone = $driverScore->getNumExtensionsDone();
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
            $pctScore = sprintf("%.1f%%", $driverScore->getScore() * 100);
            $vulkanVersion = $driverScore->getApiVersion();
            if ($vulkanVersion === NULL) {
                $vulkanVersion = "N/A";
            }
?>
                    <tr class="<?= $rankClass ?>">
                        <th class="lbCol-rank"><?= !$sameRank ? $rank : "" ?></th>
                        <td class="lbCol-driver"><?= $drivername ?></td>
                        <td class="lbCol-score"><span class="lbCol-pctScore">(<?= $pctScore ?>)</span> <?= $numExtsDone ?></td>
                        <td class="lbCol-version"><?= $vulkanVersion ?></td>
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
};
