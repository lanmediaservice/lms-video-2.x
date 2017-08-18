<?php
/**
 * LMS Library
 *
 * 
 * @version $Id: Text.php 553 2010-10-22 21:58:26Z macondos $
 * @copyright 2007-2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov<webtota@gmail.com>
 * @package Text
 */

/**
 * Class for working with text data
 *
 */
define("CASE_SENSITIVE", log(0));

class Lms_Text {

    const CASE_SENSITIVE   = CASE_SENSITIVE;
    const CASE_INSENSITIVE = false;
    private static $enableMultiByte = false;
    private static $encoding = false;
    
    /**
     * Enable support of multibyte encoding
     *
     */
    public static function enableMultiByte()
    {
        self::$enableMultiByte = true;
    }
    /**
     * Disable support of multibyte encoding
     *
     */
    public static function disableMultiByte()
    {
        self::$enableMultiByte = false;
    }
    /**
     * Set strings encoding
     *
     * @param string $encoding
     */
    public static function setEncoding($encoding)
    {
        self::$encoding = $encoding;
    }
    
    public static function getEncoding()
    {
        return self::$encoding; 
    }
    /**
     * Returns the numeric position of the first occurrence of needle in the haystack string.
     * Case-insensitive.
     *
     * @param string $haystack
     * @param string $needle
     * @param CASE_SENSITIVE/CASE_INSENSITIVE $sensitive
     * @return int/bool
     */
    static function pos($haystack, $needle, $offset = 0, $sensitive = self::CASE_INSENSITIVE)
    {
        if($offset == self::CASE_SENSITIVE) {
            $sensitive = $offset;
            $offset    = 0;
        }
        if (!self::$enableMultiByte) {
            if ($sensitive) {
                return strpos($haystack, $needle, $offset);
            } else {
                return stripos($haystack, $needle, $offset);
            }
        } else {
            $encoding = self::checkEncoding();
            if ($sensitive) {
                return mb_strpos($haystack, $needle, $offset, $encoding);
            } else {
                return mb_stripos($haystack, $needle, $offset, $encoding);
            }
        }
    }
    
    /**
     * Returns an array of numeric positions of each occurence of needle in the haystack string 
     *
     * @param string $haystack
     * @param string $needle
     * @param CASE_SENSITIVE/CASE_INSENSITIVE $sensitive
     * @return int
     */
    static public function multipos($haystack, $needle,  $sensitive = self::CASE_INSENSITIVE)
    {
        $posArray = array();
        $pos = 0;
        while (false !== ($pos = self::pos($haystack, $needle, $pos , $sensitive))) {
            $posArray[] = $pos;
            $pos++;
        }
        return $posArray;	
    }
    
    /**
     * This function returns a string or an array with all occurrences of search in subject 
     * replaced with the given replace value
     *
     * @param mixed $search
     * @param mixed $replace
     * @param mixed $subject
     * @param CASE_SENSITIVE/CASE_INSENSITIVE $sensitive
     * @return mixed
     */
    static  function replace($search, $replace, $subject, $sensitive = self::CASE_INSENSITIVE)
    {
        if(!self::$enableMultiByte) {
            return ($sensitive)?  str_replace($search, $replace, $subject) : str_ireplace($search, $replace, $subject);
        }
        if($sensitive) {
            return str_replace($search, $replace, $subject);
        }
        if(is_string($search) && is_string($replace)){
            $search_len  = self::length($search);
            $replace_len = self::length($replace);
            $pos = self::pos($subject, $search, $sensitive);
            while ($pos !== false)
            {
                $subject = self::substring($subject, 0, $pos) . $replace
                         . self::substring($subject, $pos + $search_len);
                $pos = self::pos($subject, $search, $pos + $replace_len, $sensitive);
            }
        }
        if(is_array($search)) {
            $count = count($search);
            for($i=0; $i<$count; $i++) {
                $currentSearch      = $search[$i];
                $currentReplacement = (is_array($replace))? $replace[$i] : $replace;
                $subject = self::replace($currentSearch, $currentReplacement, $subject, $sensitive);
            }
        }
        return $subject;

    }
    
    
    /**
     * Make a string uppercase
     *
     * @param string $string
     * @return string
     */
    public static function uppercase($string)
    {
        if(!self::$enableMultiByte) {
            return strtoupper($string);
        } else {
           $encoding = self::checkEncoding();
            return mb_strtoupper($string, $encoding);
        }
    }
    
    /**
     * Make a string lowercase
     *
     * @param string $string
     * @return string
     */
    public static function lowercase($string)
    {
        if(!self::$enableMultiByte) {
            return strtolower($string);
        } else {
           $encoding = self::checkEncoding();
            return mb_strtolower($string, $encoding);
        }
    }
    
    /**
     * Uppercase the first character of each word in a string
     *
     * @param string $string
     * @return string
     */
    public static function titlecase($string)
    {
        if(!self::$enableMultiByte) {
            $string = self::lowercase($string);
            return ucwords($string);
        } else {
            $encoding = self::checkEncoding();
            return mb_convert_case($string, MB_CASE_TITLE, $encoding);
        }
    }
    
    /**
     * Convert a string to array of string chunks of defined length  
     *
     * @param $string
     * @param int $split_length
     * @return array
     */
    public static function split($string, $split_length = 1)
    {
        if(!self::$enableMultiByte) {
            return str_split($string, $split_length);
        }
        $strlen   = self::length($string);
        while ($strlen) {
            $array[] = self::substring($string, 0, $split_length);
            $string  = self::substring($string, $split_length, $strlen);
            $strlen  = self::length($string);
        }
        return $array;
    }
    
    /**
     * Check current encoding
     *
     * @return mixed
     */
    private static function checkEncoding()
    {
    	return (self::$encoding)? self::$encoding : mb_internal_encoding(); 
    }
    
    /**
     * Get length of string
     *
     * @param string $string
     * @return int
     */
    public static function length($string)
    {
        if(!self::$enableMultiByte) {
            return strlen($string);
        } else {
        	$encoding = self::checkEncoding();
        	return mb_strlen($string, $encoding);
        }
    }
    
    /**
     * Return part of a string
     *
     * @param string $sting
     * @param int $start
     * @param int $length
     * @return string
     */
    public static function substring($string, $start, $length = false)
    {
        if(!self::$enableMultiByte) {
            return ($length)? substr($string, $start, $length) : substr($string, $start);
        } else {
            $encoding = self::checkEncoding();
            if(false !== $length) {
               return mb_substr($string, $start, $length, $encoding);
            } else {
                $length = self::length($string);
                return mb_substr($string, $start, $length , $encoding);
            }
        }
    }
    
    /**
     * Reverse string
     *
     * @param string $string
     * @return string
     */
    
    public static function reverse($string)
    {
        if(!self::$enableMultiByte) {
            return strrev($string);
        } else {
            return join('', array_reverse(self::split($string)));
        }
    }
    
    /**
     * Returns part of haystack string from the first occurrence of needle to the end of haystack 
     *
     * @param string $haystack
     * @param string $needle
     * @param CASE_SENSITIVE/CASE_INSENSITIVE $sensitive
     * @return string
     */
    public static function fromStringToEnd($haystack, $needle, $sensitive = self::CASE_INSENSITIVE)
    {
        if(!self::$enableMultiByte) {
            return ($sensitive)? strstr($haystack, $needle) : stristr($haystack, $needle);
        } else {
            $encoding = self::checkEncoding();
            return ($sensitive)? mb_strstr($haystack, $needle, false, $encoding) : mb_stristr($haystack, $needle, false, $encoding);
        }
    }
    
    /**
     * translate all occurrences of each character in $from to the corresponding character in $to 
     *
     * @param string $string
     * @param string $from
     * @param string $to
     * @param CASE_SENSITIVE/CASE_INSENSITIVE $sensitive
     * @return string
     */
    public static function translate($string, $from, $to = false, $sensitive = self::CASE_INSENSITIVE)
    {
        if (is_string($from)) {//using translate with string FROM & TO
            if ($to === false) { //No TO param!!!
                trigger_error('Expected $to param', E_USER_WARNING);
                return $string;
            }
            if (self::$enableMultiByte) {//multibyte
                if($sensitive) {//case-sensitive multibyte
                    $fromKeys   = self::split($from);
                    $toValues   = self::split($to);
                    $replacePairs  = array_combine($fromKeys, $toValues);
                    return strtr($string, $replacePairs);
                } else {//case-insensintive multibte
                    $minLength = min( self::length($from), self::length($to));
                    $truncatedFrom = self::substring($from, 0, $minLength);
                    $fromString    = self::uppercase($truncatedFrom) . self::lowercase($truncatedFrom);
                    $truncatedTo   = self::substring($to, 0, $minLength);	
                    $toString      = $truncatedTo . $truncatedTo;
                    $fromKeys      = self::split($fromString);
                    $toValues      = self::split($toString);
                    $replacePairs  = array_combine($fromKeys, $toValues);
                    return strtr($string, $replacePairs);
                }
            } else {//singlebyte
                    if ($sensitive) {//case-sensitive singlebyte
                       return strtr($string, $from, $to);
                    } else {//case-insensitive singlebyte
                        $minLength = min( self::length($from), self::length($to));
                        $truncatedFrom = self::substring($from, 0, min( self::length($from), self::length($to)));
                        $truncatedTo   = self::substring($to, 0, min( self::length($from), self::length($to)));
                        return strtr($string, ( self::uppercase($truncatedFrom) . self::lowercase($truncatedFrom) ), ( $truncatedTo . $truncatedTo ));
                    }
                }
        }
        if (is_array($from)) {//using translate with array (FROM) & without TO
            $sensitive = ($to == self::CASE_SENSITIVE )? $to : $sensitive;
            if ($sensitive) {//case-sensitive
                return strtr($string, $from);
            } else {//case-insensitive
                $sortedPairs = $from;
                uksort($sortedPairs, array(__CLASS__, "compareByLength"));
                $currentString = $string;
                $collector = '';
                while (self::length($currentString)) {
                    foreach ($sortedPairs as $from => $to) {
                        if (0 === self::pos($currentString, $from)) {
                            $collector .= $to;
                            $currentString = self::substring($currentString, self::length($from));
                            continue 2;
                        }
                    }
                    $collector .= self::substring($currentString, 0, 1);
                    $currentString = self::substring($currentString, 1);
                }
                return $collector;
            }
        }
    }
  	/**
  	 * Compare two strings by their length
  	 *
  	 * @param string $a
  	 * @param string $b
  	 * @return int
  	 */
  	private static function compareByLength($a, $b)
  	{
  		return self::length($b) - self::length($a);
		
  	}
  	
	/**
	 * Compare strings
	 *
	 * @param string $string1
	 * @param string $string2
	 * @param CASE_SENSITIVE/CASE_INSENSITIVE $sensitive
	 * @return int
	 */
	public static function compare($string1, $string2, $sensitive = self::CASE_INSENSITIVE)
	{
		return ($sensitive)? strcmp($string1, $string2) : strcmp(self::lowercase($string1), self::lowercase($string2));
	}
	
    function declension($int, $expressions, $langCode2 = 'en')
    { 
        settype($int, "integer");
        if (count($expressions) < 2) $expressions[1] = $expressions[0];
        if (count($expressions) < 3) $expressions[2] = $expressions[1];
        switch ($langCode2) {
        case 'en':
            $result = $int==1? $expressions[0] : $expressions[1];
            break;
        case 'ru':
        case 'uk':
        case 'be':
            $count = $int % 100; 
            if ($count >= 5 && $count <= 20) { 
                $result = $expressions['2']; 
            } else { 
                $count = $count % 10; 
                if ($count == 1) { 
                    $result = $expressions['0']; 
                } elseif ($count >= 2 && $count <= 4) { 
                    $result = $expressions['1']; 
                } else { 
                    $result = $expressions['2']; 
                }
            } 
            break;
        default: 
            $result = $expressions[0];
        }
        return $result; 
    }

    public static function nameFromUri($uri)
    {
        //magnet
        if (preg_match('{dn=([^=&]*)}i', $uri, $matches)) {
            return 'magnet:' . urldecode($matches[1]);
        } else {
            return $uri;
        }
    }
    
    private static function replaceLinks($matches)
    {
        return "<a href=\"" . htmlspecialchars($matches[1])."\">" . htmlspecialchars(Lms_Text::nameFromUri($matches[1])) . "</a>";
    }

    public static function htmlizeText($text)
    {
        $text = strip_tags($text);
        $text = str_replace(array("\r\n", "\r", "\n"), "<br>", $text);
        $text = preg_replace_callback('{((?:https?://|magnet:\?|ed2k://)[^\s<]+)}i', "self::replaceLinks", $text);
        return $text;
    }
    
    public static function generateString($length = 8, $symbols = '0123456789bcdfghjkmnpqrstvwxyz', $unique = true)
    {
        $string = "";
        $i = 0; 
        while ($i < $length) { 
            $char = substr($symbols, mt_rand(0, strlen($symbols)-1), 1);
            if ($unique && strstr($string, $char)) { 
                continue;
            }
            $string .= $char;
            $i++;
        }
        return $string;
    }

    public static function decodeMimeStr($string, $charset="UTF-8") 
    { 
        $newString = ''; 
        $elements=imap_mime_header_decode($string); 
        for ($i=0; $i<count($elements); $i++) { 
            if ($elements[$i]->charset == 'default')  {
                $elements[$i]->charset = 'iso-8859-1'; 
            }
            $newString .= iconv($elements[$i]->charset, $charset, $elements[$i]->text); 
        } 
        return $newString; 
    } 

    public static function tinyBasename($basename, $maxSymbols = 20)
    {
        $l = self::length($basename);
        if ($l<$maxSymbols) {
            return $basename;
        }
        $maxSymbols = max(10, $maxSymbols);
        if (self::pos($basename, '.')===false) {
            return self::tinyString($basename, $maxSymbols);
        }
        
        $delta = $l - $maxSymbols;
        $filename = pathinfo($basename, PATHINFO_FILENAME);
        $extension = pathinfo($basename, PATHINFO_EXTENSION);
        $lengthFilename = self::length($filename);
        $lengthExtension = self::length($extension);
        
        if ($lengthExtension<=5) {
            return self::tinyString($filename, $maxSymbols - $lengthExtension - 1) . '.' . $extension;
        } else if ($lengthFilename<=4) {
            return $filename . '.' . self::tinyString($extension, $maxSymbols - $lengthFilename - 1, 1);
        } else {
            return self::tinyString($basename, $maxSymbols);
        }
    }

    public static function tinyEmail($email)
    {
        $l = self::length($email);
        return self::tinyString($email, $l-2);
    }
        
    public static function tinyString($string, $maxSymbols = 20, $offset = 0.67)
    {
        $l = self::length($string);
        if ($l<$maxSymbols) {
            return (string)$string;
        }
        $maxSymbols = max(4, $maxSymbols);
        
        $cutSymbols = $l - $maxSymbols + 3;
        $cutStart = round(($l - $cutSymbols)*$offset);
        return self::substring($string, 0, $cutStart) . '...' . self::substring($string, $cutStart + $cutSymbols);
    }
    
    /**
     * Given a body string and an encoding type,
     * this function will decode and return it.
     *
     * @param  string Input body to decode
     * @param  string Encoding type to use.
     * @return string Decoded body
     * @access private
     */
    public static function decodeMime($input, $encoding = '7bit')
    {
        switch (strtolower($encoding)) {
            case '7bit':
                return $input;
                break;

            case 'quoted-printable':
                return self::quotedPrintableDecode($input);
                break;

            case 'base64':
                return base64_decode($input);
                break;

            default:
                return $input;
        }
    }

    /**
     * Given a quoted-printable string, this
     * function will decode and return it.
     *
     * @param  string Input body to decode
     * @return string Decoded body
     * @access private
     */
    public static function quotedPrintableDecode($input)
    {
        // Remove soft line breaks
        $input = preg_replace("/=\r?\n/", '', $input);

        // Replace encoded characters
        $input = preg_replace('/=([a-f0-9]{2})/ie', "chr(hexdec('\\1'))", $input);

        return $input;
    }
    
    public static function htmlSafeFilter($input)
    {
        static $purifier = false;
        if (!$purifier) {
            new HTMLPurifier_Bootstrap();
            $purifier = new HTMLPurifier();
        }
        return $purifier->purify($input);
    }
    
    public static function collapsePlainTextQuotes($input)
    {
        return preg_replace_callback('{((\r\n|\r|\n)(&gt;|>)[^\r\n]*){3,}}si', array(__CLASS__, '_replaceQuote'), $input);
    }

    private static function _replaceQuote($matches)
    {
        return "\n<span onclick=\"this.parentNode.select('.quote-text').invoke('toggle');\" class=\"toggle-quote\">&mdash; Показать/скрыть цитируемый текст &mdash;</span><span class=\"quote-text\" style=\"display:none\">{$matches[0]}</span>";
    }
    
    public static function translit($text)
    {
        static $tr = array("�" => "G", "�" => "YO", "�" => "E", "�" => "YI", "�" => "I",
	"�" => "i", "�" => "g", "�" => "yo", "�" => "#", "�" => "e",
	"�" => "yi", "�" => "A", "�" => "B", "�" => "V", "�" => "G",
	"�" => "D", "�" => "E", "�" => "ZH", "�" => "Z", "�" => "I",
	"�" => "Y", "�" => "K", "�" => "L", "�" => "M", "�" => "N",
	"�" => "O", "�" => "P", "�" => "R", "�" => "S", "�" => "T",
	"�" => "U", "�" => "F", "�" => "H", "�" => "TS", "�" => "CH",
	"�" => "SH", "�" => "SCH", "�" => "'", "�" => "YI", "�" => "",
	"�" => "E", "�" => "YU", "�" => "YA", "�" => "a", "�" => "b",
	"�" => "v", "�" => "g", "�" => "d", "�" => "e", "�" => "zh",
	"�" => "z", "�" => "i", "�" => "y", "�" => "k", "�" => "l",
	"�" => "m", "�" => "n", "�" => "o", "�" => "p", "�" => "r",
	"�" => "s", "�" => "t", "�" => "u", "�" => "f", "�" => "h",
	"�" => "ts", "�" => "ch", "�" => "sh", "�" => "sch", "�" => "'",
	"�" => "yi", "�" => "", "�" => "e", "�" => "yu", "�" => "ya"
	);
	return strtr($text, $tr);
    }
    
    public static function safeFilename($filename, $webSafe = true, $lowerCase = true)
    {
        $filename = preg_replace('{[\|/\?\*:;\-"\'\{\}\\\]}', '_', $filename); 
        if ($webSafe) {
            $filename = self::translit($filename);
            $filename = preg_replace('{[\s\(\)!%&#]}', '_', $filename); 
            if ($lowerCase) {
                $filename = strtolower($filename);
            }
        }
        $filename = trim($filename, '_ ');
        $filename = preg_replace('{[_]+}', '_', $filename); 
        
        return $filename;
    }
    
    /**
     * �������� ���������������� ���� � http://www.translit.ru/
     * 
     * @staticvar null $tra
     * @staticvar null $abc2
     * @param type $txt
     * @return string 
     */
    private static function detranslitSymbol($txt)
    {
        static $tra = null;
        static $abc2 = null;
        if (!$tra || !$abc2) {
            $tra = array();
            $abc2 = array();

            $tra['a'] = array('�+','�+','�+','�+','�','�','�','�','','');
            $abc2['a'] = array('��','��','��','��','�','�','�','�','�','a');

            $tra['b'] = array('','');
            $abc2['b'] = array('�','b');

            $tra['v'] = array('','');
            $abc2['v'] = array('�','v');

            $tra['g'] = array('','');
            $abc2['g'] = array('�','g');

            $tra['d'] = array('','');
            $abc2['d'] = array('�','d');

            $tra['e'] = array('�+','�+','�','�','','');
            $abc2['e'] = array('��','��','�','�','�','e');

            $tra['o'] = array('�+','�+','�+','�+','�','�','�','�','','');
            $abc2['o'] = array('��','��','��','��','�','�','�','�','�','o');

            $tra['?'] = array('','');
            $abc2['?'] = array('�','?');

            $tra['h'] = array('��', '��+','��+','�+','��','�+','�+','�+','�+','�+','��','�+','�+','�','�','�','�','�','�','�','�','','');
            $abc2['h'] = array('�', '���','���','��','�','��','��','��','��','��','�','��','��','�','�','�','�','�','�','�','�','�','h');

            $tra['z'] = array('','');
            $abc2['z'] = array('�','z');

            $tra['i'] = array('�','','');
            $abc2['i'] = array('��','�','i');

            $tra['j'] = array('','');
            $abc2['j'] = array('�','j');

            $tra['k'] = array('','');
            $abc2['k'] = array('�','k');

            $tra['l'] = array('','');
            $abc2['l'] = array('�','l');

            $tra['m'] = array('','');
            $abc2['m'] = array('�','m');

            $tra['n'] = array('','');
            $abc2['n'] = array('�','n');

            $tra['p'] = array('','');
            $abc2['p'] = array('�','p');

            $tra['r'] = array('','');
            $abc2['r'] = array('�','r');

            $tra['s'] = array('','');
            $abc2['s'] = array('�','s');

            $tra['t'] = array('','');
            $abc2['t'] = array('�','t');

            $tra['u'] = array('�+','�+','�+','�+','�','�','�','�','','');
            $abc2['u'] = array('��','��','��','��','�','�','�','�','�','u');

            $tra['f'] = array('','');
            $abc2['f'] = array('�','f');

            $tra['x'] = array('','');
            $abc2['x'] = array('�','x');

            $tra['c'] = array('','');
            $abc2['c'] = array('�','c');

            $tra['w'] = array('','');
            $abc2['w'] = array('�','w');

            $tra['#'] = array('�+','�','','');
            $abc2['#'] = array('��','�','�','#');

            $tra['y'] = array('�','','');
            $abc2['y'] = array('��','�','y');

            $tra['\''] = array('�+','�','','');
            $abc2['\''] = array('��','�','�','\'');

            $tra['?'] = array('','');
            $abc2['?'] = array('�','?');

            $tra['?'] = array('','');
            $abc2['?'] = array('�','?');

            $tra['q'] = array('','');
            $abc2['q'] = array('�','q');

            $tra['A'] = array('�+','�+','�','�','','');
            $abc2['A'] = array('��','��','�','�','�','A');

            $tra['B'] = array('','');
            $abc2['B'] = array('�','B');

            $tra['V'] = array('','');
            $abc2['V'] = array('�','V');

            $tra['G'] = array('','');
            $abc2['G'] = array('�','G');

            $tra['D'] = array('','');
            $abc2['D'] = array('�','D');

            $tra['E'] = array('�+','�','','');
            $abc2['E'] = array('��','�','�','E');

            $tra['O'] = array('�+','�+','�','�','','');
            $abc2['O'] = array('��','��','�','�','�','O');

            $tra['?'] = array('','');
            $abc2['?'] = array('�','?');

            $tra['H'] = array('��+','�+','��','�+','�+','�+','�','�','�','�','','');
            $abc2['H'] = array('���','��','�','��','��','��','�','�','�','�','�','H');

            $tra['Z'] = array('','');
            $abc2['Z'] = array('�','Z');

            $tra['I'] = array('','');
            $abc2['I'] = array('�','I');

            $tra['J'] = array('','');
            $abc2['J'] = array('�','J');

            $tra['K'] = array('','');
            $abc2['K'] = array('�','K');

            $tra['L'] = array('','');
            $abc2['L'] = array('�','L');

            $tra['M'] = array('','');
            $abc2['M'] = array('�','M');

            $tra['N'] = array('','');
            $abc2['N'] = array('�','N');

            $tra['P'] = array('','');
            $abc2['P'] = array('�','P');

            $tra['R'] = array('','');
            $abc2['R'] = array('�','R');

            $tra['S'] = array('','');
            $abc2['S'] = array('�','S');

            $tra['T'] = array('','');
            $abc2['T'] = array('�','T');

            $tra['U'] = array('�+','�+','�','�','','');
            $abc2['U'] = array('��','��','�','�','�','U');

            $tra['F'] = array('','');
            $abc2['F'] = array('�','F');

            $tra['X'] = array('','');
            $abc2['X'] = array('�','X');

            $tra['C'] = array('','');
            $abc2['C'] = array('�','C');

            $tra['W'] = array('','');
            $abc2['W'] = array('�','W');

            $tra['Y'] = array('','');
            $abc2['Y'] = array('�','Y');

            $tra['?'] = array('','');
            $abc2['?'] = array('�','?');

            $tra['?'] = array('','');
            $abc2['?'] = array('�','?');

            $tra['Q'] = array('','');
            $abc2['Q'] = array('�','Q');
        }
         
        $pretxt = substr($txt, 0, strlen($txt)-1);
        $last = substr($txt, strlen($txt)-1, 1);
        $lat = @$tra[$last];
        $rus = @$abc2[$last];
        if ($lat) {
            for ($ii=0; $ii<count($lat); $ii++) {
                $pos = (strlen($pretxt) > strlen($lat[$ii]))? (strlen($pretxt) - strlen($lat[$ii])) : 0;
                if ($lat[$ii]==substr($pretxt, $pos, strlen($pretxt) - $pos)) {
                    return substr($pretxt, 0, strlen($pretxt) - strlen($lat[$ii])) . $rus[$ii];
                }
            }
        }
        return $txt;
    }
    
    
    public static function detranslit($latinText)
    {
        $cyrText = "";
	for ($i=0; $i < strlen($latinText); $i++) {
            $cyrText = self::detranslitSymbol($cyrText . substr($latinText, $i, 1));		
	}
	return $cyrText;        
    }
    
    public static function testText($text, $freqIndex)
    {
        if (strlen($text)<3) {
            return 1;
        }
        $text = strtolower($text);
        $ngrams = array();
        $textLength = Lms_Text::length($text);
        for ($i=0; $i<=$textLength-3; $i++) {
            $ngram = substr($text, $i, 3);
            $ngrams[] = $ngram;
        }
        for ($i=0; $i<=$textLength-4; $i++) {
            $ngram = substr($text, $i, 4);
            $ngrams[] = $ngram;
        }
        $value = 0;
        foreach ($ngrams as $ngram) {
            if (array_key_exists($ngram, $freqIndex)) {
                $value += pow($freqIndex[$ngram], 1/strlen($ngram));
            }
        }
        return $value;
    }
    
    public static function autoDetranslit($text, $freqIndex)
    {
        $detranslitedText = Lms_Text::detranslit($text);
        $en = Lms_Text::testText($text, $freqIndex);
        $ru = Lms_Text::testText($detranslitedText, $freqIndex);
        return ($ru>$en)? $detranslitedText : $text;
    }    
    
    public static function escapeshellarg($arg)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return escapeshellarg($arg);
        } else {
            return "'" . str_replace("'", "'\\''", $arg) . "'";
        }
        
    }
    
}