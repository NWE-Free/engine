<?php
/**
 * Creates the public menu.
 */
$menuEntries = array();
global $menuEntries;

RunHook("public_menu.php", "menuEntries");

MenuEntry::Sort($menuEntries);

$content['sideMenu'] = "";
$lastGroup = null;
foreach ($menuEntries as $entry) {
    // Allow only the logout in case of blocked module
    if (GetBlockedModule() != null && $entry->link != "logout") {
        continue;
    }
    
    if (!isset($entry->label)) {
        continue;
    }
    if ($entry->group != $lastGroup) {
        $content['sideMenu'] .= "<br><b>" . Translate($entry->group) . "</b>:<br>";
        $lastGroup = $entry->group;
    }
    
    if (strncmp($entry->link, "index.php", 9) == 0) {
        $content['sideMenu'] .= "<div class='menuEntry'><a href='{$entry->link}' id='" . MakeId("menu_",
                $entry->label) . "'>" . Translate($entry->label) . "</a></div>";
    } else {
        $content['sideMenu'] .= "<div class='menuEntry'><a href='index.php?h={$entry->link}' id='" . MakeId("menu_",
                $entry->label) . "'>" . Translate($entry->label) . "</a></div>";
    }
}

if (isset($content['sideMenu_footer'])) {
    $content['sideMenu'] .= $content['sideMenu_footer'];
}
