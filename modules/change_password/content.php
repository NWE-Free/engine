<?php
// Somthing has been sent
if (isset($_POST["oldPass"])) {
    $password = md5(substr(strtolower($_SESSION["username"]), 0, 2) . $_POST["oldPass"]);
    $result = $db->Execute("select password from users where id = ?", $userId);
    $storedPass = $result->fields[0];
    $result->Close();
    
    // Old password is incorrect.
    if ($storedPass != $password) {
        ErrorMessage("Old password is not correct.");
    } // Passwords do not match
    else if ($_POST["newPass"] != $_POST["confirm"]) {
        ErrorMessage("Passwords do not match.");
    } // New password is too short.
    else if (strlen(trim($_POST["newPass"])) < intval(GetConfigValue("minPasswordLength", "register"))) {
        ErrorMessage(Translate("Passwords must have at least %d characters.",
            intval(GetConfigValue("minPasswordLength", "register")), false));
    } // All fine, then set the new password.
    else {
        $db->Execute("update users set password = ? where id = ?",
            md5(substr(strtolower($_SESSION["username"]), 0, 2) . trim($_POST["newPass"])), $userId);
        ResultMessage("New password has been set.");
    }
}

// Show the formulat
echo "<form method='post' name='chgPass'>";
TableHeader("Change Password");
echo "<table class='plainTable'>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;",
        Translate("Old password")) . ":</b></td><td><input type='password' name='oldPass'></td></tr>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;",
        Translate("New password")) . ":</b></td><td><input type='password' name='newPass'></td></tr>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;",
        Translate("Confirm")) . ":</b></td><td><input type='password' name='confirm'></td></tr>";
echo "</table>";
TableFooter();
echo "</form>";

ButtonArea();
SubmitButton("Change Password", "chgPass");
LinkButton("Cancel", "index.php");
EndButtonArea();
