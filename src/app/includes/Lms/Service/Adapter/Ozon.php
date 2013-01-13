<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Kinopoisk.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Service_Adapter_Ozon
{

    public static function constructPath($action, $params)
    {
        switch($action){
            case 'search':
                if (!isset($params['group'])) {
                    $params['group'] = 'div_dvd';
                }
                $query = urlencode($params['query']);
                return "http://www.ozon.ru/?context=search&group=".$params['group']."&text=$query";
                break;
            case 'film':
                return "http://www.ozon.ru/context/detail/id/{$params['id']}/?type=1";
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
    }    
    
    public static function afterParseMovie($url, &$data)
    {
        if ($data) {
            $data['name'] = $data['names'][0];
            $data['international_name'] = isset($data['names'][1])? $data['names'][1] : '';
            $data['poster'] = isset($data['posters'][0])? $data['posters'][0] : '';
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

