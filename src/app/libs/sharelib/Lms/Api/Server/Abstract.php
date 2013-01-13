<?php
/**
 * Абстракный класс Api-функций
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Abstract.php 291 2009-12-28 12:55:20Z macondos $
 * @package Api
 */

/** 
 * @package Api
 */
  class Lms_Api_Server_Abstract
{
    
    public static function test($params = false)
    {
        if (!isset($params['text'])) {
            $params['text'] = '123';
        }
        return new Lms_Api_Response(200, 'OK', md5($params['text']));
    }
}
