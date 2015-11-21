<?php
/*
 * This file is part of mesamatrix.
 *
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

use \Symfony\Component\HttpFoundation\Response as HTTPResponse;
use \Suin\RSSWriter\Feed as RSSFeed;
use \Suin\RSSWriter\Channel as RSSChannel;
use \Suin\RSSWriter\Item as RSSItem;

$gl3Path = \Mesamatrix::path(\Mesamatrix::$config->getValue("info", "xml_file"));
$xml = simplexml_load_file($gl3Path);
if (!$xml) {
    \Mesamatrix::$logger->critical("Can't read ".$gl3Path);
    exit();
}

$baseUrl = \Mesamatrix::$request->getSchemeAndHttpHost()
    . \Mesamatrix::$request->getBasePath();

// prepare RSS
$rss = new RSSFeed();

$channel = new RSSChannel();
$channel
    ->title(\Mesamatrix::$config->getValue("info", "title"))
    ->description(\Mesamatrix::$config->getValue("info", "description"))
    ->url($baseUrl)
    ->appendTo($rss);

$commitWeb = \Mesamatrix::$config->getValue("git", "mesa_web") . "/commit/" 
    . \Mesamatrix::$config->getValue("git", "gl3") . "?id=";

foreach ($xml->commits->commit as $commit) {
    $item = new RSSItem();
    $item
        ->title((string)$commit["subject"])
        ->description((string)$commit)
        //->url($commitWeb . $commit["hash"])
        ->url($baseUrl . '?commit=' . $commit["hash"])
        ->pubDate((int)$commit["timestamp"])
        ->appendTo($channel);
}

// send response
$response = new HTTPResponse(
    $rss,
    HTTPResponse::HTTP_OK,
    ['Content-Type' => 'text/xml']
);

$response->prepare(\Mesamatrix::$request);
$response->send();

