<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Cli
 *
 * @author macondos
 */
class Lms_Api_Cli {

    public static function getModule($module)
    {
        $module = "Lms_Api_Cli_" . ucfirst(preg_replace('{\W}', '', strtolower($module)));
        if (!class_exists($module, true)) {
            throw new Lms_Exception("CLI API module '$module' not found!");
        }
        return $module;
    }

    public static function exec($module, $method)
    {
        $module = self::getModule($module);
        if (!method_exists($module, $method)) {
            throw new Lms_Exception("CLI API method '$method' in module '$module' not found!");
        }
        return $module::$method();
    }
    
    public static function showUsageAndExit($options, $exitCode = 0)
    {
        echo $options->getUsageMessage();
        exit($exitCode);
    }

    public static function output($str)
    {
        $encoding = null;
        if (Lms_Application::isWindows()) {
            $encoding = 'CP866';
        } elseif ($lang = getenv('LANG')) {
            if (preg_match('{\.(\S+)$}', $lang, $matches)) {
                $encoding = $matches[1];
            }
        }
        if (!empty($encoding) && $encoding!='CP1251') {
            return Lms_Translate::translate('CP1251', $encoding, $str);
        } else {
            return $str;
        }
    }

}
