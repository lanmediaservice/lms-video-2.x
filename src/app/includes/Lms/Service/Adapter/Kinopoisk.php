<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Kinopoisk.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Service_Adapter_Kinopoisk
{

    static private $genresMap = array(
        "игра" => "Игровое шоу",
        "Mystery" => "Мистика",
        "реальное ТВ" => "Реал-ТВ",
        "короткометражка" => "Короткометражный",
        "военный" => "Война",
    );

    public static function constructPath($action, $params)
    {
        switch($action){
            case 'search':
                if (Lms_Application::getConfig('parser_service', 'old_kinopoisk_mode')) {
                    $query = urlencode($params['query']);
                    return "http://www.kinopoisk.ru/index.php"
                         . "?kp_query=$query";
                }
                $query = urlencode(Lms_Translate::translate('CP1251', 'UTF-8', $params['query']));
                return "http://www.kinopoisk.ru/search/?text=$query";
                break;
            case 'film':
                return "http://www.kinopoisk.ru/film/{$params['id']}/";
                break;
        }
    }

    public static function getKinopoiskIdFromUrl($url)
    {
        if (preg_match('{/film/(\d+)}', $url, $matches)) {
            return $matches[1];
        } else {
            throw new Lms_Exception("Invalid kinopoisk url: $url");
        }
    }

    public static function afterParseSearchResults($url, &$data)
    {
        if (isset($data['attaches']['film'])) {
            $film = $data['attaches']['film'];
            $url = $data['suburls']['film'][2];
            
            $cast = array();
            $directors = array();
            foreach ($film['persones'] as $person) {
                switch ($person['role']) {
                    case 'режиссер':
                        $directors[] = array_pop($person['names']);
                        break;
                    case 'актер': // break intentionally omitted
                    case 'актриса':
                        $cast[] = array_pop($person['names']);
                        break;
                    default:
                        break;
                }
            }            
            $data['items'] = array();
            $data['items'][] = array(
                "names" => $film['names'],
                "year" => $film['year'],
                "url" => $url,
                "image" => $film['posters'][0],
                "country" => implode(", ", $film['countries']),
                "director" => implode(", ", $directors),
                "genre" => implode(", ", $film['genres']),
                "actors" => Lms_Text::tinyString(implode(", ", $cast), 100, 1),
                "rating" => isset($film['rating']['kinopoisk']['value'])? $film['rating']['kinopoisk']['value'] : null,
            );
        }
        foreach ($data['items'] as &$item) {
            unset($item['info']);
        }
    }    
    
    public static function afterParseMovie($url, &$data)
    {
        $kinopoiskId = self::getKinopoiskIdFromUrl($url);
        if ($data) {
            $data['name'] = $data['names'][0];
            $data['international_name'] = isset($data['names'][1])? $data['names'][1] : '';
            $data['poster'] = isset($data['posters'][0])? $data['posters'][0] : '';
            $data['kinopoisk_id'] = $kinopoiskId;
            $data['rating_kinopoisk_value'] = isset($data['rating']['kinopoisk']['value'])? $data['rating']['kinopoisk']['value'] : '';
            $data['rating_kinopoisk_count'] = isset($data['rating']['kinopoisk']['count'])? $data['rating']['kinopoisk']['count'] : '';
            $data['rating_imdb_value'] = isset($data['rating']['imdb']['value'])? $data['rating']['imdb']['value'] : '';
            $data['rating_imdb_count'] = isset($data['rating']['imdb']['count'])? $data['rating']['imdb']['count'] : '';
            foreach ($data['genres'] as &$genre) {
                $genre = isset(self::$genresMap[$genre])? self::$genresMap[$genre] : $genre;
                $genre = ucfirst($genre);
            }
            if (isset($data['type']) && $data['type']=='series') {
                $data['genres'][] = 'Сериал';
            }
        }
    }
    
    public static function afterParsePerson($url, &$data)
    {
        if ($data) {
            $data['name'] = $data['names'][0];
            $data['international_name'] = isset($data['names'][1])? $data['names'][1] : '';
            
            $data['info'] = "";
            
            if (isset($data['born_date'])) {
                $data['info'] .= $data['born_date'];
                if (isset($data['born_place'])) {
                    $data['info'] .= " (" . $data['born_place'] . ")";
                }
                $data['info'] .= "\n";
            }
            if (isset($data['profile'])) {
                $data['info'] .= strtolower($data['profile']);
            }
            if (isset($data['about'])) {
                $data['info'] .= "\n{$data['about']}";
            }
        }
    }
}

