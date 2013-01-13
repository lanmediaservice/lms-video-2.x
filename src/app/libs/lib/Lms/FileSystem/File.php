<?php
/**
 * LMS Library
 * 
 * @version $Id: File.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov<webtota@gmail.com>
 * @package FileSystem
 */

/**
 * 
 * @package FileSystem
 */

class Lms_FileSystem_File
{
    private $_data = array();
    
    function __construct($path)
    {
        $this->_data['path'] = $path;
    }

    function getPath()
    {
        return $this->_data['path'];
    }

    function getSize()
    {
        if (!isset($this->_data['size'])) {
            $this->_data['size'] = Lms_Ufs::filesize($this->getPath());
        }
        return $this->_data['size'];
    }

    function getParentFolder()
    {
        return dirname($this->getPath());
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

    function create()
    {
       Lms_Ufs::fopen($this->getPath(), 'wb');
    }

    function copy($newDesination)
    {
       Lms_Ufs::copy($this->getPath(), $newDesination);
    }

    function move($newDesination)
    {
       Lms_Ufs::rename($this->getPath(), $newDesination);
    }

    function delete()
    {
       Lms_Ufs::unlink($this->getPath());
    }

}
