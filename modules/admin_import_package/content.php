<?php
// Not an admin? Go away!
if (!IsSuperUser()) {
    header("Location: index.php");
    return;
}

if (isset($_FILES['packageFile'])) {
    global $demoEngine;
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
        return;
    }
    
    $data = file_get_contents($_FILES['packageFile']['tmp_name']);
    $data = unserialize(gzuncompress($data));
    
    if (!isset($data["type"]) || !in_array($data["type"], array("module", "template", "fonts"))) {
        ErrorMessage("Wrong package type.");
        return;
    }
    
    if (StoreInstallModule($data['name'], $data['data'], $data['type'])) {
        ResultMessage("Module correctly installed.");
    }
    
    CleanHookCache();
}

TableHeader("Upload module or template package");
echo "<form enctype='multipart/form-data' method='post' name='frmUploadModule'>";
echo "<input type='file' name='packageFile'>";
echo "</form>";
TableFooter();

ButtonArea();
SubmitButton("Upload");
EndButtonArea();
