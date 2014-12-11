<?php

require_once __DIR__."/../lib/base.php";

//$gitLog = exec_git("log --pretty=format:%H --reverse " .
//    Mesamatrix::$config->getValue("git", "oldest_commit").".. -- ".Mesamatrix::$config->getValue("git", "gl3"), $log);
//$initialCommit = rtrim(fgets($log));
$initialCommit = "master";

$gitCat = Mesamatrix\Git\Util::exec(
  "show ".$initialCommit.":".Mesamatrix::$config->getValue("git", "gl3"),
  $stream);
$hints = new Mesamatrix\Parser\Hints();
$parser = new Mesamatrix\Parser\OglParser($hints);
$matrix = $parser->parse_stream($stream);
fclose($stream);
proc_close($gitCat);

$xml = new SimpleXMLElement("<mesa></mesa>");

// driver definitions
$drivers = $xml->addChild("drivers");
foreach (Mesamatrix\Parser\Constants::$allDriversVendors as $glVendor => $glDrivers) {
    $vendor = $drivers->addChild("vendor");
    $vendor->addAttribute("name", $glVendor);
    foreach ($glDrivers as $glDriver) {
        $driver = $vendor->addChild("driver");
        $driver->AddAttribute("name", $glDriver);
    }
}

// commits log
$gitLogFormat = "%H%n  timestamp: %ct%n  author: %an%n  subject: %s%n";
$gitCommits = Mesamatrix\Git\Util::exec(
  "log -n ".Mesamatrix::$config->getValue("git", "commitparser_depth").
  " --pretty=format:'".$gitLogFormat."' -- ".Mesamatrix::$config->getValue("git", "gl3"),
  $commitsStream);
$commitsParser = new Mesamatrix\CommitsParser();
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

        if(Mesamatrix::$config->getValue("opengl_links", "enabled", false)) {
            if (preg_match("/(GLX?)_([^_]+)_([a-zA-Z0-9_]+)/", $glExt->getName(), $matches) === 1) {
                if ($matches[1] === "GL") {
                    // Found a GL_TYPE_extension.
                    $openglUrl = Mesamatrix::$config->getValue("opengl_links", "url").urlencode($matches[2])."/".urlencode($matches[3]).".txt";
                    $urlHeader = get_headers($openglUrl);
                }
                else {
                    // Found a GLX_TYPE_Extension.
                    $openglUrl = Mesamatrix::$config->getValue("opengl_links", "url").urlencode($matches[2])."/glx_".urlencode($matches[3]).".txt";
                    $urlHeader = get_headers($openglUrl);
                }

                if ($urlHeader !== FALSE) {
                    Mesamatrix::debug_print("Try URL \"".$openglUrl."\". Result: \"".$urlHeader[0]."\".");
                    if ($urlHeader[0] === "HTTP/1.1 200 OK") {
                        $linkNode = $ext->addChild("link", $matches[0]);
                        $linkNode->addAttribute("href", $openglUrl);
                    }
                }
            }
        }

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
            $mesaStatus->addAttribute("hint", $hints->allHints[$mesaHintId]);
        }

        $supported = $ext->addChild("supported");
        foreach ($glExt->getSupportedDrivers() as $glDriver) {
            $driver = $supported->addChild($glDriver->getName());
            $hintId = $glDriver->getHintIdx();
            if ($hintId !== -1) {
                $driver->addAttribute("hint", $hints->allHints[$hintId]);
            }
        }
    }
}

//fclose($log);
//proc_close($gitLog);

$xmlPath = Mesamatrix::path(Mesamatrix::$config->getValue("info", "xml_file"));

$dom = dom_import_simplexml($xml)->ownerDocument;
$dom->formatOutput = true;

file_put_contents($xmlPath, $dom->saveXML());
//$xml->asXML($xmlPath);

?>
