<?php
class Lms_Item_Struct_Generator
{
    static private $_namespace = 'Lms_Item_Struct_';
    static private $_storagePath;
    
    static public function setStoragePath($storagePath)
    {
        self::$_storagePath = $storagePath;
    }
    
    static public function getTableClassName($tableName)
    {
        $camelCaseTableName = ucfirst(
            preg_replace(
                '{_([a-zA-Z])}e', "strtoupper('\\1')",
                str_replace(
                    '?_', '', 
                    preg_replace('{^[^\.]*\.}', '', $tableName)
                )
            )
        );
        return self::$_namespace . $camelCaseTableName;
    }

    static public function generate($tableClassName, $columns)
    {
        if (self::$_storagePath) {
            $structure = var_export($columns, true);
            $classDefinition = "<?php"
                             . "\nclass " . $tableClassName
                             . "\n{"
                             . "\n    static public function getColumns()"
                             . "\n    {"
                             . "\n        return $structure;"
                             . "\n    }"
                             . "\n}";
            $tableFilename = str_replace(self::$_namespace, '', $tableClassName)
                           . '.php';
            file_put_contents(
                self::$_storagePath . "/" . $tableFilename,
                $classDefinition
            );
        }
    }
    
}