<?php
/**
 *  PHP-клиент к стандартному интерфейсу Lms_Api
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Client.php 291 2009-12-28 12:55:20Z macondos $
 * @package Api
 */

/** 
 * @package Api
 */
class Lms_Api_Client
{
    private $_phpHttpRequestClient;
    private $_apiUrl;
    private $_params;
    private $_lastRequestId;
    private $_rawResponse;

    public function __construct(Lms_PhpHttpRequest_Client $phpHttpRequestClient,
        $apiUrl = null
    )
    {
        $this->reset();
        $this->_phpHttpRequestClient = $phpHttpRequestClient;
        $this->setApiUrl($apiUrl);
    }

    public function setApiUrl($apiUrl)
    {
        $this->_apiUrl = $apiUrl;
    }

    public function addRequest($actionName, $params)
    {
        $this->_lastRequestId++;
        $params['action'] = $actionName;
        foreach ($params as $paramKey => $paramValue) {
            $this->_params[$paramKey][$this->_lastRequestId] = $paramValue;
        }
        return $this;
    }
    
    public function getLastRequestId()
    {
        return $this->_lastRequestId;
    }

    public function query()
    {
        $this->_rawResponse = $this->_phpHttpRequestClient->query(
            $this->_apiUrl, $this->_params
        );
        return $this;
    }

    public function getResponse($requestId = null)
    {
        if ($requestId) {
            return $this->_rawResponse['php'][$requestId];
        } else {
            return reset($this->_rawResponse['php']);
        }
    }

    public function getRawResponse()
    {
        return $this->_rawResponse;
    }

    public function getRawHttpResponse()
    {
        return $this->_phpHttpRequestClient->getRawHttpResponse();
    }

    public function getText()
    {
        return $this->_rawResponse['text'];
    }

    public function reset()
    {
        $this->_lastRequestId = -1;
        $this->_params = array('format' => 'php');
        $this->_rawResponse = null;
        return $this;
    }
}