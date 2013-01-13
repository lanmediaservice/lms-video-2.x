<?php
class Lms_Translate {
    
    public static function translate($fromEncoding, $toEncoding, $string)
    {
        if(!is_string($fromEncoding) || !is_string($toEncoding) || !is_string($string)) {
            return false;
        }
        if ($toEncoding == $fromEncoding) {
            return $string;
        }
        if (function_exists('iconv')) {
            $res = iconv($fromEncoding, $toEncoding, $string);
            if (false === $res) {
                throw new Lms_Exception("Unknown encoding '$fromEncoding' or '$toEncoding'!");
                return NULL;
            }
            return $res;
        } else {
            if (function_exists('mb_convert_encoding')) {
                $res = mb_convert_encoding( $string, $toEncoding,  $fromEncoding);
                if (false === $res) {
                    throw new Lms_Exception("Unknown encoding '$fromEncoding' or '$toEncoding'!");
                    return NULL;
                }
                return $res;
            }
        }
        return self::useDecoder($fromEncoding, $toEncoding, $string);
    }
    /**
     * Заглушка. Здесь должны быть собственные фунции  конвертации для простых кодировок
     *
     * @param string $fromEncoding
     * @param string $toEncoding
     * @param string $string
     * @return string
     */
    static private function useDecoder($fromEncoding, $toEncoding, $string)
    {
        return false;
    }
}
?>