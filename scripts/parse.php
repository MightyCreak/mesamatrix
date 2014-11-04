<?php

require_once __DIR__."/../config.inc.php";
require_once "scripts/oglparser.inc.php";
require_once "scripts/commitsparser.inc.php";
require_once "scripts/git.inc.php";

//$gitLog = exec_git("log --pretty=format:%H --reverse " .
//    MesaMatrix::$config["git"]["oldest_commit"].".. -- ".MesaMatrix::$config["git"]["gl3"], $log);
//$initialCommit = rtrim(fgets($log));
$initialCommit = "master";

$gitCat = exec_git("show ".$initialCommit.":".MesaMatrix::$config["git"]["gl3"], $stream);
$parser = new OglParser();
$matrix = $parser->parse_stream($stream);
fclose($stream);
proc_close($gitCat);

$xml = new SimpleXMLElement("<mesa></mesa>");

// driver definitions
$drivers = $xml->addChild("drivers");
foreach ($allDriversVendors as $glVendor => $glDrivers) {
    $vendor = $drivers->addChild("vendor");
    $vendor->addAttribute("name", $glVendor);
    foreach ($glDrivers as $glDriver) {
        $driver = $vendor->addChild("driver");
        $driver->AddAttribute("name", $glDriver);
    }
}

// commits log
$gitLogFormat = "%H%n  timestamp: %ct%n  author: %an%n  subject: %s%n";
$gitCommits = exec_git("log -n ".MesaMatrix::$config["git"]["commitparser_depth"].
    " --pretty=format:'".$gitLogFormat."' -- ".MesaMatrix::$config["git"]["gl3"],
    $commitsStream);
$commitsParser = new CommitsParser();
$commits = $commitsParser->parse_stream($commitsStream);
fclose($commitsStream);
proc_close($gitCommits);

$xmlCommits = $xml->addChild("commits");
foreach ($commits as $gitCommit) {
    $commit = $xmlCommits->addChild("commit");
    $commit->addAttribute("hash", $gitCommit["hash"]);
    $commit->addAttribute("timestamp", $gitCommit["timestamp"]);
    $commit->addAttribute("subject", $gitCommit["subject"]);
}

// status definitions
$statuses = $xml->addChild("statuses");

$complete = $statuses->addChild("complete");
$complete->addChild("match", "DONE*");

$incomplete = $statuses->addChild("incomplete");
$incomplete->addChild("match", "not started*");

$inProgressStatus = $statuses->addChild("started");
$inProgressStatus->addChild("match", "*");

// extensions
foreach ($matrix->getGlVersions() as $glVersion) {
    $gl = $xml->addChild("gl");
    $gl->addAttribute("name", $glVersion->getGlName());
    $gl->addAttribute("version", $glVersion->getGlVersion());
    $glsl = $gl->addChild("glsl");
    $glsl->addAttribute("name", $glVersion->getGlslName());
    $glsl->addAttribute("version", $glVersion->getGlslVersion());

    foreach ($glVersion->getExtensions() as $glExt) {
        $ext = $gl->addChild("extension");
        $ext->addAttribute("name", $glExt->getName());

        $mesaStatus = $ext->addChild("mesa");
        $statusLength = 0;
        foreach ($statuses as $matchStatus) {
            foreach ($matchStatus as $match) {
                if (fnmatch($match, $glExt->getStatus()) && strlen($match) >= $statusLength) {
                    $mesaStatus->addAttribute("status", $matchStatus->getName());
                    $statusLength = strlen($match);
                }
            }
        }
        $mesaHintId = $glExt->getHintIdx();
        if ($mesaHintId !== -1) {
            $mesaStatus->addAttribute("hint", $allHints[$mesaHintId]);
        }

        $supported = $ext->addChild("supported");
        foreach ($glExt->getSupportedDrivers() as $glDriver) {
            $driver = $supported->addChild($glDriver->getName());
            $hintId = $glDriver->getHintIdx();
            if ($hintId !== -1) {
                $driver->addAttribute("hint", $allHints[$hintId]);
            }
        }
    }
}

//fclose($log);
//proc_close($gitLog);

$dom = dom_import_simplexml($xml)->ownerDocument;
$dom->formatOutput = true;

file_put_contents(MesaMatrix::$config["info"]["xml_file"], $dom->saveXML());
//$xml->asXML(MesaMatrix::$config["info"]["xml_file"]);

?>
