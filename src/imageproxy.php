<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: api.php 700 2011-06-10 08:40:53Z macondos $
 */

if (!isset($_GET['url'])) {
    exit;
}
define('SKIP_DEBUG_CONSOLE', true);

require_once dirname(__FILE__) . "/app/config.php";

Lms_Application::setRequest();
Lms_Application::prepareApi();
Lms_Debug::debug('Request URI: ' . $_SERVER['REQUEST_URI']);
Lms_Debug::debug($_GET['url']);

$user = Lms_User::getUser();
if ($user->isAllowed("image-proxy", "use")) {
    $url = Lms_Thumbnail::proxyUrl($_GET['url']);
    if ($url) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $url);
        header("Pragma: public");
        header("Cache-Control: public");
        header("Expires: " . date("r", time() + 600));
    }
} else {
    echo 'Недостаточно прав';
}

Lms_Application::close();