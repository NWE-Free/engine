<?php
$stats = UserStat::LoadStats($_GET["uid"]);

if (isset($_GET["cmd"]) && $_GET["cmd"] == "chgstat") {
    if (isset($_POST["newValue"])) {
        $stats[$_GET["stat"]]->value = $_POST["newValue"];
        UserStat::SaveStats($stats, $_GET["uid"]);
        
        ResultMessage("Stats updated");
    } else {
        TableHeader("Stats:");
        echo "<form method='post' name='frmChgStat'>";
        echo "<table class='plainTable'>";
        echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;",
                Translate("Stat")) . ":</b></td><td>" . Translate($_GET["stat"]) . "</td></tr>";
        echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;",
                Translate("Value")) . ":</b></td><td><input type='text' name='newValue' value='" . $stats[$_GET["stat"]]->value . "'></td></tr>";
        echo "</table>";
        echo "</form>";
        
        ButtonArea();
        SubmitButton("Set", "frmChgStat");
        LinkButton("Cancel", "index.php?p=admin_user&uid={$_GET['uid']}");
        EndButtonArea();
        
        TableFooter();
        return;
    }
}

TableHeader("Stats:");
echo "<div style='height: 200px; overflow: auto;'>";
echo "<table class='plainTable'>";
$row = 0;
foreach ($stats as $key => $s) {
    if ($row % 2 == 0) {
        echo "<tr class='evenLine'>";
    } else {
        echo "<tr class='oddLine'>";
    }
    echo "<td width='1%'><b>" . str_replace(" ", "&nbsp;", Translate($key)) . ":</b></td>";
    echo "<td><a href='index.php?p=admin_user&uid={$_GET['uid']}&cmd=chgstat&stat=" . urlencode($key) . "'>" . $s->value . "</a></td><td width='1%'>";
    LinkButton("Edit", "index.php?p=admin_user&uid={$_GET['uid']}&cmd=chgstat&stat=" . urlencode($key));
    echo "</td></tr>";
    $row++;
}
echo "</table>";
echo "</div>";
TableFooter();