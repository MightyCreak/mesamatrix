<?php

require_once __DIR__."/../config.inc.php";
require_once "scripts/oglparser.inc.php";
require_once "scripts/commitsparser.inc.php";
require_once "scripts/git.inc.php";

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
