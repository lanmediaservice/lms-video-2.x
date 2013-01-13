<?php
 /**
 * Модуль авторизации Invision Power Board 2
 * 
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Ipb2.php 291 2009-12-28 12:55:20Z macondos $
 * @package Lms_Auth
 */

/**
 * @package Lms_Auth
 */
class Lms_Auth_Adapter_Ipb2 extends Lms_Auth_Adapter_DbGeneric
                            implements Zend_Auth_Adapter_Interface
{
    protected $_tableName = '?_members m INNER JOIN ?_members_converge mc ON (m.id=mc.converge_id)';
    protected $_identityColumn = 'name';
    protected $_credentialColumn = 'converge_pass_hash';
    protected $_credentialTreatment = 'MD5(CONCAT(MD5(converge_pass_salt), MD5(?)))';
    
}
