<?php

/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014 Romain "Creak" Failliot.
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

namespace Mesamatrix\Parser;

use Mesamatrix\Mesamatrix;

class UrlCache
{
    private const EXPIRATION_DELAY = 7776000; // 90 * 24 * 60 * 60 = 90 days.
    private const RE_VALID_HTTP_RESPONSE = '/^HTTP\/[^ ]+? 2[0-9]{2}/';

    /**
     * Default constructor.
     */
    public function __construct()
    {
        $this->instanceTime = time();
        $this->cachedUrls = array();
    }

    /**
     * Load the URL cache file.
     *
     * This file is encoded in JSON.
     */
    public function load(): void
    {
        $privateDir = Mesamatrix::$config->getValue("info", "private_dir");
        $filepath = $privateDir . '/' . Mesamatrix::$config->getValue("extension_links", "cache_file", "urlcache.json");
        if (file_exists($filepath) !== false) {
            $urlCacheContents = file_get_contents($filepath);
            if ($urlCacheContents !== false) {
                $this->cachedUrls = json_decode($urlCacheContents, true);
                Mesamatrix::$logger->info("URL cache file loaded.");
            }
        }
    }

    /**
     * Save the URL cache file.
     *
     * This file is encoded in JSON.
     */
    public function save(): void
    {
        $privateDir = Mesamatrix::$config->getValue("info", "private_dir", "private");
        if (file_exists($privateDir) === false) {
            mkdir($privateDir, 0777, true);
        }
        $filepath = $privateDir . '/' . Mesamatrix::$config->getValue("extension_links", "cache_file", "urlcache.json");
        $res = file_put_contents($filepath, json_encode($this->cachedUrls));
        if ($res !== false) {
            Mesamatrix::$logger->info("URL cache file saved: " . $filepath . ".");
        } else {
            Mesamatrix::$logger->error("Can't save URL cache file in \"" . $filepath . "\".");
        }
    }

    /**
     * Test the URL and returns if it is valid or not.
     *
     * Also update the URL in the database if needed.
     *
     * @param string $url URL to test.
     */
    public function testUrl(string $url): bool
    {
        if ($this->needCheck($url)) {
            $this->updateUrl($url);
        }
        return $this->cachedUrls[$url]['is_valid'];
    }

    /**
     * Get if an url need to be checked or not.
     *
     * @param string $url URL to test.
     * @return boolean Whether or not the given URL need to be checked again.
     */
    private function needCheck(string $url): bool
    {
        $urlKnown = array_key_exists($url, $this->cachedUrls);
        return !$urlKnown || $this->cachedUrls[$url]['expiration_date'] < $this->instanceTime;
    }

    /**
     * Update the URL in the database.
     *
     * @param string $url URL to update.
     */
    private function updateUrl(string $url): void
    {
        $urlHeader = get_headers($url);
        $valid = false;
        if ($urlHeader !== false) {
            $response = $urlHeader[0];
            $valid = $this->isValidResponse($response);
            if ($valid) {
                Mesamatrix::$logger->info("URL \"{$url}\" is valid");
            } else {
                Mesamatrix::$logger->warning("URL \"{$url}\" is invalid");
            }
        }

        // Register URL's validity.
        $this->cachedUrls[$url] = array(
            'expiration_date' => $this->instanceTime + self::EXPIRATION_DELAY,
            'is_valid' => $valid);
    }

    /**
     * Get if the HTTP response is valid or not.
     *
     * @param string $response HTTP response.
     */
    public function isValidResponse(string $response): bool
    {
        return preg_match(self::RE_VALID_HTTP_RESPONSE, $response) !== 0;
    }

    private $instanceTime;
    private $cachedUrls;
}
