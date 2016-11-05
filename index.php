<?php
/******************************************************************
 * New worlds engine - (c) 2012 Alain Bertrand
 * Base engine code which loads common libraries and loads
 * the appropriate module.
 ******************************************************************/

$profilerInfo = "";
list ($usec, $sec) = explode(" ", microtime());
$startEngineTime = $lastEngineTime = ((float)$usec + (float)$sec);

ob_start();
header("Expires: now");

// Clean the "magic" quotes if any as they are just counter productive.
if (get_magic_quotes_gpc() != 0) {
    foreach ($_GET as $key => $value) {
        $_GET[$key] = stripcslashes($value);
    }
    
    foreach ($_POST as $key => $value) {
        if (!is_array($value)) {
            $_POST[$key] = stripcslashes($value);
        }
    }
}

include "config/license.php";
include "libs/hooks.php";
include "libs/common.php";

// If the installer marker is present run the installer
if (file_exists("install/installer.marker")) {
    session_start();
    include "install/installer.php";
    exit();
}

include "config/config.php";
include "libs/template.php";
include "libs/roles.php";

// Loads the include when needed to avoid loading all if not required
// It's also now possible to catch wrongly written class names
function __autoload($className)
{
    $classes = array(
        "Database" => "libs/db.php",
        "UserStat" => "libs/stats.php",
        "Item" => "libs/items.php",
        "Ajax" => "libs/ajax.php"
    );
    if (isset($classes[$className])) {
        include $classes[$className];
    } else {
        $bt = debug_backtrace();
        $f = array_shift($bt);
        engine_error_handling(0, "Class $className not defined", $f["file"], $f["line"], null);
    }
}

$profilerInfo .= "<div class='pColA'>Include time:</div><div class='pColB'>" . sprintf("%.0f ms",
        1000 * (Database::microtime_float() - $lastEngineTime)) . "</div>";
$lastEngineTime = Database::microtime_float();

session_start();

try {
    // Creates a connection to the database using the configuration found in the
    // config file.
    $db = new Database($dbhost, $dbuser, $dbpass, $dbname);
    if ($db->error != null) {
        $template = "error";
        echo "<span class='title'>Database down or wrong DB settings.<br>Please try again later.</span><br>";
        $content['main'] = ob_get_clean();
        error_reporting(0);
        ShowTemplate();
        exit();
    }
    
    // Set the error handler after the connection in case of issues.
    // If set before it would intercept the error.
    set_error_handler("engine_error_handling");
    register_shutdown_function("engine_stop");
    
    if (isset($_SESSION['profiler']) && $_SESSION["profiler"] == "on") {
        $profilerInfo .= "<div class='pColA'>DB Conn time:</div><div class='pColB'>" . sprintf("%.0f ms",
                1000 * (Database::microtime_float() - $lastEngineTime)) . "</div>";
        $lastEngineTime = Database::microtime_float();
    }
    
    if (ini_get('date.timezone') == "") {
        date_default_timezone_set((GetConfigValue("defaultTimeZone",
            "") == null ? 'UTC' : GetConfigValue("defaultTimeZone", "")));
    }
    
    // A bug report has been done!
    if (isset($_POST['action']) && $_POST['action'] == 'reportBug') {
        if (isset($_SESSION["bug_report"])) {
            $userId = 1;
            if (isset($_SESSION["userid"]) && $_SESSION["userid"] != null) {
                $userId = $_SESSION["userid"];
            }
            
            // Search duplicate entries
            $result = $db->Execute("select id from bugs where status = 'Open' and filename = ? and lineno = ?",
                $_SESSION["bug_file"], $_SESSION["bug_line"]);
            if ($result->EOF) {
                $duplicate = null;
            } else {
                $duplicate = $result->fields[0];
            }
            $result->Close();
            
            // Generate an ID for the bug report
            $bugId = md5($userId . rand() . time() . "some text to make it longer");
            // We do have a duplication (same row same file)
            if ($duplicate != null) {
                $db->Execute("insert into bugs(id,duplicate_of,reported_by,data,filename,lineno,step_by_step,status) values(?,?,?,?,?,?,?,?)",
                    $bugId, $duplicate, $userId, $_SESSION["bug_report"], $_SESSION["bug_file"], $_SESSION["bug_line"],
                    $_POST['report'], 'Closed');
            }
            // We do not have duplication then insert the bug and send a message
            // to all admins
            else {
                $db->Execute("insert into bugs(id,reported_by,data,filename,lineno,step_by_step) values(?,?,?,?,?,?)",
                    $bugId, $userId, $_SESSION["bug_report"], $_SESSION["bug_file"], $_SESSION["bug_line"],
                    $_POST['report']);
                
                if (file_exists("$baseDir/modules/messages/lib.php")) {
                    include "$baseDir/modules/messages/lib.php";
                    $msgContent = $_POST['report'] . "\n\n--* DO NOT TOUCH *--\n--* MODULE: BUG_TRACKING, ID=$bugId *--";
                    $result = $db->Execute("select user_id from user_roles where role_id = 1000");
                    while (!$result->EOF) {
                        SendMessage($result->fields[0], "Bug report: $bugId", $msgContent);
                        $result->MoveNext();
                    }
                    $result->Close();
                }
            }
            $_SESSION["bug_report"] = null;
        }
        
        $template = "error";
        echo "<span class='title'>" . Translate("Thanks for reporting the bug.") . "</span><br>";
        ButtonArea();
        LinkButton("Back", "index.php");
        EndButtonArea();
        
        $content['main'] = ob_get_clean();
        error_reporting(0);
        ShowTemplate();
        exit();
    }
    
    include "config/auto_defines.php";
    
    // Initialize the variables to empty, such that the different modules can
    // fill
    // them in.
    $userId = -1;
    $content = array();
    $content['sideMenu'] = "";
    $content['stats'] = "";
    $content['footerScript'] = "";
    $content['header'] = "";
    $content['footer'] = "";
    $content['footerJS'] = "";
    
    // session is too old? let's destroy it
    if (!isset($_SESSION['last_check']) || (time() - $_SESSION['last_check'] > 1800)) {
        session_unset();
    }
    
    if (isset($_SESSION["userid"]) && $_SESSION["userid"] != null) {
        $userId = $_SESSION["userid"];
    }
    if (isset($_SESSION["username"]) && $_SESSION["username"] != null) {
        $username = $_SESSION["username"];
    }
    
    $_SESSION['last_check'] = time();
    $_SESSION["ip"] = $_SERVER['REMOTE_ADDR'];
    
    RunHook("pre_process.php");
    if (isset($_SESSION['profiler']) && $_SESSION["profiler"] == "on") {
        $profilerInfo .= "<div class='pColA'>Pre process time:</div><div class='pColB'>" . sprintf("%.0f ms",
                1000 * (Database::microtime_float() - $lastEngineTime)) . "</div>";
        $lastEngineTime = Database::microtime_float();
    }
    
    // Checks that the user still exists (not deleted)
    if ($userId != -1) {
        $result = $db->Execute("select id from users where id = ?", $userId);
        if ($result->EOF) {
            $userId = -1;
        }
        $result->Close();
    }
    
    // Not logged in, then work with public modules only.
    if ($userId == -1 || isset($_GET["h"])) {
        // Switch to the public template.
        $template = $publicTemplate;
        
        $lst_modules = $modules;
        // Scans all modules to see if there is a library to load.
        RunHook("lib.php");
        
        // Scans all the modules to see if there is a pre public action to
        // execute
        RunHook("auto_pre_public.php");
        
        if (!isset($_GET["h"])) {
            IncludePublicPage("");
        } else {
            IncludePublicPage($_GET["h"]);
        }
        
        if (isset($_POST["AJAX"]) && $_POST["AJAX"] == "CALLBACK") {
            Ajax::RunRegisteredFunction($_POST["func"]);
            UserStat::SaveStats();
            return;
        }
        
        // Scans all the modules to see if there is a post public action to
        // execute
        RunHook("auto_post_public.php");
    } else {
        // Checks that the referrer is correctly set if the field is there
        // This is a quick and partial test to avoid CSRF
        if (isset($_SERVER["HTTP_REFERER"])) {
            list (, , $ref) = explode("/", $_SERVER["HTTP_REFERER"]);
            if ($ref != $_SERVER["HTTP_HOST"] && $ref != $_SERVER["SERVER_NAME"]) {
                session_unset();
                header("Location: index.php");
                return;
            }
        }
        
        IsSuperUser();
        $db->Execute("update users set last_action = CURRENT_TIMESTAMP, online = 'yes' where id = ?", $userId);
        
        if (GetConfigValue("gameOnline", "") == "false" && !IsSuperUser()) {
            ErrorMessage("The game is currently offline.");
        } else {
            $userStats = UserStat::LoadStats();
            if (isset($_SESSION['profiler']) && $_SESSION["profiler"] == "on") {
                $profilerInfo .= "<div class='pColA'>Load stats time:</div><div class='pColB'>" . sprintf("%.0f ms",
                        1000 * (Database::microtime_float() - $lastEngineTime)) . "</div>";
                $lastEngineTime = Database::microtime_float();
            }
            
            if (!isset($_GET["p"]) || $_GET["p"] == "" || !in_array($_GET["p"], $modules)) {
                $moduleLoaded = $defaultModule;
            } else {
                $moduleLoaded = $_GET["p"];
            }
            
            $lst_modules = $modules;
            // Scans all modules to see if there is a library to load.
            $list = RunHook("lib.php");
            if (isset($_SESSION['profiler']) && $_SESSION["profiler"] == "on") {
                $profilerInfo .= "<div class='profilerCollaps'>";
                $profilerInfo .= "<div class='pColA'>Run libs time:</div><div class='pColB'>" . sprintf("%.0f ms",
                        1000 * (Database::microtime_float() - $lastEngineTime)) . "</div>";
                $profilerInfo .= "<table width='100%' border='0' cellspacing='0' celllpadding='0' style='margin-left: 20px; width: 280px'>";
                foreach ($list as $key => $val) {
                    if (strncmp($key, "cached_libs/", 12) == 0) {
                        $mod = $key;
                    } else {
                        $mod = substr($key, 0, strlen($key) - strlen("/lib.php"));
                    }
                    if (in_array("admin_code_editor", $modules)) {
                        $profilerInfo .= "<tr><td><a href='index.php?p=admin_code_editor&f=" . urlencode($key) . "'>$mod</a></td><td align='right'>$val ms</td></tr>";
                    } else {
                        $profilerInfo .= "<tr><td>$mod</td><td align='right'>$val ms</td></tr>";
                    }
                }
                $profilerInfo .= "</table>";
                $profilerInfo .= "</div>";
                $lastEngineTime = Database::microtime_float();
            }
            
            $stopWorkflow = false;
            // Scans all modules to see if there is something to do before the
            // content.
            $list = RunHook("auto_pre_content.php");
            if (!$stopWorkflow) {
                if (isset($_SESSION['profiler']) && $_SESSION["profiler"] == "on") {
                    $profilerInfo .= "<div class='profilerCollaps'>";
                    $profilerInfo .= "<div class='pColA'>Run auto_pre_content time:</div><div class='pColB'>" . sprintf("%.0f ms",
                            1000 * (Database::microtime_float() - $lastEngineTime)) . "</div>";
                    $profilerInfo .= "<table width='100%' border='0' cellspacing='0' celllpadding='0' style='margin-left: 20px; width: 280px'>";
                    foreach ($list as $key => $val) {
                        if (strncmp($key, "cached_libs/", 12) == 0) {
                            $mod = $key;
                        } else {
                            $mod = substr($key, 0, strlen($key) - strlen("/auto_pre_content.php"));
                        }
                        if (in_array("admin_code_editor", $modules)) {
                            $profilerInfo .= "<tr><td><a href='index.php?p=admin_code_editor&f=" . urlencode($key) . "'>$mod</a></td><td align='right'>$val ms</td></tr>";
                        } else {
                            $profilerInfo .= "<tr><td>$mod</td><td align='right'>$val ms</td></tr>";
                        }
                    }
                    $profilerInfo .= "</table>";
                    $profilerInfo .= "</div>";
                    $lastEngineTime = Database::microtime_float();
                }
                
                // The module is blocked and the request is not a logout
                if (isset($_SESSION["block"]) && $_SESSION["block"] != null && (!isset($_GET["p"]) || $_GET["p"] != "logout")) {
                    IncludePrivatePage($_SESSION["block"]);
                } // No modules have been requested
                else if (!isset($_GET["p"])) {
                    IncludePrivatePage("");
                } // Run the requested module.
                else {
                    IncludePrivatePage($_GET["p"]);
                }
                if (isset($_SESSION['profiler']) && $_SESSION["profiler"] == "on") {
                    $profilerInfo .= "<div class='pColA'>Run module time:</div><div class='pColB'>" . sprintf("%.0f ms",
                            1000 * (Database::microtime_float() - $lastEngineTime)) . "</div>";
                    $lastEngineTime = Database::microtime_float();
                }
                
                // Scans all the modules to see if there is a post content
                // action to execute
                $list = RunHook("auto_post_content.php");
                
                if (isset($_SESSION['profiler']) && $_SESSION["profiler"] == "on") {
                    $profilerInfo .= "<div class='profilerCollaps'>";
                    $profilerInfo .= "<div class='pColA'>Run auto_post_content time:</div><div class='pColB'>" . sprintf("%.0f ms",
                            1000 * (Database::microtime_float() - $lastEngineTime)) . "</div>";
                    $profilerInfo .= "<table width='100%' border='0' cellspacing='0' celllpadding='0' style='margin-left: 20px; width: 280px'>";
                    foreach ($list as $key => $val) {
                        if (strncmp($key, "cached_libs/", 12) == 0) {
                            $mod = $key;
                        } else {
                            $mod = substr($key, 0, strlen($key) - strlen("/auto_post_content.php"));
                        }
                        if (in_array("admin_code_editor", $modules)) {
                            $profilerInfo .= "<tr><td><a href='index.php?p=admin_code_editor&f=" . urlencode($key) . "'>$mod</a></td><td align='right'>$val ms</td></tr>";
                        } else {
                            $profilerInfo .= "<tr><td>$mod</td><td align='right'>$val ms</td></tr>";
                        }
                    }
                    $profilerInfo .= "</table>";
                    $profilerInfo .= "</div>";
                    $lastEngineTime = Database::microtime_float();
                }
                
                if (isset($_POST["AJAX"]) && $_POST["AJAX"] == "CALLBACK") {
                    Ajax::RunRegisteredFunction($_POST["func"]);
                    UserStat::SaveStats();
                    return;
                }
                
                UserStat::SaveStats();
            }
        }
    }
    
    // We get back the content and store it in the $content variable
    $content['main'] = ob_get_contents();
    $content['main'] .= $content['footerScript'];
    if ($content['footerJS'] != "") {
        $content['main'] .= "<script>{$content['footerJS']}</script>";
    }
    
    // Clean the output to avoid double content
    ob_end_clean();
    ob_start();
    
    // Shows the content with the template
    ShowTemplate();
    
    $output = ob_get_contents();
    global $output;
    RunHook("post_process.php", "output");
    ob_end_clean();
    
    if (isset($_SESSION['profiler']) && $_SESSION["profiler"] == "on") {
        $profilerInfo .= "<div class='pColA'>Run finish time:</div><div class='pColB'>" . sprintf("%.0f ms",
                1000 * (Database::microtime_float() - $lastEngineTime)) . "</div>";
        $lastEngineTime = Database::microtime_float();
    }
    
    echo $output;
    
    // Adds the profile info panel
    if (isset($_SESSION['profiler']) && $_SESSION["profiler"] == "on") {
        $endEngineTime = Database::microtime_float();
        $profilerInfo .= "<div class='pColA'>Full engine time:</div><div class='pColB'>" . sprintf("%.0f ms",
                1000 * (Database::microtime_float() - $startEngineTime)) . "</div>";
        $profilerInfo .= "<div class='pColA'><a href='{$webBaseDir}modules/admin_profiler/view_db_profiler.php' target='db_profiler'>Queries:</a></div><div class='pColB'>" . count($_SESSION['profiler_db']) . "</div>";
        $totDbTime = 0;
        foreach ($_SESSION['profiler_db'] as $i) {
            $totDbTime += $i['time'];
        }
        $profilerInfo .= "<div class='pColA'>Query Time:</div><div class='pColB'>" . sprintf("%.0f ms",
                1000 * $totDbTime) . "</div>";
        echo "<style>";
        echo ".profilerDiv {position: absolute; top: 0px; width: 300px; color: black; background-color: white; border: solid 1px black; padding: 2px; margin: 5px; height: 14px; overflow: hidden; font-size: 12px; cursor: pointer; }";
        echo ".profilerCollaps { height: 14px; overflow: hidden; width: 300px; }";
        echo ".profilerCollaps > div.pColA { text-decoration: underline; }";
        echo ".pColA { width: 180px; float: left; }";
        echo ".pColB { width: 50px; float: right; text-align: right; }";
        echo ".profilerDiv:hover { height: auto; }";
        echo ".profilerDetails { }";
        echo "</style>";
        Ajax::IncludeLib();
        $html = "<div class='profilerDiv'><b>Profiler info:</b> (mouse over -> expand)<br>$profilerInfo</div>";
        if (function_exists("Secure")) {
            echo Secure($html);
        } else {
            echo $html;
        }
        echo "<script>\$('.profilerCollaps').click(function() { if(\$(this).css('height') != '14px') {\$(this).css('height','14px');} else {\$(this).css('height','auto');} });</script>";
    }
} catch (Exception $mainEx) {
    ob_end_clean();
    handle_error("Un-handled Exception: " . $mainEx->getMessage(), $mainEx->getFile(), $mainEx->getLine(),
        $mainEx->getTrace());
}

StoreTranlation();