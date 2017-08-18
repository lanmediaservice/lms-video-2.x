<?php

class Lms_Item_Participant extends Lms_Item_Abstract {
    
    public static function getTableName()
    {
        return '?_participants';
    }

    public static function _customInitStructure(
        Lms_Item_Struct $struct,
        DbSimple_Database $masterDb,
        DbSimple_Database $slaveDb
    )
    {
        parent::_customInitStructure($struct, $masterDb, $slaveDb);
        $struct->addIndex('movie_id', array('movie_id'));
        $struct->addIndex('person_id', array('person_id'));
    }
    
}
