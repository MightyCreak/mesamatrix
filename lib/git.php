<?php

function exec_git($cmd, &$pipe) {
    $gitDir = MesaMatrix::$config["git"]["dir"];

    MesaMatrix::debug_print("git ".$cmd);
    $process = proc_open(
        "git ".$cmd,
        array(1 => array("pipe", "w")),
        $pipes,
        $gitDir
    );
    if (!is_resource($process)) {
        die("Unable to execute git");
    }
    $pipe = $pipes[1];
    return $process;
}

