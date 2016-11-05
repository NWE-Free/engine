<?php
TableHeader("Messages");
$result = $db->Execute("select count(id) from messages where inbox_of = ? and is_new = 'yes'", $userId);
$nb = 0;
if (!$result->EOF) {
    $nb = $result->fields[0] + 0;
}
$result->Close();

if ($nb == 0) {
    echo Translate("You don't have any new message.") . "<br>";
    echo "<center>";
    LinkButton("Send a message", "index.php?p=messages");
    echo "</center>";
} else {
    echo Translate("<b>You got some new messages!</b>") . "<br>";
    echo "<center>";
    LinkButton("Check the message(s)", "index.php?p=messages");
    echo "</center>";
}
TableFooter();