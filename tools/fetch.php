<?php

require_once __DIR__."/../lib/base.php";
require_once "oglparser.php";
require_once "commitsparser.php";
require_once "git.php";

$gitFetch = exec_git("fetch origin master:master", $stream);
if(MesaMatrix::$config->getValue("info", "debug") === TRUE)
{
    $output = stream_get_contents($stream);
    if(!empty($output))
    {
        MesaMatrix::debug_print(stream_get_contents($stream));
    }
}

fclose($stream);
proc_close($gitFetch);

?>
