<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: MultiAuth.php 630 2011-02-14 09:03:03Z macondos $
 * @copyright 2007-2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 */


/**
 * @package    Accounting
 */
class Lms_MultiAuth extends Lms_Singleton {

    /**
     * Persistent storage handler
     *
     * @var Zend_Auth_Storage_Interface
     */
    protected $_storage = null;

    public static function getInstance($class = null) {
        return parent::getInstance(__CLASS__);
    }

    /**
     * Returns the persistent storage handler
     *
     * Session storage is used by default unless a different storage adapter has been set.
     *
     * @return Zend_Auth_Storage_Interface
     */
    public function getStorage()
    {
        if (null === $this->_storage) {
            /**
             * @see Zend_Auth_Storage_Session
             */
            require_once 'Zend/Auth/Storage/Session.php';
            $this->setStorage(new Zend_Auth_Storage_Session());
        }

        return $this->_storage;
    }

    /**
     * Sets the persistent storage handler
     *
     * @param  Zend_Auth_Storage_Interface $storage
     * @return Zend_Auth Provides a fluent interface
     */
    public function setStorage(Zend_Auth_Storage_Interface $storage)
    {
        $this->_storage = $storage;
        return $this;
    }
    
       
    /**
     * Authenticates against the supplied adapter
     *
     * @param  Zend_Auth_Adapter_Interface $adapter
     * @return Zend_Auth_Result
     */
    public function authenticate(Zend_Auth_Adapter_Interface $adapter, $authProviderKey, $protectCode = null)
    {
        $result = $adapter->authenticate();

        if ($result->isValid()) {
            $this->getStorage()->write(array($result->getIdentity(), $authProviderKey, $protectCode));
        }

        return $result;
    }

    public function forceAuthenticate($identity, $authProviderKey, $protectCode = null)
    {
        $this->getStorage()->write(array($identity, $authProviderKey, $protectCode));
        return $this;
    }
    
    /**
     * Returns true if and only if an identity is available from storage
     *
     * @return boolean
     */
    public function hasIdentity()
    {
        return !$this->getStorage()->isEmpty();
    }

    /**
     * Returns the identity from storage or null if no identity is available
     *
     * @return mixed|null
     */
    public function getIdentity()
    {
        $storage = $this->getStorage();

        if ($storage->isEmpty()) {
            return null;
        }
        $data = $storage->read();
        return $data[0];
    }

    public function getAuthProviderKey()
    {
        $storage = $this->getStorage();

        if ($storage->isEmpty()) {
            return null;
        }
        $data = $storage->read();
        return $data[1];
    }
    
    public function getProtectCode()
    {
        $storage = $this->getStorage();

        if ($storage->isEmpty()) {
            return null;
        }
        $data = $storage->read();
        return isset($data[2])? $data[2] : null;
    }

    /**
     * Clears the identity from persistent storage
     *
     * @return void
     */
    public function clearIdentity()
    {
        $this->getStorage()->clear();
    }
}
