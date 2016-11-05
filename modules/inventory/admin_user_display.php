<?php
if (isset($_GET["cmd"]) && $_GET["cmd"] == "add_item") {
    
    if (isset($_POST["item"])) {
        $obj = Item::GetObjectInfo($_POST['item']);
        Item::InventoryAdd($obj->id, $_POST['quantity'], null, $_GET["uid"]);
        ResultMessage("Item(s) added.");
    } else {
        TableHeader("Inventory");
        if (!isset($_POST['quantity'])) {
            $_POST['quantity'] = 1;
        }
        
        echo "<form method='post' name='frmAddItem'>";
        echo "<table class='plainTable'>";
        echo "<tr><td width='1%' valign='top'><b>" . str_replace(" ", "&nbsp;",
                Translate("Item name:")) . "</b></td><td>" . SmartSelection("select id, name from objects",
                "item") . "</td></tr>";
        echo "<tr><td width='1%' valign='top'><b>" . str_replace(" ", "&nbsp;",
                Translate("Quantity:")) . "</b></td><td><input type='text' name='quantity' value='" . htmlentities($_POST['quantity']) . "'></td></tr>";
        echo "</table>";
        echo "</form>";
        
        ButtonArea();
        SubmitButton("Add", "frmAddItem");
        LinkButton("Cancel", "index.php?p=admin_user&uid={$_GET['uid']}");
        EndButtonArea();
        TableFooter();
        
        return;
    }
}

$result = $db->Execute("select inventory.object_id,objects.name,inventory.health,inventory.quantity
        from inventory left join objects on inventory.object_id = objects.id where inventory.user_id=?", $_GET["uid"]);

TableHeader("Inventory");
echo "<div style='height: 155px; overflow: auto;'>";
echo "<table class='plainTable'>";
echo "<tr class='titleLine'><td>" . Translate("Name") . "</td><td>" . Translate("Quantity") . "</td></tr>";
$r = 0;
foreach ($result as $row) {
    if ($r % 2 == 0) {
        echo "<tr class='evenLine'>";
    } else {
        echo "<tr class='oddLine'>";
    }
    echo "<td>{$row[1]}</td><td>{$row[3]}</td></tr>";
    echo "</tr>";
    $r++;
}
echo "</table>";
echo "</div>";

ButtonArea();
LinkButton("Add", "index.php?p=admin_user&uid={$_GET['uid']}&cmd=add_item");
EndButtonArea();
TableFooter();

$result->Close();