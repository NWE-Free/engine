<?php
if (isset($_POST["action"]) && $_POST["action"] == "daily") {
    // Let's run all the modules cron.
    RunHook("daily_cron.php");
    $t = date("z Y");
    SetConfigValue("lastCronRun", $t);
    
    $todo = array();
    global $modules;
    foreach ($modules as $module) {
        if (file_exists("$baseDir/modules/$module/daily_cron.php")) {
            $todo[] = "$baseDir/modules/$module/daily_cron.php";
        }
    }
    
    if (count($todo) > 0) {
        ResultMessage("Done.");
        TableHeader("Hook executed");
        foreach ($todo as $i) {
            echo "$i<br>";
        }
        TableFooter();
    } else {
        ErrorMessage("No daily_cron.php hooks found.");
    }
}

echo "<form method='post' name='frmCron'>";
echo "<input type='hidden' name='action' value='daily' />";
echo "</form>";
TableHeader("Run Crons");
echo Translate("Do you want to execute manually now the daily crons?");
TableFooter();

ButtonArea();
SubmitButton("Yes", "frmCron");
LinkButton("No", "index.php?p=admin_panel");
EndButtonArea();