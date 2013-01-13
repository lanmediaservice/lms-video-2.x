<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: api.php 700 2011-06-10 08:40:53Z macondos $
 */

if (!isset($_GET['q'])) {
    exit;
}
require_once dirname(__FILE__) . "/app/config.php";

$query = Lms_Translate::translate('UTF-8', 'CP1251', $_GET['q']);
$_POST['action'][0] = 'Video.getSuggestion';
$_POST['query'][0] = $query;
$_GET['format'] = 'json';


Lms_Application::runApi();

header("Pragma: private");
header("Cache-Control: private");
header("Expires: " . date("r", time() + 600));

