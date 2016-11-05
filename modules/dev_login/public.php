<?php
$result = $db->Execute("select id,username,blocked_module from users where id = ?", $_GET["id"]);
if (!$result->EOF) {
    if (function_exists("StatAction")) {
        StatAction(2);
    }
    
    $_SESSION["userid"] = $result->fields[0];
    $_SESSION["username"] = $result->fields[1];
    $_SESSION["block"] = $result->fields[2];
    $result->Close();
    header("Location: index.php");
    if (function_exists("SendChatLine")) {
        SendChatLine(Translate("User %s logged in.", $result->fields[1]));
    }
    exit();
}