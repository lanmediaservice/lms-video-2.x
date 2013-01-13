#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/files-metainfo.pid');
if ($pid->isRunning()) {
    exit;
}


$log = Lms_Item_Log::create('files-metainfo', 'Началось');
$report = '';

try {
    echo "\nGenerate metainfo: ";
    $i = 0;
    while (true) {
        $files = Lms_Item_File::selectWithoutMetainfo(20);
        if (!count($files)) {
            break;
        }

        foreach ($files as $file) {
            try {
                echo '.';
                $log->progress("Обрабатывается файл: " . $file->getPath());
                $file->parseMetainfo()
                     ->save();
                $i++;
                $report .= "\nОбработан файл: " . $file->getPath();
            } catch (Lms_Exception $e) {
                Lms_Debug::err($e->getMessage());
                $message = "Ошибка: " . $e->getMessage();
                $report .= "\n$message";
                $log->progress($message);
            }
        }
    }

    echo " done\n";
    $log->done(Lms_Item_Log::STATUS_DONE, "Готово. Обработано файлов: $i", trim($report));
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "Ошибка: " . $e->getMessage(), trim($report));
}

require_once dirname(__FILE__) . '/include/end.php'; 
