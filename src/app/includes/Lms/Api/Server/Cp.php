<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Movies.php 700 2011-06-10 08:40:53Z macondos $
 * @package Api
 */
 
/**
 * @package Api
 */
class Lms_Api_Server_Cp extends Lms_Api_Server_Abstract
{
    public static function __callStatic($name, $arguments) 
    {
        try {
            $user = Lms_User::getUser();
            if (!$user->isAllowed("movie", "moderate")) {
                return new Lms_Api_Response(403);
            }
            $method = "_$name";
            return call_user_func_array(array('self', $method), $arguments);
        } catch (Exception $e) {
            Lms_Debug::crit($e->getMessage());
            Lms_Debug::crit($e->getTraceAsString());
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
    
    protected static function _getTranslations($params)
    {
        $result = Lms_Application::getConfig('translation_options');
        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _getQualities($params)
    {
        $result = Lms_Application::getConfig('quality_options');
        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _getDefaultEngines($params)
    {
        $result = array_keys(array_filter(Lms_Application::getConfig('parsing', 'default_engines')));
        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _getCatalogQualitiesAndTranslations($params)
    {
        $db = Lms_Db::get('main');
        $sql = "SELECT quality, translation FROM movies WHERE quality!='' OR translation!=''
                UNION 
                SELECT quality, translation FROM files WHERE quality!='' OR translation!=''";
        $rows = $db->select($sql);
        $qualitiesIndex = array();
        $translationsIndex = array();
        foreach ($rows as $row) {
            $a = unserialize($row['translation']);
            if (is_array($a)) {
                foreach ($a as $translation) {
                    @$translationsIndex[$translation]++;
                }
            }
            @$qualitiesIndex[$row['quality']]++;
        }
        arsort($qualitiesIndex);
        arsort($translationsIndex);

        $result = array();
        $result['qualities'] = array();
        foreach ($qualitiesIndex as $name => $count) {
            $result['qualities'][] = array(
                'name' => $name,
                'count' => $count,
            );
        }
        $result['translations'] = array();
        foreach ($translationsIndex as $name => $count) {
            $result['translations'][] = array(
                'name' => $name,
                'count' => $count,
            );
        }
        return new Lms_Api_Response(200, null, $result);
    }
    
    protected static function _getCountries($params)
    {
        $db = Lms_Db::get('main');
        $sql = "SELECT * FROM countries ORDER BY name";
        $result = $db->select($sql);
        return new Lms_Api_Response(200, null, $result);
    }
    
    protected static function _getGenres($params)
    {
        $db = Lms_Db::get('main');
        $sql = "SELECT * FROM genres ORDER BY name";
        $result = $db->select($sql);
        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _getRoles($params)
    {
        $db = Lms_Db::get('main');
        $sql = "SELECT * FROM roles ORDER BY `sort`, `name`";
        $result = $db->select($sql);
        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _getIncoming($params)
    {
        $offset = isset($params['offset'])? (int)$params['offset'] : 0;
        $size = isset($params['size'])? (int)$params['size'] : 40;
        $showHidden = isset($params['show_hidden'])? (bool)$params['show_hidden'] : false;
        $forceScan = isset($params['force_scan'])? $params['force_scan'] : false;

        $incomingNamespace = new Zend_Session_Namespace('Incoming');
        if ($forceScan 
            || !isset($incomingNamespace->lastScan) 
            || (time()-$incomingNamespace->lastScan)>Lms_Application::getConfig('incoming', 'cache_time')
        ) {
            Lms_Item_Incoming::scanIncoming();
            $incomingNamespace->lastScan = time();
        }
        
        $files = Lms_Item_Incoming::selectAsStruct($offset, $size, $showHidden, $total);

        $result = array();
        $result['files'] = $files;
        $result['offset'] = $offset;
        $result['size'] = $size;
        $result['total'] = $total;
        return new Lms_Api_Response(200, null, $result);
    }

/*    protected static function _setIncomingField($params)
    {
        $db = Lms_Db::get('main');
        $incomingId = $params['incoming_id'];
        $field = $params['field'];
        $value = $params['value'];

        $incoming = Lms_Item::create('Incoming', $incomingId);
        
        $method = "set$field";
        $incoming->$method($value)
                 ->save();
        
        return new Lms_Api_Response(200);
    }    
*/
    protected static function _setIncomingField($params)
    {
        $db = Lms_Db::get('main');
        $incomingId = $params['incoming_id'];
        $incoming = Lms_Item::create('Incoming', $incomingId);

        $field = $params['field'];
        $value = $params['value'];

        $keys = explode('/', $field);
        $mainField = array_shift($keys);
 
        $getMethod = "get$mainField";
        $setMethod = "set$mainField";
        
        $sourceValue = $incoming->$getMethod();
        
        $ref =& $sourceValue; 
        foreach ($keys as $key) {
            if (!isset($ref[$key])) {
                $ref[$key] = null;
            }
            $ref =& $ref[$key];
        }
        $ref = $value;
        
        $incoming->$setMethod($sourceValue)
                 ->save();
        
        return new Lms_Api_Response(200);
    }
    
    protected static function _removeIncomingPerson($params)
    {
        $db = Lms_Db::get('main');
        $incomingId = $params['incoming_id'];
        $incoming = Lms_Item::create('Incoming', $incomingId);

        $personIndex = $params['person_index'];

        $info = $incoming->getInfo();
        
        unset($info['persones'][$personIndex]);
        
        $incoming->setInfo($info)
                 ->save();
        
        return new Lms_Api_Response(200);
    }

    protected static function _insertIncomingPerson($params)
    {
        $db = Lms_Db::get('main');
        $incomingId = $params['incoming_id'];
        $incoming = Lms_Item::create('Incoming', $incomingId);
        
        $persones = $params['persones'];
        
        $info = $incoming->getInfo();
        
        if (!isset($info['persones'])) {
            $info['persones'] = array();
        }
            
        $info['persones'] = array_merge($info['persones'], $persones);
        
        $incoming->setInfo($info)
                 ->save();
        
        return new Lms_Api_Response(200);
    }

    protected static function _clearIncomingInfo($params)
    {
        $db = Lms_Db::get('main');
        $incomingId = $params['incoming_id'];

        $incoming = Lms_Item::create('Incoming', $incomingId);
        
        $incoming->setParsedInfo(null)
                 ->setInfo(null)
                 ->save();
        
        return new Lms_Api_Response(200);
    }    
    
    protected static function _expandIncoming($params)
    {
        $db = Lms_Db::get('main');
        $incomingId = $params['incoming_id'];
        $incoming = Lms_Item::create('Incoming', $incomingId);
        $incoming->setExpanded(1)
                 ->save();
        $incomingNamespace = new Zend_Session_Namespace('Incoming');
        $incomingNamespace->lastScan = 0;
        return new Lms_Api_Response(200);
    }

    protected static function _collapseIncoming($params)
    {
        $db = Lms_Db::get('main');
        $incomingId = $params['incoming_id'];
        $incoming = Lms_Item::create('Incoming', $incomingId);
        $incoming->setExpanded(0)
                 ->save();
        $incomingNamespace = new Zend_Session_Namespace('Incoming');
        $incomingNamespace->lastScan = 0;
        return new Lms_Api_Response(200);
    }

    protected static function _hideIncoming($params)
    {
        $db = Lms_Db::get('main');
        $incomingIds = $params['incoming_ids'];
        foreach ($incomingIds as $incomingId) {
            $incoming = Lms_Item::create('Incoming', $incomingId);
            $incoming->setHidden(1)
                    ->save();
        }
        $incomingNamespace = new Zend_Session_Namespace('Incoming');
        $incomingNamespace->lastScan = 0;
        return new Lms_Api_Response(200);
    }

    protected static function _unhideIncoming($params)
    {
        $db = Lms_Db::get('main');
        $incomingIds = $params['incoming_ids'];
        foreach ($incomingIds as $incomingId) {
            $incoming = Lms_Item::create('Incoming', $incomingId);
            $incoming->setHidden(0)
                    ->save();
        }
        $incomingNamespace = new Zend_Session_Namespace('Incoming');
        $incomingNamespace->lastScan = 0;
        return new Lms_Api_Response(200);
    }
    
    
    protected static function _getIncomingDetails($params)
    {
        $db = Lms_Db::get('main');
        $incomingId = $params['incoming_id'];
        $result = Lms_Item_Incoming::getIncomingDetails($incomingId);
        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _searchMovie($params)
    {
        $db = Lms_Db::get('main');
        $incomingId = $params['incoming_id'];
        $query = $params['query'];
        $engines = $params['engines'];
        foreach ($engines as $engine) {
            $result['sections'][] = array(
                'name' => $engine,
                'items' => Lms_Service_Movie::searchMovie($query, $engine)
            );
        }

        $incoming = Lms_Item::create('Incoming', $incomingId);
        $incoming->setLastQuery($query)
                 ->setSearchResults($result)
                 ->setParsingUrl(array())
                 ->save();

        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _searchKinopoiskMovie($params)
    {
        $query = $params['query'];
        $result = array();
        $result['items'] = Lms_Service_Movie::searchMovie($query, 'kinopoisk');

        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _setParsingUrl($params)
    {
        $incomingId = $params['incoming_id'];
        $url = $params['url'];
        $replace = $params['replace'];

        $incoming = Lms_Item::create('Incoming', $incomingId);
        if (!$url) {
            $incoming->setParsingUrl(array());
        } else if ($replace) {
            $incoming->setParsingUrl(array($url));
        } else {
            $parsingUrl = $incoming->getParsingUrl()?: array();
            $parsingUrl[] = $url;
            $incoming->setParsingUrl(array_unique($parsingUrl));
        }
        $incoming->save();

        return new Lms_Api_Response(200);
    }

    protected static function _autoSearchMovie($params)
    {
        $db = Lms_Db::get('main');
        $incomingId = $params['incoming_id'];
        $incoming = Lms_Item::create('Incoming', $incomingId);
        $query = $incoming->getName();
        if ($query) {
            $engines = array('kinopoisk');
            foreach ($engines as $engine) {
                $result['sections'][] = array(
                    'name' => $engine,
                    'items' => Lms_Service_Movie::searchMovie($query, $engine)
                );
            }

            $incoming->setLastQuery($query)
                    ->setSearchResults($result)
                    ->save();
        } else {
            $result['sections'] = array();
        }
        
        return new Lms_Api_Response(200, null, $result);
    }
    
    
    protected static function _parseMovie($params)
    {
        $db = Lms_Db::get('main');
        $incomingId = $params['incoming_id'];
        $incoming = Lms_Item::create('Incoming', $incomingId);

        $urls = $incoming->getParsingUrl();
        
        if (!$urls) {
            return new Lms_Api_Response(400, "Need url");
        }
        
        $forceMerge = false;
        if (count($urls)>1) {
            $incoming->setInfo(null);
            $forceMerge = true;
        }
        
        foreach ($urls as $url) {
            $engine = Lms_Service_Movie::getModuleByUrl($url);
            $data = Lms_Service_Movie::parseMovie($url, $engine);
            $incoming->mergeParsedInfo($data, $engine, $forceMerge);
        }

        $incoming->save();

        $result = array();
        $result['info'] = $incoming->getInfo();
        $result['parsed_info'] = $data;

        return new Lms_Api_Response(200, null, $result);
    }

   protected static function _parseKinopoiskMovie($params)
    {
        $url = $params['url'];
        $data = Lms_Service_Movie::parseMovie($url);

        $result = array();
        $result['movie'] = $data;
        $result['url'] = $url;
        
        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _replaceKinopoiskMovie($params)
    {
        $url = $params['url'];
        $movieId = $params['movie_id'];
        $movie = Lms_Item::create('Movie', $movieId);
        
        $data = Lms_Service_Movie::parseMovie($url);
        $db = Lms_Db::get('main');
        $db->transaction();
        
        $movie->resetInfo($data);
        
        $db->commit();

        return new Lms_Api_Response(200);
    }
    
    protected static function _mergeKinopoiskMovie($params)
    {
        $url = $params['url'];
        $movieId = $params['movie_id'];
        $movie = Lms_Item::create('Movie', $movieId);
        
        $data = Lms_Service_Movie::parseMovie($url);
        $db = Lms_Db::get('main');
        $db->transaction();
        
        $movie->mergeInfo($data);
        
        $db->commit();

        return new Lms_Api_Response(200);
    }
    
    protected static function _parsePerson($params)
    {
        $personId = $params['person_id'];
        if ($personId) {
            $person = Lms_Item::create('Person', $personId);
            $person->parse()
                   ->save();
        }

        return new Lms_Api_Response(200);
    }
    
    
    protected static function _parseIncomingFiles($params)
    {
        $incomingId = $params['incoming_id'];

        $incoming = Lms_Item::create('Incoming', $incomingId);
        $incoming->parseFiles()
                 ->save();

        $result = array();
        $result['files'] = $incoming->getCompactFiles();
        $result['metainfo'] = $incoming->getCompactMetainfo();
        $result['audio_tracks_count'] = $incoming->getAudioTracksCount();
        $result['quality'] = $incoming->getQuality();
        $result['translation'] = $incoming->getTranslation();
        $result['size'] = $incoming->getSize();

        return new Lms_Api_Response(200, null, $result);
    }
    
    
    protected static function _importIncoming($params)
    {
        
        $db = Lms_Db::get('main');
        $incomingId = $params['incoming_id'];

        $incoming = Lms_Item::create('Incoming', $incomingId);
        if ($incoming->getActive()) {
            $incoming->setActive(0)
                     ->save();

            if (!$incoming->getFiles()) {
                $incoming->parseFiles();
            }

            $db->transaction();

            $info = $incoming->getInfo();
            $files = $incoming->getFiles();
            $quality = $incoming->getQuality();
            $translation = $incoming->getTranslation();

            Lms_Item_Movie::createFromInfo($info, $files, $quality, $translation);

            $db->commit();

            Lms_Application::tryRunTasks();
        }
        return new Lms_Api_Response(200);
    }

    protected static function _getMovies($params)
    {
        $offset = isset($params['offset'])? (int)$params['offset'] : 0;
        
        $size = isset($params['size'])? (int)$params['size'] : 100;
        $sort = isset($params['sort'])? $params['sort'] : 'updated_at';
        $order = isset($params['order'])? $params['order'] : -1;
        $filter = isset($params['filter'])? $params['filter'] : array();
        
        
        $movies = Lms_Item_Movie::selectAsStruct($total, $offset, $size, $sort, $order, $filter);

        $result['movies'] = $movies;
        $result['offset'] = $offset;
        $result['size'] = $size;
        $result['total'] = $total;
        return new Lms_Api_Response(200, null, $result);
    }
    
    protected static function _getMovie($params)
    {
        $movieId = $params['movie_id'];

        $movie = Lms_Item::create('Movie', $movieId);

        $result = array(); 	 	 	 	 	 	 	 	 	 		 	 	 	
        $result['movie'] = array(
            'movie_id' => $movie->getId(),
            'name' => $movie->getName(),
            'international_name' => $movie->getInternationalName(),
            'year' => $movie->getYear(),
            'description' => $movie->getDescription(),
            'updated_at' => $movie->getUpdatedAt(),
            'created_at' => $movie->getCreatedAt(),
            'quality' => $movie->getQuality(),
            'translation' => $movie->getTranslation(),
            'mpaa' => $movie->getMpaa(),
            'covers' => $movie->getCovers(),
            'trailer' => $movie->getTrailer(),
            'hidden' => $movie->getHidden(),
            'hit' => $movie->getHit(),
            'created_by' => $movie->getCreatedBy(),
            'present_by' => $movie->getPresentBy(),
            'group' => $movie->getGroup(),
            'countries' => $movie->getCountriesAsArray(),
            'genres' => $movie->getGenresAsArray(),
            'files' => $movie->getFilesAsArray($audioTracksCount),
            'audio_tracks_count' => $audioTracksCount,
            'participants' => $movie->getParticipantsAsArray(),
            'ratings' => $movie->getRatingsAsArray(),
        );

        return new Lms_Api_Response(200, null, $result);
    }
 
    protected static function _setMovieField($params)
    {
        $movieId = $params['movie_id'];
        $movie = Lms_Item::create('Movie', $movieId);

        $field = $params['field'];
        $value = $params['value'];

        $keys = explode('/', $field);
        $mainField = array_shift($keys);
 
        $getMethod = "get$mainField";
        $setMethod = "set$mainField";
        
        $sourceValue = $movie->$getMethod();
        
        $ref =& $sourceValue; 
        foreach ($keys as $key) {
            if (!isset($ref[$key])) {
                $ref[$key] = null;
            }
            $ref =& $ref[$key];
        }
        $ref = $value;
        
        $movie->$setMethod($sourceValue)
              ->save();
        
        return new Lms_Api_Response(200);
    }    
    
    protected static function _setFileField($params)
    {
        $fileId = $params['file_id'];
        $file = Lms_Item::create('File', $fileId);

        $field = $params['field'];
        $value = $params['value'];

        $keys = explode('/', $field);
        $mainField = array_shift($keys);
 
        $getMethod = "get$mainField";
        $setMethod = "set$mainField";
        
        $sourceValue = $file->$getMethod();
        
        $ref =& $sourceValue; 
        foreach ($keys as $key) {
            if (!isset($ref[$key])) {
                $ref[$key] = null;
            }
            $ref =& $ref[$key];
        }
        $ref = $value;
        
        $file->$setMethod($sourceValue)
             ->save();
        
        return new Lms_Api_Response(200);
    }    

    protected static function _setParticipantField($params)
    {
        $participantId = $params['participant_id'];
        $participant = Lms_Item::create('Participant', $participantId);

        $field = $params['field'];
        $value = $params['value'];

        $setMethod = "set$field";
        
        $participant->$setMethod($value)
                    ->save();
        
        return new Lms_Api_Response(200);
    }    

    protected static function _setPersonField($params)
    {
        $personId = $params['person_id'];
        $person = Lms_Item::create('Person', $personId);

        $field = $params['field'];
        $value = $params['value'];

        $setMethod = "set$field";
        
        $person->$setMethod($value)
               ->save();
        
        return new Lms_Api_Response(200);
    }   

    protected static function _setUserField($params)
    {
        if (!Lms_User::getUser()->isAllowed("user", "edit")) {
            return new Lms_Api_Response(403);
        }

        $userId = $params['user_id'];
        $user = Lms_Item::create('User', $userId);

        $field = $params['field'];
        $value = $params['value'];

        $keys = explode('/', $field);
        $mainField = array_shift($keys);
 
        $getMethod = "get$mainField";
        $setMethod = "set$mainField";
        
        $sourceValue = $user->$getMethod();
        
        $ref =& $sourceValue; 
        foreach ($keys as $key) {
            if (!isset($ref[$key])) {
                $ref[$key] = null;
            }
            $ref =& $ref[$key];
        }
        $ref = $value;
        
        $user->$setMethod($sourceValue)
             ->save();
        
        return new Lms_Api_Response(200);
    }    
    
    
    protected static function _insertMoviePerson($params)
    {
        $movieId = $params['movie_id'];
        $persones = $params['persones'];
        
        $movie = Lms_Item::create('Movie', $movieId);
        
        foreach ($persones as $person) {
            $personItem = Lms_Item_Person::getByMiscOrCreate($person['names']);
            $roleItem = Lms_Item::create('Role', $person['role_id']);
            
            $item = Lms_Item::create('Participant');
            $item->setMovieId($movie->getId())
                 ->setRoleId($roleItem->getId())
                 ->setPersonId($personItem->getId());
            $item->save();
        }
        
        return new Lms_Api_Response(200);
    }
    
    protected static function _removeParticipant($params)
    {
        $participantId = $params['participant_id'];
        $participant = Lms_Item::create('Participant', $participantId);
        
        $participant->delete();
        
        return new Lms_Api_Response(200);
    }    
    
    protected static function _addFile($params)
    {
        $movieId = $params['movie_id'];
        $movie = Lms_Item::create('Movie', $movieId);

        $path = $params['path'];
        $path = Lms_Application::normalizePath($path);
        if (!Lms_Ufs::file_exists($path)) {
            return new Lms_Api_Response(500, "Файл $path не найден");
        }

        $db = Lms_Db::get('main');
        $db->transaction();

        $files = Lms_Item_File::parseFiles($path);
        //$movie->addFilesByStruct($files);
        $movie->updateFilesByStruct($files);
        
        $db->commit();
        
        return new Lms_Api_Response(200);
    }    
    
    protected static function _removeFile($params)
    {
        $fileId = $params['file_id'];
        if (!$fileId) {
            return new Lms_Api_Response(400, "Need file_id");
        }
        $file = Lms_Item::create('File', $fileId);
        
        $file->delete();
        
        return new Lms_Api_Response(200);
    }    

    protected static function _generateFrames($params)
    {
        $filesIds = $params['files_ids'];
        
        foreach ($filesIds as $fileId) {
            if ($fileId) {
                $file = Lms_Item::create('File', $fileId);
                $file->generateFrames()
                    ->save();
            }
        }
        
        return new Lms_Api_Response(200);
    }    
    
    protected static function _reparseFiles($params)
    {
        $movieId = $params['movie_id'];
        if (!$movieId) {
            return new Lms_Api_Response(400, "Need movie_id");
        }
        $movie = Lms_Item::create('Movie', $movieId);
        
        $filesIds = $params['files_ids'];
        
        $db = Lms_Db::get('main');
        $db->transaction();
        foreach ($filesIds as $fileId) {
            $file = Lms_Item::create('File', $fileId);
            $path = Lms_Application::normalizePath($file->getPath());
            if (!Lms_Ufs::file_exists($path)) {
                continue;
            }
            if (Lms_Item_FileTask::pathInTasks($path)) {
                Lms_Debug::warn("Cannot reparse '$path' while working files tasks");
                continue;
            }

            $files = Lms_Item_File::parseFiles($path);
            $movie->updateFilesByStruct($files);
        }
        $db->commit();
        
        return new Lms_Api_Response(200);
    }    
    
    protected static function _removeMovie($params)
    {
        $movieId = $params['movie_id'];
        if (!$movieId) {
            return new Lms_Api_Response(400, "Need movie_id");
        }
        $movie = Lms_Item::create('Movie', $movieId);
        
        $db = Lms_Db::get('main');
        $db->transaction();

        $movie->delete();
        
        $db->commit();
        
        return new Lms_Api_Response(200);
    }    
    
    protected static function _getPersones($params)
    {
        $offset = isset($params['offset'])? (int)$params['offset'] : 0;
        $size = isset($params['size'])? (int)$params['size'] : 500;
        $sort = isset($params['sort'])? $params['sort'] : 'person_id';
        $order = isset($params['order'])? $params['order'] : -1;
        $filter = isset($params['filter'])? $params['filter'] : array();

        $persones = Lms_Item_Person::selectAsStruct($total, $offset, $size, $sort, $order, $filter);

        $result['persones'] = $persones;
        $result['offset'] = $offset;
        $result['size'] = $size;
        $result['total'] = $total;
        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _getPerson($params)
    {
        $personId = $params['person_id'];

        $person = Lms_Item::create('Person', $personId);

         	 	 	 	 	 	 	 	 	 		 	 	 	
        $result['person'] = array(
            'person_id' => $person->getId(),
            'name' => $person->getName(),
            'international_name' => $person->getInternationalName(),
            'info' => $person->getInfo(),
            'photos' => $person->getPhotos(),
            'url' => $person->getUrl(),
            'updated_at' => $person->getUpdatedAt(),
        );

        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _getUsers($params)
    {
        $offset = isset($params['offset'])? (int)$params['offset'] : 0;
        $size = isset($params['size'])? (int)$params['size'] : 500;
        $sort = isset($params['sort'])? $params['sort'] : 'id';
        $order = isset($params['order'])? $params['order'] : -1;
        $filter = isset($params['filter'])? $params['filter'] : array();

        $users = Lms_Item_User::selectAsStruct($total, $offset, $size, $sort, $order, $filter);

        $result['users'] = $users;
        $result['offset'] = $offset;
        $result['size'] = $size;
        $result['total'] = $total;
        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _getUser($params)
    {
        $db = Lms_Db::get('main');
        $userId = $params['user_id'];

        $user = Lms_Item::create('User', $userId);

         	 	 	 	 	 	 	 	 	 		 	 	 	
        $result['user'] = array(
            'ID' => $user->getId(),
            'Login' => $user->getLogin(),
            'Email' => $user->getEmail(),
            'IP' => $user->getIP(),
            'UserGroup' => $user->getUserGroup(),
            'RegisterDate' => $user->getRegisterDate(),
            'Enabled' => $user->getEnabled(),
        );

        return new Lms_Api_Response(200, null, $result);
    }
    
    protected static function _searchGoogleImages($params)
    {
        $query = $params['query'];
        $type = $params['type'];
        if (!$query) {
            return new Lms_Api_Response(400, "Need query");
        }
        $data = Lms_Service_Google::searchImages($query, 'CP1251');
        $searchResults = array();
        $sortarray = array();
        foreach ($data['search_results'] as $value) {
            if ($type == 'vertical') {
                if (($value["height"]/$value["width"])<1.25) {
                    continue;
                }
            } else if ($type == 'horizontal') {
                if (($value["width"]/$value["height"])<1.25) {
                    continue;
                }
            }
            $value[] = 0;
            $searchResults[] = $value;
            $sortarray[] = $value['height']*$value['width'];
        }
        array_multisort($sortarray, SORT_DESC, $searchResults);
        
        $result = array(
            'search_results' => $searchResults
        );
        
        return new Lms_Api_Response(200, null, $result);
    }

    protected static function _getAttachInfo($params)
    {
        $movieId = $params['movie_id'];

        $movie = Lms_Item::create('Movie', $movieId);

        $result = array(
            'folders' => $movie->getFolders(),
            'files' => $movie->getFilesAsArray($audioTracksCount),
            'audio_tracks_count' => $audioTracksCount,
            'quality' => $movie->getQuality(),
            'translation' => $movie->getTranslation(),
        );

        return new Lms_Api_Response(200, null, $result);
    }
    
    protected static function _attachFile($params)
    {
        $incomingId = $params['incoming_id'];
        $incoming = Lms_Item::create('Incoming', $incomingId);
        if (!$incoming->getFiles()) {
            $incoming->parseFiles();
        }
        
        $movieId = $params['movie_id'];
        $movie = Lms_Item::create('Movie', $movieId);

        $targetPath = isset($params['target_path'])? Lms_Application::normalizePath(trim($params['target_path'])) : null;
        $deleteFilesIds = isset($params['delete_files'])? $params['delete_files'] : array();
        
        $deleteFiles = array();
        foreach ($deleteFilesIds as $fileId) {
            if ($fileId) {
                $file = Lms_Item::create('File', $fileId);
                $deleteFiles[$file->getPath()] = $file;           
            }
        }
        krsort($deleteFiles);
        foreach ($deleteFiles as $file) {
            $path = $file->getPath();
            $file->delete();
            Lms_Debug::debug("Delete $path");
            if (Lms_Ufs::is_dir($path)) {
                Lms_Ufs::rmdir($path);
            } else {
                Lms_Ufs::unlink($path);
            }
        }
        
        $db = Lms_Db::get('main');
        $db->transaction();
        
        $translation = $incoming->getTranslation();
        if (isset($translation['global']) && $translation['global']) {
            $movie->setTranslation($translation['global']);
        }
        
        $quality = $incoming->getQuality();
        if (isset($quality['global']) && $quality['global']) {
            $movie->setQuality($quality['global']);
        }
        $movie->save();
        
        $movieFiles = $movie->getFiles();
        $movieFilesIndex = array();
        foreach ($movieFiles as $file) {
            $path = Lms_Application::normalizePath($file->getPath());
            $movieFilesIndex[$path] = 1;
        }
        
        
        $sourcePath = Lms_Application::normalizePath($incoming->getPath());
         
        $files = $incoming->getFiles();
        
        if (!$targetPath) {
            //Если импорт в отдельную директорию определяем путь
            $targetStorage = Lms_Application::getTargetStorage();
            if ($targetStorage) {
                //создаем путь в свободном хранилище
                $directory = $movie->getInternationalName()?: $movie->getName();
                $directory .= " (" . $movie->getYear() . ")";
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
        }
        foreach ($incoming->getFiles() as $n => $file) {
            $path = $file['path'];
            
            $fileItem = Lms_Item::create('File');

            if ($targetPath && $targetPath!=$sourcePath) {
                if (Lms_Application::isWindows()) {
                    $path = str_ireplace($sourcePath, $targetPath, $path);
                } else {
                    $path = str_replace($sourcePath, $targetPath, $path);
                }
                if (Lms_Ufs::is_file($path)) {
                    throw new Lms_Exception("File '$path' already exists");
                }
                Lms_Item_FileTask::create($file['path'], $path);
                if (isset($movieFilesIndex[$path])) {
                    continue;
                }
                $fileItem->setActive(0);
            }
            $fileItem->setName(basename($path))
                     ->setIsDir($file['is_dir'])
                     ->setPath($path)
                     ->setSize($file['size']? $file['size'] : 0)
                     ->setMetainfo(isset($file['metainfo'])? $file['metainfo'] : null)
                     ->setQuality(isset($quality[$n])? $quality[$n] : '')
                     ->setTranslation(isset($translation[$n])? $translation[$n] : '');

            $movie->add($fileItem);
        }

        if (!empty($params['up'])) {
            $movie->setUpdatedAt(date('Y-m-d H:i:s'))
                  ->save();
        }
        
        $incoming->setActive(0)
                 ->save();
        
        $db->commit();
        
        Lms_Application::tryRunTasks(true);

        return new Lms_Api_Response(200);
    }
    
    protected static function _getFilesTasks($params)
    {
        $filesTasks = Lms_Item_FileTask::select(0, 0);
        $result = array();
        foreach ($filesTasks as $filesTask) {
            $size = Lms_Ufs::is_file($filesTask->getFrom())? Lms_Ufs::filesize($filesTask->getFrom()) : 0;
            $done = Lms_Ufs::is_file($filesTask->getTo())? Lms_Ufs::filesize($filesTask->getTo()) : 0;
            $task = array(
                'from' => $filesTask->getFrom(),
                'to' => $filesTask->getTo(),
                'size' => $size,
                'done' => $done,
                'tries' => $filesTask->getTries(),
                'created_at' => $filesTask->getCreatedAt(),
            );
            $result['files_tasks'][] = $task;
        }
        return new Lms_Api_Response(200, null, $result);
    }    

    protected static function _getCurrentStatus($params)
    {
        $filesTasks = Lms_Item_FileTask::select(0, 0);
        $result = array();
        $result['files_tasks'] = array();
        foreach ($filesTasks as $filesTask) {
            $size = Lms_Ufs::is_file($filesTask->getFrom())? Lms_Ufs::filesize($filesTask->getFrom()) : 0;
            $done = 0;
            $task = array(
                'from' => $filesTask->getFrom(),
                'to' => $filesTask->getTo(),
                'size' => $size,
                'done' => $done,
                'tries' => $filesTask->getTries(),
                'created_at' => $filesTask->getCreatedAt(),
            );
            $result['files_tasks'][] = $task;
        }
        $logs = Lms_Item_Log::selectLast(array(
            Lms_Item_Log::TYPE_PERSONES_FIX, 
            Lms_Item_Log::TYPE_RATINGS_UPDATE,
            Lms_Item_Log::TYPE_RATINGS_LOCAL_UPDATE,
            Lms_Item_Log::TYPE_FILES_CHECK
        ));
        foreach ($logs as $log) {
            $type = $log->getType();
            $result[$type] = array(
                'log_id' => $log->getId(),
                'pid' => $log->getPid(),
                'status' => $log->getStatus(),
                'started_at' => $log->getStartedAt(),
                'ended_at' => $log->getEndedAt(),
                'message' => $log->getMessage(),
                'has_report' => (strlen($log->getReport())>0)
            );
        }
        
        
        return new Lms_Api_Response(200, null, $result);
    }    
    
    
    protected static function _updateRatings($params)
    {
        Lms_Application::runTask(Lms_Application::TASK_RATINGS_UPDATE);
        //Lms_Item_Rating::updateRatings();
        return new Lms_Api_Response(200);
    }
    
    protected static function _updateLocalRatings($params)
    {
        Lms_Application::runTask(Lms_Application::TASK_RATINGS_LOCAL_UPDATE);
        return new Lms_Api_Response(200);
    }

    protected static function _fixPersones($params)
    {
        Lms_Application::runTask(Lms_Application::TASK_PERSONES_FIX);
        //Lms_Item_Person::fixAll();
        return new Lms_Api_Response(200);
    }

    protected static function _checkFiles($params)
    {
        Lms_Application::runTask(Lms_Application::TASK_FILES_CHECK);
        return new Lms_Api_Response(200);
    }
    
    protected static function _getReport($params)
    {
        $logId = $params['log_id'];
        if (!$logId) {
            return new Lms_Api_Response(400);
        }
        
        $log = Lms_Item::create('Log', $logId);

        $result = Lms_Text::htmlizeText($log->getReport());

        return new Lms_Api_Response(200, null, $result);
    }
    
    protected static function _relocateLostFiles($params)
    {
        $db = Lms_Db::get('main');
        
        $result = $db->query('UPDATE files f INNER JOIN `files_lost` fl USING(file_id) SET f.path = fl.path WHERE 1');
        $db->query('DELETE FROM `files_lost`');
        
        return new Lms_Api_Response(200, null, $result);
    }
    
    protected static function _hideBrokenMovies($params)
    {
        $db = Lms_Db::get('main');
        
        $result = $db->query('UPDATE `movies` INNER JOIN movies_files USING(movie_id) INNER JOIN `files` f USING(file_id) SET `hidden`=1 WHERE f.`active`=0');
        
        return new Lms_Api_Response(200, null, $result);
    }
    
    protected static function _resetFilesTasksTries($params)
    {
        Lms_Item_FileTask::resetTries();
        return new Lms_Api_Response(200);
    }

    protected static function _clearFilesTasks($params)
    {
        Lms_Item_FileTask::clear();
        return new Lms_Api_Response(200);
    }
    
    protected static function _checkUpdates()
    {
        $result = Lms_Application::checkUpdates();
        return new Lms_Api_Response(200, null, $result);
    }
    
    protected static function _upgrade($params)
    {
        $confirm = $params['confirm'];
        $result = Lms_Application::upgrade($confirm);
        return new Lms_Api_Response(200, null, $result);
    }
    
    
    protected static function _getSettings($params)
    {
        if (!Lms_User::getUser()->isAllowed("settings", "edit")) {
            return new Lms_Api_Response(403);
        }
        $result = array();
        $result['config']['title'] = Lms_Application::getConfigDefault('title');
        $result['config']['topmenu_links'] = Lms_Application::getConfigDefault('topmenu_links');
        $result['config']['support_links'] = Lms_Application::getConfigDefault('support_links');
        $result['config']['rating/count'] = Lms_Application::getConfigDefault('rating', 'count');

        $result['config']['incoming/root_dirs'] = Lms_Application::getConfigDefault('incoming', 'root_dirs');
        $result['config']['incoming/page_size'] = Lms_Application::getConfigDefault('incoming', 'page_size');
        $result['config']['incoming/limit'] = Lms_Application::getConfigDefault('incoming', 'limit');
        $result['config']['incoming/ignore_files'] = Lms_Application::getConfigDefault('incoming', 'ignore_files');
        $result['config']['incoming/cache_time'] = Lms_Application::getConfigDefault('incoming', 'cache_time');
        $result['config']['incoming/hide_import'] = Lms_Application::getConfigDefault('incoming', 'hide_import');
        $result['config']['incoming/force_tasks'] = Lms_Application::getConfigDefault('incoming', 'force_tasks');
        $result['config']['incoming/storages'] = Lms_Application::getConfigDefault('incoming', 'storages');


        $result['config']['parser_service/username'] = Lms_Application::getConfigDefault('parser_service', 'username');
        $result['config']['parser_service/password'] = Lms_Application::getConfigDefault('parser_service', 'password');
        $result['config']['parser_service/builtin'] = Lms_Application::getConfigDefault('parser_service', 'builtin');

        $result['config']['filesystem/ls_dateformat_in_iso8601'] = Lms_Application::getConfigDefault('filesystem', 'ls_dateformat_in_iso8601');
        $result['config']['filesystem/disable_4gb_support'] = Lms_Application::getConfigDefault('filesystem', 'disable_4gb_support');
        $result['config']['filesystem/encoding/default'] = Lms_Application::getConfigDefault('filesystem', 'encoding', 'default');
        $result['config']['filesystem/directories'] = Lms_Application::getConfigDefault('filesystem', 'directories');

        $result['config']['ga/account'] = Lms_Application::getConfigDefault('ga', 'account');
        $result['config']['ga/domain_name'] = Lms_Application::getConfigDefault('ga', 'domain_name');
        $result['config']['ga/js'] = Lms_Application::getConfigDefault('ga', 'js');

        $result['config']['mplayer/bin'] = Lms_Application::getConfigDefault('mplayer', 'bin');
        $result['config']['mplayer/tmp'] = Lms_Application::getConfigDefault('mplayer', 'tmp');
        
        $result['config']['files/tth/bin'] = Lms_Application::getConfigDefault('files','tth', 'bin');
        $result['config']['files/tth/enabled'] = Lms_Application::getConfigDefault('files','tth', 'enabled');
        
        $result['config']['download/license'] = Lms_Application::getConfigDefault('download', 'license');
        
        $result['config']['download/masks'] = Lms_Application::getConfigDefault('download', 'masks');
        $result['config']['download/license'] = Lms_Application::getConfigDefault('download', 'license');
        $result['config']['download/escape/enabled'] = Lms_Application::getConfigDefault('download', 'escape', 'enabled');
        $result['config']['download/escape/ie'] = Lms_Application::getConfigDefault('download', 'escape', 'ie');
        $result['config']['download/escape/encoding'] = Lms_Application::getConfigDefault('download', 'escape', 'encoding');

        $result['config']['download/smb'] = Lms_Application::getConfigDefault('download', 'smb');
        $result['config']['download/selectable'] = Lms_Application::getConfigDefault('download', 'selectable');
        $result['config']['download/defaults'] = Lms_Application::getConfigDefault('download', 'defaults');
        $result['config']['download/players/selectable/la'] = Lms_Application::getConfigDefault('download', 'players', 'selectable', 'la');
        $result['config']['download/players/selectable/mp'] = Lms_Application::getConfigDefault('download', 'players', 'selectable', 'mp');
        $result['config']['download/players/selectable/mpcpl'] = Lms_Application::getConfigDefault('download', 'players', 'selectable', 'mpcpl');
        $result['config']['download/players/selectable/bsl'] = Lms_Application::getConfigDefault('download', 'players', 'selectable', 'bsl');
        $result['config']['download/players/selectable/crp'] = Lms_Application::getConfigDefault('download', 'players', 'selectable', 'crp');
        $result['config']['download/players/selectable/tox'] = Lms_Application::getConfigDefault('download', 'players', 'selectable', 'tox');
        $result['config']['download/players/selectable/kaf'] = Lms_Application::getConfigDefault('download', 'players', 'selectable', 'kaf');
        $result['config']['download/players/selectable/pls'] = Lms_Application::getConfigDefault('download', 'players', 'selectable', 'pls');
        $result['config']['download/players/selectable/xspf'] = Lms_Application::getConfigDefault('download', 'players', 'selectable', 'xspf');
        $result['config']['download/players/default'] = Lms_Application::getConfigDefault('download', 'players', 'default');

        $result['config']['translation_options'] = Lms_Application::getConfigDefault('translation_options');
        $result['config']['quality_options'] = Lms_Application::getConfigDefault('quality_options');

        $result['config']['auth/register_timeout'] = Lms_Application::getConfigDefault('auth', 'register_timeout');
        $result['config']['auth/cookie/crypt'] = Lms_Application::getConfigDefault('auth', 'cookie', 'crypt');
        $result['config']['auth/cookie/key'] = Lms_Application::getConfigDefault('auth', 'cookie', 'key');
        
        $result['config']['timezone'] = @date_default_timezone_get();

        $result['config']['trailers/download'] = Lms_Application::getConfigDefault('trailers', 'download');
        
        $result['db'] = array();
        $db = Lms_Db::get('main');
        $rows = $db->select('SELECT `key` AS ARRAY_KEY, type, value, active FROM `config`');
        foreach ($rows as $key => $row) {
            switch ($row['type']) {
                case 'array': 
                    $value = unserialize($row['value']);
                    break;
                case 'scalar': 
                default: 
                    $value = $row['value'];
                    break;
            }
            $result['db'][$key] = array(
                'value' => $value,
                'active' => $row['active']
            );
        }
        return new Lms_Api_Response(200, null, $result);
    }    

    protected static function _saveSettings($params)
    {
        if (!Lms_User::getUser()->isAllowed("settings", "edit")) {
            return new Lms_Api_Response(403);
        }
        
        Lms_Debug::debug($params['data']);

        $db = Lms_Db::get('main');
        
        foreach ($params['data'] as $key => $row) {
            if (isset($row['active']) && $row['active']==0) {
                $db->query('DELETE FROM `config` WHERE `key`=?', $key);
                continue;
            }
            if (array_key_exists('value', $row)) {
                switch ($row['type']) {
                    case 'array':
                        $value = $row['value'];
                        $value = Lms_Translate::translate('CP1251', 'UTF-8', $value);
                        $value = Zend_Json::decode($value);
                        array_walk_recursive($value, array(__CLASS__, '_cp1251ToUtf'));
                        $row['value'] = serialize($value);
                        break;
                    case 'scalar': 
                    default: 
                        $row['value'] = $row['value'];
                        break;
                }
            }
            $db->query('INSERT INTO `config` SET `key`=?, ?a ON DUPLICATE KEY UPDATE ?a', $key, $row, $row);
        }
        
        return new Lms_Api_Response(200);
    }    
    
    private static function _cp1251ToUtf(&$text, $key)
    {
        $text = Lms_Translate::translate('UTF-8', 'CP1251', $text);
    }
    
}
