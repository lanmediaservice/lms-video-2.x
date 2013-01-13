<?php
/**
 * LMS Library
 * 
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov <webtota@gmail.com>
 * @version $Id: Abstract.php 553 2010-10-22 21:58:26Z macondos $
 * @copyright 2008-2009
 * @package Lms_Item 
 */


abstract class Lms_Item_Abstract
{
    
    const IF_NOT_EXISTS = true;
    const FROM_BUFFER = true;
    
    /**
     * Ссылка на драйвер бд
     *
     * @var DbSimple_Generic_Database
     */
    protected $_slaveDb;    
    
    /**
     * Ссылка на драйвер бд
     *
     * @var DbSimple_Generic_Database
     */
    protected $_masterDb;
    
    /**
     * Переменная для временного хранения данных несохраненной записи
     * @var array
     */
    protected $_buffer;
    
    /**
     * Cкалярное (целочисленное или строковое) значение первичного ключа
     * значение null указывает на то, что запись не сохранена
     * @var string/int
     */
    protected $_scalarPkValue;

    /**
     * Флаг, использовать ли обновление всех полей записи
     *
     * @var bool
     */
    protected $_fullUpdate = false;
    
    /**
     * Constructor
     *
     * @param DbSimple_Generic_Database $_masterDb
     * @param DbSimple_Generic_Database $_slaveDb
     * @param null|int|string $pkValue
     * 
     */
    public function __construct(
        DbSimple_Generic_Database $masterDb = null,
        DbSimple_Generic_Database $slaveDb = null,
        $pkValue = null
    )
    {
        if ($slaveDb!==null) {
            $this->_slaveDb  = $slaveDb;
        }
        if ($masterDb!==null) {
            $this->_masterDb = $masterDb;
        }
        $this->init($pkValue);
    }
    
    
    /**
     * �?нициализирует структуру
     *
     */
    public static function _customInitStructure(
        Lms_Item_Struct $struct,
        DbSimple_Generic_Database $masterDb,
        DbSimple_Generic_Database $slaveDb
    )
    {
        $struct->setDb($masterDb, $slaveDb);
        //$masterDb->_identPrefix
        $tableName = $struct->getTableName();
        $tableClassName = Lms_Item_Struct_Generator::getTableClassName($tableName);
        if (class_exists($tableClassName, true)) {
            $columns = call_user_func(array($tableClassName, 'getColumns'));
            $struct->setColumns(array_change_key_case($columns, CASE_LOWER));
            $struct->setInited();
        } else {
            $struct->fetchStructure();
            Lms_Item_Struct_Generator::generate(
                $tableClassName, $struct->getColumns()
            );
        }
    }
    
    /**
     * Возвращает имя поля в таблице бд на основании имени свойства объекта
     *
     * @param string $property
     * @return string
     */
    protected function _getFieldNameByProperty($property)
    {
        static $translator = false;
        if ($translator === false) {
            $struct = $this->getStruct();
            foreach (array_keys($struct->getColumns()) as $fieldName) {
                $key = strtolower(str_replace('_', '', $fieldName));
                $translator[$key] = strtolower($fieldName);
            }
        }

        if (!isset($translator[$property])) {
            return $property;
        }
        return $translator[$property];
    }
    
    /**
     * �?нициализирует объект
     *
     * @param int $id
     */
    final protected function init($pkValue = null)
    {
        if ($pkValue !== null) {
            $this->_scalarPkValue = Lms_Item_Scalar::scalarize($pkValue);
            if (!Lms_Item_Store::has($this->getTableName(), $this->_scalarPkValue)) {
                $this->load($pkValue);
            }
        }
    }
    
    /**
     * �?нициализирует связи
     *
     * @param string $className
     */
    public static function initRelations($className = null)
    {
        if (!$className) {
            $className = get_class($this);
        }
        $relationClassName = str_replace(
            'Lms_Item_', 'Lms_Item_Relations_', $className
        );
        if (class_exists($relationClassName, true)) {
            call_user_func(array($relationClassName, 'perform'));
        }
    }
    
    /**
     * Загрузить данные
     * если PK не указан, то определяет его по данным стуктутуры и имеющихся
     * значений полей (на случай перезагрузки записи, при неполной ее
     * предварительной загрузке)
     * вызывает preLoad()
     * вызывает _load(PK) (реализация забора данных)
     * если данные были загружены успешно, вызывает postLoad()
     * сохраняет полученные данные в хранилище Lms_Item_Store
     * 
     * @param mixed $pkValue
     * @return Lms_Item_Abstract
     */
    final public function load($pkValue = null)
    {
        if ($pkValue === null) {
            $assocPkValue = $this->_getAssocPkValue();
        } else {
            if (is_array($pkValue)) {
                $assocPkValue = $pkValue;
            } else {
                $struct = $this->getStruct();
                $pk = $struct->getPk();
                $assocPkValue = Lms_Item_Scalar::descalarize($pkValue, $pk);
            }
        }
        $this->_preLoad();
        if (false !== $value = $this->_load($assocPkValue)) {
            if ($this->_scalarPkValue === null) {
                $this->_scalarPkValue = Lms_Item_Scalar::scalarize($assocPkValue);
            }
            Lms_Item_Store::setValues(
                $this->getTableName(), $this->_scalarPkValue,
                $value
            );
            $this->_postLoad();
        } else {
            if ($pkValue !== null) {
                $this->_scalarPkValue = null;
            }
        }
        
        return $this;
    }
    
    /**
     * Возвращает ассоциированный массив первичных ключей и их значений на
     * основании данных в общем хранилище данных, а еслие передан
     * параметр $fromBuffer, то массив из свойства $_buffer объекта
     *
     * @return array;
     */
    protected function _getAssocPkValue($fromBuffer = false)
    {
        $struct = $this->getStruct();
        $pk = $struct->getPk();
        $assocPkValue = array();
        foreach ($pk as $fieldName) {
            $fieldName = strtolower($fieldName);
            if ($fromBuffer) {
                if (isset($this->_buffer[$fieldName])) {
                    $assocPkValue[$fieldName] = $this->_buffer[$fieldName];
                } else {
                    $assocPkValue[$fieldName] = null;
                }
            } else {
                $assocPkValue[$fieldName] = Lms_Item_Store::getValue(
                    $this->getTableName(), $this->_scalarPkValue, $fieldName
                );
            }
        } 
        return $assocPkValue;
    }
    
    /**
     * Возвращает имя первичного ключа.
     * Для мультикей сущностей бросает Exception
     *
     * @return string
     */
    protected function _getSimplePk()
    {
        $pk = $this->getStruct()->getPk();
        if (count($pk) != 1) {
            throw new Lms_Exception(
                'You must specify full primary key for multikey items'
            );
        }
        return reset($pk);
    }
    
    /*
     * Осуществляет загрузку данных из БД (с помощью драйвера slaveDb).
     * Возвращает набор данных
     * @param $assocPkValue
     * @return array
     */
    protected function _load($assocPkValue)
    {
        $query = "SELECT * FROM ".$this->getTableName() 
               . " WHERE " . Lms_Item_Sql::combineToAnd($assocPkValue);
        $result = $this->_slaveDb->selectRow($query);

        if (!$result) {
            throw new Lms_Item_RecordNotExistsException(
                'No such record in data base'
            );
        }
        return array_change_key_case($result, CASE_LOWER);
        //return $result;
    }
    
    /**
     * Осущесвляет какие-либо действия перед вызовом _load
     *
     */
    protected function _preLoad() 
    {
        //virtual
    }
    
    /**
     * Осущесвляет какие-либо действия после вызова _load
     *
     */
    protected function _postLoad()
    {
        //virtual
    }
    
    
    /**
     * Сохранить данные.
     * Если запись не была сохранена, то:
     *      вызывает preInsert()
     *      вызывает _insert() (реализация сохранения данных из buffer)
     *      если данные были сохранены успешно, вызывает postInsert()
     * Если запись была сохранена, то:
     *      вызывает preUpdate()
     *      вызывает _update() (реализация обновления записи)
     *      вызывает postUpdate()
     */
    final public function save()
    {
        if (!$this->isSaved()) {
            $this->_preInsert();
            $this->_insert();
            if ($this->_scalarPkValue !== null) {
                $this->_postInsert();
            }
        } else {
            $this->_preUpdate();
            if ($this->_fullUpdate) {
                $this->_updateAll();
            } else {
                $this->_update();
            }
            $this->_postUpdate();
        }
        return $this;
    }
    
   
    
    /**
     * Возвращает истину, если текущая сущность сохранена.
     * Устанавливает _scalarPk, если сущность сохранена и $fixScalarPk = true
     *
     * @param bool $fixScalarPk �?справлять ли значение первичного ключа,
     *                          если сущность сохранена в Lms_Item_Store
     * @return bool
     */
    public function isSaved()
    {
        $saved = false;
        
        if ($this->_scalarPkValue !== null) {
            $saved = true;
        } else {
            $assocPkValue = $this->_getAssocPkValue(self::FROM_BUFFER);
            $scalarPkValue = Lms_Item_Scalar::scalarize($assocPkValue);
            if (Lms_Item_Store::has($this->getTableName(), $scalarPkValue)) {
                $saved = true;
                $this->_scalarPkValue = $scalarPkValue;
                $this->_fullUpdate = true;
            }
        } 
        
        return $saved;
    }
    

    /**
     * Возвращает истину если у сущности установлены
     * значения всех первичных ключей
     * @return bool
     */
    protected function _hasFullMultiKey()
    {
        $assocPkValue = $this->_getAssocPkValue(self::FROM_BUFFER);
        $aiFieldName = $this->getStruct()
                            ->getAiFieldName();
        if ($aiFieldName) {
            unset($assocPkValue[$aiFieldName]);
        }

        if (false === array_search(null, $assocPkValue)) {
            return true;
        }
        return false;
    }
    
    
    /*
     * Осуществляет вставку данных в БД (с помощью драйвера masterDb).
     */
    protected function _insert()
    {
        if ($this->getStruct()->hasAutoIncrement()) {
            $this->_buffer[$this->getStruct()->getAiFieldName()] = null;
        }
        
        if (!$this->_buffer) {//Если буфер пуст
            throw new Exception('There is no data to save');
        }
        $query = Lms_Item_Sql::insertStatement(
            $this->getTableName(), $this->_buffer
        );
        $insertResult = $this->_masterDb->query($query);
        if ($this->_isMultiKey()) {
            if ($this->getStruct()->hasAutoIncrement()) {
                $aiFieldName = $this->getStruct()->getAiFieldName();
                $this->_buffer[$aiFieldName] = $insertResult;
            }
        } else {
            $simplePk = $this->_getSimplePk();
            if (!$this->_hasPkValueInBuffer()) {
                $this->_buffer[$simplePk] = $insertResult;
            }
        }
        $assocPkValue = $this->_getAssocPkValue(self::FROM_BUFFER);
        $this->_scalarPkValue = Lms_Item_Scalar::scalarize($assocPkValue);

        Lms_Item_Store::setValues(
            $this->getTableName(), $this->_scalarPkValue,
            $this->_buffer
        );
        $this->_buffer = array();
    }
    
    /**
     * Копирует в текущий объект имена и значения первичных
     * ключей переданных объектов
     *
     */
    protected function _assignForeignKey($foreignObject)
    {
        $relation = Lms_Item_Relations::get(
            Lms_Item::getItemName($foreignObject),
            Lms_Item::getItemName($this)
        );
        if (!$relation) {
            throw new Lms_Item_Exception("Relation not found. See call stack.");
        }
        $value = $foreignObject->__getValue($relation['parent_key']);
        $this->__setValue($relation['foreign_key'], $value);
    }
    
    
    /**
     * Проверяет имеет ли текущая сущность множественный первичный ключ
     *
     * @return bool
     */
    protected function _isMultiKey()
    {
        $struct = $this->getStruct();
        return count($struct->getPk()) > 1 ? true : false;
    }
    
    /**
     * Проверяет имеет ли текущая сущность установленный извне первичный ключ
     * @return bool
     */
    protected function _hasPkValueInBuffer()
    {
        return isset($this->_buffer[$this->_getSimplePk()])? true : false;
    }
    
    /**
     * Осуществляет действия перед вставкой данных в бд
     */
    protected function _preInsert() 
    {
        //virtual
    }
    
    /**
     * Осуществляет действия после вставки данных в бд
     *
     */
    protected function _postInsert() 
    {
        //virtual
    }
    
    /**
     * Экономично обновляет измененные данные, незатронутые поля 
     * записи не обновляются "вхолостую"
     * 
     * Определяет измененные поля (список изменений хранится в Lms_Item_Store)
     * Lms_Item_Store::getChanges(getTableName(), _scalarPK)
     * Если изменений нету return 
     * Производит обновление (SQL UPDATE)
     * Очищает список изменений
     * Lms_Item_Store::flushChanges(getTableName(), _scalarPK)
     * @return $this
     */
    protected function  _update()
    {
        $changes = Lms_Item_Store::getChanges(
            $this->getTableName(), $this->_scalarPkValue
        );
        if (count($changes)) {
            $query = Lms_Item_Sql::updateStatement(
                $this->getTableName(), $changes,
                $this->_getAssocPkValue()
            );
            $this->_masterDb->query($query);
            Lms_Item_Store::flushChanges(
                $this->getTableName(), $this->_scalarPkValue
            );
        }
        return $this;
    }
    
    /**
     * Производит обновление как изменившихся так и не затронутых полей
     * Очищает буффер, снимает флаг о необходимости полного обновления
     */
    protected function _updateAll()
    {
        if (!$this->_buffer) {
            throw new Exception('There is no data to save');
        }
        $query = Lms_Item_Sql::updateStatement(
            $this->getTableName(), $this->_buffer,
            $this->_getAssocPkValue()
        );
        $this->_masterDb->query($query);
        Lms_Item_Store::setValues(
            $this->getTableName(), $this->_scalarPkValue,
            $this->_buffer
        );
        $this->_buffer = array();//Очищаем буфер
        $this->_fullUpdate = false;//Отключаем полное обновление
    }
    
    /**
     * Производит действия перед обновлением данных
     *
     */
    protected function _preUpdate() 
    {
        //virtual
    }
    
    /**
     * Производит действия после обновления данных
     *
     */
    protected function _postUpdate() 
    {
        //virtual
    }
    
    
    /**
     * Удаляет запись из БД и Store
     * @return $this
     */
    final public function delete()
    {
        $this->_preDelete();
        $query = Lms_Item_Sql::deleteStatement(
            $this->getTableName(),
            $this->_getAssocPkValue()
        );
        $this->_masterDb->query($query);
        Lms_Item_Store::delete($this->getTableName(), $this->_scalarPkValue);
        $this->_postDelete();
        return $this;
    }
    
    /**
     * Производит действия перед удалением данных
     *
     */
    protected function _preDelete()
    {
        //virtual
    }
    
    /**
     * Производит действия после удаления данных
     *
     */
    protected function _postDelete()
    {
        //virtual
    }
    
    
    /**
     * Добавляет зависимый объект и производит сохранение (если требуется)
     * текущего и зависимого объектов в необходимой последовательности
     * @param Lms_Item_Abstract $child
     * @return Lms_Item_Abstract
     */
    public function add()
    {
        $items = func_get_args();
        array_unshift($items, $this);
        $dependentItem = null;
        if (2 == count($items)) {
            //Добавлялся 1 объект
            $relation = Lms_Item_Relations::get(
                Lms_Item::getItemName($items[0]),
                Lms_Item::getItemName($items[1])
            );
            if ($relation) {
                //Объекты имеют прямую связь
                $dependentItem = $this->_popDependent($items);
            }
        }

        if (!$dependentItem) {
            //Объекты не имеет прямую связь, требуется линкатор
            $linkatorClass = Lms_Item::getLinkator($items);
            $dependentItem = Lms_Item::create($linkatorClass);
        }

        //сохраняем независимые объекты
        foreach ($items as $item) {
            if (!$item->isSaved()) {
                $item->save();
            }
            $dependentItem->_assignForeignKey($item);
        }
        if (!$dependentItem->_isMultiKey()
            || ($dependentItem->_isMultiKey()
                && $dependentItem->_hasFullMultiKey())
        ) {
            $dependentItem->save();
        }

        return $this;
    }

    private function _popDependent(&$items)
    {

        foreach ($items as $key => $item) {
            if ($item->_isMultiKey()) {//является мультикей
                $dependent = $item;
                unset($items[$key]);
                return $dependent;
            }
            //проверяем нет ли у данного объекта зависимостей с внешним ключом,
            //не совпадающим с первичным ключом
            foreach ($items as $subItemKey => $subItem) {
                if ($key != $subItemKey) {
                    //определяем связующий ключ
                    $relationKey = null;
                    $relation = Lms_Item_Relations::get(
                        Lms_Item::getItemName($item),
                        Lms_Item::getItemName($subItem)
                    );
                    if ($relation) {
                        $relationKey = $relation['parent_key'];
                    }
                    if ($relationKey
                        && ($relationKey != $item->_getSimplePk()
                            || ($relationKey == $item->_getSimplePk()
                                && !$item->getStruct()->hasAutoIncrement()
                            )
                        )
                    ) {
                        //если ключ связи есть и он не совпадает с первичным
                        //ключом данного объекта, то он является зависимым
                        $dependent = $item;
                        unset($items[$key]);
                        return $dependent;
                    }

                }
            }
        }

        throw new Lms_Item_Exception('Can not found depend item');
    }
    
    /**
     * Устанавливает значение свойства
     *
     * @param string $propetryName
     * @param unknown_type $value
     * @return Lms_Item_Abstract
     */
    protected function _set($propetryName, $value)
    {
        $fieldName = $this->_getFieldNameByProperty($propetryName);
        $this->__setValue($fieldName, $value);
        return $this;
    }
    
    /**
     * Получает значение свойства
     *
     * @param string $propetryName
     * @return unknown
     */
    protected function _get($propetryName)
    {
        $fieldName = $this->_getFieldNameByProperty($propetryName);
        return $this->__getValue($fieldName);
    }
    
    /**
     * Добавляет методом Lms_Item_Abstract::add() зависимый объект
     * с именем $itemName и устанавливает созданному объекту
     * $itemName свойство name равным $value
     *
     * @param string $itemName
     * @param string $value
     * @return Lms_Item_Abstract
     */
    protected function _add($itemName, $value)
    {
        $item = Lms_Item::create($itemName);
        $item->setName($value);
        $this->add($item);
        return $this;
    }
    
   
    /**
     * Create virtual get, set and add methods
     *
     * @param string $method
     * @param mixed $arguments
     * @return unknown
     */
    
    public function __call($method, $arguments = null)
    {      
        $operation = substr($method, 0, 3);
        $subject  = strtolower(substr($method, 3));
        switch ($operation) {
        case "get":
            return $this->_get($subject); 
            break;        
        case "set":
            return $this->_set($subject, $arguments[0]);
            break;
        case "add":
            return $this->_add($subject, $arguments[0]);
            break;
        default:
            throw new Lms_Item_Exception(
                "Unsupported method: $method; operation: $operation"
            );
        }
    }
    
    /**
     * Сохраняет значение поля $fieldName
     *
     * @param String $fieldName
     * @param unknown_type $value
     */
    public function __setValue($fieldName, $value)
    {
        if ($this->_scalarPkValue === null) {
            $this->_buffer[$fieldName] = $value;
        } else {
            Lms_Item_Store::setValue(
                $this->getTableName(), $this->_scalarPkValue,
                $fieldName, $value, false, false
            );
        }
    }
    
    /**
     * Возвращает значение поля $fieldName
     * 
     * @param string $fieldName
     * @return mixed
     */
    public function __getValue($fieldName)
    {
        if ($this->_scalarPkValue === null) {
            if (array_key_exists($fieldName, (array)$this->_buffer)) {
                return $this->_buffer[$fieldName];
            } else {
            /**
             * @todo 
             * Надо ли возвращать значение по дефолту??
             */
                return null;
            }
        } else {
            try {
                return Lms_Item_Store::getValue(
                    $this->getTableName(), $this->_scalarPkValue,
                    $fieldName
                );
            } catch (Lms_Item_Store_FieldValueNotExistsException $e){
                $this->load();
                return Lms_Item_Store::getValue(
                    $this->getTableName(), $this->_scalarPkValue,
                    $fieldName
                );
            }
            
        }
    }
    
    /**
     * Возвращает элементы/аттрибуты указанные в пути xpath
     * 
     * @param string $xpath
     * @return mixed
     */

    public function getChilds($xpath)
    {
        if (!$xpath) {
            return $this;
        }
        //разбор запроса
        $xpaths = explode("/", $xpath, 2);
        $xpathCurrentStep = array_shift($xpaths);
        $xpathNextStep = count($xpaths)? array_shift($xpaths) : null;
        $predicat = array();
        if (preg_match('{^(.*?)\[(.*?)\]$}', $xpathCurrentStep, $matches)) {
            $xpathCurrentStep = $matches[1];
            $predicat[] = $matches[2];
        }

        //запрашивается поле
        if (0===strpos($xpathCurrentStep, '@')) {
            $xpathCurrentStep = substr($xpathCurrentStep, 1);
            return call_user_func(array($this, "get$xpathCurrentStep"));
        }

        //запрашивается зависимый объект подразумевая использование линкатора
        $thisItemName = Lms_Item::getItemName($this);
        $relation = Lms_Item_Relations::get($thisItemName, $xpathCurrentStep);
        if (!$relation) {
            $linkator = Lms_Item::getLinkator($thisItemName, $xpathCurrentStep);
            return $this->getChilds("$linkator/$xpath");
        }

        //запрашивается зависимый объект имеющий прямую связь
        $subTableName = Lms_Item::getTableName($xpathCurrentStep);
        $foreignKey = $relation['foreign_key'];
        $parentKeyValue = $this->__getValue($relation['parent_key']);
        Lms_Item::initStructure(Lms_Item::getClassName($xpathCurrentStep));
        if (Lms_Item_Relations::ONE==$relation['type']) {
            //связь 1 к 1
            if (Lms_Item::getSimplePk($xpathCurrentStep)==$foreignKey) {
                try {
                    $item = Lms_Item::create($xpathCurrentStep, $parentKeyValue);
                    return $item->getChilds($xpathNextStep);
                } catch (Lms_Item_RecordNotExistsException $e) {
                    return null;
                }
            } else {
                $this->completeIndexValues(
                    $xpathCurrentStep, $foreignKey, $parentKeyValue
                );
                $subPkValues = Lms_Item_Store::getIndexedValues(
                    $subTableName, $foreignKey, $parentKeyValue
                );
                if (count($subPkValues)) {
                    $subPkValue = reset($subPkValues);
                    try {
                        $item = Lms_Item::create($xpathCurrentStep, $subPkValue);
                        return $item->getChilds($xpathNextStep);
                    } catch (Lms_Item_RecordNotExistsException $e) {
                        return null;
                    }
                }
            }
        } else {
            //связь 1 ко многим
            $this->completeIndexValues(
                $xpathCurrentStep, $foreignKey, $parentKeyValue
            );
            $result = array();
            $subPkValues = Lms_Item_Store::getIndexedValues(
                $subTableName, $foreignKey, $parentKeyValue
            );
            foreach ($subPkValues as $subPkValue) {
                $item = Lms_Item::create($xpathCurrentStep, $subPkValue);
                $result[] = $item->getChilds($xpathNextStep);
            }
            $this->performHandlers($result, $predicat);
            return $result;
        }
    }

    private function completeIndexValues($itemName, $indexName, $indexValue)
    {
        $tableName = Lms_Item::getTableName($itemName);
        $status = Lms_Item_Store::getIndexStatus(
            $tableName, $indexName, $indexValue
        );
        if ($status != Lms_Item_Struct::FULL_INDEX) {
            $this->loadRelated($itemName, $indexName);
            Lms_Item_Store::setIndexStatus(
                $tableName, $indexName,
                $indexValue, Lms_Item_Struct::FULL_INDEX
            );
        }
    }

    private function performHandlers(&$items, $handlers)
    {
        if (count($handlers)) {
            $currentHandler = array_shift($handlers);
            $isSortHandler = preg_match(
                '{sortby\((.*?),?(desc|asc)?\)$}i', $currentHandler, $matches
            );
            if ($isSortHandler) {
                $sortField = trim($matches[1], '"\' ');
                if (isset($matches[2])) {
                    switch (strtolower($matches[2])) {
                    case 'desc':
                        $direction = SORT_DESC;
                        break;
                    case 'asc':
                    default:
                        $direction = SORT_ASC;
                    }
                } else {
                    $direction = SORT_ASC;
                }
                $sortArray = array();
                foreach ($items as $item) {
                    $sortArray[] = call_user_func(
                        array($item, "get$sortField")
                    );
                }
                array_multisort($sortArray, $direction, $items);
            }
            $isFilterHandler = preg_match(
                '{([^=]+?)=(.*?)$}i', $currentHandler, $matches
            );
            if ($isFilterHandler) {
                $filterField = trim($matches[1], '"\' ');
                $filterValue = trim($matches[2], '"\' ');
                $result = array();
                foreach ($items as $item) {
                    $value = call_user_func(array($item, "get$filterField"));
                    if ($filterValue==$value) {
                        $result[] = $item;
                    }
                }
                $items = $result;
            }
        }
    }


    /**
     * Загружает зависимости $subItemName для данной сущности
     * @param $subItemName
     * return bool
     */
    function loadRelated($subItemName, $fk = null)
    {
        if (method_exists($this, "load$subItemName")) {
            return call_user_func(array($this, "load$subItemName"));
        } else {
            $tableName = Lms_Item::getTableName($subItemName);
            if ($fk === null) {
                $fk = $this->_getSimplePk();
            }
            $fkValue = $this->__getValue($fk);
            $rows = $this->_slaveDb->select(
                "SELECT * FROM $tableName WHERE ?#=?",
                $fk, $fkValue
            );
            if ($rows) {
                $subItemPk = Lms_Item::getStruct($subItemName)->getPk();
                foreach ($rows as $row) {
                    $scalarPkValue = Lms_Item_Scalar::extractScalarPkValue(
                        $row, $subItemPk
                    );
                    Lms_Item_Store::setValues($tableName, $scalarPkValue, $row, true);
                }
                Lms_Item_Store::rebuildIndex($tableName);
            }
        }
    }
    
    /**
     * Возвращает значение скаляризованного первичного ключа
     *
     * @return int/string
     */
    public function getId()
    {
        return $this->_scalarPkValue;
    }
    
    public function setScalarPkValue($value)
    {
        $this->_scalarPkValue = $value;
    }
    
    public function getStruct()
    {
        return Lms_Item_Store::getStruct($this->getTableName());
    }
    
    public function storeData($data)
    {
        $this->_buffer = $data;
        $this->_scalarPkValue = Lms_Item_Scalar::scalarize(
            $this->_getAssocPkValue(self::FROM_BUFFER)
        );
        Lms_Item_Store::setValues(
            $this->getTableName(), $this->_scalarPkValue, $data
        );
    }
    
    public static function rowsToItems($rows, $itemName = null)
    {
        if ($rows) {
            if (!$itemName) {
                $itemName = Lms_Item::getCallingItemName();
            }
            $items = array();
            Lms_Item::initStructure(Lms_Item::getClassName($itemName));
            $simplePk = Lms_Item::getSimplePk($itemName);
            $tableName = Lms_Item::getTableName($itemName);
            foreach ($rows as $row) {
                Lms_Item_Store::setValues(
                    $tableName,
                    $row[$simplePk], 
                    $row, 
                    false
                ); 
                $items[] = Lms_Item::create($itemName, $row[$simplePk]);
            }
            Lms_Item_Store::rebuildIndex($tableName);
            return $items;
        }
        return array();
    }

    public static function rowToItem($row, $itemName = null)
    {
        if (!$row) {
            return null;
        }
        if (!$itemName) {
            $itemName = Lms_Item::getCallingItemName();
        }
        Lms_Item::initStructure(Lms_Item::getClassName($itemName));
        $simplePk = Lms_Item::getSimplePk($itemName);
        $tableName = Lms_Item::getTableName($itemName);
        
        Lms_Item_Store::setValues(
            $tableName,
            $row[$simplePk], 
            $row, 
            true
        ); 
        return Lms_Item::create($itemName, $row[$simplePk]);
    }
}