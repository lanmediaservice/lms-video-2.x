<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Kinopoisk.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Service_Adapter_Worldart
{
    
    static private $genresMap = array(
        "боевые искусства" => "Боевик",
        "война" => "Война",
        "детектив" => "Детектив",
        "для детей" => "Семейный",
        "драма" => "Драма",
        "история" => "История",
        "комедия" => "Комедия",
        "махо-сёдзё" => "Махо-сёдзё",
        "меха" => "Меха",
        "мистика" => "Мистика",
        "музыкальный" => "Мюзикл",
        "образовательный" => "Образовательный",
        "пародия" => "Пародия",
        "повседневность" => "Повседневность",
        "приключения" => "Приключения",
        "романтика" => "Мелодрама",
        "самурайский боевик" => "Боевик",
        "сёдзё" => "Сёдзё",
        "сёдзё-ай" => "Сёдзё-ай",
        "сёнэн" => "Сёнэн",
        "сёнэн-ай" => "Сёнэн-ай",
        "сказка" => "Фэнтази",
        "спорт" => "Спорт",
        "триллер" => "Триллер",
        "школа" => "Школа",
        "фантастика" => "Фантастика",
        "фэнтези" => "Фэнтези",
        "эротика" => "Эротика",
        "этти" => "Этти",
        "ужасы" => "Ужасы",
        "хентай" => "Хентай",
        "юри" => "Юри",
        "яой" => "Яой",
    );

    public static function constructPath($action, $params)
    {
        switch($action){
            case 'search':
                $query = urlencode($params['query']);
                return "http://www.world-art.ru/search.php?global_sector=animation&public_search=$query";
                break;
            case 'film':
                return "http://www.world-art.ru/animation/animation.php?id={$params['id']}";
                break;
        }
    }

    public static function afterParseSearchResults($url, &$data)
    {
        if (isset($data['attaches']['film'])) {
            $film = $data['attaches']['film'];
            $url = $data['suburls']['film'][2];
            $data['items'] = array();
            $data['items'][] = array(
                "names" => $film['names'],
                "year" => $film['year'],
                "url" => $url
            );
        }
        if (!empty($data['items'])) {
            foreach ($data['items'] as &$item) {
                $item['names'] = array_splice($item['names'], 0, 2);
            }
        }
    }    
    
    public static function afterParseMovie($url, &$data)
    {
        if ($data) {
            $data['name'] = $data['names'][0];
            $data['international_name'] = isset($data['names'][1])? $data['names'][1] : '';
            $data['poster'] = isset($data['posters'][0])? $data['posters'][0] : '';
            if (!is_array($data['genres'])) {
                $data['genres'] = array();
            }
            array_unshift($data['genres'], 'Аниме');
            foreach ($data['genres'] as &$genre) {
                $genre = isset(self::$genresMap[$genre])? self::$genresMap[$genre] : $genre;
            }
            $data['genres'] = array_unique($data['genres']);
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

