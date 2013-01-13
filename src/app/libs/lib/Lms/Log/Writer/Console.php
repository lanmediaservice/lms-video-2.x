<?php
/**
 * Плагин Zend_Log_Writer для вывода отладочной информации в Debug_HackerConsole
 * 
 *
 * @copyright 2006-2009 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Console.php 302 2009-12-30 09:26:31Z macondos $
 */


/**
 * Вывод отладочной информации в Debug_HackerConsole
 *
 *
 * @copyright 2006-2009 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 */
class Lms_Log_Writer_Console extends Zend_Log_Writer_Abstract
{
    protected $_debugHackerConsole = null;

    public function __construct(Debug_HackerConsole_Main $debugHackerConsole = null)
    {
        if (!$debugHackerConsole) {
            $debugHackerConsole = new Debug_HackerConsole_Main(true);
        }
        $this->_debugHackerConsole = $debugHackerConsole;
        
        $this->_formatter = new Zend_Log_Formatter_Simple();
    }

    /**
     * Write a message to the log.
     *
     * @param  array  $event  event data
     * @return void
     */
    protected function _write($event)
    {
        $line = $this->_formatter->format($event);
        $redLevel = 10 - min(10, $event['priority']);
        $greenLevel = 10 - $redLevel;
        $blueLevel = 10 - $redLevel;
        $redHex = dechex((int)256 * $redLevel / 10);
        $greenHex = dechex((int)256 * $greenLevel / 10);
        $blueHex = dechex((int)256 * $blueLevel / 10);
        $cssColor = "#{$redHex}{$greenHex}{$blueHex}";
        call_user_func(
            array($this->_debugHackerConsole, 'out'),
            $line, $event['priorityName'], $cssColor
        );
    }

}
