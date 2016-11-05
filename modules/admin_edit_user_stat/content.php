<?php
if (!IsAdmin()) {
    header("Location: index.php");
    return;
}

if (isset($_POST["user"])) {
    $stats = UserStat::LoadStats($_POST["user"]);
    if ($_POST["cmd"] == "add") {
        $stats[$_POST["stat"]]->value += $_POST["quantity"];
    } else {
        $stats[$_POST["stat"]]->value -= $_POST["quantity"];
    }
    UserStat::SaveStats($stats, $_POST["user"]);
    ResultMessage("Stats updated.");
}

TableHeader("Add / Remove stat to some user");
echo "<form method='post' name='frmUserStat'>";
echo "<input type='hidden' name='cmd' value='add'>";
echo "<table class='plainTable'>";
echo "<tr valign='top'><td width='1%'><b>" . str_replace(" ", "&nbsp;",
        Translate("User")) . ":</b></td><td>" . SmartSelection("select id,username from users", "user",
        $userId) . "</td></tr>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;", Translate("Stat")) . ":</b></td><td><select name='stat'>";
$result = $db->Execute("select name from user_stat_types order by name");
while (!$result->EOF) {
    if (isset($_POST["stat"]) && $_POST["stat"] == $result->fields[0]) {
        echo "<option selected>{$result->fields[0]}</option>";
    } else {
        echo "<option>{$result->fields[0]}</option>";
    }
    $result->MoveNext();
}
$result->Close();
echo "</select></td></tr>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;", Translate("Quantity")) . ":</b></td>";
echo "<td><input type='text' name='quantity' value='" . (isset($_POST["quantity"]) ? htmlentities($_POST["quantity"]) : "0") . "'></td></tr>";
echo "</table>";
echo "</form>";
TableFooter();

ButtonArea();
LinkButton("Add", "#",
    "document.forms['frmUserStat'].cmd.value='add';document.forms['frmUserStat'].submit();return false;");
LinkButton("Remove", "#",
    "document.forms['frmUserStat'].cmd.value='remove';document.forms['frmUserStat'].submit();return false;");
LinkButton("Cancel", "index.php?p=admin_panel");
EndButtonArea();