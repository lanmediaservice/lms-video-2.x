<?php
class Lms_Service_DataParserLocal extends Lms_Logable{
    
    private $_httpClient;
    private $_encoding = 'CP1251';
    
    public function __construct($httpClient= null)
    {
        if ($httpClient) {
            $this->setHttpClient($httpClient);
        }
    }
    
    public function setRequestClient(Lms_PhpHttpRequest_Client $requestClient)
    {
    }
    
    public function setHttpClient(Zend_Http_Client $httpClient)
    {
        $this->_httpClient = $httpClient;
    }
    
    public function setServiceApp($app)
    {
    }
    
    public function setServiceUrl($url)
    {
    }
    
    public function setAuthData($username, $password)
    {
    }
    
    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
    }
    
    function parseUrl($url, $module, $context, $acceptedAttaches = array())
    {
        if ($module=='kinopoisk' && !Lms_Application::getConfig('parser_service', 'old_kinopoisk_mode')) {
            $url = $url . ((strpos($url, "?")===FALSE)? '?' : '&');
            $url .= 'nocookiesupport=yes';
        }
        $response = $this->_httpClient->resetParameters()
                                      ->setUri($url)
                                      ->setMethod(Zend_Http_Client::GET)
                                      ->setHeaders('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
                                      ->setHeaders('Accept-Language', 'ru,en-us;q=0.7,en;q=0.3')
                                      ->setHeaders('Accept-Encoding', 'gzip, deflate')
                                      ->setHeaders('Accept-Charset', 'windows-1251,utf-8;q=0.7,*;q=0.7')
                                      ->setHeaders('Referer', dirname($url))
                                      ->request();
        if ($module=='kinopoisk' && !Lms_Application::getConfig('parser_service', 'old_kinopoisk_mode')) {
            $body = $response->getBody();
            if (preg_match('{<meta http-equiv="Refresh"[^>]*url=(.*?)">}is', $body, $matches)) {
                $newUrl = html_entity_decode($matches[1]);
                $response = $this->_httpClient->setUri($newUrl)
                                              ->setMethod(Zend_Http_Client::GET)
                                              ->setHeaders('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
                                              ->setHeaders('Accept-Language', 'ru,en-us;q=0.7,en;q=0.3')
                                              ->setHeaders('Accept-Encoding', 'gzip, deflate')
                                              ->setHeaders('Accept-Charset', 'windows-1251,utf-8;q=0.7,*;q=0.7')
                                              ->setHeaders('Referer', $url)
                                              ->request();
            }
        }
        $res = Lms_DataParser::parse($module, $context, $response, $url);
        array_walk_recursive($res, array(__CLASS__, 'utfToEncoding'), $this->_encoding);
        Lms_Debug::debug($res);


        if ($res && count($acceptedAttaches) && isset($res['suburls'])) {
            foreach ($res['suburls'] as $attachName => $subUrlStruct) {
                if (!isset($res['attaches'][$attachName])) {
                    list($subModule, $subContext, $subUrl) = $subUrlStruct;
                    $res['attaches'][$attachName] = $this->parseUrl($subUrl, $subModule, $subContext);
                }
            }
        }
        return $res;
    }

    function updateRatings($movies)
    {
        $res = false;
        return $res;
    }
    
    function searchKinopoiskId($name, $year)
    {
        $res = false;
        return $res;
    }

    private static function utfToEncoding(&$text, $key, $encoding)
    {
        static $tr = array();
        if (!is_string($text)) return;
        //echo $text;
        //$text = htmlentities($text, ENT_QUOTES, $encoding);
        if (preg_match('{utf}i', $encoding)) {
            return;
        } elseif (preg_match('{^(\w+\-?1250|\w+\-?1251|\w+\-?1252|KOI8.*?|ISO-8859.*?|CP866)$}i', $encoding)) {
            $f = 0xffff;
            $convmap = array(128, 0xffff, 0, $f);
            if (!isset($tr[$encoding])) {
                $tr = array();
                for ($i=128; $i<256; $i++) {
                    $utfSymbol = mb_convert_encoding(chr($i), 'UTF-8', $encoding);
                    $htmlEntity = mb_encode_numericentity($utfSymbol, $convmap, 'UTF-8');
                    if (strlen($htmlEntity)>1) {
                        $tr[$encoding][$htmlEntity] = chr($i);
                    }
                }
            }
            $text = mb_encode_numericentity($text, $convmap, 'UTF-8');
            $text = strtr($text, $tr[$encoding]);
        } else {
            $text = mb_convert_encoding($text ,$encoding, 'UTF-8');
        }
    }
}