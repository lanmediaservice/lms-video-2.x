#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/suggestion-cache.pid');
if ($pid->isRunning()) {
    exit;
} 


$db = Lms_Db::get('main');

$log = Lms_Item_Log::create('suggestion-cache', 'Началось');

try {
    @$db->query('SET lock_wait_timeout=30;');
    $db->query('TRUNCATE `suggestion_cache`');
    echo "\nIndexing...";
    $n = 1;
    $limit = 100;
    while (true) {
        $rows = $db->select('SELECT (LEFT(`word`,?d)) as `query`, COUNT(*) as c FROM `suggestion` WHERE LENGTH(`word`)>=?d GROUP BY (LEFT(`word`, ?d)) HAVING c>?d', $n, $n, $n, $limit);
        if (!count($rows)) {
            break;
        }
        echo "\n$n: " . count($rows) . " ({$rows[0]['query']}) ";
        foreach ($rows as $num => $row) {
            if (!($num % 10)) {
                echo '.';
            }
            $suggestion = Lms_Item_Suggestion::getSuggestion($row['query']);
            $db->query(
                'INSERT IGNORE INTO `suggestion_cache` SET `query`=? ,`result`=?',
                $row['query'], Zend_Json::encode($suggestion)
            );
        }
        echo ' OK';
        $n += 1;
    }

    echo "\nDone\n";
    $log->done(Lms_Item_Log::STATUS_DONE, "Готово");
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "Ошибка: " . $e->getMessage());
}

require_once dirname(__FILE__) . '/include/end.php'; 
