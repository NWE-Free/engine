<?php
// Not an admin? Go away!
if (!IsAdmin()) {
    header("Location: index.php");
    return;
}

function PrepareCell($source)
{
    return "<div style='height: 16px; overflow: hidden;'>" . htmlentities(preg_replace("/(\\S)([;\\.,])(\\S)/",
        "\$1\$2 \$3", $source)) . "</div>";
}

function DisplayTable($action, $value, $param)
{
    global $db, $webBaseDir;
    
    $table = $_SESSION["admin_edit_table"];
    $displayColumns = $_SESSION["admin_edit_table_cols"];
    $primaryKey = $_SESSION["admin_edit_table_pk"];
    $primaryKeyPos = $_SESSION["admin_edit_table_pk_pos"];
    $lookups = $_SESSION["admin_edit_table_lookup"];
    if (isset($_SESSION["admin_edit_table_page"])) {
        $page = $_SESSION["admin_edit_table_page"];
    } else {
        $page = 0;
    }
    
    $result = $db->Execute("select * from $table limit 0,1");
    $knownCols = array_keys($result->getColumnsNames());
    
    switch ($action) {
        case "sort":
            if (in_array($value, $knownCols)) {
                if ($_SESSION["admin_edit_table_sort"] == $value && $_SESSION["admin_edit_table_sort_order"] == "ASC") {
                    $_SESSION["admin_edit_table_sort_order"] = "DESC";
                } else {
                    $_SESSION["admin_edit_table_sort_order"] = "ASC";
                }
                
                $_SESSION["admin_edit_table_sort"] = $value;
            }
            $page = 0;
            break;
        case "filter":
            if (in_array($value, $knownCols)) {
                if (trim($param) == "") {
                    unset($_SESSION["admin_edit_table_filters"][$value]);
                } else {
                    $_SESSION["admin_edit_table_filters"][$value] = $param;
                }
            }
            $page = 0;
            break;
        case "prev":
            $page--;
            if ($page < 0) {
                $page = 0;
            }
            break;
        case "next":
            $page++;
            break;
    }
    
    $_SESSION["admin_edit_table_page"] = $page;
    
    if (!isset($_SESSION["admin_edit_table_sort_order"])) {
        $_SESSION["admin_edit_table_sort_order"] = "ASC";
    }
    
    $query = "select {$displayColumns[$table]} from $table";
    $first = true;
    $values = array();
    foreach ($_SESSION["admin_edit_table_filters"] as $key => $val) {
        if ($first) {
            $query .= " where ";
        } else {
            $query .= " and ";
        }
        if (isset($lookups[$key])) {
            $possibleVals = array();
            foreach ($lookups[$key] as $k => $v) {
                if (strpos(strtolower($v), strtolower($val)) !== false) {
                    $possibleVals[] = $k;
                }
            }
            if (count($possibleVals)) {
                $query .= " $key in (" . implode(",", $possibleVals) . ")";
            } // We found nothing, so make a false assert
            else {
                $query .= "1 = 2";
            }
        } else {
            $query .= " upper($key) like upper(?)";
            $values[] = "%$val%";
        }
        $first = false;
    }
    $query .= " order by " . $_SESSION["admin_edit_table_sort"] . " " . $_SESSION["admin_edit_table_sort_order"];
    $query .= " limit " . ($page * 20) . ",21";
    
    if (count($values) > 0) {
        $result = $db->Execute($query, $values);
    } else {
        $result = $db->Execute($query);
    }
    $fields = $result->FetchFields();
    $nbCols = count($fields);
    
    if ($action == null) {
        echo "<table class='plainTable'>";
        echo "<thead>";
        echo "<tr class='titleLine'>";
        echo "<td colspan='2'>&nbsp;</td>";
        foreach ($fields as $field) {
            echo "<td>";
            echo "<input type='text' id='empty_filter_{$field->name}' value='" . Translate("filter") . "' ";
            echo "style='background-Color: #E0E0E0; color: #808080; font-size: 10px;' onfocus='FilterGotFocus(\"{$field->name}\");'>";
            echo "<input type='text' id='filter_{$field->name}' style='font-size: 10px; display:none;'";
            if (isset($_SESSION["admin_edit_table_filters"][$field->name])) {
                echo " value='" . htmlentities($_SESSION["admin_edit_table_filters"][$field->name]) . "'";
            }
            echo " onkeypress='TableFilter(\"{$field->name}\");' onblur='FilterLostFocus(\"{$field->name}\");'>";
            echo "</td>";
        }
        echo "</tr>";
        echo "</thead>";
        echo "<tbody id='tableDisplay'>";
    }
    
    echo "<tr class='titleLine'>";
    echo "<td colspan='2'>&nbsp;</td>";
    foreach ($fields as $field) {
        echo "<td><a href='#' onclick='DisplayTable(\"sort\",\"{$field->name}\",\"\");return false;'>";
        if ($field->name == $_SESSION["admin_edit_table_sort"]) {
            if ($_SESSION["admin_edit_table_sort_order"] == "DESC") {
                echo "<img src='{$webBaseDir}modules/admin_edit_tables/desc.png' border='0'>";
            } else {
                echo "<img src='{$webBaseDir}modules/admin_edit_tables/asc.png' border='0'>";
            }
        } else {
            echo "<img src='{$webBaseDir}modules/admin_edit_tables/no_sort.png' border='0'>";
        }
        echo "&nbsp;{$field->name}";
        
        echo "</a></td>";
    }
    echo "</tr>";
    
    $row = 0;
    while (!$result->EOF) {
        if ($row > 20) {
            break;
        }
        if ($row % 2 == 0) {
            echo "<tr valign='top' class='evenLine'>";
        } else {
            echo "<tr valign='top' class='oddLine'>";
        }
        echo "<td width='1%'>";
        
        $key = "";
        foreach ($primaryKeyPos as $p) {
            if ($key != "") {
                $key .= "\n";
            }
            $key .= urlencode($result->fields[$p]);
        }
        $key = urlencode($key);
        
        LinkButton("Edit", "#", "TableEdit('$key');return false;");
        echo "</td>";
        echo "<td width='1%'>";
        LinkButton("Delete", "#",
            "if(confirm(unescape('" . rawurlencode(Translate("Are you sure you want to delete this row?")) . "'))){TableEditDelete('$key');}return false;");
        echo "</td>";
        for ($i = 0; $i < $nbCols; $i++) {
            // Is there a lookup? If yes show the linked value
            if (isset($lookups[$fields[$i]->name]) && isset($lookups[$fields[$i]->name][$result->fields[$i]])) {
                if ($result->fields[$i] == null) {
                    echo "<td>-&nbsp;NULL&nbsp;-</td>";
                } else {
                    echo "<td>" . PrepareCell($lookups[$fields[$i]->name][$result->fields[$i]]) . "</td>";
                }
            } // Show the stored value directly
            else {
                echo "<td>" . PrepareCell($result->fields[$i]) . "</td>";
            }
        }
        echo "</tr>";
        $result->MoveNext();
        $row++;
    }
    
    if ($page > 0 || $row > 20) {
        echo "<tr><td colspan='" . ($nbCols + 2) . "' align='center'>";
        if ($page > 0) {
            LinkButton("Previous", "#", "DisplayTable('prev','','');return false;");
        }
        if ($row > 20) {
            LinkButton("Next", "#", "DisplayTable('next','','');return false;");
        }
        echo "</td></tr>";
    }
    
    if ($action == null) {
        echo "</tbody>";
        echo "</table>";
    }
}

Ajax::RegisterFunction("DisplayTable", "tableDisplay");
if (Ajax::IsAjaxCallback()) {
    return;
}

// Checks all tables which are configured as editable
$allowedTables = array();
$tablesModules = array();
$specialEditor = array();
$displayColumns = array();

foreach ($modules as $module) {
    if (file_exists("$baseDir/modules/$module/config.xml")) {
        $doc = new XMLReader();
        $doc->open("$baseDir/modules/$module/config.xml");
        while ($doc->read()) {
            if ($doc->nodeType == XMLReader::END_ELEMENT) {
                continue;
            }
            if ($doc->name == "table") {
                $allowedTables[] = $doc->getAttribute("name");
                $tablesModules[$doc->getAttribute("name")] = $module;
                $specialEditor[$doc->getAttribute("name")] = $doc->getAttribute("special_editor");
                if ($doc->getAttribute("display_columns") == null) {
                    $displayColumns[$doc->getAttribute("name")] = "*";
                } else {
                    $displayColumns[$doc->getAttribute("name")] = $doc->getAttribute("display_columns");
                }
            }
        }
        $doc->close();
    }
}

if (isset($_POST["selectTable"])) {
    $_GET["table"] = $_POST["selectTable"];
    $table = $_GET["table"];
    unset($_SESSION["admin_edit_table"]);
    unset($_SESSION["admin_edit_table_cols"]);
    unset($_SESSION["admin_edit_table_pk"]);
    unset($_SESSION["admin_edit_table_pk_pos"]);
    unset($_SESSION["admin_edit_table_lookup"]);
    unset($_SESSION["admin_edit_table_sort"]);
    unset($_SESSION["admin_edit_table_sort_order"]);
    unset($_SESSION["admin_edit_table_filters"]);
    unset($_SESSION["admin_edit_table_page"]);
} // Get the table name to be edited
else if (isset($_GET["table"]) && count($_POST) == 0) {
    $table = $_GET["table"];
    unset($_SESSION["admin_edit_table"]);
    unset($_SESSION["admin_edit_table_cols"]);
    unset($_SESSION["admin_edit_table_pk"]);
    unset($_SESSION["admin_edit_table_pk_pos"]);
    unset($_SESSION["admin_edit_table_lookup"]);
    unset($_SESSION["admin_edit_table_sort"]);
    unset($_SESSION["admin_edit_table_sort_order"]);
    unset($_SESSION["admin_edit_table_filters"]);
    unset($_SESSION["admin_edit_table_page"]);
} else if (isset($_SESSION["admin_edit_table"])) {
    $_GET["table"] = $_SESSION["admin_edit_table"];
    $table = $_SESSION["admin_edit_table"];
} else if (isset($_GET["table"])) {
    $table = $_GET["table"];
}

// If not in the allowed one, refuse.
if (!in_array($table, $allowedTables)) {
    ErrorMessage("You are not allowed to edit this table.");
    return;
}

// Load the lookup values (foreign key defined in the config.xml)
$conditionWizard = array();
$actionWizard = array();
$lookups = array();
$lookupsNull = array();
$dom = new DOMDocument();
$dom->load("$baseDir/modules/{$tablesModules[$table]}/config.xml");
$xpath = new DOMXpath($dom);

if (GetConfigValue("useTableEditorWizards") != "false") {
    // Search all the table tags with name being the viewed table
    // and search inside any condition_wizard tag
    $elements = $xpath->query("//table[@name='$table']/condition_wizard");
    if (!is_null($elements)) {
        foreach ($elements as $element) {
            $conditionWizard[] = $element->attributes->getNamedItem('column')->nodeValue;
        }
    }
    
    // Search all the table tags with name being the viewed table
    // and search inside any action_wizard tag
    $elements = $xpath->query("//table[@name='$table']/action_wizard");
    if (!is_null($elements)) {
        foreach ($elements as $element) {
            $actionWizard[] = $element->attributes->getNamedItem('column')->nodeValue;
        }
    }
}

// Grab an empty line in order to get the definition
$result = $db->Execute("select * from $table where 1 = 0 limit 0,1");
$fields = $result->FetchFields();
$nbCols = count($fields);
$c = 0;
$result->Close();

// This table requires a special editor, let's run that one instead.
if (isset($specialEditor[$table]) && $specialEditor[$table] != null && $specialEditor[$table] != "") {
    include "$baseDir/modules/" . $tablesModules[$table] . "/" . $specialEditor[$table];
    return;
}

// Search the primary key (name and position)
// Composite primary keys are not currently supported!
$primaryKey = array();
$primaryKeyPos = array();
foreach ($fields as $field) {
    if ($field->flags & Resultset::$PRI_KEY_FLAG) {
        $primaryKey[] = $field->name;
        $primaryKeyPos[] = $c;
        $c++;
    }
}

// Search all the table tags with name being the viewed table
// and search inside any lookup tag
$elements = $xpath->query("//table[@name='$table']/lookup");
if (!is_null($elements)) {
    foreach ($elements as $element) {
        $column = $element->attributes->getNamedItem('column')->nodeValue;
        $foreign_table = $element->attributes->getNamedItem('table')->nodeValue;
        $foreign_key = $element->attributes->getNamedItem('key')->nodeValue;
        $foreign_display = $element->attributes->getNamedItem('display')->nodeValue;
        if ($element->attributes->getNamedItem('allow_null') != null && $element->attributes->getNamedItem('allow_null')->nodeValue == "true") {
            $lookupsNull[$column] = true;
        } else {
            $lookupsNull[$column] = false;
        }
        
        $sql = "select $foreign_key \"v1\", $foreign_display \"v2\" from $foreign_table order by v1";
        $result = $db->Execute($sql);
        if ($result === false) {
            ErrorMessage("Configuration invalid for the selected table.");
            echo $sql;
            return;
        }
        $lookups[$column] = array();
        while (!$result->EOF) {
            $lookups[$column][$result->fields[0]] = $result->fields[1];
            $result->MoveNext();
        }
        $result->Close();
    }
}

// We need to delete a row.
if (isset($_POST['delete'])) {
    global $demoEngine;
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
        return;
    }
    
    $vals = array();
    $_POST['delete'] = rawurldecode($_POST['delete']);
    foreach (explode("\n", $_POST['delete']) as $v) {
        $vals[] = urldecode($v);
    }
    $sql = "delete from $table where ";
    $isFirst = true;
    foreach ($primaryKey as $k) {
        if (!$isFirst) {
            $sql .= " and ";
        }
        $sql .= "$k = ?";
        $isFirst = false;
    }
    $db->Execute($sql, $vals);
    ResultMessage("Row deleted.");
} // Save the row edition
else if (isset($_POST['edit']) && isset($_POST['action']) && $_POST['action'] == 'save') {
    global $demoEngine;
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
        return;
    }
    
    $sql = "update $table set ";
    $where = "where ";
    $isFirst = true;
    $vals = array();
    $c = 0;
    $firstWhere = true;
    foreach ($fields as $field) {
        if ($field->flags & Resultset::$PRI_KEY_FLAG) {
            if (!$firstWhere) {
                $where .= " and ";
            }
            $where .= "{$field->name}=?";
            $firstWhere = false;
        }
        if ($field->flags & Resultset::$PRI_KEY_FLAG && count($primaryKey) == 1) {
        } else {
            if (!$isFirst) {
                $sql .= ", ";
            }
            $sql .= "{$field->name} = ?";
            $vals[] = $_POST["col_$c"];
            $isFirst = false;
        }
        $c++;
    }
    $sql = "$sql $where";
    foreach (explode("\n", $_POST['edit']) as $v) {
        $vals[] = urldecode($v);
    }
    if ($db->Execute($sql, $vals) === true) {
        ResultMessage("Row updated.");
    }
} // Show the row to edit
else if (isset($_POST['edit'])) {
    $sql = "select * from $table where ";
    $isFirst = true;
    foreach ($primaryKey as $key) {
        if (!$isFirst) {
            $sql .= " and ";
        }
        $sql .= "$key=?";
        $isFirst = false;
    }
    $vals = array();
    $_POST['edit'] = rawurldecode($_POST['edit']);
    foreach (explode("\n", $_POST['edit']) as $v) {
        $vals[] = urldecode($v);
    }
    $result = $db->Execute("$sql limit 0,1", $vals);
    if ($result->EOF) {
        ErrorMessage("Row doesn't exists");
        return;
    }
    echo "<form method='post' name='editRow'>";
    echo "<input type='hidden' name='edit' value='{$_POST['edit']}'>";
    echo "<input type='hidden' name='action' value='save'>";
    TableHeader("Edit Row");
    echo "<table class='plainTable'>";
    $c = 0;
    $colConditionWizard = array();
    $colActionWizard = array();
    foreach ($fields as $field) {
        echo "<tr valign='top'><td width='1%'><b>" . str_replace(" ", "&nbsp;", $field->name) . ":</b></td>";
        if ($field->flags & Resultset::$PRI_KEY_FLAG && count($primaryKey) == 1) {
            echo "<td>" . htmlentities($result->fields[$c]) . "</td></tr>";
        } else if (in_array($field->name, $conditionWizard)) {
            echo "<td><input type='hidden' name='col_$c' value='" . htmlentities($result->fields[$c]) . "' id='col_$c'><div id='cond_wizard_$c'></div></td>";
            $colConditionWizard[] = $c;
        } else if (in_array($field->name, $actionWizard)) {
            echo "<td><input type='hidden' name='col_$c' value='" . htmlentities($result->fields[$c]) . "' id='col_$c'><div id='act_wizard_$c'></div></td>";
            $colActionWizard[] = $c;
        } else if (isset($lookups[$field->name])) {
            echo "<td><select name='col_$c'>";
            if ($lookupsNull[$field->name] == true) {
                echo "<option value=\"\">- NULL -</option>";
            }
            foreach ($lookups[$field->name] as $key => $value) {
                if ($key == $result->fields[$c]) {
                    echo "<option selected value=\"" . htmlentities($key) . "\">$value</option>";
                } else {
                    echo "<option value=\"" . htmlentities($key) . "\">$value</option>";
                }
            }
            echo "</select></td>";
        } else if ($field->flags & Resultset::$ENUM_FLAG) {
            $r = $db->Execute("show columns from $table where field = ?", $field->name);
            $enum = explode(',', substr($r->fields[1], 5, strlen($r->fields[1]) - 6));
            $r->Close();
            
            echo "<td><select name='col_$c'>";
            foreach ($enum as $i) {
                $v = substr($i, 1, strlen($i) - 2);
                if ($v == $result->fields[$c]) {
                    echo "<option selected>$v</option>";
                } else {
                    echo "<option>$v</option>";
                }
            }
            echo "</select></td>";
        } else if ($field->length > 80) {
            echo "<td><textarea name='col_$c' rows='5'>" . htmlentities($result->fields[$c]) . "</textarea></td></tr>";
        } else {
            echo "<td><input type='text' name='col_$c' value=\"" . htmlentities($result->fields[$c]) . "\"></td></tr>";
        }
        $c++;
    }
    echo "</table>";
    TableFooter();
    echo "</form>";
    ButtonArea();
    SubmitButton("Save", "editRow");
    LinkButton("Cancel", "index.php?p={$_GET['p']}");
    EndButtonArea();
    $result->Close();
    
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
    return;
} // Save the newly created row
else if (isset($_GET['action']) && $_GET['action'] == 'add' && isset($_POST['action']) && $_POST['action'] == 'save') {
    global $demoEngine;
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
        return;
    }
    
    $isFirst = true;
    $sql_fields = "";
    $sql_values = "";
    $vals = array();
    
    $c = 0;
    foreach ($fields as $field) {
        if ($field->flags & Resultset::$AUTO_INCREMENT_FLAG) {
        } else {
            if (!$isFirst) {
                $sql_fields .= ", ";
                $sql_values .= ", ";
            }
            
            $sql_fields .= $field->name;
            $sql_values .= "?";
            
            $vals[] = $_POST["col_$c"];
            $isFirst = false;
        }
        $c++;
    }
    
    if ($db->Execute("insert into $table($sql_fields) values($sql_values)", $vals) === true) {
        ResultMessage("Row added.");
    }
} // Show the add row form
else if (isset($_GET['action']) && $_GET['action'] == 'add') {
    echo "<form method='post' name='addRow'>";
    echo "<input type='hidden' name='action' value='save'>";
    TableHeader("Add Row");
    echo "<table class='plainTable'>";
    $c = 0;
    $colConditionWizard = array();
    $colActionWizard = array();
    foreach ($fields as $field) {
        $colDefs = $db->LoadData("show columns from $table where field = ?", $field->name);
        $def = $colDefs['Default'];
        
        echo "<tr valign='top'><td width='1%'><b>" . str_replace(" ", "&nbsp;", $field->name) . ":</b></td>";
        if ($field->flags & Resultset::$AUTO_INCREMENT_FLAG) {
            echo "<td>Auto Increment</td></tr>";
        } else if ($field->flags & Resultset::$PRI_KEY_FLAG && $field->type == 3 && count($primaryKey) == 1) {
            $result = $db->Execute("select max({$primaryKey[0]}) from $table");
            $nextId = $result->fields[0] + 1;
            $result->Close();
            
            echo "<td><input type='text' name='col_$c' value=\"$nextId\"></td></tr>";
        } else if (in_array($field->name, $conditionWizard)) {
            echo "<td><input type='hidden' name='col_$c' value='' id='col_$c'><div id='cond_wizard_$c'></div></td>";
            $colConditionWizard[] = $c;
        } else if (in_array($field->name, $actionWizard)) {
            echo "<td><input type='hidden' name='col_$c' value='' id='col_$c'><div id='act_wizard_$c'></div></td>";
            $colActionWizard[] = $c;
        } else if (isset($lookups[$field->name])) {
            echo "<td><select name='col_$c'>";
            if ($lookupsNull[$field->name] == true) {
                echo "<option value=\"\">- NULL -</option>";
            }
            foreach ($lookups[$field->name] as $key => $value) {
                if ($key == $def) {
                    echo "<option value=\"" . htmlentities($key) . "\" selected>$value</option>";
                } else {
                    echo "<option value=\"" . htmlentities($key) . "\">$value</option>";
                }
            }
            echo "</select></td>";
        } else if ($field->flags & Resultset::$ENUM_FLAG) {
            $enum = explode(',', substr($colDefs['Type'], 5, strlen($colDefs['Type']) - 6));
            
            echo "<td><select name='col_$c'>";
            foreach ($enum as $i) {
                $v = substr($i, 1, strlen($i) - 2);
                if ($v == $def) {
                    echo "<option selected>$v</option>";
                } else {
                    echo "<option>$v</option>";
                }
            }
            echo "</select></td>";
        } else if ($field->length > 80) {
            echo "<td><textarea name='col_$c' rows='5'>$def</textarea></td></tr>";
        } else {
            echo "<td><input type='text' name='col_$c' value=\"$def\"></td></tr>";
        }
        $c++;
    }
    echo "</table>";
    TableFooter();
    echo "</form>";
    ButtonArea();
    SubmitButton("Save", "addRow");
    LinkButton("Cancel", "index.php?p={$_GET['p']}");
    EndButtonArea();
    
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
    return;
}

// Get back the whole table
// We may need to add paging later on.

if (count($primaryKey) == 0) {
    ErrorMessage(Translate("Missing primary key on table %s", $table), false);
    return;
}

echo "<form method='post' name='frmSelectTable'>";
echo "<table class='plainTable'>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;", Translate("Switch to table")) . ":</b></td>";
echo "<td><select name='selectTable' onchange='document.forms[\"frmSelectTable\"].submit();'>";
foreach ($modules as $module) {
    if (file_exists("$baseDir/modules/$module/config.xml")) {
        $doc = new XMLReader();
        $doc->open("$baseDir/modules/$module/config.xml");
        while ($doc->read()) {
            if ($doc->nodeType == XMLReader::END_ELEMENT) {
                continue;
            }
            if ($doc->name == "table") {
                $t = $doc->getAttribute("name");
                if ($t == $table) {
                    echo "<option selected>$t</option>";
                } else {
                    echo "<option>$t</option>";
                }
            }
        }
        $doc->close();
    }
}
echo "</select><td></tr></table></form>";

echo "<form method='post' name='frmTableEdit'><input type='hidden' name='edit' id='frmEditRowId'></form>";
echo "<form method='post' name='frmTableDelete'><input type='hidden' name='delete' id='frmDeleteRowId'></form>";

$_SESSION["admin_edit_table"] = $table;
$_SESSION["admin_edit_table_cols"] = $displayColumns;
$_SESSION["admin_edit_table_pk"] = $primaryKey;
$_SESSION["admin_edit_table_pk_pos"] = $primaryKeyPos;
$_SESSION["admin_edit_table_lookup"] = $lookups;
if (!isset($_SESSION["admin_edit_table_sort"])) {
    $_SESSION["admin_edit_table_sort"] = $primaryKey[0];
    $_SESSION["admin_edit_table_sort_order"] = "ASC";
}
if (!isset($_SESSION["admin_edit_table_filters"])) {
    $_SESSION["admin_edit_table_filters"] = array();
}

ButtonArea();
LinkButton("Add new row", "index.php?p={$_GET['p']}&action=add");
if (in_array("admin_module_manager", $modules)) {
    LinkButton("Module Manager", "index.php?p=admin_module_manager");
}
LinkButton("Admin Panel", "index.php?p=admin_panel");
EndButtonArea();

TableHeader("Table $table");

DisplayTable(null, null, null);

TableFooter();
?>
<script>
    var filterTimeout = null;
    var currentFilter = null;
    
    function FilterLostFocus(filter) {
        if (document.getElementById('filter_' + filter).value == '') {
            document.getElementById('empty_filter_' + filter).style.display = '';
            document.getElementById('filter_' + filter).style.display = 'none';
        }
    }
    
    function FilterGotFocus(filter) {
        document.getElementById('empty_filter_' + filter).style.display = 'none';
        document.getElementById('filter_' + filter).style.display = '';
        document.getElementById('filter_' + filter).focus();
    }
    
    function TableFilter(filter) {
        if (filterTimeout != null && currentFilter != filter) {
            DoTableFilter();
            clearTimeout(filterTimeout);
        }
        
        if (filterTimeout != null)
            clearTimeout(filterTimeout);
        currentFilter = filter;
        filterTimeout = setTimeout('DoTableFilter()', 200);
    }
    
    function DoTableFilter() {
        DisplayTable("filter", currentFilter, document.getElementById('filter_' + currentFilter).value);
        filterTimeout = null;
    }
    
    function TableEditDelete(row) {
        //alert(row);
        document.getElementById('frmDeleteRowId').value = row;
        document.forms['frmTableDelete'].submit();
    }
    
    function TableEdit(row) {
        document.getElementById('frmEditRowId').value = row;
        document.forms['frmTableEdit'].submit();
    }
</script>