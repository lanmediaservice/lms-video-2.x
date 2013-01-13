<?php

class Lms_Item_Linkator_CommentMovie extends Lms_Item_Abstract {
    
    static public function getTableName() 
    {
        return '?_movies_comments';
    }
    
    public static function _customInitStructure(
        Lms_Item_Struct $struct,
        DbSimple_Generic_Database $masterDb,
        DbSimple_Generic_Database $slaveDb
    )
    {
        parent::_customInitStructure($struct, $masterDb, $slaveDb);
        $struct->addIndex('comment_id', array('comment_id'));
        $struct->addIndex('movie_id', array('movie_id'));
    }
}