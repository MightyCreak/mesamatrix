<?php

require_once __DIR__."/../config.inc.php";

$gitCmd = "git clone --bare --depth ".$config["git"]["depth"]." ".
        $config["git"]["url"]." ".$config["git"]["dir"];

debug_print($gitCmd);
system($gitCmd);

?>
