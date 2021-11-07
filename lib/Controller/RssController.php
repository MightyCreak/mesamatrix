<?php

namespace Mesamatrix\Controller;

use Mesamatrix\Mesamatrix;
use Symfony\Component\HttpFoundation\Response as HTTPResponse;
use Suin\RSSWriter\Feed as RSSFeed;
use Suin\RSSWriter\Channel as RSSChannel;
use Suin\RSSWriter\Item as RSSItem;

class RssController
{
    public function run()
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
        $response = new HTTPResponse(
            $rssContents,
            HTTPResponse::HTTP_OK,
            ['Content-Type' => 'text/xml']
        );

        $response->prepare(Mesamatrix::$request);
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

    private function generateRss(string $featuresXmlFilepath)
    {
        $xml = simplexml_load_file($featuresXmlFilepath);
        if (!$xml) {
            Mesamatrix::$logger->critical("Can't read " . $featuresXmlFilepath);
            return null;
        }

        // Get the time of the last commit and subtract a year.
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
            if ((int)$commit["timestamp"] < $minTime) {
                continue;
            }

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
}
