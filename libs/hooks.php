<?php
/**
 * Check for hooks files in the enabled modules.
 * This function can run cached versions in the full version of the engine.
 * In the devel version this is disabled as not useful.
 *
 * @param $hookName         string
 *                          the hook file name (for example menu.php)
 * @param $globalsToGetBack mixed
 *                          either a single variable name as string or an array of string of
 *                          all the variables to pass to the hooks as global variables.
 *
 * @return array
 */
function RunHook($hookName, $globalsToGetBack = null)
{
    global $gameName, $modules, $allModules, $baseDir, $defaultModule, $db, $userStats, $username, $userId, $moduleLoaded, $moduleTime, $template, $webBaseDir, $content, $hookCache;
    
    if ($globalsToGetBack != null) {
        if (is_array($globalsToGetBack)) {
            foreach ($globalsToGetBack as $i) {
                eval("global \$$i;");
            }
        } else {
            eval("global \$$globalsToGetBack;");
        }
    }
    
    $todo = array();
    foreach ($modules as $module) {
        if (file_exists("$baseDir/modules/$module/$hookName")) {
            $todo[] = "$baseDir/modules/$module/$hookName";
        }
    }
    
    $run = array();
    foreach ($todo as $hookToDo) {
        $start = microtime(true);
        include $hookToDo;
        $end = microtime(true);
        $run[substr(str_replace("\\", "/", $hookToDo), strlen("$baseDir/modules/"))] = round(($end - $start) * 1000);
    }
    return $run;
}

/**
 * Placeholder for compatibility reasons
 */
function CleanHookCache()
{
}