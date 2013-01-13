<?php
/**
 * Интерфейс форматировщиков API-ответов
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Interface.php 291 2009-12-28 12:55:20Z macondos $
 * @package Api
 */

/** 
 * @package Api
 */

interface Lms_Api_Formatter_Interface
{
    public function setUp();
   
    public function format($responseNum, Lms_Api_Response $response);

}
