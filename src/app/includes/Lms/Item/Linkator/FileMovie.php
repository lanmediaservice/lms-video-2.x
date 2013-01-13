<?php

class Lms_Item_Linkator_FileMovie extends Lms_Item_Abstract 
{
       
    static public function getTableName() 
    {
        return '?_movies_files';
    }
    
    public static function _customInitStructure(
        Lms_Item_Struct $struct,
        DbSimple_Generic_Database $masterDb,
        DbSimple_Generic_Database $slaveDb
    )
    {
        parent::_customInitStructure($struct, $masterDb, $slaveDb);
        $struct->addIndex('movie_id', array('movie_id'));
        $struct->addIndex('file_id', array('file_id'));
    }
    
}
