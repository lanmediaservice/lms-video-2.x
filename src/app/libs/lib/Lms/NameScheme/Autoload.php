<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: Autoload.php 273 2009-12-15 15:08:21Z macondos $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package Lms
 */
 
require_once "PHP/Autoload.php";
require_once "PEAR/NameScheme.php";

class Lms_NameScheme_Autoload {
    private static $loadedFiles;
    private static $excludeRegexp;
    static function classAutoloader($classname) {
        $fname = PEAR_NameScheme::name2path($classname);
        if ($f = @fopen($fname, "r", true)) {
            fclose($f);
            $result = include_once($fname);
            if (!preg_match('{\\\\}i', $classname)) {
                self::$loadedFiles[] = $fname;
            }
            if (class_exists('Lms_Debug', false)) {
                //Lms_Debug::debug("Autoloading... $fname");
            }
            return $result;
        }
        return false;
    }
    
    public static function addExcludeRegexp($regexp)
    {
        self::$excludeRegexp = $regexp;
    }
    
    public static function compileTo($outputFile)
    {
        if (!count(self::$loadedFiles)) {
            return;
        }
        $fp = fopen($outputFile, "a+");
        if (flock($fp, LOCK_EX)) {
            if ($filesize = filesize($outputFile)) {
                fseek($fp, 0);
                $currentFile = fread($fp, $filesize);
            } else {
                $currentFile = '';
            }
            
            if (!$currentFile) {
                $appendSource = "<?php\n";
                $existingClasses = array();
            } else {
                $appendSource = '';
                $existingClasses = self::getClassesFromSource($currentFile);
            }
            for ($i = 0; $i < count(self::$loadedFiles); $i++) {
                $filename = self::$loadedFiles[$i];
                if (self::$excludeRegexp && preg_match(self::$excludeRegexp, $filename)) {
                    continue;
                }
                $f = @fopen($filename, "r", true);
                $fstat = fstat($f);
                $file = fread($f, $fstat['size']);
                fclose($f);
                $classes = self::getClassesFromSource($file);

                if (!count(array_intersect($existingClasses, $classes))) {
                    if (strpos($file, '__FILE__') === false) {
                        Lms_Debug::debug("Complile autoload $filename");
                        $endFile = substr($file, -2) == '?>' ? -2 : null;
                        $appendSource .= ($endFile === null ? substr($file, 5) : substr($file, 5, -2));
                    } else {
                        //Потенциально ненадежно, но работает
                        $filePath = self::realPath($filename);
                        if ($filePath) {
                            Lms_Debug::warn("Complile autoload with __FILE__ constant $filename");
                            $file = str_replace('__FILE__', "'$filePath'", $file);
                            $endFile = substr($file, -2) == '?>' ? -2 : null;
                            $appendSource .= ($endFile === null ? substr($file, 5) : substr($file, 5, -2));
                        }
                    }
                } else {
                    Lms_Debug::debug("Conflict detect on file $filename. Complile autoload terminated.");
                    $appendSource = '';
                    break;
                }
            }
            if ($appendSource) {
                fseek($fp, 0, SEEK_END);
                fwrite($fp, $appendSource);
            }
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
    
    public static function getClassesFromSource($source)
    {
        /*
        //Данный метож требует относительно много памяти
        $classes = array();
        $tokens = token_get_all($source);
        foreach($tokens as $key => $token) {
            if (in_array($token[0], array(T_CLASS, T_INTERFACE))) {
                $classes[] = $tokens[$key+2][1];
            }
        }
        return $classes;
        */
        preg_match_all('{^\s*(class|interface)\s+(.*?)(\s|$)}im', $source, $matches, PREG_PATTERN_ORDER);
        //Lms_Debug::debug(print_r($matches, 1));
        return $matches[2];
        
    }
    
    public static function realPath($relativeFilename)
    {
        // Check for absolute path
        if (realpath($relativeFilename) == $relativeFilename) {
            return $relativeFilename;
        }
        
        // Otherwise, treat as relative path
        $paths = explode(PATH_SEPARATOR, get_include_path());
        foreach ($paths as $path) {
            $path = str_replace('\\', '/', $path);
            $path = rtrim($path, '/') . '/';
            $fullpath = realpath($path . $relativeFilename);
            if ($fullpath) {
                return $fullpath;
            }
        }

        return false;
    }
}

PHP_Autoload::register(array("Lms_NameScheme_Autoload", "classAutoloader"));