<?php
/**
 * Форматировщик API-ответов в формат PHP
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
class Lms_Api_Formatter_Php implements Lms_Api_Formatter_Interface
{
    public function setUp()
    {
        $phpHttpRequestServer = new Lms_PhpHttpRequest_Server();
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
}
