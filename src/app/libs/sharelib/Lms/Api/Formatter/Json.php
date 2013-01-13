<?php
/**
 * Форматировщик API-ответов в формат JSON
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Php.php 291 2009-12-28 12:55:20Z macondos $
 * @package Api
 */

/** 
 * @package Api
 */
class Lms_Api_Formatter_Json implements Lms_Api_Formatter_Interface
{
    public static $encoding = 'UTF-8';

    public static function setEncoding($encoding)
    {
        self::$encoding = $encoding;
    }

    public function setUp()
    {
        ob_start(array(&$this, "_obHandler"));
        // Check if headers are already sent (see Content-Type library usage).
        // If true - generate a debug message and exit.
        $file = $line = null;
        $result = version_compare(PHP_VERSION, "4.3.0") < 0? headers_sent() : headers_sent($file, $line);
        if ($result) {
            trigger_error(
                "HTTP headers are already sent" . ($line !== null? " in $file on line $line" : " somewhere in the script") . ". "
                . "Possibly you have an extra space (or a newline) before the first line of the script or any library. ",
                E_USER_ERROR
            );
            exit();
        }
    }

    function _obHandler($text)
    {

        // Make a resulting hash.
        if (!isset($this->RESULT)) {
            $this->RESULT = isset($GLOBALS['_RESULT'])? $GLOBALS['_RESULT'] : null;
        }
        $result = array(
            'json'   => $this->RESULT,
            'text' => $text,
        );
        return $this->_encodeResponse($result);
    }

    function _encodeResponse($data){
        Zend_Json::$useBuiltinEncoderDecoder = true;
        if (self::$encoding!='UTF-8') {
            array_walk_recursive($data, array(__CLASS__, 'encodingToUtf8'), self::$encoding);
        }
        return Zend_Json::encode($data);
    }

    public function format($responseNum, Lms_Api_Response $response)
    {
        // @codingStandardsIgnoreStart
        global $_RESULT;
        $_RESULT[$responseNum]['status'] = $response->getStatus();
        $_RESULT[$responseNum]['message'] = $response->getMessage();
        $_RESULT[$responseNum]['response'] = $response->getResponse();
        // @codingStandardsIgnoreEnd
        return null;
    }
    
    private function encodingToUtf8(&$text, $key, $encoding)
    {
        static $tr = array();
        if (!is_string($text)) return;
        $text = Lms_Translate::translate($encoding, 'UTF-8', $text);
        
    }
            
}
