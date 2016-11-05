<?php

if (!isset($_GET["p"])) {
    return;
}

if (!in_array($_GET["p"], $modules)) {
    return;
}
if (!file_exists("$baseDir/modules/{$_GET['p']}/config.xml")) {
    return;
}

function cmp_keys($a, $b)
{
    return strcasecmp($a, $b);
}

// Checks if the current module is an admin medule.
$doc = new XMLReader();
$doc->open("$baseDir/modules/{$_GET['p']}/config.xml");
$isAdminModule = false;
while ($doc->read()) {
    if ($doc->nodeType == XMLReader::END_ELEMENT) {
        continue;
    }
    if ($doc->name == "module") {
        $isAdminModule = ($doc->getAttribute("type") == "admin");
        break;
    }
}
$doc->close();

// If not then leave
if (!$isAdminModule) {
    return;
}

global $content, $webBaseDir, $allModules, $moduleInfo, $baseDir;

$current = ob_get_clean();
ob_start();

$moduleKeys = array();
$allKeys = array();
$moduleTables = array();
$allTables = array();
$modulesToCheck = $allModules;
$modulesToCheck[] = ".";
foreach ($modulesToCheck as $m) {
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
            $allKeys[$doc->getAttribute("name")] = $m;
        }
        if ($doc->name == "table") {
            $moduleTables[$m][] = $doc->getAttribute("name");
            $allTables[$doc->getAttribute("name")] = $m;
        }
    }
    $doc->close();
}

echo "</td><td><a href='#' onclick='expandContractSideMenu();return false;'><img src='images/side_contract.png' id='adminSideSeparator' style='margin: 5px;' border='0'></a></td>";
echo "<td>";
echo "<div id='adminSideMenuContent' style='width: 180px; overflow: hidden;'>";
TableHeader("Admin Quick Links");

global $menuEntries;
$menuEntries = array();
RunHook("admin_quick_link.php", "menuEntries");
MenuEntry::Sort($menuEntries);

echo "<div><a href='#' onclick='expandContractSideMenuGroup(\"Links\");return false;'><img id='adminSideImageLinks' border='0' src='images/plus.png'></a><b>" . Translate("Links") . "</b></div>";
echo "<div id='adminSideGroupLinks' style='visibility: hidden; position: absolute; margin-left: 20px; overflow: hidden; height: 0px;'>";
foreach ($menuEntries as $entry) {
    if (strncmp($entry->link, "index.php", 9) == 0) {
        echo "<a href='{$entry->link}' id='" . MakeId("menu_",
                $entry->label) . "'>" . Translate($entry->label) . "</a><br>";
    } else {
        echo "<a href='index.php?p={$entry->link}' id='" . MakeId("menu_",
                $entry->label) . "'>" . Translate($entry->label) . "</a><br>";
    }
}
echo "</div>";

echo "<div><a href='#' onclick='expandContractSideMenuGroup(\"Keys\");return false;'><img id='adminSideImageKeys' border='0' src='images/plus.png'></a><b>" . Translate("Configuration keys") . "</b></div>";
echo "<div id='adminSideGroupKeys' style='visibility: hidden; position: absolute; margin-left: 20px; overflow: hidden; height: 0px;'>";
uksort($allKeys, "cmp_keys");
foreach ($allKeys as $k => $m) {
    echo "<a href='index.php?p=admin_edit_config&key=" . urlencode($k) . "'>" . ucwords(preg_replace("/([A-Z])([A-Z])/",
            '$1.$2',
            preg_replace("/([A-Z])([A-Z])/", '$1.$2', preg_replace("/([a-z])([A-Z])/", '$1 $2', $k)))) . "</a><br>";
}
echo "</div>";

echo "<div><a href='#' onclick='expandContractSideMenuGroup(\"Tables\");return false;'><img id='adminSideImageTables' border='0' src='images/plus.png'></a><b>" . Translate("Tables") . "</b></div>";
echo "<div id='adminSideGroupTables' style='visibility: hidden; position: absolute; margin-left: 20px; overflow: hidden; height: 0px;'>";
ksort($allTables);
foreach ($allTables as $k => $m) {
    echo "<a href='index.php?p=admin_edit_tables&table=" . urlencode($k) . "'>" . $k . "</a><br>";
}
echo "</div>";

TableFooter();
echo "</div>";
echo "</td></tr></table>";

echo "<script src='{$webBaseDir}js/ajax_helper.js'></script>";
echo "<script src='{$webBaseDir}modules/admin_side_panel/dyn.js'></script>";
$end = ob_get_clean();
$content['footerScript'] = $end;

ob_start();
echo "<table class='plainTable'>";
echo "<tr valign='top'><td width='100%'>";
echo $current;
