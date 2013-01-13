<?php
/**
 * LMS Library
 * 
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov <webtota@gmail.com>
 * @version $Id: Store.php 461 2010-07-20 07:25:23Z macondos $
 * @copyright 2008-2009
 * @package Lms_Item 
 */

/**
 * Класс является локальным хранилищем данных.
 * Все методы и свойства класса статичны
 *
 */
class Lms_Item_Store
{
    
    /**
     * Cтруктуры данных
     * 
     * @var array
     */
    private static $_struct;
    
    /**
     * Значения полей записи по таблицам и полям
     * 
     * @var array
     */
    private static $_data;
    
    /**
     * списки названий полей измененных записей (для дальнейшего использования 
     * в качестве экономичного обновления)
     * 
     * @var array
     */
    private static $_changes;
    
    /**
     * индексы по таблицам 
     *
     * @var array
     */
    private static $_index;
    
    /**
     * статусы индексов
     *
     * @var array
     */
    private static $_indexStatuses;
    
    /**
     * Устанавливает значение поля записи
     * 
     * @param string $tableName
     * @param string $scalarPK
     * @param string $fieldName
     * @param int/string $value
     * @param bool $isInit Если true - производит обновление связанных индексов
     * @param bool $disableIndex
     * @return bool
     */
    public static function setValue(
        $tableName, $scalarPK, $fieldName, $value,
        $isInit = false, $disableIndex = false
    )
    {
        if (isset(self::$_data[$tableName][$scalarPK][$fieldName]) 
            && $value==self::$_data[$tableName][$scalarPK][$fieldName]
        ) {
            return true;
        }
        if (!$disableIndex) {
            //Если данное поле учавствует в индексе
            $struct = self::getStruct($tableName);
            $fieldIndexes = $struct->getIndexByFieldName($fieldName);
            if (count($fieldIndexes)) {
                // нужно удалить из затрагиваемых изменениями 
                //_index текущую запись:
                foreach ($fieldIndexes as $indexName) {
                    $oldIndexKey = self::getScalarIndexKey(
                        $tableName, $indexName, $scalarPK
                    );
                    if (isset(self::$_index[$tableName][$indexName][$oldIndexKey])) {
                        $k = array_search(
                            $scalarPK, 
                            self::$_index[$tableName][$indexName][$oldIndexKey]
                        );
                        if ($k !== false) {
                            unset(self::$_index[$tableName][$indexName][$oldIndexKey][$k]);
                        }
                    }
                }
            }
        }
        //сохраняет запись в _data
        self::$_data[$tableName][$scalarPK][$fieldName] = $value;
        //если isInit==false, то нужно пополнить список измененных полей:
        if (!$isInit 
            && (!isset(self::$_changes[$tableName][$scalarPK])
                || !is_array(self::$_changes[$tableName][$scalarPK])
                || !in_array($fieldName, self::$_changes[$tableName][$scalarPK]))
        ) {
            self::$_changes[$tableName][$scalarPK][] = $fieldName;
        }
        
        if (!$disableIndex) {
            if (count($fieldIndexes)) {
                //Если данное поле учавствует в индексе
                foreach ($fieldIndexes as $indexName) {
                    //то нужно добавить текущую запись в затрагиваемые индексы:
                    $newIndexKey = self::getScalarIndexKey(
                        $tableName, $indexName, $scalarPK
                    );
                    if (!isset(self::$_index[$tableName][$indexName][$newIndexKey])) {
                        self::$_index[$tableName][$indexName][$newIndexKey] = array();
                    }
                    if (!in_array($scalarPK, self::$_index[$tableName][$indexName][$newIndexKey])) {
                        self::$_index[$tableName][$indexName][$newIndexKey][] = $scalarPK;
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Возвращает значение поля записи
     * 
     * @param string $tableName
     * @param string $scalarPK
     * @param string $fieldName
     * @return mixed
     * @throws Lms_Item_Store_FieldValueNotExistsException
     */
    public static function getValue($tableName, $scalarPK, $fieldName)
    {
        if (!isset(self::$_data[$tableName])
            || !is_array(self::$_data[$tableName])
            || !isset(self::$_data[$tableName][$scalarPK])
            || !array_key_exists($fieldName, self::$_data[$tableName][$scalarPK])
        ) {
            throw new Lms_Item_Store_FieldValueNotExistsException(
                "No such data at the Store (#$scalarPK: $fieldName)"
            );
        }
        //возвращает данные
        return self::$_data[$tableName][$scalarPK][$fieldName];
    }
    
    /**
     * Возвращает список измененных полей и их значения
     * в виде ассоциированного массива
     * 
     * поля на основе _changes[tableName][scalarPK]
     * данные на основе _data[<tablename>][<scalarPK>]
     * @param string $tableName
     * @param string $scalarPK
     * @return array
     */
    public static function getChanges($tableName, $scalarPK)
    {
        if (!isset(self::$_changes[$tableName][$scalarPK])
            || !is_array(self::$_changes[$tableName][$scalarPK])
        ) {
            return array();
        }
        $result = array();
        foreach (self::$_changes[$tableName][$scalarPK] as $fieldName) {
            $result[$fieldName] = self::$_data[$tableName][$scalarPK][$fieldName];
        }
        return $result;
    }
    
    /**
     * Обнуляет список измененных полей
     * @param string $tableName
     * @param string $scalarPK
     * @return bool
     */
    public static function flushChanges($tableName, $scalarPK)
    {
        self::$_changes[$tableName][$scalarPK] = array();
        return true;
    }
    
    /**
     * Возвращает скаляризованный ключ индекса $indexName таблицы $tableName
     * для поля $scalarPK
     * Например, 
     * INSERT INTO files(file_id, movie_id, path) (1, 1000, '/file');
     * $struct->addIndex('movie_id', array('movie_id'));
     * echo Lms_Item_Store::getScalarIndexKey('files', 'movie_id', 1); //1000
     * @param string $indexName
     * @param string $scalarPK
     * @return int/string
     */
    private static function getScalarIndexKey($tableName, $indexName, $scalarPK)
    {
        $struct = self::getStruct($tableName);
        $index = $struct->getIndex($indexName);
        $indexKey = array();
        try {
            foreach ($index['fields'] as $fieldName) {
                $indexKey[$fieldName] = self::getValue(
                    $tableName, $scalarPK, $fieldName
                );
            }
        } catch (Lms_Item_Store_FieldValueNotExistsException $e) {
            return null;
        }
        return Lms_Item_Scalar::scalarize($indexKey);
    }
    
    /**
     * Перестраивает индексы 
     * @param string $tableName
     * @param string $indexName
     * @return bool
     */
    public static function rebuildIndex($tableName, $indexName = false)
    {
        $rebuldIndexes = array();
        if (!$indexName) {//если indexName == false, то перестраиваем все индексы
            $rebuldIndexes = self::getStruct($tableName)->getIndexesNames();
        } else {//перестраиваем только $indexName
            $rebuldIndexes = array($indexName);
        }
        foreach ($rebuldIndexes as $rebuldIndexName) {
            self::$_index[$tableName][$rebuldIndexName] = array();
            $index = self::getStruct($tableName)->getIndex($rebuldIndexName);
            if (isset(self::$_data[$tableName])) {
                foreach (self::$_data[$tableName] as $scalarPK => $values) {
                    $scalarIndexKey = Lms_Item_Scalar::extractScalarPkValue(
                        $values, $index['fields']
                    );
                    self::$_index[$tableName][$rebuldIndexName][$scalarIndexKey][] = $scalarPK;
                }
            }
        }
        return true;
    }
    
    /**
     * Удаляет запись и обновляет индексы, в которых она учавствует
     * @param string $tableName
     * @param string $scalarPK
     * @return 
     */
    public static function delete($tableName, $scalarPK)
    {
        $indexes = self::getStruct($tableName)->getIndexesNames();
        foreach ($indexes as $indexName) {
            $oldIndexKey = self::getScalarIndexKey($tableName, $indexName, $scalarPK);
            if (($k = array_search($scalarPK, self::$_index[$tableName][$indexName][$oldIndexKey]))!==false) {
                unset(self::$_index[$tableName][$indexName][$oldIndexKey][$k]);
            }
        }
        
        unset(self::$_data[$tableName][$scalarPK]);
        self::flushChanges($tableName, $scalarPK);
        return true;
    }
    
    /**
     * Устанавливает одновременно несколько значений полей записи
     * @param string $tableName Название таблицы
     * @param string $scalarPK Скалярный ключ записи
     * @param array $values Ассоциированный массив значений полей
     * @param bool $disableIndex При true отключается обновление индекса
     * @return 
     */
    public static function setValues(
        $tableName, $scalarPK, $values, $disableIndex = false
    )
    {
        if ($disableIndex) {
            self::$_data[$tableName][$scalarPK] = $values;
        } else {
            foreach ($values as $fieldName => $value) {
                 self::setValue(
                     $tableName, $scalarPK, $fieldName,
                     $value, true, $disableIndex
                 );
            }
        }
        return true;
    }
    
    /**
     * Возвращает скалярные ключи всех записей по индексу $indexName и
     * индексному ключу $scalarIndexKey таблицы $tableName
     * @param string $tableName
     * @param string $indexName
     * @param string $scalarIndexKey
     * @return 
     */
    public static function getIndexedValues(
        $tableName, $indexName, $scalarIndexKey
    )
    {

        if (isset(self::$_index[$tableName][$indexName][$scalarIndexKey])) {
            return self::$_index[$tableName][$indexName][$scalarIndexKey];
        } else {
            return array();
        }
    }
    
    /**
     * Устанавливает статус $status для индексного ключа $scalarIndexKey
     * индекса $indexName
     * @param string $indexName
     * @param string $scalarIndexKey
     * @param int $status
     * @return Lms_Item_Struct
     */
    public static function setIndexStatus(
        $tableName, $indexName, $scalarIndexKey, $status
    )
    {
        self::$_indexStatuses[$tableName][$indexName][$scalarIndexKey] = $status;
    }

    /**
     * Возвращает статус для индексного ключа $scalarIndexKey индекса $indexName
     * @param string $indexName
     * @param string $scalarIndexKey
     */
    public static function getIndexStatus($tableName, $indexName, $scalarIndexKey)
    {
        if (!self::getStruct($tableName)->hasIndex($indexName)) {
            throw new Exception(
                "Index $indexName is not defined in table '$tableName'"
            );
        }
        if (isset(self::$_indexStatuses[$tableName][$indexName][$scalarIndexKey])) {
            return self::$_indexStatuses[$tableName][$indexName][$scalarIndexKey];
        }
        return null;
    }

    /**
     * Возвращает ссылку на структуру таблицы $tableName
     * Если структура не существует, то предварительно создает ее
     * 
     * @param string $tableName
     * @return Lms_Item_Struct
     */
    public static function getStruct($tableName)
    {
        if (!isset(self::$_struct[$tableName])) {
            self::$_struct[$tableName] = new Lms_Item_Struct();
            self::$_struct[$tableName]->setTableName($tableName);
        }
        return self::$_struct[$tableName];
    }
    
    /**
     * Возвращает истину, если запись с именем таблицы $tableName и
     * скаляризованным ключом $scalarPK присутствует в хранилище данных
     *
     * @param string $tableName
     * @param string $scalarPK
     * @return bool
     */
    public static function has($tableName, $scalarPK)
    {
        //TODO: isset быстрее array_key_exists до 6 раз, но не ловит null
        /* if (!isset(self::$_data[$tableName])) {
            return false;
        }
        return array_key_exists($scalarPK, (array)self::$_data[$tableName]);
        */
        return isset(self::$_data[$tableName][$scalarPK]);
    }
    /**
     * Полностью очищает регистр
     *
     */
    public static function clean()
    {
        $tables = func_get_args();
        if (count($tables)) {
            foreach ($tables as $tableName) {
                self::$_data[$tableName] = array(); 
                self::$_index[$tableName] = array();
                self::$_indexStatuses[$tableName] = array();
                self::$_changes[$tableName] = array();
            }
        } else {
            self::$_data = array(); 
            self::$_index = array();
            self::$_indexStatuses = array();
            self::$_changes = array();
        }
    }
    
    /**
     * Возвращает список скалярных ключей таблицы
     *
     * @param string $tableName
     * @return array
     */
    public static function getTableSkalarKeys($tableName)
    {
        if (isset(self::$_data[$tableName])) {
            return array_keys(self::$_data[$tableName]);
        } else {
            return array();
        }
    }
    
    public static function getAllFieldValues($scalarPKs, $tableName, $fieldName)
    {
        $outputArray = array();
        foreach ($scalarPKs as $scalarPK) {
            $outputArray[] = self::$_data[$tableName][$scalarPK][$fieldName];
        }
        return $outputArray;
    }
    
    public static function getData()
    {
        return self::$_data;
    }
    
    public static function getIndex()
    {
        return self::$_index;
    }
    
}