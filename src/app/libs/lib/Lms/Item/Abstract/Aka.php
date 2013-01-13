<?php

abstract class Lms_Item_Abstract_Aka extends Lms_Item_Abstract
{
    function addAkas(array $akas) 
    {
        $itemName = Lms_Item::getItemName($this);
        $itemAkaName = $itemName . 'Aka';
        $akas = array_unique($akas);//Для избежания дублирования ака
        foreach ($akas as $aka) {
            $akaObj = Lms_Item::create($itemAkaName);
            $akaObj->setName($aka);
            $this->add($akaObj);
        }
    }
}