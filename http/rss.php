<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2020 Romain "Creak" Failliot.
 * Copyright (C) 2015 Robin McCorkell <rmccorkell@karoshi.org.uk>
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

require_once "../lib/base.php";

use Mesamatrix\Mesamatrix;
use Symfony\Component\HttpFoundation\Response as HTTPResponse;
use Suin\RSSWriter\Feed as RSSFeed;
use Suin\RSSWriter\Channel as RSSChannel;
use Suin\RSSWriter\Item as RSSItem;

function rssGenerationNeeded(string $featuresXmlFilepath, string $rssFilepath)
{
    if (file_exists($featuresXmlFilepath) && file_exists($rssFilepath))
    {
        $lastCommitFilepath = Mesamatrix::path(Mesamatrix::$config->getValue('info', 'private_dir', 'private'))
            . '/last_commit_parsed';
        if (file_exists($lastCommitFilepath))
        {
            // Generate RSS if file is older than last_commit_parsed modification time.
            return filemtime($rssFilepath) < filemtime($lastCommitFilepath);
        }
    }

    return true;
}

function generateRss(string $featuresXmlFilepath)
{
    $xml = simplexml_load_file($featuresXmlFilepath);
    if (!$xml) {
        Mesamatrix::$logger->critical("Can't read ".$featuresXmlFilepath);
        exit();
    }

    // Get the time of the last commit and substract a year.
    $minTime = 0;
    foreach ($xml->commits->commit as $commit) {
        $minTime = max($minTime, (int)$commit["timestamp"]);
    }
    $minTime = $minTime - (60 * 60 * 24 * 365);

    // prepare RSS
    $rss = new RSSFeed();

    $baseUrl = Mesamatrix::$request->getSchemeAndHttpHost()
        . Mesamatrix::$request->getBasePath();

    $channel = new RSSChannel();
    $channel
        ->title(Mesamatrix::$config->getValue("info", "title"))
        ->description(Mesamatrix::$config->getValue("info", "description"))
        ->url($baseUrl)
        ->appendTo($rss);

    //$commitWeb = Mesamatrix::$config->getValue("git", "mesa_commit_url");

    foreach ($xml->commits->commit as $commit) {
        if ((int)$commit["timestamp"] < $minTime)
            continue;

        $description = (string)$commit;
        $description = str_replace('<pre>', '<pre style="white-space: pre-wrap;">', $description);
        $lines = explode("\n", $description);
        $lines = preg_replace('/^\+.*$/', '<span style="color: green">$0</span>', $lines);
        $lines = preg_replace('/^-.*$/', '<span style="color: red">$0</span>', $lines);
        $description = implode("\n", $lines);

        $item = new RSSItem();
        $item
            ->preferCdata(true)
            ->title((string)$commit["subject"])
            ->description($description)
            //->url($commitWeb . $commit["hash"])
            ->url($baseUrl . '?commit=' . $commit["hash"])
            ->pubDate((int)$commit["timestamp"])
            ->appendTo($channel);
    }

    return $rss->render();
}

$featuresXmlFilepath = Mesamatrix::path(Mesamatrix::$config->getValue("info", "xml_file"));
$rssFilepath = Mesamatrix::path(Mesamatrix::$config->getValue('info', 'private_dir', 'private'))
    . '/rss.xml';

$mustGenerateRss = rssGenerationNeeded($featuresXmlFilepath, $rssFilepath);

$rssContents = null;
if ($mustGenerateRss)
{
    // Generate RSS.
    $rssContents = generateRss($featuresXmlFilepath);

    // Write to file.
    $h = fopen($rssFilepath, "w");
    if ($h !== false) {
        fwrite($h, $rssContents);
        fclose($h);
    }

    Mesamatrix::$logger->info('RSS file generated.');
}
else
{
    // Read from file.
    $rssContents = file_get_contents($rssFilepath);
}

// Send response.
$response = new HTTPResponse(
    $rssContents,
    HTTPResponse::HTTP_OK,
    ['Content-Type' => 'text/xml']
);

$response->prepare(Mesamatrix::$request);
$response->send();
