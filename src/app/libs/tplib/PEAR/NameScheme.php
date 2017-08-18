<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */
// +--------------------------------------------------------------------+
// | PHP version 4                                                      |
// +--------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                              |
// +--------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,     |
// | that is bundled with this package in the file LICENSE, and is      |
// | available through the world-wide-web at the following url:         |
// | http://www.php.net/license/3_0.txt.                                |
// | If you did not receive a copy of the PHP license and are unable to |
// | obtain it through the world-wide-web, please send a note to        |
// | license@php.net so we can mail you a copy immediately.             |
// +--------------------------------------------------------------------+
// | Authors: Dmitry Koteroff  <dmitry SOBAKA koteroff TOCHKA ru>       |
// +--------------------------------------------------------------------+
//
// $Id: standards.xml,v 1.24 2004/05/31 04:25:36 danielc Exp $


/**
 * PHP files extension.
 */
define("PEAR_NameScheme_ext", "php");

/**
 * Delimiter used to split packages inside full classname.
 */
define("PEAR_NameScheme_bar", "_");


/**
 * Namespace contains functions to translate classnames to filenames (and
 * vice versa) according to PEAR naming standards. These functions could 
 * be used in PHP's __autoload() method.
 * 
 * @category PEAR
 * @package  PEAR
 * @author   Dmitry Koteroff <dmitry SOBAKA koteroff TOCHKA ru>
 * @version  $Id: standards.xml,v 1.24 2004/05/31 04:25:36 danielc Exp $
 * @access   public
 */
class PEAR_NameScheme 
{
	var $VERSION = "1.00";
    // {{{ name2Path()

    /**
     * Translate classname to PEAR naming standard's filename which this 
     * class could be load from. Searching is performed in include_path 
     * variable.
     *
     * Function could be used, for example, in PHP5's __autoload() method 
     * to load classes on demand (see PEAR_NameScheme_Autoload).
     *
     * Convertion is CASE SENSITIVE!
     *
     * Samples:
     *   name2Path("PEAR", true)   -> /usr/lib/php/PEAR.php
     *   name2Path("pEaR", true)   -> no match (on Unix)
     *   name2Path("PEAR")         -> PEAR.php
     *   name2Path("XML_Parser")   -> XML/Parser.php
     *
     * @param string $classname   case-insensetive name of class to 
     *                            translate to filename.
     * @param bool   $absolutize  if TRUE, function absolutizes pathes 
     *                            before returning value.
     *
     * @return string  filename corresponding to classname. FALSE if 
     *                 file could not be found.
     *
     * @access public
     * @static
     */
    public static function name2Path($classname, $absolutize = false) 
    {
        $classname = str_replace('\\', "/", $classname);
        $fname = str_replace(PEAR_NameScheme_bar, '/', $classname) . 
                 '.' . PEAR_NameScheme_ext;
        foreach (PEAR_NameScheme::getInc($absolutize) as $libDir) {
            $path = $libDir . '/' . $fname;
            if (file_exists($path)) {
                if (!$absolutize) return $fname;
                else return $path;
            }
        }
        return false;
    }

    /**
     * Translate PEAR naming standard's filename to name of class which
     * is held in this file. You may specify absolute or relative 
     * filenames.
     *
     * If filename is relative, function does not check existance of the 
     * file. For absolute pathes it returns FALSE if file is not found.
     *
     * Samples:
     *   path2Name("/usr/lib/php/XML/Parser.php")  -> XML_Parser
     *   name2Path("XML/Parser")                   -> XML_Parser
     *   name2Path("XML/parser.php")               -> XML_parser
     *                                             
     * @param string $path case-sensetive absolute or relative pathname.
     *
     * @return string  classname corresponding to filename. FALSE 
     *                 if $path is absolute and could not be found.
     *
     * @access public
     * @static
     */
    public static function path2Name($path) 
    {
        if (preg_match('{^\w:|^[/\\\\]}s', $path)) {
            $path = str_replace("\\", "/", realpath($path));
            $inc = PEAR_NameScheme::getInc(true);
            $found = false;
            foreach ($inc as $i) {
                if (strpos($path, $i.'/') === 0) {
                    $path = substr($path, strlen($i)+1);
                    $found = true;
                    break;
                }
            }
            if (!$found) return false;
        }
        $name = preg_replace("{[/\\\\]}s", PEAR_NameScheme_bar, $path);
        $name = preg_replace('/\.'.PEAR_NameScheme_ext.'$/s', '', $name);
        return $name;
    }

    /**
     * Returns PHP's include_path as array (list), not as string. 
     * Also function can absolutize pathes found in include_path.
     *
     * @param bool $absolutize  if TRUE, returned elements are converted
     *                          to absolute pathnames.
     *
     * @return array  list of include_path entries.
     *
     * @access public
     * @static
     */
    public static function getInc($absolutize = false) 
    {
        $sep = defined("PATH_SEPARATOR")? PATH_SEPARATOR : 
            ((strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')? ";" : ":");
        $inc = explode($sep, ini_get("include_path"));
        if ($absolutize) $inc = array_map("realpath", $inc);
        return str_replace("\\", "/", $inc);
    }
}