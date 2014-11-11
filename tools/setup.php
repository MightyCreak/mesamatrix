<?php

require_once __DIR__."/../lib/base.php";

$git = Mesamatrix\Git\Util::exec(
  "clone --bare --depth ".Mesamatrix::$config->getValue("git", "depth")." ".
  Mesamatrix::$config->getValue("git", "url")." @gitDir@"
);

proc_close($git);

?>
