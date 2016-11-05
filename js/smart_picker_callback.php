<?php
session_start();

if (!isset($_SESSION["smartPick"])) {
    return;
}

if (!isset($_GET["f"])) {
    return;
}

if (!isset($_SESSION["smartPick"][$_GET["f"]])) {
    return;
}

include "../libs/db.php";
include "../config/config.php";
$db = new Database($dbhost, $dbuser, $dbpass, $dbname);

$source = $_SESSION["smartPick"][$_GET["f"]];

$res = preg_split("/[^a-zA-Z0-9_]/", strtolower($source), -1, PREG_SPLIT_NO_EMPTY);
if ($res[1] == "unique" || $res[1] == "distinct") {
    array_splice($res, 1, 1);
}

$colA = $res[1];
$colB = $res[2];

if ($_GET["q"] == null) {
    $query = "$source order by $colB limit 0,30";
    $result = $db->Execute($query);
} else {
    if (in_array("where", $res)) {
        $query = "$source and $colB like ? order by $colB limit 0,30";
    } else {
        $query = "$source where lower($colB) like ? order by $colB limit 0,30";
    }
    $result = $db->Execute($query, "%" . strtolower($_GET["q"]) . "%");
}

echo "[";
$isFirst = true;
while (!$result->EOF) {
    if (!$isFirst) {
        echo ",";
    }
    echo '{id:' . $result->fields[0] . ',text:unescape("';
    echo rawurlencode($result->fields[1]) . '")}';
    
    $result->MoveNext();
    $isFirst = false;
}
echo "]";
$result->Close();
$db->Close();