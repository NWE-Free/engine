<?php
include "../../config/config.php";
include "../../libs/db.php";
$db = new Database($dbhost, $dbuser, $dbpass, $dbname);

session_start();

$userId = -1;
if (isset($_SESSION["userid"]) && $_SESSION["userid"] != null) {
    $userId = $_SESSION["userid"];
}

if ($userId == -1 && !IsAdmin()) {
    return;
}

echo "var conditionWizard=[";

$result = $db->Execute("select id,name, code,label_1,param_1,label_2,param_2,label_3,param_3,label_4,param_4,label_5,param_5 from wizard_logic order by name");
$row = 0;
while (!$result->EOF) {
    if ($row != 0) {
        echo ",";
    }
    echo "{id:{$result->fields[0]},\nname:unescape('" . rawurlencode($result->fields[1]) . "'),\n";
    echo "code:unescape('" . rawurlencode($result->fields[2]) . "'),\n";
    $code = preg_replace("/@p[1-9]@/", "(.*)",
        str_replace(array(
            "\\",
            ".",
            "[",
            "]",
            "+",
            "-",
            "*",
            "$",
            "(",
            ")",
            "|",
            "<",
            ">",
            "=",
            "!",
            "?",
            ":",
            "{",
            "}",
            "^"
        ),
            array(
                "\\\\",
                "\\.",
                "\\[",
                "\\]",
                "\\+",
                "\\-",
                "\\*",
                "\\$",
                "\\(",
                "\\)",
                "\\|",
                "\\<",
                "\\>",
                "\\=",
                "\\!",
                "\\?",
                "\\:",
                "\\{",
                "\\}",
                "\\^"
            ), $result->fields[2]));
    echo "expCode:new RegExp(unescape('" . rawurlencode("^" . $code . "\$") . "'))\n";
    for ($i = 0; $i < 5; $i++) {
        echo ",label_" . ($i + 1) . ":unescape('" . rawurlencode($result->fields[$i * 2 + 3]) . "')\n";
        echo ",type_" . ($i + 1) . ":'" . substr(strtolower($result->fields[$i * 2 + 4]), 0, 1) . "'\n";
        if (substr(strtolower($result->fields[$i * 2 + 4]), 0, 1) == "s") {
            echo ",options_" . ($i + 1) . ":[";
            $r2 = $db->Execute($result->fields[$i * 2 + 4]);
            if ($r2 !== false) {
                $isFirst = true;
                while (!$r2->EOF) {
                    if (!$isFirst) {
                        echo ",";
                    }
                    echo "{id:unescape('" . rawurlencode($r2->fields[0]) . "')";
                    if (count($r2->fields) == 2) {
                        echo ",value:unescape('" . rawurlencode($r2->fields[1]) . "')}";
                    } else {
                        echo ",value:unescape('" . rawurlencode($r2->fields[0]) . "')}";
                    }
                    $isFirst = false;
                    $r2->MoveNext();
                }
                $r2->Close();
            }
            echo "]\n";
        } else if (substr(strtolower($result->fields[$i * 2 + 4]), 0, 1) == "v") {
            echo ",options_" . ($i + 1) . ":[";
            $source = file_get_contents("../../config/auto_defines.php");
            preg_match_all("/define\\('([a-zA-Z_0-9]+)'/", $source, $matches, PREG_PATTERN_ORDER);
            $isFirst = true;
            foreach ($matches[1] as $m) {
                if (!$isFirst) {
                    echo ",";
                }
                echo "{id:'{$m}',value:'{$m}'}";
                $isFirst = false;
            }
            echo "]\n";
        } else if (count(explode(' ', $result->fields[$i * 2 + 4])) > 1) {
            echo ",default_" . ($i + 1) . ":unescape('" . rawurlencode(substr($result->fields[$i * 2 + 4],
                    strpos($result->fields[$i * 2 + 4], ' ') + 1)) . "')\n";
        }
    }
    echo "}";
    $result->MoveNext();
    $row++;
}
$result->Close();
echo "];\n";
$db->Close();