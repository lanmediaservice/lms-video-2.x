<?php

if (!defined('SKIP_DEBUG_CONSOLE')) {
    define('SKIP_DEBUG_CONSOLE', true); 
}

header('Expires: -1');
require_once __DIR__ . "/app/config.php";

Lms_Application::prepare();

$movieId = (isset($_REQUEST['m'])) ? (int) $_REQUEST['m'] : null;
$fileId = (isset($_REQUEST['f'])) ? (int) $_REQUEST['f'] : null;
$player = $_REQUEST["p"];
    

$files = array();

try {
    if ($fileId) {
        $files[] = Lms_Item::create('File', $fileId);
    } else {
        $db = Lms_Db::get('main');
        $sql = "SELECT f.* FROM movies_files mf INNER JOIN files f USING(file_id) WHERE mf.movie_id = ?d AND is_dir=0 ORDER BY f.name";
        $rows = $db->select($sql, $movieId);
        $files = Lms_Item_Abstract::rowsToItems($rows, 'File');
    }

    Lms_Playlist::generatePlaylist($files, $player);
} catch (Exception $e) {
    echo "Error";
    Lms_Debug::crit($e->getMessage());
    Lms_Debug::crit($e->getTraceAsString());
}
