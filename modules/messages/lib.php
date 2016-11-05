<?php

/**
 * Sends an in game message to somebody
 *
 * @param string $to
 * @param string $subject
 * @param string $content
 *
 * @throws Exception if an user is not found
 */
function SendMessage($to, $subject, $content, $fromUser = null)
{
    global $userId, $db;
    
    if ($fromUser == null) {
        $fromUser = $userId;
    }
    
    $t = explode(",", $to);
    $dest = array();
    $names = array();
    foreach ($t as $i) {
        $uid = FindUser(trim($i));
        if ($uid == null) {
            throw new Exception(Translate("User %s not found.", $i));
        }
        
        if ("$uid" == "" . trim($i)) {
            $uinfo = $db->LoadData("select username from users where id = ?", $uid);
            $names[] = $uinfo['username'];
        } else {
            $names[] = trim($i);
        }
        
        $dest[] = $uid + 0;
    }
    
    $to = implode(", ", $names);
    
    foreach ($dest as $i) {
        // We don't send to system!
        if ($i + 0 == 1) {
            continue;
        }
        $hasAttachement = "no";
        if (strpos($content, "--* MODULE:") !== false) {
            $hasAttachement = "yes";
        }
        $db->Execute("insert into messages(from_user,inbox_of,sent_to,subject,message,has_attachement) values(?,?,?,?,?,?)",
            $fromUser, $i, $to, $subject, $content, $hasAttachement);
    }
}

function AddMessageData($data, $moduleName = null)
{
    global $moduleLink;
    
    if ($moduleName == null) {
        $bt = debug_backtrace();
        $f = array_shift($bt);
        $filename = str_replace("\\", "/", $f["file"]);
        $path = explode("/", $filename);
        
        if (in_array("cached_libs", $path)) {
            $f = array_shift($bt);
            $hookName = explode("/", str_replace("\\", "/", $f['file']));
            $hookName = str_replace(".php", "", substr(array_pop($hookName), 6));
            $moduleName = substr($f["function"], strlen($hookName) + 1);
        } else {
            array_pop($path);
            $moduleName = array_pop($path);
        }
    }
    
    if ($moduleLink != "") {
        $moduleLink .= "\n";
    }
    if (is_array($data)) {
        $data = http_build_query($data);
    }
    $moduleLink .= "--* MODULE: " . strtoupper($moduleName) . ", " . $data . " *--";
}