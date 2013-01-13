<?php

class Lms_Item_Comment extends Lms_Item_Abstract {
    
    public static function getTableName()
    {
        return '?_comments';
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
    
    protected function _preDelete() 
    {
        $this->getChilds('Linkator_CommentMovie')->delete(); 
    }      
}
