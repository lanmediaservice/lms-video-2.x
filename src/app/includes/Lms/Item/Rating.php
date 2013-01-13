<?php

class Lms_Item_Rating extends Lms_Item_Abstract {
    
    public static function getTableName()
    {
        return '?_ratings';
    }

    protected function _preInsert() 
    {
        /*if (!$this->getUpdatedAt()) {
            $this->setUpdatedAt(date('Y-m-d H:i:s'));
        }*/
    }
    
    protected function _preUpdate() 
    {
        /*$changes = Lms_Item_Store::getChanges(
            $this->getTableName(), $this->_scalarPkValue
        );
        if (!array_key_exists('updated_at', $changes)
            && (array_key_exists('count', $changes)
                || array_key_exists('value', $changes))
        ) {
            $this->setUpdatedAt(date('Y-m-d H:i:s'));
        }*/
    }
    
    public static function _customInitStructure(
        Lms_Item_Struct $struct,
        DbSimple_Generic_Database $masterDb,
        DbSimple_Generic_Database $slaveDb
    )
    {
        parent::_customInitStructure($struct, $masterDb, $slaveDb);
        $struct->addIndex('movie_id', array('movie_id'));
        $struct->addIndex('system', array('movie_id', 'system'));
    }

    public static function getBySystem($movieId, $system)
    {
        $scalarPks = Lms_Item_Store::getIndexedValues(
            self::getTableName(), 'system', Lms_Item_Scalar::scalarize(array('movie_id'=>$movieId, 'system'=>$system))
        );
        if ($scalarPks) {
            return Lms_Item::create('Rating', $scalarPks[0]);
        }
        $db = Lms_Db::get('main');
        $row = $db->selectRow('SELECT * FROM ' . self::getTableName() . ' WHERE movie_id=?d AND `system`=?', $movieId, $system);
        return self::rowToItem($row);
    }
    
    public static function getBySystemOrCreate($movieId, $system)
    {
        $item = self::getBySystem($movieId, $system);
        if (!$item) {
            $item = Lms_Item::create('Rating');
            $item->setSystem($system)
                 ->setMovieId($movieId)
                 ->save();
        }
        return $item;
    }
    
    public static function updateRatings() 
    {
        $db = Lms_Db::get('main');

        $movies = array();
        $rows = $db->select('SELECT * FROM ratings WHERE `system` IN(?a)', array('kinopoisk', 'imdb'));
        self::rowsToItems($rows);//cache
        
        foreach ($rows as $row) {
            if ($row['system']=='kinopoisk') {
                $movies[$row['movie_id']]['kinopoisk_id'] = $row['system_uid'];
            }
            if ($row['system']=='imdb') {
                $movies[$row['movie_id']]['imdb_id'] = $row['system_uid'];
            }
        }
        
        $result = array(
            'kinopoisk_add' => 0,
            'kinopoisk_update' => 0,
            'imdb_add' => 0,
            'imdb_update' => 0,
        );
        $ratings = Lms_Service_Movie::updateRatings($movies);
        foreach ($ratings as $movieId => $movie) {
            if (!empty($movie['kinopoisk_id']) || !empty($movie['kinopoisk_rating_count'])) {
                $rating  = Lms_Item_Rating::getBySystemOrCreate($movieId, 'kinopoisk');
                if (!empty($movie['kinopoisk_id']) && !$rating->getSystemUid()) {
                    $rating->setSystemUid($movie['kinopoisk_id']);
                    $rating->setUpdatedAt($movie['kinopoisk_updated_at']);
                    $result['kinopoisk_add']++;
                }
                if (!empty($movie['kinopoisk_rating_count']) && $rating->getCount()<$movie['kinopoisk_rating_count']) {
                    $rating->setValue($movie['kinopoisk_rating_value']);
                    $rating->setCount($movie['kinopoisk_rating_count']);
                    $rating->setUpdatedAt($movie['kinopoisk_updated_at']);
                    $result['kinopoisk_update']++;
                }
                $rating->save();
            }
            if (!empty($movie['imdb_id']) || !empty($movie['imdb_rating_count'])) {
                $rating  = Lms_Item_Rating::getBySystemOrCreate($movieId, 'imdb');
                if (!empty($movie['imdb_id']) && !$rating->getSystemUid()) {
                    $rating->setSystemUid($movie['imdb_id']);
                    $rating->setUpdatedAt($movie['imdb_updated_at']);
                    $result['imdb_add']++;
                }
                if (!empty($movie['imdb_rating_count']) && $rating->getCount()<$movie['imdb_rating_count']) {
                    $rating->setValue($movie['imdb_rating_value']);
                    $rating->setCount($movie['imdb_rating_count']);
                    $rating->setUpdatedAt($movie['imdb_updated_at']);
                    $result['imdb_update']++;
                }
                $rating->save();
            }
        }
        return $result;
    }
    
    public static function getBayes($ratings, $min = 8, $avg=7.2453)
    {
        $sum = 0;
        $count = count($ratings);
        $result = array();
        $result['detail'] = array(1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0, 10=>0);
        foreach($ratings as $value){
            $result['detail'][$value]++;
            $sum += $value;
        }
        $averange = $count? $sum/$count : 0;
        $result['averange'] = $averange;
        $result['count'] = $count;
        $result['bayes'] = ($count>=$min) ? round($averange*($count/($count+$min)) + $avg*($min/($count+$min)), 4) : null;
        return $result;
    }
    
    public static function updateLocalRatings()
    {
        $db = Lms_Db::get('main');
        $rows = $db->select("SELECT movie_id, rating FROM movies_users_ratings");
        $userRatings = array();
        foreach ($rows as $row) {
            $movieId = $row['movie_id'];
            $userRatings[$movieId][] = $row['rating'];
        }
        
        $rows = $db->select('SELECT * FROM ratings WHERE `system`=?', 'local');
        self::rowsToItems($rows);//cache

        $result = array(
            'updated' => 0,
        );
        foreach ($userRatings as $movieId => $ratings) {
            $rating = self::getBayes($ratings, Lms_Application::getConfig('rating', 'count'));
            $localRating  = Lms_Item_Rating::getBySystemOrCreate($movieId, 'local');
            if ($localRating->getValue()!=$rating['bayes'] || $localRating->getCount()!=$rating['count']) {
                $localRating->setValue($rating['bayes'])
                            ->setCount($rating['count'])
                            ->setDetail($rating['detail'])
                            ->save();
                $result['updated']++;
            }
        }
        return $result;
    }
    
}
