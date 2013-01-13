<?php

class Lms_MetaParser {
    /**
     * Instance of Lms_Logable
     *
     * @var object
     */
    private static $_logInstance;
    
    /**
     * Config Array
     *
     * @var array
     */
    private static $_config = array (
        "defaultDemuxer" => "getID3",
        "bigFileSizeDemuxers" => array(//order by priority desc
                "getID3",
                "mplayer",
                "ffmpeg"
         ),
        "demuxers" => array(//order by priority desc
                "getID3",
                "mplayer"//,
                //"ffmpeg"
        ),
        "fileExtensions" => array(//demuxers are ordered by priorities desc
                                   
             "getID3" => array(
                                 "avi", "flv", "vob", "ogg", "wma", "wmv", "asf", "wav", "aac", "vqf", "rm"
                                ),
             "mplayer"=> array(
                                    "avi", "flv", "iso", "mov", "mkv", "mp4", "vob", "asf", "wma", "wmv", "rm", "qt", "vivo", "fli", "film", "roq"
                                ),

             "ffmpeg"=> array(
                                    "avi", "flv", "iso", "mov", "mkv", "mp4", "vob"
                                )

        ),
        "priority" => array(
                
                            ),
        "instances" => array(    
            "getID3" => '',//here must be an object                    
            "mplayer"=> '',//here must be an object                    
            "ffmpeg" => ''//here must be an object                    
            )                       
    );
    /**
     * Set instances of demuxers to config. 
     * You should set instance of demuxer to config array before using demuxer
     * @param object $instances
     */
    public static function setInstances($instances)
    {
        if (is_array($instances)) { 
            foreach ($instances as $key=>$value) {
            	self::$_config['instances'][$key] = $value;
            }
        } else { 
            throw new Lms_Exception("Can't set Instances. The type of param must be an array."); 
        } 
    }
    
    /**
     * Set parameter to config array
     *
     * @param unknown_type $param
     * @param unknown_type $value
     */
    public static function  setConfigParam($param, $value)
    {
        if (isset(self::$_config[$param])) {
            self::$_config[$param] = $value;
        }
    }  
    
    /**
     * Add parameter to configArray
     *
     * @param unknown_type $param
     * @param unknown_type $value
     */
    
    public static function addConfigParam($param, $value)
    {
       self::$_config[$param] = $value;
    }
    
    /**
     * Set order of demxers in which they will be applied
     *
     */
    public static function setDemuxersOrder()
    {
        $demuxers = func_get_args();
        self::setConfigParam("demuxers", $demuxers);    
    }
    
    
    /**
     * Set priority of demuxers for extensions getted by first param  
     *
     * @param unknown_type $extensions
     * @param unknown_type $demuxers
     */
    public static function setDemuxersPriority($extensions, $demuxers)
    {
        if(!is_array($demuxers)) {
            $demuxers = array($demuxers);
        }
        if (is_array($extensions)) {
            foreach ($extensions as $extension) {
                self::$_config['priority'][$extension] = $demuxers;
            }
        } else {
            self::$_config['priority'][$extensions] = $demuxers;
        }
    }
    /**
     * Set $extensions which can be parsed by $demuxer 
     *
     * @param string $demuxer
     * @param unknown_type $extensions
     */
    public static function setSupportedExtensions($demuxer, $extensions)
    {
        self::$_config['fileExtensions'][$demuxer] = $extensions;    
    }
    
    /**
     * Parse url
     *
     * @param string $url
     * @return unknown
     */
    static public function parseUrl($url)
    {
        $fileSize = Lms_Ufs::filesize($url);//Для удалённых файлов не отработает т.к. нет модулей UFS_HTTP, UFS_FTP ...
        $filePath = Lms_Ufs::getAbsolutePath($url);
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $demuxer = self::_getDemuxer($fileSize, $extension);
        $localtempfilename = false;
        $info = array();
        if ($demuxer) {
            if (self::_isRemote($url)) {
                if ($fp_remote = fopen($url, 'rb')) {
                    $localtempfilename = tempnam('/tmp', 'getID3');
                    if ($fp_local = fopen($localtempfilename, 'wb')) {
                        $buffer = '';
                        while (!feof($fp_remote) && (strlen($buffer) < 32768)) {
                            $buffer .= fread($fp_remote, 8192); 
                        }
                        fwrite($fp_local, $buffer);
                        fclose($fp_local);
                        self::_log("Localize $filePath to $localtempfilename");
                        $filePath = $localtempfilename;
                    }
                    fclose($fp_remote);
                }
            }
            do{
                $curAdapter = Lms_Modular::loadModule('Adapter_' . ucfirst($demuxer), true);
                if (!$curAdapter) {
                    throw new Lms_Exception("Can't get instance of Adapter_" . ucfirst($demuxer));
                }
                if (isset(self::$_config['instances'][$demuxer]) && is_object(self::$_config['instances'][$demuxer])) {
                    $instance = self::$_config['instances'][$demuxer];
                    try{
                        $next = false;
                        self::_log("Parse $filePath with $demuxer");
                        $info = call_user_func(array($curAdapter, 'analyze'), $instance, $filePath, $fileSize);
                    } catch (Lms_Exception $e) {
                        self::_log("$demuxer failed");
                        if ($nextDemuxer = self::_getNextDemuxer($fileSize, $extension, $demuxer)) {
                            $next = true;
                            $demuxer = $nextDemuxer;
                        } else {
                            $next = false;
                        }
                    }
                
                } else {
                    self::_log("You must get an instance of demuxer: $demuxer before parsing url! ");
                    if($nextDemuxer = self::_getNextDemuxer($fileSize, $extension, $demuxer)) {
                            $next = true;
                            $demuxer = $nextDemuxer;
                    } else {
                            $next = false;
                    }
                }
            } while ($next);
            if ($localtempfilename) {
                self::_log("Unlink $localtempfilename");
                unlink($localtempfilename);
                $localtempfilename = false;        
            }
            return $info;
        } else {
            throw new Lms_Exception("There is no demuxer to parse this extension: " .$extension);
        }
    }
    
    /**
     * Get next demuxer in the demuxer's list
     *
     * @param int $fileSize
     * @param string $extension
     * @param string $previous
     * @return string
     */
    static private function _getNextDemuxer($fileSize, $extension, $previous)
    {
        if (in_array($extension, self::$_config['priority'])) {
            for ($i=0 ; $i < count(self::$_config['priority'][$extension]); $i++) { 
                if ( self::$_config['priority'][$extension][$i] == $previous) {
                    if(isset(self::$_config['priority'][$extension][$i + 1])) {
                        return self::$_config['priority'][$extension][$i + 1];
                    } else {
                        return false;
                    }       
                }
            }
            
        }
        if ($fileSize > pow(2, 31)) {
            $demuxers = self::$_config['bigFileSizeDemuxers'];
            for ($i = 0; $i < count($demuxers); $i++) {
                if($demuxers[$i] == $previous) {
                    if (isset($demuxers[$i+1])) {
                        $demuxer = $demuxers[$i+1];
                    } else {
                        return false;
                    }
                }
            }
            if (in_array($extension, self::$_config['fileExtensions'][$demuxer])) {
                return $demuxer;
            } else {
                throw new Lms_Exception("The extension: $extension of file you are parsing is not assign to demuxer: $demuxer");
            }
        } else {
            
            $demuxers = self::$_config['demuxers'];
            for ($i = 0; $i < count($demuxers); $i++) {
                if($demuxers[$i] == $previous) {
                    if (isset($demuxers[$i+1])) {
                        $demuxer = $demuxers[$i+1];
                    } else {
                        return false;
                    }
                }
            }
            if (in_array($extension, self::$_config['fileExtensions'][$demuxer])) {
                return $demuxer;
            } else {
                throw new Lms_Exception("The extension: $extension of file you are parsing is not assign to demuxer: $demuxer");
            }
            
        }  
    }
    
    /**
     * Check whether a file is remote
     *
     * @param unknown_type $url
     * @return unknown
     */
    static private function _isRemote($url)
    {
        return (bool)((Lms_Text::pos($url, "http://") !== false) || (Lms_Text::pos($url, "ftp://") !== false)); 
    }
    
    /**
     * Get demuxer for file of current size and with current extension 
     *
     * @param int $fileSize
     * @param string $extension
     * @return string
     */
    static private function _getDemuxer($fileSize, $extension)
    {
        $priorities = self::$_config['priority'];
        if (array_key_exists($extension, $priorities)) {
            $demuxer = self::$_config['priority'][$extension][0];
            return $demuxer;
        }
        if ($fileSize > pow(2, 31)) {
            $demuxers = self::$_config['bigFileSizeDemuxers'];
            for ($i = 0; $i < count($demuxers); $i++) {
                if (in_array($extension, self::$_config['fileExtensions'][$demuxers[$i]])) {
                    return $demuxers[$i];
                }
            }
        } else {
            $demuxers = self::$_config['demuxers'];
            for ($i = 0; $i < count($demuxers); $i++) {
                if (in_array($extension, self::$_config['fileExtensions'][$demuxers[$i]])) {
                    return $demuxers[$i];
                }
            }
        } 
        return (isset(self::$_config['defaultDemuxer']))? self::$_config['defaultDemuxer'] : false;
    }
    
    /**
     * Log message
     *
     * @param string $message
     */
    static private function _log($message)
    {
        if(!is_object(self::$_logInstance)) {
            self::$_logInstance = new Lms_Logable();
        }   
        $loger = self::$_logInstance;
        $loger->log($message);
    }
    
    /**
     * Set function that will be used for loging;
     *
     * @param unknown_type $callback
     */
    static public function setLogger($callback)
    {
        if(!is_object(self::$_logInstance)) {
            self::$_logInstance = new Lms_Logable();
        }   
        $loger = self::$_logInstance;
        $loger->setLogger($callback);
    }
}