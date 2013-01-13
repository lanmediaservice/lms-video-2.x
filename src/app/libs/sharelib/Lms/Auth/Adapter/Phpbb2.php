<?php
/**
 * Модуль авторизации PhpBB 2
 * 
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Phpbb2.php 291 2009-12-28 12:55:20Z macondos $
 * @package Lms_Auth
 */

/**
 * @package Lms_Auth
 */
class Lms_Auth_Adapter_Phpbb2 extends Lms_Auth_Adapter_DbGeneric
                            implements Zend_Auth_Adapter_Interface
{
    protected $_tableName = 'users';
    protected $_identityColumn = 'username';
    protected $_credentialColumn = 'user_password';
    protected $_credentialTreatment = 'MD5(?)';
    protected $_conditionStatement = '`user_active`=1';
}
