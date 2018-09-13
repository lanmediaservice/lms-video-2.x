<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Kinopoisk.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Service_Movie
{
    private static $_parserInstance;
    static private $modulesMaps = array(
        "world-art" => "worldart_animation"
    );

    public static function getParser()
    {
        if (!self::$_parserInstance) {
            self::setParser();
        }
        return self::$_parserInstance;
    }

    public static function setParser($parser = null)
    {
        if ($parser === null) {
            $httpClient = Lms_Application::getHttpClient();
            $requestClient = new Lms_PhpHttpRequest_Client($httpClient);
            if (Lms_Application::getConfig('parser_service', 'builtin')) {
                $parserService = new Lms_Service_DataParserLocal($httpClient);
            } else {
                $parserService = new Lms_Service_DataParser($requestClient, $httpClient);
            }
            $config = Lms_Application::getConfig('parser_service');
            $parserService->setServiceUrl($config['url']);
            $parserService->setAuthData(
                $config['username'],
                $config['password']
            );
            $parserService->setServiceApp('lms-video-2.0');
            self::$_parserInstance = $parserService;
        } else {
            self::$_parserInstance = $parser;
        }
    }

    public static function getMovieUrlById($id, $module)
    {
        $adapter = self::getAdapter($module);
        return $adapter::constructPath('film', array('id'=>$id));
    }    
    
    public static function searchMovie($queryText, $module = null)
    {
        if ($module == null) {
            $module = self::getModuleByUrl($url);
        }
        $adapter = self::getAdapter($module);
        $parserService = self::getParser();
        $results = array();
        $url = $adapter::constructPath('search', array('query'=>$queryText));
        $data = $parserService->parseUrl(
            $url,
            isset(self::$modulesMaps[$module])? self::$modulesMaps[$module] : $module,
            'search_results',
            array('film')
        );
        $adapter::afterParseSearchResults($url, $data);
        $results = $data['items'];
        return $results;
    }

    public static function parseMovie($url, $module = null)
    {
        if ($module == null) {
            $module = self::getModuleByUrl($url);
        }
        $adapter = self::getAdapter($module);
        $parserService = self::getParser();
        $moduleCode = isset(self::$modulesMaps[$module])? self::$modulesMaps[$module] : $module;
        if ($moduleCode == 'kinopoisk' && !Lms_Application::getConfig('parser_service', 'old_kinopoisk_mode')) {
            $url .= 'details/cast/';
        }
        $data = $parserService->parseUrl(
            $url,
            $moduleCode, 
            'film'
        );
        $adapter::afterParseMovie($url, $data);
        return $data;
    }

    public static function parsePerson($url, $module = null)
    {
        if ($module == null) {
            $module = self::getModuleByUrl($url);
        }
        $adapter = self::getAdapter($module);
        $parserService = self::getParser();
        $data = $parserService->parseUrl(
            $url, 
            isset(self::$modulesMaps[$module])? self::$modulesMaps[$module] : $module, 
            'person'
        );
        $adapter::afterParsePerson($url, $data);
        return $data;
    }
    
    public static function getAdapter($module)
    {
        return "Lms_Service_Adapter_" . ucfirst(preg_replace('{\W}', '', strtolower($module)));
    }
    
    public static function getModuleByUrl($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (preg_match('{([^\.]*?)\.[^\.]+$}i', $host, $matches)) {
            return $matches[1];
        } else {
            return false;
        }
    }
    
    public static function updateRatings($movies)
    {
        $parserService = self::getParser();
        $data = $parserService->updateRatings($movies);
        return $data;
    }
    
    public static function searchKinopoiskId($name, $year)
    {
        $parserService = self::getParser();
        $data = $parserService->searchKinopoiskId($name, $year);
        return $data;
    }
    
    public function merge($currentData, $data, $engine, $forceMerge = false)
    {
        if ($forceMerge
            || ((isset($currentData['year']) && isset($data['year']) && $currentData['year']==$data['year'])
                && (
                    (isset($currentData['name']) && isset($data['name']) && $currentData['name']==$data['name'])
                    || (isset($currentData['international_name']) && isset($data['international_name']) && trim($currentData['international_name'], ' "\'')==trim($data['international_name'], ' "\''))
                ))
        ) {
            //merge
            foreach ($data as $field => $value) {
                $oldWeight = isset($currentData['weights'][$field])? $currentData['weights'][$field] : 1;
                $newWeight = self::getFieldWeight($engine, $field);
                if (!isset($currentData[$field]) 
                    || self::compareValue($currentData[$field], $oldWeight, $value, $newWeight)
                ) {
                    $currentData[$field] = $value;
                    $currentData['weights'][$field] = $newWeight;
                }
            }
        } else {
            foreach ($data as $field => $value) {
                $data['weights'][$field] = self::getFieldWeight($engine, $field);
            }
            $currentData = $data;
        }
        return $currentData;
    }    
    
    private static function getFieldWeight($engine, $field)
    {
        $defaultWeight = Lms_Application::getConfig('automerge', $engine, 'default') || 1;
        $weight = Lms_Application::getConfig('automerge', $engine, $field);
        return $weight!==null? $weight : $defaultWeight;
    }

    private static function compareValue($oldValue, $oldWeight, $newValue, $newWeight)
    {
        
        if (is_array($newValue)) {
            if (!is_array($oldValue)) {
                return true;
            }
            $newValue = count($newValue)? 1 : 0;
            $oldValue = count($oldValue)? 1 : 0;
        } else if (is_string($newValue)) {
            if (!is_string($oldValue)) {
                return true;
            }
            $newValue = strlen($newValue)? 1 : 0;
            $oldValue = strlen($oldValue)? 1 : 0;
        } else {
            $newValue = $newValue? 1 : 0;
            $oldValue = $oldValue? 1 : 0;
        }
        return $newValue*$newWeight > $oldValue*$oldWeight;
    }
    
}
