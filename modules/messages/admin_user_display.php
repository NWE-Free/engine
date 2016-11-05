<?php
if (isset($_GET["cmd"]) && $_GET["cmd"] == "view_message") {
    TableHeader("Messages");
    echo "<div style='height: 155px; overflow: auto;'>";
    $result = $db->Execute("select messages.id, users.username, sent_on, subject, message
            from messages left join users on users.id = messages.from_user where messages.id = ?", $_GET["id"]);
    echo "<b>" . Translate("From:") . "</b> {$result->fields[1]}<br>";
    echo "<b>" . Translate("Date:") . "</b> {$result->fields[2]}<br>";
    echo "<b>" . Translate("Subject:") . "</b> {$result->fields[3]}<br>";
    echo PrettyMessage($result->fields[4]);
    $result->Close();
    echo "</div>";
    
    ButtonArea();
    LinkButton("Cancel", "index.php?p=admin_user&uid={$_GET['uid']}");
    EndButtonArea();
    
    TableFooter();
    return;
}

TableHeader("Messages");
echo "<div style='height: 200px; overflow: auto;'>";
echo "<table class='plainTable'>";

$result = $db->Execute("select messages.id, users.username, sent_on, subject
        from messages left join users on users.id = messages.from_user where inbox_of = ? order by messages.id desc",
    $_GET["uid"]);

echo "<tr class='titleLine'>";
echo "<td>" . Translate("From") . "</td>";
echo "<td>" . Translate("Date") . "</td>";
echo "<td>" . Translate("Subject") . "</td>";
echo "</tr>";

$nbcols = $result->FieldCount();
$ln = 0;
foreach ($result as $row) {
    if ($ln % 2 == 0) {
        echo "<tr class='evenLine'>";
    } else {
        echo "<tr class='oddLine'>";
    }
    
    for ($i = 1; $i < $nbcols; $i++) {
        echo "<td><a href='index.php?p=admin_user&uid={$_GET['uid']}&cmd=view_message&id={$row[0]}'>{$row[$i]}</a></td>";
    }
    
    echo "</tr>";
    $ln++;
}
echo "</table>";
echo "</div>";
TableFooter();