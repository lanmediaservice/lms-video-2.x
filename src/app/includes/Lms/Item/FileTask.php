<?php

class Lms_Item_FileTask extends Lms_Item_Abstract {
    
    static public function getTableName()
    {
        return '?_files_tasks';
    }
    
     
    protected function _preInsert() 
    {
        if (!$this->getCreatedAt()) {
            $this->setCreatedAt(date('Y-m-d H:i:s'));
        }
    }   
    
    public static function select($limit = 100, $maxTries = 4)
    {
        $db = Lms_Db::get('main');
        $rows = $db->select(
            'SELECT * FROM ' . self::getTableName() . ' {WHERE tries<?d} ORDER BY tries, created_at {LIMIT ?d}',
            $maxTries?: DBSIMPLE_SKIP,
            $limit?: DBSIMPLE_SKIP
        );
        return self::rowsToItems($rows);
    }
    
    public static function create($from, $to)
    {
        $fileTask = Lms_Item::create('FileTask');
        if (is_dir($from)) {
            $from = rtrim($from, '/') . '/';
        }
        $fileTask->setFrom($from)
                 ->setTo($to)
                 ->save();
        return $fileTask;
    }
    
    public function exec()
    {
        $this->setTries($this->getTries() + 1)
             ->save();
        if (Lms_Ufs::is_dir($this->getFrom())) {
            $targetCreated = Lms_Ufs::file_exists($this->getTo()) || Lms_Ufs::mkdir($this->getTo(), Lms_Application::getConfig('filesystem', 'permissions', 'directory'), true);
            $sourceDeleted = !Lms_Ufs::file_exists($this->getFrom()) || Lms_Ufs::rmdir($this->getFrom());
            if ($targetCreated && $sourceDeleted) {
                $this->delete();
            }
        } else {
            $targetDir = dirname($this->getTo());
            if (!Lms_Ufs::is_dir($targetDir)) {
                Lms_Ufs::mkdir($targetDir, Lms_Application::getConfig('filesystem', 'permissions', 'directory'), true);
            }
            if (Lms_Ufs::rename($this->getFrom(), $this->getTo())) {
                $files = Lms_Item_File::selectByPath($this->getTo());
                foreach ($files as $file) {
                    $file->setActive(1)
                         ->save();
                }
                Lms_Ufs::chmod($this->getTo(), Lms_Application::getConfig('filesystem', 'permissions', 'file'));
                $this->delete();
            }
        }
    }
    
    public static function pathInTasks($path)
    {
        $db = Lms_Db::get('main');
        $count = $db->selectCell(
            'SELECT count(*) FROM ' . self::getTableName() . ' WHERE `from` LIKE ? OR `to` LIKE ?',
            "$path%", "$path%"
        );
        
        return (bool) $count;
    }
    
    
    public static function resetTries()
    {
        $db = Lms_Db::get('main');
        $db->query('UPDATE ' . self::getTableName() . ' SET tries=0');
    }

    public static function clear()
    {
        $db = Lms_Db::get('main');
        $db->query('DELETE FROM ' . self::getTableName());
    }
    
}
