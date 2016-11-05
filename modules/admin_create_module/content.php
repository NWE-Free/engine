<?php
// Not an admin? Go away!
if (!IsSuperUser()) {
    header("Location: index.php");
    return;
}

// Checks if the module already exists (to avoid to overwrite one)
if (isset($_POST["moduleName"]) && in_array($_POST["moduleName"], $allModules)) {
    ErrorMessage("This module already exists.");
} // Checks if the module name is valid.
else if (isset($_POST["moduleName"]) && preg_match("/^[a-z0-9_\\-]+\$/", $_POST["moduleName"]) != 1) {
    ErrorMessage("The module name is not valid.");
} // Ok there is a valid module name
else if (isset($_POST["moduleName"])) {
    global $demoEngine;
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
        return;
    }
    
    // Let's create the directory
    mkdir("$baseDir/modules/" . $_POST["moduleName"]);
    // Let's create the XML file for it
    $data = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<configuration xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"http://www.nw-engine.com/modules.xsd\">\r\n";
    $data .= "\t<module name=\"{$_POST['moduleDisplayName']}\" version=\"{$_POST['moduleVersion']}\" author=\"{$_POST['moduleAuthor']}\"/>\r\n";
    $lines = explode("\n", trim($_POST['userVars']));
    // Let's add all the user variables
    foreach ($lines as $l) {
        $p = trim($l);
        $data .= "\t<variable name=\"$p\"/>\r\n";
    }
    // Let's add all the configuration keys
    for ($i = 0; $i < 5; $i++) {
        if (trim($_POST["confKey$i"]) == "") {
            continue;
        }
        $data .= "\t<key name=\"" . $_POST["confKey$i"] . "\" description=\"" . $_POST["confDesc$i"] . "\" value=\"" . $_POST["confVal$i"] . "\"/>\r\n";
    }
    $data .= "</configuration>\r\n";
    // store the config.xml
    file_put_contents("$baseDir/modules/" . $_POST["moduleName"] . "/config.xml", $data);
    
    // Shall we make a public menu?
    if (trim($_POST["publicEntry"]) != "") {
        file_put_contents("$baseDir/modules/" . $_POST["moduleName"] . "/public_menu.php",
            "<?php\r\n\$menuEntries[]=new MenuEntry(\"{$_POST['publicEntry']}\");\r\n?>");
        file_put_contents("$baseDir/modules/" . $_POST["moduleName"] . "/public.php",
            "<?php\r\necho Translate(\"Will come soon.\");\r\n?>");
    }
    // Shall we make an in game menu?
    if (trim($_POST["insideEntry"]) != "") {
        file_put_contents("$baseDir/modules/" . $_POST["moduleName"] . "/menu.php",
            "<?php\r\n\$menuEntries[]=new MenuEntry(\"{$_POST['insideEntry']}\");\r\n?>");
        file_put_contents("$baseDir/modules/" . $_POST["moduleName"] . "/content.php",
            "<?php\r\necho Translate(\"Will come soon.\");\r\n?>");
    }
    // Shall we make an admin menu?
    if (trim($_POST["adminEntry"]) != "") {
        file_put_contents("$baseDir/modules/" . $_POST["moduleName"] . "/admin_menu.php",
            "<?php\r\n\$adminEntries[]=new MenuEntry(\"{$_POST['adminEntry']}\");\r\n?>");
        file_put_contents("$baseDir/modules/" . $_POST["moduleName"] . "/content.php",
            "<?php\r\n// Not an admin? Go away!\r\nif (! IsSuperUser())\r\n{\r\n\theader(\"Location: index.php\");\r\n\treturn;\r\n}\r\n\r\necho Translate(\"Will come soon.\");\r\n?>");
    }
    
    ResultMessage("Module created.");
    if (InstallModule($db, $_POST["moduleName"])) {
        ResultMessage("Module installed.");
        RegisterModuleVariables($_POST["moduleName"]);
    }
    
    CleanHookCache();
}

echo "<form method='post' name='frmCreateModule'>";

// General information about the module
TableHeader("General information");
echo "<table class='plainTable'>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;", Translate("Module name")) . ":</b></td>";
echo "<td><input type='text' name='moduleName' value='" . (isset($_POST['moduleName']) ? htmlentities($_POST['moduleName']) : "") . "'></td></tr>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;", Translate("Author")) . ":</b></td>";
echo "<td><input type='text' name='moduleAuthor' value='" . (isset($_POST['moduleAuthor']) ? htmlentities($_POST['moduleAuthor']) : $username) . "'></td></tr>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;", Translate("Display name")) . ":</b></td>";
echo "<td><input type='text' name='moduleDisplayName' value='" . (isset($_POST['moduleDisplayName']) ? htmlentities($_POST['moduleDisplayName']) : "") . "'></td></tr>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;", Translate("Version")) . ":</b></td>";
echo "<td><input type='text' name='moduleVersion' value='" . (isset($_POST['moduleVersion']) ? htmlentities($_POST['moduleVersion']) : "0.0") . "'></td></tr>";
echo "</table>";
TableFooter();

// Information about the public menu
TableHeader("Public menu");
echo Translate("Leave empty if not needed.");
echo "<table class='plainTable'>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;", Translate("Public menu entry")) . ":</b></td>";
echo "<td><input type='text' name='publicEntry' value='" . (isset($_POST['publicEntry']) ? htmlentities($_POST['publicEntry']) : "") . "'></td></tr>";
echo "</table>";
TableFooter();

// Information about the in game menu
TableHeader("In game menu");
echo Translate("Leave empty if not needed.");
echo "<table class='plainTable'>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;", Translate("In game menu entry")) . ":</b></td>";
echo "<td><input type='text' name='insideEntry' value='" . (isset($_POST['insideEntry']) ? htmlentities($_POST['insideEntry']) : "") . "'></td></tr>";
echo "</table>";
TableFooter();

// Information about the admin menu
TableHeader("Admin menu");
echo Translate("Leave empty if not needed.");
echo "<table class='plainTable'>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;", Translate("Admin panel entry")) . ":</b></td>";
echo "<td><input type='text' name='adminEntry' value='" . (isset($_POST['adminEntry']) ? htmlentities($_POST['adminEntry']) : "") . "'></td></tr>";
echo "</table>";
TableFooter();

// User variables
TableHeader("User variables required");
echo Translate("Leave empty if not needed.") . "<br>";
echo Translate("One variable per line") . ":<br>";
echo "<textarea rows='5' name='userVars'>" . (isset($_POST['userVars']) ? htmlentities($_POST['userVars']) : "") . "</textarea>";
TableFooter();

// Configuration keys
TableHeader("Configuration keys");
echo Translate("Leave empty if not needed.") . "<br>";
echo "<table class='plainTable'>";
echo "<tr><td><b>" . str_replace(" ", "&nbsp;", Translate("Name")) . ":</b></td>";
echo "<td><b>" . str_replace(" ", "&nbsp;", Translate("Default value")) . ":</b></td>";
echo "<td><b>" . str_replace(" ", "&nbsp;", Translate("Description")) . ":</b></td></tr>";
for ($i = 0; $i < 5; $i++) {
    echo "<tr><td><input type='text' name='confKey$i' value='" . (isset($_POST["confKey$i"]) ? htmlentities($_POST["confKey$i"]) : "") . "'></td>";
    echo "<td><input type='text' name='confVal$i' value='" . (isset($_POST["confVal$i"]) ? htmlentities($_POST["confVal$i"]) : "") . "'></td>";
    echo "<td><input type='text' name='confDesc$i' value='" . (isset($_POST["confDesc$i"]) ? htmlentities($_POST["confDesc$i"]) : "") . "'></td></tr>";
}
echo "</table>";
TableFooter();

echo "</form>";

ButtonArea();
SubmitButton("Create");
EndButtonArea();
