<?php

class Lms_Item_Person extends Lms_Item_Abstract {
    
    public static function getTableName()
    {
        return '?_persones';
    }
    
    protected function _postInsert() 
    {
        $this->updateSearchIndex();
    }    

    protected function _preDelete() 
    {
        foreach ($this->getChilds('Participant') as $item) {
            $item->delete();
        }
        $db = Lms_Db::get('main');
        $db->query('DELETE FROM search_trigrams WHERE `type`=? AND `id`=?d', 'person', $this->getId());
        $db->query('DELETE FROM suggestion WHERE `type`=? AND `id`=?d', 'person', $this->getId());
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
    
    public static function getByMisc($names, $url)
    {
        $db = Lms_Db::get('main');
        
        if ($url) {
            $row = $db->selectRow(
                'SELECT * FROM ' . self::getTableName() . ' WHERE url=? LIMIT 1',
                $url
            );
            if ($row) {
                return self::rowToItem($row);
            }
        }

        $row = $db->selectRow(
            'SELECT * FROM ' . self::getTableName() . ' WHERE `name` IN (?a) OR `international_name` IN(?a) LIMIT 1',
            array_filter($names),
            array_filter($names)
        );
        return self::rowToItem($row);
    }

    public static function getByMiscOrCreate($names, $url = false)
    {
        $item = self::getByMisc($names, $url);
        if (!$item) {
            $item = Lms_Item::create('Person');
            $detectedNames = Lms_Application::_detectNames($names);
            $item->setName($detectedNames['name'])
                 ->setInternationalName($detectedNames['international_name'])
                 ->setInfo('')
                 ->setUrl($url)
                 ->save();
        }
        return $item;
    }

    public function getPhoto()
    {
        $photos = array_values(array_filter(array_merge(
            preg_split("/(\r\n|\r|\n)/", $this->getPhotos())
        )));
        return array_shift($photos);
    }

    public static function selectForParsing($limit = 10)
    {
        $db = Lms_Db::get('main');
        $rows = $db->selectPage(
            $total,
            'SELECT * FROM ' . self::getTableName() . ' WHERE `updated_at`=\'0000-00-00 00:00:00\' AND LENGTH(`url`) AND `tries`<10 ORDER BY `tries` LIMIT ?d', 
            $limit
        );
        return Lms_Item_Abstract::rowsToItems($rows);
    }
    
    public function parse()
    {
        $this->setTries($this->getTries()+1);
        try {
            if ($this->getUrl()) {
                $data = Lms_Service_Movie::parsePerson($this->getUrl());
                if ($data && (!empty($data['name']) || !empty($data['international_name']))) {
                    $photos = array_values(array_filter(array_merge(
                        preg_split("/(\r\n|\r|\n)/", $this->getPhotos())
                    )));
                    $this->setName($data['name'])
                         ->setInternationalName(isset($data['international_name'])? $data['international_name'] : '')
                         ->setPhotos(implode("\n", array_unique(array_merge($data['photos'], $photos))))
                         ->setUpdatedAt(date('Y-m-d H:i:s'));
                    if (strlen($data['info'])>strlen($this->getInfo())) {
                        $this->setInfo($data['info']);
                    }
                } else {
                    $this->setUpdatedAt(date('Y-m-d H:i:s'));
                }
            }
        } catch (Lms_Service_DataParser_Exception $e) {
            Lms_Debug::err($e->getMessage());
        }
        return $this;
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
            "SELECT 
                person_id, name, international_name 
            FROM persones 
            WHERE 1=1 
                {AND (name LIKE ? OR international_name LIKE ?)}
            ORDER BY ?# $order {LIMIT ?d, ?d}", 
            !empty($filter['name'])? "%{$filter['name']}%" : DBSIMPLE_SKIP, !empty($filter['name'])? "%{$filter['name']}%" : DBSIMPLE_SKIP,
            $sort,
            $offset!==null? $offset : DBSIMPLE_SKIP, 
            $size!==null? $size : DBSIMPLE_SKIP
        );
        return $rows;
    }    
    
    public function updateSearchIndex()
    {
        $this->_masterDb->query('DELETE FROM `search_trigrams` WHERE `type`=? AND `id`=?d', 'person', $this->getId());
        $this->_masterDb->query('DELETE FROM `suggestion` WHERE `type`=? AND `id`=?d', 'person', $this->getId());
 
        $trigramValues = array();
        $suggestionValues = array();
        Lms_Application::prepareTextIndex($this->getName(), 'person', $this->getId(), $trigramValues, $suggestionValues);
        if ($this->getInternationalName()!=$this->getName()) {
            Lms_Application::prepareTextIndex($this->getInternationalName(), 'person', $this->getId(), $trigramValues, $suggestionValues);
        }
        if ($trigramValues) {
            $this->_masterDb->query('INSERT IGNORE INTO `search_trigrams`(`trigram`,`type`, `id`) VALUES ' . implode(', ', $trigramValues));
        } 
        
        if ($suggestionValues) {
            $this->_masterDb->query('INSERT IGNORE INTO `suggestion`(`word`,`type`, `id`) VALUES ' . implode(', ', $suggestionValues));
        }
    }

    private static function getHostByUrl($url)
    {
        if (preg_match('{//(?:www\.)?([^/]+)}i', $url, $matches)) {
            return $matches[1];
        } else {
            return '';
        }
    }
    
    private static function mergeFieldBySize($fields)
    {
        $result = '';
        foreach ($fields as $field) {
            if (strlen($field) > strlen($result)) {
                $result = $field;
            }
        }
        return $result;
    }
    
    private static function mergeFieldByPriority($fields, $priority)
    {
        foreach ($priority as $service) {
            if (!empty($fields[$service])) {
                return $fields[$service];
            }
        }
        return self::mergeFieldBySize($fields);
    }
    
    public static function mergePersones($persones)
    {
        if (count($persones)<2) {
            return;
        }
        //Lms_Debug::debug('mergePersones');
        $serviceCounter = array();
        $allFields = array();
        foreach ($persones as $n => $person) {
            //Lms_Debug::debug($person->getUrl());
            if ($url = $person->getUrl()) {
                $service = self::getHostByUrl($url);
                $serviceCounter[$service][$url] = 1;
                if (count($serviceCounter[$service])>1) {
                    Lms_Debug::debug("Persones is not duplicates: " . implode(', ', array_keys($serviceCounter[$service])));
                    return;
                }
            } else {
                $service = $n;
            }
            $allFields['name'][$service] = $person->getName();
            $allFields['international_name'][$service] = $person->getInternationalName();
            $allFields['info'][$service] = $person->getInfo();
            $allFields['photos'][$service] = array_values(array_filter(array_merge(
                preg_split("/(\r\n|\r|\n)/", $person->getPhotos())
            )));
            $allFields['url'][$service] = $url;
        }
        //Lms_Debug::debug($allFields);
        $firstPerson = array_shift($persones);
        $firstPerson->setName(self::mergeFieldByPriority($allFields['name'], array('kinopoisk.ru', 'ozon.ru', 'world-art.ru')));
        $firstPerson->setInternationalName(self::mergeFieldByPriority($allFields['international_name'], array('imdb.com', 'kinopoisk.ru', 'ozon.ru', 'world-art.ru')));
        $firstPerson->setInfo(self::mergeFieldBySize($allFields['info']));
        $photos = array();
        foreach (array('kinopoisk.ru', 'ozon.ru') as $service) {
            if (!empty($allFields['photos'][$service])) {
                $photos = array_merge($photos, $allFields['photos'][$service]);
                unset($allFields['photos'][$service]);
            }
        }
        foreach ($allFields['photos'] as $servicePhotos) {
            if (!empty($servicePhotos)) {
                $photos = array_merge($photos, $servicePhotos);
            }
        }
        $firstPerson->setPhotos(implode("\n", array_unique($photos)));
        $firstPerson->setUrl(self::mergeFieldByPriority($allFields['url'], array('kinopoisk.ru', 'ozon.ru', 'world-art.ru')));
        $firstPerson->save();
        
        $db = Lms_Db::get('main');
        foreach ($persones as $person) {
            $db->query(
                'UPDATE IGNORE participants SET person_id=?d WHERE person_id=?d', 
                $firstPerson->getId(), 
                $person->getId()
            );
            $person->delete();
        }
        return true;
    }
    
    public static function fixAll()
    {
        $result = array(
            'merged' => 0,
        );
        $db = Lms_Db::get('main');
        
        //fix kinopoisk url
        $db->query('UPDATE `persones` SET `url`=REPLACE(`url`, ?, ?) WHERE `url` LIKE ?', 'level/4/people', 'name', 'http://www.kinopoisk.ru/level/4/people/%');
        
        $names = $db->selectCol('SELECT `name` FROM `persones` WHERE LENGTH(`name`)>0 GROUP BY `name` HAVING(count(*)>1)');
        if ($names) {
            $rows = $db->select('SELECT * FROM `persones` WHERE `name` IN(?a)', $names);
            $groups = array();
            foreach ($rows as $row) {
                $name = $row['name'];
                $groups[$name][] = self::rowToItem($row);
            }
            foreach ($groups as $persones) {
                $db->transaction();
                if (self::mergePersones($persones)) {
                    $result['merged']++;
                }
                $db->commit();
            }
        }
        
        $names = $db->selectCol('SELECT `international_name` FROM `persones` WHERE LENGTH(`international_name`)>0 GROUP BY `international_name` HAVING(count(*)>1)');
        if ($names) {
            $rows = $db->select('SELECT * FROM `persones` WHERE `international_name` IN(?a)', $names);
            $groups = array();
            foreach ($rows as $row) {
                $name = $row['international_name'];
                $groups[$name][] = self::rowToItem($row);
            }
            foreach ($groups as $persones) {
                $db->transaction();
                if (self::mergePersones($persones)) {
                    $result['merged']++;
                }
                $db->commit();
            }
        }        
        
        //Удаляем персоналии не связанные с фильмами
        $result['persones_deleted'] = $db->query('DELETE `persones` FROM `persones` LEFT JOIN participants USING(person_id) WHERE participant_id IS NULL');
        //Удаляем из индекса поиска ссылки на несуществущие персоналии
        $db->query('DELETE search_trigrams FROM search_trigrams LEFT JOIN persones ON(type=\'person\' AND id=person_id) WHERE type=\'person\' AND person_id IS NULL');
        $db->query('DELETE suggestion FROM suggestion LEFT JOIN persones ON(id=person_id AND type=\'person\') WHERE type=\'person\' AND person_id IS NULL');
        //Удаляем из фильмов ссылки на несуществущие персоналии
        $result['participants_deleted'] = $db->query('DELETE participants FROM participants LEFT JOIN persones USING(person_id) WHERE persones.person_id IS NULL');
        
        return $result;
    }    
}
