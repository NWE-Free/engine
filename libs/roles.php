<?php
$isSuperUser = null;

/**
 * Checks if the user is administrator
 *
 * @return boolean true if administrator
 */
function IsSuperUser()
{
    global $db, $userId, $isSuperUser, $isAdmin, $isModerator;
    
    if ($isSuperUser != null) {
        return $isSuperUser;
    }
    
    $result = $db->Execute("select count(*) from user_roles where user_id = ? and role_id = 1000", $userId);
    if ($result->fields[0] + 0 != 0) {
        $result->Close();
        $isSuperUser = true;
        $isAdmin = true;
        $isModerator = true;
        return true;
    }
    
    $result->Close();
    $isSuperUser = false;
    return false;
}

$isAdmin = null;

function IsAdmin()
{
    global $db, $userId, $isAdmin, $isModerator;
    
    if ($userId == -1) {
        return false;
    }
    
    if ($isAdmin != null) {
        return $isAdmin;
    }
    
    $result = $db->Execute("select count(*) from user_roles where user_id = ? and role_id in (1000,900)", $userId);
    if ($result->fields[0] + 0 != 0) {
        $result->Close();
        $isAdmin = true;
        $isModerator = true;
        return true;
    }
    
    $result->Close();
    $isAdmin = false;
    return false;
}

$isModerator = null;

function IsModerator()
{
    global $db, $userId, $isModerator;
    
    if ($isModerator != null) {
        return $isModerator;
    }
    
    $result = $db->Execute("select count(*) from user_roles where user_id = ? and role_id in (1000,900,500)", $userId);
    if ($result->fields[0] + 0 != 0) {
        $result->Close();
        $isModerator = true;
        return true;
    }
    
    $result->Close();
    $isModerator = false;
    return false;
}

$userRoles = null;

/**
 * Checks if the user has the role.
 *
 * @param $roleId integer
 *
 * @return boolean returns true if the user have the role, false otherwise
 */
function HasRole($roleId)
{
    global $db, $userId, $userRoles;
    
    if (IsSuperUser()) {
        return true;
    }
    
    if ($userRoles == null) {
        $userRoles = array();
        
        $result = $db->Execute("select role_id from user_roles where user_id = ?", $userId);
        while (!$result->EOF) {
            $userRoles[] = $result->fields[0] + 0;
            $result->MoveNext();
        }
        $result->Close();
    }
    
    return in_array($roleId + 0, $userRoles);
}
