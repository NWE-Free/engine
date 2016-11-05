<?php
include "../../libs/common.php";
include "../../libs/db.php";
include "../../libs/items.php";
include "../../libs/template.php";
include "../../libs/roles.php";
InitModules();
session_start();
include "$baseDir/config/config.php";
$db = new Database($dbhost, $dbuser, $dbpass, $dbname);
?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
<head>
    <title><?php echo $gameName; ?></title>
    <link rel="icon" type="image/png"
          href="<?php echo $webBaseDir; ?>favicon.ico"/>
<?php
// Search a css file inside the template directory
$files = scandir("$baseDir/templates/$template");
foreach ($files as $f) {
    if (preg_match("/\\.css$/i", $f) == 1) {
        echo "<link href=\"{$webBaseDir}templates/$template/$f\" type=\"text/css\" rel=\"stylesheet\" />";
        break;
    }
}
echo "</head>\n";
echo "<body>\n";
$obj = Item::GetObjectInfo(intval($_GET["id"]));
TableHeader($obj->name, false);
echo "<div class='evenLine'>";

if ($obj->image_file != null) {
    echo "<center><img src='{$webBaseDir}modules/inventory/images/$obj->image_file'></center><br>";
}

echo $obj->description . "<br><br>";

$attr = $obj->GetAttributes();

echo "<table class='plainTable'>";
$ignoreAttr = array(
    "id",
    "name",
    "description",
    "allow_fraction",
    "quest_item",
    "object_type_id",
    "usage_label",
    "usage_code",
    "image_file",
    "requirements"
);
foreach ($attr as $key => $val) {
    if (in_array($key, $ignoreAttr)) {
        continue;
    }
    if ($val == null) {
        continue;
    }
    echo "<tr><td width='1%'><b>" . str_replace(array("_", " "), array("&nbsp;", "&nbsp;"),
            $key) . ":</b></td><td>$val</td></tr>";
}
echo "</table>";

echo "<center>";
LinkButton("Close", "#", "window.close();return false;");
echo "</center>";
echo "</div>";
TableFooter();

echo "</body>\n";
echo "</html>\n";
$db->Close();
