<?php

class Lms_Item_File extends Lms_Item_Abstract_Serialized {
    
    protected $_serializedFields = array(
        'metainfo',
        'frames',
        'translation',
    ); 
    
    public static function getTableName()
    {
        return '?_files';
    }
    
    protected function _preDelete() 
    {
        $this->getChilds('Linkator_FileMovie')->delete(); 
        $this->clearFrames();
    } 
    
    public function clearFrames() 
    {
        $frames = $this->getFrames();
        
        if (is_array($frames)) {
            foreach ($frames as $frame) {
                if (is_file($frame)) {
                    Lms_Debug::debug("Delete frame $frame");
                    unlink($frame);
                }
            }
        }
        $this->setFrames(null);
    } 
    
    public static function selectMovieFiles($movieId)
    {
        $db = Lms_Db::get('main');
        $rows = $db->select("SELECT f.* FROM files f INNER JOIN movies_files USING(file_id) WHERE movie_id=?d ORDER BY path", $movieId);
        return Lms_Item_Abstract::rowsToItems($rows);
    }

    public static function selectWithoutFrames($limit)
    {
        $db = Lms_Db::get('main');
        $rows = $db->select("SELECT * FROM files WHERE `active` AND is_dir=0 AND frames IS NULL AND LENGTH(metainfo) AND `path` NOT IN(SELECT `to` FROM `files_tasks`) ORDER BY file_id DESC LIMIT ?d", $limit);
        return Lms_Item_Abstract::rowsToItems($rows);
    }

    public static function selectWithoutMetainfo($limit)
    {
        $db = Lms_Db::get('main');
        $rows = $db->select("SELECT * FROM files WHERE `active` AND is_dir=0 AND metainfo IS NULL  AND `path` NOT IN(SELECT `to` FROM `files_tasks`) ORDER BY file_id DESC LIMIT ?d", $limit);
        return Lms_Item_Abstract::rowsToItems($rows);
    }
    
    public static function selectWithoutTthHash($limit)
    {
        $db = Lms_Db::get('main');
        $rows = $db->select("SELECT * FROM ?_files WHERE `active` AND is_dir=0 AND tth_hash LIKE '' AND `path` NOT IN(SELECT `to` FROM `files_tasks`) ORDER BY `tries`, RAND() LIMIT ?d", $limit);
        return Lms_Item_Abstract::rowsToItems($rows);
    }

    public static function selectByPath($path)
    {
        $db = Lms_Db::get('main');
        $rows = $db->select("SELECT * FROM ?_files WHERE `path` LIKE ?", $path);
        return Lms_Item_Abstract::rowsToItems($rows);
    }

    public static function parseFiles($path)
    {
        $files = array();
        self::scanFiles($path, Lms_Application::calcLevel($path), $files);
        $mplayer = Lms_Application::getMplayer();
        Lms_MetaParser::setLogger(function($this, $message, $caller) {
            Lms_Debug::debug($message);
        });
        Lms_MetaParser::setInstances(array("mplayer" => $mplayer));
        Lms_MetaParser::setConfigParam("defaultDemuxer", "mplayer");
        Lms_MetaParser::setDemuxersOrder("mplayer");
        Lms_MetaParser::setDemuxersPriority("", array("mplayer"));
        foreach ($files as &$file) {
            if ($file['type']=='video') {
                $file['metainfo'] = Lms_MetaParser::parseUrl($file['path']);
            }
        }
        return $files;
    }
    
    private static function scanFiles($path, $baseLevel, &$files)
    {
        $path = Lms_Application::normalizePath($path);
        $isDir = (bool) Lms_Ufs::is_dir($path);
        $level = Lms_Application::calcLevel($path) - $baseLevel;
        $basename = basename($path);
        foreach (Lms_Application::getConfig('metaparser', 'ignore_files') as $ignoreFile) {
            if (preg_match('{^/}', $ignoreFile)) { //$ignoreFile is regular expression
                if (preg_match($ignoreFile, $basename)) {
                    return;
                }
            } else if (strtolower($basename) == strtolower($ignoreFile)) {
                return;
            }
        }
        if ($level>Lms_Application::getConfig('metaparser', 'max_deep')) {
            return;
        }
        $file = array(
            'path' => $path,
            'basename' => $basename,
            'size' => !$isDir? Lms_Ufs::filesize($path) : null,
            'level' => $level,
            'is_dir' => $isDir,
            'type' => Lms_Application::getType($path, $isDir)
        );
        $files[] = $file;
        if ($isDir) {
            if ($dh = Lms_Ufs::opendir($path)) {
                while (($file = Lms_Ufs::readdir($dh)) !== false) {
                    if (($file!='.') && ($file!='..')) {
                        self::scanFiles($path . "/" . $file, $baseLevel, $files);
                    }
                }
                Lms_Ufs::closedir($dh);
            }
        }
    }
    
    public function parseMetainfo()
    {
        if (Lms_Ufs::is_file($this->getPath())) {
            $mplayer = Lms_Application::getMplayer();
            Lms_MetaParser::setLogger(function($this, $message, $caller) {
                Lms_Debug::debug($message);
            });
            Lms_MetaParser::setInstances(array("mplayer" => $mplayer));
            Lms_MetaParser::setConfigParam("defaultDemuxer", "mplayer");
            Lms_MetaParser::setDemuxersOrder("mplayer");
            Lms_MetaParser::setDemuxersPriority("", array("mplayer"));
            $metainfo = Lms_MetaParser::parseUrl($this->getPath());
            $this->setMetainfo($metainfo); 
        } else {
            $this->setMetainfo(null); 
        }
        return $this;
    }
    
    public function generateFrames($count = null)
    {
        if (!$count) {
            $count = Lms_Application::getConfig('frames', 'count');
        }
        $metainfo = $this->getMetainfo();
        $this->clearFrames();
        if (Lms_Ufs::is_file($this->getPath()) && isset($metainfo['playtime_seconds'])) {
            $playtime = $metainfo['playtime_seconds'];
            $mplayer = Lms_Application::getMplayer();
            $path = Lms_Ufs::encodeUrl($this->getPath());
            $frames = $mplayer->getFrames($path, $playtime, $count);
            $relativeTargetDir = 'media/frames/' . implode("/", str_split(substr($this->getId(), 0, 2))) . "/" . $this->getId();
            $targetDir = dirname(APP_ROOT) . "/" . $relativeTargetDir;
            Lms_FileSystem::createFolder($targetDir, 0777, true);
            foreach ($frames as &$frame) {
                $relativeTargetFile = $relativeTargetDir . "/" . basename($frame);
                $targetFile = $targetDir . "/" . basename($frame);
                rename($frame, $targetFile);
                $frame = $relativeTargetFile;
            }
            $this->setFrames($frames);
        }
        return $this;
    }
    
    public function calcTthHash()
    {
        $this->setTries($this->getTries()+1)
             ->save();
        
        if (Lms_Ufs::is_file($this->getPath())) {
            Lms_Debug::debug("Calc TTH (#" . $this->getId() . ")");
            $path = Lms_Ufs::encodeUrl($this->getPath());
            if ($bin = Lms_Application::getConfig('files', 'tth', 'bin')) {
                $cmd = "$bin " . escapeshellarg($path) . " | awk '{print $1}'";
                $tthSum = exec($cmd, $res, $ret);
                if ($ret!=0) {
                    throw new Lms_Exception("Command $cmd return $ret");
                }                
            } else {
                $tthSum = Lms_Hash_Tth::getTTH($path);
            }
            if (strlen($tthSum)==39) {
                $this->setTthHash($tthSum)
                     ->save();
            } else {
                throw new Lms_Exception('Failed calc TTH: ' . $tthSum);
            }
        } else {
            throw new Lms_Exception('File ' . $this->getPath() . ' not found');
        }
        
        return $this;
    }
    
    public function getDownloadLink()
    {
        $path = Lms_Application::normalizePath($this->getPath());
        $link = $path;
        foreach (Lms_Application::getConfig('download', 'masks') as $mask) {
            if (stripos($link, $mask['source'])!==false) {
                $link = str_ireplace($mask['source'], $mask['download'], $link);
                break;
            }
        }
        if ($encoding = Lms_Application::getConfig('download', 'escape', 'encoding')) {
            $link = Lms_Translate::translate('CP1251', $encoding, $link);
        }
        $isIE = false;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $isIE = preg_match('/MSIE/i', $_SERVER['HTTP_USER_AGENT']) && !preg_match('/(opera|gecko)/i', $_SERVER['HTTP_USER_AGENT']);
        }
        if (Lms_Application::getConfig('download', 'escape', 'enabled') && (!$isIE || Lms_Application::getConfig('download', 'escape', 'ie'))) {
            $t = explode("/", $link);
            for ($i=3; $i<count($t); $i++) {
                $t[$i] = rawurlencode($t[$i]);
            }
            $link = implode("/", $t);
        }
        
        return $link;
    }
    
    public function getLinks()
    {
        $user = Lms_User::getUser();
        $path = Lms_Application::normalizePath($this->getPath());
        $links = array();
        $downloadEnabled = Lms_Application::getConfig('download', 'selectable', 'download') || Lms_Application::getConfig('download', 'defaults', 'download');
        if ($downloadEnabled) {
            if (Lms_Application::getConfig('download', 'license')) {
                $v = Lms_Application::getLeechProtectionCode(array($this->getId(), $user->getId()));
                $links['license'] = "download.php?u=" . $user->getId() . "&f=" . $this->getId() . "&v=$v";
            } else {
                $links['download'] = $this->getDownloadLink();
            }
        }
        $tthEnabled = Lms_Application::getConfig('download', 'selectable', 'dcpp') || Lms_Application::getConfig('download', 'defaults', 'dcpp');
        if ($tthEnabled && $this->getTthHash()) {
            $links['dcpp'] = "magnet:?xt=urn:tree:tiger:" . $this->getTthHash() . "&xl=" . $this->getSize() . "&dn=" . rawurlencode(basename($path));
        }
        return $links;
    }
    
    public static function selectMovieFilesAsStruct($movieId)
    {
        $user = Lms_User::getUser();
        $items = Lms_Item_File::selectMovieFiles($movieId);
        
        $files = array();
        foreach ($items as $item) {
            $path = Lms_Application::normalizePath($item->getPath());
            $files[$path] = array(
                'file_id' => $item->getId(),
                'name' => $item->getName(),
                'size' => $item->getSize(),
                'is_dir' => (bool)$item->getIsDir(),
                'links' => $item->getLinks(),
                'metainfo' => $item->getMetainfo()? Lms_Application::formatMetainfo($item->getMetainfo()) : null,
                'quality' => $item->getQuality(),
                'translation' => $item->getTranslation(),
                'path' => $path,
                'childs' => array()
            );
        }
        ksort($files);
        
        $rootPathOfThisTree = 0;
        foreach ($files as $path => &$file) {
            if (!$rootPathOfThisTree) {
                $rootPathOfThisTree = $path;
                $level = 0;
            } else {
                $rootFile = $files[$rootPathOfThisTree];
                if (Lms_Application::isParentDirectory($rootPathOfThisTree, $file['path'])) {
                    $level = Lms_Application::calcLevel($file['path']) - Lms_Application::calcLevel($rootPathOfThisTree);
                } else {
                    $level = 0;
                    $rootPathOfThisTree = $path;
                }
            }
            $file['level'] = $level;
            
            $parentDirectory = dirname($file['path']);
            if (isset($files[$parentDirectory])) {
                $files[$parentDirectory]['childs'][] = $file['path'];
            }
        }
        
        foreach ($files as $parentFile) {
            foreach ($parentFile['childs'] as $childPath) {
                $child =& $files[$childPath];
                if (is_array($parentFile['translation'])) {
                    foreach ($parentFile['translation'] as $num => $translation) {
                        if (empty($child['translation'][$num])) {
                            $child['translation'][$num] = $translation;
                        }
                    }
                }
                if ($parentFile['quality']) {
                    if (empty($child['quality'])) {
                        $child['quality'] = $parentFile['quality'];
                    }
                }
            }
        }        
        
        return array_values($files);
    }

}
