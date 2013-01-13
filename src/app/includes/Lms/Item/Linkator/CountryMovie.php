<?php

class Lms_Item_Linkator_CountryMovie extends Lms_Item_Abstract {
    static public function getTableName() 
    {
        return '?_movies_countries';
    }
    
    public static function _customInitStructure(
        Lms_Item_Struct $struct,
        DbSimple_Generic_Database $masterDb,
        DbSimple_Generic_Database $slaveDb
    )
    {
        parent::_customInitStructure($struct, $masterDb, $slaveDb);
        $struct->addIndex('country_id', array('country_id'));
        $struct->addIndex('movie_id', array('movie_id'));
    }
}