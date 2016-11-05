<?php
if (IsSuperUser()) {
    $adminEntries[] = new MenuEntry("Submit module to marketplace", "Modules", null, 1100);
}
