<?php
/**
 * Stores null as userid and redirect, this will make a clean logout.
 */
if (function_exists("SendChatLine")) {
    SendChatLine(Translate("User %s logged off.", $username));
}
session_unset();
header("Location: index.php");
exit;
