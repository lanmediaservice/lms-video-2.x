<?php
/**
 * Модуль авторизации Joomla 1.0, 1.5
 * 
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Joomla.php 291 2009-12-28 12:55:20Z macondos $
 * @package Lms_Auth
 */

/**
 * @package Lms_Auth
 */
class Lms_Auth_Adapter_Joomla extends Lms_Auth_Adapter_DbGeneric
                            implements Zend_Auth_Adapter_Interface
{
    protected $_tableName = '?_users';
    protected $_identityColumn = 'username';
    protected $_credentialColumn = 'password';
    protected $_credentialTreatment = "CONCAT(MD5(CONCAT(?, IF(LOCATE(':', `password`), SUBSTRING_INDEX( `password` , ':', -1 ), ''))), IF(LOCATE(':', `password`), CONCAT(':', SUBSTRING_INDEX( `password` , ':', -1 )), ''))";
    protected $_conditionStatement = '`block`=0';
   
}
