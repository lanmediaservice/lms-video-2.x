<?php
class Lms_DataParser {
    
    const ALL_WHITE_SPACE = 1;
    const BETWEEN_TAGS_ONLY = 2;
    const TEST_MODE = true;
    
    static public function constructPath($moduleName, $action, $params, $absolutize = false)
    {
        $method = 'constructPath' . str_replace(" ", "", ucwords(strtolower(str_replace("_", " ", $action))));
        $normalizedModuleName = str_replace(" ", "_", ucwords(strtolower(str_replace("_", " ", $moduleName))));
        $className = Lms_Modular::loadModule($normalizedModuleName, true);
        if (!$className) {
            throw new Lms_DataParser_Exception("Module $moduleName not found!");
        }
        if (!is_callable(array($className, $method))) {
            throw new Lms_DataParser_Exception("Construct method $className::$method not found!");
        }
        $path = call_user_func(array($className, $method), $params);
        if ($absolutize) {
            $path = self::absolutize($path, '', call_user_func(array($className, 'getHost')), false);
        }
        return $path;
    }
    
    static public function parse($moduleName, $context, $response, $url, $testMode = false)
    {
        $method = 'parse' . str_replace(" ", "", ucwords(strtolower(str_replace("_", " ", $context))));
        $normalizedModuleName = str_replace(" ", "_", ucwords(strtolower(str_replace("_", " ", $moduleName))));
        $className = Lms_Modular::loadModule($normalizedModuleName, true);
        if (!$className) {
            throw new Lms_DataParser_Exception("Module $moduleName not found!");
        }
        if (!is_callable(array($className, $method))) {
            throw new Lms_DataParser_Exception("Context method $className::$method not found!");
        }
        if ($response->getStatus()==200 && !$response->getBody()) {
            throw new Lms_DataParser_Exception("Body is empty!");
        }

        return call_user_func(array($className, $method), $response, $url, $testMode);
    }

    static public function absolutize($path, $parentpath = '', $host = 'www.example.com', $encode = false) {
        $strTransform = array("\\"=>"/");
        $path = strtr($path, $strTransform);
        $parentpath = strtr($parentpath, $strTransform);
        if ($encode) {
            $start = 0;
            if (substr($path, 0, 7)=='http://' || substr($path, 0, 8)=='https://') $start = 3;
            $t = explode("/", $path);
            for ($i = $start; $i<count($t); $i++) {
                $t[$i] = rawurlencode($t[$i]);
            }
            $path = implode("/", $t);
        }
        if  (substr($parentpath, -1)!='/') $parentpath .= '/';

        if ($path{0}=='/') {
            if ($path{1}=='/') {
                return 'http:' . $path;
            } else {
                return 'http://' . $host . $path;
            }
        } elseif (substr($path, 0, 7)=='http://') {
            return $path;
        } elseif (substr($path, 0, 8)=='https://') {
            return str_replace('https://', 'http://', $path);
        } elseif ($parentpath{0}=='/') {
            return 'http://' . $host . $parentpath . $path;
        } elseif (substr($parentpath,0,7)=='http://') {
            return $parentpath . $path;
        }
    }


    static public function compactTags($htmlText, $mode = self::BETWEEN_TAGS_ONLY)
    {
        switch ($mode) {
        case self::BETWEEN_TAGS_ONLY:
            return preg_replace("{>(\s|\xC2\xA0)+<}is", '><', $htmlText);
            break;
        case self::ALL_WHITE_SPACE:
            $output = preg_replace("{>(?:\s|\xC2\xA0)+(\S)}is", '>$1', $htmlText);
            $output = preg_replace("{(\S)(?:\s|\xC2\xA0)+<}is", '$1<', $output);
            return $output;
            break;
        default:
            throw new Lms_Exception("Unknown mode: #{$mode} for compact tags");
        }
    }

    static public function utfBodyFromResponse(Zend_Http_Response $response)
    {
        $encoding = 'UTF-8';
        $body = $response->getBody();
        $contentType = $response->getHeader('Content-Type');
        if (preg_match("{<meta\s+http-equiv.*?Content-Type.*?charset=([a-zA-Z0-9\-]*)}i", $body, $matches)) {
            $encoding = $matches[1];
        }
        if (preg_match("{charset=([a-zA-Z0-9\-]*)}i", $contentType, $matches)) {
            $encoding = $matches[1];
        }
        if (!preg_match('{utf}i', $encoding)) {
            //$body = html_entity_decode($body, ENT_QUOTES, $encoding);
            $body = preg_replace_callback('{&#(\d+);}i' , array(__CLASS__, '_entReplaceDec'), $body);
            $body = preg_replace_callback('{&#x([0-9a-fA-F]+);}i' , array(__CLASS__, '_entReplaceHex'), $body);
            $body = mb_convert_encoding($body, 'UTF-8', $encoding);
        } else {
            $body = preg_replace_callback('{&#(\d+);}i' , array(__CLASS__, '_entReplaceDecUtf8'), $body);
            $body = preg_replace_callback('{&#x([0-9a-fA-F]+);}i' , array(__CLASS__, '_entReplaceHexUtf8'), $body);
        }
        $body = html_entity_decode($body, ENT_COMPAT | ENT_HTML5, 'UTF-8');
        return $body;
    }
    private static function _entReplaceHex($matches)
    {
        $matches[1] = hexdec($matches[1]);
        return self::_entReplaceDec($matches);
    }
    private static function _entReplaceDec($matches)
    {
        $entityNum = $matches[1];
        if ($entityNum>=128 && $entityNum<160) {
            $flags = version_compare(phpversion(), '5.4', '<') ? ENT_COMPAT : (ENT_COMPAT | ENT_HTML401);
            return htmlspecialchars(chr($entityNum), $flags, 'cp1251');
        } else {
            return $matches[0];
        }
    }
    private static function _entReplaceHexUtf8($matches)
    {
        $matches[1] = hexdec($matches[1]);
        return self::_entReplaceDecUtf8($matches);
    }
    private static function _entReplaceDecUtf8($matches)
    {
        $entityNum = $matches[1];
        if ($entityNum>=128 && $entityNum<160) {
            return mb_convert_encoding(chr($entityNum), 'UTF-8', 'CP1251');
        } else {
            return $matches[0];
        }
    }
    static private function unichr($c)
    {
        if ($c <= 0x7F) {
            return chr($c);
        } else if ($c <= 0x7FF) {
            return chr(0xC0 | $c >> 6) . chr(0x80 | $c & 0x3F);
        } else if ($c <= 0xFFFF) {
            return chr(0xE0 | $c >> 12) . chr(0x80 | $c >> 6 & 0x3F)
                                        . chr(0x80 | $c & 0x3F);
        } else if ($c <= 0x10FFFF) {
            return chr(0xF0 | $c >> 18) . chr(0x80 | $c >> 12 & 0x3F)
                                        . chr(0x80 | $c >> 6 & 0x3F)
                                        . chr(0x80 | $c & 0x3F);
        } else {
            return false;
        }
    }
    
    static public function testRange(&$value, $min, $max)
    {
        if ($value<$min || $value>$max) {
            $value = 'fail';
        } else {
            $value = 'ok';
        }
    }
}