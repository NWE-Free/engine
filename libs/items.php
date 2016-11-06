<?php

/**
 *  Class handling all the items / inventory / equiped manipulation.
 */
class Item
{
    private static $inventoryObjects = null;
    private static $equipedObjects = null;
    
    private static $allowedToModify = array("health", "quantity");
    
    public $added = false;
    
    private $equipped = false;
    
    private $attributes = array();
    
    /**
     * Gets back all the objects in the inventory.
     *
     * @return array
     */
    public static function AllInventory()
    {
        if (Item::$inventoryObjects == null) {
            Item::LoadUserInventory();
        }
        return Item::$inventoryObjects;
    }
    
    /**
     * Loads back the user inventory.
     */
    private static function LoadUserInventory()
    {
        global $db, $userId;
        
        Item::$inventoryObjects = array();
        
        $result = $db->Execute("select object_id,health,quantity from inventory where user_id=?", $userId);
        while (!$result->EOF) {
            $obj = Item::GetObjectInfo($result->fields[0]);
            $obj->attributes['object_health'] = $result->fields[1];
            $obj->attributes['quantity'] = $result->fields[2];
            
            Item::$inventoryObjects[] = $obj;
            $result->MoveNext();
        }
        
        $result->Close();
    }
    
    /**
     * Gets back an Item object containing the base information of an item.
     *
     * @param $object mixed
     *                can be an object id or an object name.
     *
     * @return \Item
     */
    public static function GetObjectInfo($object)
    {
        global $db;
        
        $obj = new Item();
        
        $objectId = $object + 0;
        if ($objectId != 0) {
            $result = $db->Execute(
                "select objects.id,objects.name,objects.description,objects.price,
                    objects.requirements,objects.durability,objects.allow_fraction,objects.quest_item,
                    object_types.id \"object_type_id\", object_types.name \"object_type\",
                    object_types.usage_label, object_types.usage_code, objects.image_file, objects.usage_label, objects.usage_code
                    from objects left join object_types on objects.object_type = object_types.id where objects.id=?",
                $objectId);
        } else {
            $result = $db->Execute(
                "select objects.id,objects.name,objects.description,objects.price,
                    objects.requirements,objects.durability,objects.allow_fraction,objects.quest_item,
                    object_types.id \"object_type_id\", object_types.name \"object_type\",
                    object_types.usage_label, object_types.usage_code, objects.image_file, objects.usage_label, objects.usage_code
                    from objects left join object_types on objects.object_type = object_types.id where objects.name=?",
                $object);
        }
        if ($result->EOF) {
            $result->Close();
            return null;
        }
        
        $fields = $result->FetchFields();
        for ($i = 0; $i < count($fields); $i++) {
            if (($fields[$i]->name == "usage_code" || $fields[$i]->name == "usage_label") && $result->fields[$i] == "") {
                if (!isset($obj->attributes[$fields[$i]->name])) {
                    $obj->attributes[$fields[$i]->name] = $result->fields[$i];
                }
            } else {
                $obj->attributes[$fields[$i]->name] = $result->fields[$i];
            }
        }
        $result->Close();
        
        $sql = "select object_types_attributes.name, s1.value from object_types_attributes
                left join (select * from object_attributes where object_id=?) s1
                on object_types_attributes.id = s1.attribute_id
                where object_types_attributes.name = ?";
        
        $result = $db->Execute($sql, $objectId, $obj->attributes['object_type_id']);
        
        while (!$result->EOF) {
            $obj->attributes[strtolower($result->fields[0])] = $result->fields[1];
            $result->MoveNext();
        }
        
        $result->Close();
        
        return $obj;
    }
    
    /**
     * Returns the quantity the user transport of a given object.
     *
     * @param $object mixed
     *                can be an object id or an object name.
     * @param $health double
     *                if null then return the first found.
     * @param $user   integer
     *                if null then for the current user.
     *
     * @return bool|int|mixed
     */
    public static function OwnedNumber($object, $health = null, $user = null)
    {
        $obj = Item::GetInventoryObject($object, $health, $user);
        if ($obj == null) {
            return 0;
        }
        return $obj->quantity;
    }
    
    /**
     * Get an object info out of the inventory.
     *
     * @param $object mixed
     *                can be an object id or an object name.
     * @param $health double
     *                if null then return the first found.
     * @param $user   integer
     *                if null then for the current user.
     *
     * @return \Item|null
     */
    public static function GetInventoryObject($object, $health = null, $user = null)
    {
        global $userId;
        
        if ($user != null && $userId != $user) {
            $obj = Item::GetObjectInfo($object);
            if ($obj->LoadInventoryDetails($user)) {
                return $obj;
            }
            return null;
        } else {
            if (Item::$inventoryObjects == null) {
                Item::LoadUserInventory();
            }
            
            $objectId = $object + 0;
            
            if ($objectId != 0) {
                foreach (Item::$inventoryObjects as $obj) {
                    if ($obj->id == $objectId && ($health == null || $obj->object_health == "$health")) {
                        return $obj;
                    }
                }
            } else {
                foreach (Item::$inventoryObjects as $obj) {
                    if ($obj->name == $object && ($health == null || $obj->object_health == "$health")) {
                        return $obj;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Load inventory informations for the current object for a given user.
     *
     * @param $user integer
     *              if null the current user will be used.
     *
     * @return bool
     */
    public function LoadInventoryDetails($user = null)
    {
        global $db;
        
        $ret = false;
        
        $result = $db->Execute("select object_id,health,quantity from inventory where user_id=? and object_id = ?",
            $user, $this->id);
        while (!$result->EOF) {
            $this->attributes['object_health'] = $result->fields[1];
            $this->attributes['quantity'] = $result->fields[2];
            $ret = true;
            $result->MoveNext();
        }
        $result->Close();
        return $ret;
    }
    
    /**
     * Returns true if the user wears the given object.
     *
     * @param $object integer
     *                the id of the object to check
     * @param $user   integer
     *                if null then for the current user.
     *
     * @return boolean
     */
    public static function IsWearing($object, $user = null)
    {
        $equipment = Item::LoadUserEquiped($user);
        foreach ($equipment as $i) {
            if ($i->id == $object) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Loads back the user equiped items.
     */
    private static function LoadUserEquiped($user = null)
    {
        global $db, $userId;
        
        if ($user == null) {
            $user = $userId;
        }
        
        $res = array();
        
        $result = $db->Execute("select f1.object_id,f1.health,slots.name from slots left join (select * from equiped where user_id=?) f1 on slots.id = f1.slot_id",
            $user);
        while (!$result->EOF) {
            if ($result->fields[0] == null) {
                $obj = new Item();
                $obj->attributes['id'] = null;
                $obj->attributes['name'] = "";
                $obj->attributes['object_health'] = "";
                $obj->attributes['image_file'] = "";
            } else {
                $obj = Item::GetObjectInfo($result->fields[0]);
                $obj->attributes['object_health'] = $result->fields[1];
            }
            $obj->attributes['slot'] = $result->fields[2];
            $res[] = $obj;
            $result->MoveNext();
        }
        $result->Close();
        
        return $res;
    }
    
    /**
     * Equip an item (out of the inventory) to a given slot.
     *
     * @param $object mixed
     *                can be an object id or an object name.
     * @param $health double
     *
     * @return Item returns the previously equiped item on that slot.
     * @throws Exception throws an error in case of problem.
     */
    public static function Equip($object, $health)
    {
        global $db, $userId, $userStats;
        
        $obj = Item::GetInventoryObject($object, $health);
        if ($obj == null) {
            throw new Exception("The item doesn't appear in the inventory.");
        }
        
        if ($obj->requirements != null) {
            $ret = NWEval("return ({$obj->requirements});");
            if (!$ret) {
                throw new Exception("You don't have the requirements to use this item.");
            }
        }
        
        $equiped = Item::AllEquiped();
        $objTypes = Item::ObjectTypesToEquip();
        
        $slot = $objTypes[$obj->object_type_id];
        $return = Item::UnEquip($slot);
        
        $result = $db->Execute("select id from slots where name=?", $slot);
        $slotId = $result->fields[0];
        $result->Close();
        
        Item::InventoryRemove($obj->name, 1, $obj->object_health);
        $db->Execute("insert into equiped(user_id,slot_id,object_id,health) values(?,?,?,?)", $userId, $slotId,
            $obj->id, $obj->object_health);
        
        Item::$equipedObjects = null;
        return $return;
    }
    
    /**
     * Loads back all the equiped objects.
     */
    public static function AllEquiped($user = null)
    {
        global $userId;
        
        if ($user == null || $user == $userId) {
            if (Item::$equipedObjects == null) {
                Item::$equipedObjects = Item::LoadUserEquiped();
            }
            return Item::$equipedObjects;
        } else {
            return Item::LoadUserEquiped($user);
        }
    }
    
    /**
     * Returns all the object types which can be equiped.
     *
     * @return array with the key as object type id and the value the slot name
     *         on which this object can be equiped.
     */
    public static function ObjectTypesToEquip()
    {
        global $db;
        
        $ret = array();
        
        $result = $db->Execute("select distinct object_type,slots.name from slot_type_accepted, slots where slot_type_accepted.slot_id = slots.id");
        while (!$result->EOF) {
            $ret[$result->fields[0]] = $result->fields[1];
            $result->MoveNext();
        }
        $result->Close();
        
        return $ret;
    }
    
    /**
     * Un-Equip a slot.
     *
     * @param $slot string
     *              slot name.
     *
     * @return Item returns the previously equiped item on that slot.
     */
    public static function UnEquip($slot)
    {
        global $db, $userId;
        
        $return = null;
        
        $equiped = Item::AllEquiped();
        $pos = 0;
        foreach ($equiped as $e) {
            if ($e->slot == $slot) {
                if ($e->name == "") {
                    break;
                }
                
                $return = $e;
                
                Item::InventoryAdd($e->id, 1, $e->object_health);
                
                $db->Execute("delete from equiped where user_id = ? and slot_id in (select id from slots where name = ?)",
                    $userId, $e->slot);
                unset(Item::$equipedObjects[$pos]);
                break;
            }
            $pos++;
        }
        return $return;
    }
    
    /**
     * Adds an item to an inventory.
     *
     * @param $object   mixed
     *                  can be an object id or an object name.
     * @param $quantity double
     * @param $health   double
     * @param $user     integer
     *
     * @throws Exception throws an exception in case of error.
     */
    public static function InventoryAdd($object, $quantity, $health = null, $user = null)
    {
        global $db, $userId;
        if (doubleval($quantity) == 0) {
            return;
        }
        if (doubleval($quantity) < 0) {
            throw new Exception("Must be a positive number.");
        }
        
        $info = Item::GetObjectInfo($object);
        
        if ($info->allow_fraction == 'no' && "$quantity" != "" . intval($quantity)) {
            throw new Exception("Must be a whole number.");
        }
        
        $objectId = $object + 0;
        $obj = Item::GetInventoryObject($object, $health, $user);
        
        if ($user == null) {
            $user = $userId;
        }
        
        if ($health == null) {
            
            if ($obj == null) {
                $health = $info->durability;
            } else {
                $health = $obj->durability;
            }
        }
        
        if ($obj == null) {
            if ($objectId == 0) {
                $result = $db->Execute("select id from objects where name = ?", $object);
                if ($result->EOF) {
                    $result->Close();
                    throw new Exception("Object name cannot be found in the database.");
                }
                $objectId = $result->fields[0];
                $result->Close();
            }
            
            $db->Execute("insert into inventory(user_id,object_id,health,quantity) values(?,?,?,?)", $user, $objectId,
                $health == null ? 1 : $health, $quantity);
            
            if ($user == $userId) {
                $obj = $info;
                $obj->attributes['object_health'] = $health;
                $obj->attributes['quantity'] = $quantity;
                Item::$inventoryObjects[] = $obj;
            }
        } else {
            $obj->attributes['quantity'] += $quantity;
            $db->Execute("update inventory set quantity=quantity+? where user_id=? and object_id = ? and health = ?",
                $quantity, $user, $obj->id, $obj->object_health);
        }
    }
    
    /**
     * Removes an item from the inventory.
     *
     * @param $object   mixed
     *                  can be an object id or an object name.
     * @param $quantity double
     * @param $health   double
     * @param $user     integer
     *
     * @throws Exception throws an exception in case of error.
     */
    public static function InventoryRemove($object, $quantity, $health = null, $user = null)
    {
        global $db, $userId;
        if (doubleval($quantity) == 0) {
            return;
        }
        if (doubleval($quantity) < 0) {
            throw new Exception("Must be a positive number.");
        }
        
        $objectId = $object + 0;
        $obj = Item::GetInventoryObject($object, $health, $user);
        
        if ($user == null) {
            $user = $userId;
        }
        
        if ($obj == null) {
            throw new Exception("The inventory doesn't contain any of those objects.");
        }
        if (doubleval($quantity) > doubleval($obj->quantity)) {
            throw new Exception("The inventory doesn't contain enough of those objects.");
        }
        
        if ($obj->allow_fraction == 'no' && "$quantity" != "" . intval($quantity)) {
            throw new Exception("Must be a whole number.");
        }
        
        if (doubleval($quantity) == doubleval($obj->quantity)) {
            $db->Execute("delete from inventory where user_id=? and object_id = ? and health = ?", $user, $obj->id,
                $obj->object_health);
            
            if ($user == $userId) {
                $key = null;
                foreach (Item::$inventoryObjects as $k => $v) {
                    if ($obj == $v) {
                        $key = $k;
                        break;
                    }
                }
                unset(Item::$inventoryObjects[$k]);
            }
        } else {
            $obj->attributes['quantity'] -= $quantity;
            $db->Execute("update inventory set quantity=quantity-? where user_id=? and object_id = ? and health = ?",
                $quantity, $user, $obj->id, $obj->object_health);
        }
    }
    
    public function __get($name)
    {
        $name = strtolower($name);
        if ($name == "equiped") {
            return $this->equipped;
        }
        if (!array_key_exists($name, $this->attributes)) {
            throw new Exception("attribute '$name' doesn't exists");
        }
        return $this->attributes[$name];
    }
    
    public function __set($name, $value)
    {
        if ($name == "equiped") {
            return $this->equipped;
        }
        throw new Exception('Attributes cannot be modified.');
    }
    
    public function __isset($name)
    {
        $name = strtolower($name);
        if ($name == "equiped") {
            return true;
        }
        return (array_key_exists($name, $this->attributes));
    }
    
    public function GetAttributes()
    {
        return $this->attributes;
    }
}
