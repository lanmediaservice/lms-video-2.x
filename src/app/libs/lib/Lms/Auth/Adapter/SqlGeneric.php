<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: SqlGeneric.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007-2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 */


/**
 * @package    Accounting
 */
class Lms_Auth_Adapter_SqlGeneric implements Zend_Auth_Adapter_Interface
{
    /**
     * Database provider
     *
     * @var DbSimple_Database
     */
    protected $_db = null;

    protected $_sql = null;

    /**
     * Identity value
     *
     * @var string
     */
    protected $_identity = null;

    /**
     * Credential values
     *
     * @var string
     */
    protected $_credential = null;
    /**
     * $_authenticateResultInfo
     *
     * @var array
     */
    protected $_authenticateResultInfo = null;
    

    public function __construct(DbSimple_Database $db, $sql = null)
    {
        $this->_db = $db;

        if (null !== $sql) {
            $this->setSql($sql);
        }
    }

    public function setSql($sql)
    {
        $this->_sql = $sql;
        return $this;
    }

    public function setIdentity($value)
    {
        $this->_identity = $value;
        return $this;
    }

    public function setCredential($credential)
    {
        $this->_credential = $credential;
        return $this;
    }
    
    public function authenticate()
    {
        $this->_authenticateSetup();
        $resultIdentities = $this->_authenticate();
        $authResult = $this->_authenticateValidateResultset($resultIdentities);
        if ($authResult instanceof Zend_Auth_Result) {
            return $authResult;
        }
        
        $authResult = $this->_authenticateValidateResult(
            array_shift($resultIdentities)
        );
        return $authResult;
    }

    protected function _authenticateSetup()
    {
        $exception = null;

        if ($this->_sql == '') {
            $exception = 'A sql must be supplied for the ' . __CLASS__
                       . ' authentication adapter.';
        } elseif ($this->_identity == '') {
            $exception = 'A value for the identity was not provided prior ' 
                       . 'to authentication with ' . __CLASS__ . '.';
        } elseif ($this->_credential === null) {
            $exception = 'A credential value was not provided prior' 
                       . ' to authentication with ' . __CLASS__ . '.';
        }

        if (null !== $exception) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception($exception);
        }
        
        $this->_authenticateResultInfo = array(
            'code'     => Zend_Auth_Result::FAILURE,
            'identity' => $this->_identity,
            'messages' => array()
            );
            
        return true;
    }

    protected function _authenticate()
    {
        try {
            $resultIdentities = $this->_db->select(
                $this->_sql,
                $this->_credential, $this->_identity
            );
        } catch (Exception $e) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            $exception = 'The supplied parameters to ' . __CLASS__
                       . 'failed to produce a valid sql statement, please '
                       . 'check table and column names for validity.';
            throw new Zend_Auth_Adapter_Exception($exception);
        }
        return $resultIdentities;
    }

    protected function _authenticateValidateResultSet(array $resultIdentities)
    {
        // @codingStandardsIgnoreStart
        if (count($resultIdentities) < 1) {
            $this->_authenticateResultInfo['code'] = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
            $this->_authenticateResultInfo['messages'][] = 'A record with the supplied identity could not be found.';
            return $this->_authenticateCreateAuthResult();
        } elseif (count($resultIdentities) > 1) {
            $this->_authenticateResultInfo['code'] = Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
            $this->_authenticateResultInfo['messages'][] = 'More than one record matches the supplied identity.';
            return $this->_authenticateCreateAuthResult();
        }
        // @codingStandardsIgnoreEnd
        return true;
    }

    protected function _authenticateValidateResult($resultIdentity)
    {
        // @codingStandardsIgnoreStart
        if ($resultIdentity['zend_auth_credential_match'] != '1') {
            $this->_authenticateResultInfo['code'] = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
            $this->_authenticateResultInfo['messages'][] = 'Supplied credential is invalid.';
            return $this->_authenticateCreateAuthResult();
        }

        unset($resultIdentity['zend_auth_credential_match']);
        $this->_resultRow = $resultIdentity;

        $this->_authenticateResultInfo['code'] = Zend_Auth_Result::SUCCESS;
        $this->_authenticateResultInfo['messages'][] = 'Authentication successful.';
        return $this->_authenticateCreateAuthResult();
        // @codingStandardsIgnoreEnd
    }
    
    protected function _authenticateCreateAuthResult()
    {
        return new Zend_Auth_Result(
            $this->_authenticateResultInfo['code'],
            $this->_authenticateResultInfo['identity'],
            $this->_authenticateResultInfo['messages']
        );
    }

}
