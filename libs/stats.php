<?php
$userStats = array();

/**
 * Stores the user statistics as well as if it has been modified.
 * Automatically clamp values to the min / max.
 */
class UserStat
{
    private $data = array();
    
    public function __construct(
        $typeId,
        $name = null,
        $value = 0.0,
        $maxValue = 0.0,
        $minValue = 0.0,
        $codeWhenMin = null,
        $statBar = true,
        $modified = false,
        $onChange = null,
        $restoreRate = null,
        $displayCode = null
    ) {
        $this->data['typeId'] = $typeId;
        $this->data['name'] = $name;
        $this->data['value'] = $value;
        $this->data['maxValue'] = $maxValue;
        $this->data['minValue'] = doubleval($minValue);
        $this->data['codeWhenMin'] = $codeWhenMin;
        $this->data['modified'] = $modified;
        $this->data['statBar'] = $statBar;
        $this->data['onChange'] = $onChange;
        $this->data['restoreRate'] = $restoreRate;
        $this->data['displayCode'] = $displayCode;
    }
    
    /**
     * Save any changes of the user stats.
     */
    public static function SaveStats($stats = null, $user = null)
    {
        global $userStats, $db, $userId;
        
        if ($stats == null) {
            $stats = $userStats;
        }
        if ($user == null) {
            $user = $userId;
        } else {
            $user = FindUser($user);
        }
        
        if (count($stats) == 0) {
            return;
        }
        foreach ($stats as $key => $s) {
            if (!$s->modified) {
                continue;
            }
            $db->Execute("replace into user_stats(user_id,stat_type,value,max_value) values(?,?,?,?)", $user,
                $s->typeId, $s->value, $s->maxValue);
            $s->modified = false;
        }
        if ($user == $userId) {
            $_SESSION["stats"] = $stats;
            $userStats = $stats;
        } else {
            $db->Execute("update users set stats_modified = 'yes' where id = ?", $user);
        }
    }
    
    /**
     * Load back the user stats either from the session or the database.
     */
    public static function LoadStats($user = null)
    {
        global $db, $userId, $userStats;
        
        if ($user == null) {
            $user = $userId;
        } else {
            $user = FindUser($user);
            if ($user == null) {
                return null;
            }
        }
        
        if ($userId == $user) {
            $result = $db->Execute("select stats_modified from users where id = ?", $userId);
            if ($result->EOF) {
                return array();
            }
            $modified = $result->fields[0];
            $result->Close();
            if ($modified == 'no') {
                if (isset($userStats) && count($userStats) > 0) {
                    return $userStats;
                } else if (isset($_SESSION["stats"]) && count($_SESSION["stats"]) > 0) {
                    return $_SESSION["stats"];
                }
            } else {
                $db->Execute("update users set stats_modified = 'no' where id = ?", $userId);
            }
        }
        
        $return = array();
        
        $result = $db->Execute(
            "select t.id, t.name, u.value, t.initial_value, u.max_value,
                t.max_value, t.min_value, t.code_when_min, t.stat_bar,t.on_change, t.restore_rate, t.display_code
                from user_stat_types t left join (select * from user_stats where user_id = ?) u on
                t.id = u.stat_type
                order by t.position, name", $user);
        
        while (!$result->EOF) {
            $s = new UserStat(intval($result->fields[0]), $result->fields[1],
                $result->fields[2] == null ? $result->fields[3] : $result->fields[2],
                $result->fields[4] == null ? $result->fields[5] : $result->fields[4], $result->fields[6],
                $result->fields[7], $result->fields[8] == "yes", $result->fields[2] == null, $result->fields[9],
                $result->fields[10], $result->fields[11]);
            $return[$s->name] = $s;
            
            $result->MoveNext();
        }
        $result->Close();
        
        return $return;
    }
    
    public function __get($name)
    {
        if ($name == "percent") {
            if ($this->data['maxValue'] == null) {
                return null;
            }
            return round($this->data['value'] * 100.0 / $this->data['maxValue'], 2);
        }
        return $this->data[$name];
    }
    
    public function __set($name, $value)
    {
        global $userStats, $db;
        
        if ($name == 'value') {
            $value = doubleval($value);
            if ($value < $this->data['minValue']) {
                $value = $this->data['minValue'];
            }
            if ($this->data['maxValue'] != null && $value > doubleval($this->data['maxValue'])) {
                $value = doubleval($this->data['maxValue']);
            }
        }
        
        $value = round($value, 2);
        
        if ("" . $value != $this->data[$name]) {
            if ($name != 'modified') {
                $this->data['modified'] = true;
            }
            
            $this->data[$name] = $value;
            if ($name == "value" && $this->data['onChange'] != null) {
                NWEval($this->data['onChange']);
            }
        }
    }
    
    public function SetValueNoCallBack($value)
    {
        $value = doubleval($value);
        $value = round($value, 2);
        if ($value < $this->data['minValue']) {
            $value = $this->data['minValue'];
        }
        if ($this->data['maxValue'] != null && $value > doubleval($this->data['maxValue'])) {
            $value = doubleval($this->data['maxValue']);
        }
        $this->data['value'] = $value;
        $this->data['modified'] = true;
    }
}
