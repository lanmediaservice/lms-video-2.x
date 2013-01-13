<?php

class Lms_Item_Role extends Lms_Item_Abstract {
    
    static public function getTableName()
    {
        return '?_roles';
    }
    
    static public function getByName($name)
    {
        $db = Lms_Db::get('main');
        $row = $db->selectRow('SELECT * FROM ' . self::getTableName() . ' WHERE name=?', $name);
        return self::rowToItem($row);
    }

    static public function getByNameOrCreate($name)
    {
        $item = self::getByName($name);
        if (!$item) {
            $item = Lms_Item::create('Role');
            $item->setName($name)
                 ->save();
        }
        return $item;
    }
    
}
