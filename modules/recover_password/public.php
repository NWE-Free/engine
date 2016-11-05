<?php
global $engineLicenseKey;

if (isset($_GET["key"])) {
    $result = $db->Execute("select id,username from users where email = ? limit 0,1", $_GET["email"]);
    if ($result->EOF) {
        ErrorMessage("Account not found.");
    } else if ($_GET["key"] == md5("passwordRecovery" . $engineLicenseKey . $result->fields[0] . $result->fields[1])) {
        $newPass = substr(md5(time() . rand(0, 10000) . $gameName), 0, 6);
        mail($_GET["email"], "$gameName: password recovery completed",
            "Your new password as been set:\nUsername: {$result->fields[1]}\nPassword: $newPass",
            "from: no-reply@" . $_SERVER['SERVER_NAME']);
        $db->Execute("update users set password = ? where id = ? limit 1",
            md5(substr(strtolower($result->fields[1]), 0, 2) . trim($newPass)), $result->fields[0]);
        ResultMessage("New password set, please check your emails.");
    } else {
        ErrorMessage("Account not found.");
    }
    $result->Close();
}
if (isset($_POST["email"])) {
    $result = $db->Execute("select id,username from users where email = ? limit 0,1", $_POST["email"]);
    if ($result->EOF) {
        ErrorMessage("Account not found.");
    } else {
        mail($_POST["email"], Translate("%s: password recovery", $gameName),
            Translate("Hi,\n\nSomebody, possibly you, tried to recover your password on %s.\nIf you want to recover your lost password, click the following link, otherwise please ignore this email.\n\n",
                $gameName) . "http://" .
            $_SERVER['SERVER_NAME'] . Secure("{$webBaseDir}index.php?h=recover_password&key=" . md5("passwordRecovery" . $engineLicenseKey . $result->fields[0] . $result->fields[1]) . "&email=" . urlencode($_POST["email"]),
                true),
            "from: no-reply@" . $_SERVER['SERVER_NAME']);
        ResultMessage("Email sent. Check your inbox or your spam filter folder.");
    }
    $result->Close();
}

TableHeader("Recover username and password");
echo "<form method='post' name='frmPasswordRecover'>";
echo "<table class='plainTable'>";
echo "<tr><td width='1%'><b>" . str_replace(" ", "&nbsp;",
        Translate("Enter your email")) . ":</b></td><td><input type='text' name='email' value='" . (isset($_POST['email']) ? htmlentities($_POST['email']) : "") . "'></td></tr>";
echo "</table>";
echo "</form>";
TableFooter();

ButtonArea();
SubmitButton("Recover Password", "frmPasswordRecover");
EndButtonArea();