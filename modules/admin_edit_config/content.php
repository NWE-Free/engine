<?php
// Not an admin? Go away!
if (!IsAdmin()) {
    header("Location: index.php");
    return;
}

global $demoEngine;

if (!isset($_GET['key'])) {
    return;
}
$keyToEdit = $_GET['key'];

$found = false;
$keyModule = "";
$description = "";
$value = "";
$options = null;

$allKeys = $modules;
$allKeys[] = "";
// Search the module which contains this configuration key.
foreach ($allKeys as $module) {
    if (file_exists("$baseDir/modules/$module/config.xml")) {
        $doc = new XMLReader();
        $doc->open("$baseDir/modules/$module/config.xml");
        while ($doc->read()) {
            if ($doc->nodeType == XMLReader::END_ELEMENT) {
                continue;
            }
            if ($doc->name == "key") {
                $name = $doc->getAttribute("name");
                // We found it, now let's load back the data
                if ($name == $keyToEdit) {
                    // The file is not writable, we will not be able to write it
                    // back.
                    if (!is_writable("$baseDir/modules/$module/config.xml") && !(isset($demoEngine) && $demoEngine === true)) {
                        ErrorMessage(Translate("The file %s is not writable. We cannot continue.",
                            "modules/$module/config.xml"), false);
                        return;
                    }
                    $keyModule = $module;
                    $description = $doc->getAttribute("description");
                    $options = $doc->getAttribute("options");
                    $value = GetConfigValue($keyToEdit, $keyModule);
                    $found = true;
                    break;
                }
            }
        }
        $doc->close();
    }
}

// Odd, we didn't found the module. Somebody is playing with us?
if (!$found) {
    return;
}

// We need to save the new value.
if (isset($_POST['value'])) {
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
    } else {
        SetConfigValue($keyToEdit, $_POST['value'], $keyModule);
        ResultMessage("Configuration saved.");
        $value = $_POST['value'];
    }
}

echo "<form method='post' name='saveKey'>";
TableHeader(Translate("Configuration key: %s", $keyToEdit), false);
echo "$description<br>";
echo "<table class='plainTable'>";
echo "<tr><td width='1%'>" . Translate("Value") . ":</td>";
if ($options == null) {
    echo "<td><input type='text' name='value' value='" . htmlentities($value) . "'></td></tr>";
} else {
    echo "<td>";
    echo "<select name='value'>";
    $opt = explode(",", $options);
    foreach ($opt as $i) {
        if ($value == $i) {
            echo "<option selected>$i</option>";
        } else {
            echo "<option>$i</option>";
        }
    }
    echo "</select>";
    echo "</td></tr>";
}
echo "</table>";
TableFooter();
echo "</form>";

ButtonArea();
SubmitButton("Save", "saveKey");
if (in_array("admin_module_manager", $modules)) {
    LinkButton("Module Manager", "index.php?p=admin_module_manager");
}
LinkButton("Admin Panel", "index.php?p=admin_panel");
EndButtonArea();
