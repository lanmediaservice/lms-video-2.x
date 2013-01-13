<?php

class Lms_Item_Bookmark extends Lms_Item_Abstract {
    
    public static function getTableName()
    {
        return '?_bookmarks';
    }
    
    public static function _customInitStructure(
        Lms_Item_Struct $struct,
        DbSimple_Generic_Database $masterDb,
        DbSimple_Generic_Database $slaveDb
    )
    {
        parent::_customInitStructure($struct, $masterDb, $slaveDb);
        $struct->addIndex('movie_id', array('movie_id'));
    }
    
    protected function _preInsert() 
    {
        if (!$this->getUserId()) {
            $this->setUserId(Lms_User::getUser()->getId());
        }
    }
    
    public static function deleteBookmark($movieId)
    {
        $db = Lms_Db::get('main');
        $sql = "DELETE FROM bookmarks WHERE user_id=?d AND movie_id=?d";
        $db->query($sql, Lms_User::getUser()->getId(), $movieId);
    }
}
