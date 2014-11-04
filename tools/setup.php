<?php

require_once __DIR__."/../config.php";

$gitCmd = "git clone --bare --depth ".MesaMatrix::$config["git"]["depth"]." ".
        MesaMatrix::$config["git"]["url"]." ".MesaMatrix::$config["git"]["dir"];

MesaMatrix::debug_print($gitCmd);
system($gitCmd);

?>
