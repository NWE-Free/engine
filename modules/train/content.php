<?php
if (function_exists("IsInJail") && IsInJail()) {
    TableHeader("Jail");
    echo Translate("You cannot train while being in jail.");
    TableFooter();
    
    ButtonArea();
    LinkButton("Continue", "index.php?p=jail");
    EndButtonArea();
    return;
}

if (function_exists("IsInHospital") && IsInHospital()) {
    TableHeader("Hospital");
    echo Translate("You cannot train while being in hospital.");
    TableFooter();
    
    ButtonArea();
    LinkButton("Continue", "index.php?p=hospital");
    EndButtonArea();
    return;
}

$nbRemaining = GetUserVariable(nbTrainingRemaining) + 0;
/**
 * A training is in progress?
 */
if ($nbRemaining > 0) {
    // Still need to wait
    if (GetUserVariable(nextTrainingVisit) + 0 > time()) {
        TableHeader("Training in progress");
        echo Translate("Your training is in progress, please come back in %s.",
            TimeInterval(time(), GetUserVariable(nextTrainingVisit) + 0));
        TableFooter();
        return;
    } // Time is over
    else {
        SetUserVariable(nbTrainingRemaining, $nbRemaining - 1);
        SetUserVariable(nextTrainingVisit, time() + GetConfigValue("trainVisitTime"));
        if ($nbRemaining - 1 > 0) {
            TableHeader("Next training section");
            echo Translate("Your training is progressing! You have still %d sessions till the end.", $nbRemaining - 1);
            TableFooter();
        } else {
            $result = $db->Execute("select requirements,effect,name,display_condition,nb_visits from trainings where id = ?",
                GetUserVariable(trainingInProgress));
            if (function_exists("StorePersonalLog")) {
                StorePersonalLog(Translate("You trained yourself for %s.", $result->fields[2]));
            }
            if (function_exists("QuestCallback")) {
                QuestCallback("train", $result->fields[2]);
            }
            NWEval($result->fields[1]);
            $db->Execute("replace into training_done(user_id,training_id) values(?,?)", $userId,
                GetUserVariable(trainingInProgress));
            SetUserVariable(trainingInProgress, null);
            
            TableHeader("Training finished");
            echo Translate("Your training is finished. Congratulations.");
            TableFooter();
        }
        ButtonArea();
        LinkButton("Continue", "index.php?p=train");
        EndButtonArea();
        return;
    }
}

/**
 * A training has been selected.
 */
if (isset($_GET["training"])) {
    /**
     * Checks the requirments and effects for it.
     */
    $result = $db->Execute("select requirements,effect,name,display_condition,nb_visits from trainings where id = ?",
        $_GET["training"]);
    if ($result->fields[3] != null) {
        $cond = NWEval("return (" . $result->fields[0] . ")&&(" . $result->fields[3] . ");");
    } else {
        $cond = NWEval("return (" . $result->fields[0] . ");");
    }
    
    /**
     * requirements matched, therefore we run the effect.
     */
    if ($cond) {
        if ($result->fields[4] + 0 > 1) {
            SetUserVariable(trainingInProgress, $_GET["training"] + 0);
            SetUserVariable(nextTrainingVisit, time() + GetConfigValue("trainVisitTime"));
            SetUserVariable(nbTrainingRemaining, $result->fields[4] - 1);
            
            TableHeader("Training started");
            echo Translate("You started the training! You have still %d sessions till the end.",
                $result->fields[4] - 1);
            TableFooter();
            
            ButtonArea();
            LinkButton("Continue", "index.php?p=train");
            EndButtonArea();
            return;
        } else {
            ResultMessage("You trained yourself successfully.");
            if (function_exists("StorePersonalLog")) {
                StorePersonalLog(Translate("You trained yourself for %s.", $result->fields[2]));
            }
            if (function_exists("QuestCallback")) {
                QuestCallback("train", $result->fields[2]);
            }
            
            NWEval($result->fields[1]);
            $db->Execute("replace into training_done(user_id,training_id) values(?,?)", $userId, $_GET["training"]);
        }
    } // Not matched... show an error.
    else {
        ErrorMessage("You do not have the requirements.");
    }
    $result->Close();
}

TableHeader("Trainings");
echo Translate("Choose what you would like to train!");
echo "<br>";
echo "<br>";

/**
 * Read all the trainings done by the player.
 */
$trainingDone = array();
$result = $db->Execute("select training_id from training_done where user_id = ?", $userId);
while (!$result->EOF) {
    $trainingDone[] = $result->fields[0] + 0;
    $result->MoveNext();
}
$result->Close();

/**
 * Offers a list of all the possible trainings with their descriptions.
 * For each runs also the checks to see if the requirements are matched.
 */
$result = $db->Execute("select id,name,description,requirements,display_condition,do_only_once from trainings order by id");
$isFirst = true;
while (!$result->EOF) {
    // Skip trainings which have been already done.
    if ($result->fields[5] == 'yes' && in_array($result->fields[0] + 0, $trainingDone)) {
        $result->MoveNext();
        continue;
    }
    
    // Skip trainings which cannot be displayed.
    if ($result->fields[4] != null) {
        $cond = NWEval("return (" . $result->fields[4] . ");");
        if (!$cond) {
            $result->MoveNext();
            continue;
        }
    }
    
    if (!$isFirst) {
        echo "<hr>";
    }
    echo "<span class='title'>" . Translate($result->fields[1]) . "</span><br>";
    echo Translate($result->fields[2]);
    echo "<br>";
    echo "<br>";
    $cond = NWEval("return (" . $result->fields[3] . ");");
    if ($cond) {
        LinkButton("Train it!", "index.php?p=train&training=" . $result->fields[0]);
    } else {
        echo Translate("You do not have the requirements.");
    }
    echo "<br>";
    $result->MoveNext();
    $isFirst = false;
}
$result->Close();
TableFooter();

ButtonArea();
LinkButton("Cancel", "index.php");
EndButtonArea();
