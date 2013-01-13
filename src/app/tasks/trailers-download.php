#!/usr/local/bin/php -q
<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: trailers-download.php 700 2011-06-10 08:40:53Z macondos $
 */

require_once dirname(__FILE__) . '/include/init.php';

if (!Lms_Application::getConfig('trailers', 'download')) {
    exit;
}

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/trailers-download.pid');
if ($pid->isRunning()) {
    exit;
}

function downloadCurl($url, $destinationFilePath)
{
    $tmpPath = Lms_Application::getConfig('tmp') . '/tmp-trailer-' . time();
    
    $fileHandle = fopen($tmpPath, 'w');

    if (false === $fileHandle) {
        throw new Exception('Could not open filehandle');
    }

    $ch = curl_init();
    $headers = array(
        'Accept: */*',
        'Accept-Language: ru',
        'Accept-Encoding: gzip, deflate',
        'Referer: ' . dirname($url) 
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FILE, $fileHandle);

    $result = curl_exec($ch);
    curl_close($ch);
    fclose($fileHandle);
    
    Lms_FileSystem::createFolder(dirname($destinationFilePath), 0777, true); 
    rename($tmpPath, $destinationFilePath);

    if (false === $result) {
        throw new Exception('Could not download file');
    }
}

$movies = Lms_Item_Movie::selectNotLocalized(50);
foreach ($movies as $movie) {
    try {
        $moiveId = $movie->getId();
        echo "\n#{$moiveId}:";
        $trailerStruct = $movie->getTrailer();
//        echo "\n    {$trailerStruct['preview']}: "; 
//        Lms_Application::thumbnail($trailerStruct['preview']);
//        echo " OK"; 
        
        $dstVideo = Lms_Application::pathToLocalizedVideo($trailerStruct['video']);
        if (!is_file($dstVideo)) {
            echo "\n    {$trailerStruct['video']}: ";
            downloadCurl($trailerStruct['video'], $dstVideo);
            echo " OK";
        }
        
        $movie->setTrailerLocalized(1)
              ->save();
    } catch (Exception $e) {
        echo $e->getMessage();
        Lms_Debug::err($e->getMessage());
        $movie->setTrailerLocalized(0)
              ->save();
    }
}
require_once dirname(__FILE__) . '/include/end.php';