<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: Singleton.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package Lms
 */

class Lms_Singleton
{
    private static $instances = array();
 
    protected function __construct(){}
 
    public static function getInstance ($class = null)
    {
        if (is_null($class)) {
            throw new Exception("Missing class information");
        }
        if (!array_key_exists($class, self::$instances)) {
            self::$instances[$class] = new $class;
        }
        return self::$instances[$class];
    }

    public static function isInstanciated($class = null)
    {
        return array_key_exists($class, self::$instances);
    }

    public static function deleteInstance( $class = null )
    {
        if (array_key_exists($class, self::$instances)) {
            unset(self::$instances[$class]);
        }
    }

    public final function __clone()
    {
        trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
    }
}

?>