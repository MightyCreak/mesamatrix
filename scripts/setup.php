<?php

require_once __DIR__."/../config.inc.php";

$gitCmd = "git clone --bare --depth ".MesaMatrix::$config["git"]["depth"]." ".
        MesaMatrix::$config["git"]["url"]." ".MesaMatrix::$config["git"]["dir"];

MesaMatrix::debug_print($gitCmd);
system($gitCmd);

?>
