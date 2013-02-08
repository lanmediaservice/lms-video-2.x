#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/bestsellers.pid');
if ($pid->isRunning()) {
    exit;
}


function genre($name) 
{
    static $genres;
    if (!$genres) {
        $db = Lms_Db::get('main');
        $genres = $db->selectCol('SELECT name AS ARRAY_KEY, genre_id FROM genres');
    }
    if (array_key_exists($name, $genres)) {
        return $genres[$name];
    } else {
        return false;
    }
}

function genreExist($genreName, $movieId)
{
    static $moviegenres;
    if (!$moviegenres) {
        $db = Lms_Db::get('main');
        $rows = $db->select('SELECT * FROM movies_genres');
        $moviegenres = array();
        foreach ($rows as $row) {
            $moviegenres[$row['movie_id']][] = $row['genre_id'];
        }
    }
    if (!isset($moviegenres[$movieId])) {
        return false;
    }
    return in_array(genre($genreName), $moviegenres[$movieId]);
}

function country($name) 
{
    static $countries;
    if (!$countries) {
        $db = Lms_Db::get('main');
        $countries = $db->selectCol('SELECT name AS ARRAY_KEY, country_id FROM countries');
    }
    if (array_key_exists($name, $countries)) {
        return $countries[$name];
    } else {
        return false;
    }
}

function countryExist($countryName, $movieId)
{
    static $moviecountries;
    if (!$moviecountries) {
        $db = Lms_Db::get('main');
        $rows = $db->select('SELECT * FROM movies_countries');
        $moviecountries = array();
        foreach ($rows as $row) {
            $moviecountries[$row['movie_id']][] = $row['country_id'];
        }
    }
    if (!isset($moviecountries[$movieId])) {
        return false;
    }
    return in_array(country($countryName), $moviecountries[$movieId]);
}

$db = Lms_Db::get('main');

$log = Lms_Item_Log::create('bestsellers', 'Началось');
$report = '';

try {
    echo $m = "\nPrepare ...";
    $report .= $m;
    $log->progress("Подготовка");
    
    $db->query('TRUNCATE `bestsellers`');

    $sql = "SELECT movie_id as ARRAY_KEY, "
        . "    movie_id, "
        . "    rank as rank "
        . "FROM movies WHERE hidden=0 "; 
    $movies = $db->select($sql);

    //calculate categories 
    $categoryIndex = array(); 
    define('CATEGORY_SERIES',1); 
    define('CATEGORY_RUSSIAN_SERIES',2); 
    define('CATEGORY_COMEDY',3); 
    define('CATEGORY_ACTION',4); 
    define('CATEGORY_DOCUMENTARY',5); 
    define('CATEGORY_ANIME',6); 
    define('CATEGORY_ANIMATION',7); 
    define('CATEGORY_HORROR',8); 
    define('CATEGORY_FANTASY_SCFI',9); 
    define('CATEGORY_DRAMA',10); 

    $categoryNames = array( 
    CATEGORY_RUSSIAN_SERIES => 'Русские шоу и сериалы', 
    CATEGORY_SERIES => 'Зарубежные сериалы', 
    CATEGORY_COMEDY => 'Комедии', 
    CATEGORY_ACTION => 'Боевики', 
    CATEGORY_DOCUMENTARY => 'Документальные', 
    CATEGORY_ANIME => 'Аниме', 
    CATEGORY_ANIMATION => 'Мультфильмы', 
    CATEGORY_HORROR => 'Ужасы', 
    CATEGORY_FANTASY_SCFI => 'Фантастика/фэнтези', 
    CATEGORY_DRAMA => 'Драма/мелодрама' 
    ); 
    echo $m = " OK";
    $report .= $m;
    
    echo $m = "\nSorting ...";
    $report .= $m;
    $log->progress("Сортировка");
    
    $n = 0;
    foreach ($movies as $movieId => $movie) { 
        $weight = $movie['rank'];
        if (genreExist('Сериал', $movieId)) { 
            if (countryExist('СССР', $movieId) || countryExist('Россия', $movieId) || countryExist('Украина', $movieId) || countryExist('Беларусь', $movieId)) {
                $categoryIndex[CATEGORY_RUSSIAN_SERIES][$movieId] = $weight; 
            } else {
                $categoryIndex[CATEGORY_SERIES][$movieId] = $weight; 
            }
        } elseif (genreExist('Документальный', $movieId)) { 
            $categoryIndex[CATEGORY_DOCUMENTARY][$movieId] = $weight; 
        } elseif (genreExist('Аниме', $movieId)) { 
            $categoryIndex[CATEGORY_ANIME][$movieId] = $weight; 
        } elseif (genreExist('Мультфильм', $movieId)) { 
            $categoryIndex[CATEGORY_ANIMATION][$movieId] = $weight; 
        } elseif (genreExist('Комедия', $movieId) && !genreExist('Триллер', $movieId) && !genreExist('Ужасы', $movieId)) { 
            $categoryIndex[CATEGORY_COMEDY][$movieId] = $weight; 
        } elseif (genreExist('Фэнтази', $movieId) || genreExist('Фэнтези', $movieId) || genreExist('Фантастика', $movieId)) { 
            $categoryIndex[CATEGORY_FANTASY_SCFI][$movieId] = $weight; 
        } elseif (genreExist('Ужасы', $movieId)) { 
            $categoryIndex[CATEGORY_HORROR][$movieId] = $weight; 
        } elseif (genreExist('Боевик', $movieId) || genreExist('Триллер', $movieId) || genreExist('Война', $movieId) || genreExist('Приключения', $movieId)) { 
            $categoryIndex[CATEGORY_ACTION][$movieId] = $weight; 
        } elseif ((genreExist('Драма', $movieId) || genreExist('Мелодрама', $movieId)) && !genreExist('Война', $movieId) && !genreExist('Приключения', $movieId)) { 
            $categoryIndex[CATEGORY_DRAMA][$movieId] = $weight; 
        }
        if (!($n % 1000) ) {
            echo $m = "..$n..";
            $report .= $m;
        }
        $n++;
    } 
    echo $m = " OK";
    $report .= $m;

    echo $m = "\nSave ...";
    $report .= $m;
    $log->progress("Сохранение");
    
    $selectedFilms = array(); 
    $categoriesWeight = array(); 
    foreach ($categoryIndex as $categoryId => $categoryContent) { 
    arsort($categoryContent); 
    $c = 0; 
    foreach ($categoryContent as $movieId => $weight) { 
        $c++; 
        if ($c>5) {
            break; 
        }
        @$categoriesWeight[$categoryId] += $weight; 
        $selectedFilms[$categoryId][] = $movieId; 
    } 
    } 
    arsort($categoriesWeight); 

    foreach ($selectedFilms as $categoryId => $movies) {
        $row = array(
            'category_id' => $categoryId,
            'name' => $categoryNames[$categoryId],
            'movies' => Zend_Json::encode($movies),
            'rank' => $categoriesWeight[$categoryId],
        );
        $db->query('INSERT INTO bestsellers SET ?a', $row);
    }
    echo $m = " OK";
    $report .= $m;

    echo $m = "\nDone\n";
    $report .= $m;
    
    $log->done(Lms_Item_Log::STATUS_DONE, "Готово", trim($report));
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "Ошибка: " . $e->getMessage(), trim($report));
}
require_once dirname(__FILE__) . '/include/end.php'; 
