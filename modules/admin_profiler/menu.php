<?php
if (!IsAdmin()) {
    return;
}
if (!isset($_SESSION["profiler"])) {
    $menuEntries[] = new MenuEntry("Enable Profiler", "Admin", null, 1000, "index.php?p=admin_profiler&profiler=on");
} else {
    $menuEntries[] = new MenuEntry("Disable Profiler", "Admin", null, 1000, "index.php?p=admin_profiler&profiler=off");
}
