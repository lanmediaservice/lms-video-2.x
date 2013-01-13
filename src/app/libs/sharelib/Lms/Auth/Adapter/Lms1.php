<?php
/**
 * Модуль авторизации lms 1.x
 * 
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Lms1.php 291 2009-12-28 12:55:20Z macondos $
 * @package Lms_Auth
 */

/**
 * @package Lms_Auth
 */
class Lms_Auth_Adapter_Lms1 extends Lms_Auth_Adapter_DbGeneric
                            implements Zend_Auth_Adapter_Interface
{
    protected $_tableName = 'users';
    protected $_identityColumn = 'Login';
    protected $_credentialColumn = 'Password';
    protected $_credentialTreatment = 'MD5(?)';
    protected $_conditionStatement = '`UserGroup`>0';
}
