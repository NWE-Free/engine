<?php
if (IsSuperUser()) {
    $adminEntries[] = new MenuEntry("Scan Hooks", "Modules", null, 1100);
}
