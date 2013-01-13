<?php

class Lms_Item_Log extends Lms_Item_Abstract {
    
    const STATUS_ERROR = 0;
    const STATUS_IN_PROCESS = 1;
    const STATUS_DONE = 2;
    
    const TYPE_RATINGS_UPDATE = 'ratings-update';
    const TYPE_RATINGS_LOCAL_UPDATE = 'ratings-local-update';
    const TYPE_PERSONES_FIX = 'persones-fix';
    const TYPE_FILES_CHECK = 'files-check';
    
    static public function getTableName()
    {
        return '?_log';
    }
    
    protected function _preInsert() 
    {
        if (!$this->getPid()) {
            $this->setPid(getmypid());
        }
        if (!$this->getStartedAt()) {
            $this->setStartedAt(date('Y-m-d H:i:s'));
        }
        if (!$this->getStatus()) {
            $this->setStatus(self::STATUS_IN_PROCESS);
        }
        
        
    }   
    
    public static function create($type, $message)
    {
        $log = Lms_Item::create('Log');
        $log->setType($type)
            ->setMessage($message)
            ->save();
        return $log;
    }    
    
    public function progress($message)
    {
        $this->setMessage($message)
             ->save();
        return $this;
    }    
    
    public function done($status, $message, $report = null)
    {
        $this->setStatus($status)
             ->setEndedAt(date('Y-m-d H:i:s'))
             ->setMessage($message)
             ->setReport($report)
             ->save();
        return $this;
    }    
    
    public static function selectLast($includeOnly = null)
    {
        $db = Lms_Db::get('main');
        $ids = $db->selectCol(
            'SELECT max( `log_id` ) FROM ' . self::getTableName() . ' WHERE 1 {AND `type` IN (?a)} GROUP BY `type`',
            is_array($includeOnly)? $includeOnly : DBSIMPLE_SKIP
        );
        
        if (!$ids) {
            return array();
        }
        $rows = $db->select('SELECT * FROM ' . self::getTableName() . ' WHERE log_id IN (?a)', $ids);
        return self::rowsToItems($rows);
    }
    
}
