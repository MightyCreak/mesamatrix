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

class UrlCache
{
    const EXPIRATIONDELAY = 7776000; // 90 * 24 * 60 * 60 = 90 days.

    /**
     * Default constructor.
     */
    public function __construct() {
        $this->instanceTime = time();
        $this->cachedUrls = array();
    }

    /**
     * Load the URL cache file.
     *
     * This file is encoded in JSON.
     */
    public function load() {
        $privateDir = \Mesamatrix::$config->getValue("info", "private_dir");
        $filepath = $privateDir.'/'.\Mesamatrix::$config->getValue("extension_links", "cache_file", "urlcache.json");
        if (file_exists($filepath) !== FALSE) {
            $urlCacheContents = file_get_contents($filepath);
            if ($urlCacheContents !== FALSE) {
                $this->cachedUrls = json_decode($urlCacheContents, true);
                \Mesamatrix::$logger->info("URL cache file loaded.");
            }
        }
    }

    /**
     * Save the URL cache file.
     *
     * This file is encoded in JSON.
     */
    public function save() {
        $privateDir = \Mesamatrix::$config->getValue("info", "private_dir", "private");
        if (file_exists($privateDir) === FALSE)
            mkdir($privateDir, 0777, true);
        $filepath = $privateDir.'/'.\Mesamatrix::$config->getValue("extension_links", "cache_file", "urlcache.json");
        $res = file_put_contents($filepath, json_encode($this->cachedUrls));
        if ($res !== FALSE)
            \Mesamatrix::$logger->info("URL cache file saved: ".$filepath.".");
        else
            \Mesamatrix::$logger->error("Can't save URL cache file in \"".$filepath."\".");
    }

    /**
     * Return if the URL is valid or not.
     *
     * Also update the URL in the database if needed.
     *
     * @param string $url URL to test.
     */
    public function isValid($url) {
        if ($this->needCheck($url))
            $this->updateUrl($url);
        return $this->cachedUrls[$url]['is_valid'];
    }

    /**
     * Get if an url need to be checked or not.
     *
     * @param string $url URL to test.
     * @return boolean Whether or not the given URL need to be checked again.
     */
    private function needCheck($url) {
        $urlKnown = array_key_exists($url, $this->cachedUrls);
        return !$urlKnown || $this->cachedUrls[$url]['expiration_date'] < $this->instanceTime;
    }

    /**
     * Update the URL in the database.
     *
     * @param string $url URL to update.
     */
    private function updateUrl($url) {
        $urlHeader = get_headers($url);
        $isValid = FALSE;
        if ($urlHeader !== FALSE) {
            \Mesamatrix::$logger->info("Try URL \"".$url."\". Result: \"".$urlHeader[0]."\".");
            $isValid = $urlHeader[0] === "HTTP/1.1 200 OK";
        }

        // Register URL's validity.
        $this->cachedUrls[$url] = array(
            'expiration_date' => $this->instanceTime + self::EXPIRATIONDELAY,
            'is_valid' => $isValid);
    }

    private $instanceTime;
    private $cachedUrls;
}
