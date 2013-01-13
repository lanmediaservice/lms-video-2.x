<?php
/**
 * LMS Library
 * 
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov <webtota@gmail.com>
 * @version $Id: Preloader.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2008-2009
 * @package Lms_Item 
 */


class Lms_Item_Preloader
{
    static private $_db;
    
    static public function load(
        $parent, $childs, $keyField, array $ids, $foreignKey = null
    )
    {
        self::parseItemStatement($parent, $parentItemName, $parentFields);
        Lms_Item::initStructure(Lms_Item::getClassName($parentItemName));
        $struct = Lms_Item::getStruct($parentItemName);
        if (count($parentFields)) {
            if ($foreignKey && !in_array($foreignKey, $parentFields)) {
                array_unshift($parentFields, $foreignKey);
            }
            foreach ($struct->getPk() as $fieldName) {
                if (!in_array($fieldName, $parentFields)) {
                    array_unshift($parentFields, $fieldName);
                }
            }
        }
        if (!$foreignKey && $struct->hasIndex($keyField)) {
            $foreignKey = $keyField;
        }
        $newScalarPKs = self::_exec(
            $parentItemName, $parentFields, $keyField, $ids, $foreignKey
        );
        foreach ($childs as $child) {
            self::parseItemStatement($child, $childItemName, $childFields);
            Lms_Item::initStructure(Lms_Item::getClassName($childItemName));
            $relation = Lms_Item_Relations::get($parentItemName, $childItemName);
            if (!$relation) {
                $linkatorItemName = Lms_Item::getLinkator(
                    $parentItemName, $childItemName
                );
                $subRelation = Lms_Item_Relations::get(
                    $parentItemName, $linkatorItemName
                );
                $newKeys = array_unique(
                    Lms_Item_Store::getAllFieldValues(
                        $newScalarPKs,
                        Lms_Item::getTableName($parentItemName),
                        $subRelation['parent_key']
                    )
                );
                self::load(
                    $linkatorItemName, array($child),
                    $subRelation['foreign_key'], $newKeys,
                    $subRelation['foreign_key']
                );
            } else {
                if (count($childFields)) {
                    if ($relation
                        && !in_array($relation['foreign_key'], $childFields)
                    ) {
                        array_unshift($childFields, $relation['foreign_key']);
                    }
                    $struct = Lms_Item::getStruct($childItemName);
                    foreach ($struct->getPk() as $fieldName) {
                        if (!in_array($fieldName, $childFields)) {
                            array_unshift($childFields, $fieldName);
                        }
                    }
                }
                $newKeys = array_unique(
                    Lms_Item_Store::getAllFieldValues(
                        $newScalarPKs,
                        Lms_Item::getTableName($parentItemName),
                        $relation['parent_key']
                    )
                );
                self::_exec(
                    $childItemName, $childFields,
                    $relation['foreign_key'], $newKeys,
                    $relation['foreign_key']
                );
            }
        }
    }
    
    static public function parseItemStatement(
        $statement, &$itemName = '', &$fields = array()
    )
    {
        if (preg_match('{^(.*?)(\((.*?)\))?$}i', $statement, $matches)) {
            $itemName = trim($matches[1]);
            $fields = array();
            if (isset($matches[3])) {
                $exploadedFields = explode(",", $matches[3]);
                foreach ($exploadedFields as $fieldName) {
                     $fields[] = trim($fieldName);
                }
            }
        }
    }
    
    /**
     * Устанавливает драйвер таблицы
     *
     * @param unknown_type $db
     */
    static public function setDb($db)
    {
        self::$_db = $db;
    }
    
    static private function _exec(
        $itemName, $fields, $keyField, $ids, $foreignKey = null
    )
    {
        if (!count($ids)) {
            return array();
        }
        if (count($fields)) {
            $escapedFields = array();
            foreach ($fields as $fieldName) {
                $escapedFields[] = self::$_db->escape($fieldName, true);
            }
            $sqlFields = implode(',', $escapedFields);
        } else {
            $sqlFields = '*';
        }
        $tableName = Lms_Item::getTableName($itemName);
        $query = "SELECT $sqlFields FROM $tableName WHERE ?# IN (?a)";
        $rows = self::$_db->select($query, $keyField, $ids);
        return self::_fillData($tableName, $rows, $foreignKey);
    }

    private static function _fillData($tableName, $rows, $foreignKey)
    {
        $newScalarPKs = array();
        if (!count($rows)) {
            return $newScalarPKs;
        }
        $struct = Lms_Item_Store::getStruct($tableName);
        $pk = $struct->getPk();
        for ($i = count($rows)-1; $i>=0; $i--) {
            if (is_array($pk)) {
                $assocPK = array();
                foreach ($pk as $pkFieldName) {
                    $assocPK[$pkFieldName] = $rows[$i][$pkFieldName];
                }
                $scalarPk = Lms_Item_Scalar::scalarize($assocPK);
            } else {
                $scalarPk = $rows[$i][$pk];
            }
            $newScalarPKs[] = $scalarPk;
            Lms_Item_Store::setValues($tableName, $scalarPk, $rows[$i], true);
            if ($foreignKey && isset($rows[$i][$foreignKey])) {
                $indexKey = $rows[$i][$foreignKey];
                Lms_Item_Store::setIndexStatus(
                    $tableName, $foreignKey, $indexKey,
                    Lms_Item_Struct::FULL_INDEX
                );
            }
        }
        Lms_Item_Store::rebuildIndex($tableName);
        return $newScalarPKs;
    }
}