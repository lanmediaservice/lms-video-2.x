<?php
/**
 * Функции для работы с массивами
 * 
 *
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Array.php 350 2010-02-14 19:28:36Z macondos $
 * @category Lms
 * @package Lms_Utils
 */

/**
 * @category Lms
 * @package Lms_Utils
 */
class Lms_Array
{
    
    /**
     * Call member function on each array's item
     *
     * @param unknown_type $memberFunction
     * @param unknown_type $array
     * @return unknown
     */
    
    static function mapObjects($memberFunction, array $array)
    {
        $values = array();
        if (is_string($memberFunction) && is_array($array)) {
            $callback = create_function(
                '$e',
                'return call_user_func(array($e, "' . $memberFunction .'"));'
            );
            $values = array_map($callback, $array);
        }
    
        return $values;
    }
    /**
     * Returns specific key/column from an array of objects.
     *
     * @param string/int $key
     * @param array $array
     * @return array
     */
    static function pluck($key, array $array)
    {
        if (is_array($key) || !is_array($array)) {
            return array();
        }
        $func = create_function(
            '$e',
            'return is_array($e) && '
            . 'array_key_exists("' . $key . '",$e) ? $e["' . $key . '"] : null;'
        );
        return array_map($func, $array);
    }
    
    /**
     * Возвращает массив с удаленными пустыми значениями
     *
     * @param array $data
     * @return array
     */
    static function filterBlank($data)
    {
        if (!is_array($data)) return array();
        $callback = create_function(
            '$e',
            'return strlen(strval(trim($e)))? true : false;'
        );
        return array_filter($data, $callback);
    }
    /**
     * Возвращает из многомерного массива аля
     * $data(0=>(xxx=>1, yyy=2), 1=>(xxx=>3, yyy=4))
     * одну колонку col($data, 'yyy') -> array(2, 4)
     * 
     * @param array $inputArray
     * @param string $colName
     * @return array
     */
    static public function col($inputArray, $colName)
    {
        if (!is_array($inputArray)) return array();
        $outputArray = array();
        foreach ($inputArray as $row) {
            $outputArray[] = $row[$colName];
        }
        return $outputArray;
    }

    /**
     * Возвращает массив, в которым рекурсивно ко всем элементам применена
     * функция удаления слешей stripslashes
     * @param array|mixed $var
     * @return array|mixed
     */
    public static function recursiveStripSlashes($var)
    {
        if (is_array($var)) {
            return array_map(array(__CLASS__, 'recursiveStripSlashes'), $var);
        } else {
            return stripslashes($var);
        }
    }
    
    /**
    * To illustrate, here’s an example script will output either A, B, or C with probabilities of 15%, 35% and 50% respectively :
    * $values = array('A', 'B', 'C');
    * $weights = array(3, 7, 10);
    * echo Lms_Array::weightedRandom($values, $weights);
    * 
    * @param mixed $values
    * @param mixed $weights
    * @return mixed
    */
    public static function weightedRandom($values, $weights)
    { 
        $count = count($values); 
        $i = 0; 
        $n = 0; 
        $num = mt_rand(0, array_sum($weights)); 
        while($i < $count){
            $n += $weights[$i]; 
            if($n >= $num){
                break; 
            }
            $i++; 
        } 
        return $values[$i]; 
    }
    
    private static function insensitiveCompare($a, $b)
    {
        return strtolower($a)>strtolower($b);
    }
    
    public static function iksort(&$array)
    {
        uksort($array, "Lms_Array::insensitiveCompare");     
    }
}
