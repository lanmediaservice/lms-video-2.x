<?php

 /**
 * LMS Library
 * 
 * @copyright 2006-2009 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov <webtota@gmail.com>
 * @version $Id: Db.php 447 2010-07-03 10:32:41Z macondos $
 */

/**
 * Статический класс для работы с базой данных
 *
 *
 * @copyright 2006-2009 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 */
 


class Lms_Db
{
    /**
     * Массив данных для подключения к различным бд
     *
     * @var array
     */
    private static $_configs = array();
    /**
     * Массив экземпляров класса DbSimple_Generic.
     *
     * @var array
     */
    private static $_instances = array();

    /**
     * Добавляет реквизиты для подключения к бд
     *
     * @param string $dbAlias Псевдоним бд
     * @param string $connectUri Строка с реквизитами подключения
     * @param string $initSQL SQL-запрос выполняемый при подключении
     * @param int|bool $debug Флаг выводить ли информацию об отладке
     */
    public static function addDb(
        $dbAlias, $connectUri, $initSQL = false, $debug = false
    )
    {
        self::$_configs[$dbAlias]['connectUri'] = $connectUri;
        self::$_configs[$dbAlias]['initSQL'] = $initSQL;
        self::$_configs[$dbAlias]['debug'] = $debug;
    }
    
    public static function getConfig($dbAlias, $param)
    {
        return self::$_configs[$dbAlias][$param];
    }

    public static function setConfig($dbAlias, $param, $value)
    {
        self::$_configs[$dbAlias][$param] = $value;
    }

    /**
     * Возвращает экземпляр класса для подключения к бд
     *
     * @param string  $dbAlias
     * @return DbSimple_Generic
     */
    public static function get($dbAlias)
    {
        if (!isset(self::$_instances[$dbAlias])) {
            $db = DbSimple_Generic::connect(
                self::$_configs[$dbAlias]['connectUri']
            );
            if (self::$_configs[$dbAlias]['initSQL']) {
                $db->query(self::$_configs[$dbAlias]['initSQL']);
            }
            $db->setErrorHandler(array(__CLASS__,'databaseErrorHandler'));
            $db->addIgnoreInTrace(__CLASS__ . '::databaseErrorHandler');
            if (self::$_configs[$dbAlias]['debug']) {
                $db->setLogger(array(__CLASS__,'databaseLogger'));
                $db->addIgnoreInTrace(__CLASS__ . '::databaseLogger');
            }

            self::$_instances[$dbAlias] = $db;
        }
        return self::$_instances[$dbAlias];
    }

    /**
     * Возвращает истину если экземпляр класса DbSimple существует
     *
     * @param string $dbAlias
     * @return bool
     */
    public static function isInstanciated($dbAlias)
    {
        return isset(self::$_instances[$dbAlias])? true : false;
    }    

    /**
    * Db::deleteInstance()
    * Not recommended for using 
    * @return void
    * @access public
    */
    public static function deleteInstance($dbAlias)
    {
        unset(self::$_instances[$dbAlias]);
    }
    /**
     * Обработчик SQL - ошибок
     *
     * @param string $message
     * @param array $info
     */
    public static function databaseErrorHandler($message, $info)
    {
        if (!error_reporting()) {
            return;
        }
        throw new Lms_Db_Exception(
            "SQL Error: $message\n" . print_r($info, true)
        );
    }
    
    /**
     * Журналирует сообщения
     *
     * @param string $db
     * @param string $sql
     */
    public static function databaseLogger($db, $sql)
    {    
        $caller = $db->findLibraryCaller();
        Lms_Debug::debug($sql . " -- {$caller['file']}, {$caller['line']} ");
    }
    
}
