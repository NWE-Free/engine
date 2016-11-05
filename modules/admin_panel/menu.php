<?php
if (!IsModerator()) {
    return;
}
$menuEntries[] = new MenuEntry("Admin Panel", "Admin", null, 1000);
