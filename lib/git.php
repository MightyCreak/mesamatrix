<?php

function exec_git($cmd, &$pipe = false) {
    $gitDir = MesaMatrix::path(MesaMatrix::$config->getValue("git", "dir"));
    $cmd = str_replace('@gitDir@', escapeshellarg($gitDir), $cmd);

    $pipeArray = array(
      1 => STDOUT,
      2 => STDERR
    );
    if ($pipe !== false)
    {
        $pipeArray[1] = array("pipe", "w");
    }

    MesaMatrix::debug_print("git ".$cmd);
    $process = proc_open(
        "git ".$cmd,
        $pipeArray,
        $pipes,
        $gitDir
    );
    if (!is_resource($process)) {
        die("Unable to execute git");
    }
    if ($pipe !== false)
    {
        $pipe = $pipes[1];
    }
    return $process;
}

