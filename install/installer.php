<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title>New Worlds Engine - Installer</title>
    <link href="install/install.css" type="text/css" rel="stylesheet"/>
</head>
<body>
<h1>New Worlds Engine Installer</h1>
<?php

function ShowInstallStep()
{
    echo "<div id='progressionDiv'>
    <div>Identification</div>
    <div>Main settings</div>
    <div>Configuration check</div>
    <div>Schema installation</div>
    <div>Administrator account</div>
    <div>Modules installation</div>
    <div>Modules setup</div>
    <div>Package installation</div>
    <div>Finished</div>
    </div>";
}

function ShowUpgradeStep()
{
    echo "<div id='progressionDiv'>
<div>Identification</div>
<div>Upgrade structure</div>
<div>Install new modules</div>
<div>Upgrade schema</div>
<div>Upgrade package</div>
<div>Cleanup</div>
</div>";
}

echo "<div id='versionDiv'>Engine version: " . GetModuleVersion("") . "<br>&copy; FunMayhem.com - 2012-2015</div>";

function ShowPosition($pos)
{
    echo "<style>#progressionDiv > div:nth-child($pos) { color: #008000; } #progressionDiv > div:nth-child($pos):before {content: '=>'; }</style>";
}

// Verify that the file has not be started manually
$s = explode('/', $_SERVER['SCRIPT_NAME']);
$script = array_pop($s);
if ($script != "index.php") {
    echo "<script>document.location='../index.php';</script>";
    return;
}

// Required for the template
include "$baseDir/config/config.php";
include "$baseDir/libs/db.php";
include "$baseDir/libs/template.php";

$filesOk = true;
$errMessage = "";
if (!is_writable("$baseDir/config/config.php")) {
    $errMessage .= "<li>" . Translate("The file %s is not writable. Installation cannot continue until %s is writable.",
            "config/config.php", "config/config.php") . "</li>";
    $filesOk = false;
}
if (!is_writable("$baseDir/config/auto_defines.php")) {
    $errMessage .= "<li>" . Translate("The file %s is not writable. Installation cannot continue until %s is writable.",
            "config/auto_defines.php", "config/auto_defines.php") . "</li>";
    $filesOk = false;
}
if (!is_writable("$baseDir/language/en.xml")) {
    $errMessage .= "<li>" . Translate("The file %s is not writable. Installation cannot continue until %s is writable.",
            "language/en.xml", "language/en.xml") . "</li>";
    $filesOk = false;
}
if (!is_writable("$baseDir/cached_libs")) {
    $errMessage .= "<li>" . Translate("The directory %s is not writable. Installation cannot continue until %s is writable.",
            "cached_libs", "cached_libs") . "</li>";
    $filesOk = false;
}

if (!$filesOk) {
    ErrorMessage("Please correct the following issues.");
    echo "<ul>" . $errMessage . "</ul>";
    return;
}

if (!CheckLicense($engineLicenseKey)) {
    ErrorMessage("License invalid. Please purchase a valid license at: <a href='http://nwe.funmayhem.com/'>New Worlds Engine</a>.");
    return;
}

// Install password has been provided
if (isset($_POST['installPass'])) {
    // There was no install password at first.
    if ($installerPassword == "") {
        if (trim($_POST['installPass']) != "" && trim($_POST['installPass']) == trim($_POST['installPassConfirm'])) {
            $_SESSION["installPass"] = $_POST['installPass'];
        } else if (trim($_POST['installPass']) == "") {
            ErrorMessage("Cannot be empty.");
        } else {
            ErrorMessage("Passwords do not match.");
        }
    } // Check if it is correct.
    else if (trim($_POST['installPass']) == $installerPassword) {
        $_SESSION["installPass"] = $_POST['installPass'];
    }
}

// No install password in the config, ask for one.
if ($installerPassword == "" && !isset($_SESSION["installPass"])) {
    if (CheckRequirements() == false) {
        return;
    }
    
    ShowInstallStep();
    ShowPosition(1);
    echo "<h2>" . Translate("Identification") . "</h2>";
    
    echo "<form method='post' autocomplete='off'>";
    echo Translate("Please choose a password to be used during the installation process.") . "<br>";
    echo Translate("This password will be stored in the config.php file and will be requested in case you want to re-install.") . "<br>";
    echo "<table>";
    echo "<tr valign='top'><td>" . Translate("Password") . ":</td>";
    echo "<td><input type='password' name='installPass'></td></tr>";
    echo "<tr valign='top'><td>" . Translate("Confirm") . ":</td>";
    echo "<td><input type='password' name='installPassConfirm'></td></tr>";
    echo "</table>";
    ButtonArea();
    SubmitButton("Start");
    EndButtonArea();
    echo "</form>";
    return;
}

// Check if the password provided is correct.
if (!isset($_SESSION["installPass"])) {
    if (CheckRequirements() == false) {
        return;
    }
    
    ShowInstallStep();
    ShowPosition(1);
    echo "<h2>" . Translate("Identification") . "</h2>";
    
    echo "<form method='post' autocomplete='off'>";
    echo "<table>";
    echo "<tr valign='top'><td>" . Translate("Installer password<br>(Is set in the config/config.php file)") . "</td>";
    echo "<td><input type='password' name='installPass'></td></tr>";
    echo "</table>";
    ButtonArea();
    SubmitButton("Start");
    EndButtonArea();
    echo "</form>";
    return;
}

if (isset($_GET["stopall"]) && $_GET["stopall"] == true) {
    @unlink("$baseDir/install/installer.marker");
    echo "<script>document.location='index.php?v=" . rand() . "';</script>";
    return;
}

if (!isset($_SESSION['install_step'])) {
    $_SESSION['install_step'] = 0;
}
$step = $_SESSION['install_step'];

if (isset($_GET['step'])) {
    $step = intval($_GET['step']);
}

if (!isset($_SESSION['install_upgrade'])) {
    $conn = new Database($dbhost, $dbuser, $dbpass, $dbname);
    if ($conn->error != null) {
        $_SESSION['install_upgrade'] = false;
    } else {
        $result = $conn->Execute("select * from users where 1 = 2");
        if ($result === false) {
            $_SESSION['install_upgrade'] = false;
        } else {
            // We asked if the user want an upgrade or a fresh install, now we
            // check
            // the answer
            if (isset($_GET['confirm'])) {
                if ($_GET['confirm'] == 'yes') {
                    $result = $conn->Execute("select version from modules where name = 'MainEngine'");
                    if ($result->EOF) {
                        $_SESSION['install_upgrade'] = false;
                    } else {
                        $_SESSION['install_upgrade'] = true;
                        $_SESSION['install_upgrade_from'] = $result->fields[0];
                    }
                    $result->Close();
                } else {
                    $_SESSION['install_upgrade'] = false;
                    $_SESSION['install_skip_upgrade'] = true;
                }
            } else {
                ShowPosition(1);
                ShowUpgradeStep();
                echo "<h2>" . Translate("Upgrade?") . "</h2>";
                echo Translate("You already have a previous version installed, do you want to continue with the upgrade?") . "<br><br>";
                ButtonArea();
                LinkButton("Upgrade", "index.php?confirm=yes");
                LinkButton("Re-Install", "index.php?confirm=no");
                LinkButton("Cancel Installation", "index.php?stopall=true");
                EndButtonArea();
                $conn->Close();
                return;
            }
        }
    }
    $conn->Close();
}

if ($_SESSION['install_upgrade'] === true) {
    UpgradeStep($step);
} else {
    InstallStep($step);
}

function CheckRequirements()
{
    echo "<h2>" . Translate("Check Requirements") . "</h2>";
    $phpversion = substr(PHP_VERSION, 0, 6);
    echo "<table class='checkResults'>";
    echo "<tr><td>" . Translate("Requires") . "</td><td>" . Translate("Result") . "</td></tr>";
    $allOk = true;
    if ($phpversion >= 5.1) {
        echo "<tr><td>PHP Version &gt;= 5.1</td><td><b style='color: green;'>Passed ($phpversion)</b></td></tr>";
    } else {
        echo "<tr><td>PHP Version &gt;= 5.1</td><td><b style='color: red;'>Failed ($phpversion)</b></td></tr>";
        $allOk = false;
    }
    
    // Go through all the extensions required
    $neededExtensions = array(
        "gd" => "GD",
        "mysqli" => "MySQLi",
        "session" => "Session",
        "xmlreader" => "XMLReader",
        "xmlwriter" => "XMLWriter",
        "pcre" => "PCRE",
        "dom" => "DOM"
    );
    foreach ($neededExtensions as $key => $val) {
        if (extension_loaded($key)) {
            echo "<tr><td>$val</td><td><b style='color: green;'>" . Translate("Passed") . "</b></td></tr>";
        } else {
            echo "<tr><td>$val</td><td><b style='color: red;'>" . Translate("Failed") . "</b></td></tr>";
            $allOk = false;
        }
    }
    echo "</table><br>";
    
    // Go through all the
    $wishedExtensions = array("zlib" => "ZLib", "curl" => "cURL");
    echo "<table class='checkResults'>";
    echo "<tr><td>" . Translate("Wished") . "</td><td>" . Translate("Result") . "</td></tr>";
    foreach ($wishedExtensions as $key => $val) {
        if (extension_loaded($key)) {
            echo "<tr><td>$val</td><td><b style='color: green;'>" . Translate("Passed") . "</b></td></tr>";
        } else {
            echo "<tr><td>$val</td><td><b style='color: #E06000;'>" . Translate("Missing but works anyhow") . "</b></td></tr>";
        }
    }
    echo "</table><br><br>";
    
    return $allOk;
}

function UpgradeStep($step)
{
    global $baseDir, $modules, $allModules;
    include "config/config.php";
    ShowUpgradeStep();
    
    switch ($step) {
        // Upgrade the main engine and the package
        case 0:
            ShowPosition(2);
            echo "<h2>" . Translate("Upgrading database structure.") . "</h2>";
            
            $conn = new Database($dbhost, $dbuser, $dbpass, $dbname);
            
            // Upgrade the base
            $files = FindUpgrades("$baseDir/install", $_SESSION['install_upgrade_from']);
            $hasErrors = false;
            foreach ($files as $v => $file) {
                $sql = file_get_contents("$baseDir/install/$file");
                $sql = str_replace("\r\n", "\n", $sql);
                $statements = explode(";\n", $sql);
                foreach ($statements as $cmd) {
                    if (trim($cmd) == "") {
                        continue;
                    }
                    
                    if ($conn->Execute($cmd) === false) {
                        ErrorMessage(Translate("Error whilst executing:<br>%s", $cmd), false);
                        $hasErrors = true;
                    }
                }
            }
            
            if (!$hasErrors) {
                $conn->Execute("replace into modules(name,version) values(?,?)", "MainEngine", GetModuleVersion(""));
                
                $step++;
                ResultMessage("Done. Installation will continue...");
                echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
            }
            $conn->close();
            break;
        // Installing new modules.
        // Will check what .SQL files exists and also register the variables
        case 1:
            ShowPosition(3);
            echo "<h2>" . Translate("Installing new modules.") . "</h2>";
            
            $conn = new Database($dbhost, $dbuser, $dbpass, $dbname);
            $result = $conn->Execute("select name from modules");
            $installed = array();
            while (!$result->EOF) {
                $installed[] = $result->fields[0];
                $result->MoveNext();
            }
            
            $hasErrors = false;
            
            foreach ($allModules as $module) {
                if (!in_array($module, $installed)) {
                    echo "- $module<br>";
                    if (!InstallModule($conn, $module)) {
                        $hasErrors = true;
                    } else {
                        RegisterModuleVariables($module);
                    }
                }
            }
            $conn->close();
            
            if (!$hasErrors) {
                $step++;
                ResultMessage("Done. Installation will continue...");
                echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
            }
            break;
        // Upgrade the SQL for the existing modules.
        case 2:
            ShowPosition(4);
            echo "<h2>" . Translate("Upgrading modules.") . "</h2>";
            
            $hasErrors = false;
            $conn = new Database($dbhost, $dbuser, $dbpass, $dbname);
            $result = $conn->Execute("select name,version from modules");
            while (!$result->EOF) {
                if ($result->fields[0] != "MainEngine") {
                    if (!UpgradeModule($conn, $result->fields[0], $result->fields[1])) {
                        $hasErrors = true;
                    } else {
                        RegisterModuleVariables($result->fields[0]);
                    }
                }
                $result->MoveNext();
            }
            
            $conn->close();
            
            if (!$hasErrors) {
                $step++;
                ResultMessage("Done. Installation will continue...");
                echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
            }
            break;
        // Upgrade the package
        case 3:
            ShowPosition(5);
            echo "<h2>" . Translate("Upgrading package.") . "</h2>";
            
            $hasErrors = false;
            $conn = new Database($dbhost, $dbuser, $dbpass, $dbname);
            
            $files = FindUpgrades("$baseDir/install", $_SESSION['install_upgrade_from'],
                "/^package-([0-9_]+)\\.sql\$/");
            foreach ($files as $v => $file) {
                $sql = file_get_contents("$baseDir/install/$file");
                $sql = str_replace("\r\n", "\n", $sql);
                $statements = explode(";\n", $sql);
                foreach ($statements as $cmd) {
                    if (trim($cmd) == "") {
                        continue;
                    }
                    
                    if ($conn->Execute($cmd) === false) {
                        ErrorMessage(Translate("Error whilst executing:<br>%s", $cmd), false);
                        $hasErrors = true;
                    }
                }
            }
            
            if (!$hasErrors) {
                $conn->Execute("replace into modules(name,version) values(?,?)", "MainEngine", GetModuleVersion(""));
                
                $step++;
                ResultMessage("Done. Installation will continue...");
                echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
            }
            $conn->close();
            break;
        // Upgrade finished.
        case 4:
            ShowPosition(6);
            $caches = scandir("$baseDir/cached_libs");
            foreach ($caches as $i) {
                if (preg_match("/\\.php\$/", $i) == 1) {
                    unlink("$baseDir/cached_libs/$i");
                }
            }
            
            echo "<h2>" . Translate("Installation finished") . "</h2>";
            ResultMessage("The installation was successful");
            echo Translate("The installation was successful, you may now reload and log in with your administrator account.");
            unlink("$baseDir/install/installer.marker");
            session_unset();
            ButtonArea();
            LinkButton("Continue", "index.php");
            EndButtonArea();
            return;
        default:
            echo "Error.";
    }
    $_SESSION['install_step'] = $step;
}

function InstallStep($step)
{
    global $baseDir, $modules, $allModules;
    include "config/config.php";
    
    ShowInstallStep();
    
    if (isset($_SESSION['gameName'])) {
        $gameName = $_SESSION['gameName'];
    }
    if (isset($_SESSION['dbhost'])) {
        $dbhost = $_SESSION['dbhost'];
    }
    if (isset($_SESSION['dbuser'])) {
        $dbuser = $_SESSION['dbuser'];
    }
    if (isset($_SESSION['dbpass'])) {
        $dbpass = $_SESSION['dbpass'];
    }
    if (isset($_SESSION['dbname'])) {
        $dbname = $_SESSION['dbname'];
    }
    
    switch ($step) {
        // Main configuration
        case 0:
            ShowPosition(2);
            echo "<h2>" . Translate("Main settings") . "</h2>";
            
            echo Translate("Welcome to the New Worlds Engine Installer.<br> This installer will help you setup the database as well as define the base settings for your game.");
            echo "<br><br>";
            echo "<form method='post' action='index.php?step=1' autocomplete='off'>";
            
            echo "<table>";
            echo "<tr><td>" . Translate("Game name") . ":</td><td><input type=text name='gameName' value=\"$gameName\"></td></tr>";
            echo "<tr><td>" . Translate("Database host") . ":</td><td><input type=text name='dbhost' value=\"$dbhost\"></td></tr>";
            echo "<tr><td>" . Translate("Database username") . ":</td><td><input type=text name='dbuser' value=\"$dbuser\"></td></tr>";
            echo "<tr><td>" . Translate("Database password") . ":</td><td><input type=text name='dbpass' value=\"$dbpass\"></td></tr>";
            echo "<tr><td>" . Translate("Database name") . ":</td><td><input type=text name='dbname' value=\"$dbname\"></td></tr>";
            echo "</table>";
            
            ButtonArea();
            SubmitButton("Continue");
            EndButtonArea();
            
            echo "</form>";
            break;
        // Database check
        case 1:
            ShowPosition(3);
            if (trim($_POST['gameName']) != "") {
                $_SESSION['gameName'] = trim($_POST['gameName']);
            }
            if (trim($_POST['dbhost']) != "") {
                $_SESSION['dbhost'] = trim($_POST['dbhost']);
            }
            if (trim($_POST['dbuser']) != "") {
                $_SESSION['dbuser'] = trim($_POST['dbuser']);
            }
            if (trim($_POST['dbpass']) != "") {
                $_SESSION['dbpass'] = trim($_POST['dbpass']);
            }
            if (trim($_POST['dbname']) != "") {
                $_SESSION['dbname'] = trim($_POST['dbname']);
            }
            
            if (isset($_SESSION['gameName'])) {
                $gameName = $_SESSION['gameName'];
            }
            if (isset($_SESSION['dbhost'])) {
                $dbhost = $_SESSION['dbhost'];
            }
            if (isset($_SESSION['dbuser'])) {
                $dbuser = $_SESSION['dbuser'];
            }
            if (isset($_SESSION['dbpass'])) {
                $dbpass = $_SESSION['dbpass'];
            }
            if (isset($_SESSION['dbname'])) {
                $dbname = $_SESSION['dbname'];
            }
            
            $conn = new Database($dbhost, $dbuser, $dbpass, $dbname);
            if ($conn->error != null) {
                ErrorMessage(Translate("Database configuration is Incorrect: %s", $conn->error), false);
                if (strpos($conn->error, "Unknown database") !== false) {
                    ErrorMessage("You must create the database first or specify an existing database.");
                }
                if (strpos($conn->error, "No connection could be made") !== false) {
                    ErrorMessage("The database host name is Incorrect.");
                }
                if (strpos($conn->error, "Access denied") !== false) {
                    ErrorMessage("Please double check the database username and password.");
                }
                $step = 0;
                InstallStep(0);
                break;
            }
            $conn->close();
            
            // Write back the configuration file with the new information
            $file = fopen("$baseDir/config/config.php", "w");
            fwrite($file, "<?php\n");
            fwrite($file, "// Modify it before upload for more security.\n");
            fwrite($file, "\$installerPassword=\"{$_SESSION["installPass"]}\";\n");
            fwrite($file, "\n");
            fwrite($file, "// Your game name\n");
            fwrite($file, "\$gameName=rawurldecode(\"" . rawurlencode($gameName) . "\");\n");
            fwrite($file, "\n");
            fwrite($file, "// Web base directory of your game\n");
            fwrite($file,
                "\$webBaseDir=\"" . substr($_SERVER["SCRIPT_NAME"], 0, strlen($_SERVER["SCRIPT_NAME"]) - 9) . "\";\n");
            fwrite($file, "\n");
            fwrite($file, "// Main template\n");
            fwrite($file, "\$template=\"$template\";\n");
            fwrite($file, "\n");
            fwrite($file, "// Database host\n");
            fwrite($file, "\$dbhost=\"$dbhost\";\n");
            fwrite($file, "// Database username\n");
            fwrite($file, "\$dbuser=\"$dbuser\";\n");
            fwrite($file, "// Database password\n");
            fwrite($file, "\$dbpass=\"$dbpass\";\n");
            fwrite($file, "// Database name\n");
            fwrite($file, "\$dbname=\"$dbname\";\n");
            fwrite($file, "\n");
            fwrite($file, "// Default module to load once logged in\n");
            fwrite($file, "\$defaultModule=\"$defaultModule\";\n");
            fwrite($file, "\n");
            fwrite($file, "// Default module for non-logged users\n");
            fwrite($file, "\$defaultPublic=\"$defaultPublic\";\n");
            fwrite($file, "// Default template for non-logged users\n");
            fwrite($file, "\$publicTemplate=\"$publicTemplate\";\n");
            fwrite($file, "\n");
            fwrite($file, "// Default language for the game\n");
            fwrite($file, "\$language=\"$language\";\n");
            fwrite($file, "\n");
            fwrite($file, "// Config values stored of file system\n");
            fwrite($file, "\$storeXmlConfig = FALSE;\n");
            fwrite($file, "\n");
            fwrite($file, "// If set to true, the error details will be display for all even non-admins.\n");
            fwrite($file, "\$alwaysShowErrorDetails = FALSE;\n");
            fwrite($file, "\n");
            fwrite($file, "// If set to true the hook file will be cached.\n");
            if ($hookCache == false) {
                fwrite($file, "\$hookCache = FALSE;\n");
            } else {
                fwrite($file, "\$hookCache = TRUE;\n");
            }
            fwrite($file, "\n");
            fwrite($file, "// If set to true HTML will be blocked in all GET and POST\n");
            if (!isset($blockHTML) || $blockHTML == true) {
                fwrite($file, "\$blockHTML = TRUE;\n");
            } else {
                fwrite($file, "\$blockHTML = FALSE;\n");
            }
            fwrite($file, "\n");
            fwrite($file,
                "// If set to false, static content like JPG, GIF, PNG, JS and CSS will not be cached. Good while developing.\n");
            if (!isset($enableHTTPCache) || $enableHTTPCache == true) {
                fwrite($file, "\$enableHTTPCache = TRUE;\n");
            } else {
                fwrite($file, "\$enableHTTPCache = FALSE;\n");
            }
            fclose($file);
            
            $step++;
            echo "<h2>" . Translate("Writing config.php file.") . "</h2>";
            ResultMessage("Done. Installation will continue...");
            echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
            break;
        // Main table structure
        case 2:
            ShowPosition(4);
            $conn = new Database($dbhost, $dbuser, $dbpass, $dbname);
            // Shall we do an upgrade
            if (isset($_SESSION['install_skip_upgrade']) && $_SESSION['install_skip_upgrade'] == true) {
            } else if (isset($_GET['confirm']) == true) {
                // Yes? then let's read back the version
                if ($_GET['confirm'] == 'yes') {
                    $step = 0;
                    $_SESSION['install_upgrade'] = true;
                    $result = $conn->Execute("select version from modules where name = 'MainEngine'");
                    $_SESSION['install_upgrade_from'] = $result->fields[0];
                    $result->Close();
                    $conn->Close();
                    ResultMessage("Going to upgrade.");
                    echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
                    break;
                } else // No then let's continue;
                {
                }
            } else {
                // Check if there is already a modules table. If yes we shall
                // ask if we must do an upgrade.
                $result = $conn->Execute("select version from modules where name = 'MainEngine'");
                if ($result !== false) {
                    $result->Close();
                    $conn->Close();
                    echo "<h2>" . Translate("Upgrade?") . "</h2>";
                    echo Translate("You already have a previous version installed, do you want to upgrade?") . "<br><br>";
                    ButtonArea();
                    LinkButton("Upgrade", "index.php?confirm=yes");
                    LinkButton("Re-Install", "index.php?confirm=no");
                    EndButtonArea();
                    break;
                }
            }
            
            echo "<h2>" . Translate("Setting up database structure.") . "</h2>";
            $hasErrors = false;
            echo "<b>" . Translate("Creating tables") . ":</b><br>";
            $sql = file_get_contents("$baseDir/install/tables.sql");
            $sql = str_replace("\r\n", "\n", $sql);
            $statements = explode(";\n", $sql);
            foreach ($statements as $cmd) {
                if (trim($cmd) == "") {
                    continue;
                }
                if (strncasecmp($cmd, "create table ", 13) == 0) {
                    $table = substr($cmd, 13);
                    $table = trim(substr($table, 0, strpos($table, '(') - 1));
                    echo "- $table<br>";
                }
                if ($conn->Execute($cmd) === false) {
                    ErrorMessage(Translate("Error whilst executing:<br>%s", $cmd), false);
                    $hasErrors = true;
                }
            }
            
            $conn->Execute("replace into modules(name,version) values(?,?)", "MainEngine", GetModuleVersion(""));
            
            if (!$hasErrors) {
                $step++;
                ResultMessage("Done. Installation will continue...");
                echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
            }
            $conn->close();
            break;
        // Administrator account
        case 3:
            ShowPosition(5);
            echo "<h2>" . Translate("Creating administrator account.") . "</h2>";
            
            // Dev engine without register
            if (!file_exists("$baseDir/modules/register/public.php")) {
                $conn = new Database($dbhost, $dbuser, $dbpass, $dbname);
                $conn->Execute("insert into users(username,password,email,created_on) values(?,?,?,now())",
                    "Administrator", "*", "admin@localhost");
                $conn->Execute("insert into user_roles(user_id,role_id) value(" . $conn->LastId() . ",1000)");
                
                $conn->Execute("insert into users(username,password,email,created_on) values(?,?,?,now())", "Player",
                    "*", "player@localhost");
                $conn->close();
                
                $step++;
                
                ResultMessage("Done. Installation will continue...");
                echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
                break;
            } else {
                if (isset($_POST['adminPass']) && $_POST['confirmAdminPass'] != $_POST['adminPass']) {
                    ErrorMessage("Passwords do not match.");
                } else if (isset($_POST['adminUser']) && trim($_POST['adminUser']) != "" && isset($_POST['adminPass']) && trim($_POST['adminPass']) != "" && isset($_POST['adminEmail']) && trim($_POST['adminEmail']) != "") {
                    $conn = new Database($dbhost, $dbuser, $dbpass, $dbname);
                    $a = trim($_POST['adminUser']);
                    $b = md5(substr(strtolower(trim($_POST["adminUser"])), 0, 2) . $_POST["adminPass"]);
                    $c = trim($_POST['adminEmail']);
                    $conn->Execute("insert into users(username,password,email,created_on) values(?,?,?,now())", $a, $b,
                        $c);
                    $conn->Execute("insert into user_roles(user_id,role_id) value(" . $conn->LastId() . ",1000)");
                    $conn->close();
                    $step++;
                    ResultMessage("Done. Installation will continue...");
                    echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
                    break;
                }
                if (!isset($_POST['adminUser'])) {
                    $_POST['adminUser'] = 'Administrator';
                }
                if (!isset($_POST['adminPass'])) {
                    $_POST['adminPass'] = '';
                }
                if (!isset($_POST['confirmAdminPass'])) {
                    $_POST['confirmAdminPass'] = '';
                }
                if (!isset($_POST['adminEmail'])) {
                    $_POST['adminEmail'] = 'admin@localhost';
                }
                echo "<form method='post' action='index.php' autocomplete='off'>";
                echo Translate("We will now create an administrator account which will grant administrator rights within the administration panel.");
                echo "<table>";
                echo "<tr><td><b>" . Translate('Administrator username') . ":</b></td><td><input type='text' name='adminUser' value='" . $_POST['adminUser'] . "'></td></tr>";
                echo "<tr><td><b>" . Translate('Administrator password') . ":</b></td><td><input type='password' name='adminPass' value='" . $_POST['adminPass'] . "'></td></tr>";
                echo "<tr><td><b>" . Translate('Confirm password') . ":</b></td><td><input type='password' name='confirmAdminPass' value='" . $_POST['confirmAdminPass'] . "'></td></tr>";
                echo "<tr><td><b>" . Translate('Administrator email') . ":</b></td><td><input type='text' name='adminEmail' value='" . $_POST['adminEmail'] . "'></td></tr>";
                echo "</table>";
                ButtonArea();
                SubmitButton("Continue");
                EndButtonArea();
                echo "</form>";
            }
            break;
        // Run the module install.sql files
        case 4:
            ShowPosition(6);
            $hasErrors = false;
            echo "<h2>" . Translate("Installing modules.") . "</h2>";
            echo "<b>" . Translate("Modules") . ":</b><br>";
            $conn = new Database($dbhost, $dbuser, $dbpass, $dbname);
            foreach ($allModules as $module) {
                echo "- $module<br>";
                if (!InstallModule($conn, $module)) {
                    $hasErrors = true;
                }
            }
            if (!$hasErrors) {
                $step++;
                ResultMessage("Done. Installation will continue...");
                echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
            }
            $conn->Close();
            break;
        // Setting up the modules variables
        case 5:
            ShowPosition(7);
            echo "<h2>" . Translate("Setting up modules variables") . "</h2>";
            echo "<b>" . Translate("Modules") . ":</b><br>";
            
            $file = fopen("$baseDir/config/auto_defines.php", "w");
            fwrite($file, "<?php\n");
            fwrite($file, "/**\n");
            fwrite($file, " * This file will be automatically generated after importation / setup of a module.\n");
            fwrite($file, " * It will check all the ID required for the variables and define constant for them.\n");
            fwrite($file, " */\n");
            
            $nextId = 1;
            foreach ($allModules as $module) {
                if (!file_exists("$baseDir/modules/$module/config.xml")) {
                    continue;
                }
                echo "- $module<br>";
                $doc = new XMLReader();
                $doc->open("$baseDir/modules/$module/config.xml");
                while ($doc->read()) {
                    if ($doc->nodeType == XMLReader::END_ELEMENT) {
                        continue;
                    }
                    if ($doc->name == "variable") {
                        fwrite($file, "define('" . $doc->getAttribute("name") . "',$nextId);\n");
                        $nextId++;
                    }
                }
                $doc->close();
            }
            fwrite($file, "?>");
            fclose($file);
            $step++;
            ResultMessage("Done. Installation will continue...");
            echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
            break;
        // Package content
        case 6:
            ShowPosition(8);
            echo "<h2>" . Translate("Installing base package.") . "</h2>";
            $hasErrors = false;
            $conn = new Database($dbhost, $dbuser, $dbpass, $dbname);
            $sql = file_get_contents("$baseDir/install/package.sql");
            $sql = str_replace("\r\n", "\n", $sql);
            $statements = explode(";\n", $sql);
            foreach ($statements as $cmd) {
                if (trim($cmd) == "") {
                    continue;
                }
                
                if ($conn->Execute($cmd) === false) {
                    ErrorMessage(Translate("Error whilst executing:<br>%s", $cmd), false);
                    $hasErrors = true;
                }
            }
            if (!$hasErrors) {
                $step++;
                ResultMessage("Complete. Installation will continue...");
                echo "<script>setTimeout('document.location=\"index.php?v=" . rand() . "\";',1000);</script>";
            }
            $conn->close();
            break;
        // Remove the install marker
        case 7:
            ShowPosition(9);
            $caches = scandir("$baseDir/cached_libs");
            foreach ($caches as $i) {
                if (preg_match("/\\.php\$/", $i) == 1) {
                    unlink("$baseDir/cached_libs/$i");
                }
            }
            
            echo "<h2>" . Translate("Installation finished") . "</h2>";
            @unlink("$baseDir/install/installer.marker");
            if (file_exists("$baseDir/install/installer.marker")) {
                ResultMessage("The installation has been completed");
                echo "<b style='color:red;'>" . Translate("The installation is now complete, however you must delete the install/installer.marker file manually.") . "</b>";
            } else {
                ResultMessage("The installation was successful");
                echo Translate("The installation was successful you may now reload and log in with your administrator account.");
            }
            session_unset();
            ButtonArea();
            LinkButton("Continue", "index.php");
            EndButtonArea();
            return;
        default:
            echo "Error!";
            break;
    }
    
    $_SESSION['install_step'] = $step;
}

?>
</body>
</html>