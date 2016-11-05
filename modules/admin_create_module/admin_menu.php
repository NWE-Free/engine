<?php
if (IsSuperUser()) {
    $adminEntries[] = new MenuEntry("Create new module", "Modules", null, 1100);
}
