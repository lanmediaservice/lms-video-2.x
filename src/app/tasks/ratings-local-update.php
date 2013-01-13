#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/ratings-local-update.pid');
if ($pid->isRunning()) {
    exit;
} 

$log = Lms_Item_Log::create('ratings-local-update', 'Началось');

try {

    echo "\nUpdate local ratings";
    $result = Lms_Item_Rating::updateLocalRatings();
    echo "\nDone\n";
    
    $report = "Пересчитано рейтингов: " . $result['updated'];
    echo $report;
    $log->done(Lms_Item_Log::STATUS_DONE, "Готово", $report);
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "Ошибка: " . $e->getMessage());
}

require_once dirname(__FILE__) . '/include/end.php'; 
