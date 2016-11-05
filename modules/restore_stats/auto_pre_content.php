<?php
/**
 * Increment the Action Points (AP) over time.
 */

/**
 * Checks when we did the last check
 */
$lastCheck = GetUserVariable(statsLastCheck);
/**
 * Never did it, therefore we set the time.
 */
if ($lastCheck == null) {
    SetUserVariable(statsLastCheck, time());
    return;
}
$lastCheck = intval($lastCheck);

/**
 * A minute or more passed.
 */
if ((time() - $lastCheck) > 60) {
    /**
     * Calculate the number of minutes
     */
    $minutes = floor((time() - $lastCheck) / 60);
    /**
     * Calculate the time remaining (left over from a full minute)
     */
    $leftOver = (time() - $lastCheck) % 60;
    
    /**
     * Checks all the stats and increase those which have a restore rate.
     */
    foreach ($userStats as $s) {
        if ($s->restoreRate != null) {
            $s->value += $minutes * $s->restoreRate;
        }
    }
    
    /**
     * Stores the new statsLastCheck
     */
    SetUserVariable(statsLastCheck, time() - $leftOver);
}
