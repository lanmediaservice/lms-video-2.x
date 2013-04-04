<?php
/**
 * LMS Library
 *
 * Предоставляет статический интерфейс для унифицированной работы с основными операциями
 * с файловой системой.
 * 
 * @version $Id: Ufs.php 562 2010-11-03 09:04:21Z macondos $
 * @copyright 2007-2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov<webtota@gmail.com>
 */

/**
 * Операции выполняемые над ресурсом должны быть реализованы через _performOperation(),
 * а принимающие первым параметром `путь` операции(is_dir, ftell ... и т.д.)  через _execute()
 */

/**
 * @package Ufs
 */
class Lms_Ufs 
{
    
    /**
     *    'c:/tmp/' => 'CP1251',
     *    'c:/tmp/pp/' => 'UTF-8',
     *    'c:/tmp/pp/122' => 'KOI8-R',
     *
     * @var unknown_type
     */
    
    /**
     * Array for configuration settings
     *
     * @var array
     */
    static public $config = array();
    
    /**
     * Internal encoding 
     *
     * @var string
     */
    static $internalEncoding  = 'UTF-8';
    
    /**
     * Encoding of the system you working with
     *
     * @var string
     */
    static $systemEncoding;
    
    /**
     * Array of cached resources
     *
     * @var array
     */
    static private $res = array();
    
    /**
     * Name of callback using for logging
     *
     * @var unknown_type
     */
    static private $logger = '';
    
    /**
     * Set encoding for current file or directory
     *
     * @param string $path
     * @param string $encoding
     */
    static public function setEncoding($path, $encoding)
    {
        $path = self::_getPath($path); 
        self::$config[$path]['encoding'] = $encoding;
    }
    
    /**
     * Get encoding of object
     *
     * @param string/resource $object
     * @return string
     */
    static public function getEncoding($object)
    {
        $encoding = (is_string($object))? self::_getEncodingByPath($object) : self::_getEncodingByResource($object);
        if($encoding) {
            return $encoding;
        } else {
            if(isset(self::$systemEncoding)) {
                return self::$systemEncoding;
            }
            return self::$internalEncoding;
        }
    }
    /**
     * Provide getPath method for external classes
     *
     * @return unknown
     */
    static public function getAbsolutePath($path) 
    {
        
        $fileUrl = self::_getPath($path);
        $absolutePath = self::encodeUrl($fileUrl);
        return $absolutePath;
    }
    
    /**
     * get clear absolute path
     * 
     * @param string $path
     * @return string 
     */
     
    static private function _getPath($path)
    {
        if(strpos($path, "http://") === 0){return $path;}//Временно. Нужно предусмотреть https, ftp и др.
        $validPath = array(':/'=>1, '/'=>0, ':\\' =>1, '\\'=>0, '://'=>1);
        foreach ($validPath as $substring => $position) {
            if($position === strpos($path, $substring)) {
                $normalizedPath = self::_normalize($path);
                $curPath = self::_purify($normalizedPath);
                return $curPath;
            }
        }
        return self::_getRealPath($path);
        
    }
    
    /**
     * Purify $path from dots and double slashes
     * 
     * @param string $path
     * @return string
     */ 
    static private function _purify($path)
    {
        $purePath = str_replace('/./', '/', $path);
        $purePath = substr($purePath, 0, 1) . preg_replace('{/+}', '/', substr($purePath, 1));
        //Если путь вида url/../../chunk
//        НЕ тестировалось
//        if (false !== strpos($purePath, '../')) {
//            do { 
//              $purePathBefore = $purePath;
//              $purePath = preg_replace('~/[^/]*/\.\.~', '', $purePath);
//            } while ($purePathBefore != $purePath);
//        }
        
        $purePath = preg_replace('~/[^/]*/\.\.~', '', $purePath);
        return $purePath;
    }
    
    /**
     * Convert all backslashes to forward slashes
     *
     * @param string $path
     * @return string
     */
    static private function _normalize($path)
    {
        return $normalizedPath = str_replace('\\', '/', $path);
    }
    
    /**
     * Get real path to file from relative path, even for not existant files 
     * 
     * @param string $path
     * @return string 
     */
    
    static private function _getRealPath($path)
    {
        $absPathToDir = getcwd();
        $normalizedDirPath = self::_normalize($absPathToDir);
        $normalizedPath = self::_normalize($path);
        if (strrpos($normalizedDirPath, '/') !== (strlen($normalizedDirPath) - 1)) {
            $normalizedDirPath .= '/';
        }
        $absCurPath = $normalizedDirPath . $normalizedPath;
        $clearPath  = self::_purify($absCurPath);
        return $clearPath;
    }
    
    /**
     * Get encoding (if setted) for current file or directory 
     *
     * @param string $path
     * @return string
     */
    static public function _getEncodingByPath($path)
    {
        $path = self::_getPath($path);
        $matchPath = false;
        foreach (self::$config as $pathItem => $settings) {
            if(false !==  strpos($path, $pathItem)) {
                $nums[] = $pathItem;
                $matchPath = strlen($pathItem) > strlen($nums[0]) ? $pathItem : $nums[0];
            }
        }
        if ($matchPath) {
            return self::$config[$matchPath]['encoding'];
        }
        return false;
    }
    
    /**
     * Get encoding (if setted) for curent resource
     *
     * @param resource $resource
     * @return string/bool
     */
    static private function _getEncodingByResource($resource)
    {
        $hash = self::getHashByResource($resource);
        foreach (self::$res as $items) {
            return $items[$hash]['encoding'];
/*            foreach ($items as $config) {
                if($resource == $config['resource']) {
                    return $config['encoding'];
                }
            }*/
        } 
        return false;
    }
    
    /**
     * Set the encoding of the system you working with
     *
     * @param string $encoding
     */
    static public function setSystemEncoding($encoding)
    {
        self::$systemEncoding = $encoding;   
    }
   
    /**
     * Perform current operation with current resource using required module 
     *
     * @return mixed
     */
    static private function _performOperation()
    {
    	
    	$start = microtime();
        $result = false;
        $operation = func_get_arg(0);
        $resource  = func_get_arg(1);
        $className = self::_getClassByResource($resource);
        if (!$className) {
            return false;
        }
        $a = func_get_args();
        array_shift($a);
        $operationParams = $a; 
        $result          = call_user_func_array(array($className, $operation), $operationParams);
        if ($result === false) {
            return false;
        }
        $end  = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        self::_log("Operation '$operation' for '$resource' completed in $time seconds.");
        return $result;
    }
    
    /**
     * Get classname  of the Module which can process this $uri
     *
     * @param string $uri
     * @return string
     */
    static private function _getClassNameByUri($uri)
    {
        $fs = Lms_Ufs_Index::findModule($uri);
        if ($fs == NULL) {
          throw new Lms_Exception("There is no Module to process this uri: ".$uri, E_USER_ERROR);
        } else {
          	$className = Lms_Modular::loadModule($fs);
        }
        return $className; 
    }
    
    /**
     * Perform current operation with current file or directory using required module 
     *
     * @param string $operation
     * @param string $fileUrl
     * @return mixed
     */
    static private function _execute($operation, $fileUrl)
    {
        $operataionParams = func_get_args();
        
        $operation  = array_shift($operataionParams);
        $fileUrl    = array_shift($operataionParams);
        $fileUrl    = self::_getPath($fileUrl);
        $encodedUrl = self::encodeUrl($fileUrl);
        
        array_unshift($operataionParams, $encodedUrl);
        $className  = self::_getClassNameByUri($fileUrl);
        $result     = call_user_func_array(array($className, $operation), $operataionParams);
        return $result;
    }
    
    /**
     * Add settings for current $path to config array  
     *
     * @param string $path
     * @param string/array $params
     */
    public static function addConfig($path, $params)
    {
        self::$config[$path] = $params;
    }

    /**
     * Set internal encoding
     *
     * @param unknown_type $encoding
     */
    static public function setInternalEncoding($encoding)
    {
        self::$internalEncoding = $encoding;
    }
    
    /**
     * Wrapper for mkdir
     *
     * @param string $dirUrl
     * @param int $mode
     * @param bool $recursively
     * @return bool
     */
    static public function mkdir($dirUrl, $mode = 0777, $recursively = false)
    {
        return self::_execute('mkdir', $dirUrl, $mode, $recursively);
    }
    
    /**
     * wrapper for rmdir 
     *
     * @param string $dirUrl
     * @return bool
     */
    static public function rmdir($dirUrl)
    {
        return self::_execute('rmdir', $dirUrl);
    }
    
    /**
     * wrapper for touch
     *
     * @param string $file
     * @param int $time
     * @param int $atime
     * @return bool
     */
    static public function touch($file, $time = false, $atime = false)
    {
         return self::_execute('touch', $file, $time, $atime);
    }
    
    /**
     * wrapper for chmod
     *
     * @param string $file
     * @param int $mode
     * @return bool
     */
    static public function chmod($file, $mode)
    {
         return self::_execute('chmod', $file, $mode);
    }
    
    /**
     * wrapper for file_exists 
     *
     * @param string $fileUrl
     * @return bool
     */
    static public function file_exists($fileUrl)
    {
        return self::_execute('file_exists', $fileUrl);
    }
    
    /**
     * wrapper for opendir
     *
     * @param string $dirUrl
     * @return resource
     */
    static public function opendir($dirUrl)
    {
        $encodedDirUrl = self::encodeUrl($dirUrl);
        $className     = self::_getClassNameByUri($encodedDirUrl);
        $handler       = call_user_func_array(array($className, 'opendir'), array($encodedDirUrl));
        self::_toResCache($handler, $className, self::getEncoding($dirUrl));
        return $handler;
    }
    
   /**
    * wrapper for fopen
    *
    * @param string $fileUrl
    * @param string $mode
    * @param bool $use_include_path
    * @return resource
    */
    static public function fopen($fileUrl, $mode, $use_include_path = false)
    {
        $encodedFileUrl = self::encodeUrl($fileUrl);
        $className      = self::_getClassNameByUri($encodedFileUrl);
        $handler        = call_user_func_array(array($className, 'fopen'), array($encodedFileUrl, $mode, $use_include_path));
        self::_toResCache($handler, $className, self::getEncoding($fileUrl));
        return $handler;
    }
    
    /**
     * wrapper for readdir
     *
     * @param resource $resource
     * @return string
     */
    static public function readdir($resource)
    {
        $result = self::_performOperation('readdir', $resource);
        $result = Lms_Translate::translate(self::_getEncodingByResource($resource), self::$internalEncoding, $result);
        return $result;
    }
    
    /**
     * wrapper for fgets
     *
     * @param resource $resource
     * @param int $length
     * @return string
     */
    static public function fgets($resource, $length = 1024)
    {
        $result = self::_performOperation('fgets', $resource, $length);
        return $result;
    }
    
    /**
     * wrapper for fread 
     *
     * @param resource $resource
     * @param int $length
     * @return string
     */
    static public function fread($resource, $length)
    {
        $result = self::_performOperation('fread', $resource, $length);
        return $result;
    }
    
    /**
     * wrapper for ftell 
     *
     * @param resource $resource
     * @return int
     */
    static public function ftell($resource)
    {
        $result = self::_performOperation('ftell', $resource);
        return $result;
    }
    
    /**
     * wrapper for fruncate 
     *
     * @param resource $resource
     * @param int $size
     * @return bool
     */
    static public function ftruncate($resource, $size)
    {
        $result = self::_performOperation('ftruncate', $resource, $size);
        return $result;
    }
    
    /**
     * wrapper for fwrite
     *
     * @param resource $resource
     * @param string $string
     * @param int $length
     * @return int
     */
    static public function fwrite($resource, $string, $length = -1)
    {
        $result = self::_performOperation('fwrite', $resource, $string, $length);
        return $result;
    }
    
    /**
     * wrapper for fseek 
     *
     * @param resource $resource
     * @param int $offset
     * @param int $whence
     * @return int
     */
    static public function fseek($resource, $offset, $whence = SEEK_SET)
    {
        $result = self::_performOperation('fseek', $resource, $offset, $whence = SEEK_SET);
        return $result;
    }
    
    /**
     * wrapper for rewind
     *
     * @param resource $resource
     * @return bool
     */
    static public function rewind($resource)
    {
        $result = self::_performOperation('rewind', $resource);
        return $result;
    }
    
    /**
     * wrapper for copy 
     *
     * @param string $sourceUrl
     * @param string $destinationUrl
     * @return string
     */
    static public function copy($sourceUrl, $destinationUrl)
    {
        $decodedSourceUrl       = self::encodeUrl($sourceUrl);
        $decodedDestinationUrl  = self::encodeUrl($destinationUrl);

        if ($sourceFs = self::_getClassNameByUri($decodedSourceUrl)) {
            $destFs = self::_getClassNameByUri($decodedDestinationUrl);
            if ($sourceFs==$destFs) {
                return call_user_func_array(array($sourceFs, 'copy'), array($decodedSourceUrl, $decodedDestinationUrl));
            } else {
                throw new Lms_Exception("Copy between different filesystems not available");
            }
        }
    }

    /**
     * wapper for rename 
     *
     * @param string $sourceUrl
     * @param string $destinationUrl
     * @returnbool
     */
    static public function rename($sourceUrl, $destinationUrl)
    {
        $decodedSourceUrl      = self::encodeUrl($sourceUrl);
        $decodedDestinationUrl = self::encodeUrl($destinationUrl);

        if ($sourceFs = self::_getClassNameByUri($decodedSourceUrl)) {
            $destFs = self::_getClassNameByUri($decodedDestinationUrl);
            if ($sourceFs==$destFs) {
                return call_user_func_array(array($sourceFs, 'rename'), array($decodedSourceUrl, $decodedDestinationUrl));
            } else {
                throw new Lms_Exception("Rename files between different filesystems not available");
            }
        }
    }
        
    /**
     * wrapper for feof
     *
     * @param resource $resource
     * @return bool
     */
    static public function feof($resource)
    {
        $result = self::_performOperation('feof', $resource);
        return $result;
    }
    
    /**
     * wrapper for closedir
     *
     * @param resource $resource
     * @return bool
     */
    static public function closedir($resource)
    {
        self::_delResCache($resource);
        self::_performOperation('closedir', $resource);
        return true;
        
    }
    
    /**
     * wrapper for fclose
     *
     * @param resource $resource
     * @return bool
     */
    static public function fclose($resource)
    {
        self::_delResCache($resource);
        self::_performOperation('fclose', $resource);
        return true;
    }
    
    /**
     * wrapper for rewwinddir
     *
     * @param resource $resource
     * @return bool
     */
    static public function rewinddir($resource)
    {
        self::_performOperation('rewinddir', $resource);
        return true;
    }
    
    /**
     * wrapper for filemtime
     *
     * @param string $file
     * @return int
     */
    static public function filemtime($file)
    {
       return self::_execute('filemtime', $file);
    }
    
    /**
     * wrapper for is_writable
     *
     * @param string $file
     * @return bool
     */
    static public function is_writable($file)
    {
        return self::_execute('is_writable', $file);
    }
    
    /**
     * wrapper for filesize
     *
     * @param string $fileUrl
     * @return int
     */
    static public function filesize($fileUrl)
    {
        if(!self::file_exists($fileUrl)) return false;
        return self::_execute('filesize', $fileUrl);
    }
    
    /**
     * wrapper for is_dir
     *
     * @param string $fileUrl
     * @return bool
     */
    static public function is_dir($fileUrl)
    {
        return self::_execute('is_dir', $fileUrl);
    }
    
    /**
     * wrpapper for is_file
     *
     * @param string $fileUrl
     * @return bool
     */
    static public function is_file($fileUrl)
    {
     	return self::_execute('is_file', $fileUrl);
    }

    /**
     * wrapper for is_dir
     *
     * @param string $fileUrl
     * @return bool
     */
    static public function is_readable($fileUrl)
    {
        return self::_execute('is_readable', $fileUrl);
    }
    
    /**
     * wrapper for unlink
     *
     * @param string $fileUrl
     * @return bool
     */
    static public function unlink($fileUrl)
    {
        return self::_execute('unlink', $fileUrl);
    }
    
    /**
     * Decode string to $internalEncoding
     *
     * @param string $fileUrl
     * @return string
     */
    static public function decodeUrl($fileUrl){
        return Lms_Translate::translate(self::getEncoding($fileUrl), self::$internalEncoding, $fileUrl);
    }

    /**
     * Encode string to $internalEncoding
     *
     * @param string $fileUrl
     * @return srting
     */
    static public function encodeUrl($fileUrl){
        return Lms_Translate::translate( self::$internalEncoding, self::getEncoding($fileUrl), $fileUrl);
    }

    /**
     * Get class for processing current resource
     *
     * @param resource $resource
     * @return string
     */
    static private function _getClassByResource($resource)
    {
        $hash = self::getHashByResource($resource);
        foreach (self::$res as $module => $items) {
            if (!empty($items[$hash])) {
                return $module;
            }
        } 
        return false;
    }
    
   /**
    * Clear cached settings of current resource
    *
    * @param resource $resource
    */
    static private function _delResCache($resource)
    {
        $hash = self::getHashByResource($resource);
        foreach (self::$res as &$items) {
            if (!empty($items[$hash])) {
                unset($items[$hash]);
            }
        } 
    }
    
    /**
     * Cache config for current resource
     *
     * @param resource $resource
     * @param string $module
     * @param string $encoding
     */
    static private function _toResCache($resource, $module, $encoding)
    {
        $hash = self::getHashByResource($resource);
        self::$res[$module][$hash] = array("encoding" => $encoding);
    }
    
    private static function getHashByResource($resource) {
        return get_resource_type($resource) . ":" . (string)(int)$resource;
    }
    
    /**
     * wrapper for file_put_contents
     *
     * @param string $file
     * @param mixed $data
     * @param int $flag
     * @return int
     */
    static function file_put_contents($file, $data, $flag = false) 
    {
        $encodedFileUrl = self::encodeUrl($file);
        $className = self::_getClassNameByUri($file);
        $result = call_user_func_array(array($className, 'file_put_contents'), array($encodedFileUrl, $data, $flag));
        return $result;
    }
    
    /**
     * function for calling logger (if enabled);
     *
     * @param string $message
     */
    static private function _log($message)
    {
       if (is_callable(self::$logger)) {
            call_user_func_array(self::$logger, $message);
        }
        
    }
    /**
     * Set callback function which perform logging 
     *
     * @param string $callback
     */
    static public function setLogger($callback)
    {
        self::$logger = $callback;
    }
    
}
?>