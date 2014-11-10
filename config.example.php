<?php
///////////////////////////////////////
// Config.

// All paths relative to root directory
chdir(__DIR__);

class MesaMatrix
{
    static $config = array(
        "info" => array(
            "debug" => FALSE,
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
        ),

        "flattr" => array(
            "enabled" => FALSE,
            "id" => "your_flattr_id",
            "language" => "en_US",
            "tags" => "mesa,opengl",
        ),

        "git" => array(
            "url" => "git://anongit.freedesktop.org/mesa/mesa",
            "dir" => "mesa.git",
            "depth" => 6000,
            // oldest_commit is based on parser compatibility
            "oldest_commit" => "b6ab52b7f941b689753d4b9af7d58083e6917fd6",
            "commitparser_depth" => 10,

            "web" => "http://cgit.freedesktop.org/mesa/mesa",
            "gl3" => "docs/GL3.txt",
        ),
    );

    public static function debug_print($line)
    {
        if(self::$config["info"]["debug"])
        {
            print("DEBUG: ".$line."<br />\n");
        }
    }
};

///////////////////////////////////////
// Common code for all pages.

date_default_timezone_set('UTC');

if(MesaMatrix::$config["info"]["debug"])
{
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
}

