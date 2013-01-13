<?php
/**
 * Модуль авторизации vBulletin 3
 * 
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Vb3.php 291 2009-12-28 12:55:20Z macondos $
 * @package Lms_Auth
 */

/**
 * @package Lms_Auth
 */
class Lms_Auth_Adapter_Vb3 extends Lms_Auth_Adapter_DbGeneric
                            implements Zend_Auth_Adapter_Interface
{
    protected $_tableName = 'user';
    protected $_identityColumn = 'username';
    protected $_credentialColumn = 'password';
    protected $_credentialTreatment = 'MD5(CONCAT(MD5(?), `salt`))';
}
