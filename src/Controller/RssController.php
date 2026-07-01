<?php

declare(strict_types=1);

/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2026 Romain "Creak" Failliot.
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
use Suin\RSSWriter\Feed as RSSFeed;
use Suin\RSSWriter\Channel as RSSChannel;
use Suin\RSSWriter\Item as RSSItem;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class RssController
{
    private readonly HttpRequest $request;

    public function __construct()
    {
        $this->request = HttpRequest::createFromGlobals();
    }

    public function run(): void
    {
        $featuresXmlFilepath = Mesamatrix::path(Mesamatrix::$config->getValue("info", "xml_file"));
        $rssFilepath = Mesamatrix::path(Mesamatrix::$config->getValue('info', 'private_dir', 'private'))
            . '/rss.xml';

        $mustGenerateRss = $this->rssGenerationNeeded($featuresXmlFilepath, $rssFilepath);

        $rssContents = null;
        if ($mustGenerateRss) {
            // Generate RSS.
            $rssContents = $this->generateRss($featuresXmlFilepath);

            // Write to file.
            $h = fopen($rssFilepath, "w");
            if ($h !== false) {
                fwrite($h, $rssContents);
                fclose($h);
            }

            Mesamatrix::$logger->info('RSS file generated.');
        } else {
            // Read from file.
            $rssContents = file_get_contents($rssFilepath);
        }

        // Send response.
        $response = new HttpResponse(
            $rssContents,
            HttpResponse::HTTP_OK,
            ['Content-Type' => 'text/xml']
        );

        $response->prepare($this->request);
        $response->send();
    }

    private function rssGenerationNeeded(string $featuresXmlFilepath, string $rssFilepath): bool
    {
        if (file_exists($featuresXmlFilepath) && file_exists($rssFilepath)) {
            $lastCommitFilepath = Mesamatrix::path(Mesamatrix::$config->getValue('info', 'private_dir', 'private'))
                . '/last_commit_parsed';
            if (file_exists($lastCommitFilepath)) {
                // Generate RSS if file is older than last_commit_parsed modification time.
                return filemtime($rssFilepath) < filemtime($lastCommitFilepath);
            }
        }

        return true;
    }

    private function generateRss(string $featuresXmlFilepath): ?string
    {
        $xml = simplexml_load_file($featuresXmlFilepath);
        if (!$xml) {
            Mesamatrix::$logger->critical("Can't read " . $featuresXmlFilepath);
            return null;
        }

        // Get the time of the last commit and subtract 90 days.
        $minTime = 0;
        foreach ($xml->commits->commit as $commit) {
            $minTime = max($minTime, (int)$commit["timestamp"]);
        }
        $minTime = $minTime - (60 * 60 * 24 * 90);

        // prepare RSS
        $rss = new RSSFeed();

        $baseUrl = $this->request->getSchemeAndHttpHost()
            . $this->request->getBasePath();

        $channel = new RSSChannel();
        $channel
            ->title(Mesamatrix::$config->getValue("info", "title"))
            ->description(Mesamatrix::$config->getValue("info", "description"))
            ->url($baseUrl)
            ->appendTo($rss);

        foreach ($xml->commits->commit as $commit) {
            if ((int)$commit["timestamp"] < $minTime) {
                continue;
            }

            $commitUrl = $baseUrl . '?commit=' . $commit["hash"];

            $item = new RSSItem();
            $item
                ->title((string)$commit["subject"])
                ->description((string)$commit)
                ->url($commitUrl)
                ->pubDate((int)$commit["timestamp"])
                ->guid($commitUrl)
                ->preferCdata(true)
                ->appendTo($channel);
        }

        return $rss->render();
    }
}
