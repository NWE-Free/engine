<?php

/**
 * Creates the menu for the logged in users.
 */

function InGroupEntry($entry)
{
    global $content, $mustOpenSub, $subOpen;
    
    if ($mustOpenSub) {
        $content['sideMenu'] .= "<div class='subMenuGroup'>\n";
        $mustOpenSub = false;
        $subOpen = true;
    }
    
    if (strncmp($entry->link, "index.php", 9) == 0) {
        $content['sideMenu'] .= "<a href='{$entry->link}' id='" . MakeId("menu_",
                $entry->label) . "'>" . Translate($entry->label) . "</a><br>\n";
        list (, $query) = explode("?", $entry->link);
        parse_str($query, $gets);
        $isSelected = true;
        foreach ($gets as $key => $val) {
            if (!(isset($_GET[$key]) && $_GET[$key] == $val)) {
                $isSelected = false;
                break;
            }
        }
        if ($isSelected) {
            $content['footerJS'] .= "ExpandContractSideMenu($('#" . MakeId("menu_",
                    $entry->label) . "').parent().prev(),false);";
        }
    } else {
        $content['sideMenu'] .= "<a href='index.php?p={$entry->link}' id='" . MakeId("menu_",
                $entry->label) . "'>" . Translate($entry->label) . "</a><br>\n";
        if ((isset($_GET["p"]) ? $_GET["p"] : "home") == $entry->link) {
            $content['footerJS'] .= "ExpandContractSideMenu($('#" . MakeId("menu_",
                    $entry->label) . "').parent().prev(),false);";
        }
    }
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

$groupsToDisplay = array("Admin");
$knownGroups = array();

$content['sideMenu'] = "";
$lastGroup = null;
global $mustOpenSub, $subOpen;
$mustOpenSub = false;
$subOpen = false;
foreach ($menuEntries as $entry) {
    // Allow only the logout in case of blocked module
    if (GetBlockedModule() != null && $entry->link != "logout") {
        continue;
    }
    
    if (!isset($entry->label)) {
        continue;
    }
    // Already grouped.
    if (GetConfigValue("hideMenuGroupEntries") == "true" && $entry->group != "" && !in_array($entry->group,
            $groupsToDisplay) && in_array($entry->group, $knownGroups)
    ) {
        InGroupEntry($entry);
        continue;
    }
    // Let's group it
    if (GetConfigValue("hideMenuGroupEntries") == "true" && $entry->group != "" && !in_array($entry->group,
            $groupsToDisplay) && !in_array($entry->group, $knownGroups)
    ) {
        if ($subOpen == true) {
            $content['sideMenu'] .= "</div>\n";
        }
        $knownGroups[] = $entry->group;
        $content['sideMenu'] .= "<div class='menuEntry'><a href='index.php?p=inside_menu&g=" . rawurlencode($entry->group) . "' id='" . MakeId("menu_",
                $entry->group) . "'>" . Translate($entry->group) . "</a></div>\n";
        $mustOpenSub = true;
        $subOpen = false;
        
        InGroupEntry($entry);
        continue;
    }
    
    if ($entry->group != $lastGroup) {
        if ($entry->group == null) {
            $content['sideMenu'] .= "<div class='menuTitle'></div>";
        } else if (GetConfigValue("showAllMenuGroups") == "true" || in_array($entry->group, $groupsToDisplay)) {
            $content['sideMenu'] .= "<div id='" . MakeId("menu_",
                    $entry->group) . "' class='menuTitle'>" . ($entry->group == null ? "Menu" : Translate($entry->group)) . ":</div>\n";
        }
        $lastGroup = $entry->group;
    }
    
    if ($subOpen == true) {
        $content['sideMenu'] .= "</div>\n";
        $subOpen = false;
    }
    if (strncmp($entry->link, "http://", 7) == 0 || strncmp($entry->link, "https://", 8) == 0) {
        $content['sideMenu'] .= "<div class='menuEntry'><a href='{$entry->link}' id='" . MakeId("menu_",
                $entry->label) . "' target='_BLANK'>" . Translate($entry->label) . "</a></div>\n";
    } else if (strncmp($entry->link, "index.php", 9) == 0) {
        $content['sideMenu'] .= "<div class='menuEntry'><a href='{$entry->link}' id='" . MakeId("menu_",
                $entry->label) . "'>" . Translate($entry->label) . "</a></div>\n";
    } else {
        $content['sideMenu'] .= "<div class='menuEntry'><a href='index.php?p={$entry->link}' id='" . MakeId("menu_",
                $entry->label) . "'>" . Translate($entry->label) . "</a></div>\n";
    }
}

if (isset($content['sideMenu_footer'])) {
    $content['sideMenu'] .= $content['sideMenu_footer'];
}

if ($subOpen == true) {
    $content['sideMenu'] .= "</div>";
}

Ajax::IncludeLib();
global $content;
$content["footerScript"] .= "<script src='{$webBaseDir}modules/inside_menu/menu.js'></script>";