<?php

// Default configuration for MesaMatrix
// Copy to config/config.php for use

use \Monolog\Logger as Log;
/* available log levels:
 * Log::EMERGENCY
 * Log::ALERT
 * Log::CRITICAL
 * Log::ERROR
 * Log::WARNING (default)
 * Log::NOTICE
 * Log::INFO
 * Log::DEBUG
 */

$CONFIG = array(
    "info" => array(
        "log_level" => Log::WARNING,
        "version" => "1.0",
        "title" => "The OpenGL vs Mesa matrix",
        "description" => "Show Mesa progress for the OpenGL implementation into an easy to read HTML page.",
        "xml_file" => "http/gl3.xml",
        "project_url" => "https://github.com/MightyCreak/mesamatrix",
        // Either '$author' or '$author => $website'
        "authors" => array(
            "Romain 'Creak' Failliot",
            "Tobias Droste",
            "Robin McCorkell" => "mailto:rmccorkell@karoshi.org.uk",
        ),
        "private_dir" => "private",
    ),

    "opengl_links" => array(
        "enabled" => TRUE,
        "url" => "https://www.opengl.org/registry/specs/",
        "cache_file" => "urlcache.json",
    ),

    "flattr" => array(
        "enabled" => FALSE,
        "id" => "your_flattr_id",
        "language" => "en_US",
        "tags" => "mesa,opengl",
    ),

    "git" => array(
        "mesa_web" => "http://cgit.freedesktop.org/mesa/mesa",
        "mesa_url" => "git://anongit.freedesktop.org/mesa/mesa",
        "mesa_dir" => "mesa.git",

        "depth" => 6000,
        // oldest_commit is based on parser compatibility
        "oldest_commit" => "b6ab52b7f941b689753d4b9af7d58083e6917fd6",
        "commitparser_depth" => 10,

        "gl3" => "docs/GL3.txt",
    ),
);
