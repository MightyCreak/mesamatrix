<?php

require_once __DIR__."/../lib/base.php";
require_once "git.php";

$git = exec_git(
  "clone --bare --depth ".MesaMatrix::$config->getValue("git", "depth")." ".
  MesaMatrix::$config->getValue("git", "url")." @gitDir@"
);

proc_close($git);

?>
