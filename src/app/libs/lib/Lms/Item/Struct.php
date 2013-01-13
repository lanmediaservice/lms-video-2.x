<?php
/**
 * LMS Library
 * 
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov <webtota@gmail.com>
 * @version $Id: Struct.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2008-2009
 * @package Lms_Item 
 */

/**
 * Класс структуры таблицы
 *
 */
class Lms_Item_Struct
{
    
    /**
     * Является ли индекс полным
     *
     */
    const FULL_INDEX = 1;
    
    /**
     * Варианты экранирования значений, как int, float, string соответственно
     *
     */
    const ESCAPE_AS_INT = 2;
    const ESCAPE_AS_FLOAT = 4;
    const ESCAPE_AS_STRING = 8;
    
    /**
     * Ссылки на драйверы бд 
     *
     * @var srting
     */
    protected $_slaveDb;
    protected $_masterDb;
    
    /**
     * Имя таблицы 
     * @var string
     */
    private $_tableName;
    
    /**
     * Массив столбцов таблицы
     * @var array
     */
    private $_columns;
    
    /**
     * Флаг, инициализирована ли структура
     * @var  bool
     */
    private $_inited = false;
    
    /**
     * Перечень полей и индексов,в которых они учавствуют (для обновления в
     * реальном времени)
     * @var array
     */
    private $_indexingFields;
    
    /**
     *  Cписки индексов
     * @var array
     */
    private $_indexes;
    
    /**
     * Установить драйвера для доступа к бд
     *
     * @param DbSimple_Generic_Database $masterDb
     * @param DbSimple_Generic_Database $slaveDb
     */
    public function setDb(
        DbSimple_Generic_Database $masterDb,
        DbSimple_Generic_Database $slaveDb = null
    )
    {
        $this->_masterDb = $masterDb;
        $this->_slaveDb = ($slaveDb == null)? $masterDb : $slaveDb;
    }
    
    /**
     * Устанавливает название таблицы
     *
     * @return Lms_Item_Struct
     */
    public function setTableName($tableName)
    {
        $this->_tableName = $tableName; 
        return $this;
    }
    
    public function getTableName()
    {
        return $this->_tableName;    
    }
    
    /**
     * Извлекает структуру таблицы из БД
     * @return Lms_Item_Struct 
     */
    public function fetchStructure()
    {
        $columns = $this->_slaveDb->query(
            "SHOW COLUMNS FROM {$this->_tableName}"
        );
        foreach ($columns as $column) {
            $colName = array_shift($column);
            $this->_columns[$colName] = array_change_key_case(
                $column, CASE_LOWER
            );
            $this->_columns[$colName]['escape'] =
                $this->_getEscapeMethod($this->_columns[$colName]['type']);
        }
        $this->_inited = true;
        
        return $this;
    }

    /**
     * Возвращает способ экранирования переданного значения
     *
     * @param string $dbType
     * @return int
     */
    private function _getEscapeMethod($dbType)
    {
        $pos = strpos($dbType, '(');
        $type = false === $pos? substr($dbType, 0) : substr($dbType, 0, $pos);
        $typeMap = array(
            "int"       => self::ESCAPE_AS_INT,
            "varchar"   => self::ESCAPE_AS_STRING,
            "text"      => self::ESCAPE_AS_STRING,
            "tinyint"   => self::ESCAPE_AS_INT,
            "mediumint" => self::ESCAPE_AS_INT,
            "bigint"    => self::ESCAPE_AS_INT,
            "float"     => self::ESCAPE_AS_FLOAT,
            "double"    => self::ESCAPE_AS_FLOAT 
        );
        if (in_array($type, array_keys($typeMap))) {
            return $typeMap[$type];
        }
        return self::ESCAPE_AS_STRING;
    }
    
    /**
     * Добавляет определение столбца в структуру
     *
     * @param array $column вида array('colName'=>array('type'=>...))
     * @return Lms_Item_Struct
     */
    public function addColumn($columnName, $columnDefinition)
    {
        if (!is_array($columnDefinition)) {
            throw new Lms_Exception(
                'Definition of new colunm must be an array'
            );
        }
        if (!isset($this->_columns[$columnName])) {
            $this->_columns[$columnName] = $columnDefinition;
            $this->_columns[$columnName]['escape'] =
                $this->_getEscapeMethod($columnDefinition['type']);
        } else {
            throw new Lms_Exception(
                "Column $columnName is aldready exists "
                . "in the table {$this->tableName}"
            );
        }
        $this->_inited = true;
        
        return $this;
    }
    
    /**
     * Устанавливает определение столбца в структуру
     * @param string $columnName
     * @param array $columnDefinition
     * @return Lms_Item_Struct
     */
    public function setColumn($columnName, $columnDefinition)
    {
        $this->columns[$columnName] = $columnDefinition;
        $this->_inited = true;
        return $this;
    }
    
    
    /**
     * Возвращает признак проинициализирована ли структура
     *
     * @return bool
     */
    public function isInited()
    {
        return $this->_inited;
    }
    
    /**
     * Установливает флаг инициализирована ли структура
     *
     * @param bool $state
     */
    public function setInited($state = true)
    {
        $this->_inited = $state;
    }
    /**
     * Добавляет индекс $indexName по полям $fields
     * @param string $indexName
     * @param array $fields
     * @return Lms_Item_Struct 
     */
    public function addIndex($indexName, array $fields)
    {
        $this->_indexes[$indexName] = array(
            'fields' => $fields
        );
        foreach ($fields as $fieldName) {
            $this->_indexingFields[$fieldName][] = $indexName; 
        }
        Lms_Item_Store::rebuildIndex($this->_tableName, $indexName);
        /*
        Добавляет индекс
        Добавляет в _indexingFields соответствующие значения
        вызывает Lms_Item_Store::rebuildIndex(_tableName, indexName)
        */
        return $this;
    }
    
    /**
     * @param string $indexName
     * @return array
     */
    public function hasIndex($indexName)
    {
        return isset($this->_indexes[$indexName]);
    }

    /**
     * Возвращает определение индекса $indexName
     * @param string $indexName
     * @return array
     */
    public function getIndex($indexName)
    {
        return $this->_indexes[$indexName];
        //возвращает _indexes[indexName]
    }
    
    /**
     * Возвращает названия всех индексов таблицы
     */
    public function getIndexesNames()
    {
        return is_array($this->_indexes)? array_keys($this->_indexes) : array();
    }
    
    /**
     * Возвращает индекс, в которых учавствует поле fieldName:
     * @param string $fieldName
     * @return array
     */
    public function getIndexByFieldName($fieldName)
    {
        if (isset($this->_indexingFields[$fieldName])) {
            return $this->_indexingFields[$fieldName];
        } else {
            return array();
        }
    }
    

    /**
     * Возвращает первичный ключ в виде массива полей
     * @return array
     */
    public function getPk()
    {
        $primaryKeys = array();
        foreach ($this->_columns as $fieldName => $meta) {
            if (strtolower($meta['key']) == 'pri') {
                $primaryKeys[] = $fieldName; 
            }
        }
        if (!$primaryKeys) {
            throw new Lms_Exception(
                'There is no primary key or structure is not inited'
            );
        }
        return $primaryKeys;
    }


    public function hasAutoIncrement()
    {
        return (bool) $this->getAiFieldName();
    }

    public function getAiFieldName()
    {
        foreach ($this->getColumns() as $fieldName => $meta) {
            if ($meta['extra'] == 'auto_increment') {
                return $fieldName;
            }
        }
        return;
    }

    /**
     * Получить имена всех полей записи
     *
     * @return array
     */
    public function getFields()
    {
        $fields = array();
        foreach ($this->_columns as $fieldName => $meta) {
            $fields[] = $fieldName;
        }
        return $fields;
    }
    /**
     * Получить все столбцы в записи
     *
     * @return array
     */
    public function getColumns()
    {
        if (!$this->_inited) {
            $this->fetchStructure();
        }
        return $this->_columns;
    }
    
    /**
     * Устанавливает значение всех столбцов структуры
     *
     * @param array $columns
     */
    public function setColumns($columns)
    {
        $this->_columns = $columns;
    }
}