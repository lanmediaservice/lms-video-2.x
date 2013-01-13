<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: Timers.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package Lms
 */

class Lms_Timers {
    private static $timers = array();

    public static function start($name){
        $timer = self::_getTimer($name);
        return $timer->start();
    }    

    public static function stop($name){
        $timer = self::_getTimer($name);
        return $timer->stop();
    }    

    public static function cancel($name){
        $timer = self::_getTimer($name);
        return $timer->cancel();
    }    

    public static function reset($name){
        $timer = self::_getTimer($name);
        return $timer->reset();
    }    

    public static function getSumTime($name){
        $timer = self::_getTimer($name);
        return $timer->getSumTime();
    }    
    
    public static function getCount($name){
        $timer = self::_getTimer($name);
        return $timer->getCount();
    }    
    
    public static function getTimers(){
        return self::$timers;
    }    

    public static function getTimersNames(){
        return array_keys(self::$timers);
    }    

    private static function &_getTimer($name){
        if (!isset(self::$timers[$name])) {
            self::$timers[$name] = new Lms_Timer();
        }
        return self::$timers[$name];
    }    
    
}
