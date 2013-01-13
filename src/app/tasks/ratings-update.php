#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/ratings-update.pid');
if ($pid->isRunning()) {
    exit;
} 

$log = Lms_Item_Log::create('ratings-update', 'Началось');

try {

    echo "\nUpdate ratings";
    $result = Lms_Item_Rating::updateRatings();
    echo "\nDone\n";
    
    $report = "Добавлено рейтингов KinoPoisk: " . $result['kinopoisk_add']
            . "\nОбновлено рейтингов KinoPoisk: " . $result['kinopoisk_update']
            . "\nДобавлено рейтингов IMDb: " . $result['imdb_add']
            . "\nОбновлено рейтингов IMDb: " . $result['imdb_update'];
    echo $report;
    $log->done(Lms_Item_Log::STATUS_DONE, "Готово", $report);
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "Ошибка: " . $e->getMessage());
}

require_once dirname(__FILE__) . '/include/end.php'; 
