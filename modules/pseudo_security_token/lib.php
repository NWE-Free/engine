<?php
function SecureReplace($matches)
{
    global $uservals;
    
    return $matches[1] . "index.php?" . $matches[2] . "&token=MySecurityToken" . $matches[3];
}

/**
 * Search all "index.php" strings and add the security token
 *
 * @param $buffer string
 * @param $force  boolean
 *                (if true add the token even if it's not after a " or a =)
 *
 * @return string
 */
function Secure($buffer, $force = false)
{
    if ($force) {
        $exp = "/()index\\.php\\?([a-zA-Z0-9\\+\\-&\\/=%\\._]*)()/";
    } else {
        $exp = "/([=\"'])index\\.php\\?([a-zA-Z0-9\\+\\-&\\/=%\\._]*)([\"' >])/";
    }
    return preg_replace_callback($exp, "SecureReplace", $buffer);
}
