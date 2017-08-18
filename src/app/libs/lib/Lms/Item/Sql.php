<?php
class Lms_Item_Sql
{
    private static $_db;

    public static function setDb(DbSimple_Database $db)
    {
        self::$_db = $db;
    }

    public static function combineToAnd(array $assocArray, $db = null)
    {
        if ($db == null) {
            $db = self::$_db;
        }
        $statements = array();
        foreach ($assocArray as $key => $value) {
            $statements[] = $db->escape($key, true)
                          . " = " . $db->escape($value);
        }
        return implode(' AND ', $statements);
    }

    public static function deleteStatement($tableName, $assocPkValue, $db = null)
    {
        if ($db == null) {
            $db = self::$_db;
        }
        $where = Lms_Item_Sql::combineToAnd($assocPkValue);
        $query = "DELETE FROM $tableName WHERE $where LIMIT 1";
        return $query;
    }

    public static function insertStatement($tableName, $data, $db = null)
    {
        if ($db == null) {
            $db = self::$_db;
        }
        $sets = self::setsStatement($tableName, $data, $db);
        $query = "INSERT INTO $tableName SET $sets";
        return $query;
    }


    public static function updateStatement(
        $tableName, $data, $assocPkValue, $db = null
    )
    {
        if ($db == null) {
            $db = self::$_db;
        }
        $sets = self::setsStatement($tableName, $data, $db);
        $where = Lms_Item_Sql::combineToAnd($assocPkValue);
        $query = "UPDATE $tableName SET $sets WHERE $where LIMIT 1";
        return $query;
    }

    public static function setsStatement($tableName, $data, $db = null)
    {
        if ($db == null) {
            $db = self::$_db;
        }
        $struct = Lms_Item_Store::getStruct($tableName);
        $columns = $struct->getColumns();
        $sets = array();
        foreach ($columns as $fieldName => $meta) {
            if (array_key_exists($fieldName, $data)) {
                $statement = $db->escape($fieldName, true) . '=';
                if ($data[$fieldName] === null) {
                    $statement .= 'NULL';
                } else {
                    switch ($meta['escape']) {
                        case Lms_Item_Struct::ESCAPE_AS_INT:
                            $statement .= intval($data[$fieldName]);
                            break;
                        case Lms_Item_Struct::ESCAPE_AS_FLOAT :
                            $statement .= str_replace(
                                ',', '.', floatval($data[$fieldName])
                            );
                            break;
                        case Lms_Item_Struct::ESCAPE_AS_STRING:
                        default:
                            if (strlen($data[$fieldName])>255) {
                                $statement .= "X'" . bin2hex($data[$fieldName]) . "'";
                            } else {
                                $statement .= $db->escape($data[$fieldName]);
                            }
                            break;
                    }
                }
                $sets[] = $statement;
            }
        }
        return implode(', ', $sets);
    }
}
