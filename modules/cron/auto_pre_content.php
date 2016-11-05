<?php
/**
 * This is the cron handler, it can be run directly within the engine (and checked on each page load)
 * or run outside from a normal cron scheduler.
 *
 * By default it is within the engine such that game owners don't need to setup any cron.
 * Easier for new comers.
 */

// Let's check if we are called directly or not.
$standalone = false;
if (!isset($baseDir)) {
    include_once "../../libs/common.php";
    $standalone = true;
}

// Seems we are called directly.
if ($standalone) {
    if (GetConfigValue("embeddedCron") == "true") {
        echo "The crons will be run automatically from within the engine.";
        return;
    }
    include_once "$baseDir/libs/db.php";
    include_once "$baseDir/config/config.php";
    $db = new Database($dbhost, $dbuser, $dbpass, $dbname);
    
    InitModules();
    if (ini_get('date.timezone') == "") {
        date_default_timezone_set((GetConfigValue("defaultTimeZone",
            "") == null ? 'UTC' : GetConfigValue("defaultTimeZone", "")));
    }
}
// Not called directly.
// Check if we shall do it from within the game or not.
else if (GetConfigValue("embeddedCron") == "false") {
    return;
}

// Check the last time we ran the cron.
//$t = floor(time() / (3600 * 24));
$t = date("z Y");
$lastRun = intval(GetConfigValue("lastCronRun"));
// It didn't passed a day, therefore we skip.
if ($t == $lastRun) {
    return;
}

SetConfigValue("lastCronRun", $t);

// Let's run all the modules cron.
RunHook("daily_cron.php");
