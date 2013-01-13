<?php
/**
 * Контроллер запросов к Lms_Api
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Controller.php 590 2010-12-13 17:57:35Z macondos $
 * @package Api
 */

/** 
 * @package Api
 */
class Lms_Api_Controller extends Lms_Singleton
{
    private $_actions = array();
    private $_format;
    private $_lang;

    private function _init()
    {
        $this->_actions = array();
        $this->_format = 'debug';
    }

    public function __construct()
    {
        $this->_init();
    }
    
    public static function getInstance($class = null)
    {
        return parent::getInstance(__CLASS__);
    }            
    
    public function analyzeHttpRequest()
    {
        if (isset($_GET['lang'])) {
            $this->_lang = $_GET['lang'];
        }
        if (isset($_GET['format']) && ($_GET['format']=='ajax')) {
            // Correct global variables
            new Lms_CorrectSuperGlobal('CP1251');
        }

        $this->_actions = array();
        $getAndPostArray = array_merge($_GET, $_POST);
        if (isset($getAndPostArray['action'])
            && is_array($getAndPostArray['action'])
        ) {
            foreach ($getAndPostArray['action'] as $actionNum => $actionName) {
                $this->_actions[$actionNum]['action'] = $actionName;
            }            
            foreach ($getAndPostArray as $paramName => $paramValues) {
                if (is_array($paramValues)) {
                    foreach ($paramValues as $actionNum => $value) {
                        if (isset($this->_actions[$actionNum])) {
                            $this->_actions[$actionNum][$paramName] = $value;
                        }
                    }
                }
            }
        }
        if (isset($getAndPostArray['format'])) {
            $this->_format = $getAndPostArray['format'];
        }
        return $this->_actions;
    }

    public function getActionsAsString()
    {
        $a = array();
        foreach ($this->_actions as $actionNum => $actionParams) {
            $a[] = $actionNum . ':' . implode('/', array_values($actionParams));
        }
        return implode(" ", $a);
    }

    public function exec()
    {
        $responses = array();
        foreach ($this->_actions as $actionNum => $actionParams) {
            Lms_Debug::debug('Action: ' . str_replace("\n", "", print_r($actionParams, 1)));
            $actionName = $actionParams['action'];
            list($serverName, $method) = explode('.', $actionName);
            $serverClass = 'Lms_Api_Server_' . ucfirst($serverName);
            if (is_callable(array($serverClass, $method))) {
                $responses[$actionNum] = call_user_func(
                    array($serverClass, $method), $actionParams
                );
            } else if (class_exists($serverClass, true)) {
                $responses[$actionNum] = new Lms_Api_Response(
                    500, 'Unknown API Service method'
                );
            } else {
                $responses[$actionNum] = new Lms_Api_Response(
                    500, 'Unknown API Service'
                );
            }
        }
        $formatterClass = 'Lms_Api_Formatter_' . ucfirst($this->_format);
        $formatter = new $formatterClass();
        $formatter->setUp();
        foreach ($responses as $actionNum => $response) {
            echo $formatter->format($actionNum, $response);
        }
    }
    
    public function getLang()
    {
        return $this->_lang;
    }
}