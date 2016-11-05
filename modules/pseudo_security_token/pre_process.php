<?php
if (count($_GET) != 0) {
    if (!isset($_GET["token"])) {
        header("Location: index.php");
        session_unset();
        exit();
    }
    if ($_GET["token"] != "MySecurityToken") {
        header("Location: index.php");
        session_unset();
        exit();
    }
}
