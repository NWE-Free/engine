<?php
if (!IsSuperUser()) {
    header("Location: index.php");
    return;
}

global $knownHooks, $implemendedHooks;
$knownHooks = array();
$implemendedHooks = array();

/**
 * Scans all the directories (recursively) and search for a RunHook call.
 *
 * @param $dir string
 *             directory where to start scanning.
 */
function FindHooks($dir)
{
    global $knownHooks;
    
    $files = scandir($dir);
    
    foreach ($files as $f) {
        if ($f == "." || $f == "..") {
            continue;
        }
        if (is_dir("$dir/$f")) {
            FindHooks("$dir/$f");
        } else if (preg_match("/\\.php\$/i", $f)) {
            $source = file_get_contents("$dir/$f");
            preg_match_all("/RunHook\\([\"']([A-Z0-9\\-a-z_]+\\.php)[\"']/", $source, $matches);
            foreach ($matches[1] as $i) {
                if (!array_key_exists($i, $knownHooks)) {
                    $knownHooks[$i] = array();
                    $knownHooks[$i][] = "$dir/$f";
                } else if (!in_array("$dir/$f", $knownHooks[$i])) {
                    $knownHooks[$i][] = "$dir/$f";
                }
            }
        }
    }
}

/**
 * Searches all the implemented hook files.
 *
 * @param $dir string
 *             directory where to start scanning.
 */
function SearchImplementedHooks($dir)
{
    global $knownHooks, $implemendedHooks;
    
    $files = scandir($dir);
    
    foreach ($files as $f) {
        if ($f == "." || $f == "..") {
            continue;
        }
        if (is_dir("$dir/$f")) {
            SearchImplementedHooks("$dir/$f");
        } else if (array_key_exists($f, $knownHooks)) {
            if (!array_key_exists($f, $implemendedHooks)) {
                $implemendedHooks[$f] = array();
            }
            $implemendedHooks[$f][] = $dir;
        }
    }
}

FindHooks($baseDir);
SearchImplementedHooks($baseDir);
ksort($knownHooks);

global $modules;

TableHeader(Translate("Hooks found: %d", count($knownHooks)), false);
foreach ($knownHooks as $key => $val) {
    echo "<img src='{$webBaseDir}images/plus.png' id='img_$key' onclick='expandContractHook(\"$key\")' align='top'> <b>$key</b><br>";
    echo "<div style='visibility: hidden; position: absolute; margin-left: 20px;' id='grp_$key'>";
    echo "Requested by:<br>";
    echo "<div style='margin-left: 20px;'>";
    foreach ($val as $i) {
        if (in_array("admin_code_editor", $modules) && strncmp(substr($i, strlen($baseDir) + 1), "modules/", 8) == 0) {
            echo "- <a href='index.php?p=admin_code_editor&f=" . urlencode(substr($i,
                    strlen($baseDir . "/modules/"))) . "'>" . substr($i, strlen($baseDir) + 1) . "</a><br>";
        } else {
            echo "- " . substr($i, strlen($baseDir) + 1) . "<br>";
        }
    }
    echo "</div>";
    if (isset($implemendedHooks[$key])) {
        echo "Implemented by:<br>";
        echo "<div style='margin-left: 20px;'>";
        foreach ($implemendedHooks[$key] as $i) {
            if (in_array("admin_code_editor", $modules) && strncmp(substr($i, strlen($baseDir) + 1), "modules/",
                    8) == 0
            ) {
                echo "- <a href='index.php?p=admin_code_editor&f=" . urlencode(substr($i,
                            strlen($baseDir . "/modules/")) . "/" . $key) . "'>" . substr($i,
                        strlen($baseDir) + 1) . "</a><br>";
            } else {
                echo "- " . substr($i, strlen($baseDir) + 1) . "<br>";
            }
        }
        echo "</div>";
    }
    echo "</div>";
}
TableFooter();

// Makes the result collapsible... Easier to navigate with.
echo "<script>
var minusURL='{$webBaseDir}images/minus.png';
var plusURL='{$webBaseDir}images/plus.png';
function expandContractHook(name)
{
	if(document.getElementById('grp_'+name).style.visibility == 'hidden')
	{
		document.getElementById('grp_'+name).style.visibility='visible';
		document.getElementById('grp_'+name).style.position='';
		document.getElementById('img_'+name).src=minusURL;
	}
	else
	{
		document.getElementById('grp_'+name).style.visibility='hidden';
		document.getElementById('grp_'+name).style.position='absolute';
		document.getElementById('img_'+name).src=plusURL;
	}
}
</script>";