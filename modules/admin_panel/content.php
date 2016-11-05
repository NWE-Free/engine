<?php
if (!IsModerator()) {
    header("Location: index.php");
    return;
}

global $modules;
// Run a module admin panel
if (isset($_GET["a"]) && in_array($_GET["a"], $modules) && file_exists("$baseDir/modules/{$_GET['a']}/admin.php")) {
    include "$baseDir/modules/{$_GET['a']}/admin.php";
    return;
}

function HasModulesToUpgrade()
{
    if (CanPostToServer()) {
        // Retreive all modules information
        global $moduleInfo, $allModules, $baseDir;
        
        $moduleKeys = array();
        $moduleTables = array();
        foreach ($allModules as $m) {
            if (!file_exists("$baseDir/modules/$m/config.xml")) {
                continue;
            }
            $doc = new XMLReader();
            $doc->open("$baseDir/modules/$m/config.xml");
            $moduleKeys[$m] = array();
            $moduleTables[$m] = array();
            while ($doc->read()) {
                if ($doc->nodeType == XMLReader::END_ELEMENT) {
                    continue;
                }
                if ($doc->name == "module") {
                    $moduleInfo[$m] = array(
                        "version" => $doc->getAttribute("version"),
                        "author" => $doc->getAttribute("author"),
                        "description" => $doc->getAttribute("description")
                    );
                }
                if ($doc->name == "key" && ($doc->getAttribute("browsable") == null || $doc->getAttribute("browsable") == "true")) {
                    $moduleKeys[$m][] = $doc->getAttribute("name");
                }
                if ($doc->name == "table") {
                    $moduleTables[$m][] = $doc->getAttribute("name");
                }
            }
            $doc->close();
        }
        
        $mods = array();
        foreach ($moduleInfo as $key => $vals) {
            $mods[$key] = $vals["version"];
        }
        
        $res = PostMessageToServer("nwe.funmayhem.com", "/check_version.php", "mods=" . urlencode(serialize($mods)));
        $diffs = unserialize($res);
        
        return (count($diffs) > 0);
    }
    return false;
}

function HasAdditionalModules()
{
    global $allModules, $demoEngine, $engineLicenseKey, $db, $baseDir;
    
    if (!CanPostToServer()) {
        return false;
    }
    
    if ($engineLicenseKey != "-") {
        if (!(isset($demoEngine) && $demoEngine == true) && isset($_GET["install"])) {
            $data = file_get_contents("http://nwe.funmayhem.com/get_module.php?l=$engineLicenseKey&m=" . urlencode($_GET["install"]));
            $data = unserialize(gzuncompress($data));
            
            if (StoreInstallModule($data['name'], $data['data'], $data['type'])) {
                ResultMessage("Module correctly installed.");
            }
            
            CleanHookCache();
            $allModules = array();
            $files = scandir("$baseDir/modules");
            foreach ($files as $f) {
                if ($f[0] != ".") {
                    $allModules[] = $f;
                }
            }
        }
        
        if (isset($_GET["ignore"])) {
            $db->Execute("insert into module_manager_ignore(name) values(?)", $_GET["ignore"]);
        }
        
        $installedModules = $allModules;
        $result = $db->Execute("select name from module_manager_ignore");
        foreach ($result as $m) {
            $installedModules[] = $m[0];
        }
        $files = scandir("$baseDir/templates");
        foreach ($files as $f) {
            if ($f[0] != ".") {
                $installedModules[] = $f;
            }
        }
        $files = scandir("$baseDir/images/fonts");
        foreach ($files as $f) {
            if ($f[0] != ".") {
                $installedModules[] = $f;
            }
        }
        $r = PostMessageToServer("nwe.funmayhem.com", "/check_available.php",
            "l=$engineLicenseKey&list=" . urlencode(serialize($installedModules)));
        if ($r == "- INVALID -") {
            return;
        }
        
        if ($r == "") {
            return false;
        }
        $error_reporting = error_reporting(error_reporting() ^ E_NOTICE);
        restore_error_handler();
        $availableModules = unserialize($r);
        error_reporting($error_reporting);
        set_error_handler("engine_error_handling");
        if ($availableModules === false) {
            $availableModules = array();
        }
        return count($availableModules) > 0;
    }
    return false;
}

echo "<table class='plainTable'>";
echo "<tr valign='top'><td width='50%'>";

TableHeader("Engine Information");
echo "New Worlds Engine - &copy; FunMayhem.com - 2012-2015<br>";
echo Translate("Installed engine version: %s", GetModuleVersion("")) . "<br>";
if ($_SERVER["HTTP_HOST"] != "localhost" || GetConfigValue("workOffline", "admin_panel") == "false") {
    if (CheckLastVersion("") != GetModuleVersion("")) {
        echo "<b style='color: red;'>" . Translate("Latest version available: %s", CheckLastVersion("")) . "</b><br>";
    } else {
        echo Translate("Running with the latest version.") . "<br>";
    }
}
echo Translate("PHP version: %s", phpversion()) . "<br>";
$mysql = $db->LoadData("select version() version");
echo Translate("MySQL version: %s", $mysql['version']) . "<br>";
if ($_SERVER["HTTP_HOST"] != "localhost" || GetConfigValue("workOffline", "admin_panel") == "false") {
    global $engineLicenseKey;
    if (!CheckLicense($engineLicenseKey)) {
        ErrorMessage("License invalid. Please purchase a valid license at: <a href='http://nwe.funmayhem.com/'>New Worlds Engine</a>.<br>");
        TableFooter();
        return;
    }
    if ($engineLicenseKey == "-") {
        echo "<b style='color: red;'>" . Translate("Running on a free version.") . "</b><br>";
    } else {
        echo "<a href='http://nwe.funmayhem.com/verify.php?l=" . substr($engineLicenseKey, 0,
                10) . "' target='_blank'>" . Translate("License is valid and checked.") . "</a><br>";
    }
    
    if (HasAdditionalModules()) {
        if (file_exists("$baseDir/modules/admin_module_manager")) {
            echo "<a href='index.php?p=admin_module_manager'>" . Translate("Additional modules available") . "</a><br>";
        } else {
            echo "<b>" . Translate("Additional modules available") . "</b><br>";
        }
    }
    
    if (HasModulesToUpgrade()) {
        if (file_exists("$baseDir/modules/admin_module_manager")) {
            echo "<a href='index.php?p=admin_module_manager'>" . Translate("New module(s) version available.") . "</a><br>";
        } else {
            echo "<b>" . Translate("New module(s) version available.") . "</b><br>";
        }
    }
}

TableFooter();

echo "</td><td>";
echo "<style>
.news-date { display: block; font-weight: bold; text-align: center;}
.news-entry { margin-bottom: 5px; }
</style>";
TableHeader("NWE News:");
echo "<div style='height: 90px; overflow: auto;'>";
if ($_SERVER["HTTP_HOST"] != "localhost" || GetConfigValue("workOffline", "admin_panel") == "false") {
    readfile("http://nwe.funmayhem.com/news.php");
} else {
    echo "<b style='color: red;'>" . Translate("Offline mode, no checks on the server.") . "</b>";
}
echo "</div>";
TableFooter();
echo "</td></tr></table>";

$currentGroup = null;
$adminEntries = array();

global $adminEntries;
RunHook("admin_menu.php", "adminEntries");

MenuEntry::Sort($adminEntries);

$lastGroup = null;
foreach ($adminEntries as $entry) {
    if (!isset($entry->label)) {
        continue;
    }
    if ($entry->group != $lastGroup) {
        if ($currentGroup != null) {
            TableFooter();
        }
        TableHeader($entry->group);
        $currentGroup = $entry->group;
        $lastGroup = $entry->group;
    }
    
    if ($currentGroup == null) {
        TableHeader("Administration function");
        $currentGroup = "Administration function";
    }
    
    if (strncmp($entry->link, "index.php", 9) == 0) {
        echo "<span class='panelMenuEntry'><a href='{$entry->link}'>" . Translate($entry->label) . "</a></span>";
    } else if (file_exists("$baseDir/modules/{$entry->link}/admin.php")) {
        echo "<span class='panelMenuEntry'><a href='index.php?p=admin_panel&a={$entry->link}'>" . Translate($entry->label) . "</a></span>";
    } else {
        echo "<span class='panelMenuEntry'><a href='index.php?p={$entry->link}'>" . Translate($entry->label) . "</a></span>";
    }
}

if ($currentGroup != null) {
    TableFooter();
}
