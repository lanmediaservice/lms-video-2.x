<?php
/**
 * LMS2
 * 
 * Класс предоставляет набор методов для операций с файловой сиситемой при локальном доступе
 * 
 * @version $Id: Local.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov<webtota@gmail.com>
 */

class Lms_Ufs_Local
{

    
    public static function urlToPath($fileUrl)
    {
        if (Lms_Text::pos($fileUrl, 'file:///localhost/') === 0)
        {
            $filePath = Lms_Text::replace("\\", '/', Lms_Text::replace('file:///localhost/', '', $fileUrl));
        }
        elseif (Lms_Text::pos($fileUrl, 'file://localhost/') === 0)
        {
            $filePath = Lms_Text::replace("\\", '/', Lms_Text::replace('file://localhost/', '', $fileUrl));
        }
        elseif (Lms_Text::pos($fileUrl, 'file:///') === 0)
        {
            $filePath = Lms_Text::replace("\\", '/', Lms_Text::replace('file:///', '', $fileUrl));
        }
        elseif (Lms_Text::pos($fileUrl, 'file://') === 0)
        {
            $filePath = Lms_Text::replace("\\", '/', Lms_Text::replace('file://', '', $fileUrl));
        }
        else
        {
            $filePath = $fileUrl;
        }
        if ($filePath[Lms_Text::length($filePath) - 1] == '/')
        {
            $filePath = Lms_Text::substring($filePath, 0, strlen($filePath) - 1);
        }
        return $filePath;
    }

    
    public static function touch($fileUrl, $time, $atime)
    {
        $filePath = self::urlToPath($fileUrl);
        if(!$time) {
            $time = time();
        }
        if($atime) {
            return touch($filePath, $time, $atime);
        } else {
            return touch($filePath, $time);
        }
    }
    
    public static function chmod($fileUrl, $mode)
    {
        $filePath = self::urlToPath($fileUrl);
        return chmod($filePath, $mode);
    }
    
    public static function file_exists($fileUrl)
    {
        $filePath = self::urlToPath($fileUrl);
        return file_exists($filePath);
    }

    public static function opendir($fileUrl)
    {
        $filePath = self::urlToPath($fileUrl);
        return opendir($filePath);
    }

    public static function readdir($resource)
    {
        return readdir($resource);
    }

    public static function rmdir($fileUrl)
    {
        $filePath = self::urlToPath($fileUrl);
        return rmdir($filePath);
    }

    public static function fgets($resource, $length)
    {
        return fgets($resource, $length);
    }

    public static function fread($resource, $length)
    {
        return fread($resource, $length);
    }

    public static function fseek($resource, $offset, $whence)
    {
        return fseek($resource, $offset, $whence);
    }

    public static function rewinddir($resource)
    {
        rewinddir($resource);
    }

    public static function ftell($resource)
    {
        return ftell($resource);
    }

    public static function closedir($resource)
    {
        closedir($resource);
    }

    public static function fclose($resource)
    {
        fclose($resource);
    }

    public static function feof($resource)
    {
        return feof($resource);
    }

    public static function ftruncate($resource, $size)
    {
        return ftruncate($resource, $size);
    }

    public static function rewind($resource)
    {
        return rewind($resource);
    }

    public static function copy($sourceUrl, $destinationUrl)
    {
        $sourcePath = self::urlToPath($sourceUrl);
        $destinationPath = self::urlToPath($destinationUrl);
        $res = copy($sourcePath, $destinationPath);
        if(function_exists('exec')) {
            if (!$res && !(Lms_Text::uppercase(Lms_Text::substring(PHP_OS, 0, 3)) === 'WIN'))
            {
                exec("cp -r ".escapeshellarg($sourcePath)." ".escapeshellarg($destinationPath));
                return true;
            }
        } else {
            throw new Exception("Can't perform shell command. Probably 'exec' is disabled in your php.ini file");
        }
        return $res;
    }

    public static function fwrite($resource, $string, $length = -1)
    {
        if ($length == -1)
            return fwrite($resource, $string);
        else
            return fwrite($resource, $string, $length);
    }

    public static function fopen($fileUrl, $mode, $use_include_path)
    {
        $filePath = self::urlToPath($fileUrl);
        return fopen($filePath, $mode, $use_include_path);
    }

    public static function filemtime($fileUrl)
    {
        $filePath = self::urlToPath($fileUrl);
        $res = filemtime($filePath);
        if (!$res && !(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'))
        {
            if(function_exists('exec')){
                if (strtoupper(PHP_OS) === 'LINUX') 
                {
                    $res = exec('stat -c %Y '.escapeshellarg($filePath));
                }
                elseif(strtoupper(PHP_OS) === 'FREEBSD') 
                {
                    $res = exec('stat -f %m '.escapeshellarg($filePath));
                }
            } else {
                throw new Exception("Can't perform shell command. Probably 'exec' is disabled in your php.ini file");
            }
        }
        return $res;
    }

    public static function unlink($fileUrl)
    {
        $filePath = self::urlToPath($fileUrl);
        clearstatcache();
        return unlink($filePath);
    }
    
    public static function is_writable($fileUrl)
    {
        $filePath = self::urlToPath($fileUrl);
        return is_writable($filePath);
    }

    public static function mkdir($fileUrl, $mode = 0770, $recursively = false)
    {
        $filePath = self::urlToPath($fileUrl);
        if(file_exists($filePath) && is_dir($filePath)) {
        	//trigger_error("Can not create directory. Directory at " . $filePath . " allready exists", E_USER_WARNING);
        	return false;
        }
        return mkdir($filePath, $mode, $recursively);
    }

    public static function rename($sourceUrl, $destinationUrl)
    {
        $sourcePath = self::urlToPath($sourceUrl);
        $destinationPath = self::urlToPath($destinationUrl);
        if(file_exists($destinationPath) && is_dir($destinationPath)) {
        	//trigger_error("Can not rename directory. The distination directory at " . $destinationPath . " allready exists", E_USER_WARNING);
        	return false;
        }
        return rename($sourcePath, $destinationPath);
    }

    public static function is_dir($fileUrl)
    {
        $filePath = self::urlToPath($fileUrl);
        return is_dir($filePath);
//            worked with files more than 4Gb, but if will not work try this
//            return (('d' == substr(exec("ls -dl ".escapeshellarg($fileUrl)), 0, 1)) ? true : false);
    }

    public static function is_file($fileUrl)
    {
        $filePath = self::urlToPath($fileUrl);
        return is_file($filePath);
//            worked with files more than 4Gb, but if will not work try this
//            return (('d' == substr(exec("ls -dl ".escapeshellarg($fileUrl)), 0, 1)) ? true : false);
    }

    public static function is_readable($fileUrl)
    {
        $filePath = self::urlToPath($fileUrl);
        return is_readable($filePath);
    }
    
    public static function filesize($fileUrl)
    {
        static $cache = array();
        if (self::is_dir($fileUrl)) return 0;
        if ((PHP_INT_SIZE > 4) || @Lms_Ufs::$config['disable_4gb_support']) {//Для систем с битностью больше 32 размер файла больше 4 Гб определяется корректно
            return filesize(self::urlToPath($fileUrl));
        } 
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            if (class_exists("COM")){
                $fsobj = new COM("Scripting.FileSystemObject");
                $ofile = $fsobj->GetFile(self::urlToPath($fileUrl));
                $size = floatval($ofile->Size);
            }else{
                throw new Exception("There is no COM Object. Can't get real filesize for files more than 4 Gb");
                //$size = filesize(self::urlToPath($fileUrl)); 
            }
        }else{
            $file = self::urlToPath($fileUrl);
            $dir  = dirname($file);
            $list = array();
            if(function_exists('exec')) {
                if (!isset($cache[$file])) {
                    exec("ls -l ".escapeshellarg($dir) . '/', $list);
                    $files = self::_ParseUnixList($list, @Lms_Ufs::$config['ls_dateformat_in_iso8601']);
                    foreach ($files as $v){
                        $cache[$dir."/".$v['name']] = $v['size'];
                    }
                }
                $size = $cache[$file];
            } else {
                throw new Exception("Can't perform shell command. Probably 'exec' is disabled in your php.ini file");
            }
        }
        return $size;
    }

    private static function _ParseUnixList($list, $iso8601 = false)
    {
        $dirEntries = array();
        $countChunks = $iso8601? 7 : 8;
        foreach ($list as $line) {
            if (($chunks = self::_SplitToChunks($line, $countChunks)) && count($chunks)>=$countChunks) {
                // fill dir entry
                $dirEntry = array();
                $dirEntry["is_dir"] = ($chunks[0]{0} == 'd');
                $dirEntry["is_link"] = ($chunks[0]{0} == 'l');
                $dirEntry["size"] = $chunks[4];
                if ($dirEntry["size"] < 0) $dirEntry["size"] = 4294967296 + $dirEntry["size"];
                if ($iso8601) {
                    $dirEntry["date"] = $chunks[5];
                    $dirEntry["time"] = $chunks[6];
                    $dirEntry["name"] = $chunks[7];
                } else {
                    $isTime = Lms_Text::pos($chunks[7], ":") != false;
                    $dirEntry["date"] = ($isTime ? date("Y") : $chunks[7]) . "-" . date("m-d", strtotime($chunks[5] . " " . $chunks[6]));
                    $dirEntry["time"] = $isTime ? $chunks[7] : "00:00";
                    $dirEntry["name"] = $chunks[8];
                }
                if ($dirEntry["is_link"]) {
                    $dirEntry["name"] = preg_replace('# \-> .*?$#i', '', $dirEntry["name"]);
                    preg_match('# \-> (.*?)$#i', $line, $matches);
                    $dirEntry["link"] = $matches[1];
                }
                $dirEntries[] = $dirEntry;
            }
        }
        return $dirEntries;
    }
    
    private static function _SplitToChunks($str, $chunksLimit)
    {
        $chunks = array();
        while (($chunksLimit-- > 0) && (($pos = Lms_Text::pos($str, " ")) !== false)) {
            $chunks[] = Lms_Text::substring($str, 0, $pos);
            $str = ltrim(Lms_Text::substring($str, $pos));
        }
        $chunks[] = $str;
        return $chunks;
    }
    
    public static function file_put_contents($file, $data, $flag = false)
    {
        $filePath = self::urlToPath($file);
        if(!$flag) {
            file_put_contents($filePath, $data);
        } else {
            file_put_contents($filePath, $data, $flag);
        }
        return true;
    }
}