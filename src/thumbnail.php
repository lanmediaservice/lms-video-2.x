<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: api.php 700 2011-06-10 08:40:53Z macondos $
 */

if (!isset($_GET['p']) || !isset($_GET['v'])) {
    exit;
}
define('SKIP_DEBUG_CONSOLE', true);

require_once dirname(__FILE__) . "/app/config.php";

Lms_Application::setRequest();
Lms_Application::prepareApi();
Lms_Debug::debug('Request URI: ' . $_SERVER['REQUEST_URI']);
$url = Lms_Thumbnail::processDeferUrl($_GET);
if ($url) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $url);
    header("Pragma: public");
    header("Cache-Control: public");
    header("Expires: " . date("r", time() + 600));
}

Lms_Application::close();