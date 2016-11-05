<?php
// Not an admin? Go away!
if (!IsModerator()) {
    header("Location: index.php");
    return;
}

if (isset($_POST["user"])) {
    $stats = UserStat::LoadStats($_POST["user"]);
    if ($stats == null) {
        ErrorMessage("User not found.");
    } else {
        foreach ($stats as $s) {
            if ($s->restoreRate != null) {
                $s->value = $s->maxValue;
            }
        }
        UserStat::SaveStats($stats, $_POST["user"]);
        
        if (function_exists("ReleaseFromHospital")) {
            ReleaseFromHospital();
        }
        if (function_exists("ReleaseFromJail")) {
            ReleaseFromJail();
        }
        ResultMessage("Stats updated.");
    }
} else {
    $_POST["user"] = $userId;
}

echo "<form method='post' name='formRestore'>";
TableHeader("Restore user stats");
echo "<table class='plainTable'>";
echo "<tr><td><b>" . str_replace(" ", "&nbsp;",
        Translate("User")) . ":</b></td><td>" . SmartSelection("select id,username from users where id <> 1",
        "user") . "</td></tr>";
echo "</table>";
TableFooter();
echo "</form>";

ButtonArea();
SubmitButton("Restore", "formRestore");
EndButtonArea();
