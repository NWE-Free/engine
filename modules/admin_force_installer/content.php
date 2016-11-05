<?php
// Not an admin? Go away!
if (!IsSuperUser()) {
    header("Location: index.php");
    return;
}

if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    global $demoEngine;
    if (isset($demoEngine) && $demoEngine === true) {
        ErrorMessage("Disabled in the demo");
        return;
    }
    
    $file = fopen("$baseDir/install/installer.marker", "w");
    fwrite($file, "run the installer.");
    fclose($file);
    
    session_unset();
    
    ResultMessage("Please wait...");
    echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
    return;
}

TableHeader("Run the installer?");
echo Translate("Are you sure you want to re-run the installer?");
TableFooter();

ButtonArea();
LinkButton("Yes", "index.php?p=admin_force_installer&confirm=yes");
LinkButton("No", "index.php?p=admin_panel");
EndButtonArea();
