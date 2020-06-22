<?php

/**
 * Stores menu entries in forms of label, position in the list and link.
 * It autmatically retreive the module calling it.
 */
class MenuEntry
{
    public $label;
    public $position = 1;
    public $link;
    public $group;
    public $description;

    /**
     * Create a new menu entry
     *
     * @param $label       string
     *                     label displayed on the menu
     * @param $group       string
     *                     an opional group name to group items in panels or in menu
     *                     groups.
     * @param $description string
     *                     an optional description for panels which shows a description
     *                     for a given menu entry.
     * @param $position    integer
     *                     used to sort menu entries
     * @param $link        string
     *                     if null then the module name will be used.
     *
     * @return \MenuEntry
     */
    public function __construct($label, $group = null, $description = null, $position = 1, $link = null)
    {
        $this->label = $label;
        $this->position = $position;
        $this->group = $group;
        $this->description = $description;

        // Link is not defined then let's extract it from the module name
        // calling this function.
        if ($link == null) {
            $bt = debug_backtrace();
            $f = array_shift($bt);
            $filename = str_replace("\\", "/", $f["file"]);
            $path = explode("/", $filename);
            if (in_array("cached_libs", $path)) {
                $f = array_shift($bt);
                $hookName = explode("/", str_replace("\\", "/", $f['file']));
                $hookName = str_replace(".php", "", substr(array_pop($hookName), 6));
                $this->link = substr($f["function"], strlen($hookName) + 1);
            } else {
                array_pop($path);
                $this->link = array_pop($path);
            }
        } else {
            $this->link = $link;
        }
    }

    /**
     * Sort the menu entries by their position then by their label.
     *
     * @param $arrayOfMenuEntries unknown_type
     */
    public static function Sort(&$arrayOfMenuEntries)
    {
        usort($arrayOfMenuEntries, "SortMenuCallback");
    }

    public static function HasMenuEntry(&$arrayOfMenuEntries, $name)
    {
        foreach ($arrayOfMenuEntries as $i) {
            if ($i->label == $name) {
                return true;
            }
        }
        return false;
    }
}

function SortMenuCallback($a, $b)
{
    if ($a->position > $b->position) {
        return 1;
    } else if ($a->position < $b->position) {
        return -1;
    }
    $g = (strcmp($a->group, $b->group));
    if ($g != 0) {
        return $g;
    }
    return strcmp($a->label, $b->label);
}

/**
 * Allows to block the GUI to a specified module.
 */
function BlockModule($module)
{
    global $db, $userId;

    $_SESSION['block'] = $module;
    $db->Execute("update users set blocked_module = ? where id = ?", $module, $userId);
}

function GetBlockedModule()
{
    if (!isset($_SESSION['block'])) {
        return null;
    }
    return $_SESSION['block'];
}

/**
 * Release the module block.
 */
function ReleaseModule()
{
    global $db, $userId;

    $_SESSION['block'] = null;
    $db->Execute("update users set blocked_module = ? where id = ?", "", $userId);
}

// Translation cache
$translation = null;
$translationModified = false;

/**
 * Load back the XML language file.
 */
function LoadTranslation()
{
    global $baseDir, $language, $translation;

    $translation = array();

    if (file_exists("$baseDir/language/$language.xml")) {
        $doc = new XMLReader();
        $doc->open("$baseDir/language/$language.xml");
        while ($doc->read()) {
            if ($doc->nodeType == XMLReader::END_ELEMENT) {
                continue;
            }
            if ($doc->name == "entry") {
                $key = $doc->getAttribute("key");
                $value = $doc->getAttribute("value");
                $translation[$key] = ($value == null ? "" : $value);
            }
        }
        $doc->close();
    }
}

/**
 * Translate one text into another language, or allows texts customizations.
 * Works like sprintf.
 *
 * @param $text string
 *              original text (keep it in english)
 * @param
 *              mixed args variable list of arguments
 *
 * @return string translated text
 */
function Translate()
{
    global $translation, $translationModified, $db, $webBase;

    $nb = func_num_args();
    $args = func_get_args();

    $text = array_shift($args);
    $origText = $text;

    if ($translation == null) {
        LoadTranslation();
    }

    if (!isset($translation[$text])) {
        $translation[$text] = "";
        $translationModified = true;
    }

    if ($translation[$text] != null) {
        $text = $translation[$text];
    }

    if (isset($db)) {
        $text = str_replace("!Currency", GetConfigValue("currencyStat", ""), $text);

        if (IsAdmin() && isset($_SESSION['show_edit_text']) && $_SESSION['show_edit_text'] == true) {
            return vsprintf($text,
                $args) . "<a href='index.php?p=admin_language_editor&row=" . rawurlencode($origText) . "'><img src='{$webBase}images/text_edit.gif' border='0'></a>";
        }
    }
    return vsprintf($text, $args);
}

/**
 * Called at the end, to store any modifications of the translation table.
 */
function StoreTranlation()
{
    global $baseDir, $language, $translation, $translationModified;

    if ($translationModified == false) {
        return;
    }

    $doc = new XMLWriter();
    $doc->openUri("$baseDir/language/$language.xml");
    $doc->startDocument('1.0', 'UTF-8');
    $doc->setIndent(true);
    $doc->setIndentString(' ');
    $doc->startElement('definition');
    foreach ($translation as $key => $value) {
        $doc->startElement('entry');
        $doc->writeAttribute("key", $key);
        if ($value != null && trim($value) != "") {
            $doc->writeAttribute("value", $value);
        }
        $doc->endElement();
    }
    $doc->endElement();
    $doc->endDocument();
}

$modules = array();
$allModules = array();

/**
 * Scans all the modules directories to grab a list of all possible modules
 */
function InitModules()
{
    global $modules, $allModules, $baseDir;

    $modules = array();
    $allModules = array();

    // Retrieves the base directory (installation) for the game script.
    if (!isset($baseDir)) {
        $cur = getcwd();
        $cur = str_replace("\\", "/", $cur);
        while ($cur != "") {
            if (file_exists("$cur/cached_libs")) {
                $baseDir = $cur;
                break;
            }
            $cur = substr($cur, 0, strrpos($cur, "/"));
        }
        if ($baseDir == "") {
            $baseDir = getcwd();
            if ($baseDir == "") {
                $baseDir = ".";
            }
        }
    }

    $dir = scandir("$baseDir/modules");

    foreach ($dir as $d) {
        if ($d == "." || $d == ".." || !is_dir("$baseDir/modules/$d")) {
            continue;
        }
        if ($d[0] == ".") {
            continue;
        }
        $allModules[] = $d;
        if (!file_exists("$baseDir/modules/$d/module.lock")) {
            $modules[] = $d;
        }
    }
    sort($allModules);
    sort($modules);
}

/**
 * Install a module (execute the install.sql file as well as register the module
 * in the modules table)
 *
 * @param $db     Database
 *                database connection to be used.
 * @param $module string
 *                module name to be installed
 *
 * @return boolean returns true if all went well.
 */
function InstallModule($db, $module)
{
    global $baseDir;

    $hasErrors = false;
    if (file_exists("$baseDir/modules/$module/install.sql")) {
        $sql = file_get_contents("$baseDir/modules/$module/install.sql");
        $sql = str_replace("\r\n", "\n", $sql);
        $statements = explode(";\n", $sql);
        foreach ($statements as $cmd) {
            if (trim($cmd) == "") {
                continue;
            }

            if ($db->Execute($cmd) === false) {
                ErrorMessage(Translate("Error whilst executing:<br>%s", $cmd), false);
                $hasErrors = true;
            }
        }
    }

    if (!$hasErrors) {
        $db->Execute("replace into modules(name,version) values(?,?)", $module, GetModuleVersion($module));
    }

    return !$hasErrors;
}

/**
 * Compare two version numbers (format like 0.1.2)
 *
 * @param $a  string
 *            first version
 * @param $b  string
 *            second version
 *
 * @return number
 */
function VersionCompare($a, $b)
{
    $ap = explode('.', $a);
    $bp = explode('.', $b);

    $ai = array();
    foreach ($ap as $i) {
        $ai[] = intval($i);
    }
    $bi = array();
    foreach ($bp as $i) {
        $bi[] = intval($i);
    }

    $nb = min(count($ai), count($bi));
    for ($i = 0; $i < $nb; $i++) {
        if ($ai[$i] < $bi[$i]) {
            return -1;
        }
        if ($ai[$i] > $bi[$i]) {
            return 1;
        }
    }
    return count($ai) - count($bi);
}

/**
 * Find upgrades SQL files for the base engine or modules.
 *
 * @param $directory   string
 *                     where to check
 * @param $baseVersion string
 *                     current version (older or equal than this will not be returned)
 * @param $pattern     string
 *                     file pattern to match
 *
 * @return array list of files matching the criteria
 */
function FindUpgrades($directory, $baseVersion, $pattern = "/^upgrade-([0-9_]+)\\.sql\$/")
{
    $t = scandir($directory);
    $files = array();
    foreach ($t as $f) {
        if (preg_match($pattern, $f, $out) == 0) {
            continue;
        }
        $v = str_replace("_", ".", $out[1]);

        if (VersionCompare($baseVersion, $v) < 0) {
            $files[$v] = $f;
        }
    }
    uksort($files, "VersionCompare");
    return $files;
}

/**
 * Apply all the SQL files for a given module.
 *
 * @param $db          Database
 *                     database connection to use.
 * @param $module      string
 *                     module name.
 * @param $fromVersion string
 *                     current version.
 *
 * @return boolean returns true if all went well.
 */
function UpgradeModule($db, $module, $fromVersion)
{
    global $baseDir;

    $hasErrors = false;

    $lastVersion = GetModuleVersion($module);
    // Is it the same version? If yes skip.
    if ($lastVersion == $fromVersion) {
        return true;
    }

    $files = FindUpgrades("$baseDir/modules/$module", $fromVersion);

    foreach ($files as $version => $file) {
        $sql = file_get_contents("$baseDir/modules/$module/$file");
        $sql = str_replace("\r\n", "\n", $sql);
        $statements = explode(";\n", $sql);
        foreach ($statements as $cmd) {
            if (trim($cmd) == "") {
                continue;
            }
            if ($db->Execute($cmd) === false) {
                ErrorMessage(Translate("Error whilst executing:<br>%s", $cmd), false);
                $hasErrors = true;
            }
        }
    }

    if (!$hasErrors) {
        $db->Execute("replace into modules(name,version) values(?,?)", $module, $lastVersion);
    }

    return !$hasErrors;
}

/**
 * Register the variables required by the module in the config/auto_defines.php
 *
 * @param $module string
 *                module name
 */
function RegisterModuleVariables($module)
{
    global $baseDir;

    // If there is no config.xml then we can quit already
    if (!file_exists("$baseDir/modules/$module/config.xml")) {
        return;
    }

    include_once "$baseDir/config/auto_defines.php";

    // Read back the auto_defines.php
    $defineCode = str_replace("?>", "", file_get_contents("$baseDir/config/auto_defines.php"));
    $modified = false;

    $nextId = 1;

    // Find the next free id
    preg_match_all("/,([0-9]+)\\);/", $defineCode, $matches);
    foreach ($matches[1] as $m) {
        $v = intval($m);
        if ($v >= $nextId) {
            $nextId = $v + 1;
        }
    }

    // Read the config file and register all the variables
    $doc = new XMLReader();
    $doc->open("$baseDir/modules/$module/config.xml");
    while ($doc->read()) {
        if ($doc->nodeType == XMLReader::END_ELEMENT) {
            continue;
        }
        if ($doc->name == "variable") {
            if (!defined($doc->getAttribute("name"))) {
                $defineCode .= "define('" . $doc->getAttribute("name") . "',$nextId);\n";
                $nextId++;
                $modified = true;
            }
        }
    }
    $doc->close();

    // If modified save
    if ($modified == true) {
        $defineCode .= "?>";
        file_put_contents("$baseDir/config/auto_defines.php", $defineCode);
    }
}

/**
 * Generates a captcha
 *
 * @return string the Captcha HTML code to add
 */
function CaptchaShow()
{
    global $modules, $baseDir;

    foreach ($modules as $module) {
        if (file_exists("$baseDir/modules/$module/captcha.php")) {
            include_once "$baseDir/modules/$module/captcha.php";
            return ModuleCaptchaGenerate();
        }
    }
    return "";
}

/**
 * Check if the captcha value is correct
 *
 * @return boolean returns true if correct
 */
function CaptchaCheck()
{
    global $modules, $baseDir;

    foreach ($modules as $module) {
        if (file_exists("$baseDir/modules/$module/captcha.php")) {
            include_once "$baseDir/modules/$module/captcha.php";
            return ModuleCaptchaCheck();
        }
    }
    return true;
}

/**
 * Include an inside page.
 *
 * @param $page string
 *              module name to load or empty to load the default.
 */
function IncludePrivatePage($page)
{
    global $gameName, $modules, $allModules, $baseDir, $defaultModule, $db, $userStats, $username, $userId, $moduleLoaded, $moduleTime, $template, $webBaseDir;

    $time_start = Database::microtime_float();

    // Path is not defined, then load the default
    if ($page == "") {
        $moduleLoaded = $defaultModule;
        include "$baseDir/modules/$defaultModule/content.php";
    } // Path defined, let's see if it's a real module
    else {
        $path = $page;
        // Not a module, let's load the default
        if (!in_array($path, $modules) || !file_exists("$baseDir/modules/$path/content.php")) {
            $moduleLoaded = $defaultModule;
            include "$baseDir/modules/$defaultModule/content.php";
        } // It's a valid module, we load it.
        else {
            $moduleLoaded = $path;
            include "$baseDir/modules/$path/content.php";
        }
    }

    $time_end = Database::microtime_float();
    $moduleTime = $time_end - $time_start;
}

/**
 * Includes a public page.
 *
 * @param $page string
 *              module name to load or empty to load the default.
 */
function IncludePublicPage($page)
{
    global $gameName, $modules, $baseDir, $defaultPublic, $db, $webBaseDir, $userId;

    // Path is not defined, then load the default
    if ($page == "") {
        include "$baseDir/modules/$defaultPublic/public.php";
    } // Path defined, let's see if it's a real module
    else {
        $path = $page;
        // Not a module, let's load the default
        if (!in_array($path, $modules) || !file_exists("$baseDir/modules/$path/public.php")) {
            include "$baseDir/modules/$defaultPublic/public.php";
        } // It's a valid module, we load it.
        else {
            include "$baseDir/modules/$path/public.php";
        }
    }
}

/**
 * Find a user based on an id, name or email.
 *
 * @param $user mixed
 *              the id or username or email.
 *
 * @return mixed NULL if not found or the ID of the user.
 */
function FindUser($user)
{
    global $db;

    $id = $user + 0;
    if ($id != 0) {
        $result = $db->Execute("select id from users where id=?", $user);
    } else if (strpos($user, "@") !== false) {
        $result = $db->Execute("select id from users where email=?", $user);
    } else {
        $result = $db->Execute("select id from users where username=?", $user);
    }

    if ($result->EOF) {
        $result->Close();
        return null;
    }
    $return = $result->fields[0];
    $result->Close();
    return $return;
}

$cachedConfigValue = array();
$cachedConfigKeys = array();

/**
 * Retreives a configuration parameter out of the database or the XML config
 * file.
 *
 * @param $keyName string
 *                 key name of the parameter.
 * @param $module  string
 *                 module name to search or NULL for an automatic lookup.
 *
 * @return string NULL the value found in the configuration file or NULL if not
 *         found.
 */
function GetConfigValue($keyName, $module = -1)
{
    global $baseDir, $cachedConfigValue, $cachedConfigKeys, $db;

    if ($module == -1) {
        $bt = debug_backtrace();
        $f = array_shift($bt);
        $filename = str_replace("\\", "/", $f["file"]);
        $path = explode("/", $filename);

        if (in_array("cached_libs", $path)) {
            $f = array_shift($bt);
            $hookName = explode("/", str_replace("\\", "/", $f['file']));
            $hookName = str_replace(".php", "", substr(array_pop($hookName), 6));
            $module = substr($f["function"], strlen($hookName) + 1);
        } else {
            array_pop($path);
            $module = array_pop($path);
        }
    }

    // Load back all the DB configuration values in one shot
    // (reduces the number of queries and speedup the code)
    if (count($cachedConfigValue) == 0) {
        $result = $db->Execute("select value,module,name from module_config_values");
        while (!$result->EOF) {
            $cachedConfigValue[$result->fields[1] . "_" . $result->fields[2]] = $result->fields[0];
            $cachedConfigKeys[$result->fields[2]] = $result->fields[1];
            $result->MoveNext();
        }
        $result->Close();
    }

    // Is a cached value? If yes return the cached value.
    if (isset($cachedConfigValue[$module . "_" . $keyName])) {
        return $cachedConfigValue[$module . "_" . $keyName];
    }

    // We didn't found it via $module, let's search without
    if (isset($cachedConfigKeys[$keyName])) {
        return $cachedConfigValue[$cachedConfigKeys[$keyName] . "_" . $keyName];
    }

    // Not in the database then fall back to the XML file
    $value = null;
    if (file_exists("$baseDir/modules/$module/config.xml")) {
        $doc = new XMLReader();
        $doc->open("$baseDir/modules/$module/config.xml");
        while ($doc->read()) {
            if ($doc->nodeType == XMLReader::END_ELEMENT) {
                continue;
            }
            if ($doc->name == "key" && $doc->getAttribute("name") == $keyName) {
                $value = $doc->getAttribute("value");
                $db->Execute("replace into module_config_values(module,name,value) values(?,?,?)", $module, $keyName,
                    $value);
                break;
            }
        }
        $doc->close();
    }
    $cachedConfigValue[$module . "_" . $keyName] = $value;

    return $value;
}

/**
 * Stores the config value in the database and maybe on the XML file as well (if
 * $storeXmlConfig is set to true in the config.php)
 *
 * The configuration values are stored in priority on the database, allowing to
 * replace
 * all the module files, without having to re-set all the configuration values.
 *
 * @param $keyName string
 *                 config name
 * @param $value   string
 *                 value to store
 * @param $module  string
 *                 module name or null will auto-detect
 */
function SetConfigValue($keyName, $value, $module = -1)
{
    global $baseDir, $db, $storeXmlConfig, $cachedConfigValue;

    if ($module == -1) {
        $bt = debug_backtrace();
        $f = array_shift($bt);
        $filename = str_replace("\\", "/", $f["file"]);
        $path = explode("/", $filename);

        if (in_array("cached_libs", $path)) {
            $f = array_shift($bt);
            $hookName = explode("/", str_replace("\\", "/", $f['file']));
            $hookName = str_replace(".php", "", substr(array_pop($hookName), 6));
            $module = substr($f["function"], strlen($hookName) + 1);
        } else {
            array_pop($path);
            $module = array_pop($path);
        }
    }

    // Load back all the DB configuration values in one shot
    // (reduces the number of queries and speedup the code)
    if (count($cachedConfigValue) == 0) {
        $result = $db->Execute("select value,module,name from module_config_values");
        while (!$result->EOF) {
            $cachedConfigValue[$result->fields[1] . "_" . $result->fields[2]] = $result->fields[0];
            $cachedConfigKeys[$result->fields[2]] = $result->fields[1];
            $result->MoveNext();
        }
        $result->Close();
    }

    if (isset($cachedConfigValue[$module . "_" . $keyName])) {
        $cachedConfigValue[$module . "_" . $keyName] = $value;
    } // We didn't found it via $module, let's search without
    else if (isset($cachedConfigKeys[$keyName])) {
        $module = $cachedConfigKeys[$keyName];
        $cachedConfigValue[$module . "_" . $keyName] = $value;
    }

    if (isset($cachedConfigValue[$module . "_" . $keyName])) {
        $db->Execute("replace into module_config_values(module,name,value) values(?,?,?)", $module, $keyName, $value);
    }

    if ($storeXmlConfig === true) {
        // To update (easily) the XML file, we use the DOM parser instead of the
        // XML
        // Writer.
        $dom = new DOMDocument();
        $dom->load("$baseDir/modules/$module/config.xml");
        // Search all keys
        $markers = $dom->documentElement->getElementsByTagName('key');
        foreach ($markers as $m) {
            // Find the right one
            if ($m->getAttribute("name") == $keyName) {
                // Set the new value
                $m->setAttribute("value", $value);
                break;
            }
        }
        // Save the file and done!
        $dom->save("$baseDir/modules/$module/config.xml");
    }
}

$moduleInfo = array();

/**
 * Returns a module version
 *
 * @param $name string
 *              module name
 */
function GetModuleVersion($module)
{
    global $baseDir, $moduleInfo;
    $returnValue = "0.0.0";

    if (isset($moduleInfo[$module])) {
        return $moduleInfo[$module]["version"];
    }

    if ($module != "core" && !file_exists("$baseDir/modules/$module/config.xml")) {
        return $returnValue;
    }

    $doc = new XMLReader();
    if ($module == "core") {
        $doc->open("$baseDir/modules/config.xml");
    } else {
        $doc->open("$baseDir/modules/$module/config.xml");
    }
    while ($doc->read()) {
        if ($doc->nodeType == XMLReader::END_ELEMENT) {
            continue;
        }
        if ($doc->name == "module") {
            $moduleInfo[$module] = array(
                "version" => $doc->getAttribute("version"),
                "author" => $doc->getAttribute("author"),
                "description" => $doc->getAttribute("description")
            );
            $returnValue = $doc->getAttribute("version");
        }
    }
    $doc->close();
    return $returnValue;
}

/**
 * Returns a module author
 *
 * @param $name string
 *              module name
 */
function GetModuleAuthor($module)
{
    global $baseDir, $moduleInfo;
    $returnValue = "";

    if (isset($moduleInfo[$module])) {
        return $moduleInfo[$module]["author"];
    }

    if (!file_exists("$baseDir/modules/$module/config.xml")) {
        return $returnValue;
    }

    $doc = new XMLReader();
    $doc->open("$baseDir/modules/$module/config.xml");
    while ($doc->read()) {
        if ($doc->nodeType == XMLReader::END_ELEMENT) {
            continue;
        }
        if ($doc->name == "module") {
            $moduleInfo[$module] = array(
                "version" => $doc->getAttribute("version"),
                "author" => $doc->getAttribute("author"),
                "description" => $doc->getAttribute("description")
            );
            $returnValue = $doc->getAttribute("author");
        }
    }
    $doc->close();
    return $returnValue;
}

/**
 * Retreives the description for a given module.
 *
 * @param $module string
 *                the module name
 *
 * @return string
 */
function GetModuleDescription($module)
{
    global $baseDir, $moduleInfo;
    $returnValue = "";

    if (isset($moduleInfo[$module])) {
        return $moduleInfo[$module]["description"];
    }

    if (!file_exists("$baseDir/modules/$module/config.xml")) {
        return $returnValue;
    }

    $doc = new XMLReader();
    $doc->open("$baseDir/modules/$module/config.xml");
    while ($doc->read()) {
        if ($doc->nodeType == XMLReader::END_ELEMENT) {
            continue;
        }
        if ($doc->name == "module") {
            $moduleInfo[$module] = array(
                "version" => $doc->getAttribute("version"),
                "author" => $doc->getAttribute("author"),
                "description" => $doc->getAttribute("description")
            );
            $returnValue = $doc->getAttribute("description");
        }
    }
    $doc->close();
    return $returnValue;
}

/**
 * Retreive the stored user variable
 *
 * @param $variableId integer
 *                    ID of the variable to get.
 */
function GetUserVariable($variableId, $user = null)
{
    global $db, $userId, $demoEngine;

    if ($user == null) {
        $user = $userId;
    }

    $value = null;

    if (isset($demoEngine) && $demoEngine === true && $user == $userId) {
        if (isset($_SESSION["user_vars"]) && isset($_SESSION["user_vars"][$variableId])) {
            return $_SESSION["user_vars"][$variableId];
        }
    }

    $result = $db->Execute("select value from user_variables where user_id = ? and variable_id = ?", $user,
        $variableId);
    if (!$result->EOF) {
        $value = $result->fields[0];
    }
    $result->Close();

    return $value;
}

/**
 * Stores a user variable
 *
 * @param $variableId integer
 *                    ID of the variable to store
 * @param $value      string
 *                    value to be stored
 */
function SetUserVariable($variableId, $value, $user = null)
{
    global $db, $userId, $demoEngine;

    if ($user == null) {
        $user = $userId;
    }

    if (isset($demoEngine) && $demoEngine === true && $user == $userId) {
        if (!isset($_SESSION["user_vars"])) {
            $_SESSION["user_vars"] = array();
        }
        $_SESSION["user_vars"][$variableId] = $value;
        return;
    }

    $db->Execute("replace into user_variables(user_id,variable_id,value) values(?,?,?)", $user, $variableId, $value);
}

/**
 * Checks if the functions to post a message back to the central server are
 * available.
 */
function CanPostToServer()
{
    if (!function_exists("curl_init") && !function_exists("fsockopen")) {
        return false;
    }
    return true;
}

/**
 * Implements the POST either via CURL or via fsockopen
 *
 * @param $host    string
 * @param $url     string
 * @param $message string
 * @param $proto   the
 *                 protocol used (http or https)
 *
 * @throws Exception
 * @return string
 */
function PostMessageToServer($host, $url, $message, $proto = "http")
{
    if (function_exists("curl_init")) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$proto}://{$host}{$url}");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $return = curl_exec($ch);
        curl_close($ch);
        return $return;
    } else {
        if ($proto == "https") {
            throw new Exception("You  must have curl installed to use https.");
        }
        $fp = fsockopen($host, 80, $errno, $errstr, 30);
        if (!$fp) {
            throw new Exception("Error: $errstr ($errno)<br>\n");
        }

        $http = "POST $url HTTP/1.1\r\n";

        fputs($fp, $http);
        fputs($fp, "Host: " . $host . "\r\n");
        fputs($fp, "Accept-Language: en-us,en;q=0.5\r\n");
        fputs($fp,
            "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1) Gecko/20061010 Firefox/2.0\r\n");
        fputs($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
        fputs($fp, "Content-Length: " . strlen($message) . "\r\n");
        fputs($fp, "Connection: close\r\n");
        fputs($fp, "\r\n");
        fputs($fp, $message);
        fflush($fp);

        $res = stream_get_contents($fp);
        $res = substr($res, strpos($res, "\r\n\r\n") + 4);
        fclose($fp);
        return $res;
    }
}

/**
 * Sends a message to the central repository
 *
 * @param $message array
 *
 * @return array the answer as array
 */
function SendRepositoryMessage($message)
{
    $data = gzcompress(serialize($message), 9);
    $r = PostMessageToServer("nwe.funmayhem.com", "/repository.php", "message=" . urlencode(base64_encode($data)));
    return unserialize($r);
}

/**
 * Creates a selection of values which will be loaded asyncronously and filtered
 * via AJAX.
 *
 * @param $source       string
 *                      the select statement to be used (must return 2 columns key /
 *                      value)
 * @param $fieldId      string
 *                      field name of the form element
 * @param $defaultValue string
 *                      default selected value of the element
 */
function SmartSelection($source, $fieldId, $defaultValue = false)
{
    global $content, $webBaseDir, $db;

    if ($defaultValue === false && isset($_POST[$fieldId])) {
        $defaultValue = $_POST[$fieldId];
    }

    $res = preg_split("/[^a-zA-Z0-9_]/", strtolower($source), -1, PREG_SPLIT_NO_EMPTY);
    if ($res[1] == "unique" || $res[1] == "distinct") {
        array_splice($res, 1, 1);
    }

    $colA = $res[1];
    $colB = $res[2];

    $displayText = "";

    if ($defaultValue !== false) {
        if (in_array("where", $res)) {
            $query = "$source and $colA = ?";
        } else {
            $query = "$source where $colA = ?";
        }
        $result = $db->Execute($query, $defaultValue);
        if (!$result->EOF) {
            $displayText = $result->fields[1];
        }
    }

    $html = "";
    if (strpos($content['footerScript'], "smart_pick.js") === false) {
        $content['footerScript'] .= "<script src='{$webBaseDir}js/smart_pick.js'></script>";
    }

    $html .= "<table class='plainTable'>";
    $html .= "<tr><td>";
    if ($defaultValue !== false) {
        $html .= "<input type='hidden' name='$fieldId' id='$fieldId' value='" . htmlentities($defaultValue) . "'>";
    } else {
        $html .= "<input type='hidden' name='$fieldId' id='$fieldId'>";
    }
    $html .= "<input type='text' id='type_$fieldId' onkeyup='smartKeyPress(\"$fieldId\");' value='" . htmlentities($displayText) . "' onfocus='smartFocus(\"{$fieldId}\");' onblur='smartBlur(\"{$fieldId}\");'></td></tr>\n";
    $html .= "<tr id='row_choice_{$fieldId}' style='visibility: hidden; position: absolute;'><td><select id='choice_{$fieldId}' size='10' onclick='smartPickClick(\"{$fieldId}\");' onfocus='smartFocus(\"{$fieldId}\");' onblur='smartBlur(\"{$fieldId}\");'>\n";

    if (!isset($_SESSION["smartPick"])) {
        $_SESSION["smartPick"] = array();
    }

    // Store the query for the ajax callback
    $_SESSION["smartPick"][$fieldId] = $source;

    $query = "$source order by $colB limit 0,30";

    $result = $db->Execute($query);
    while (!$result->EOF) {
        $html .= "<option value='{$result->fields[0]}'>{$result->fields[1]}</option>";
        $result->MoveNext();
    }
    $result->Close();
    $html .= "</select></td></tr>";
    $html .= "</table>";

    return $html;
}

/**
 * Makes an ID out of a prefix and a label name.
 * Used to create DOM IDs.
 *
 * @param $prefix string
 * @param $label  string
 *
 * @return string the generated id.
 */
function MakeId($prefix, $label)
{
    return $prefix . str_replace(array(" ", "\"", "'", "<", ">", "(", ")", "/", "=", "?", ".", ":"),
        array("_", "", "", "", "", "", "", "", "", "", "", ""), strtolower($label));
}

/**
 * Checks the version of a module by connecting to the market place and checking
 * the last submitted version.
 * An empty module name will return the engine version.
 *
 * @param $module string
 *                the module name.
 *
 * @return string the version of the module.
 */
function CheckLastVersion($module)
{
    return trim(PostMessageToServer("nwe.funmayhem.com", "/check_version.php", "module=" . $module));
}

$evalCode = array();

function NWEval($code)
{
    global $evalCode, $userStats, $modules, $allModules, $db, $content;

    $evalCode[] = $code;
    $res = eval($code);
    array_pop($evalCode);
    return $res;
}

function engine_stop()
{
    error_reporting(0);
    try {
        if (function_exists("error_get_last")) {
            $error = error_get_last();
            if ($error !== null) {
                handle_error($error['message'], $error['file'], $error['line'], array());
            }
        }
    } catch (Exception $ex) {
    }
}

/**
 * Handles errors of the engine or the modules
 *
 * @param $errorMessage string
 * @param $filename     string
 * @param $lineNumber   integer
 * @param $stack        array
 *                      stack trace
 */
function handle_error($errorMessage, $filename, $lineNumber, $stack)
{
    global $db, $content, $template, $isAdmin, $alwaysShowErrorDetails, $modules, $baseDir, $isSuperUser, $evalCode, $userId;

    ob_get_clean();
    ob_start();

    $template = "error";

    $filename = str_replace("\\", "/", $filename);

    $fullError = "";
    if (isset($db) && isset($db->conn) && isset($db->conn->error) && $db->conn->error != null && $db->conn->error != "") {
        $fullError .= "Query error: " . $db->conn->error . "<br>";
        $fullError .= "<div style='border: solid 2px red; padding: 3px;'>{$db->lastQuery}</div><br>";
    } else if (strpos($errorMessage, "number of parameters in prepared statement") !== false) {
        $fullError .= "Query error: Number of variables doesn't match number of parameters in prepared statement<br>";
        $fullError .= "<div style='border: solid 2px red; padding: 3px;'>{$db->lastQuery}</div><br>";
    } else {
        $fullError .= "Error: $errorMessage<br>";
    }

    if (strpos($errorMessage, "unexpected T_STRING") !== false) {
        $fullError .= "<b style='color: blue;'>HINT:</b> Maybe you didn't closed / escaped a string correctly or missing a ; sign at the end of a line.<br><br>";
    } else if (strncmp($errorMessage, "Undefined index: ", 17) == 0) {
        $fullError .= "<b style='color: blue;'>HINT:</b> If you are accessing a GET or POST, make sure to use the isset function.<br><br>";
    } else if (strncmp($errorMessage, "Undefined variable: ", 20) == 0) {
        $fullError .= "<b style='color: blue;'>HINT:</b> You should always declare your variables before using them. However this error could be triggered as well if you made a typo in the variable name or you missed to call the global \$var_name command.<br><br>";
    } else if (strncmp($errorMessage, "Use of undefined constant ", 26) == 0) {
        $fullError .= "<b style='color: blue;'>HINT:</b> Maybe you forget to put a \$ sign before a variable name. If it's a string you want to use, put a single or double quote before and after the string.<br><br>";
    } else if (strncmp($errorMessage, "syntax error,", 13) == 0) {
        $fullError .= "<b style='color: blue;'>HINT:</b> Maybe you forget to put a ; sign at the end of a line.<br><br>";
    }

    if (count($evalCode) > 0) {
        $fullError .= "Error in evaluated code:<br>";
        $fullError .= "<div style='border: solid 2px red; padding: 3px;'><pre>";
        $fullError .= $evalCode[count($evalCode) - 1];
        $fullError .= "</pre></div>";
    }

    // Shows the link to the code editor if the module exists.
    if (function_exists("Secure") && in_array("admin_code_editor", $modules) && strncmp(substr($filename,
            strlen($baseDir) + 1), "modules/", 8) == 0
    ) {
        $fullError .= "Error in <a href='" . Secure("index.php?p=admin_code_editor&f=" . urlencode(substr($filename,
                    strlen($baseDir . "/modules/"))) . "&l=$lineNumber",
                true) . "'>$filename</a><br>Line $lineNumber<br>";
    } else {
        $fullError .= "Error in \"$filename\"<br>Line $lineNumber<br>";
    }

    array_shift($stack);
    foreach ($stack as $s) {
        if (isset($s['file'])) {
            $fullError .= "Error in {$s['file']}<br>";
        }
        if (isset($s['line'])) {
            $fullError .= "Line {$s['line']}<br>";
        }
    }

    // Admin show the full info
    if ($alwaysShowErrorDetails == true || ($isAdmin && GetConfigValue("adminViewException", "bug_tracking") != "no")) {
        echo "$fullError<br>";
    }

    echo Translate("Please help us to improve the game by providing as much information regards any bugs.");
    if ($userId == -1 && !($alwaysShowErrorDetails == true || ($isAdmin && GetConfigValue("adminViewException",
                    "bug_tracking") != "no"))
    ) {
        echo "<br><br><b>" . Translate("If you are the administrator of this site, you may edit the config/config.php file and set the variable \$alwaysShowErrorDetails to TRUE to get more information about this error.") . "</b>";
    }
    echo "</span><br> <br>";
    echo "<form method='post' name='reportBug'>";
    echo "<input type='hidden' name='action' value='reportBug'>";
    TableHeader("Step by step how to reproduce it:");
    echo "<textarea name='report'></textarea><br>";
    TableFooter();
    echo "</form>";
    ButtonArea();
    SubmitButton("Report", "reportBug");
    LinkButton("Back", "index.php");
    EndButtonArea();

    $_SESSION["bug_line"] = $lineNumber;
    $_SESSION["bug_file"] = $filename;
    $_SESSION["bug_report"] = str_replace("<br>", "\n", $fullError);

    $content['main'] = ob_get_clean();
    // Clean the output to avoid double content
    error_reporting(0);
    ShowTemplate();
    exit();
}

/**
 * Receives the callback from the set_error_handler
 *
 * @param $errno        integer
 * @param $errorMessage string
 * @param $filename     string
 * @param $lineNumber   integer|string
 * @param $vars         mixed
 */
function engine_error_handling($errno, $errorMessage, $filename, $lineNumber, $vars)
{
    handle_error($errorMessage, $filename, $lineNumber, debug_backtrace());
}

/**
 * Returns a time difference in format days hh:mm:ss
 *
 * @param $startTime      long
 * @param $endTime        long
 *                        optional, if left empty will use the current time
 * @param $reloadWhenZero bool
 *                        If set to true, when the timer reach 0 it will reload the page.
 *                        optional, if left empty will be true.
 *
 * @return string the time difference
 */
function TimeInterval($startTime, $endTime = null, $reloadWhenZero = true)
{
    global $webBaseDir, $content;

    static $timeIntervalId = 0;

    if ($timeIntervalId == 0) {
        Ajax::IncludeLib();
        $content['footerScript'] .= "<script src='{$webBaseDir}js/time_interval.js'></script>";
        $content['footerJS'] .= "intervalTextDays=unescape('" . rawurlencode(Translate("days")) . "');\n";
        $content['footerJS'] .= "intervalTextDay=unescape('" . rawurlencode(Translate("day")) . "');\n";
        $content['footerJS'] .= "intervalTextHours=unescape('" . rawurlencode(Translate("hours")) . "');\n";
        $content['footerJS'] .= "intervalTextHour=unescape('" . rawurlencode(Translate("hour")) . "');\n";
        $content['footerJS'] .= "intervalTextMinutes=unescape('" . rawurlencode(Translate("minutes")) . "');\n";
        $content['footerJS'] .= "intervalTextMinute=unescape('" . rawurlencode(Translate("minute")) . "');\n";
        $content['footerJS'] .= "intervalTextSeconds=unescape('" . rawurlencode(Translate("seconds")) . "');\n";
        $content['footerJS'] .= "intervalTextSecond=unescape('" . rawurlencode(Translate("second")) . "');\n";
    }

    if ($endTime == null) {
        $endTime = time();
    }

    $res = "<span id='diff_time_$timeIntervalId'>";
    $diff = $endTime - $startTime;
    // Add one sec to be sure to not reload before time.
    $diff++;
    $days = floor($diff / 86400.0);

    if ($days > 0) {
        $diff = $diff % 86400;
        $res .= $days . " " . ($days > 1 ? Translate("days") : Translate("day")) . " ";
    }
    $hours = floor($diff / 3600);
    $diff = $diff % 3600;
    $min = floor($diff / 60);
    $diff = $diff % 60;
    $secs = $diff;

    if ($hours > 0) {
        $res .= $hours . " " . ($hours > 1 ? Translate("hours") : Translate("hour")) . " ";
    }
    if ($min > 0) {
        $res .= $min . " " . ($min > 1 ? Translate("minutes") : Translate("minute")) . " ";
    }
    if ($secs > 0) {
        $res .= $secs . " " . ($secs > 1 ? Translate("seconds") : Translate("second")) . " ";
    }
    $res .= "</span>";

    $content['footerJS'] .= "intervalGoalTime[$timeIntervalId]=" . ($endTime - $startTime) . ";\n";
    $content['footerJS'] .= "intervalGoalReload[$timeIntervalId]=" . ($reloadWhenZero ? "true" : "false") . ";\n";
    $timeIntervalId++;
    return $res;
}

/**
 * This function is used by the PrettyMessage function and will work ONLY if the
 * match doesn't contain the server name or its IP.
 *
 * @param $matches array
 *
 * @return string
 */
function PrettyMessageImageReplace($matches)
{
    if (strpos(strtolower($matches[2]), strtolower($_SERVER['SERVER_NAME'])) !== false) {
        return $matches[0];
    }
    if (strpos(strtolower($matches[2]), strtolower($_SERVER['SERVER_ADDR'])) !== false) {
        return $matches[0];
    }
    return "{$matches[1]}<a href='{$matches[2]}' target='_blank'><img src='{$matches[2]}' border='2' width='64' height='64'></a>{$matches[5]}";
}

/**
 * This function is used by the PrettyMessage function and will work ONLY if the
 * match doesn't contain the server name or its IP.
 *
 * @param $matches array
 *
 * @return string
 */
function PrettyMessageLinkReplace($matches)
{
    if (strpos(strtolower($matches[2]), strtolower($_SERVER['SERVER_NAME'])) !== false) {
        return $matches[0];
    }
    if (strpos(strtolower($matches[2]), strtolower($_SERVER['SERVER_ADDR'])) !== false) {
        return $matches[0];
    }
    return "{$matches[1]}[<a href='{$matches[2]}{$matches[3]}' target='_blank'>{$matches[2]} ...</a>]";
}

/**
 * Parse a message and add smilies, links and image links.
 *
 * @param $source string
 *
 * @return string
 */
function PrettyMessage($source)
{
    global $baseDir, $webBaseDir;

    $source = htmlentities($source);

    include "$baseDir/smilies/smilies.php";
    foreach ($smilies as $key => $val) {
        $source = str_replace($key, "<img src='{$webBaseDir}smilies/$val.gif'>", $source);
    }

    $source = preg_replace_callback('/(^|[^' . "'" . '])(http(|s):\/\/[a-zA-Z0-9\/\-\+:\.\?=_\&\#\;\%\,]*(\.jpg|\.jpeg|\.gif|\.png))($|[^' . "'" . '])/',
        "PrettyMessageImageReplace", $source);
    $source = preg_replace_callback('/(^|\s|\>)(http[s]{0,1}:\/\/[a-zA-Z0-9\/\-\+:\.\?=_\&\#\;\%\,]{1,30})([a-zA-Z0-9\/\-\+:\.\?=_\&\#\;\%\,]*)/',
        "PrettyMessageLinkReplace", $source);

    // Allows modules to extend this feature
    global $sourceText;
    $sourceText = $source;
    RunHook("pretty_message.php", "sourceText");
    return nl2br($sourceText);
}

/**
 * Gets all the files and the contents in an array
 *
 * @param $moduleName string
 *
 * @return array
 */
function GetModuleCode($moduleName, $type = "module")
{
    global $baseDir, $allModules;
    $dir = "";

    $data = array();
    if ($type == "template") {
        $files = scandir("$baseDir/templates");
        $templates = array();
        foreach ($files as $i) {
            if ($i[0] == ".") {
                continue;
            }
            $templates[] = $i;
        }

        if (!in_array($moduleName, $templates)) {
            return null;
        }

        $dir = "$baseDir/templates/$moduleName";
    } else if ($type == "fonts") {
        $files = scandir("$baseDir/images/fonts");
        $templates = array();
        foreach ($files as $i) {
            if ($i[0] == ".") {
                continue;
            }
            $templates[] = $i;
        }

        if (!in_array($moduleName, $templates)) {
            return null;
        }

        $dir = "$baseDir/images/fonts/$moduleName";
    } else {
        if (!in_array($moduleName, $allModules)) {
            return null;
        }
        $dir = "$baseDir/modules/$moduleName";
    }

    $todo = scandir($dir);
    while (count($todo) > 0) {
        $f = array_pop($todo);
        if ($f == "." || $f == "..") {
            continue;
        }
        if ($f == ".svn") {
            continue;
        }
        if ($f == "module.lock") {
            continue;
        }
        if (strpos($f, ".psd") !== false) {
            continue;
        }
        if (is_dir("$dir/$f")) {
            $data[$f] = null;
            $sub = scandir("$dir/$f");
            foreach ($sub as $i) {
                if ($i == "." || $i == "..") {
                    continue;
                }
                if ($i == ".svn") {
                    continue;
                }
                if ($i == "module.lock") {
                    continue;
                }
                if (strpos($i, ".psd") !== false) {
                    continue;
                }
                $todo[] = "$f/$i";
            }
        } else {
            $data[$f] = file_get_contents("$dir/$f");
        }
    }

    return $data;
}

/**
 * Recursively delete all files and directory.
 * Can be really dangerous!
 *
 * @param $dirName string
 */
function CleanDirectory($dirName)
{
    global $baseDir;

    $dirName = str_replace("\\", "/", $dirName);
    $path = explode("/", $dirName);
    // Checks that we do not contain a . or ..
    if (in_array(".", $path) || in_array("..", $path)) {
        throw new Exception("Invalid path");
    }

    // Checks that only modules directory are touched.
    if (strncmp($baseDir, $dirName, strlen($baseDir)) != 0) {
        throw new Exception("Invalid path");
    }

    if (!file_exists($dirName)) {
        return;
    }
    if (!is_dir($dirName)) {
        unlink($dirName);
        return;
    }

    $todo = scandir($dirName);
    foreach ($todo as $t) {
        if ($t == "." || $t == "..") {
            continue;
        }
        if (is_dir("$dirName/$t")) {
            CleanDirectory("$dirName/$t");
        } else {
            unlink("$dirName/$t");
        }
    }
    rmdir($dirName);
}

/**
 * Stores a module data
 *
 * @param $moduleName string
 * @param $moduleData array
 *
 * @throws Exception
 */
function StoreModule($moduleName, $moduleData, $type)
{
    global $baseDir;

    if (!in_array($type, array("module", "template", "fonts"))) {
        throw new Exception("Wrong package type.");
    }

    if (preg_match("/^[a-z0-9_\\-]+\$/", $moduleName) != 1) {
        throw new Exception("Module name is invalid");
    }

    if ($type == "template" && !is_writable("$baseDir/templates")) {
        throw new Exception("Template directory is not writtable");
    }
    if ($type == "module" && !is_writable("$baseDir/modules")) {
        throw new Exception("Module directory is not writtable");
    }
    if ($type == "fonts" && !is_writable("$baseDir/images/fonts")) {
        throw new Exception("Fonts directory is not writtable");
    }

    if ($type == "template") {
        $dir = "$baseDir/templates/$moduleName";
    } else if ($type == "fonts") {
        $dir = "$baseDir/images/fonts/$moduleName";
    } else {
        $dir = "$baseDir/modules/$moduleName";
    }

    // Remove the existing files
    if (file_exists($dir)) {
        CleanDirectory($dir);
    }

    // Create the directory
    @mkdir($dir, 0777, true);

    foreach ($moduleData as $key => $val) {
        $path = explode("/", $key);
        if (in_array(".", $path) || in_array("..", $path)) {
            throw new Exception("Module path seems invalid");
        }

        if ($val == null) {
            @mkdir("$dir/$key", 0777, true);
        } else {
            file_put_contents("$dir/$key", $val);
        }
    }
}

/**
 * Stores and install or upgrade a module
 *
 * @param $moduleName string
 * @param $moduleData array
 *
 * @return boolean returns true if all went fine.
 */
function StoreInstallModule($moduleName, $moduleData, $type)
{
    global $db;
    $isOk = true;

    // Checks if there is a config.xml inside the module
    if (isset($moduleData["config.xml"])) {
        $doc = new XMLReader();
        // Loads the XML from the string
        $doc->XML($moduleData["config.xml"]);

        // Extract the requirements from the config.xml
        $requirements = array();
        while ($doc->read()) {
            if ($doc->nodeType == XMLReader::END_ELEMENT) {
                continue;
            }
            if ($doc->name == "requires") {
                $requirements[] = array(
                    "module" => $doc->getAttribute("module"),
                    "version" => $doc->getAttribute("version")
                );
            }
        }
        $doc->close();

        // We do have requirements, let's check if we meet them.
        if (count($requirements) > 0) {
            $result = "";
            foreach ($requirements as $i) {
                // Compare the versions of the existing (installed) modules and what the module requires.
                if (VersionCompare(GetModuleVersion($i['module']), $i['version']) < 0) {
                    $result .= "Requires {$i['module']} with version {$i['version']} or above.<br>";
                }
            }
            if ($result != "") {
                ErrorMessage($result, false);
                return false;
            }
        }
    }

    try {
        StoreModule($moduleName, $moduleData, $type);
    } catch (Exception $ex) {
        ErrorMessage($ex->getMessage(), false);
        return false;
    }

    $result = $db->Execute("select version from modules where name = ?", $moduleName);
    $version = null;
    if (!$result->EOF) {
        $version = $result->fields[0];
    }
    $result->Close();

    if ($version == null) {
        $isOk = InstallModule($db, $moduleName);
        RegisterModuleVariables($moduleName);
    } else {
        $isOk = UpgradeModule($db, $moduleName, $version);
        RegisterModuleVariables($moduleName);
    }
    return $isOk;
}

/**
 * Transforms the string or unix timestamp in a date string.
 *
 * @param $data mixed
 *              if left empty uses the current time stamp.
 *
 * @return string
 */
function FormatDate($data = null)
{
    if ($data == null) {
        return date(GetConfigValue("dateFormat", ""), time());
    } else if (is_integer($data)) {
        return date(GetConfigValue("dateFormat", ""), $data);
    } else {
        return date(GetConfigValue("dateFormat", ""), strtotime($data));
    }
}

/**
 * Transforms the string or unix timestamp in a date string in short format.
 *
 * @param $data mixed
 *              if left empty uses the current time stamp.
 *
 * @return string
 */
function FormatShortDate($data = null)
{
    if ($data == null) {
        return date(GetConfigValue("shortDateFormat", ""), time());
    } else if (is_integer($data)) {
        return date(GetConfigValue("shortDateFormat", ""), $data);
    } else {
        return date(GetConfigValue("shortDateFormat", ""), strtotime($data));
    }
}

/**
 * Format a number to string in a readable format.
 *
 * @param $number  double
 * @param $decimal integer
 *
 * @return string
 */
function FormatNumber($number, $decimal = 0)
{
    return number_format($number, $decimal, GetConfigValue("defaultDecimalSeparator", ""),
        GetConfigValue("defaultThousandSeparator", ""));
}

/**
 * Checks if a specified user is online or not
 *
 * @param mixed $user
 *
 * @return boolean returns true if the user is online
 */
function IsPlayerOnline($user)
{
    global $db;

    $uid = FindUser($user);
    if ($uid == null) {
        return false;
    }
    $result = $db->Execute("select online from users where id = ?", $uid);
    $res = ($result->fields[0] == "yes");
    $result->Close();
    return $res;
}

InitModules();
