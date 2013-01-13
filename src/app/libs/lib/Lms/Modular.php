<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: Modular.php 469 2010-07-26 10:17:08Z macondos $
 * @copyright 2007-2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package Lms
 */

class Lms_Modular {
    static public function loadModule($moduleName, $useUppercase = false){
        $moduleSafeName = ($useUppercase)? preg_replace("{[^a-zA-Z0-9_]}", "", $moduleName) : preg_replace("{[^a-z0-9_]}", "", $moduleName);
        $trace = debug_backtrace();
        $className = $trace[1]['class'] . '_' . ucfirst($moduleSafeName);
        return class_exists($className, true)? $className : false;
    }
}