<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: Logable.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package Lms
 */
 

class Lms_Logable
{
    private $_ignoresInTraceRe = 'call_user_func.*';
 
    /**
     * Name of callback-function for logging  
     */
    private $_logger;

    /**
     * void addIgnoreInTrace($reName)
     * Add regular expression matching ClassName::functionName or functionName.
     * Matched stack frames will be ignored in stack
     * traces passed to query logger.
     */   
    public function addIgnoreInTrace($name)
    {
        $this->_ignoresInTraceRe .= "|" . $name;
    }
    
    public function setLogger($logger)
    {
        $prev = $this->_logger;
        $this->_logger = $logger;
        return $prev;
    }

    public function log($message)
    {
        if (is_callable($this->_logger)) {
            $caller = $this->findCaller();
            call_user_func($this->_logger, $this, $message, $caller);
        }
    }

    private function findCaller()
    {
        $caller = call_user_func(
            array($this, 'debugBacktraceSmart'),
            $this->_ignoresInTraceRe,
            true
        );
        return $caller;
    }

    private function debugBacktraceSmart($ignoresRe=null, $returnCaller=false)
    {
        if (!is_callable($tracer = 'debug_backtrace')) {
            return array();
        }
        $trace = $tracer();

        if ($ignoresRe !== null) $ignoresRe = "/^(?>{$ignoresRe})$/six";
        $smart = array();
        $framesSeen = 0;
        for ($i=0, $n=count($trace); $i<$n; $i++) {
            $t = $trace[$i];
            if (!$t) continue;

            // Next frame.
            $next = isset($trace[$i+1])? $trace[$i+1] : null;

            // Dummy frame before call_user_func* frames.
            if (!isset($t['file'])) {
                $t['over_function'] = $trace[$i+1]['function'];
                $t = $t + $trace[$i+1];
                $trace[$i+1] = null; // skip call_user_func on next iteration
            }

            // Skip myself frame.
            if (++$framesSeen < 2) continue;
            // 'class' and 'function' field of next frame define where
            // this frame function situated. Skip frames for functions
            // situated in ignored places.
            if ($ignoresRe && $next) {
                // Name of function "inside which" frame was generated.
                $frameCaller = (isset($next['class'])? $next['class'].'::' : '')
                             . (isset($next['function'])? $next['function'] : '');
                if (preg_match($ignoresRe, $frameCaller)) continue;
            }

            // On each iteration we consider ability to add PREVIOUS frame
            // to $smart stack.
            if ($returnCaller) return $t;
            $smart[] = $t;
        }
        return $smart;
    }   
     
}
