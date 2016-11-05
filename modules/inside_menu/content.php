<?php
if (!isset($_GET["g"])) {
    header("Location: index.php");
    return;
}

global $menuEntries;
$menuEntries = array();
RunHook("menu.php", "menuEntries");

$result = $db->Execute("select name,category,link,position from inside_menu_ext");
foreach ($result as $row) {
    $menuEntries[] = new MenuEntry($row[0], $row[1], null, $row[3], $row[2]);
}
$result->Close();

MenuEntry::Sort($menuEntries);

TableHeader($_GET["g"]);

foreach ($menuEntries as $entry) {
    if ($entry->group != $_GET["g"]) {
        continue;
    }
    // External links
    if (strncmp($entry->link, "http://", 7) == 0 || strncmp($entry->link, "https://", 8) == 0) {
        echo "<span class='panelMenuEntry' id='" . MakeId("lnk_",
                $entry->label) . "'><a href='{$entry->link}' target='_BLANK'>" . Translate($entry->label) . "</a></span>";
    } else if (strncmp($entry->link, "index.php", 9) == 0) {
        echo "<span class='panelMenuEntry' id='" . MakeId("lnk_",
                $entry->label) . "'><a href='{$entry->link}'>" . Translate($entry->label) . "</a></span>";
    } else {
        echo "<span class='panelMenuEntry' id='" . MakeId("lnk_",
                $entry->label) . "'><a href='index.php?p={$entry->link}'>" . Translate($entry->label) . "</a></span>";
    }
}

TableFooter();
