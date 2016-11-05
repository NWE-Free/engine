<?php
/**
 * Let administrators add items to themselves or other users.
 */

// Not an admin? Go away!
if (!IsModerator()) {
    header("Location: index.php");
    return;
}

// An item has been defined
if (isset($_POST['item'])) {
    $obj = Item::GetObjectInfo($_POST['item']);
    // Wrong info
    if ($obj == null) {
        ResultMessage("Item unkown.");
    } // Let's continue
    else {
        $user = FindUser($_POST['user']);
        // User not found
        if ($user == null) {
            ErrorMessage("User not found.");
        } else {
            // Let's try to add the item.
            try {
                Item::InventoryAdd($obj->id, $_POST['quantity'], null, $user);
                ResultMessage("Item(s) added.");
            } catch (Exception $ex) {
                ErrorMessage($ex->getMessage());
            }
        }
    }
}

if (!isset($_POST['item'])) {
    $_POST['item'] = "";
}
if (!isset($_POST['user'])) {
    $_POST['user'] = $userId;
}
if (!isset($_POST['quantity'])) {
    $_POST['quantity'] = 1;
}

echo "<form method='post' name='addItem'>";
TableHeader("Add item");
echo "<table class='plainTable'>";
echo "<tr><td width='1%' valign='top'><b>" . str_replace(" ", "&nbsp;",
        Translate("Item name:")) . "</b></td><td>" . SmartSelection("select id, name from objects", "item",
        $_POST['item']) . "</td></tr>";
echo "<tr><td width='1%' valign='top'><b>" . str_replace(" ", "&nbsp;",
        Translate("User:")) . "</b></td><td>" . SmartSelection("select id,username from users where id <> 1", "user",
        $_POST['user']) . "</td></tr>";
echo "<tr><td width='1%' valign='top'><b>" . str_replace(" ", "&nbsp;",
        Translate("Quantity:")) . "</b></td><td><input type='text' name='quantity' value='" . htmlentities($_POST['quantity']) . "'></td></tr>";
echo "</table>";
TableFooter();
echo "</form>";

ButtonArea();
SubmitButton("Add", "addItem");
EndButtonArea();
