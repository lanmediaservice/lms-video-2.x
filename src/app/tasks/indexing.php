#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$db = Lms_Db::get('main');

$log = Lms_Item_Log::create('indexing', 'Началось');
$report = '';

try {
    @$db->query('SET lock_wait_timeout=30;');

    $i = 0;
    while (true) {
        try {
            $db->query('TRUNCATE `suggestion`');
            $db->query('TRUNCATE `search_trigrams`');

            echo $m = "\nIndexing";
            $report .= $m;
            echo $m = "\nMovies: ";
            $report .= $m;
            $log->progress("Индексация фильмов");
            $n = 0;
            $size = 200;
            while (true) {
                $rows = $db->select('SELECT movie_id, name, international_name FROM movies ORDER BY movie_id LIMIT ?d, ?d', $n, $size);
                if (!count($rows)) {
                    break;
                }
                echo $m = "..$n..";
                $report .= $m;
                $trigramValues = array();
                $suggestionValues = array();
                foreach ($rows as $row) {
                    Lms_Application::prepareTextIndex($row['name'], 'movie', $row['movie_id'], $trigramValues, $suggestionValues);
                    if ($row['name']!=$row['international_name']) {
                        Lms_Application::prepareTextIndex($row['international_name'], 'movie', $row['movie_id'], $trigramValues, $suggestionValues);
                    }
                }
                $db->query('INSERT IGNORE INTO `search_trigrams`(`trigram`,`type`, `id`) VALUES ' . implode(', ', $trigramValues));
                $db->query('INSERT IGNORE INTO `suggestion`(`word`,`type`, `id`) VALUES ' . implode(', ', $suggestionValues));

                $n += $size;
            }
            echo $m = " done\n";
            $report .= $m;

            echo $m = "\nPersons: ";
            $report .= $m;
            $log->progress("Индексация персоналий");

            $n = 0; 
            $size = 500;
            while (true) {
                $rows = $db->select('SELECT person_id, name, international_name FROM persones ORDER BY person_id LIMIT ?d, ?d', $n, $size);
                if (!count($rows)) {
                    break;
                }
                echo $m = "..$n..";
                $report .= $m;


                $trigramValues = array();
                $suggestionValues = array();
                foreach ($rows as $row) {
                    Lms_Application::prepareTextIndex($row['name'], 'person', $row['person_id'], $trigramValues, $suggestionValues);
                    if ($row['name']!=$row['international_name']) {
                        Lms_Application::prepareTextIndex($row['international_name'], 'person', $row['person_id'], $trigramValues, $suggestionValues);
                    }
                }
                $db->query('INSERT IGNORE INTO `search_trigrams`(`trigram`,`type`, `id`) VALUES ' . implode(', ', $trigramValues));
                $db->query('INSERT IGNORE INTO `suggestion`(`word`,`type`, `id`) VALUES ' . implode(', ', $suggestionValues));

                $n += $size;
            }
            echo $m = " done\n";
            $report .= $m;

        } catch (Lms_Db_Exception $e) {
            Lms_Debug::err($e->getMessage());
            Lms_Debug::debug('Restart indexing...');
            if ($i>=3) {
                break;
            }
            $i++;
            continue;
        }
        break;
    }

    $log->done(Lms_Item_Log::STATUS_DONE, "Готово", trim($report));
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "Ошибка: " . $e->getMessage(), trim($report));
}


require_once dirname(__FILE__) . '/include/end.php'; 
