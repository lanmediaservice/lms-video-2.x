<?php
/**
 * LMS Library
 * 
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov <webtota@gmail.com>
 * @version $Id: Scalar.php 395 2010-03-21 21:19:28Z macondos $
 * @copyright 2008-2009
 * @package Lms_Item 
 */

class Lms_Item_Scalar
{
    
    /**
     * Приводит входящий первичный ключ к строке
     * @param mixed $pkValue
     * @return int/string
     */
    static public function scalarize($pkValue)
    {
        if (is_scalar($pkValue)) {
            return $pkValue;
        }
        if (is_array($pkValue) && count($pkValue)) {
            if (1==count($pkValue)) {
                return reset($pkValue);
            } else {
                ksort($pkValue);
                return implode('|', $pkValue);
            }
        }
        throw new Lms_Item_Exception(
            "$pkValue must be scalar value or array"
            . " of 1 element or associate array"
        );
    }
    
    static public function extractScalarPkValue($row, $fields)
    {
        sort($fields);
        $scalarPkValue = null;
        $first = true;
        foreach ($fields as $fieldName) {
            if (!$first) {
                $scalarPkValue .= '|';
            }
            $scalarPkValue .= $row[$fieldName];
            $first = false;
        }
        return $scalarPkValue;
    }

    static public function descalarize($scalarPkValue, $fieldNames)
    {
        if (!is_scalar($scalarPkValue)) {
            throw new Lms_Item_Exception("$pkValue must be scalar value");
        }
        sort($fieldNames);
        $values = explode("|", $scalarPkValue);
        $assocPkValue = array_combine($fieldNames, $values);
        return $assocPkValue;
    }

}
