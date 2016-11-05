<?php
// Not an admin? Go away!
if (!IsSuperUser()) {
    header("Location: index.php");
    return;
}

if (isset($_POST['cmd']) && $_POST['cmd'] == "go") {
    global $demoEngine;
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
        return;
    }
    
    $t = scandir("$baseDir/templates");
    $templates = array();
    foreach ($t as $i) {
        if ($i[0] == ".") {
            continue;
        }
        $templates[] = $i;
    }
    
    $t = scandir("$baseDir/images/fonts");
    $fonts = array();
    foreach ($t as $i) {
        if ($i[0] == ".") {
            continue;
        }
        $fonts[] = $i;
    }
    
    if ($_POST['moduleType'] == "Module" && !in_array($_POST['moduleName'], $modules)) {
        header("Location: index.php");
        return;
    } else if ($_POST['moduleType'] == "Template" && !in_array($_POST['moduleName'], $templates)) {
        header("Location: index.php");
        return;
    } else if ($_POST['moduleType'] == "Fonts" && !in_array($_POST['moduleName'], $fonts)) {
        header("Location: index.php");
        return;
    }
    
    ob_end_clean();
    header("Content-type: application/binnary");
    header("Content-Disposition: filename=\"" . $_POST['moduleName'] . ".nwp\"");
    $package = array(
        "name" => $_POST['moduleName'],
        "data" => GetModuleCode($_POST['moduleName'], strtolower($_POST["moduleType"])),
        "type" => strtolower($_POST["moduleType"])
    );
    echo gzcompress(serialize($package), 9);
    exit();
} else {
    $_POST['moduleName'] = "";
}

TableHeader("Choose a module to export");
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

echo "<select name='moduleName'>";
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
    "document.forms['frmChooseModule'].cmd.value='go';document.forms['frmChooseModule'].submit();return false;");
EndButtonArea();
