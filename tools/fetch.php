<?php

require_once __DIR__."/../lib/base.php";

$gitFetch = Mesamatrix\Git\Util::exec("fetch origin master:master", $stream);
if(Mesamatrix::$config->getValue("info", "debug") === TRUE)
{
    $output = stream_get_contents($stream);
    if(!empty($output))
    {
        Mesamatrix::debug_print(stream_get_contents($stream));
    }
}

fclose($stream);
proc_close($gitFetch);

?>
