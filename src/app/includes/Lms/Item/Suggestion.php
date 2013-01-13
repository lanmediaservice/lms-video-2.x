<?php

class Lms_Item_Suggestion extends Lms_Item_Abstract {
    
    public static function getTableName()
    {
        return '?_suggestion';
    }
    
    public static function getSuggestion($query, $limit = 6)
    {
        $db = Lms_Db::get('main');

        $wheres = array();
        $wheres[] = "`word` LIKE '" . mysql_real_escape_string($query) . "%'";
        $wheres[] = "`type` = 'movie'";
        $wheres[] = " hidden=0 ";

        $sql = "SELECT DISTINCT movie_id "
             . "FROM `movies`"
             . "INNER JOIN suggestion s ON (s.id = movie_id ) "
             . "WHERE " . implode(' AND ', $wheres) . " "
             . "ORDER BY rank DESC LIMIT ?d";
        $movies = $db->selectCol($sql, $limit);

        $wheres = array();
        $wheres[] = "`word` LIKE '" . mysql_real_escape_string($query) . "%'";
        $wheres[] = "`type` = 'person'";

        $sql = "SELECT DISTINCT person_id "
             . "FROM persones "
             . "INNER JOIN `suggestion` s ON (s.id = person_id) "
             . "WHERE " . implode(' AND ', $wheres) . " "
             . "ORDER BY rank DESC LIMIT ?d";

        $persones = $db->selectCol($sql, $limit);

        $count = count($movies)+count($persones);
        if ($count> $limit) {
            $proportion = count($movies)/$count;
            $countMovies = ceil($proportion * count($movies));
            $countPersones = $limit - $countMovies;
            $movies = array_slice($movies, 0, $countMovies);
            $persones = array_slice($persones, 0, $countPersones);
        }
        
        return array(
            'movies' => $movies,
            'persones' => $persones,
        );
        
    }
}
