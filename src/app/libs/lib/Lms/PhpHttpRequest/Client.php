<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: Client.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package Lms
 */
 
class Lms_PhpHttpRequest_Client{
    
    private $_httpClient;
    private $_rawHttpResponse;
    
    public function __construct(Zend_Http_Client $http_client)
    {
        $this->_httpClient = $http_client;
    }
    
    public function query($url, $params = array())
    {
        $this->_httpClient->resetParameters()
                          ->setUri($url)
                          ->setMethod(Zend_Http_Client::POST)
                          ->resetParameters()
                          ->setParameterPost($params)
                          ->setEncType(Zend_Http_Client::ENC_URLENCODED);
        $this->_rawHttpResponse = $this->_httpClient->request();
        if ($this->_rawHttpResponse->isSuccessful()) {
            $body = trim( $this->_rawHttpResponse->getBody());
            return $this->_decodeResponse($body);
        } else {
            throw new Lms_Exception("Error while get '$url', server return wrong status '" .  $this->_rawHttpResponse->getStatus() . "'");
        } 
    }
    
    public function _decodeResponse($data)
    {
        return unserialize($data);
    }

    public function getRawHttpResponse()
    {
        return $this->_rawHttpResponse;
    }
}