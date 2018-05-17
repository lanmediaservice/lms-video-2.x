<?php
/**
 * LMS Library
 * 
 * @version $Id: Imdb.php 78 2010-10-23 08:11:19Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package DataParser
 */

class Lms_DataParser_Google_Images extends Lms_DataParser_Generic{
    
    static private $version = '$Id:$';
    static private $host = 'www.google.com';
    
    static public function getHost()
    {
        return self::$host;
    }

    static public function getVersion()
    {
        return self::$version;
    }
    
    static public function constructPathSearch($params)
    {
        return '/search?q=' . urlencode($params['query']) . '&safe=off&tbm=isch&ijn=1&start=0&csl=1';
    }
    

    static public function parseSearchResults(Zend_Http_Response $response, $url, $testMode = false)
    {
        $result = array();
        if ($response->isSuccessful()) {
            $body = Lms_DataParser::compactTags(Lms_DataParser::utfBodyFromResponse($response));
            if (preg_match_all('{<a[^>]*?href="(?:http://images\.google\.com)?/imgres\?([^"]*?)".*?><img[^>]*?src="([^"]*)"}i', $body, $data, PREG_SET_ORDER)) {
                foreach ($data as $row) {
                    parse_str($row[1], $vars);
                    $info = array();
                    $info['thumbnail_escaped'] = trim($row[2],'"\'');
                    $info['width'] = $vars['w'];
                    $info['height'] = $vars['h'];
                    if (preg_match('{imgurl=([^&"]+)}i', $row[1], $matches)) {
                        $info['url'] = $matches[1];
                        $info['url'] = str_ireplace(array("%3F", "%3D", "%26"), array("?", "=", "&"), $info['url']);
                    } else {
                        $info['url'] = $vars['imgurl'];
                    }
                    $result['search_results'][] = $info;
                }
            }
        } else {
            throw new Lms_DataParser_Exception("Error while parse $url : server returned " .  $response->getStatus());
        }
        $result['version'] = self::getVersion();
        return $result;
    }
}