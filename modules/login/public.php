<?php
/**
 * Checks if the username and password do match with the data inside the database.
 * If it matches redirect to the inner pages.
 * If not shows an error message and the welcome page.
 */

global $demoEngine;
// Valid only for a demo of the engine, the username and password can be passed
// in the query string
if (isset($demoEngine) && $demoEngine === true) {
    if (isset($_GET["username"])) {
        $_POST["username"] = $_GET["username"];
    }
    if (isset($_GET["password"])) {
        $_POST["password"] = $_GET["password"];
    }
}

// Cleanup old bad trials
$db->Execute("delete from bad_trials where last_tried < date_sub(now(),interval 15 minute)");

// Checks if there is some open bad trials either for this user or for the IP
$result = $db->Execute("select sum(nb_trials) from bad_trials where user_id in (select id from users where username = ?) or ip = ?",
    $_POST["username"], $_SERVER["REMOTE_ADDR"]);
$nb = 0;
if (!$result->EOF) {
    $nb = $result->fields[0] + 0;
}
$result->Close();

// More than 3 bad trials? Sorry no way
if ($nb > 3) {
    $db->Execute("update bad_trials set last_tried=now() where user_id in (select id from users where username = ?) or ip = ?",
        $_POST["username"], $_SERVER["REMOTE_ADDR"]);
    ErrorMessage("Too many bad trials. Please wait 15 min");
    IncludePublicPage("");
    return;
}

// Check if the username and password match
$password = md5(substr(strtolower($_POST["username"]), 0, 2) . trim($_POST["password"]));
$result = $db->Execute("select id,username,blocked_module from users where upper(username) = upper(?) and password = ?",
    $_POST["username"], $password);
if (!$result->EOF) {
    if (function_exists("StatAction")) {
        StatAction(2);
    }
    
    $_SESSION["userid"] = $result->fields[0];
    $_SESSION["username"] = $result->fields[1];
    $_SESSION["block"] = $result->fields[2];
    $userId = $result->fields[0];
    $result->Close();
    
    if (GetConfigValue("gameLocked", "game_lock") == "true" && !IsAdmin()) {
        session_destroy();
        header("Location: index.php");
        exit();
    }
    
    header("Location: index.php");
    if (function_exists("SendChatLine")) {
        SendChatLine(Translate("User %s logged in.", $result->fields[1]));
    }
    exit();
}
$result->Close();

// Doesn't match increase the bad trials
$result = $db->Execute("select id from users where upper(username) = upper(?)", $_POST["username"]);
$uid = 0;
if (!$result->EOF) {
    $uid = $result->fields[0] + 0;
}
$result->Close();

// Check if there is already a bad trial on this user
$result = $db->Execute("select nb_trials from bad_trials where user_id = ?", $uid);
// None so create an entry
if ($result->EOF) {
    $db->Execute("insert into bad_trials(user_id,ip) values(?,?)", $uid, $_SERVER["REMOTE_ADDR"]);
} // Yes then update the entry
else {
    $db->Execute("update bad_trials set nb_trials=nb_trials+1 where user_id = ?", $uid);
}
$result->Close();
ErrorMessage("Invalid username or password.");

IncludePublicPage("");
