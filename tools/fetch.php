<?php

require_once __DIR__."/../config.php";
require_once "lib/oglparser.php";
require_once "lib/commitsparser.php";
require_once "lib/git.php";

$gitFetch = exec_git("fetch origin master:master", $stream);
if(MesaMatrix::$config["info"]["debug"] === TRUE)
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
