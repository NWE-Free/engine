<?php
if (!IsAdmin()) {
    return;
}

if (isset($_GET["profiler"])) {
    if ($_GET["profiler"] == "on") {
        $_SESSION["profiler"] = "on";
        ResultMessage("Profiler is now on.");
    } else {
        unset($_SESSION["profiler"]);
        ResultMessage("Profiler is now off.");
    }
}
