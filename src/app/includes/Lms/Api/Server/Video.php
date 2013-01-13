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
class Lms_Api_Server_Video extends Lms_Api_Server_Abstract
{

    public static function getCatalog($params)
    {
        try {
            $genre = (isset($params['genre'])) ? (int) $params['genre'] : null;
            $country = (isset($params['country'])) ? (int) $params['country'] : null;
            $order = (isset($params['order'])) ? (int) $params['order'] : 0;
            $dir = (isset($params['dir'])) ? $params['dir'] : "";
            $offset = (isset($params['offset'])) ? (int) $params['offset'] : 0;
            $size = (isset($params['size'])) ? (int) $params['size'] : 20;

            $join = "";
            $wheres = array();

            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');
            
            if (!$user->isAllowed("movie", "moderate")) {
                $wheres[] = " m.hidden=0 ";
            }
            
            if ($genre) {
                $wheres[] = " movies_genres.genre_id=$genre ";
                $join .= " LEFT JOIN movies_genres USING(movie_id) ";
            }
            if ($country) {
                $wheres[] = " movies_countries.country_id=$country ";
                $join .= " LEFT JOIN movies_countries USING(movie_id) ";
            }

            $orderby = " ORDER BY ";
            switch ($order) {
                case 100:
                    $orderby .= " `movie_id` ";
                break;
                case 0:
                    $orderby .= " m.`updated_at` ";
                break;
                case 1:
                    $orderby .= " `year` ";
                break;
                case 2:
                    $orderby .= " `value` ";
                    $join .= " LEFT JOIN ratings USING(movie_id) ";
                    $wheres[] = " `system`='imdb' AND (`count`=0 OR `count` IS NULL OR `count`>100) ";
                break;
                case 9:
                    $orderby .= " `value` ";
                    $join .= " LEFT JOIN ratings USING(movie_id) ";
                    $wheres[] = " `system`='kinopoisk' AND `count`>100 ";
                break;
                case 3:
                    $orderby .= " `value` ";
                    $join .= " LEFT JOIN ratings USING(movie_id) ";
                    $wheres[] = " `system`='local' ";
                break;
                case 4:
                    $orderby .= " rating ";
                    $join .= " LEFT JOIN movies_users_ratings mur ON(m.movie_id=mur.movie_id AND mur.user_id=" . $user->getId() . ") ";
                break;
                case 6:
                    $orderby .= " hit ";
                break;
                case 8:
                    $orderby .= " rank ";
                break;
                default:
                    $orderby .= " m.`updated_at` DESC";
            }
            switch ($dir) {
                case 'DESC':
                case 'ASC':
                    $orderby .= " $dir ";
                    break;
                default:
                    $orderby .= " DESC ";
            }

            if ($order==3) {
                $orderby .= " , `count` DESC ";
            }
            
            if ($order==1) {
                $orderby .= ", m.`updated_at` DESC";
            } else {
                //$orderby .= ", movie_id";
            }

            $where = (count($wheres)) ? " WHERE ".implode(" AND ",$wheres) : "";

            $avgHit = $db->selectCell('SELECT sum(hit)/count(*) FROM movies WHERE hidden=0');
            $hitThreshold = round($avgHit*Lms_Application::getConfig('hit_factor'));

            $sql = "SELECT m.movie_id AS ARRAY_KEY
                    FROM movies m $join $where $orderby LIMIT ?d, ?d";
            
            $total = 0;
            $rows = $db->selectPage(
                $total, $sql, 
                $offset, $size
            );
            $result['total'] = $total;
            $result['offset'] = $offset;
            $result['pagesize'] = $size;
            
            $moviesIds = array_keys($rows);

            
            $sql = "SELECT m.movie_id AS ARRAY_KEY,
                        m.movie_id,
                        `name`,
                        international_name,
                        year,
                        m.created_at,
                        m.updated_at,
                        description,
                        `covers`,
                        translation,
                        quality,
                        hidden,
                        hit
                    FROM movies m WHERE m.movie_id IN(?a)";
            
            $movies = array();
            $rows = $db->select($sql, $moviesIds);
            foreach ($moviesIds as $movieId) {
                $movies[$movieId] = $rows[$movieId];
            }
            
            $rows = $db->select(
                "SELECT movie_id, name FROM movies_genres LEFT JOIN genres USING(genre_id) WHERE movie_id IN(?a)",
                $moviesIds
            );
            foreach ($rows as $row) {
                $movieId = $row['movie_id'];
                $movies[$movieId]['genres'][] = $row['name'];
            }

            $rows = $db->select(
                "SELECT movie_id, name FROM movies_countries LEFT JOIN countries USING(country_id) WHERE movie_id IN(?a)",
                $moviesIds
            );
            foreach ($rows as $row) {
                $movieId = $row['movie_id'];
                $movies[$movieId]['countries'][] = $row['name'];
            }

            $rows = $db->select(
                "SELECT movie_id, count(*) as c FROM comments LEFT JOIN movies_comments USING(comment_id) WHERE movie_id IN(?a) AND (ISNULL(to_user_id ) OR to_user_id  IN(0, ?d) OR user_id=?d) GROUP BY movie_id",
                $moviesIds,
                $user->getId(),
                $user->getId()
            );
            foreach ($rows as $row) {
                $movieId = $row['movie_id'];
                $movies[$movieId]['comments_count'] = $row['c'];
            }

            $rows = $db->select(
                "SELECT * FROM ratings WHERE movie_id IN(?a)",
                $moviesIds
            );
            foreach ($rows as $row) {
                $movieId = $row['movie_id'];
                $valueKey = "rating_" . $row['system'] . "_value";
                $countKey = "rating_" . $row['system'] . "_count";
                $movies[$movieId][$valueKey] = $row['value'];
                $movies[$movieId][$countKey] = $row['count'];
            }
            
            $rows = $db->select(
                "SELECT movie_id, p.`name`, p.`international_name`, r.`name` as `role`, r.`sort` FROM participants LEFT JOIN roles r USING(role_id) LEFT JOIN persones p USING(person_id) WHERE participants.movie_id IN(?a) ORDER BY `sort`, LENGTH(`photos`) DESC",
                $moviesIds
            );
            foreach ($rows as $row) {
                $movieId = $row['movie_id'];
                if ($row["role"]=="режиссер") {
                    if (!isset($movies[$movieId]['directors'])) {
                        $movies[$movieId]['directors'] = array();
                    }
                    if (count($movies[$movieId]['directors'])<=2) {
                        $movies[$movieId]['directors'][] = trim($row["name"])? $row["name"] : $row["international_name"];
                    }
                }
                if (in_array($row["role"], array("актер","актриса"))) {
                    if (!isset($movies[$movieId]['cast'])) {
                        $movies[$movieId]['cast'] = array();
                    }
                    if (count($movies[$movieId]['cast'])<=4) {
                        $movies[$movieId]['cast'][] = trim($row["name"]) ? $row["name"] : $row["international_name"];
                    }
                }
            }

            foreach ($movies as &$movie) {
                $movie["popular"] = $movie['hit']>$hitThreshold? true : false;
                $movie["hidden"] = $movie['hidden']? true : false;
                
                if (Lms_Application::getConfig('short_translation')) {
                    $movie["short_translation"] = strtr($movie["translation"], Lms_Application::getConfig('short_translation'));
                }

                if (Lms_Application::getConfig('short_description')) {
                    $movie["description"] = Lms_Text::tinyString(strip_tags($movie["description"]), Lms_Application::getConfig('short_description'), 1);
                } else{
                    unset($movie["description"]);
                }
            }
            Lms_Item_Movie::postProcess($movies, 100);
            
            $result['movies'] = array_values($movies);

            return new Lms_Api_Response(200, null, $result);
            
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
    
    public static function getGenres($params)
    {
        try {
            $country = isset($params['country']) ? (int) $params['country'] : null;
            $wheres = array();
            $join = "";

            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');
            
            if (!$user->isAllowed("movie", "moderate")) {
                $wheres[] = " movies.hidden=0 ";
            }
            if ($country) {
                $wheres[] = " movies_countries.country_id=$country ";
                $join .= " LEFT JOIN movies_countries USING (movie_id) ";
            }
            $where = (count($wheres)) ? " WHERE " . implode(" AND ", $wheres) : "";

            $result["genres"] = array();
            
            $sql = "SELECT g.genre_id as id, g.name, count(*) as `count` FROM genres g INNER JOIN movies_genres USING(genre_id) INNER JOIN movies USING(movie_id) $join $where GROUP BY g.genre_id ORDER BY g.`name`";
            $rows = $db->select($sql);
            foreach ($rows as $row) {
                $result["genres"][] = $row;
            }
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    
    public static function getCountries($params)
    {
        try {
            $genre = isset($params['genre']) ? (int) $params['genre'] : null;
            $wheres = array();
            $join = "";

            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');
            
            if (!$user->isAllowed("movie", "moderate")) {
                $wheres[] = " movies.hidden=0 ";
            }
            if ($genre) {
                $wheres[] = " movies_genres.genre_id=$genre ";
                $join .= " LEFT JOIN movies_genres USING (movie_id) ";
            }
            $where = (count($wheres)) ? " WHERE " . implode(" AND ", $wheres) : "";

            $result["countries"] = array();
            
            $sql = "SELECT c.country_id as id, c.name, count(*) as `count` FROM countries c INNER JOIN movies_countries USING(country_id) INNER JOIN movies USING(movie_id) $join $where GROUP BY c.country_id ORDER BY c.`name`";
            $rows = $db->select($sql);
            foreach ($rows as $row) {
                $result["countries"][] = $row;
            }
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
    
    public static function getLastComments($params)
    {
        try {
            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');
            $wheres = array();
            
            $wheres[] = "(ISNULL(to_user_id) OR to_user_id=0 {OR to_user_id=?d OR user_id=?d})";
            if (!$user->isAllowed("movie", "moderate")) {
                $wheres[] = " m.hidden=0 ";
            }
            $sql = "SELECT m.movie_id, m.name, max(c.comment_id) as last_comment_id "
                 . "FROM comments c INNER JOIN movies_comments USING(comment_id) INNER JOIN movies m USING(movie_id) "
                 . "WHERE " . implode(' AND ', $wheres) . " "
                 . "GROUP BY m.movie_id ORDER BY last_comment_id DESC LIMIT 0,20";
            
            $movies = $db->select(
                $sql,
                $user->getId()? $user->getId() : DBSIMPLE_SKIP,
                $user->getId()? $user->getId() : DBSIMPLE_SKIP
            );
            if (count($movies)) {
                $maxlength = 80;
                $commentsIds = array();
                foreach ($movies as $row) {
                    $commentsIds[] = $row['last_comment_id'];
                }
                array_multisort($commentsIds, SORT_DESC, $movies);
                
                $sql = 'SELECT c.comment_id AS ARRAY_KEY, users.Login as user_name, c.created_at, `Text` as `text` '
                     . 'FROM comments c INNER JOIN users ON(c.user_id=users.ID) ' 
                     . 'WHERE c.comment_id IN(?a) ORDER BY c.`created_at` DESC';
                $comments = $db->select($sql, $commentsIds);
                foreach ($movies as &$row) {
                    $commentId = $row['last_comment_id'];
                    $comment = $comments[$commentId];
                    $row['text'] = Lms_Text::tinyString($comment['text'], 500, 1);
                    $row['user_name'] = $comment["user_name"];
                    $row["created_at"] = $comment["created_at"];
                }
            }
            $result['movies'] = $movies;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
    
    public static function getLastRatings($params)
    {
        try {
            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');
            $wheres = array();
            if (!$user->isAllowed("movie", "moderate")) {
                $wheres[] = " m.hidden=0 ";
            }
            $sql = "SELECT m.movie_id, `name`, `rating` "
                 . "FROM movies_users_ratings mur LEFT JOIN movies m USING(movie_id) "
                 . (count($wheres)? "WHERE " . implode(' AND ', $wheres) . " " : "")
                 . "ORDER BY mur.`created_at` DESC LIMIT 0,20";
            
            $ratings = $db->select($sql);
            $result['movies'] = $ratings;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
        
    public static function getRandomMovie($params)
    {
        try {
            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');

            $avgHit = $db->selectCell('SELECT sum(Hit)/count(*) FROM movies WHERE hidden=0');
            $hitThreshold = round($avgHit*Lms_Application::getConfig('hit_factor'));
            $maxMovieId = $db->selectCell('SELECT MAX(movie_id) FROM movies WHERE hidden=0');
            $offsetId = rand(0, $maxMovieId);
            
            $wheres = array();
            if (!$user->isAllowed("movie", "moderate")) {
                $wheres[] = " movies.hidden=0 ";
            }
            $wheres[] = " movies.movie_id>=$offsetId";
            $sql = "SELECT movie_id, "
                 . "    name, "
                 . "    international_name, "
                 . "    `year`, "
                 . "    `covers`, "
                 . "    `hit` "
                 . "FROM movies "
                 . (count($wheres)? "WHERE " . implode(' AND ', $wheres) . " " : "")
                 . "LIMIT 1";
            $movie = $db->selectRow($sql);
            $result = array();
            if ($movie) {
                $movie["popular"] = $movie['hit']>$hitThreshold? 1 : 0;
                //$movie["international_name"] = htmlentities($movie["international_name"], ENT_NOQUOTES, 'cp1252');
                $covers = array_values(array_filter(array_merge(
                    preg_split("/(\r\n|\r|\n)/", $movie["covers"])
                )));
                $movie["cover"] = array_shift($covers);
                if ($movie["cover"]) {
                    $width = 100;
                    $height = 0;
                    $movie["cover"] = Lms_Application::thumbnail($movie["cover"], $width, $height);
                }
                unset($movie["covers"]);
                $result['movie'] = $movie;
            }
            
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function getPopMovies($params)
    {
        try {
            $count = isset($params['count']) ? (int) $params['count'] : 10;

            $db = Lms_Db::get('main');
            $sql = "SELECT movie_id, `name` FROM movies " 
                 . "WHERE hidden=0 " 
                 . "ORDER BY rank DESC LIMIT ?d";
            $movies = $db->select($sql, $count);
            $result['movies'] = $movies;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function getMovie($params)
    {
        try {
            $movieId = (int) $params['movie_id'];
            if (!$movieId) {
                return new Lms_Api_Response(400);
            }

            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');
            
            if ($user->getId()) {
                $db->query('UPDATE users SET ViewActivity=ViewActivity+1 WHERE ID=?d', $user->getId());
            }
            
            $avgHit = $db->selectCell('SELECT sum(Hit)/count(*) FROM movies WHERE hidden=0');
            $hitThreshold = round($avgHit*Lms_Application::getConfig('hit_factor'));
            
            $movieItem = Lms_Item::create('Movie', $movieId);
            $result = array();
            if ($movieItem && (!$movieItem->getHidden() || $user->isAllowed("movie", "moderate"))) {
                $movie = array(
                    'movie_id' => $movieItem->getId(),
                    'name' => $movieItem->getName(),
                    'international_name' => $movieItem->getInternationalName(),
                    'year' => $movieItem->getYear(),
                    'description' => $movieItem->getDescription(),
                    'mpaa' => $movieItem->getMpaa(),
                    'translation' => $movieItem->getTranslation(),
                    'quality' => $movieItem->getQuality(),
                    'created_at' => $movieItem->getCreatedAt(),
                    'updated_at' => $movieItem->getUpdatedAt(),
                    'covers' => $movieItem->getCovers(),
                    'hidden' => (bool) $movieItem->getHidden(),
                    'hit' => $movieItem->getHit(),
                    'present_by' => $movieItem->getPresentBy(),
                    'group' => $movieItem->getGroup(),
                    'rating_personal_value' => $movieItem->getUserRating(),
                    'created_by' => $movieItem->getCreatorName(),
                    'popular' => $movieItem->getHit()>$hitThreshold? true : false,
                );
                if ($movieItem->getTrailerLocalized()) {
                    $trailer = $movieItem->getTrailer();
                    $trailer['video'] = Lms_Application::urlToLocalizedVideo($trailer['video']);
                    $movie['trailer'] = $trailer;
                }

                //$movie["international_name"] = htmlentities($movie["international_name"], ENT_NOQUOTES, 'cp1252');

                $movie["genres"] = $db->selectCol('SELECT name FROM movies_genres LEFT JOIN genres USING(genre_id) WHERE movie_id=?d', $movieId);
                $movie["countries"] = $db->selectCol('SELECT name FROM movies_countries LEFT JOIN countries USING(country_id) WHERE movie_id=?d', $movieId);
                
                $rows = $db->select(
                    "SELECT p.*, r.name as `role`, `character` FROM participants LEFT JOIN roles r USING(role_id) LEFT JOIN persones p USING(person_id) WHERE participants.movie_id=?d ORDER BY `sort`, participant_id",
                    $movieId
                );
                $persones = array();
                foreach ($rows as $row) {
                    $personId = $row['person_id'];
                    if ($row["role"]=="режиссер") {
                        $movie['directors'][] = trim($row["name"])? $row["name"] : $row["international_name"];
                    }
                    $persones[$personId]['person_id'] = $personId;
                    $persones[$personId]['name'] = $row["name"];
                    $persones[$personId]['international_name'] = $row["international_name"];
                    $persones[$personId]['names'] = array_values(array_filter(array(trim($row["name"]), trim($row["international_name"]))));
                    $photos = array_values(array_filter(
                        preg_split("/(\r\n|\r|\n)/", $row["photos"])
                    ));
                    $photo = array_shift($photos);
                    if ($photo) {
                        $width = 90;
                        $height = 0;
                        $photo = Lms_Application::thumbnail($photo, $width, $height, $defer = true);
                    }
                    unset($row["photos"]);
                    $persones[$personId]['photo'] = $photo;
                    $persones[$personId]['roles'][] = array("role" => $row["role"], "character" => $row["character"]);
                }
                $movie['persones'] = array_values($persones);
                
                $covers = array_values(array_filter(
                    preg_split("/(\r\n|\r|\n)/", $movie["covers"])
                ));
                array_splice($covers, 6);
                $movie["covers"] = array();
                foreach ($covers as $cover) {
                    $width = 0;
                    $height = 0;
                    $coverStruct = array();
                    $coverStruct['original'] = Lms_Application::thumbnail($cover, $width, $height, $defer = true, $force = false);
                    
                    $width = 200;
                    $height = 0;
                    $coverStruct['thumbnail'] = Lms_Application::thumbnail($cover, $width, $height, $defer = true, $force = false);
                    
                    $movie["covers"][] = $coverStruct;
                }
                
                $rows = $db->select(
                    "SELECT * FROM ratings WHERE movie_id=?d",
                    $movieId
                );
                foreach ($rows as $row) {
                    $valueKey = "rating_" . $row['system'] . "_value";
                    $countKey = "rating_" . $row['system'] . "_count";
                    $urlKey = $row['system'] . "_url";
                    $movie[$valueKey] = $row['value'];
                    $movie[$countKey] = $row['count'];
                    if ($row['system_uid'] && ($row['system']=='imdb' || $row['system']=='kinopoisk')) {
                        $movie[$urlKey] = Lms_Service_Movie::getMovieUrlById($row['system_uid'], $row['system']);
                    }
                }
                
                $movie["comments_count"] = $db->selectCell(
                    "SELECT count(*) as count FROM comments INNER JOIN movies_comments USING(comment_id) WHERE movie_id=?d AND (ISNULL(to_user_id) OR to_user_id=0 {OR to_user_id=?d OR user_id=?d})",
                    $movieId,
                    $user->getId()? $user->getId() : DBSIMPLE_SKIP,
                    $user->getId()? $user->getId() : DBSIMPLE_SKIP
                );

                $movie['files'] = $movieItem->getFilesAsArray2();
                
                foreach ($movie['files'] as &$file) {
                    if ($file['frames']) {
                        array_splice($file['frames'], 6);
                        $file["small_frames"] = array();
                        foreach ($file['frames'] as $frame) {
                            $width = 225;
                            $height = 0;
                            $file["small_frames"][] = Lms_Application::thumbnail($frame, $width, $height, $defer = false, $force = false);
                        }
                    }
                }
                
                if (Lms_Application::getConfig('download', 'smb')) {
                    $mode = $user->getMode();
                    if (Lms_Application::getConfig('download', 'modes', $mode, 'smb')) {
                        $movie['smb'] = 1;
                    }
                }

                if ($movie["group"]) {
                    $movies = $db->select(
                        "SELECT movie_id, name, "
                          . "   international_name, "
                          . "   year, "
                          . "   `covers` "
                          . "FROM movies "
                          . "WHERE `group`=? AND movie_id!=?d {AND movies.hidden=?d} "
                          . "ORDER BY year",
                        $movie["group"],
                        $movieId,
                        $user->isAllowed("movie", "moderate")? DBSIMPLE_SKIP : 0
                    );
                    Lms_Item_Movie::postProcess($movies, 90);
                    $movie['other_movies'] = $movies;
                }
            } else {
                $movie = null;
            }
            $result['movie'] = $movie;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
        
    }
    
    public static function getPerson($params) 
    {
        try {
            $personId = (int) $params['person_id'];
            $sql = "SELECT * FROM persones WHERE person_id=?d";
            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');

            $person = $db->selectRow($sql, $personId);
            if (!$person) {
                return new Lms_Api_Response(404);
            }
            $person["info"] = Lms_Text::htmlizeText($person["info"]);

            $photos = array_values(array_filter(
                preg_split("/(\r\n|\r|\n)/", $person["photos"])
            ));
            $person["photos"] = array();
            foreach ($photos as $photo) {
                $width = 0;
                $height = 0;
                $photoStruct = array();
                $photoStruct['original'] = Lms_Application::thumbnail($photo, $width, $height, $defer = true, $force = false);

                $width = 0;
                $height = 190;
                $photoStruct['thumbnail'] = Lms_Application::thumbnail($photo, $width, $height, $defer = true);
                
                $person["photos"][] = $photoStruct;
            }
            
            $wheres = array();
            if (!$user->isAllowed("movie", "moderate")) {
                $wheres[] = " movies.hidden=0 ";
            }

            $sql = "SELECT DISTINCT participants.movie_id, "
                 . "    movies.name, "
                 . "    `year`, "
                 . "    `covers`, "
                 . "    roles.name as `role` " 
                 . "FROM participants INNER JOIN roles USING(role_id) INNER JOIN movies USING(movie_id) " 
                 . "WHERE participants.person_id=?d " . (count($wheres)? " AND " . implode(' AND ', $wheres) . " " : "") 
                 . "ORDER BY year, sort";

            $rows = $db->select($sql, $personId);
            $movies = array();
            foreach ($rows as $row) {
                $movieId = $row['movie_id'];
                $movies[$movieId]["movie_id"] = $movieId;
                $movies[$movieId]["name"] = $row["name"];
                $movies[$movieId]["year"] = $row["year"];
                $movies[$movieId]["roles"][] = $row["role"];
            }
            $person['movies'] = array_values($movies);
            $result['person'] = $person;
            
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
        
    }

    public static function getComments($params)
    {
        try {
            $movieId = (int) $params['movie_id'];
            $user = Lms_User::getUser();
            $db = Lms_Db::get('main');

            $wheres = array();
            $wheres[] = "movie_id=?d";
            $wheres[] = "(ISNULL(to_user_id) OR to_user_id=0 {OR to_user_id=?d OR user_id=?d})";
            if (!$user->isAllowed("movie", "moderate")) {
                $wheres[] = " m.hidden=0 ";
            }
            $sql = "SELECT c.comment_id, "
                 . "    c.user_id, "
                 . "    users.Login as user_name, "
                 . "    `text`, "
                 . "    c.`created_at`, "
                 . "    u.Login as to_user_name, "
                 . "    c.ip as ip "
                 . "FROM comments c "
                 . "    INNER JOIN movies_comments mc USING(comment_id) "
                 . "    INNER JOIN movies m USING(movie_id) "
                 . "    INNER JOIN users ON (users.ID = c.user_id) "
                 . "    LEFT JOIN users u ON (u.ID = c.to_user_id) "
                 . "WHERE " . implode(' AND ', $wheres) . " "
                 . "ORDER BY `created_at`";

            $comments = $db->select(
                $sql,
                $movieId,
                $user->getId()? $user->getId() : DBSIMPLE_SKIP,
                $user->getId()? $user->getId() : DBSIMPLE_SKIP
            );
            foreach ($comments as &$comment) {
                //$field["text"] = preg_replace("/(\r\n|\r|\n)/","<br>",$field["Text"]);
            }
            if (!$user->isAllowed("comment", "edit")) {
                foreach ($comments as &$comment) {
                    unset($comment['ip']);
                }
            }
            $result['comments'] = $comments;
            $result['movie_id'] = $movieId;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
            
    public static function getSuggestion($params)
    {
        try {
            $db = Lms_Db::get('main');
            $query = $params['query'];
            
            $cell = $db->selectCell('SELECT `result` FROM `suggestion_cache` WHERE `query` LIKE ?', $query);
            if ($cell) {
                $suggestion = Zend_Json::decode($cell);
            } else {
                $suggestion = Lms_Item_Suggestion::getSuggestion($query);
            }
            
            if ($suggestion['movies']) {
                $sql = "SELECT movie_id , "
                     . "    name, "
                     . "    `international_name`, " 
                     . "    year, "
                     . "    `covers` "
                     . "FROM `movies`"
                     . "WHERE movie_id IN(?a) "
                     . "ORDER BY rank DESC";
                $movies = $db->select($sql, $suggestion['movies']);

                foreach ($movies as &$movie) {
                    //$movie["international_name"] = htmlentities($movie["international_name"], ENT_NOQUOTES, 'cp1252');
                    $covers = array_values(array_filter(
                        preg_split("/(\r\n|\r|\n)/", $movie["covers"])
                    ));
                    $movie["cover"] = array_shift($covers);
                    if ($movie["cover"]) {
                        $width = 40;
                        $height = 0;
                        $movie["cover"] = Lms_Application::thumbnail($movie["cover"], $width, $height, $defer = true);
                    }
                    unset($movie["covers"]);
                }
            } else {
                $movies = array();
            }
            
            if ($suggestion['persones']) {
                $sql = "SELECT person_id , "
                     . "    `name`, " 
                     . "    `international_name`, " 
                     . "    `photos` "
                     . "FROM persones "
                     . "WHERE person_id IN(?a) "
                     . "ORDER BY rank DESC";

                $persones = $db->select($sql, $suggestion['persones']);
                foreach ($persones as &$person) {
                    $photos = array_values(array_filter(
                        preg_split("/(\r\n|\r|\n)/", $person["photos"])
                    ));
                    $person["photo"] = array_shift($photos);
                    if ($person["photo"]) {
                        $width = 40;
                        $height = 0;
                        $person["photo"] = Lms_Application::thumbnail($person["photo"], $width, $height, $defer = true);
                    }
                    unset($person["photos"]);
                }
            } else {
                $persones = array();
            }
            $result['query'] = $query;
            $result['movies'] = $movies;
            $result['persones'] = $persones;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function search($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            $query = $params['query'];
            
            $words = preg_split('{\s+}i', $query);
            
            $queryLength = Lms_Text::length($query);
            if ($queryLength>=3) {
                $wheres = array();
                $wheresLike = array();
                $trigramCount = 0;
                for ($i=0; $i<=$queryLength-3; $i++) {
                    $trigram = strtolower(substr($query, $i, 3));
                    $wheresLike[] = "`trigram`='" . mysql_real_escape_string($trigram) . "'";
                    $trigramCount++;
                }
                $wheres[] = "(". implode(' OR ', $wheresLike) .")";
                $wheres[] = "`type` = 'movie'";
                if (!$user->isAllowed("movie", "moderate")) {
                    $wheres[] = " m.hidden=0 ";
                }

                $sql = "SELECT movie_id, "
                     . "    name, "
                     . "    `international_name`, " 
                     . "    year, "
                     . "    `covers` "
                     . "FROM `search_trigrams` s "
                     . "INNER JOIN `movies` m ON(s.id=m.movie_id) "
                     . "WHERE " . implode(' AND ', $wheres) . " "
                     . "GROUP BY s.id "
                     . "HAVING count(*)>=?d "
                     . "ORDER BY count(*) DESC, rank DESC LIMIT ?d";
                $movies = $db->select($sql, floor(0.66*$trigramCount), 20);
            } else {
                $wheres = array();
                $joins = array();
                foreach ($words as $n => $word) {
                    $table = "s$n";
                    $joins[] = "INNER JOIN suggestion $table ON ($table.id = m.movie_id) ";
                    $wheres[] = "$table.`word` LIKE '" . mysql_real_escape_string($word) . "%'";
                    $wheres[] = "$table.`type` = 'movie'";
                }
                if (!$user->isAllowed("movie", "moderate")) {
                    $wheres[] = " m.hidden=0 ";
                }

                $sql = "SELECT DISTINCT movie_id, "
                     . "    name, "
                     . "    `international_name`, " 
                     . "    year, "
                     . "    `covers` "
                     . "FROM `movies` m "
                     . implode(' ', $joins) . " "
                     . "WHERE " . implode(' AND ', $wheres) . " "
                     . "ORDER BY rank DESC LIMIT ?d";
                $movies = $db->select($sql, 20);
            }
          
            if ($queryLength>=3) {
                $wheres = array();
                $wheresLike = array();
                $trigramCount = 0;
                for ($i=0; $i<=$queryLength-3; $i++) {
                    $trigram = strtolower(substr($query, $i, 3));
                    $wheresLike[] = "`trigram`='" . mysql_real_escape_string($trigram) . "'";
                    $trigramCount++;
                }
                $wheres[] = "(". implode(' OR ', $wheresLike) .")";
                $wheres[] = "`type` = 'person'";

                $sql = "SELECT person_id , "
                     . "    `name`, " 
                     . "    `international_name`, " 
                     . "    `info`, " 
                     . "    `photos` "
                     . "FROM `search_trigrams` s "
                     . "INNER JOIN `persones` p ON(s.id=p.person_id) "
                     . "WHERE " . implode(' AND ', $wheres) . " "
                     . "GROUP BY person_id "
                     . "HAVING count(*)>=?d "
                     . "ORDER BY count(*) DESC, rank DESC LIMIT ?d";
                $persones = $db->select($sql, floor(0.66*$trigramCount), 20);
            } else {
                $wheres = array();
                $joins = array();
                foreach ($words as $n => $word) {
                    $table = "s$n";
                    $joins[] = "INNER JOIN suggestion $table ON ($table.id = p.person_id) ";
                    $wheres[] = "$table.`word` LIKE '" . mysql_real_escape_string($word) . "%'";
                    $wheres[] = "$table.`type` = 'person'";
                }

                $sql = "SELECT person_id , "
                     . "    `name`, " 
                     . "    `international_name`, " 
                     . "    `photos` "
                     . "FROM persones p "
                     . implode(' ', $joins) . " "
                     . "WHERE " . implode(' AND ', $wheres) . " "
                     . "ORDER BY rank DESC LIMIT ?d";
                $persones = $db->select($sql, 20);
            }
            
            $result['movies'] = array();
            foreach ($movies as $movie) {
                if (self::matchStrings($query, $movie['name']) || self::matchStrings($query, $movie['international_name'])) {
                    $result['movies'][] = $movie;
                }
            }
            $result['persones'] = array();
            foreach ($persones as $person) {
                if (self::matchStrings($query, $person['name']) || self::matchStrings($query, $person['international_name'])) {
                    $result['persones'][] = $person;
                }
            }
            
            foreach ($result['movies'] as &$movie) {
                //$movie["international_name"] = htmlentities($movie["international_name"], ENT_NOQUOTES, 'cp1252');
                $covers = array_values(array_filter(
                    preg_split("/(\r\n|\r|\n)/", $movie["covers"])
                ));
                $movie["cover"] = array_shift($covers);
                if ($movie["cover"]) {
                    $width = 40;
                    $height = 0;
                    $movie["cover"] = Lms_Application::thumbnail($movie["cover"], $width, $height, $defer = true);
                }
                unset($movie["covers"]);
            }
            
            foreach ($result['persones'] as &$person) {
                $photos = array_values(array_filter(
                    preg_split("/(\r\n|\r|\n)/", $person["photos"])
                ));
                $person["photo"] = array_shift($photos);
                if ($person["photo"]) {
                    $width = 40;
                    $height = 0;
                    $person["photo"] = Lms_Application::thumbnail($person["photo"], $width, $height, $defer = true);
                }
                unset($person["photos"]);
            }
           
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
    
    public static function getBestsellers()
    {
        try {
            $db = Lms_Db::get('main');
            
            $bestsellers = $db->select('SELECT category_id, name, movies FROM `bestsellers` ORDER BY rank DESC');
            foreach ($bestsellers as &$bestseller) {
                $moviesIds = Zend_Json::decode($bestseller['movies']);

                $sql = "SELECT movie_id , "
                     . "    `name`, "
                     . "    `international_name`, " 
                     . "    `year`, "
                     . "    `covers` "
                     . "FROM `movies`"
                     . "WHERE movie_id IN(?a) "
                     . "ORDER BY rank DESC";
                $movies = $db->select($sql, $moviesIds);

                foreach ($movies as &$movie) {
                    //$movie["international_name"] = htmlentities($movie["international_name"], ENT_NOQUOTES, 'cp1252');
                    $covers = array_values(array_filter(
                        preg_split("/(\r\n|\r|\n)/", $movie["covers"])
                    ));
                    $movie["cover"] = array_shift($covers);
                    if ($movie["cover"]) {
                        $width = 120;
                        $height = 0;
                        $movie["cover"] = Lms_Application::thumbnail($movie["cover"], $width, $height, $defer = true);
                    }
                    unset($movie["covers"]);
                }
                $bestseller['movies'] = $movies;
            }
            $result['bestsellers'] = $bestsellers;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    
    private static function maxLevensteinDistance($length)
    {
	$res = 2;
	switch (true) {
            case $length<=3:
                $res = 0;
                break;
            case $length<=7:
                $res = 1;
                break;
            default:
                $res = round($length*0.25);
                break;
	}
	return $res;
    }

    private static function compareStrings($str1, $str2)
    {
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);
	$distance = 255;
	$j = 0;
	while ($j <= (strlen($str2) - strlen($str1))) {
            $distance = min($distance, levenshtein($str1, substr($str2, $j, strlen($str1))));
            if ($distance == 0) break;
            $j++;
	}
	return $distance;
    } 

    private static function matchStrings($str1, $str2)
    {
        return (self::compareStrings($str1, $str2)<=self::maxLevensteinDistance(Lms_Text::length($str1)));
    } 

    public static function changePassword($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();

            $oldPassword = md5($params['password_old']);
            if ($user->getPassword()!=md5($params['password_old'])) {
                return new Lms_Api_Response(400, 'Старый пароль введен не верно');
            }
            $errors = array();
            $newPassword = $params['password_new'];
            if (strlen($newPassword) < 3) {
                $errors[] = "Ошибка. Пароль содержит менее 3 символов.";
            }
            if (strlen($newPassword) > 16) {
                $errors[] = "Ошибка. Пароль содержит более 16 символов.";
            }
            if (!preg_match('{^[a-z0-9][a-z0-9]*[a-z0-9]$}i', $newPassword)) {
                $errors[] = "Ошибка. Пароль должен состоять только из латинских букв или цифр.";
            }
            if (!count($errors)) {
                $db->query("UPDATE users SET Password=? WHERE ID=?d", md5($newPassword), $user->getId());
                $_SESSION['pass'] = $newPassword;
                return new Lms_Api_Response(200);
            } else {
                return new Lms_Api_Response(400, implode(" ", $errors));
            }
            
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function logoff($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }


    public static function addBookmark($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->getId()) {
                return new Lms_Api_Response(401, 'Unauthorized');
            }
            if (!$user->isAllowed("bookmark", "add")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }
            $movieId = $params['movie_id'];
            
            $movie = Lms_Item::create('Movie', $movieId);
            if ($movie) {
                $bookmark = Lms_Item::create('Bookmark');
                $bookmark->setMovieId($movie->getId())
                         ->save();
                return self::getBookmarks();
            }
            
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function deleteBookmark($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->getId()) {
                return new Lms_Api_Response(401, 'Unauthorized');
            }
            if (!$user->isAllowed("bookmark", "delete")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }
            $movieId = $params['movie_id'];
            Lms_Item_Bookmark::deleteBookmark($movieId);
            return new Lms_Api_Response(200);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function getBookmarks()
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->getId()) {
                return new Lms_Api_Response(401, 'Unauthorized');
            }
            if (!$user->isAllowed("bookmark", "view")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }
            
            $sql = "SELECT movie_id, "
                 . "    `name`, "
                 . "    `international_name`, " 
                 . "    `year`, "
                 . "    `covers` "
                 . "FROM bookmarks INNER JOIN movies USING(movie_id) " 
                 . "WHERE user_id=?d ORDER BY bookmark_id DESC";
            $movies = $db->select($sql, $user->getId());
            
            foreach ($movies as &$movie) {
                //$movie["international_name"] = htmlentities($movie["international_name"], ENT_NOQUOTES, 'cp1252');
                $cover = array_values(array_filter(
                    preg_split("/(\r\n|\r|\n)/", $movie["covers"])
                ));
                $movie["cover"] = array_shift($cover);
                if ($movie["cover"]) {
                    $width = 16;
                    $height = 0;
                    $movie["cover"] = Lms_Application::thumbnail($movie["cover"], $width, $height, $defer = true, $force = false);
                }
                unset($movie["covers"]);
            }
            $result['movies'] = $movies;
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function postComment($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();

            if (!$user->isAllowed("comment", "post")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }
            
            $movieId = $params['movie_id'];
            $text = trim($params['text']);
            
            if ($text && $movieId) {
                $comment = Lms_Item::create('Comment');
                $comment->setText($text);
                $movie = Lms_Item::create('Movie', $movieId);
                $movie->add($comment);
            }
            return self::getComments(array('movie_id'=>$movieId));
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function editComment($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->isAllowed("comment", "edit")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }
            $commentId = $params['comment_id'];
            $text = trim($params['text']);

            $comment = Lms_Item::create('Comment', $commentId);
            $comment->setText($text)
                    ->save();

            return new Lms_Api_Response(200);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function deleteComment($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->isAllowed("comment", "delete")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }
            
            $commentId = $params['comment_id'];
            $comment = Lms_Item::create('Comment', $commentId);
            $comment->delete();
            return new Lms_Api_Response(200);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function setRating($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();

            if (!$user->isAllowed("rating")) {
                return new Lms_Api_Response(403, 'Forbidden');
            }

            $movieId = $params['movie_id'];
            $rating = $params['rating'];
            if ($movieId && ($rating>0) && ($rating<=10)){
                $movie = Lms_Item::create('Movie', $movieId);
                if ($movie){
                    Lms_Item_MovieUserRating::replaceRating($movieId, $rating);
                }
            } else if ($movieId && $rating==0) {
                Lms_Item_MovieUserRating::deleteRating($movieId);
            }
            $localRating = Lms_Item_Movie::updateLocalRating($movieId);
            
            $result = array(
                'rating_local_value' => $localRating['bayes'] ? str_replace(",", ".", strval($localRating['bayes'])) : null,
                'rating_local_count' => $localRating['count'],
                'rating_local_detail' => $localRating['detail'],
                'rating_personal_value' => $rating
            );
            
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function setMovieField($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            if (!$user->isAllowed("movie", "moderate")) {
                return new Lms_Api_Response(403);
            }
            $movieId = $params['movie_id'];
            $field = $params['field'];
            $value = $params['value'];
            $movie = Lms_Item::create('Movie', $movieId);
            call_user_func(array($movie, "set$field"), $value);
            $movie->save();
            
            return new Lms_Api_Response(200);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function getRandomText($params)
    {
        try {
            $db = Lms_Db::get('main');
            $user = Lms_User::getUser();
            return new Lms_Api_Response(200, null, $result);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }

    public static function hitMovie($params)
    {
        try {
            $db = Lms_Db::get('main');
            $movieId = (int) $params['movie_id'];
            Lms_Item_Movie::hitMovie($movieId);
            return new Lms_Api_Response(200);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
    
    public static function logout($params)
    {
        try {
            Lms_Application::clearAuthData();
            return new Lms_Api_Response(200);
        } catch (Exception $e) {
            return new Lms_Api_Response(500, $e->getMessage());
        }
    }
    
}
