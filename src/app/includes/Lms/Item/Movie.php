<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: User.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Item_Movie extends Lms_Item_Abstract_Serialized
{
    protected $_serializedFields = array(
        'trailer',
        'translation',
    );
    
    public static function getTableName()
    {
        return '?_movies';
    }

    protected function _preInsert() 
    {
        if (!$this->getCreatedAt()) {
            $this->setCreatedAt(date('Y-m-d H:i:s'));
        }
        if (!$this->getUpdatedAt()) {
            $this->setUpdatedAt(date('Y-m-d H:i:s'));
        }
        if (!$this->getCreatedBy()) {
            $this->setCreatedBy(Lms_User::getUser()->getId());
        }
    }
    
    protected function _postInsert() 
    {
        $this->updateSearchIndex();
    }    

    protected function _preUpdate() 
    {
        $changes = Lms_Item_Store::getChanges(
            $this->getTableName(), $this->_scalarPkValue
        );
        if (array_key_exists('name', $changes)
            || array_key_exists('international_name', $changes)
        ) {
            $this->updateSearchIndex();
        }
    }
    
    
    protected function _preDelete() 
    {
        foreach ($this->getChilds('File') as $item) {
            $item->delete(); 
        }
        foreach ($this->getChilds('Linkator_GenreMovie') as $item) {
            $item->delete(); 
        }
        foreach ($this->getChilds('Linkator_CountryMovie') as $item) {
            $item->delete(); 
        }
        foreach ($this->getChilds('Bookmark') as $item) {
            $item->delete(); 
        }
        foreach ($this->getChilds('Comment') as $item) {
            $item->delete(); 
        }
        foreach ($this->getChilds('Participant') as $item) {
            $item->delete(); 
        }
        foreach ($this->getChilds('Rating') as $item) {
            $item->delete(); 
        }
        
        $db = Lms_Db::get('main');
        $db->query('DELETE FROM search_trigrams WHERE `type`=? AND `id`=?d', 'movie', $this->getId());
        $db->query('DELETE FROM suggestion WHERE `type`=? AND `id`=?d', 'movie', $this->getId());
    } 
    
    public static function createFromInfo($info, $files, $quality, $translation)
    {
        $movie = Lms_Item::create('Movie');
        //translation 	quality 	
        //present_by 	group 	
        $movie->setName($info['name'])
              ->setInternationalName($info['international_name']?: Lms_Text::translit($info['name']))
              ->setDescription(isset($info['description'])? $info['description'] : '')
              ->setYear($info['year'])
              ->setMpaa(isset($info['mpaa'])? $info['mpaa'] : '')
              ->setCovers($info['poster'])
              ->setTrailer(isset($info['trailer'])? $info['trailer'] : null)
              ->setTrailerLocalized(null)
              ->setQuality(isset($quality['global'])? $quality['global'] : '')
              ->setTranslation(isset($translation['global'])? $translation['global'] : '')
              ->setHidden(Lms_Application::getConfig('incoming', 'hide_import')? 1 : 0)
              ->save();
        
        foreach ($info['countries'] as $name) {
            $item = Lms_Item_Country::getByNameOrCreate($name);
            $movie->add($item);
        }

        foreach ($info['genres'] as $name) {
            $item = Lms_Item_Genre::getByNameOrCreate($name);
            $movie->add($item);
        }
        
        $participantsIndex = array();
        foreach ($info['persones'] as $person) {
            if (array_filter($person['names']) || $person['url']) {
                $personItem = Lms_Item_Person::getByMiscOrCreate($person['names'], $person['url']);
                if (!empty($person['url']) && !$personItem->getUrl()) {
                    $personItem->setUrl($person['url'])
                               ->save();
                }
                $roleItem = Lms_Item_Role::getByNameOrCreate($person['role']);
                
                if (isset($participantsIndex[$movie->getId()][$roleItem->getId()][$personItem->getId()])) {
                    Lms_Debug::warn("Participant " . $movie->getId() . " - " . $roleItem->getId() . " - " . $personItem->getId() . " already exists");
                    continue;
                }
                
                $item = Lms_Item::create('Participant');
                $item->setMovieId($movie->getId())
                    ->setRoleId($roleItem->getId())
                    ->setPersonId($personItem->getId());
                if (isset($person['character'])) {
                    $item->setCharacter($person['character']);
                }
                $item->save();
                $participantsIndex[$movie->getId()][$roleItem->getId()][$personItem->getId()] = true;
            }
            //$item = Lms_Item_Genre::getByNameOrCreate($name);
            //$movie->add($item);
        }
        $item = Lms_Item::create('Rating');
        $item->setSystem('local');
        $movie->add($item);
        
        if (isset($info['imdb_id']) || isset($info['rating_imdb_value'])) {
            $item = Lms_Item::create('Rating');
            $item->setSystem('imdb');
            if (isset($info['imdb_id'])) {
                $item->setSystemUid($info['imdb_id']);
            }
            if (isset($info['rating_imdb_count'])) {
                $item->setCount($info['rating_imdb_count']);
            }
            if (isset($info['rating_imdb_value'])) {
                $item->setValue($info['rating_imdb_value']);
            }
            $movie->add($item);
        }
        
        if (isset($info['kinopoisk_id']) || isset($info['rating_kinopoisk_value'])) {
            $item = Lms_Item::create('Rating');
            $item->setSystem('kinopoisk');
            if (isset($info['kinopoisk_id'])) {
                $item->setSystemUid($info['kinopoisk_id']);
            }
            if (isset($info['rating_kinopoisk_count'])) {
                $item->setCount($info['rating_kinopoisk_count']);
            }
            if (isset($info['rating_kinopoisk_value'])) {
                $item->setValue($info['rating_kinopoisk_value']);
            }
            $movie->add($item);
        }
        
        $targetStorage = Lms_Application::getTargetStorage();
        if ($targetStorage) {
            $directory = $info['international_name']? $info['international_name'] : $info['name'];
            $directory .= " ({$info['year']})";
            $directory = Lms_Text::safeFilename($directory);
            $postfix = false;
            while (Lms_Ufs::is_dir($targetPath = $targetStorage . '/' . $directory . ($postfix? "_$postfix" : ''))) {
                if (!$postfix) {
                    $postfix = 2;
                } else {
                    $postfix++;
                }
            }
            Lms_Ufs::mkdir($targetPath, Lms_Application::getConfig('filesystem', 'permissions', 'directory'), true);
            $topFile = $files[0];
            if (Lms_Ufs::is_dir($topFile['path'])) {
                $sourcePath = $topFile['path'];
            } else {
                $sourcePath = dirname($topFile['path']);
                $fileItem = Lms_Item::create('File');
                $fileItem->setName(basename($targetPath))
                         ->setIsDir(1)
                         ->setPath($targetPath);
                $movie->add($fileItem);
            }
        }
        
        foreach ($files as $n => &$file) {
            if (isset($quality[$n])) {
                $file['quality'] = $quality[$n];
            }
            if (isset($translation[$n])) {
                $file['translation'] = $translation[$n];
            }
            if ($targetStorage) {
                if (Lms_Application::isWindows()) {
                    $path = str_ireplace($sourcePath, $targetPath, $file['path']);
                } else {
                    $path = str_replace($sourcePath, $targetPath, $file['path']);
                }
                Lms_Item_FileTask::create($file['path'], $path);
                $file['path'] = $path;
                $file['basename'] = basename($path);
                $file['active'] = 0;
            } else {
                $file['active'] = 1;
            }
        }
        
        $movie->updateFilesByStruct($files);
        
        return $movie;
    }
    
    public function updateFilesByStruct($files)
    {
        $movieFiles = $this->getChilds('File');
        $movieFilesIndex = array();
        foreach ($movieFiles as $fileItem) {
            $movieFilesIndex[$fileItem->getPath()] = $fileItem->getId();
        }
            
        foreach ($files as $file) {
            if (!isset($movieFilesIndex[$file['path']])) {
                $fileItem = Lms_Item::create('File');
                $fileItem->setName($file['basename'])
                         ->setIsDir($file['is_dir'])
                         ->setPath($file['path'])
                         ->setSize($file['size']? $file['size'] : 0)
                         ->setMetainfo(isset($file['metainfo'])? $file['metainfo'] : null)
                         ->setQuality(isset($file['quality'])? $file['quality'] : '')
                         ->setTranslation(isset($file['translation'])? $file['translation'] : '');
                if (isset($file['active'])) {
                    $fileItem->setActive($file['active']);
                }
                $this->add($fileItem);
            } else {
                $fileId = $movieFilesIndex[$file['path']];
                $fileItem = Lms_Item::create('File', $fileId);
                if (!$fileItem->getIsDir() && $fileItem->getSize()!=$file['size']) {
                    $fileItem->setMd5Hash('')
                             ->setTthHash('');
                    $fileItem->clearFrames();
                }
                $fileItem->setIsDir($file['is_dir'])
                         ->setSize($file['size']? $file['size'] : 0)
                         ->setMetainfo(isset($file['metainfo'])? $file['metainfo'] : null);
                $fileItem->save();
                unset($movieFilesIndex[$file['path']]);
            }
        }
        //удаляем старые файлы, которые должны были быть в списке files
        foreach ($movieFilesIndex as $path => $fileId) {
            foreach ($files as $file) {
                if (Lms_Application::isParentDirectory($file['path'], $path)) {
                    $fileItem = Lms_Item::create('File', $fileId);
                    $fileItem->delete();
                    break;
                }
            }
        }
            
    }
    
    public function resetInfo($info)
    {
        foreach ($this->getChilds('Linkator_GenreMovie') as $item) {
            $item->delete(); 
        }
        foreach ($this->getChilds('Linkator_CountryMovie') as $item) {
            $item->delete(); 
        }
        foreach ($this->getChilds('Participant') as $item) {
            $item->delete(); 
        }
        foreach ($this->getChilds('Rating') as $item) {
            if ($item->getSystem()!='local') {
                $item->delete(); 
            }
        }
        
        $this->setName($info['name'])
              ->setInternationalName(isset($info['international_name'])? $info['international_name'] : Lms_Text::translit($info['name']))
              ->setDescription(isset($info['description'])? $info['description'] : '')
              ->setYear($info['year'])
              ->setMpaa(isset($info['mpaa'])? $info['mpaa'] : '')
              ->setCovers($info['poster'])
              ->setTrailer(isset($info['trailer'])? $info['trailer'] : null)
              ->setTrailerLocalized(null)
              ->save();
        
        foreach ($info['countries'] as $name) {
            $item = Lms_Item_Country::getByNameOrCreate($name);
            $this->add($item);
        }

        foreach ($info['genres'] as $name) {
            $item = Lms_Item_Genre::getByNameOrCreate($name);
            $this->add($item);
        }
        
        $participantsIndex = array();
        foreach ($info['persones'] as $person) {
            if (array_filter($person['names']) || $person['url']) {
                $personItem = Lms_Item_Person::getByMiscOrCreate($person['names'], $person['url']);
                $roleItem = Lms_Item_Role::getByNameOrCreate($person['role']);
                
                if (isset($participantsIndex[$this->getId()][$roleItem->getId()][$personItem->getId()])) {
                    Lms_Debug::warn("Participant " . $this->getId() . " - " . $roleItem->getId() . " - " . $personItem->getId() . " already exists");
                    continue;
                }
                
                $item = Lms_Item::create('Participant');
                $item->setMovieId($this->getId())
                    ->setRoleId($roleItem->getId())
                    ->setPersonId($personItem->getId());
                if (isset($person['character'])) {
                    $item->setCharacter($person['character']);
                }
                $item->save();
                $participantsIndex[$this->getId()][$roleItem->getId()][$personItem->getId()] = true;
            }
        }
       
        if (isset($info['imdb_id']) || isset($info['rating_imdb_value'])) {
            $item = Lms_Item::create('Rating');
            $item->setSystem('imdb');
            if (isset($info['imdb_id'])) {
                $item->setSystemUid($info['imdb_id']);
            }
            if (isset($info['rating_imdb_count'])) {
                $item->setCount($info['rating_imdb_count']);
            }
            if (isset($info['rating_imdb_value'])) {
                $item->setValue($info['rating_imdb_value']);
            }
            $this->add($item);
        }
        
        if (isset($info['kinopoisk_id']) || isset($info['rating_kinopoisk_value'])) {
            $item = Lms_Item::create('Rating');
            $item->setSystem('kinopoisk');
            if (isset($info['kinopoisk_id'])) {
                $item->setSystemUid($info['kinopoisk_id']);
            }
            if (isset($info['rating_kinopoisk_count'])) {
                $item->setCount($info['rating_kinopoisk_count']);
            }
            if (isset($info['rating_kinopoisk_value'])) {
                $item->setValue($info['rating_kinopoisk_value']);
            }
            $this->add($item);
        }        
        
    }

    
    public function mergeInfo($info)
    {
        $merged = array();
        
        $merged['name'] = false;
        if (!empty($info['name']) && !$this->getName()) {
            $this->setName($info['name']);
            $merged['name'] = true;
        }
        
        $merged['international_name'] = false;
        if (!empty($info['international_name']) && !$this->getInternationalName()) {
            $this->setInternationalName($info['international_name']);
            $merged['international_name'] = true;
        }

        $merged['description'] = false;
        if (!empty($info['description']) && Lms_Text::length($info['description'])>Lms_Text::length($this->getDescription())) {
            $this->setDescription($info['description']);
            $merged['description'] = true;
        }

        $merged['year'] = false;
        if (!empty($info['year']) && !$this->getYear()) {
            $this->setYear($info['year']);
            $merged['year'] = true;
        }

        $merged['mpaa'] = false;
        if (!empty($info['mpaa']) && Lms_Text::length($info['mpaa'])>Lms_Text::length($this->getMpaa())) {
            $this->setMpaa($info['mpaa']);
            $merged['mpaa'] = true;
        }

        $merged['cover'] = false;
        if (!empty($info['poster']) && !$this->getCovers()) {
            $this->setCovers($info['poster']);
            $merged['cover'] = true;
        }
        
        $merged['trailer'] = false;
        if (!empty($info['trailer']) && !$this->getTrailer()) {
            $this->setTrailer($info['trailer'])
                 ->setTrailerLocalized(null);
            $merged['trailer'] = true;
        }
        $this->save();
        
        $merged['countries'] = false;
        $thisCountriesIds = array();
        foreach ($this->getChilds('Linkator_CountryMovie') as $item) {
            $thisCountriesIds[] = $item->getCountryId(); 
        }
        foreach ($info['countries'] as $name) {
            $item = Lms_Item_Country::getByNameOrCreate($name);
            if (!in_array($item->getId(), $thisCountriesIds)) {
                $this->add($item);
                $merged['countries'] = true;
            }
        }

        $merged['genres'] = false;
        $thisGenresIds = array();
        foreach ($this->getChilds('Linkator_GenreMovie') as $item) {
            $thisGenresIds[] = $item->getGenreId(); 
        }
        foreach ($info['genres'] as $name) {
            $item = Lms_Item_Genre::getByNameOrCreate($name);
            if (!in_array($item->getId(), $thisGenresIds)) {
                $this->add($item);
                $merged['genres'] = true;
            }
        }
        
        $merged['persones'] = false;
        $participantsIndex = array();
        foreach ($this->getChilds('Participant') as $item) {
                $participantsIndex[$this->getId()][$item->getRoleId()][$item->getPersonId()] = true;
        }
        foreach ($info['persones'] as $person) {
            if (array_filter($person['names']) || $person['url']) {
                $personItem = Lms_Item_Person::getByMiscOrCreate($person['names'], $person['url']);
                $roleItem = Lms_Item_Role::getByNameOrCreate($person['role']);
                
                if (isset($participantsIndex[$this->getId()][$roleItem->getId()][$personItem->getId()])) {
                    continue;
                }
                
                $item = Lms_Item::create('Participant');
                $item->setMovieId($this->getId())
                    ->setRoleId($roleItem->getId())
                    ->setPersonId($personItem->getId());
                if (isset($person['character'])) {
                    $item->setCharacter($person['character']);
                }
                $item->save();
                $participantsIndex[$this->getId()][$roleItem->getId()][$personItem->getId()] = true;
                $merged['persones'] = true;
            }
        }
       
        $merged['imdb'] = false;
        if (isset($info['imdb_id']) || isset($info['rating_imdb_value'])) {
            $item  = Lms_Item_Rating::getBySystemOrCreate($this->getId(), 'imdb');
            if (isset($info['imdb_id']) && !$item->getSystemUid()) {
                $item->setSystemUid($info['imdb_id']);
            }
            if ($item->getCount()<$info['rating_imdb_count']) {
                $item->setValue($info['rating_imdb_value']);
                $item->setCount($info['rating_imdb_count']);
                $merged['imdb'] = true;
            }
            $item->save();
        }
        
        $merged['kinopoisk'] = false;
        if (isset($info['kinopoisk_id']) || isset($info['rating_kinopoisk_value'])) {
            $item  = Lms_Item_Rating::getBySystemOrCreate($this->getId(), 'kinopoisk');
            if (isset($info['kinopoisk_id']) && !$item->getSystemUid()) {
                $item->setSystemUid($info['kinopoisk_id']);
            }
            if ($item->getCount()<$info['rating_kinopoisk_count']) {
                $item->setValue($info['rating_kinopoisk_value']);
                $item->setCount($info['rating_kinopoisk_count']);
                $merged['kinopoisk'] = true;
            }
            $item->save();
        }        
        
        return $merged;
    }
    
    public static function updateLocalRating($movieId)
    {
        $db = Lms_Db::get('main');
        $ratings = $db->selectCol("SELECT rating FROM movies_users_ratings WHERE movie_id=?d", $movieId);
        
        $rating = Lms_Item_Rating::getBayes($ratings, Lms_Application::getConfig('rating', 'count'));
        $db->query(
            "UPDATE `ratings` SET `value`=?f, `count`=?d, `details`=? WHERE `system`='local' AND movie_id=?d",
            $rating['bayes'],
            $rating['count'],
            serialize($rating['detail']),
            $movieId
        );
        return $rating;
    }
    
    public function updateHit()
    {
        $db = Lms_Db::get('main');
        $hit = $db->selectCell("SELECT count(*) FROM hits WHERE movie_id=?d", $this->getId());
        $this->setHit($hit);
        return $this;
    }
    
    public static function postProcess(&$rows, $coverWidth = 100)
    {
        foreach ($rows as &$row) {
            if (isset($row["international_name"])) {
                //$row["international_name"] = htmlentities($row["international_name"], ENT_NOQUOTES, 'cp1252');
            }
            if (isset($row["covers"])) {
                $covers = array_values(array_filter(
                    preg_split("/(\r\n|\r|\n)/", $row["covers"])
                ));
                $row["cover"] = array_shift($covers);
                if ($row["cover"]) {
                    $width = $coverWidth;
                    $height = 0;
                    $row["cover"] = Lms_Application::thumbnail($row["cover"], $width, $height, $defer = true);
                }
                unset($row["covers"]);
            }
        }
    }

    
    public static function hitMovie($movieId) 
    {
	if ($movieId){
            $db = Lms_Db::get('main');
            $ip = Lms_Ip::getIp();
            $userId = Lms_User::getUser()->getId();
            $hitId = $db->query("INSERT IGNORE INTO hits SET movie_id=?d, user_id=?d, `ip`=?, created_at=NOW()", $movieId, $userId, $ip);
            if ($hitId) {
                $db->query('UPDATE movies SET hit=hit+1 WHERE movie_id=?d', $movieId);
                $db->query("UPDATE users SET PlayActivity=PlayActivity+1 WHERE ID=?d", $userId);
            } else {
                $db->query("UPDATE hits SET created_at=NOW() WHERE movie_id=?d AND user_id=?d AND ip=?", $movieId, $userId, $ip);
            }
	}
    }

    public function getCover()
    {
        $covers = array_values(array_filter(array_merge(
            preg_split("/(\r\n|\r|\n)/", $this->getCovers())
        )));
        return array_shift($covers);
    }
    
    
    public function getCountriesAsArray()
    {
        $result = array();
        $items = $this->getChilds('Country');
        foreach ($items as $item) {
            $result[$item->getId()] = $item->getName();
        }
        return $result;
    }

    public function getParticipantsAsArray()
    {
        $result = array();
        $items = $this->getChilds('Participant');
        foreach ($items as $item) {
            $person = $item->getChilds('Person');
            $result[] = array(
                'participant_id' => $item->getId(),
                'person_id' => $person->getId(),
                'name' => $person->getName(),
                'international_name' => $person->getInternationalName(),
                'role_id' => $item->getRoleId(),
                'character' =>$item->getCharacter()
            );
        }
        return $result;
    }

    public function getGenresAsArray()
    {
        $result = array();
        $items = $this->getChilds('Genre');
        foreach ($items as $item) {
            $result[$item->getId()] = $item->getName();
        }
        return $result;
    }
    
    //dummy
    public function getCountries()
    {
        return;
    }
    
    public function setCountries($ids)
    {
        $items = $this->getChilds('Linkator_CountryMovie');
        foreach ($items as $item) {
            $id = $item->getCountryId();
            if (!in_array($id, $ids)) {
                $item->delete();
            } else {
                unset($ids[array_search($id, $ids)]);
            }
        }
        foreach ($ids as $id) {
            $item = Lms_Item::create('Country', $id);
            $this->add($item);
        }
        
        return $this;
    }
    
    //dummy
    public function getGenres()
    {
        return;
    }
    public function setGenres($ids)
    {
        $items = $this->getChilds('Linkator_GenreMovie');
        foreach ($items as $item) {
            $id = $item->getGenreId();
            if (!in_array($id, $ids)) {
                $item->delete();
            } else {
                unset($ids[array_search($id, $ids)]);
            }
        }
        foreach ($ids as $id) {
            $item = Lms_Item::create('Genre', $id);
            $this->add($item);
        }
        
        return $this;
    }
    
    //dummy
    public function getRating()
    {
        return;
    }
    public function setRating($arr)
    {
        foreach ($arr as $system => $pairs) {
            $rating = Lms_Item_Rating::getBySystemOrCreate($this->getId(), $system);
            foreach ($pairs as $field => $value) {
                $setMethod = "set$field";
                $rating->$setMethod($value)
                       ->save();
            }
        }
        return $this;
    }
    
    public function getFilesAsArray(&$audioTracksCount = 0)
    {
        $files = array();
        $items = $this->getChilds('File');
        
        $baseLevel = null;
        foreach ($items as $item) {
            $level = Lms_Application::calcLevel($item->getPath());
            if ($level<$baseLevel || $baseLevel===null) {
                $baseLevel = $level;
            }
        }
        $filesIndex = array();
        $sortarray = array();
        foreach ($items as $item) {
            $path = Lms_Application::normalizePath($item->getPath());
            $metainfo = $item->getMetainfo();
            $files[] = array(
                'file_id' => $item->getId(),
                'name' => $item->getName(),
                'path' => $path,
                'basename' => basename($item->getPath()),
                'is_dir' => (bool) $item->getIsDir(),
                'size' => $item->getSize(),
                'metainfo' => $metainfo,
                'compact_metainfo' => Lms_Application::formatMetainfo($metainfo),
                'audio_tracks_count' => isset($metainfo['audio']['streams'])? count($metainfo['audio']['streams']) : 0,
                'translation' => $item->getTranslation(),
                'quality' => $item->getQuality(),
                'md5_hash' => $item->getMd5Hash(),
                'tth_hash' => $item->getTthHash(),
                'level' => Lms_Application::calcLevel($item->getPath()) - $baseLevel,
                'type' => Lms_Application::getType($item->getPath(), $item->getIsDir())
            );
            
            $filesIndex[$path] = $item->getId();
            $sortarray[] = strtolower($path);
        }
        array_multisort($sortarray, $files);
        
        $filesIdIndex = array();
        $rootFileOfThisTree = 0;
        foreach ($files as $n => &$file) {
            if (!$n) {
                $level = 0;
            } else {
                $rootFile = $files[$rootFileOfThisTree];
                if (Lms_Application::isParentDirectory($rootFile['path'], $file['path'])) {
                    $level = Lms_Application::calcLevel($file['path']) - Lms_Application::calcLevel($rootFile['path']);
                } else {
                    $level = 0;
                    $rootFileOfThisTree = $n;
                }
            }
            $file['level'] = $level;
            
            $parentDirectory = dirname($file['path']);
            $file['parent'] = isset($filesIndex[$parentDirectory])? $filesIndex[$parentDirectory] : null;
            $filesIdIndex[$file['file_id']] = $n;
        }
        $audioTracksCount = 0;
        for ($n = count($files)-1; $n>=0; $n--) {
            $file =& $files[$n]; 
            if ($audioTracksCount < $file['audio_tracks_count']) {
                $audioTracksCount = $file['audio_tracks_count'];
            }
            
            if ($file['parent']!==null) {
                $i = $filesIdIndex[$file['parent']];
                $parentFile =& $files[$i];
                if ($parentFile['audio_tracks_count'] < $file['audio_tracks_count']) {
                    $parentFile['audio_tracks_count'] = $file['audio_tracks_count'];
                }
                if (isset($file['metainfo']['video']) || (isset($file['has_video']) && $file['has_video'])) {
                    $parentFile['has_video'] = true;
                }
            }
            
        }
        
        return $files;
    }

    private function isSubdirectoryOfRoot($dir)
    {
        foreach (Lms_Application::getConfig('incoming', 'root_dirs') as $rootdir) {
            if (Lms_Application::isParentDirectory($rootdir, $dir)) {
                return true;
            }
        }
        return false;
    }
    
    public function getFolders()
    {
        $folders = array();
        $items = $this->getChilds('File');
        foreach ($items as $item) {
            $path = $item->getPath();
            if ($item->getIsDir()) {
                $folders[] = $path;
                /*while ($this->isSubdirectoryOfRoot($path = dirname($path))) {
                    $folders[] = $path;
                }*/
            } else {
                $folders[] = dirname($path);
            }
        }
        
        if (!$folders) {
            return $folders;
        }
        
        $folders = array_unique($folders);
        sort($folders);
        
        $result = array();
        $rootFolder = $folders[0];
        foreach ($folders as $n => $folder) {
            if (!$n) {
                $level = 0;
            } else {
                if (Lms_Application::isParentDirectory($rootFolder, $folder)) {
                    $level = Lms_Application::calcLevel($folder) - Lms_Application::calcLevel($rootFolder);
                } else {
                    $level = 0;
                    $rootFolder = $folder;
                }
            }
            $result[] = array(
                'path' => $folder,
                'basename' => basename($folder),
                'level' => $level,
            );
        }
        
        
        return $result;
    }

    public function getFiles()
    {
        return $this->getChilds('File');
    }
    
    public function getFilesAsArray2()
    {
        $user = Lms_User::getUser();
        $items = $this->getChilds('File');
        
        $movieQuality = $this->getQuality();
        $movieTranslation = $this->getTranslation();
        $movieId = $this->getId();
        $files = array();
        foreach ($items as $item) {
            $path = Lms_Application::normalizePath($item->getPath());
            $files[$path] = array(
                'file_id' => $item->getId(),
                'name' => $item->getName(),
                'size' => $item->getSize(),
                'is_dir' => (bool)$item->getIsDir(),
                'links' => $item->getLinks(),
                'metainfo' => Lms_Application::formatMetainfo($item->getMetainfo()),
                'frames' => $item->getFrames(),
                'quality' => $item->getQuality(),
                'translation' => $item->getTranslation(),
                'path' => $path,
                'active' => (bool)$item->getActive(),
                'childs' => array()
            );
        }
        Lms_Array::iksort($files);
        
        $rootPathOfThisTree = 0;
        foreach ($files as $path => &$file) {
            if (!$rootPathOfThisTree) {
                $rootPathOfThisTree = $path;
                $level = 0;
            } else {
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

        foreach ($files as &$file) {
            if (is_array($movieTranslation)) {
                foreach ($movieTranslation as $num => $translation) {
                    if (empty($file['translation'][$num])) {
                        $file['translation'][$num] = $movieTranslation[$num];
                    }
                }
            }
            if ($movieQuality) {
                if (empty($file['quality'])) {
                    $file['quality'] = $movieQuality;
                }
            }
        }        
        
        
        return array_values($files);
    }    

    public static function selectAsStruct(&$total, $offset, $size, $sort, $order, $filter)
    {
        $db = Lms_Db::get('main');
        if ($order<0) {
            $order = 'DESC';
        } else if ($order>0) {
            $order = 'ASC';
        } else {
            $order = '';
        }
            
        $rows = $db->selectPage(
            $total,
            "SELECT DISTINCT 
                m.movie_id, 
                m.name, 
                m.international_name, 
                m.year 
            FROM movies m 
                {INNER JOIN movies_files USING(movie_id) INNER JOIN ?# f USING(file_id)}
            WHERE 1=1 
                {AND (m.quality LIKE ? OR f.quality LIKE ?)}
                {AND (m.translation LIKE ? OR f.translation LIKE ?)}
                {AND (m.name LIKE ? OR m.international_name LIKE ?)}
                {AND m.`hidden`=?d}
            ORDER BY m.?# $order {LIMIT ?d, }{?d}",
            (!empty($filter['quality']) || !empty($filter['translation']))? 'files' : DBSIMPLE_SKIP,

            !empty($filter['quality'])? $filter['quality'] : DBSIMPLE_SKIP, !empty($filter['quality'])? $filter['quality'] : DBSIMPLE_SKIP,
            !empty($filter['translation'])? "a:1:%{$filter['translation']}%" : DBSIMPLE_SKIP, !empty($filter['translation'])? "a:1:%{$filter['translation']}%" : DBSIMPLE_SKIP,

            !empty($filter['name'])? "%{$filter['name']}%" : DBSIMPLE_SKIP, !empty($filter['name'])? "%{$filter['name']}%" : DBSIMPLE_SKIP,

            !empty($filter['hidden'])? 1 : DBSIMPLE_SKIP,
            $sort,
            $offset!==null? $offset : DBSIMPLE_SKIP, 
            $size!==null? $size : DBSIMPLE_SKIP
        );
        return $rows;
    }    

    public static function selectByNames($names)
    {
        if (!$names) {
            return array();
        }
        $db = Lms_Db::get('main');
        $rows = $db->select(
            'SELECT * FROM movies WHERE international_name IN (?a) OR `name` IN (?a)',
            $names, $names
        );
        return Lms_Item_Abstract::rowsToItems($rows);
    }
    
    public function getRatingsAsArray()
    {
        $items = $this->getChilds('Rating');
        $ratings = array(
            'imdb' => null, 
            'kinopoisk' => null, 
            'local' => null, 
        );
        foreach ($items as $item) {
            $ratings[$item->getSystem()] = array(
                'system_uid' => $item->getSystemUid(),
                'count' => $item->getCount(),
                'value' => $item->getValue(),
            );
        }
        return $ratings;
    }
    
    public function getCreatorName()
    {
        //return $this->getChilds('User/@Login');
        return $this->_slaveDb->selectCell('SELECT Login FROM users WHERE ID=?d', $this->getCreatedBy());
    }
    
    public function getUserRating()
    {
        return Lms_Item_MovieUserRating::getRating($this->getId());
    }
    
    public function updateSearchIndex()
    {
        $this->_masterDb->query('DELETE FROM `search_trigrams` WHERE `type`=? AND `id`=?d', 'movie', $this->getId());
        $this->_masterDb->query('DELETE FROM `suggestion` WHERE `type`=? AND `id`=?d', 'movie', $this->getId());
 
        $trigramValues = array();
        $suggestionValues = array();
        Lms_Application::prepareTextIndex($this->getName(), 'movie', $this->getId(), $trigramValues, $suggestionValues);
        if ($this->getInternationalName()!=$this->getName()) {
            Lms_Application::prepareTextIndex($this->getInternationalName(), 'movie', $this->getId(), $trigramValues, $suggestionValues);
        }
        if ($trigramValues) {
            $this->_masterDb->query('INSERT IGNORE INTO `search_trigrams`(`trigram`,`type`, `id`) VALUES ' . implode(', ', $trigramValues));
        } 
        
        if ($suggestionValues) {
            $this->_masterDb->query('INSERT IGNORE INTO `suggestion`(`word`,`type`, `id`) VALUES ' . implode(', ', $suggestionValues));
        }
    }
    
    static public function selectNotLocalized($limit = 1)
    {
        $slaveDb = Lms_Item::getSlaveDb();
        $sql = "SELECT * FROM " . self::getTableName() 
             . " WHERE LENGTH(trailer)>2 AND trailer_localized IS NULL ORDER BY rank DESC, updated_at DESC LIMIT ?d";
        $rows = $slaveDb->select($sql, $limit);
        $items = Lms_Item_Abstract::rowsToItems($rows);
        return $items;
    } 
    
    static public function selectSlice($fromId, $toId)
    {
        $slaveDb = Lms_Item::getSlaveDb();
        $sql = "SELECT * FROM " . self::getTableName() 
             . " WHERE movie_id BETWEEN ?d AND ?d";
        $rows = $slaveDb->select($sql, $fromId, $toId);
        return Lms_Item_Abstract::rowsToItems($rows);
    } 
    
}