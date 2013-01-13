<?php

/**
 * @see Zend_Auth_Adapter_Interface
 */

/**
 * @see Zend_OpenId_Consumer
 */


class Lms_Auth_Adapter_OpenId extends Zend_Auth_Adapter_OpenId
{
    private $_registry;
    
    public function setIdentityRegistry(
        Lms_Registry_Adapter_Interface $registry
    )
    {
        $this->_registry = $registry;
    }

    /**
     * Authenticates the given OpenId identity.
     * Defined by Zend_Auth_Adapter_Interface.
     *
     * @throws Zend_Auth_Adapter_Exception If answering the authentication
     *                                     query is impossible
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $result = parent::authenticate();
        if ($result->isValid()
            && ($username = $this->extractUsername($result->getIdentity()))
        ) {
            return new Zend_Auth_Result(
                $result->getCode(),
                $username,
                $result->getMessages()
            );
        }
        return $result;
    }
    
    private function extractUsername($openidIdentity)
    {
        $openIdRegExp = '{openid\.yandex\.ru/([^/]*)}i';
        $yaRuRegExp = '{([^/\.]*)\.ya\.ru}i';
        $mailRuRegExp = '{openid.mail.ru/([^/]*)/([^/]*)}i';
        $googleRegExp = '{google.com/accounts}i';
        if (preg_match($openIdRegExp, $openidIdentity, $matches)
            || preg_match($yaRuRegExp, $openidIdentity, $matches)
        ) {
            return $matches[1] . '@yandex.ru';
        } else if (preg_match($googleRegExp, $openidIdentity, $matches)) {
            if ($this->_extensions instanceof Lms_OpenId_Extension_Ext1) {
                $username = $this->_extensions->getEmail();
                if ($username) {
                    if ($this->_registry) {
                        $this->_registry->set($openidIdentity, $username);
                        return $username;
                    }
                } else if ($this->_registry) {
                    return $this->_registry->get($openidIdentity, $openidIdentity);
                }
            }
        } else if (preg_match($mailRuRegExp, $openidIdentity, $matches)) {
            return $matches[2] . '@' . $matches[1] . '.ru';
        }
        
        if (preg_match('{https?://(.*)}i', $openidIdentity, $matches)) {
            return trim($matches[1], '/');
        }
        
        return $openidIdentity;
    }
}
