<?php
/**
 * LMS2
 *
 * @version $Id: CorrectSuperGlobal.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007
 */

class Lms_CorrectSuperGlobal extends Lms_JsHttpRequest_JsHttpRequest
{
    function __construct($enc)
    {
        // Detect Unicode conversion method.
        $regExp = '/^(.*)(?:&|^)JsHttpRequest=(?:(\d+)-)?([^&]+)((?:&|$).*)$/s';
        if (preg_match($regExp, @$_SERVER['QUERY_STRING'], $m)) {
            $this->ID = $m[2];
            $this->LOADER = strtolower($m[3]);
        } else {
            $this->ID = 0;
            $this->LOADER = 'unknown';
        }
        if (function_exists('mb_convert_encoding')) {
            $this->_unicodeConvMethod = 'mb';
        } else if (function_exists('iconv')) {
            $this->_unicodeConvMethod = 'iconv';
        } else {
            $this->_unicodeConvMethod = null;
        }
        $this->setEncoding($enc);
    }
}
