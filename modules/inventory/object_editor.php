<?php
/**
 * Special editor for the objects table.
 * Let you edit the object base attributes plus the one specific for the object type in one shot.
 */
if (isset($_GET["objType"]) && !isset($_POST["objType"])) {
    $_POST["objType"] = $_GET["objType"];
}

// Show all the object types
echo "<form method='post' name='frmObjType'>";
$result = $db->Execute("select id,name from object_types order by name");
echo "<select name='objType' style='width: 100%' onchange='document.forms[\"frmObjType\"].submit();'>";
foreach ($result as $row) {
    if (!isset($_POST["objType"])) {
        $_POST["objType"] = $row[0];
    }
    if ($row[0] == $_POST["objType"]) {
        echo "<option selected value='{$row[0]}'>{$row[1]}</option>";
    } else {
        echo "<option value='{$row[0]}'>{$row[1]}</option>";
    }
}
$result->Close();
echo "</select>";
echo "</form><br>";

$objType = $_POST["objType"];

// Base attributes valid for all the object types
$attributesNames = array(
    "id" => -1,
    "name" => -2,
    "description" => -3,
    "requirements" => -4,
    "durability" => -5,
    "price" => -6,
    "allow_fraction" => -7,
    "quest_item" => -8,
    "usage_label" => -9,
    "usage_code" => -10
);

// Grab the attributes for the selected object type
$attributes = array();
$result = $db->Execute("select id,name from object_types_attributes where object_type = ? order by name", $objType);
foreach ($result as $row) {
    $attributes[$row[0] + 0] = $row[1];
    $attributesNames[$row[1]] = $row[0] + 0;
}
$result->Close();

$knownFields = array();
foreach ($fields as $field) {
    $knownFields[$field->name] = $field;
}

// Deleting an object
if (isset($_GET["delete"])) {
    $db->Execute("delete from object_attributes where object_id = ?", $_GET["delete"]);
    $db->Execute("delete from objects where id = ?", $_GET["delete"]);
} else if (isset($_POST["action"]) && $_POST["action"] == "do_add") {
    $result = $db->Execute("select max(id) from objects");
    $id = $result->fields[0] + 1;
    $result->Close();
    
    $db->Execute("insert into objects(id,object_type, name, description, requirements, durability, price,
        allow_fraction, quest_item, usage_label, usage_code) values(?,?,?,?,?,?,?,?,?,?,?)", $id, $objType,
        $_POST["col_-2"], $_POST["col_-3"], $_POST["col_-4"], $_POST["col_-5"], $_POST["col_-6"], $_POST["col_-7"],
        $_POST["col_-8"], $_POST["col_-9"], $_POST["col_-10"]);
    foreach ($attributes as $key => $val) {
        $db->Execute("insert into object_attributes(object_id,attribute_id,value) values(?,?,?)", $id, $key,
            $_POST["col_$key"]);
    }
} else if (isset($_GET["action"]) && $_GET["action"] == "add") {
    $colConditionWizard = array();
    $colActionWizard = array();
    
    echo "<form method='post' name='frmAdd'>";
    echo "<input type='hidden' name='objType' value='$objType' />";
    echo "<input type='hidden' name='action' value='do_add' />";
    TableHeader("Insert Object");
    echo "<table class='plainTable'>";
    foreach ($attributesNames as $key => $val) {
        if ($key == "id") {
            continue;
        }
        $colDefs = $db->LoadData("show columns from $table where field = ?", $key);
        $def = $colDefs['Default'];
        
        echo "<tr><td width='1%'><b>$key</b>:</td>";
        if (in_array($key, $conditionWizard)) {
            echo "<td><input type='hidden' name='col_$val' value='' id='col_$val'><div id='cond_wizard_$val'></div></td>";
            $colConditionWizard[] = $val;
        } else if (in_array($key, $actionWizard)) {
            echo "<td><input type='hidden' name='col_$val' value='' id='col_$val'><div id='act_wizard_$val'></div></td>";
            $colActionWizard[] = $val;
        } else if ($val < 0 && $knownFields[$key]->flags & Resultset::$ENUM_FLAG) {
            $r = $db->Execute("show columns from $table where field = ?", $key);
            $enum = explode(',', substr($r->fields[1], 5, strlen($r->fields[1]) - 6));
            $r->Close();
            
            echo "<td><select name='col_$val'>";
            foreach ($enum as $i) {
                $v = substr($i, 1, strlen($i) - 2);
                if ($v == $def) {
                    echo "<option selected>$v</option>";
                } else {
                    echo "<option>$v</option>";
                }
            }
            echo "</select></td>";
        } else if ($val < 0 && $knownFields[$key]->length > 80) {
            echo "<td><textarea name='col_$val' rows='5'>$def</textarea></td></tr>";
        } else {
            echo "<td><input type='text' name='col_$val' value='" . htmlentities($def) . "'></td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    TableFooter();
    echo "</form>";
    
    if (count($colConditionWizard) > 0) {
        echo "<script>var conditionWizardColumns=[" . implode(",", $colConditionWizard) . "];</script>";
        echo "<script src='{$webBaseDir}modules/admin_edit_tables/known_conditions.php'></script>";
        echo "<script src='{$webBaseDir}modules/admin_edit_tables/condition_wizard.js'></script>";
    }
    if (count($colActionWizard) > 0) {
        echo "<script>var actionWizardColumns=[" . implode(",", $colActionWizard) . "];</script>";
        echo "<script src='{$webBaseDir}modules/admin_edit_tables/known_actions.php'></script>";
        echo "<script src='{$webBaseDir}modules/admin_edit_tables/action_wizard.js'></script>";
    }
    
    ButtonArea();
    SubmitButton("Save", "frmAdd");
    LinkButton("Cancel", "index.php?p={$_GET['p']}&table={$_GET['table']}&objType=$objType");
    EndButtonArea();
    return;
} else if (isset($_POST["action"]) && $_POST["action"] == "do_update") {
    $db->Execute("replace into objects(id,object_type, name, description, requirements, durability, price,
            allow_fraction, quest_item, usage_label, usage_code) values(?,?,?,?,?,?,?,?,?,?,?)", $_POST["id"], $objType,
        $_POST["col_-2"], $_POST["col_-3"], $_POST["col_-4"], $_POST["col_-5"], $_POST["col_-6"],
        $_POST["col_-7"], $_POST["col_-8"], $_POST["col_-9"], $_POST["col_-10"]);
    foreach ($attributes as $key => $val) {
        $db->Execute("replace into object_attributes(object_id,attribute_id,value) values(?,?,?)", $_POST["id"], $key,
            $_POST["col_$key"]);
    }
} else if (isset($_GET["edit"])) {
    $colConditionWizard = array();
    $colActionWizard = array();
    
    echo "<form method='post' name='frmAdd'>";
    echo "<input type='hidden' name='objType' value='$objType' />";
    echo "<input type='hidden' name='action' value='do_update' />";
    echo "<input type='hidden' name='id' value='{$_GET['edit']}' />";
    TableHeader("Insert Object");
    echo "<table class='plainTable'>";
    
    $mainData = $db->LoadData("select * from objects where id = ?", $_GET["edit"]);
    $data = array();
    $r2 = $db->Execute("select attribute_id, value from object_attributes where object_id = ?", $_GET['edit']);
    foreach ($r2 as $val) {
        $data[$val[0] + 0] = $val[1];
    }
    $r2->Close();
    
    foreach ($attributesNames as $key => $val) {
        if ($key == "id") {
            continue;
        }
        $def = "";
        if ($val < 0) {
            $def = $mainData[$key];
        } else if (isset($data[$val])) {
            $def = $data[$val];
        }
        
        echo "<tr><td width='1%'><b>$key</b>:</td>";
        if (in_array($key, $conditionWizard)) {
            echo "<td><input type='hidden' name='col_$val' value='" . htmlentities($def) . "' id='col_$val'><div id='cond_wizard_$val'></div></td>";
            $colConditionWizard[] = $val;
        } else if (in_array($key, $actionWizard)) {
            echo "<td><input type='hidden' name='col_$val' value='" . htmlentities($def) . "' id='col_$val'><div id='act_wizard_$val'></div></td>";
            $colActionWizard[] = $val;
        } else if ($val < 0 && $knownFields[$key]->flags & Resultset::$ENUM_FLAG) {
            $r = $db->Execute("show columns from $table where field = ?", $key);
            $enum = explode(',', substr($r->fields[1], 5, strlen($r->fields[1]) - 6));
            $r->Close();
            
            echo "<td><select name='col_$val'>";
            foreach ($enum as $i) {
                $v = substr($i, 1, strlen($i) - 2);
                if ($v == $def) {
                    echo "<option selected>$v</option>";
                } else {
                    echo "<option>$v</option>";
                }
            }
            echo "</select></td>";
        } else if ($val < 0 && $knownFields[$key]->length > 80) {
            echo "<td><textarea name='col_$val' rows='5'>$def</textarea></td></tr>";
        } else {
            echo "<td><input type='text' name='col_$val' value='" . htmlentities($def) . "'></td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    TableFooter();
    echo "</form>";
    
    if (count($colConditionWizard) > 0) {
        echo "<script>var conditionWizardColumns=[" . implode(",", $colConditionWizard) . "];</script>";
        echo "<script src='{$webBaseDir}modules/admin_edit_tables/known_conditions.php'></script>";
        echo "<script src='{$webBaseDir}modules/admin_edit_tables/condition_wizard.js'></script>";
    }
    if (count($colActionWizard) > 0) {
        echo "<script>var actionWizardColumns=[" . implode(",", $colActionWizard) . "];</script>";
        echo "<script src='{$webBaseDir}modules/admin_edit_tables/known_actions.php'></script>";
        echo "<script src='{$webBaseDir}modules/admin_edit_tables/action_wizard.js'></script>";
    }
    
    ButtonArea();
    SubmitButton("Save", "frmAdd");
    LinkButton("Cancel", "index.php?p={$_GET['p']}&table={$_GET['table']}&objType=$objType");
    EndButtonArea();
    return;
}

// Let's list all the objects of the selected type

ButtonArea();
LinkButton("Add new row", "index.php?p={$_GET['p']}&table={$_GET['table']}&action=add&objType=$objType");
EndButtonArea();

TableHeader("Object Editor");
echo "<table class='plainTable'>";
echo "<tr class='titleLine'>";
echo "<td>&nbsp;</td>";
echo "<td>&nbsp;</td>";

foreach ($attributesNames as $name => $id) {
    echo "<td>$name</td>";
}
echo "</tr>";

$result = $db->Execute("select id, name, description, requirements, durability, price,
        allow_fraction, quest_item, usage_label, usage_code from objects where object_type = ? order by name",
    $objType);
$l = 0;
foreach ($result as $row) {
    if ($l % 2 == 0) {
        echo "<tr class='evenLine'>";
    } else {
        echo "<tr class='oddLine'>";
    }
    
    echo "<td>";
    LinkButton("Edit", "index.php?p={$_GET['p']}&table=" . $_GET["table"] . "&objType=$objType&edit={$row[0]}");
    echo "</td><td>";
    LinkButton("Delete", "index.php?p={$_GET['p']}&table=" . $_GET["table"] . "&objType=$objType&delete={$row[0]}",
        "return confirm(unescape('" . rawurlencode(Translate("Are you sure you want to delete this row?")) . "'));");
    echo "</td>";
    
    foreach ($row as $val) {
        if ($val == null || trim($val) == "") {
            echo "<td>&nbsp;</td>";
        } else {
            echo "<td>" . PrepareCell($val) . "</td>";
        }
    }
    
    $data = array();
    $r2 = $db->Execute("select attribute_id, value from object_attributes where object_id = ?", $row[0]);
    foreach ($r2 as $val) {
        $data[$val[0] + 0] = $val[1];
    }
    $r2->Close();
    
    foreach ($attributes as $key => $val) {
        if (isset($data[$key])) {
            echo "<td>" . PrepareCell($data[$key]) . "</td>";
        } else {
            echo "<td>&nbsp;</td>";
        }
    }
    
    echo "</tr>";
    $l++;
}
$result->Close();

echo "</table>";
TableFooter();
