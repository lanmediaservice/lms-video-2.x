<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: Timer.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package Lms
 */


class Lms_Timer {
    var $firstTime;
    var $sumTime;
    var $count;
    var $started;
    
    function __construct(){
        $this->sumTime = 0;
        $this->count = 0;
        $this->started = false;
    }
        
    function start(){
        if (!$this->started){
            $this->firstTime = microtime(true);
            $this->started = true;
        }
    }

    function stop(){
        if ($this->started){
            $this->started = false;
            $this->sumTime += $this->_getElapsedTime($this->firstTime, microtime(true));
            $this->count++;
        }
    }

    function cancel(){
        $this->started = false;
    }

    function reset(){
        $this->sumTime = 0;
        $this->count = 0;
    }

    function getSumTime(){
        return $this->sumTime;
    }

    function getCount(){
        return $this->count;
    }

    function checkPoint()
    {
        $deltaTime = $this->_getElapsedTime($this->firstTime, microtime(true));
        $this->firstTime = microtime(true);
        return $deltaTime;
    }

    function _getElapsedTime($timeStart, $timeEnd)
    {
        return round($timeEnd - $timeStart, 5);
    }
}

?>