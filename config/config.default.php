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
        "version" => "3.0",
        "title" => "The Mesa drivers matrix",
        "description" => "Show Mesa progress for the OpenGL, OpenGL ES, Vulkan and OpenCL drivers implementations into an easy to read HTML page.",
        "xml_file" => "http/features.xml",
        "project_url" => "https://github.com/MightyCreak/mesamatrix",
        "commitlog_length" => 10,
        "authors" => array(
            // Either '$author' or '$author => $website'
            "Romain 'Creak' Failliot",
            "Tobias Droste",
            "Robin McCorkell",
        ),
        "private_dir" => "private",
    ),

    "opengl_links" => array(
        "enabled" => TRUE,
        "url_gl" => "https://www.khronos.org/registry/OpenGL/extensions/",
        "cache_file" => "urlcache.json",
    ),

    "vulkan_links" => array(
        "enabled" => TRUE,
        "url_vk" => "https://raw.githubusercontent.com/KhronosGroup/Vulkan-Docs/main/appendices/",
        "cache_file" => "urlcache.json",
    ),

    "git" => array(
        "mesa_web" => "https://gitlab.freedesktop.org/mesa/mesa",
        "mesa_url" => "https://gitlab.freedesktop.org/mesa/mesa.git",
        "mesa_commit_url" => "https://gitlab.freedesktop.org/mesa/mesa/commit/",
        "mesa_dir" => "mesa.git",
        "branch" => "main",
        "filepaths" => [
            [
                "name" => "docs/GL3.txt",
                "excluded_commits" => [ "f926cf5bd0ade3273b320ca4483d826fcfe20bbb" ]    // Rename from GL3.txt to features.txt
            ],
            [
                "name" => "docs/features.txt",
                "excluded_commits" => []
            ]
        ],
    ),
);
