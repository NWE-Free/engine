<?php
$isFirst = true;
$content['stats'] .= "<span class='statBox'><span>";
if (in_array("view_player", $modules)) {
    $content['stats'] .= "<a href='index.php?p=view_player&id=$userId'>$username</a>";
} else {
    $content['stats'] .= $username;
}
$content['stats'] .= "</span></span>";
foreach ($userStats as $key => $s) {
    if (!$s->statBar) {
        continue;
    }
    /*
 * if (! $isFirst) $content['stats'] .= ", ";
 */
    $value = $s->value;
    if ($s->displayCode != null) {
        $value = NWEval('return (' . $s->displayCode . ');');
    }
    $content['stats'] .= "<span class='statBox'><span>" . Translate($s->name) . ": " . $value . "</span></span>";
    $isFirst = false;
}
