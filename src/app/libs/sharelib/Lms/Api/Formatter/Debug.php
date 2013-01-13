<?php
/**
 * Форматировщик API-ответов в формат простого отладочного вывода
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Debug.php 291 2009-12-28 12:55:20Z macondos $
 * @package Api
 */

/** 
 * @package Api
 */

class Lms_Api_Formatter_Debug implements Lms_Api_Formatter_Interface
{
    public function setUp()
    {
    }

    public function format($responseNum, Lms_Api_Response $response)
    {
        $result = array('#' => $responseNum,
                        'status' => $response->getStatus(),
                        'message' => $response->getMessage(),
                        'response' => $response->getResponse());
        return '<pre>' . print_r($result, true) . '</pre>';
    }
}
