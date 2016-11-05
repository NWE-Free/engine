<?php
if (IsSuperUser()) {
    $adminEntries[] = new MenuEntry("Run Crons");
}
