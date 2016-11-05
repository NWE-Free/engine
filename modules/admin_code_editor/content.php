<?php
if (!IsSuperUser()) {
    header("Location: index.php");
    return;
}

global $content;
$content['header'] .= "<link rel='stylesheet' href='{$webBaseDir}modules/admin_code_editor/code_mirror/codemirror.css'>\n";
$content['header'] .= "<link rel='stylesheet' href='{$webBaseDir}modules/admin_code_editor/code_mirror/dialog.css'>\n";
$content['header'] .= "<script src='{$webBaseDir}modules/admin_code_editor/code_mirror/codemirror.js'></script>\n";
$content['header'] .= "<script src='{$webBaseDir}modules/admin_code_editor/code_mirror/xml.js'></script>\n";
$content['header'] .= "<script src='{$webBaseDir}modules/admin_code_editor/code_mirror/javascript.js'></script>\n";
$content['header'] .= "<script src='{$webBaseDir}modules/admin_code_editor/code_mirror/css.js'></script>\n";
$content['header'] .= "<script src='{$webBaseDir}modules/admin_code_editor/code_mirror/clike.js'></script>\n";
$content['header'] .= "<script src='{$webBaseDir}modules/admin_code_editor/code_mirror/php.js'></script>\n";
$content['header'] .= "<script src='{$webBaseDir}modules/admin_code_editor/code_mirror/search.js'></script>\n";
$content['header'] .= "<script src='{$webBaseDir}modules/admin_code_editor/code_mirror/searchcursor.js'></script>\n";
$content['header'] .= "<script src='{$webBaseDir}modules/admin_code_editor/code_mirror/dialog.js'></script>\n";
$content['header'] .= "<style type='text/css'>
.CodeMirror
{
border-top: 1px solid black;
border-bottom: 1px solid black;
background-color: white;
    overflow: auto;
    color: black;
    height: 500px;
    width: 500px;
}

.toolBar
{
border: solid 1px black;
background-color: #E0E0E0;
margin-bottom: 5px;
width: 100%;
color: black;
}

.toolBar > a > img
{
    border: outset 1px;
    padding: 2px;
    margin: 2px;
}

.toolBar > span
{
 text-align: right;
 display: block;
 float: right;
 margin: 8px;
}

.fileBrowser
{
height: 540px;
width: 220px;
overflow: auto;
left: 5px;
background-color: white;
border: solid 1px black;
}

.CodeMirror-scroll
{
    height: 100%;
    overflow: none;
}

.activeline {background: #e8f2ff !important;}
</style>\n";

global $knownFiles;
$knownFiles = array();

function DisplayDir($dir)
{
    global $webBaseDir, $baseDir, $knownFiles;
    
    $result = "";
    $files = scandir($dir);
    sort($files);
    foreach ($files as $f) {
        if ($f == "." || $f == "..") {
            continue;
        }
        if (is_dir("$dir/$f")) {
            $fileName = substr("$dir/$f", strlen($baseDir . "/modules/"));
            $grp = str_replace("/", "_", $fileName);
            $sub = DisplayDir("$dir/$f");
            if ($sub != "") {
                $result .= "<a href='#' onclick='return expandContractFolder(\"$grp\");'><img src='{$webBaseDir}modules/admin_code_editor/icons/folder.png' border='0' id='img_$grp'> $f</a><br>";
                if (isset($_GET["f"]) && strpos($sub, "&f=" . urlencode($_GET["f"]) . "'") !== false) {
                    $result .= "<div style='margin-left: 10px; visibility: visible; position: relative;' id='fld_$grp'>$sub</div>";
                    $result .= "<script>setTimeout(\"document.getElementById('img_$grp').scrollIntoView(true);\",100);</script>";
                } else {
                    $result .= "<div style='margin-left: 10px; visibility: hidden; position: absolute;' id='fld_$grp'>$sub</div>";
                }
            }
        }
    }
    
    foreach ($files as $f) {
        if ($f == "." || $f == "..") {
            continue;
        }
        if (is_dir("$dir/$f")) {
            continue;
        } else if (preg_match("/\\.(css|js|php|xml)\$/i", $f)) {
            $fileName = substr("$dir/$f", strlen($baseDir . "/modules/"));
            $result .= "<img src='{$webBaseDir}modules/admin_code_editor/icons/page_code.png'><a href='index.php?p=admin_code_editor&f=" . urlencode($fileName) . "'>$f</a><br>";
            $knownFiles[] = $fileName;
        }
    }
    return $result;
}

echo "<table class='plainTable'>";
echo "<tr valign='top'><td width='1%'>";
echo "<div class='fileBrowser'>";
echo DisplayDir("$baseDir/modules");
echo "</div>";
echo "</td><td>";

if (isset($_GET["f"]) && in_array($_GET["f"], $knownFiles)) {
    global $demoEngine;
    
    if (isset($_POST["code"]) && GetConfigValue("codeEditorAllowSave") == "true" && !(isset($demoEngine) && $demoEngine == true)) {
        $file = fopen("$baseDir/modules/" . $_GET["f"], "w");
        fwrite($file, $_POST["code"]);
        fclose($file);
    }
    
    echo "<div class='toolBar'>";
    if (GetConfigValue("codeEditorAllowSave") == "true") {
        echo "<a href='#' onclick='return saveDoc();'><img src='{$webBaseDir}modules/admin_code_editor/icons/disk.png'></a>";
    }
    echo "<a href='#' onclick='return codeFind();'><img src='{$webBaseDir}modules/admin_code_editor/icons/find.png'></a>";
    echo "<a href='#' onclick='return codeFindNext();'><img src='{$webBaseDir}modules/admin_code_editor/icons/find_next.png'></a>";
    if (GetConfigValue("codeEditorAllowSave") == "true") {
        echo "<a href='#' onclick='return codeReplace();'><img src='{$webBaseDir}modules/admin_code_editor/icons/replace.png'></a>";
        echo "<a href='#' onclick='editor.undo();return false;'><img src='{$webBaseDir}modules/admin_code_editor/icons/arrow_undo.png'></a>";
        echo "<a href='#' onclick='editor.redo();return false;'><img src='{$webBaseDir}modules/admin_code_editor/icons/arrow_redo.png'></a>";
    }
    echo "<span>" . $_GET["f"] . "</span>";
    echo "</div>";
    
    echo "<form name='frmDocument' method='post'>";
    echo "<textarea id='code' name='code'>";
    if (isset($demoEngine) && $demoEngine == true) {
        echo "Due to the demo restrictions, you cannot browse the code directly.";
    } else {
        echo str_replace(array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"),
            file_get_contents("$baseDir/modules/" . $_GET["f"]));
    }
    echo "</textarea></form>";
    echo "</td>";
}

echo "</tr></table>";
Ajax::IncludeLib();

$content['footerScript'] .= "<script>
var editor = CodeMirror.fromTextArea(document.getElementById('code'), {
lineNumbers: true,
matchBrackets: true,
mode: 'application/x-httpd-php',
indentUnit: 4,
indentWithTabs: true,
autofocus: true,
enterMode: 'keep',\n";
if (GetConfigValue("codeEditorAllowSave") != "true") {
    $content['footerScript'] .= "readOnly: true,";
}
$content['footerScript'] .= "tabMode: 'shift',
onCursorActivity: function() {
    editor.setLineClass(hlLine, null, null);
    hlLine = editor.setLineClass(editor.getCursor().line, null, 'activeline');
  }
});

var hlLine = editor.setLineClass(0, 'activeline');

function expandContractFolder(grp)
{
  var div=document.getElementById('fld_'+grp);
  if(div.style.visibility == 'hidden')
  {
	  div.style.visibility='visible';
	  div.style.position='relative';
  }
  else
  {
	  div.style.visibility='hidden';
	  div.style.position='absolute';
  }
  return false;
}

function saveDoc()
{
  document.forms['frmDocument'].submit();      
  return false;
}

function codeFind()
{
  CodeMirror.commands['find'](editor);
  return false;
}

function codeFindNext()
{
  CodeMirror.commands['findNext'](editor);
  return false;
}

function codeReplace()
{
  CodeMirror.commands['replace'](editor);
  return false;
}

function initCodeEditor()
{
";
if (isset($_GET["l"])) {
    $content['footerScript'] .= "    editor.setCursor(" . ($_GET["l"] - 1) . ",0);\n";
}
$content['footerScript'] .= "    \$('.CodeMirror').first().width(\$('.toolBar').first().width());
}

\$(window).resize(function() { \$('.CodeMirror').first().width(\$('.toolBar').first().width()); });
setTimeout('initCodeEditor()',100);
</script>";