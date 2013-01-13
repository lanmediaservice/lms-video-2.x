<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: Wrapper.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007-2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov<webtota@gmail.com>
 */

/**
 * @package Ufs
 */
class Lms_Ufs_Wrapper extends Lms_Modular
{
    private $moduleFileNamePreffix;
    private $php5    = true;
    private $plugins = array(
                        ':\\'                => array('pos' => 1, 'name' => 'local'),
                        ':/'                 => array('pos' => 1, 'name' => 'local'),
                        "/"                  => array('pos' => 0, 'name' => 'local'),
                        "smb://"             => array('pos' => 0, 'name' => 'smb'),
                        "ftp://"             => array('pos' => 0, 'name' => 'ftp'),
                        "file://"            => array('pos' => 0, 'name' => 'local'),
                        "file:///"           => array('pos' => 0, 'name' => 'local'),
                        "file://localhost/"  => array('pos' => 0, 'name' => 'local'),
                        "file:///localhost/" => array('pos' => 0, 'name' => 'local'),
                        );
    private  $config = array(
                        'encoding' => 'UTF-8',
                        'c:/tmp/' => 'CP1251',
                        'c:/tmp/pp/' => 'UTF-8',
                        'c:/tmp/pp/122' => 'KOI8-R',
                        );// все в кодировке utf8
    private $encoding  = 'CP1251';
    private $res       = array();
    private $logger    = '';
    private $log_level = 'debug';
    
    public function __construct($logger = null, $log_level = 'debug')
    {
        $ver = explode('.', phpversion());
        $ver_num = $ver[0].$ver[1].$ver[2];
        if ($ver_num < 500)
        {
            $this->php5 = false;
        }
        if ($logger != null)
        {
            $this->logger = $logger;
        }
        $this->log_level = $log_level;
    }

    public function addConfig($path, $params)
    {
        $this->config[$path] = $params;
    }

    public function setInternalEncoding($encoding)
    {
        $this->encoding = $encoding;
    }
    
    public function mkdir($dirUrl, $mode = 0777, $recursively = false)
    {
        $decodedDirUrl = $this->decodeUrl($dirUrl);

        if ($fs = $this->_getPlugin($decodedDirUrl)) {
            return $fs->mkdir($decodedDirUrl, $mode, $recursively);
        }
    }
    
    public function rmdir($dirUrl)
    {
        $decodedDirUrl = $this->decodeUrl($dirUrl);

        if ($fs = $this->_getPlugin($decodedDirUrl)) {
            return $fs->rmdir($decodedDirUrl);
        }

    }
    
    public function touch($file, $time = 0, $atime = 0)
    {
        $start = microtime();
        
        $_file = trim($file);
        $enc = $this->_getFileEncoding($_file);
        $_file = Lms_Text::convertEncoding($this->config['encoding'], $enc, $_file);
        if ($_file == NULL)
        {
            return false;
        }
        $_time = ($time == 0) ? time() : $time;
        $_atime = ($atime == 0) ? time() : $atime;
        $fs = $this->_getPlugin($_file);
        if ($fs == NULL)
        {
            return false;
        }
        $_file = $fs->get_name();
        if (!$fs->touch($_file, $_time, $_atime))
        {
            throw new Lms_Exception("Command touch for '$file' couldn't be completed.");
            return false;
        }
        
        $end = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command touch for '$file' completed in $time seconds.", 'time');
        
        return true;
    }
    
    public function file_exists($fileUrl)
    {
        $decodedFileUrl = $this->decodeUrl($fileUrl);

        if ($fs = $this->_getPlugin($decodedFileUrl)) {
            $result = $fs->file_exists($decodedFileUrl);
            return $result;
        }       
    }
    
    
    public function opendir($dirUrl)
    {
        $decodedDirUrl = $this->decodeUrl($dirUrl);

        if ($fs = $this->_getPlugin($decodedDirUrl)) {
            $result = $fs->opendir($decodedDirUrl);
            $this->_toResCache($decodedDirUrl, $result, 'opendir');
            return $result;
        }
    }
    
    public function fopen($fileUrl, $mode, $use_include_path = false)
    {
        $decodedFileUrl = $this->decodeUrl($fileUrl);
        $res = $this->_fromResCache($decodedFileUrl, 'fopen');
        if ($res != NULL)
        {
            $result = $res;
        }
        else
        {
            $fs = $this->_getPlugin($decodedFileUrl);
            if ($fs == NULL)
            {
                return false;
            }

            $result = $fs->fopen($decodedFileUrl, $mode, $use_include_path);
            
            $this->_toResCache($decodedFileUrl, $result, 'fopen');
        }

        return $result;
    }
    
    public function readdir($resource)
    {
        $result = false;

        $dir = $this->_fromResCache($resource, 'opendir');
        if ($dir == NULL)
        {
            return false;
        }
        
        $fs = $this->_getPlugin($dir);
        if ($fs == NULL)
        {
            return false;
        }
        
        $result = $fs->readdir($resource);
        if ($result === false)
        {
            return false;
        }
        $encodedUrl = $this->decodeUrl($result, $dir);
        
        return $encodedUrl;
    }
    
    public function fgets($resource, $length = 1024)
    {
        $start = microtime();
        
        $result = false;

        $file = $this->_fromResCache($resource, 'fopen');
        if ($file == NULL)
        {
            return false;
        }
        
        $fs = $this->_getPlugin($file);
        if ($fs == NULL)
        {
            return false;
        }
        
        $result = $fs->fgets($resource, $length);
        
        $end  = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command fgets for '$resource' completed in $time seconds.", 'time');
        
        return $result;
    }
    
    public function fread($resource, $length)
    {
        $start = microtime();
        
        $result = false;

        $file = $this->_fromResCache($resource, 'fopen');
        if ($file == NULL)
        {
            return false;
        }
        
        $fs = $this->_getPlugin($file);
        if ($fs == NULL)
        {
            return false;
        }
        
        $result = $fs->fread($resource, $length);
        
        $end  = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command fread for '$resource' completed in $time seconds.", 'time');
        
        return $result;
    }
    
    public function ftell($resource)
    {
        $start = microtime();
        
        $result = false;

        $file = $this->_fromResCache($resource, 'fopen');
        if ($file == NULL)
        {
            return false;
        }
        
        $fs = $this->_getPlugin($file);
        if ($fs == NULL)
        {
            return false;
        }
        
        $result = $fs->ftell($resource);
        if ($result === false)
        {
            return false;
        }
        
        $end  = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command ftell for '$resource' completed in $time seconds.", 'time');
        
        return $result;
    }
    
    public function ftruncate($resource, $size)
    {
        $start = microtime();
        
        $result = false;

        $file = $this->_fromResCache($resource, 'fopen');
        if ($file == NULL)
        {
            return false;
        }
        
        $fs = $this->_getPlugin($file);
        if ($fs == NULL)
        {
            return false;
        }
        
        $result = $fs->ftruncate($resource, $size);
        if ($result === false)
        {
            return false;
        }
        
        $end  = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command ftruncate for '$resource' completed in $time seconds.", 'time');
        
        return $result;
    }
    
    public function fwrite($resource, $string, $length = -1)
    {
        $start = microtime();
        
        $result = false;

        $file = $this->_fromResCache($resource, 'fopen');
        if ($file == NULL)
        {
            return false;
        }
        
        $fs = $this->_getPlugin($file);
        if ($fs == NULL)
        {
            return false;
        }
        
        $result = $fs->fwrite($resource, $string, $length);
        if ($result === false)
        {
            return false;
        }
        
        $end  = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command fwrite for '$resource' completed in $time seconds.", 'time');
        
        return $result;
    }
    
    public function fseek($resource, $offset, $whence = SEEK_SET)
    {
        $start = microtime();
        
        $result = 1;

        $file = $this->_fromResCache($resource, 'fopen');
        if ($file == NULL)
        {
            return 1;
        }
        
        $fs = $this->_getPlugin($file);
        if ($fs == NULL)
        {
            return 1;
        }
        
        $result = $fs->fseek($resource, $offset, $whence);

        $end  = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command fseek for '$resource' completed in $time seconds.", 'time');
        
        return $result;
    }
    
    public function rewind($resource)
    {
        $start = microtime();
        
        $result = false;

        $file = $this->_fromResCache($resource, 'fopen');
        if ($file == NULL)
        {
            return false;
        }
        
        $fs = $this->_getPlugin($file);
        if ($fs == NULL)
        {
            return false;
        }
        
        $result = $fs->rewind($resource);
        if ($result === false)
        {
            return false;
        }
        $end = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command rewind for '$resource' completed in $time seconds.", 'time');
        
        return $result;
    }
    
    public function copy($sourceUrl, $destinationUrl)
    {
        $decodedSourceUrl = $this->decodeUrl($sourceUrl);
        $decodedDestinationUrl = $this->decodeUrl($destinationUrl);

        if ($sourceFs = $this->_getPlugin($decodedSourceUrl)) {
            $destFs = $this->_getPlugin($decodedDestinationUrl);
            if ($sourceFs==$destFs) {
                return $sourceFs->copy($decodedSourceUrl, $decodedDestinationUrl);
            } else {
                throw new Lms_Exception("Copy between different filesystems not available");
            }
        }
    }

    public function rename($sourceUrl, $destinationUrl)
    {
        $decodedSourceUrl = $this->decodeUrl($sourceUrl);
        $decodedDestinationUrl = $this->decodeUrl($destinationUrl);

        if ($sourceFs = $this->_getPlugin($decodedSourceUrl)) {
            $destFs = $this->_getPlugin($decodedDestinationUrl);
            if ($sourceFs==$destFs) {
                return $sourceFs->rename($decodedSourceUrl, $decodedDestinationUrl);
            } else {
                throw new Lms_Exception("Rename files between different filesystems not available");
            }
        }
    }
        
    public function feof($resource)
    {
        $start = microtime();
        
        $result = false;

        $file = $this->_fromResCache($resource, 'fopen');
        if ($file == NULL)
        {
            return false;
        }
        
        $fs = $this->_getPlugin($file);
        if ($fs == NULL)
        {
            return false;
        }
        
        $result = $fs->feof($resource);
        if ($result === false)
        {
            return false;
        }
        
        $end  = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command feof for '$resource' completed in $time seconds.", 'time');
        
        return $result;
    }
    
    public function closedir($resource)
    {
        $dir = $this->_fromResCache($resource, 'opendir');
        if ($dir == NULL)
        {
            return false;
        }
        
        $fs = $this->_getPlugin($dir);
        if ($fs == NULL)
        {
            return false;
        }
        
        $this->_delResCache($dir, 'opendir');
        $fs->closedir($resource);
        return true;
        
    }
    
    public function fclose($resource)
    {
        $start = microtime();
        
        $file = $this->_fromResCache($resource, 'fopen');
        if ($file == NULL)
        {
            return false;
        }
        
        $fs = $this->_getPlugin($file);
        if ($fs == NULL)
        {
            return false;
        }
        
        $this->_delResCache($file, 'fopen');
        $fs->fclose($resource);
        
        $end = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command fclose for '$resource' completed in $time seconds.", 'time');
        
        return true;
    }
    
    public function rewinddir($resource)
    {
        $start = microtime();
        
        $dir = $this->_fromResCache($resource, 'opendir');
        if ($dir == NULL)
        {
            return false;
        }
        
        $fs = $this->_getPlugin($dir);
        if ($fs == NULL)
        {
            return false;
        }
        
        $fs->rewinddir($resource);
        
        $end = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command rewinddir for '$file' completed in $time seconds.", 'time');
        
        return true;
    }
    
    public function filemtime($file)
    {
        $start = microtime();
        
        $result = false;

        $_file = trim($file);
        $enc   = $this->_getFileEncoding($_file);
        $_file = Lms_Text::convertEncoding($this->config['encoding'], $enc, $_file);
        if ($_file == NULL)
        {
            return false;
        }
        $res = NULL;
        $res = $this->_fromCache($_file, 'filemtime');
        if ($res == NULL)
        {
            $fs = $this->_getPlugin($_file);
            if ($fs == NULL)
            {
                return false;
            }
            $_file  = $fs->get_name();
            $result = $fs->filemtime($_file);
            if ($result == false)
            {
                throw new Lms_Exception("Command filemtime for '$file' couldn't be completed.");
            }
            else
            {
                $this->_toCache($_file, 'filemtime', $result);
            }
        }
        else
        {
            $result = $res;
        }

        $end  = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command filemtime for '$file' completed in $time seconds.", 'time');
        
        return $result;
    }
    
    public function is_writable($file)
    {
        $start = microtime();
        
        $result = false;

        $_file = trim($file);
        $enc = $this->_getFileEncoding($_file);
        $_file = Lms_Text::convertEncoding($this->config['encoding'], $enc, $_file);
        if ($_file == NULL)
        {
            return false;
        }
        $res = NULL;
        $res = $this->_fromCache($_file, 'is_writable');
        if ($res == NULL)
        {
            $fs = $this->_getPlugin($_file);
            if ($fs == NULL)
            {
                return false;
            }
            $_file = $fs->get_name();
            $result = $fs->is_writable($_file);
            if ($result == false)
            {
                $this->_log("Command is_writable for '$file' couldn't be completed.", 'error');
            }
            else
            {
                $this->_toCache($_file, 'is_writable', $result);
            }
        }
        else
        {
            $result = $res;
        }

        $end = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command is_writable for '$file' completed in $time seconds.", 'time');
        
        return $result;
    }
    
    public function filesize($fileUrl)
    {
        $decodedFileUrl = $this->decodeUrl($fileUrl);

        if ($fs = $this->_getPlugin($decodedFileUrl)) {
            return $fs->filesize($decodedFileUrl);
        }
    }
    
    public function is_dir($fileUrl)
    {
        $decodedFileUrl = $this->decodeUrl($fileUrl);

        if ($fs = $this->_getPlugin($decodedFileUrl)) {
            return $fs->is_dir($decodedFileUrl);
        }
    }

    public function is_file($fileUrl)
    {
        $decodedFileUrl = $this->decodeUrl($fileUrl);

        if ($fs = $this->_getPlugin($decodedFileUrl)) {
            return $fs->is_file($decodedFileUrl);
        }
    }
    
    public function is_readable($fileUrl)
    {
        $decodedFileUrl = $this->decodeUrl($fileUrl);

        if ($fs = $this->_getPlugin($decodedFileUrl)) {
            return $fs->is_readable($decodedFileUrl);
        }
    }
    
    
    public function unlink($fileUrl)
    {
        $decodedFileUrl = $this->decodeUrl($fileUrl);

        if ($fs = $this->_getPlugin($decodedFileUrl)) {
            return $fs->unlink($decodedFileUrl);
        }
    }
    
    public function clearstatcache()
    {
        $start = microtime();
        
        if (file_exists('./cache'))
        {
            $dh = opendir('./cache');
            while ($obj = readdir($dh))
            {
                if($obj != '.' && $obj != '..')
                {
                    if (is_file('./cache/'.$obj))
                    {
                        unlink('./cache/'.$obj);
                    }
                }
            }
        }
        
        $end = microtime();
        $time = $end - $start;
        $time = $time < 0 ? 0 : $time;
        
        $this->_log("Command clearstatcache completed in $time seconds.", 'time');
    }
    
    public function decodeUrl($fileUrl, $urlForDetectEncoding = false){
        $enc = $this->_getFileEncoding($urlForDetectEncoding? $urlForDetectEncoding : $fileUrl);
        return $this->_convertEncoding($this->encoding, $enc, $fileUrl);
    }

    public function encodeUrl($fileUrl, $urlForDetectEncoding = false){
        $enc = $this->_getFileEncoding($urlForDetectEncoding? $urlForDetectEncoding : $fileUrl);
        return Lms_Text::convertEncoding($enc, $this->encoding, $fileUrl);
    }

    private function _fromResCache($object, $what)
    {
        if (isset($this->res["$what"]))
        {
            if (is_string($object))
            {
                if (isset($this->res["$what"]["$object"]))
                {
                    $this->_log("'$what' for '$object' loaded from resource cache.", 'debug');
                    return $this->res["$what"]["$object"];
                }
                else
                {
                    $this->_log("'$what' for '$object' not found in resource cache.", 'debug');
                    return NULL;
                }
            }
            elseif(is_resource($object))
            {
                $res = $this->res["$what"];
                foreach ($res as $key => $value)
                {
                    if ($value === $object)
                    {
                        return $key;
                    }
                }
                $this->_log("Can't load '$object' from resource cache.", 'debug');
                return NULL;
            }
            else
            {
                $this->_log("Can't load '$object' from resource cache.", 'debug');
                return NULL;
            }
        }
        else
        {
            $this->_log("'$what' not exists in resource cache.", 'debug');
            return NULL;
        }
    }
    
    private function _delResCache($object, $what)
    {
        if (isset($this->res["$what"]))
        {
            if (is_string($object))
            {
                if (isset($this->res["$what"]["$object"]))
                {
                    $this->_log("'$what' for '$object' removed from resource cache.", 'debug');
                    unset($this->res["$what"]["$object"]);
                    return;
                }
                else
                {
                    $this->_log("'$what' for '$object' not found in resource cache.", 'debug');
                    return;
                }
            }
            elseif(is_resource($object))
            {
                $res = $this->res["$what"];
                foreach ($res as $key => $value)
                {
                    if ($value === $object)
                    {
                        $this->_log("'$what' for '$object' removed from resource cache.", 'debug');
                        unset($key);
                        return;
                    }
                }
                $this->_log("Can't load '$object' from resource cache.", 'debug');
                return;
            }
            else
            {
                $this->_log("Can't load '$object' from resource cache.", 'debug');
                return;
            }
        }
        else
        {
            $this->_log("'$what' not exists in resource cache.", 'debug');
            return;
        }
    }
    
    private function _toResCache($object1, $object2, $what)
    {
        //TODO may be only by name???
        $byName = true;
        if (is_string($object1) && is_resource($object2))
        {
            $byName = true;
        }
        elseif(is_string($object2) && is_resource($object1))
        {
            $byName = false;
        }
        else
        {
            throw new Lms_Exception("Can't save '$object1' - '$object2' to resource cache.");
            return;
        }
        if($byName)
        {
            if (isset($this->res[$what]))
            {
                $this->res[$what] = array_merge($this->res[$what], array("$object1" => $object2));
            }
            else
            {
                $this->res = array_merge($this->res, array("$what" => array("$object1" => $object2)));
            }
        }
    }
    
    private function _fromCache($file, $what)
    {
        return;
        if (!file_exists('./cache/'.md5($file.'-'.$what)))
        {
            $this->_log("'$what' not cached for '$file' file.", 'debug');
            return NULL;
        }
        else
        {
            $this->_log("Reading '$what' for '$file' file from cache...", 'debug');
            return unserialize(file_get_contents('./cache/'.md5($file.'-'.$what)));
        }
    }
    
    private function _toCache($file, $what, $data)
    { 
        return;
        $this->_log("Saving '$what' for '$file' file to cache...", 'debug');
        $data = serialize($data);
        return $this->_file_put_contents('./cache/'.md5($file.'-'.$what), $data);
    }
    
    private function _file_put_contents($file, $data) 
    {
        if ($this->php5)
        {
            return file_put_contents($file, $data);
        }
        
        $f = @fopen($file, 'w');
        if ($f === false) 
        {
            return 0;
        } 
        else 
        {
            if (is_array($data)) 
                $data = implode($data);
            $bytes_written = fwrite($f, $data);
            fclose($f);
            return $bytes_written;
        }
    }
    
    private function _convertEncoding($from, $to, $string)
    {
        return $string;
        if (function_exists('iconv'))
        {
            $res = iconv($from, $to, $string);
            if (!$res)
            {
                throw new Lms_Exception("Unknown encoding '$from' or '$to'!");
                return NULL;
            }
            return $res;
        }
        else
        {
            if (strtolower($to) == 'utf-8')
            {
                return Lms_Text::convert2utf($string, $from);
            }
            else
            {
                $_from = Lms_Text::getEncSymbol($from);
                $_to = Lms_Text::getEncSymbol($to);
                if ($_from == NULL || $_to == NULL)  
                {
                    return NULL;
                }
            }
        }
    }
    
    private function _getFileEncoding($file)
    { 
        $tmp = array();
        foreach($this->config as $key=>$value)
        {
            $keyt = $key;
            $keyt = Lms_Text::replace("\\", '/', $key);
            if ($keyt[strlen($keyt) - 1] == '/')
            {
                $keyt = substr($keyt, 0, strlen($keyt) - 1);
            }
            if (Lms_Text::pos($file, $keyt) === 0)
            {
                $tmp[$keyt] = $value;
                if ($keyt == $file)
                {
                    return $value;
                }
            }
        }
        $path = explode('/', $file);
        $i = count($path);
        while ($i > 0)
        {
            $f = '';
            for ($j = 0; $j < $i; $j++)
            {
                $f = $f.$path[$j]."/";
            }
            foreach($tmp as $key=>$value)
            {
                if ($key == $f || Lms_Text::substring($keyt, 0, Lms_Item::length($f) - 1) == $key)
                {
                    return $value;
                }
            }                    
            $i--;
        }                      
        
        return 'UTF-8';
    }
    

    private function _getPlugin($file)
    {
        static $pluginsCache = array();
        foreach ($this->plugins as $str => $plugin)
        {       
            if (Lms_Text::pos($file, $str) === $plugin['pos'])
            {
                $module = $plugin['name'];
                if (!isset($pluginsCache[$module])){
                    if ($class_name = $this->loadModule($module)) {
                         $pluginsCache[$module] = new $class_name();
                    }
                }
                return $pluginsCache[$module];
            }
        }
        throw new Lms_Exception('Plugin for "'.$file.'" not found!');
        return false;
    }
    
    private function _log($message, $level)
    {
        if ($this->logger !== '')
        {
            $logger = $this->logger;
            if ($this->log_level === 'debug')
            {
                $logger($message, $level);
            }
            elseif($this->log_level === 'error')
            {
                if ($level === 'error')
                {
                    $logger($message, $level);
                }
            }
            elseif($this->log_level === 'time')
            {
                if ($level === 'time' || $level === 'error')
                {
                    $logger($message, $level);
                }
            }
        }
        else
        {
            if ($this->log_level === 'debug')
            {
                echo "<strong>".$level."</strong>: ".$message."<br />\r\n";
            }
            elseif($this->log_level === 'error')
            {
                if ($level === 'error')
                {
                    echo "<strong>".$level."</strong>: ".$message."<br />\r\n";
                }
            }
            elseif($this->log_level === 'time')
            {
                if ($level === 'time' || $level === 'error')
                {
                    echo "<strong>".$level."</strong>: ".$message."<br />\r\n";
                }
            }
        }
    }
}  
?>