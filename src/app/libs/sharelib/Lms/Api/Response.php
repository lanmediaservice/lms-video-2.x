<?php
/**
 * Класс ответа
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Response.php 291 2009-12-28 12:55:20Z macondos $
 * @package Api
 */

/** 
 * @package Api
 */
class Lms_Api_Response
{
    private $_status;
    private $_response;
    private $_message;

    public function __construct($status, $message = '', $response = null)
    {
        $this->setStatus($status);
        $this->setMessage($message);
        $this->setResponse($response);
    }

    public function setStatus($status)
    {
        $this->_status = $status;
    }

    public function setMessage($message)
    {
        $this->_message = $message;
    }

    public function setResponse($response)
    {
        $this->_response = $response;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function getResponse()
    {
        return $this->_response;
    }
}