<?php
///////////////////////////////////////
// Config.

$config["info"] = array(
    "debug" => FALSE,
    "version" => "1.0",
    "title" => "The OpenGL vs Mesa matrix",
    "description" => "Show Mesa progress for the OpenGL implementation into an easy to read HTML page.",
    "git_url" => "http://cgit.freedesktop.org/mesa/mesa",
    "gl3_file" => "src/gl3.txt",
    "log_file" => "src/gl3_log.txt",
);

$config["auto_fetch"] = array(
    "enabled" => TRUE,
    "timeout" => 3600,
    "url" => "http://cgit.freedesktop.org/mesa/mesa/plain/docs/GL3.txt",
);

$config["flattr"] = array(
    "enabled" => FALSE,
    "id" => "your_flattr_id",
    "language" => "en_US",
    "tags" => "mesa,opengl",
);

///////////////////////////////////////
// Common code for all pages.

date_default_timezone_set('UTC');

if($config["info"]["debug"])
{
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
}

function debug_print($line)
{
    if($config["info"]["debug"])
    {
        print("DEBUG: ".$line."<br />\n");
    }
}
?>

