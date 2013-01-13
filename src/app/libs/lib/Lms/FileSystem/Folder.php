<?php
/**
 * LMS Library
 * 
 * @version $Id: Folder.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007-2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov<webtota@gmail.com>
 * @package FileSystem
 */


/**
 * 
 * @package FileSystem
 */
class Lms_FileSystem_Folder
{
    private $_data = array();
    private $_files;
    private $_folders;
    private $_readed = false;
    
    function __construct($path)
    {
        if ((substr($path, -1)=='/') || (substr($path, -1)=='\\')) {
            $path = substr($path, 0, -1);
        }
        $this->_data['path'] = $path;
    }

    function getParentFolder()
    {
        return dirname($this->getPath());
    }

    function getPath()
    {
        return $this->_data['path'];
    }

    function getSize()
    {
        return 0;
    }

    function getName()
    {
        return basename($this->getPath());
    }

    function getDateCreated()
    {
        
    }

    function getDateLastAccessed()
    {
        
    }

    function getDateLastModified()
    {
        
    }
    
    function getAttribute($attributeName)
    {
        
    }

    function &getFolders()
    {
        if (!$this->_readed) $this->_readFolder(); 
        return $this->_folders;    
    }

    function &getFiles()
    {
        if (!$this->_readed) $this->_readFolder(); 
        return $this->_files;    
    }

    function create($mode = 0777, $recursively = false)
    {
       Lms_Ufs::mkdir($this->getPath(), $mode, $recursively);
    }

    function copy($newDesination)
    {
       Lms_Ufs::copy($this->getPath(), $newDesination);
    }

    function move($newDesination)
    {
       Lms_Ufs::rename($this->getPath(), $newDesination);
    }

    function delete($andSubItems = false)
    {
        if ($andSubItems) {
            $this->_readFolder();            
            $files = $this->getFiles();
            while (Lms_Enumerator::FAIL !== $file = $files->getEach()) {
                $file->delete();
            }
            $folders = $this->getFolders();
            while (Lms_Enumerator::FAIL !== $folder = $folders->getEach()) {
                $folder->delete($andSubItems);
            }
        }
        Lms_Ufs::rmdir($this->getPath());
    }

    function _readFolder()
    {
        $this->_files = new Lms_FileSystem_Files();
        $this->_folders = new Lms_FileSystem_Folders();
        $path = $this->getPath();
        if (Lms_Ufs::is_dir($path)) {
            if ($dh = Lms_Ufs::opendir($path)) {
                while (($file = Lms_Ufs::readdir($dh)) !== false) {
                    if (($file!='.') && ($file!='..')) {
                        $filePath = $path . "/" . $file;
                        if (Lms_Ufs::is_dir($filePath)) {
                            $this->_folders->add(
                                new Lms_FileSystem_Folder($filePath)
                            );
                        } else {
                            $this->_files->add(
                                new Lms_FileSystem_File($filePath)
                            );
                        }
                    }
                }
                Lms_Ufs::closedir($dh);
            }
        }
        $this->_readed = true;        
    }
}