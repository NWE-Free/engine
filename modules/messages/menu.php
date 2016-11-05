<?php
$result = $db->Execute("select count(id) from messages where inbox_of = ? and is_new = 'yes'", $userId);
$nb = 0;
if (!$result->EOF) {
    $nb = $result->fields[0] + 0;
}
$result->Close();

if ($nb > 0) {
    $menuEntries[] = new MenuEntry("Messages (<span class='blinking'>$nb</span>)", "Communication");
} else {
    $menuEntries[] = new MenuEntry("Messages", "Communication");
}
