<?php

// Default configuration for Mesamatrix.
// Values can be overwritten in a user defined config/config.php file.

use Monolog\Logger as Log;

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

$CONFIG = [
    "info" => [
        "log_level" => Log::WARNING,
        "version" => "3.0",
        "title" => "The Mesa drivers matrix",
        "description" => "Show Mesa progress for the OpenGL, OpenGL ES, Vulkan and OpenCL drivers implementations into an easy to read HTML page.",
        "xml_file" => "public/features.xml",
        "project_url" => "https://github.com/MightyCreak/mesamatrix",
        "commitlog_length" => 10,
        "authors" => array(
            // Either '$author' or '$author => $website'
            "Romain 'Creak' Failliot",
            "Tobias Droste",
            "Robin McCorkell",
        ),
        "private_dir" => "private",
    ],

    "extension_links" => [
        "enabled" => true,
        "cache_file" => "urlcache.json",
        "opengl_base_url" => "https://registry.khronos.org/OpenGL/extensions/",
        "vulkan_base_url" => "https://registry.khronos.org/vulkan/specs/latest/man/html/",
    ],

    "git" => [
        "mesa_web" => "https://gitlab.freedesktop.org/mesa/mesa",
        "mesa_url" => "https://gitlab.freedesktop.org/mesa/mesa.git",
        "mesa_commit_url" => "https://gitlab.freedesktop.org/mesa/mesa/commit/",
        "mesa_dir" => "mesa.git",
        "branch" => "main",
        "filepaths" => [
            [
                "name" => "docs/features.txt",
                "excluded_commits" => []
            ]
        ],
    ],
];
