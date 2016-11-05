<?php
session_start();
if (!isset($_SESSION['profiler_db'])) {
    return;
}

$dbProf = $_SESSION['profiler_db'];

echo "Total queries: " . count($dbProf) . "<br>";
$tot = 0;
foreach ($dbProf as $i) {
    $tot += $i['time'];
}
echo "Total time: " . round($tot, 4) . " sec.<br>";

echo "<hr>";
foreach ($dbProf as $i) {
    echo "Time: " . round($i['time'], 4) . " sec.<br>";
    echo $i['query'] . ";<br><br>";
}
