<?php

class Lms_Item_Abstract_Serialized extends Lms_Item_Abstract {
    
    protected $_serializedFields = array(
    ); 
    
    protected function _set($propetryName, $value)
    {
        $fieldName = $this->_getFieldNameByProperty($propetryName);
        if (in_array($fieldName, $this->_serializedFields)) {
            $newValue = serialize($value);
        } else {
            $newValue = $value;
        }
        return parent::_set($fieldName, $newValue);
    }
    
    protected function _get($propetryName)
    {
        $fieldName = $this->_getFieldNameByProperty($propetryName);
        $value = parent::_get($fieldName);
        if (in_array($fieldName, $this->_serializedFields)) {
            $value = unserialize($value);
        }
        return $value;
    }
}
