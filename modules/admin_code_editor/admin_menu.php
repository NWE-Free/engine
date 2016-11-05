<?php
if (IsSuperUser()) {
    $adminEntries[] = new MenuEntry("Code editor", "Modules", null, 1100);
}
