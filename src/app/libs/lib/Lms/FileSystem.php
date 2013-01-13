<?php
/**
 * LMS Library
 * 
 * @version $Id: FileSystem.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007-2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov<webtota@gmail.com>
 * @package FileSystem
 */

/**
 * Static class for misc filesystem functions.
 * @package FileSystem
 */
 
class Lms_FileSystem
{
    public static function isDir($path)
    {
        return Lms_Ufs::is_dir($path); 
    }

    public static function isFile($path)
    {
        return Lms_Ufs::is_file($path); 
    }

    public static function getFolder($path)
    {
        return new Lms_FileSystem_Folder($path);
    }

    public static function getFile($path)
    {
        return new Lms_FileSystem_File($path);
    }

    public static function fileExists($path)
    {

    }

    public static function createFile($path)
    {
        $file = new Lms_FileSystem_File($path);
        $file->create();
        return $file;
    }

    public static function createFolder($path, $mode = 0777, $recursively = false)
    {
        $folder = new Lms_FileSystem_Folder($path);
        $folder->create($mode, $recursively);
        return $folder;
    }

    public static function openFile($path)
    {
    }

    public static function copy($sourcePath, $destinationPath)
    {
        Lms_Ufs::copy($sourcePath, $destinationPath); 
    }

    public static function move($sourcePath, $destinationPath)
    {
        Lms_Ufs::rename($sourcePath, $destinationPath); 
    }

    public static function deleteFile($path)
    {
        $file = new Lms_FileSystem_File($path);
        $file->delete();
    }

    public static function deleteFolder($path)
    {
        $folder = new Lms_FileSystem_Folder($path);
        $folder->delete(false);
    }

    public static function deleteThree($path)
    {
        $folder = new Lms_FileSystem_Folder($path);
        $folder->delete(true);
    }
    
  
}