<?php
if (IsSuperUser()) {
    $adminEntries[] = new MenuEntry("Import module package", "Modules", null, 1100);
}
