<?php
if (IsSuperUser()) {
    $adminEntries[] = new MenuEntry("Run the installer again", "Installer", null, 1201);
}
