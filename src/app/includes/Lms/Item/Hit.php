<?php

class Lms_Item_Hit extends Lms_Item_Abstract {
    
    static public function getTableName()
    {
        return '?_hits';
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
        if (!$this->getCreatedAt()) {
            $this->setCreatedAt(date('Y-m-d H:i:s'));
        }
        if (!$this->getIp()) {
            $this->setIp(Lms_Ip::getIp());
        }
        if (!$this->getUserId()) {
            $this->setUserId(Lms_User::getUser()->getId());
        }
    }
    
    public static function select($movieId, $userId, $ip)
    {
        $db = Lms_Db::get('main');
        $row = $db->selectRow("SELECT * FROM hits WHERE movie_id=?d AND  user_id=?d AND `ip`=?", $movieId, $userId, $ip);
        return Lms_Item_Abstract::rowToItem($row);
    }

    public static function updateMoviesHit()
    {
        $db = Lms_Db::get('main');
        $hits = $db->selectCol("SELECT movie_id AS ARRAY_KEY, count(*) FROM hits GROUP BY movie_id");
        
        foreach ($hits as $movieId => $hit) {
            $db->query('UPDATE movies SET hit=?d WHERE movie_id', $hit, $movieId);
        }
        return;
    }
    
}
