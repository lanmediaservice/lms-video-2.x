<?php
class Lms_Service_Google {
    
    public static function searchImages($query, $encoding = "UTF-8")
    {
        $httpClient = Lms_Application::getHttpClient();

        if (!preg_match('{utf}i', $encoding)) {
            $query = Lms_Translate::translate($encoding, 'UTF-8', $query);
        }
            
        $url = Lms_DataParser::constructPath('google_images', 'search', array('query'=>$query), true);
                
        $tries = 0;
        $referrer = dirname($url);
        while (true) {
            $response = $httpClient->setUri($url)
                                   ->setMethod(Zend_Http_Client::GET)
                                   ->setHeaders('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
                                   ->setHeaders('Accept-Language', 'ru,en-us;q=0.7,en;q=0.3')
                                   ->setHeaders('Accept-Encoding', 'gzip, deflate')
                                   ->setHeaders('Accept-Charset', 'utf-8,windows-1251;q=0.7,*;q=0.7')
                                   ->setHeaders('Referrer', $referrer)
                                   ->request(); 

                $body = $response->getBody();
                if (preg_match('{<meta[^>]*HTTP-EQUIV="refresh"[^>]*url=([^"\']*)}i', $body, $matches)) {
                    $tries++;
                    if ($tries>3) {
                        throw new Lms_DataParser_Exception("Can not fetch $url"); 
                    }
                    $referrer = $url;
                    $url = $matches[1];//
                } else {
                    break;
                }
        }        
        $info = Lms_DataParser::parse('google_images', 'search_results', $response, $url);
        
        if (!$info) {
            throw new Lms_DataParser_Exception('Unknown error'); 
        }
        
        array_walk_recursive($info, array(__CLASS__, 'utfToEncoding'), $encoding); 

        return $info;  
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