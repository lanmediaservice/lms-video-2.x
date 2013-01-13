#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/persones-fix.pid');
if ($pid->isRunning()) {
    exit;
}

$log = Lms_Item_Log::create('persones-fix', 'Началось');

try {
    echo "\nFix persones: ";
    $result = Lms_Item_Person::fixAll();
    echo " done\n";
    
    $report = "Объединено персоналий: " . $result['merged']
            . "\nУдалено несвязанных с фильмами персоналий: " . $result['persones_deleted']
            . "\nУдалено ссылок на несуществующие персоналии: " . $result['participants_deleted'];
    echo $report;
    $log->done(Lms_Item_Log::STATUS_DONE, "Готово", $report);
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "Ошибка: " . $e->getMessage());
}

require_once dirname(__FILE__) . '/include/end.php'; 
