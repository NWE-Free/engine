<?php

function CalcExperienceLevel()
{
    global $userStats;
    
    do {
        $needToContinue = false;
        
        $level = $userStats['Level']->value;
        $exp = $userStats['Experience']->value;
        
        $expRequirement = $level * $level * 1000;
        
        if ($exp > $expRequirement) {
            $userStats['Experience']->SetValueNoCallBack($userStats['Experience']->value - $expRequirement);
            $userStats['Level']->value++;
            $needToContinue = true;
        }
    } while ($needToContinue);
}

function ExpPercent()
{
    global $userStats;
    
    $level = $userStats['Level']->value;
    $exp = $userStats['Experience']->value;
    $expRequirement = $level * $level * 1000;
    
    return round($exp / $expRequirement * 100.0, 1) . "%";
}