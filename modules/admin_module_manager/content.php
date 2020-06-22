<?php
// Not an admin? Go away!
if (!IsAdmin()) {
    header("Location: index.php");
    return;
}

// Upgrade directly from the marketplace.
if (isset($_GET["upg"])) {
    global $demoEngine, $engineLicenseKey;
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
    } else {
        $data = file_get_contents("http://nwe.funmayhem.com/get_module.php?l=$engineLicenseKey&m=" . urlencode($_GET["upg"]));
        // var_dump($data);
        if (strncmp($data, "Error", 5) == 0) {
            ErrorMessage($data);
        } else {
            $data = unserialize(gzuncompress($data));

            if (StoreInstallModule($data['name'], $data['data'], $data['type'])) {
                ResultMessage("Module correctly installed.");
                CleanHookCache();
            } else {
                ResultMessage("Error while installing the module.");
            }
        }
    }
} // Sending file back to the marketplace
else if (isset($_GET["snd"])) {
    global $demoEngine, $engineLicenseKey;
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
    } else {
        $moduleName = $_GET['snd'];

        $description = "";
        $version = "1.0.0";
        $version = GetModuleVersion($moduleName);
        $description = GetModuleDescription($moduleName);
        if (!in_array($_GET['snd'], $allModules)) {
            header("Location: index.php");
            return;
        }

        $message = array(
            "command" => "submitModule",
            "name" => $moduleName,
            "data" => GetModuleCode($moduleName, strtolower("module")),
            "type" => strtolower("module"),
            "description" => $description,
            "license" => $engineLicenseKey,
            "version" => $version
        );
        $result = SendRepositoryMessage($message);
        if (strncmp("error", $result['result'], 5) == 0) {
            ErrorMessage($result['result'], false);
        } else {
            ResultMessage($result['result'], false);
        }
    }
}

// Retreive all modules information
global $moduleInfo;
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

if ($_SERVER["HTTP_HOST"] != "localhost" || GetConfigValue("workOffline", "admin_panel") == "false") {
    // It's possible to post to the server, so let's grab back the info about which
    // module need to be updated.
    if (CanPostToServer()) {
        $mods = array();
        foreach ($moduleInfo as $key => $vals) {
            $mods[$key] = $vals["version"];
        }

        //$res = PostMessageToServer("nwe.funmayhem.com", "/check_version.php", "mods=" . urlencode(serialize($mods)));
        //$diffs = unserialize($res);
        $diffs = [];

        if (count($diffs) > 0) {
            TableHeader("Modules to upgrade");
            if (count($diffs) > 5) {
                echo "<div style='height: 150px; overflow: auto;'>";
            }
            echo "<table class='plainTable'>";
            echo "<tr class='titleLine'><td>&nbsp;</td><td>" . Translate("Module") . "</td><td>" . Translate("Your version") . "</td><td>" . Translate("Markeplace version") . "</td></tr>";
            $row = 0;
            foreach ($diffs as $k => $v) {
                if ($row % 2 == 0) {
                    echo "<tr class='evenLine'>";
                } else {
                    echo "<tr class='oddLine'>";
                }
                echo "<td>";
                if (isset($mods[$k]) && version_compare($mods[$k], $v) > 0) {
                    LinkButton("Send", "index.php?p=admin_module_manager&snd=" . rawurlencode($k),
                        "return confirm(unescape('" . rawurlencode(Translate("Are you sure you want to send %s?",
                            $k)) . "'));");
                } else {
                    LinkButton("Update", "index.php?p=admin_module_manager&upg=" . rawurlencode($k),
                        "return confirm(unescape('" . rawurlencode(Translate("Are you sure you want to upgrade %s?",
                            $k)) . "'));");
                }
                echo "</td>";
                echo "<td><a href='http://nwe.funmayhem.com/index.php?c=modules&mn=" . urlencode($k) . "' target='NW-SHOP'>$k</a></td>";
                echo "<td>" . (isset($mods[$k]) ? $mods[$k] : "-") . "</td>";
                echo "<td>$v</td>";
                echo "</tr>";
                $row++;
            }
            echo "</table>";
            if (count($diffs) > 5) {
                echo "</div>";
            }
            TableFooter();
        }
    }
}

// Checks if all the modules are writable
$allWritable = true;
foreach ($allModules as $m) {
    if (!is_writable("$baseDir/modules/$m")) {
        $allWritable = false;
        break;
    }
}

// Not the case, we could have issues
if (!$allWritable) {
    ErrorMessage("At least some of the module directories are not writable. Therefore you may not be able to lock / unlock them.");
}

// Let's unlock a module
if (isset($_GET['unlock'])) {
    global $demoEngine;
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
        return;
    }

    // Check that the module really is a module, and that it's not the locker
    // module itself.
    if (in_array($_GET['unlock'], $allModules) && $_GET['unlock'] != "admin_module_manager") {
        if (file_exists("$baseDir/modules/" . $_GET['unlock'] . "/module.lock")) {
            unlink("$baseDir/modules/" . $_GET['unlock'] . "/module.lock");
        }
        ResultMessage("Module unlocked");

        if (file_exists("$baseDir/modules/{$_GET['unlock']}/on_enable.php")) {
            include "$baseDir/modules/{$_GET['unlock']}/on_enable.php";
        }

        CleanHookCache();

        InitModules();
    }
} // Let's lock a module
else if (isset($_GET['lock'])) {
    global $demoEngine;
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
        return;
    }

    // Check that the module really is a module, and that it's not the locker
    // module itself.
    if (in_array($_GET['lock'], $allModules) && $_GET['lock'] != "admin_module_manager") {
        file_put_contents("$baseDir/modules/" . $_GET['lock'] . "/module.lock", time());
        ResultMessage("Module locked");

        if (file_exists("$baseDir/modules/{$_GET['lock']}/on_disable.php")) {
            include "$baseDir/modules/{$_GET['lock']}/on_disable.php";
        }

        CleanHookCache();

        InitModules();
    }
}

?>
    <script>
        if (typeof FindByAttribute != 'function') {
            function FindByAttribute(tag, attr, className) {
                var result = new Array();

                var elems = document.getElementsByTagName(tag);
                for (var i = 0; i < elems.length; i++) {
                    if (elems[i].getAttribute(attr) == className)// || elems[i].getAttribute("className") == className)
                    {
                        result[result.length] = elems[i];
                    }
                }
                return result;
            }
        }

        function expandModuleSection(rowId) {
            if (("" + document.getElementById('tree_node_' + rowId).src).indexOf('minus.png') != -1) {
                var list = FindByAttribute("tr", "row_attr", "row_" + rowId);
                for (var i = 0; i < list.length; i++) {
                    list[i].style.visibility = 'hidden';
                    list[i].style.position = 'absolute';
                }
                document.getElementById('tree_node_' + rowId).src = 'images/plus.png';
            }
            else {
                var list = FindByAttribute("tr", "row_attr", "row_" + rowId);
                for (var i = 0; i < list.length; i++) {
                    list[i].style.visibility = 'visible';
                    list[i].style.position = '';
                }
                document.getElementById('tree_node_' + rowId).src = 'images/minus.png';
            }
        }
    </script>
<?php

global $allModules, $demoEngine, $engineLicenseKey;

if ($_SERVER["HTTP_HOST"] != "localhost" || GetConfigValue("workOffline", "admin_panel") == "false") {
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
            $availableModules = array();
        } else {
            $error_reporting = error_reporting(error_reporting() ^ E_NOTICE);
            restore_error_handler();
            $availableModules = unserialize($r);
            error_reporting($error_reporting);
            set_error_handler("engine_error_handling");
            if ($availableModules === false) {
                $availableModules = array();
            }
        }

        if (count($availableModules) > 0) {
            TableHeader("Available Modules");
            if (count($availableModules) > 5) {
                echo "<div style='height: 150px; overflow: auto;'>";
            }
            echo "<table class='plainTable'>";
            echo "<tr class='titleLine'>";
            echo "<td colspan='2'>&nbsp;</td>";
            echo "<td>" . Translate('Name') . "</td>";
            echo "<td>" . Translate('Author') . "</td>";
            echo "<td>" . Translate('Type') . "</td>";
            echo "<td>" . Translate('Price') . "</td>";
            echo "</tr>";
            $row = 0;
            foreach ($availableModules as $m) {
                if ($row % 2 == 0) {
                    echo "<tr class='evenLine' valign='top'>";
                } else {
                    echo "<tr class='oddLine' valign='top'>";
                }

                echo "<td width='1%'>";
                LinkButton("Ignore", "index.php?p=admin_module_manager&ignore=" . urlencode($m[1]),
                    "return confirm(unescape('" . rawurlencode(Translate("Are you sure you don't want to see this module in this list anymore?")) . "'));");
                echo "</td>";

                if ($m[3] == 0) {
                    echo "<td width='1%'>";
                    LinkButton("Install", "index.php?p=admin_module_manager&install=" . urlencode($m[1]));
                    echo "</td>";
                } else {
                    echo "<td width='1%'>";
                    LinkButton("Check", "http://nwe.funmayhem.com/index.php?c=modules&m=" . $m[0], null, "NW-SHOP");
                    echo "</td>";
                }
                echo "<td><a href='http://nwe.funmayhem.com/index.php?c=modules&m={$m[0]}' target='NW-SHOP'>{$m[1]}</a></td>";
                echo "<td>{$m[5]}</td>";
                echo "<td>{$m[2]}</td>";
                if ($m[3] == 0) {
                    echo "<td align='right'><b>" . Translate("free") . "</b>&nbsp;</td>";
                } else {
                    echo "<td align='right'>{$m[3]}&nbsp;</td>";
                }
                echo "</tr>";
                $row++;
            }
            echo "</table>";
            if (count($availableModules) > 5) {
                echo "</div>";
            }
            TableFooter();
        }
    }
}

// Shows all the modules available
TableHeader("Modules");
echo "<table class='plainTable'>";
echo "<tr class='titleLine'>";
echo "<td>&nbsp;</td>";
// echo "<td>" . Translate('Check') . "</td>";
echo "<td>" . Translate('Name') . "</td>";
echo "<td>" . Translate('Status') . "</td>";
echo "<td>" . Translate('Description') . "</td>";
echo "<td>" . Translate('Author') . "</td>";
echo "<td>" . Translate('Version') . "</td>";
echo "</tr>";

$row = 0;
foreach ($allModules as $m) {
    if ($row % 2 == 0) {
        echo "<tr class='evenLine' valign='top'>";
    } else {
        echo "<tr class='oddLine' valign='top'>";
    }

    if (isset($moduleTables[$m]) && (count($moduleTables[$m]) > 0 || count($moduleKeys[$m]) > 0)) {
        echo "<td><a href='#' onclick='expandModuleSection($row);return false;'><img src='{$webBaseDir}images/plus.png' id='tree_node_$row' width='13' height='13' border='0'></a></td>";
    } else {
        echo "<td><img src='{$webBaseDir}images/separator.gif' width='13' height='13'></td>";
    }

    // echo "<td><a href='http://nwe.funmayhem.com/index.php?c=modules&mn="
    // . urlencode($m) . "' target='market'><img
    // src='http://nwe.funmayhem.com/check_version.php?m=" .
    // rawurlencode($m) .
    // "&v=" . GetModuleVersion($m) . "' border='0'></a></td>";

    if (file_exists("$baseDir/modules/$m/module.lock")) {
        echo "<td><a href='index.php?p=admin_module_manager&unlock=" . urlencode($m) . "' style='color: red; font-weight: bold;'>$m</a></td>";
        echo "<td><a href='index.php?p=admin_module_manager&unlock=" . urlencode($m) . "'>Enable</a></td>";
    } else {
        echo "<td><a href='index.php?p=admin_module_manager&lock=" . urlencode($m) . "' style='color: green; font-weight: bold;'>$m</a></td>";
        echo "<td><a href='index.php?p=admin_module_manager&lock=" . urlencode($m) . "'>Disable</a></td>";
    }
    echo "<td width='50%'>" . GetModuleDescription($m) . "</td>";
    echo "<td>" . GetModuleAuthor($m) . "</td>";
    echo "<td>" . GetModuleVersion($m) . "</td>";
    echo "</tr>";

    if (isset($moduleTables[$m]) && count($moduleTables[$m]) > 0) {
        foreach ($moduleTables[$m] as $t) {
            if ($row % 2 == 0) {
                echo "<tr class='evenLine' valign='top' row_attr='row_$row' style='visibility: hidden; position: absolute;'>";
            } else {
                echo "<tr class='oddLine' valign='top' row_attr='row_$row' style='visibility: hidden; position: absolute;'>";
            }
            echo "<td colspan='2'>&nbsp;</td>";
            echo "<td colspan='5'><img src='{$webBaseDir}modules/admin_module_manager/db.gif'>&nbsp;<a href='index.php?p=admin_edit_tables&table=" . urlencode($t) . "'>$t</a></td>";
            echo "</tr>";
        }
    }
    if (isset($moduleKeys[$m]) && count($moduleKeys[$m]) > 0) {
        foreach ($moduleKeys[$m] as $k) {
            if ($row % 2 == 0) {
                echo "<tr class='evenLine' valign='top' row_attr='row_$row' style='visibility: hidden; position: absolute;'>";
            } else {
                echo "<tr class='oddLine' valign='top' row_attr='row_$row' style='visibility: hidden; position: absolute;'>";
            }
            echo "<td colspan='2'>&nbsp;</td>";
            echo "<td colspan='5'><img src='{$webBaseDir}modules/admin_module_manager/key.gif'>&nbsp;<a href='index.php?p=admin_edit_config&key=" . urlencode($k) . "'>" .
                ucwords(preg_replace("/([A-Z])([A-Z])/", '$1.$2', preg_replace("/([A-Z])([A-Z])/", '$1.$2',
                    preg_replace("/([a-z])([A-Z])/", '$1 $2', $k)))) . "</a></td>";
            echo "</tr>";
        }
    }
    $row++;
}
echo "</table>";
TableFooter();
