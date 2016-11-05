<?php
if (!IsSuperUser()) {
    return;
}
if (!CanPostToServer()) {
    ErrorMessage("The engine is not able to post back to the server.");
}

if (isset($_POST['cmd']) && $_POST["cmd"] == "send") {
    global $demoEngine;
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
        return;
    }
    
    $moduleName = $_POST['moduleName'];
    
    $description = "";
    $version = "1.0.0";
    if ($_POST["moduleType"] == "Module") {
        $version = GetModuleVersion($moduleName);
        $description = GetModuleDescription($moduleName);
        if (!in_array($_POST['moduleName'], $allModules)) {
            header("Location: index.php");
            return;
        }
    }
    
    global $engineLicenseKey;
    $message = array(
        "command" => "submitModule",
        "name" => $moduleName,
        "data" => GetModuleCode($moduleName, strtolower($_POST["moduleType"])),
        "type" => strtolower($_POST["moduleType"]),
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
} else {
    $_POST['moduleName'] = "";
}

TableHeader("Choose a module to submit");
echo "<form method='post' name='frmChooseModule'>";
echo "<input type='hidden' name='cmd' value=''>";
echo "<select name='moduleType' onchange='document.forms[\"frmChooseModule\"].submit();'>";
if (!isset($_POST["moduleType"]) || $_POST["moduleType"] == "Module") {
    echo "<option selected>Module</option>";
    echo "<option>Template</option>";
    echo "<option>Fonts</option>";
} else if (isset($_POST["moduleType"]) && $_POST["moduleType"] == "Template") {
    echo "<option>Module</option>";
    echo "<option selected>Template</option>";
    echo "<option>Fonts</option>";
} else {
    echo "<option>Module</option>";
    echo "<option>Template</option>";
    echo "<option selected>Fonts</option>";
}
echo "</select>";

echo "<select name='moduleName' size='10'>";
if (isset($_POST["moduleType"]) && $_POST["moduleType"] == "Template") {
    $files = scandir("$baseDir/templates");
    sort($files);
    foreach ($files as $i) {
        if ($i[0] == ".") {
            continue;
        }
        echo "<option>$i</option>";
    }
} else if (isset($_POST["moduleType"]) && $_POST["moduleType"] == "Fonts") {
    $files = scandir("$baseDir/images/fonts");
    sort($files);
    foreach ($files as $i) {
        if ($i[0] == ".") {
            continue;
        }
        echo "<option>$i</option>";
    }
} else {
    $m = $allModules;
    sort($m);
    
    foreach ($m as $i) {
        if ($_POST['moduleName'] == $i) {
            echo "<option selected>$i</option>";
        } else {
            echo "<option>$i</option>";
        }
    }
}
echo "</select>";
echo "</form>";
TableFooter();

ButtonArea();
LinkButton("Select", "#",
    "document.forms['frmChooseModule'].cmd.value='send';document.forms['frmChooseModule'].submit();return false;");
EndButtonArea();
