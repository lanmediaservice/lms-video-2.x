<?php
/**
 * LMS Library
 * 
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov <webtota@gmail.com>
 * @version $Id: Item.php 461 2010-07-20 07:25:23Z macondos $
 * @copyright 2008-2009
 * @package Lms_Item 
 */

/**
 * @package Lms_Item
 *
 */
class Lms_Item
{
    static private $_masterDb;
    static private $_slaveDb;
    static private $_initedClasses = array();
    static private $_namespace = 'Lms_Item_';
    
    /**
     * Sets master database and optionaly slave database 
     *
     * @param DbSimple_Database $masterDb
     * @param DbSimple_Database $slaveDb
     */
    
    public static function setDb(
        DbSimple_Database $masterDb,
        DbSimple_Database $slaveDb = null,
        $itemNames = 'default'
    )
    {
        if (is_array($itemNames)) {
            foreach ($itemNames as $itemName) {
                $itemName = strtolower($itemName);
                self::$_masterDb[$itemName] = $masterDb;
                self::$_slaveDb[$itemName] = ($slaveDb == null)? $masterDb : $slaveDb;
            }
        } else {
            $itemName = strtolower($itemNames);
            self::$_masterDb[$itemName] = $masterDb;
            self::$_slaveDb[$itemName] = ($slaveDb == null)? $masterDb : $slaveDb;
        }
        Lms_Item_Sql::setDb($masterDb);
    }
    
    public static function getMasterDb($itemName = null)
    {
        if (!$itemName) {
            $itemName = self::getCallingItemName();
        }
        if ($itemName) {
            $itemName = strtolower($itemName);
        }
        if (isset(self::$_masterDb[$itemName])) {
            return self::$_masterDb[$itemName];
        } else {
            return self::$_masterDb['default'];
        }
    }

    public static function getSlaveDb($itemName = null)
    {   
        if (!$itemName) {
            $itemName = self::getCallingItemName();
        }
        if ($itemName) {
            $itemName = strtolower($itemName);
        }
        if (isset(self::$_slaveDb[$itemName])) {
            return self::$_slaveDb[$itemName];
        } else {
            return self::$_slaveDb['default'];
        }
    }
    /**
     * Creates an instance of class with Lms_Item prefix,
     * and initializes it's properties
     *
     * @param string $name
     * @param array $data
     * @param bool $saved
     * @return Lms_Item_Generic
     */
    public static function create($itemName, $pk = null)
    {
        $className = self::getClassName($itemName);
        if (!class_exists($className, true)) {
            throw new Lms_Exception('Can not instantiate class: ' . $className);
            return null;
        }
        //if (!isset(self::$_initedClasses[$className])) {
            self::initStructure($className);
        //}
        return new $className(self::getMasterDb($itemName), self::getSlaveDb($itemName), $pk);
        /*
        Создает экземляр сущности Lms_Item_<ItemName>, устанавливает драйверы БД
        */
    } 
    
    static public function initStructure($className)
    {
        if (!isset(self::$_initedClasses[$className])) {
            $struct = Lms_Item::getStruct(self::getItemName($className));
            /**
             * @todo  инициализировать связи (relations) только один раз,
             * основываясь на имени класса объекта, а не на имени таблицы,
             * так как Cover, Poster, Screenshot имееют одну и ту же
             * таблицу images
             */
            call_user_func(array($className, 'initRelations'), $className);
            $itemName = self::getItemName($className);
            call_user_func(
                array($className, '_customInitStructure'),
                $struct, self::getMasterDb($itemName), self::getSlaveDb($itemName)
            );
            self::$_initedClasses[$className] = true;
        }
    }

   /**
     * Возвращает имя сущности
     *
     * @param string $obj
     */
    static public function getItemName($objectOrClassName)
    {
        if ($objectOrClassName instanceof Lms_Item_Abstract) {
            $className = get_class($objectOrClassName);
        } elseif (is_string($objectOrClassName)) {
            $className = $objectOrClassName;
        } else {
            throw new Lms_Item_Exception(
                'Object is not instance of Lms_Item_Abstract'
            );
        }
        return str_replace(self::$_namespace, '', $className);
    }
    
   /**
     * Возвращает название класса по имени сущности
     *
     * @param string $obj
     */
    static public function getClassName($itemName)
    {
        return self::$_namespace . ucfirst($itemName);
    }
    
    static public function getStruct($itemName)
    {
        $tableName = self::getTableName($itemName);
        return Lms_Item_Store::getStruct($tableName);
    }
    
    static public function getTableName($itemName)
    {
        $className = Lms_Item::getClassName($itemName);
        return call_user_func(
            array($className, 'getTableName')
        );
    }
    
    static public function getLinkator()
    {
        $items = func_get_args();
        if (count($items) == 1 && is_array($items[0])) {
            //входящие параметры переданы массивом
            $items = $items[0];
        }
        $relations = array();
        foreach ($items as $key => $item) {
            $itemName = self::getItemName($item);
            $items[$key] = $itemName;
            self::initStructure(self::getClassName($itemName));
            $relations[] = array_keys(Lms_Item_Relations::getAll($itemName));
        }
        //Находим место пересечения связей
        $intersection = call_user_func_array('array_intersect', $relations);
        if (count($intersection)==0) {
            throw new Lms_Item_Exception(
                "Relation between " . implode(', ', $items) .  " not found"
            );
        }
        if (count($intersection)>1) {
            throw new Lms_Item_Exception(
                "Ambiguity relations between " . implode(', ', $items)
            );
        }
        //Получаем имя линкатора
        $linkatorName = reset($intersection);
        return $linkatorName;
    }

    static public function getSimplePk($itemName)
    {
        $struct = self::getStruct($itemName);
        $pk = $struct->getPk();
        if (!count($pk)==1) {
            return false;
        }
        return reset($pk);
    }
    
    static public function getCallingItemName()
    {
        foreach (debug_backtrace(false) as $trace) {
            if (preg_match('{^' . self::$_namespace . '(.*?)$}i', $trace['class'], $matches) && $matches[1]!='Abstract') {
                return $matches[1];
            }
        }
        return null;
    }
    
}