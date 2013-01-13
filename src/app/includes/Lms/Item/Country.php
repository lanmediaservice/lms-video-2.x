<?php

class Lms_Item_Country extends Lms_Item_Abstract {
    
    public static function getTableName()
    {
        return '?_countries';
    }
    
    public static function getByName($name)
    {
        $db = Lms_Db::get('main');
        $row = $db->selectRow('SELECT * FROM ' . self::getTableName() . ' WHERE name=?', $name);
        return self::rowToItem($row);
    }

    public static function getByNameOrCreate($name)
    {
        $item = self::getByName($name);
        if (!$item) {
            $item = Lms_Item::create('Country');
            $item->setName($name)
                 ->save();
        }
        return $item;
    }
    
}
