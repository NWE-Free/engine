<?php
if (GetConfigValue("registrationEnabled") == "false") {
    ErrorMessage("Registration is not allowed currently.");
    return;
}

function CheckUsername($username)
{
    global $db;
    
    if (preg_match("/^[A-Za-z0-9_\\-]+\$/", $username) != 1) {
        echo "<b style='color: red'>" . Translate("The username can contains only letters, numbers and the characters - or _") . "</b>";
    } else if (strlen($username) > 19) {
        echo "<b style='color: red'>" . Translate("A username cannot contain more than 19 characters.") . "</b>";
    } else if (strlen($username) < 5) {
        echo "<b style='color: red'>" . Translate("A username cannot contain less than 5 characters.") . "</b>";
    } else {
        $result = $db->Execute("select id from users where upper(username) = upper(?) limit 0,1", $username);
        if ($result->EOF) {
            echo "<b style='color: green'>" . Translate("Username valid.") . "</b>";
        } else {
            echo "<b style='color: red'>" . Translate("Username already in use.") . "</b>";
        }
        $result->Close();
    }
}

Ajax::RegisterFunction("CheckUsername", "usernameResult", 500);

if (Ajax::IsAjaxCallback()) {
    return;
}

/**
 * Post back from the user, we shall see what he/she wrote
 */
if (isset($_POST['username']) && trim($_POST['username']) != "") {
    $username = trim($_POST['username']);
    $result = $db->Execute("select id from users where upper(username) = upper(?) limit 0,1", $username);
    if (!$result->EOF) {
        ErrorMessage("Username already in use.");
    }
    /**
     * Captcha is not correct...
     */
    if (CaptchaCheck() == false) {
        ErrorMessage("Human verification failed.");
    } /**
     * Passwords do not match.
     */
    else if ($_POST["password"] != $_POST["confirm"]) {
        ErrorMessage("Passwords do not match.");
    } /**
     * Checks if the password meets the minimum length.
     */
    else if (strlen(trim($_POST["password"])) < intval(GetConfigValue("minPasswordLength"))) {
        ErrorMessage(Translate("Passwords must have at least %d characters.",
            intval(GetConfigValue("minPasswordLength", "register"))), false);
    } /**
     * E-mail is not in a valid format.
     */
    else if (preg_match("/^[A-Za-z0-9\\._%+-]+@[A-Za-z0-9\\.-]+\\.[A-Za-z]{2,4}\$/", $_POST["email"]) != 1) {
        ErrorMessage("You must provide a valid email address.");
    } /**
     * Checks if the username contains invalid characters.
     */
    else if (preg_match("/^[A-Za-z0-9_\\-]+\$/", $_POST["username"]) != 1) {
        ErrorMessage("The username can contains only letters, numbers and the characters - or _");
    } /**
     * Checks if the username is too long.
     */
    else if (strlen($_POST["username"]) > 19) {
        ErrorMessage("A username cannot contain more than 19 characters.");
    } else if (strlen($_POST["username"]) < 5) {
        ErrorMessage("A username cannot contain less than 5 characters.");
    } /**
     * We shall try to insert
     */
    else {
        global $demoEngine;
        if (isset($demoEngine) && $demoEngine === true) {
            ErrorMessage("Disabled in the demo");
        } else {
            $res = $db->Execute("insert into users(username,password,email,created_on) values(?,?,?,NOW())",
                trim($_POST["username"]), md5(substr(strtolower($_POST["username"]), 0, 2) . trim($_POST["password"])),
                $_POST["email"]);
            /**
             * Failed therefore we suppose a same username or email is used
             */
            if ($res === false) {
                ErrorMessage("Username or email already in use.");
            } /**
             * All fine, let's jump inside
             */
            else {
                $userId = $db->LastId();
                if (function_exists("StatAction")) {
                    StatAction(1);
                }
                
                $_SESSION["userid"] = $userId;
                $_SESSION["username"] = trim($_POST["username"]);
                $_SESSION["block"] = "";
                
                if (function_exists("SendChatLine")) {
                    SendChatLine(Translate("User %s joined the game.", trim($_POST["username"])));
                }
                
                RunHook("after_register.php");
                
                Header("Location: index.php");
                return;
            }
        }
    }
}

/**
 * Initialize the post array, such that we don't get notice errors
 */
if (!isset($_POST['username'])) {
    $_POST['username'] = "";
}
if (!isset($_POST['password'])) {
    $_POST['password'] = "";
}
if (!isset($_POST['confirm'])) {
    $_POST['confirm'] = "";
}
if (!isset($_POST['email'])) {
    $_POST['email'] = "";
}

/**
 * Creates the registration form
 */
echo "<form method='post' action='index.php?h=register' name='register'>";
TableHeader("Register");
echo "<table width='100%'>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;",
        Translate("Username")) . ":</b></td><td><input type='text' value='{$_POST['username']}' name='username' id='username' onkeyup='CheckUsername($(\"#username\").val())'></td></tr>";
echo "<tr><td colspan='2' id='usernameResult'></td></tr>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;",
        Translate("Password")) . ":</b></td><td><input type='password' value='{$_POST['password']}' name='password'></td></tr>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;",
        Translate("Confirm")) . ":</b></td><td><input type='password' value='{$_POST['confirm']}' name='confirm'></td></tr>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;",
        Translate("E-mail")) . ":</b></td><td><input type='text' value='{$_POST['email']}' name='email'></td></tr>";

RunHook("during_register.php");

$captcha = CaptchaShow();
/**
 * If a cpatcha module exists, then let's show a captcha
 */
if ($captcha != "") {
    echo "<tr valign='top'><td width='1%'><b>" . str_replace(" ", "&nbsp;",
            Translate("Human verification")) . ":</b></td><td>$captcha</td></tr>";
}

echo "</table>";
TableFooter();
echo "</form>";

ButtonArea();
SubmitButton("Register", "register");
LinkButton("Cancel", "index.php");
EndButtonArea();
