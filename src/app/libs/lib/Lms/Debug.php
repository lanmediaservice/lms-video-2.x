<?php
/**
 * Система отладки
 * 
 * @copyright 2006-2009 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @author Alex Tatulchenkov
 * @version $Id: Debug.php 260 2009-11-29 14:11:11Z macondos $
 */
 
 
/**
 * Статический класс для приема отладочных сообщений
 *
 * Принимаемые сообщения передает в $_logger (экземпляр Zend_Log)
 * Пример использования:
 * $logger = new Zend_Log();
 * $writer = new Zend_Log_Writer_Stream('php://output');
 * $logger->addWriter($writer);
 * Lms_Debug::setLogger($logger);
 * Lms_Debug::log('Informational message', Zend_Log::INFO);
 * Lms_Debug::info('Informational message');
 *
 * @copyright 2006-2009 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 */
class Lms_Debug
{
    /**
     * экземпляр Zend_Log
     *
     * @var Zend_Log
     */
    private static $_logger;
    
    /**
     * Формат сообщений об ошибках
     *
     * @var string
     */
    private static $_format='%s at %s in line %s: <strong><em>%s</em></strong>';
    
    /**
     * Журналирует сообщение с заданным приоритетом
     *
     * @param  string   $message   Message to log
     * @param  integer  $priority  Priority of message
     * @return void
     * @throws Zend_Log_Exception
     */
    public static function log($message, $priority)
    {
        if (!self::$_logger) return;
        self::$_logger->log($message, $priority);
    }
    //Шорткаты для типовых отладочных сообщений
    // Emergency: system is unusable
    public static function emerg($message)
    {
        if (!self::$_logger) return;
        self::$_logger->emerg($message);
    }
    // Alert: action must be taken immediately
    public static function alert($message)
    {
        if (!self::$_logger) return;
        self::$_logger->alert($message);
    }
    // Critical: critical conditions
    public static function crit($message)
    {
        if (!self::$_logger) return;
        self::$_logger->crit($message);
    }
    // Error: error conditions
    public static function err($message) 
    {
        if (!self::$_logger) return;
        self::$_logger->err($message);
    }
    // Warning: warning conditions
    public static function warn($message) 
    {
        if (!self::$_logger) return;
        self::$_logger->warn($message);
    }
    //Notice: normal but significant condition
    public static function notice($message) 
    {
        if (!self::$_logger) return;
        self::$_logger->notice($message);
    }
    //Informational: informational messages
    public static function info($message) 
    {
        if (!self::$_logger) return;
        self::$_logger->info($message);
    }
    // Debug: debug messages
    public static function debug($message) 
    {
        if (!self::$_logger) return;
        if (is_array($message)) {
            $message = print_r($message, true);
        }
        self::$_logger->debug($message);
    }
    
    /**
     * Устанавливает логгер
     * 
     * @param $logger
     * @return void
     */
    public static function setLogger(Zend_Log $logger)
    {
        self::$_logger = $logger;
    }
    /**
     * �?нициирует перехватчик системных сообщений
     * @return void
     */
    public static function initErrorHandler()
    {
        set_error_handler(array(__CLASS__, 'errorHandler'));
        register_shutdown_function(array(__CLASS__, 'shutdown'));
    }
    
    /**
     * Пользовательский обработчик ошибок
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @throws Exception
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!error_reporting()) {
            return;
        }
        $errno = $errno & error_reporting();
        if ($errno == 0) {
            return;
        }
        if (!defined('E_STRICT')) {
            define('E_STRICT', 2048);
        }
        if (!defined('E_RECOVERABLE_ERROR')) {
            define('E_RECOVERABLE_ERROR', 4096);
        }
        if (!defined('E_DEPRECATED')) {
            define('E_DEPRECATED', 8192);
        }
        if (!defined('E_USER_DEPRECATED')) {
            define('E_USER_DEPRECATED', 16384);
        }
        $messageFormat = self::getMessageFormat();
        $errorName = self::_getErrorNameByErrno($errno);
        $message = sprintf(
            $messageFormat,
            $errorName, $errfile, $errline, $errstr
        );
        switch ($errno) {
            case E_DEPRECATED:// break intentionally omitted
            case E_USER_DEPRECATED: // break intentionally omitted
            case E_WARNING:// break intentionally omitted
            case E_USER_WARNING:
                self::warn($message);
                break;    
            case E_NOTICE:// break intentionally omitted
            case E_USER_NOTICE:// break intentionally omitted
            case E_STRICT:
                self::notice($message);
                break;
            case E_RECOVERABLE_ERROR:// break intentionally omitted
            case E_USER_ERROR:    
                self::err($message);
                break;
            default:
                throw new Exception("Unknown error ($errno)");
                break;
        }
    }
    
    /**
     * Обработчик завершения выполнения скрипта
     *
     */
    public static function shutdown()
    {
        if ($error = error_get_last()) {
            $messageFormat = self::getMessageFormat();
            $errorName = self::_getErrorNameByErrno($error['type']);
            $message = sprintf(
                $messageFormat,
                $errorName, $error['file'], $error['line'], $error['message']
            );
            switch($error['type']){
                case E_ERROR:// break intentionally omitted
                case E_CORE_ERROR:// break intentionally omitted
                case E_COMPILE_ERROR:// break intentionally omitted
                    self::err($message);                   
                    break;
                case E_CORE_WARNING:// break intentionally omitted
                case E_COMPILE_WARNING:
                    self::warn($message);
                    break; 
                default:
                    break;       
            }
        }
        restore_error_handler();
    }
    /**
     * Возвращает объект логгера
     * 
     * @return Zend_Log
     */
    public static function getLogger()
    {
        return self::$_logger;
    }
    /**
     * Устанавливает формат сообщений об ошибке, в пригодном виде для sprintf
     *
     * @param string $format
     */
    public static function setMessageFormat($format)
    {
        self::$_format = $format;
    }
    /**
     * Возвращает формат сообщений об ошибке, в пригодном виде для sprintf
     *
     * @return string
     */
    public static function getMessageFormat()
    {
        return self::$_format;
    }
    /**
     * Возвращает имя ошибки по ее номеру
     *
     * @param int $errno
     * @return string
     */
    private static function _getErrorNameByErrno($errno)
    {
        $errnoToErrNameMap = array(1 => 'E_ERROR',
                                   2 => 'E_WARNING',
                                   4 => 'E_PARSE',
                                   8 => 'E_NOTICE',
                                   16 => 'E_CORE_ERROR',
                                   32 => 'E_CORE_WARNING',
                                   64 => 'E_COMPILE_ERROR',
                                   128 => 'E_COMPILE_WARNING',
                                   256 => 'E_USER_ERROR',
                                   512 => 'E_USER_WARNING',
                                   1024 => 'E_USER_NOTICE',
                                   2048 => 'E_STRICT',
                                   4096 => 'E_RECOVERABLE_ERROR',
                                   8192 => 'E_DEPRECATED',
                                   16384 => 'E_USER_DEPRECATED',
                                   30719 => 'E_ALL');
        if (!isset($errnoToErrNameMap[$errno])) {
            throw new Exception('Unknown error');
        }
        return $errnoToErrNameMap[$errno];                            
    }
}