#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/persones-parsing.pid');
if ($pid->isRunning()) {
    exit;
} 

$log = Lms_Item_Log::create('persones-parsing', 'Началось');

try {
    echo "\nParsing: ";
    $i = 0;
    $limit = 10;
    while (true) {
        $persons = Lms_Item_Person::selectForParsing($limit);
        if (!count($persons)) {
            break;
        }
        foreach ($persons as $person) {
            echo ".";
            $person->parse()
                ->save();
            $i++;
        }
    }

    echo " done\n";
    $log->done(Lms_Item_Log::STATUS_DONE, "Готово. Спарсено персоналий: $i");
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "Ошибка: " . $e->getMessage());
}

require_once dirname(__FILE__) . '/include/end.php'; 
