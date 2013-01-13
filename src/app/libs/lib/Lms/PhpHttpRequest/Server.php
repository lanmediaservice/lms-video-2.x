<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: Server.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package Lms
 */
 
/**
 * @package PhpHttpRequest
 */
class Lms_PhpHttpRequest_Server {

	function __construct(){
		ob_start(array(&$this, "_obHandler"));
        // Check if headers are already sent (see Content-Type library usage).
        // If true - generate a debug message and exit.
        $file = $line = null;
        $result = version_compare(PHP_VERSION, "4.3.0") < 0? headers_sent() : headers_sent($file, $line);
        if ($result) {
            trigger_error(
                "HTTP headers are already sent" . ($line !== null? " in $file on line $line" : " somewhere in the script") . ". "
                . "Possibly you have an extra space (or a newline) before the first line of the script or any library. ",
                E_USER_ERROR
            );
            exit();
        }
		
	}

    function _obHandler($text)
    {
        
        // Make a resulting hash.
        if (!isset($this->RESULT)) {
            $this->RESULT = isset($GLOBALS['_RESULT'])? $GLOBALS['_RESULT'] : null;
        }
        $result = array(
            'php'   => $this->RESULT,
            'text' => $text,
        );
        return $this->_encodeResponse($result);
    }

    function _encodeResponse($data){
    	return serialize($data) . ' '; //space  - for buggly http_client
    }


}

?>