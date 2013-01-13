<?php

/**
 * @author Alexey Kupershtokh <alexey.kupershtokh@gmail.com>
 * @url http://kupershtokh.blogspot.com/2007/12/on-phpclub.html
 */
 
class Lms_Hash_Tth
{
    private static $_base32Alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private static $_tigerHash = null;
    private static $_tigerMhash = null;

    /**
     * Generates DC-compatible TTH of a file.
     *
     * @param string $filename
     * @return string
     */
    public static function getTTH($filename)
    {
        $fp = fopen($filename, "rb");
        if ($fp) {
            $i = 1;
            $hashes = array();
            while (!feof($fp)) {
                $buf = fread($fp, 1024);
                if ($buf || ($i == 1)) {
                    $hashes[$i] = self::tiger("\0".$buf);
                    $j = 1;
                    while ($i % ($j * 2) == 0) {
                        $hashes[$i] = self::tiger(
                            "\1" . $hashes[$i - $j] . $hashes[$i]
                        );
                        unset($hashes[$i - $j]);
                        $j = round($j * 2);
                    }
                    $i++;
                }
            }
            $k = 1;
            while ($i > $k) {
                $k = round($k * 2);
            }
            for (; $i <= $k; $i++) {
                    $j = 1;
                    while ($i % ($j * 2) == 0) {
                        if (isset($hashes[$i])) {
                            $hashes[$i] = self::tiger(
                                "\1" . $hashes[$i - $j] . $hashes[$i]
                            );
                        } else if (isset($hashes[$i - $j])) {
                            $hashes[$i] = $hashes[$i - $j];
                        }
                        unset($hashes[$i - $j]);
                        $j = round($j * 2);
                    }
            }
            fclose($fp);

            return self::base32encode($hashes[$i-1]);
        }
    }

    /**
     * Generates a DC-compatible tiger hash (not TTH).
     * Automatically chooses between hash() and mhash().
     *
     * @param string $string
     * @return string
     */
    private static function tiger($string)
    {
        if (is_null(self::$_tigerHash)) {
             if (function_exists("hash_algos")
                     && in_array("tiger192,3", hash_algos())
             ) {
                 self::$_tigerHash = true;
             }
        }
        if (self::$_tigerHash) {
            return self::tigerfix(hash("tiger192,3", $string, 1));
        }

        if (is_null(self::$_tigerMhash)) {
            self::$_tigerMhash = function_exists("mhash");
        }
        if (self::$_tigerMhash) {
            return self::tigerfix(mhash(MHASH_TIGER, $string));
        }

        trigger_error(E_USER_ERROR, "Neither tiger hash function is available.");
    }

    /**
     * Repairs tiger hash for compatibility with DC.
     *
     * @url http://www.php.net/manual/en/ref.mhash.php#55737
     * @param string $binary_hash
     * @return string
     */
    private static function tigerfix($binaryHash)
    {
        $mySplit = str_split($binaryHash, 8);
        $myTiger = "";
        foreach ($mySplit as $key => $value) {
             $mySplit[$key] = strrev($value);
             $myTiger .= $mySplit[$key];
        }
        return $myTiger;
    }

    /**
     * Just a base32encode function :)
     *
     * @url http://www.php.net/manual/en/function.sha1-file.php#61741
     * @param string $input
     * @return string
     */
    private static function base32encode($input)
    {
        $output = '';
        $position = 0;
        $storedData = 0;
        $storedBitCount = 0;
        $index = 0;
        while ($index < strlen($input)) {
            $storedData <<= 8;
            $storedData += ord($input[$index]);
            $storedBitCount += 8;
            $index += 1;
            //take as much data as possible out of storedData
            while ($storedBitCount >= 5) {
                $storedBitCount -= 5;
                $output .= self::$_base32Alphabet[$storedData >> $storedBitCount];
                $storedData &= ((1 << $storedBitCount) - 1);
            }
        } //while
        //deal with leftover data
        if ($storedBitCount > 0) {
            $storedData <<= (5-$storedBitCount);
            $output .= self::$_base32Alphabet[$storedData];
        }
        return $output;
    }
}