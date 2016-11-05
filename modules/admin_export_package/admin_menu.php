<?php
if (IsSuperUser()) {
    $adminEntries[] = new MenuEntry("Export module package", "Modules", null, 1100);
}
