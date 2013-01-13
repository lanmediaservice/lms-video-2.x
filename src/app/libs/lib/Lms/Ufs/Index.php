<?php
class Lms_Ufs_Index {
	static  $shemaList = array(
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
    static private $defaultModule = 'local';
    
    static public function findModule($uri)
    {
    	foreach (self::$shemaList as $partUri => $uriConfig)
        {       
            if (Lms_Text::pos($uri, $partUri) === $uriConfig['pos'])
            {
                return $uriConfig['name'];
            }
        }
        return self::$defaultModule;
    }
}

?>