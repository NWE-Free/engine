<?php

function sort_objects($a, $b)
{
    $v = strcmp($a->object_type, $b->object_type);
    if ($v != 0) {
        return $v;
    }
    return strcmp($a->name, $b->name);
}

// We are dropping items
if (isset($_GET['drop'])) {
    $obj = Item::GetInventoryObject($_GET['drop']);
    // Somebody tried to play here!
    if ($obj == null) {
        return;
    }
    
    // There is more than one item, thefore we need to ask how many to drop.
    if ($obj->quantity + 0 > 1 && !isset($_POST['confirm'])) {
        echo "<form method='post' name='dropItems'>";
        TableHeader("How many to drop");
        echo "<input type='text' name='confirm' value='{$obj->quantity}'>";
        TableFooter();
        
        ButtonArea();
        SubmitButton("Drop", "dropItems");
        LinkButton("Cancel", "index.php?p=inventory");
        EndButtonArea();
        return;
    } // Only one or we know how many.
    else {
        try {
            // User defined number
            if (isset($_POST['confirm'])) {
                Item::InventoryRemove($_GET['drop'], floatval($_POST['confirm']));
                if (function_exists("StorePersonalLog")) {
                    StorePersonalLog(Translate("Dropped %d %s", intval($_POST['confirm']), $obj->name));
                }
            } // Drop them all
            else {
                if (function_exists("StorePersonalLog")) {
                    StorePersonalLog(Translate("Dropped %d %s", $obj->quantity, $obj->name));
                }
                Item::InventoryRemove($_GET['drop'], $obj->quantity);
            }
            
            ResultMessage("Item(s) dropped.");
        } catch (Exception $ex) {
            ErrorMessage($ex->getMessage());
        }
    }
} // Equip an item
else if (isset($_GET['equip'])) {
    try {
        $obj = Item::GetInventoryObject($_GET['equip']);
        $oldObj = Item::Equip($_GET['equip'], $_GET['health']);
        if (function_exists("StorePersonalLog") && $oldObj != null) {
            StorePersonalLog(Translate("Unequipped %s", $oldObj->name));
        }
        ResultMessage("Item equipped.");
        if (function_exists("StorePersonalLog")) {
            StorePersonalLog(Translate("Equipped %s", $obj->name));
        }
    } catch (Exception $ex) {
        ErrorMessage($ex->getMessage());
    }
} // Un-Equip an item
else if (isset($_GET['unequip'])) {
    try {
        Item::GetInventoryObject($_GET['unequip']);
        $obj = Item::UnEquip($_GET['unequip']);
        ResultMessage("Item removed.");
        if (function_exists("StorePersonalLog") && $obj != null) {
            StorePersonalLog(Translate("Unequipped %s", $obj->name));
        }
    } catch (Exception $ex) {
        ErrorMessage($ex->getMessage());
    }
} // Use an item
else if (isset($_GET['use'])) {
    global $object;
    $object = Item::GetInventoryObject($_GET['use'], $_GET['health']);
    if ($object == null) {
        ErrorMessage("You don't have this item.");
    } else if ($object->usage_label == null) {
        ErrorMessage("You cannot use this item.");
    } else {
        NWEval("global \$object;\r\n" . $object->usage_code);
        if (function_exists("StorePersonalLog")) {
            StorePersonalLog(Translate("Used %s", $object->name));
        }
    }
}

echo "<table class='plainTable'>";
echo "<tr valign='top'><td width='50%'>";

// Shows the slots and current equipment on it
TableHeader("Equipped");
echo "<table class='plainTable'>";
echo "<tr class='titleLine'>";
echo "<td width='1%'>&nbsp;</td><td width='1%'>&nbsp;</td>";
echo "<td>Slot</td><td>Item</td><td>State</td>";
echo "</tr>";
$row = 0;
$objects = Item::AllEquiped();
foreach ($objects as $obj) {
    if ($row % 2 == 0) {
        echo "<tr class='evenLine'>";
    } else {
        echo "<tr class='oddLine'>";
    }
    // The slot is emtpy
    if ($obj->name == "") {
        echo "<td>&nbsp;</td>";
    } // The slot contains something we should give the option to un-equip
    else {
        echo "<td>";
        LinkButton("Un-Equip", "index.php?p=inventory&unequip=" . urlencode($obj->slot));
        echo "</td>";
    }
    echo "<td width='1%'>" . ($obj->image_file == null ? "&nbsp;" : "<img src='{$webBaseDir}modules/inventory/images/$obj->image_file'>") . "</td>";
    echo "<td>{$obj->slot}</td>";
    if ($obj->name == "") {
        echo "<td>&nbsp;</td>";
    } else {
        echo "<td>" . LinkItemDetails($obj->name, $obj->id) . "</td>";
    }
    echo "<td>{$obj->object_health}</td>";
    echo "</tr>";
    $row++;
}
echo "</table>";
TableFooter();

echo "</td><td>";

$typesToEquip = Item::ObjectTypesToEquip();

$objectType = "";
// Make the list of all the items in the inventory
$objects = Item::AllInventory();
usort($objects, "sort_objects");

TableHeader("Inventory");
echo "<table class='plainTable' cellpadding='0'>";
$row = 0;

$descLine = "<tr class='titleLine'>";
$descLine .= "<td width='1%'>&nbsp;</td>";
$descLine .= "<td width='1%'>&nbsp;</td>";
// Can items be dropped?
if (GetConfigValue("itemsCanBeDropped") == "true") {
    $descLine .= "<td width='1%'>&nbsp;</td>";
}
$descLine .= "<td width='1%'>&nbsp;</td>";
$descLine .= "<td>Name</td>";
$descLine .= "<td>Quantity</td>";
// Do items have an health?
if (GetConfigValue("itemsHealth") == "true") {
    $descLine .= "<td>Health</td>";
}
echo "</tr>";

$group_id = 0;
foreach ($objects as $obj) {
    if ($objectType != $obj->object_type) {
        if ($group_id != 0) {
            echo "</table></td></tr>";
        }
        $objectType = $obj->object_type;
        
        echo "<tr class='titleLine'>";
        echo "<td style='text-align: center; cursor: pointer;' onclick='InventoryGroupClick({$group_id});'>";
        echo "<img id='iv_img_{$group_id}' src='$webBaseDir/images/plus.png' align='left'>";
        
        echo Translate($objectType) . "</td></tr>";
        echo "<tr><td id='iv_grp_{$group_id}' style='visibility: hidden; display: none;'><table class='plainTable'>";
        echo $descLine;
        
        $group_id++;
    }
    
    if ($row % 2 == 0) {
        echo "<tr class='evenLine'>";
    } else {
        echo "<tr class='oddLine'>";
    }
    // Is the object of any kind we can equip?
    if (isset($typesToEquip[$obj->object_type_id])) {
        echo "<td>";
        LinkButton("Equip", "index.php?p=inventory&equip={$obj->id}&health={$obj->object_health}");
        echo "</td>";
    } // No then skip
    else {
        echo "<td>&nbsp;</td>";
    }
    if ($obj->usage_label != null) {
        echo "<td>";
        LinkButton($obj->usage_label, "index.php?p=inventory&use={$obj->id}&health={$obj->object_health}");
        echo "</td>";
    } else {
        echo "<td>&nbsp;</td>";
    }
    // Is the game configured to allow dropping items?
    if (GetConfigValue("itemsCanBeDropped") == "true") {
        // Is it a quest item (therefore we cannot drop)
        if ($obj->quest_item == 'yes') {
            echo "<td>&nbsp;</td>";
        } // Show the drop item link
        else {
            echo "<td>";
            LinkButton("Drop", "index.php?p=inventory&drop={$obj->id}&health={$obj->object_health}",
                ($obj->quantity + 0 == 1 ? "return confirm(unescape('" . rawurlencode(Translate("Are you sure you want to drop this item?")) . "'));" : null));
            echo "</td>";
        }
    }
    echo "<td width='1%'>" . ($obj->image_file == null ? "&nbsp;" : "<img src='{$webBaseDir}modules/inventory/images/$obj->image_file'>") . "</td>";
    echo "<td>" . LinkItemDetails($obj->name, $obj->id) . "</td>";
    echo "<td>{$obj->quantity}</td>";
    if (GetConfigValue("itemsHealth") == "true") {
        echo "<td>{$obj->object_health}</td>";
    }
    echo "</tr>";
    echo "</tr>";
    $row++;
}

if ($group_id != 0) {
    echo "</table></td></tr>";
}

echo "</table>";
TableFooter();

echo "</td></tr></table>";
echo "<script src='{$webBaseDir}js/ajax_helper.js'></script>";
echo "<script>var minusImage='{$webBaseDir}/images/minus.png';\nvar plusImage='{$webBaseDir}/images/minus.png';\n</script>";
?>
<script>
    var oldIvGroup = -1;
    
    function InventoryGroupClick(grpId) {
        var c = getCookie("settings");
        var data = new Object();
        if (c != null)
            data = jsDeserializer(c);
        
        var ivGrp = "";
        if (data['iv_grp'] != undefined && data['iv_grp'] != null)
            ivGrp = data['iv_grp'];
        ivGrp = pad("" + ivGrp, grpId + 1, '0');
        var div = null;
        
        div = document.getElementById('iv_grp_' + grpId);
        
        if (div.style.visibility == 'visible') {
            div.style.visibility = 'hidden';
            div.style.display = 'none';
            document.getElementById('iv_img_' + grpId).src = plusImage;
            data['iv_grp'] = replaceAt(ivGrp, grpId, '0');
        }
        else {
            div.style.visibility = 'visible';
            div.style.display = 'block';
            document.getElementById('iv_img_' + grpId).src = minusImage;
            data['iv_grp'] = replaceAt(ivGrp, grpId, '1');
        }
        setCookie("settings", jsSerializer(data), 30);
    }
    
    function InitIvGroup() {
        var c = getCookie("settings");
        var data = new Object();
        if (c == null)
            return;
        data = jsDeserializer(c);
        
        if (data['iv_grp'] != undefined && data['iv_grp'] != null) {
            ivGrp = data['iv_grp'];
            for (var i = 0; i < ivGrp.length; i++) {
                if (ivGrp.charAt(i) == '1') {
                    div = document.getElementById('iv_grp_' + i);
                    if (div != null) {
                        div.style.visibility = 'visible';
                        div.style.display = 'block';
                        document.getElementById('iv_img_' + i).src = minusImage;
                    }
                }
            }
        }
    }
    
    InitIvGroup();
</script>
