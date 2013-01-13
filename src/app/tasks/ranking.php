#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/ranking.pid');
if ($pid->isRunning()) {
    exit;
} 

$db = Lms_Db::get('main');

$log = Lms_Item_Log::create('ranking', 'Началось');
$report = '';

try {
    echo $m = "\nRanking...";
    $report .= $m;

    echo $m = "\nMovies:\n";
    $report .= $m;
    $log->progress("Ранжирование фильмов");

    $db->query('UPDATE movies SET Rank=1');
    $n = 0; 
    while (true) {
        $rows = $db->select(
            'SELECT movie_id, GREATEST(1, SUM(LEAST(1, ?d/(TO_DAYS(CURDATE()) - TO_DAYS(h.created_at) + 1)))) as rank FROM `movies` m INNER JOIN hits h USING(movie_id) GROUP BY movie_id ORDER BY movie_id LIMIT ?d, 1000',
            7, $n
        );
        if (!count($rows)) {
            break;
        }
        echo $m = "..$n..";
        $report .= $m;

        foreach ($rows as $row) {
            if ($row['rank']!=1) {
                $db->query('UPDATE movies SET rank=?d WHERE movie_id=?d', $row['rank'], $row['movie_id']);
            }
        }

        $n += 1000;
    }

    echo $m = "\nPersons:\n";
    $report .= $m;
    $log->progress("Ранжирование персоналий");

    $n = 0; 
    while (true) {
        $rows = $db->select('SELECT person_id, sum(m.rank * IF(r.name IN(\'режиссер\',\'режиссёр\',\'актер\',\'актриса\'), 1, 0.2)) as rank FROM `persones` p INNER JOIN participants USING(person_id) INNER JOIN roles r USING(role_id) INNER JOIN movies m USING(movie_id) GROUP BY p.person_id ORDER BY p.person_id LIMIT ?d, 1000', $n);
        if (!count($rows)) {
            break;
        }
        echo $m = "..$n..";
        $report .= $m;

        foreach ($rows as $row) {
            $db->query('UPDATE persones SET rank=?d WHERE person_id=?d', $row['rank'], $row['person_id']);
        }
        $n += 1000;
    }

    echo $m = "\nDone\n";
    $report .= $m;

    $log->done(Lms_Item_Log::STATUS_DONE, "Готово", trim($report));
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "Ошибка: " . $e->getMessage(), trim($report));
}

require_once dirname(__FILE__) . '/include/end.php'; 
