<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: Enumerator.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007-2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov<webtota@gmail.com>
 * @package Modular
 */
define('LMS_ENUMERATOR_FAIL', log(0)); 
/**
 * @package Enumerator
 */
class Lms_Enumerator
{
    private $_data;
    private $_currentDataIndex = 0;
    private $_filter = false;

    const FAIL = LMS_ENUMERATOR_FAIL;
    
    /**
     * Class constructor
     *
     * @param unknown_type $initData
     */
    public function __construct($initData = array())
    {
        $this->init($initData);
        
    }
    /**
     * Initialize params
     *
     * @param array $initData
     */
    public function init($initData = array())
    {
        $this->_data = $initData;
        $this->_currentDataIndex = 0;
    }
    /**
     * Reset internal array counter to 0
     *
     */
    function reset()
    {
        $this->_currentDataIndex = 0;
    }

    /**
     * Count number of items in data array 
     *
     * @return int
     */
    public function getCount()
    {
        return count($this->_data);
    }

    /**
     * Get first element of data array
     *
     * @return mixed
     */
    public function getFirst()
    {
        $this->_currentDataIndex = 0;
        $current = self::FAIL;
        while (($this->getCount() - $this->_currentDataIndex) > 0) {
            if ($this->onFilter($this->getCurrent())) {
                   $current = $this->getCurrent();
                   break;
            }	
            $this->_currentDataIndex++;
        } 
        if ($current === self::FAIL) {
            $this->reset();
        }
        return $current;
    }

    /**
     * get next item of data array
     *
     * @return mixed
     */
    public function getNext()
    {
        $current = self::FAIL ; 
        while (($this->getCount() - $this->_currentDataIndex)>1) {
            $this->_currentDataIndex++;
            if ($this->onFilter($this->getCurrent())) {
                $current = $this->getCurrent();
                break;
            }
        } 
        if ($current === self::FAIL) {
            $this->reset();
        }
        return $current;
    }

    /**
     * get each item of data array. It Used with While loop 
     *
     * @return mixed
     */
    public function getEach()
    {
        $current = self::FAIL ;
        while (($this->getCount() - $this->_currentDataIndex) > 0 ) {
            if ($this->onFilter($this->getCurrent())) { 
                $current = $this->getCurrent();
                $this->_currentDataIndex++;
                break;
            }
            $this->_currentDataIndex++;
        } 
        if ($current === self::FAIL) {
            $this->reset();
        } 
        return $current;
    }

    /**
     * get previous item of data array
     *
     * @return mixed
     */
    public function getPrev($filter = false)
    {
        $current = self::FAIL; 
        while ($this->_currentDataIndex > 0) {
            $this->_currentDataIndex--;
            if ($this->onFilter($this->getCurrent())) {    
                $current = $this->getCurrent();
                break;
            }	
        }
        if ($current === self::FAIL) {
            $this->reset();
        } 
        return $current;
    }

    /**
     * get last item of data array
     *
     * @return mixed
     */
    public function getLast()
    {
        $current = self::FAIL; 
        $this->_currentDataIndex = $this->getCount()-1;
        while ($this->_currentDataIndex > 0) {
            if ($this->onFilter($this->getCurrent())) {    
                $current = $this->getCurrent();
                break;
            }	
            $this->_currentDataIndex--;
        } 
        if ($current === self::FAIL) {
            $this->reset();
        }
        return $current;
    }

    /**
     * get current item 
     *
     * @return mixed
     */
    private function getCurrent()
    {
        return $this->_data[$this->_currentDataIndex];
    }

    /**
     * add item to data array
     *
     * @param mixed $item
     */
    public function add($item)
    {
        $this->_data[] = $item;
    }
    
    /**
     * add array of items to data array
     *
     * @param array $array
     */
    public function addArray($array)
    {
        foreach ($array as $item) {
            $this->_data[] = $item;
        }
    }
    /**
     * add item to data array with filter using
     *
     * @param mixed $item
     */
    public function filteringAdd($item)
    {
        if ($this->onFilter($filter, $item)) {
            $this->_data[] = $item;
        }
    }
    /**
     * blank data array
     *
     */
    public function blankData()
    {
        $this->_data = array();
    }
    /**
     * set filter
     *
     * @param mixed $filter
     * @todo Возможность устанавливать комбинацию фильтров
     */
    public function setFilter()
    {
        foreach (func_get_args() as $filter) {
            $this->_filter[] = $filter;
        }
    }

    /**
     * Validate data getted by the param using setted filter
     *
     * @param mixed $value
     * @return bool
     */
    private function onFilter($value)
    {
        if (!$this->_filter) {//фильтр не установлен
            return true;
        }
        foreach ($this->_filter as $filter) {
            if (!is_array($filter)) {
               //фильтром является стандартная функция php
               if (is_callable($filter)) {
                   $result = call_user_func($filter, $value);
               } else {
                    throw new Lms_Exception(
                        'There is no such filter: ' . $filter
                    );
               }
            } else {
                $filterClass = "Lms_Validator_".ucfirst($filter[0]);
                $filterClassMethod = $filter[1];
                if (isset($filter[2])) {
                    $filterParams = array_slice($filter, 2);
                } else {
                    $filterParams = false;
                }
                if (!class_exists($filterClass, true)) {
                    throw new Lms_Exception(
                        "Class $filterClass for Validation does not exists."
                    );
                }
                if (!is_callable(array($filterClass, $filterClassMethod))) {
                    //фильтр определён в классе наследуемом от Lms_Validator
                    throw new Lms_Exception(
                        "Method -  $filterClassMethod of Class for Validation "
                        . "- $filterClass does not exists."
                    );
                }
                if ($filterParams) {
                    array_unshift($filterParams, $value);
                    $result = call_user_func_array(
                        array($filterClass, $filterClassMethod), $filterParams
                    );
                } else {
                    $result = call_user_func(
                        array($filterClass, $filterClassMethod), $value
                    );
                }
            }
            if (!$result) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Reset the filters array
     *
     */
    public function dropFilter()
    {
        $this->_filter = false;
    }

}