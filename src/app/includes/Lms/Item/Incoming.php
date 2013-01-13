<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: User.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Item_Incoming extends Lms_Item_Abstract_Serialized
{
    protected $_serializedFields = array(
        'files',
        'quality',
        'translation',
        'search_results',
        'parsing_url',
        'parsed_info',
        'info'
    ); 
    
    private static $_incomingCache;
    private static $_filesCache;
    private static $_counter;
    
    public static function getTableName()
    {
        return '?_incoming';
    }

    protected function _preUpdate() 
    {
        $changes = Lms_Item_Store::getChanges(
            $this->getTableName(), $this->_scalarPkValue
        );
        if (array_key_exists('name', $changes) && !array_key_exists('last_query', $changes) && !$this->getSearchResults()) {
            $this->setLastQuery($this->getName());
        }
    }    
    
    public function getType()
    {
        return Lms_Application::getType($this->getPath(), $this->getIsDir());
    }
    
    public static function select($offset, $size, $showHidden, &$total)
    {
        $db = Lms_Db::get('main');
        $rows = $db->selectPage(
            $total,
            'SELECT * FROM incoming WHERE `active` {AND `hidden`=?d} ORDER BY `sort` {LIMIT ?d, }{?d}', 
            $showHidden? DBSIMPLE_SKIP : 0, 
            $offset!==null? $offset : DBSIMPLE_SKIP, 
            $size!==null? $size : DBSIMPLE_SKIP
        );
        return Lms_Item_Abstract::rowsToItems($rows);
    }    

    public static function selectAsStruct($offset, $size, $showHidden, &$total)
    {
        $rows = array();
        $items = self::select($offset, $size, $showHidden, $total);
        foreach ($items as $item) {
            $row = array(
                'incoming_id' => $item->getId(),
                'path' => $item->getPath(),
                'basename' => basename($item->getPath()),
                'level' => $item->getLevel(),
                'is_dir' => (bool) $item->getIsDir(),
                'size' => $item->getSize(),
                'expanded' => (bool) $item->getExpanded(),
                'name' => $item->getName(),
                'metainfo' => $item->getCompactMetainfo(),
                'is_searched' => (bool) $item->getSearchResults(),
                'is_result_selected' => (bool) $item->getParsingUrl(),
                'is_parsed' => (bool) $item->getParsedInfo(),
                'is_metainfo_parsed' => (bool) $item->getFiles(),
                'active' => $item->getActive(),
                'hidden' => $item->getHidden(),
                'type' => $item->getType(),
                'is_duplicate' => (bool) $item->isDuplicate(),
            );
            $rows[] = $row;
        }
        return $rows;
    }
    
    public static function getIncomingDetails($incomingId)
    {
        $item = Lms_Item::create('Incoming', $incomingId);
        return array(
            'incoming_id' => $item->getId(), 
            'files' => $item->getCompactFiles($audioTracksCount), 
            'audio_tracks_count' => $audioTracksCount,
            'last_query' => $item->getLastQuery(), 
            'name' => $item->getName(), 
            'search_results' => $item->getSearchResults(), 
            'parsing_url' => $item->getParsingUrl(), 
            'parsed_info' => $item->getParsedInfo(), 
            'info' => $item->getInfo(),
            'duplicates' => $item->getDuplicatesAsStruct(),
            'quality' => $item->getQuality(),
            'translation' => $item->getTranslation(),
        );
    }    
    /*
    public static function getSlice($size, &$total, $prefix = null)
    {
        $db = Lms_Db::get('main');
        $rows = $db->selectPage(
            $total,
            'SELECT * FROM incoming WHERE `active` AND NOT `hidden` AND incoming_file_id>?d AND `level`=?d {AND `path` LIKE ?} ORDER BY `path` LIMIT ?d', 
            $lastId, 
            $level, 
            $prefix? preg_replace('{%}', '\%', $prefix) . "%" : DBSIMPLE_SKIP, 
            $size
        );
        foreach ($rows as $key => $row) {
            if ($row['is_dir'] && $row['expanded']) {
                $addRows = self::getSlice($level + 1, $size, 0, $subTotal, $row['path'] . "/");
                $rows[$key]['total'] = $subTotal;
                $rows = array_merge($rows, $addRows);
            }
        }
        $path = array();
        foreach ($rows as $key => $row) {
            $path[$key]  = $row['path'];
        }
        array_multisort($path, SORT_ASC, $rows);

        return $rows;
    }
    */
    
    public function mergeParsedInfo($data, $engine, $forceMerge = false)
    {
        $this->setParsedInfo($data);
        $currentInfo = $this->getInfo();
        $currentInfo = Lms_Service_Movie::merge($currentInfo, $data, $engine, $forceMerge);
        $this->setInfo($currentInfo);
        return $this;
    }

    public function parseFiles()
    {
        $path = $this->getPath();
        $files =  Lms_Item_File::parseFiles($path);
        $this->setFiles($files)
             ->calcSize();
        return $this;
    }
    
    public function calcSize()
    {
        if ($this->getIsDir() && $files = $this->getFiles()) {
            $size = 0;
            foreach ($files as $file) {
                $size += $file['size'];
            }
            $this->setSize($size);
        }
        return $this;
    }
    
    public function getAudioTracksCount()
    {
        $audioTracksCount = 0;
        $files = $this->getFiles();
        foreach ($files as $file) {
            if (isset($file['metainfo']['audio']['streams'])) {
                if (count($file['metainfo']['audio']['streams']) > $audioTracksCount) {
                    $audioTracksCount = count($file['metainfo']['audio']['streams']);
                }
            }
        }
        return $audioTracksCount;
    }
    
    public static function scanIncoming()
    {
        $db = Lms_Db::get('main');
        
        self::$_incomingCache = $db->selectCol('SELECT MD5(CONCAT(`path`, ":", `size`, ":", `is_dir`)) AS ARRAY_KEY, incoming_id FROM ?_incoming WHERE `active`=1');
        self::$_filesCache = $db->selectCol('SELECT MD5(`path`) AS ARRAY_KEY, file_id FROM ?_files');
        self::$_counter = 0;
//        Lms_Debug::debug(self::$_cache);
        
        $db->query('DELETE FROM ?_incoming WHERE `active`=0');
        
        $directories = Lms_Application::getConfig('incoming', 'root_dirs');
        
        foreach ($directories as $directory) {
            self::scanDirectory($directory, Lms_Application::calcLevel($directory));
        }
        
        foreach (self::$_incomingCache as $incomingId) {
            $db->query('UPDATE incoming SET `active`=0 WHERE incoming_id=?d', $incomingId);
        }
        
        $db->query('UPDATE incoming i INNER JOIN files USING(`path`) SET i.`active`=0');
        $db->query('UPDATE incoming INNER JOIN files_tasks ON(`path`=`from`) SET `active`=0');
        $items = self::select(null, null, true, $total);
        foreach ($items as $item) {
            $item->calcSize()
                 ->save();
        }
        
   }
    
    private static function scanDirectory($directory, $baseLevel)
    {
        //Lms_Debug::debug("scan $directory $baseLevel");
        if (Lms_Application::getConfig('incoming', 'limit') && self::$_counter >= Lms_Application::getConfig('incoming', 'limit')) {
            return;
        }
        $directory = Lms_Application::normalizePath($directory);
        $level = Lms_Application::calcLevel($directory) - $baseLevel;
        $db = Lms_Db::get('main');
        if (Lms_Ufs::is_dir($directory)) {
            if ($dh = Lms_Ufs::opendir($directory)) {
                while (($file = Lms_Ufs::readdir($dh)) !== false) {
                    if (($file!='.') && ($file!='..')) {
                        foreach (Lms_Application::getConfig('incoming', 'ignore_files') as $ignoreFile) {
                            if (preg_match('{^/}', $ignoreFile)) { //$ignoreFile is regular expression
                                if (preg_match($ignoreFile, $file)) {
                                    continue 2;
                                }
                            } else if (strtolower($file) == strtolower($ignoreFile)) {
                                continue 2;
                            }
                        }
                        if (!$file) {
                            continue;
                        }
                        $path = $directory . "/" . $file;
                        $isDir = Lms_Ufs::is_dir($path)? 1 : 0;
                        $size = !$isDir? Lms_Ufs::filesize($path) : '0';
                        $hash = md5("$path:$size:$isDir");
                        $fileHash = md5($path);
                        if (!empty(self::$_filesCache[$fileHash])) {
                            //Lms_Debug::debug("file cache hit $path");
                            continue;
                        }
                        if (Lms_Application::getConfig('incoming', 'limit') && self::$_counter >= Lms_Application::getConfig('incoming', 'limit')) {
                            break;
                        }
                        self::$_counter++;
                        if (!isset(self::$_incomingCache[$hash])) {
                            //Lms_Debug::debug("cache miss $path");
                            $filename = $isDir? $file : pathinfo($file, PATHINFO_FILENAME);
                            $name = self::extractName($filename);

                            $data = array(
                                'path' => $path,
                                'sort' => $path . ($isDir? '/' : ''),
                                'name' => $name,
                                'last_query' => $name,
                                'size' => $size? $size : 0,
                                'level' => $level,
                                'is_dir' => $isDir,
                                'active' => 1,
                            );
                            if ($detectedQuality = self::extractQuality($filename)) {
                                $quality = array();
                                if ($isDir) {
                                    $quality['global'] = $detectedQuality;
                                } else {
                                    $quality[0] = $detectedQuality;
                                }
                                $data['quality'] = serialize($quality);
                            }
                            if ($detectedTranslation = self::extractTranslation($filename)) {
                                $translation = array();
                                if ($isDir) {
                                    $translation['global'] = array($detectedTranslation);
                                } else {
                                    $translation[0] = array($detectedTranslation);
                                }
                                $data['translation'] = serialize($translation);
                            }


                            $update = $data;
                            unset($update['quality']);
                            unset($update['translation']);
                            //unset($update['path']);
                            //unset($update['sort']);
                            unset($update['name']);
                            unset($update['last_query']);
                            $incomingFileId = $db->query('INSERT INTO incoming SET ?a ON DUPLICATE KEY UPDATE incoming_id=LAST_INSERT_ID(incoming_id), ?a', $data, $update);
                        } else {
                            $incomingFileId = self::$_incomingCache[$hash];
                            //Lms_Debug::debug("cache hit $incomingFileId");
                            unset(self::$_incomingCache[$hash]);
                        }
                        if ($isDir && $db->selectCell('SELECT count(*) FROM incoming WHERE `expanded` AND `active` AND NOT `hidden` AND incoming_id=?d', $incomingFileId)) {
                            self::scanDirectory($path, $baseLevel);
                        }
                    }
                }
                Lms_Ufs::closedir($dh);
            }
        }
    }
    
    public function getMainFile()
    {
        $files = $this->getFiles();
        $mainFile = null;
        if ($files) {
            foreach ($files as $n => $file) {
                if (!$mainFile) {
                    $mainFile = $file;
                }
                if ((isset($file['metainfo']['playtime_seconds']) && !isset($mainFile['metainfo']['playtime_seconds']))
                    || ((isset($file['metainfo']['playtime_seconds']) && !isset($mainFile['metainfo']['playtime_seconds']))
                            && $file['metainfo']['playtime_seconds'] > $mainFile['metainfo']['playtime_seconds']
                       )
                ) {
                    $mainFile = $file;
                }
            }
        }
        return $mainFile;
    }
    
    public function getCompactMetainfo()
    {
        $mainFile = $this->getMainFile();
        return isset($mainFile['metainfo'])? Lms_Application::formatMetainfo($mainFile['metainfo']) : null;
    }
    
    
    public function getCompactFiles(&$audioTracksCount = 0)
    {
        $files = $this->getFiles();
        if ($files) {
            $filesIndex = array();
            foreach ($files as $n => &$file) {
                $file['audio_tracks_count'] = isset($file['metainfo']['audio']['streams'])? count($file['metainfo']['audio']['streams']) : 0;
                $file['metainfo'] = Lms_Application::formatMetainfo(isset($file['metainfo'])? $file['metainfo'] : null);
                $filesIndex[$file['path']] = $n;
            }
            foreach ($files as $n => &$file) {
                $parentDirectory = dirname($file['path']);
                $file['parent'] = isset($filesIndex[$parentDirectory])? $filesIndex[$parentDirectory] : null;
            }
            $audioTracksCount = 0;
            foreach ($files as $n => &$file) {
                if ($audioTracksCount < $file['audio_tracks_count']) {
                    $audioTracksCount = $file['audio_tracks_count'];
                }

                if ($file['parent']!==null) {
                    $i = $file['parent'];
                    $parentFile =& $files[$i];
                    if ($parentFile['audio_tracks_count'] < $file['audio_tracks_count']) {
                        $parentFile['audio_tracks_count'] = $file['audio_tracks_count'];
                    }
                }

                if (isset($file['metainfo']['video']) && $file['parent']!==null) {
                    $i = $file['parent'];
                    $parentFile =& $files[$i];
                    $parentFile['has_video'] = true;
                }
            }
        }
        return $files;
    }

    private static function extractName($filename)
    {
        static $freqIndex;
        if (!$freqIndex) {
            $freqIndex = array();
            $db = Lms_Db::get('main');
            $rows = $db->select('SELECT * FROM languages_proto');
            foreach ($rows as $row) {
                $freqIndex[$row['combination']] = $row['freq'];
            }
            $rows = $db->selectCol('SELECT DISTINCT `trigram` FROM `search_trigrams` WHERE `type` =?', 'movie');
            foreach ($rows as $row) {
                $freqIndex[$row] = 0.001;
            }
            
        }
        $text = $filename;
        $text = preg_replace('{\W(?:[PDLO]с?t?\d?|Dub\w*)(\W|$)}i', '$1', $text);
        $text = preg_replace('{s\d+(\W?e\d+)?.*?$}i', '', $text);
        $text = preg_replace('{\W\w+-?rip.*?$}i', '', $text);
        $text = preg_replace('{\W\w+TV.*?$}i', '', $text);
        $text = preg_replace('{\Wxvid.*?$}i', '', $text);
        $text = preg_replace('{\Wx\.?264.*?$}i', '', $text);
        $text = preg_replace('{\Wh\.?264.*?$}i', '', $text);
        $text = preg_replace('{\d+\s*?x\s*?\d+.*?$}i', '', $text);
        $text = preg_replace('{\Wrus.*?$|\Weng.*?$}i', '', $text);

        $text = preg_replace('{epidemz(\.net)?}i', '', $text);
        $text = preg_replace('{lostfilm(\.tv)?}i', '', $text);
        $text = preg_replace('{novafilm(\.tv)?}i', '', $text);

        
        $text = preg_replace('{(\-|\.|\'|\"|_|\(.{0,4}\))}', ' ', $text);
        
        $text = trim($text);
        $text = preg_replace('{^(.{4,})\s+\d{4}$}i', '$1', $text);
        
        $text = preg_replace('{\s+}', ' ', $text);
        $text = trim($text);
        
        $names = preg_split('{[\[\]\(\)]+}i', $text, 3);
        
        usort($names, function ($a, $b) {
            if (strlen($a) < strlen($b)) {
                return 1; 
            } else if (strlen($a) == strlen($b)) {
                return 0; 
            } else {
                return -1; 
            }
        });
        $name = array_shift($names);
        
        if (!preg_match('{s\d+(\W?e\d+)?}i', $filename)) {
            //для сериалов не пытаемся детранслитерировать название
            $name = Lms_Text::autoDetranslit($name, $freqIndex);
        }
        
        return $name;
    }

    private static function extractQuality($name)
    {
        $name = str_replace('_', ' ', $name);
        if (preg_match('{\W([\w\-]+rip|\w+TV|TS)(?:\W|$)}i', $name, $matches)) {
            $quality = $matches[1];
            $quality = str_replace('-', '', $quality);
            $quality = str_replace('TS', 'TeleSync', $quality);
            
            $quality = str_ireplace(
                Lms_Application::getConfig('quality_options'), 
                Lms_Application::getConfig('quality_options'), 
                $quality
            );
            
            return $quality;
        }
        return null;
    }    

    private static function extractTranslation($name)
    {
        $name = str_replace('_', ' ', $name);
        $translation = null;

        if (preg_match('{\W(Sub)(?:\W|$)}i', $name)) {
            $translation = "Субтитры";
        }

        if (preg_match('{\W(O|Orig\w*)(?:\W|$)}i', $name)) {
            $translation = "Оригинал";
        }
        if (preg_match('{\WL(?:\W|$)}i', $name)) {
            $translation = "Любительский";
        }
        if (preg_match('{\WL(\d)\W}i', $name, $matches)) {
            switch ($matches[1]) {
                case '1':
                    $translation = "Любительский одноголосый";
                    break;
                case '2':
                    $translation = "Любительский двухголосый";
                    break;
            }
        }
        if (preg_match('{\WP(?:\W|$)}i', $name)) {
            $translation = "Профессиональный многоголосый";
        }
        if (preg_match('{\WP(\d)\W}i', $name, $matches)) {
            switch ($matches[1]) {
                case '1':
                    $translation = "Профессиональный одноголосый";
                    break;
                case '2':
                    $translation = "Профессиональный двухголосый";
                    break;
            }
        }

        if (preg_match('{\W(VO)(?:\W|$)}i', $name)) {
            $translation = "Одноголосый";
        }
        if (preg_match('{\W(AVO)(?:\W|$)}i', $name)) {
            $translation = "Авторский одноголосый";
        }
        if (preg_match('{\W(MVO)(?:\W|$)}i', $name)) {
            $translation = "Многоголосый закадровый";
        }
        if (preg_match('{\W(DVO)(?:\W|$)}i', $name)) {
            $translation = "Двухголосый закадровый";
        }
        
        if (preg_match('{\W(D|Dub\w*)(?:\W|$)}i', $name)) {
            $translation = "Дубляж";
        }
        if (preg_match('{\WD([\[\(]TS.+|[\[\(]T.+|t)(?:\W|$)}i', $name)) {
            $translation = "Дубляж (звук из TS)";
        }
        if (preg_match('{\WD([\[\(]TS.+|[\[\(]C.+|c)(?:\W|$)}i', $name)) {
            $translation = "Дубляж (звук из CamRip)";
        }
        return $translation;
    }    
    
    public function isDuplicate()
    {
        $names = array();
        if ($this->getName()) {
            $names[] = $this->getName();
        }
        if ($info = $this->getInfo()) {
            if (!empty($info['name'])) {
                $names[] = $info['name'];
            }
            if (!empty($info['international_name'])) {
                $names[] = $info['international_name'];
            }
        }
        if (!$names) {
            return false;
        }
        $isDuplicate = $this->_slaveDb->selectCell(
            'SELECT count(*) FROM movies WHERE international_name IN (?a) OR `name` IN (?a)',
            $names, $names
        );
        
        return $isDuplicate;
    }

    public function getDuplicates()
    {
        $names = array();
        if ($this->getName()) {
            $names[] = $this->getName();
        }
        if ($info = $this->getInfo()) {
            if (!empty($info['name'])) {
                $names[] = $info['name'];
            }
            if (!empty($info['international_name'])) {
                $names[] = $info['international_name'];
            }
        }
        
        return Lms_Item_Movie::selectByNames($names);
    }

    public function getDuplicatesAsStruct()
    {
        $items = $this->getDuplicates();
        $result = array();
        foreach ($items as $item) {
            $result[] = array(
                'movie_id' => $item->getId(),
                'name' => $item->getName(),
                'international_name' => $item->getInternationalName(),
                'year' => $item->getYear(),
            );
        }
        return $result;
    }
}