#!/usr/local/bin/php -q
<?php

require_once dirname(__FILE__) . '/include/init.php';

$pid = Lms_Pid::getPid(Lms_Application::getConfig('tmp') . '/files-check.pid');
if ($pid->isRunning()) {
    exit;
}

ini_set('memory_limit', 134217728);

function scanDirectory($directory)
{
    $files = array();
    $path = Lms_Application::normalizePath($directory);
    if ($dh = Lms_Ufs::opendir($path)) {
        while (($file = Lms_Ufs::readdir($dh)) !== false) {
            if (($file!='.') && ($file!='..')) {
                $filePath = $path . "/" . $file;
                $isDir = (bool) Lms_Ufs::is_dir($filePath);
                $file = array(
                    'path' => $filePath,
                    'size' => !$isDir? Lms_Ufs::filesize($filePath) : null,
                    'is_dir' => $isDir
                );
                $files[] = $file;
            }
        }
        Lms_Ufs::closedir($dh);
    }
    return $files;
}

function getHash($name, $size)
{
    return md5(strtolower($name) . ':' . $size);
}

$log = Lms_Item_Log::create('files-check', 'Началось');
$report = '';

try {
    echo "\nReset";
    
    $db = Lms_Db::get('main');
            
    $db->query('UPDATE `files` SET `active`=1');
    $db->query('DELETE FROM `files_lost`');

    
    echo "\nLoad files";
    $dbFilesPaths = array();
    $dbFilesSizes = array();
    $dbFilesDirs = array();
    $i = 0;
    $n = 1000;
    while (true) {
        $rows = $db->select('SELECT `file_id` AS ARRAY_KEY, `path`, `size`, `is_dir` FROM files LIMIT ?d, ?d', $i, $n);
        if (!count($rows)) {
            break;
        }
        foreach ($rows as $fileId => $row) {
            $dbFilesPaths[$fileId] = Lms_Application::normalizePath($row['path']);
            $dbFilesSizes[$fileId] = floatval($row['size']);
            if ($row['is_dir']) {
                $dbFilesDirs[$fileId] = true;
            }
        }
        unset($rows);
        $i += $n;
    }
    
    echo "\nPrepare";
    $dbFilesIndex = array_flip($dbFilesPaths);
    $dbFilesParents = array();
    foreach ($dbFilesPaths as $fileId => $dbFilePath) {
        $parentPath = dirname($dbFilePath);
        if (isset($dbFilesIndex[$parentPath])) {
            $dbFilesParents[$fileId] = $dbFilesIndex[$parentPath];
        }
    }
    unset($dbFilesIndex);
    
    echo "\nScan: ";
    $sources = array();
    foreach (Lms_Application::getConfig('download', 'masks') as $mask) {
        $sources[] = $mask['source'];
    }
    $directories = array_unique(array_merge(
        Lms_Application::getConfig('incoming', 'root_dirs'), 
        Lms_Application::getConfig('incoming', 'storages')?: array(), 
        $sources
    ));
    
    $files = array();
    $filesIndex = array();

    $scannedDirectories = array();
    
    $t1 = microtime(true);
    for ($i=0; $i<=count($directories); $i++) {
        $directory = $directories[$i];
        if (array_key_exists($directory, $scannedDirectories)) {
            continue;
        }
        echo '.';
        $newFiles = scanDirectory($directory);
        $scannedDirectories[$directory] = true;
        
        foreach ($newFiles as $file) {
            if ($file['is_dir']) {
                if (Lms_Ufs::is_readable($file['path']) && !array_key_exists($file['path'], $scannedDirectories)) {
                    echo "/";
                    $directories[] = $file['path'];
                }
            }
            $name = basename($file['path']);
            $size = $file['size'];
            $hash = getHash($name, $size);
            $files[$hash] = $file['path'];
            $filesIndex[$file['path']] = true;
        }
        if ((microtime(true) - $t1)>2) {
            $t1 = microtime(true);
            $message = "Сканируется '$directory', осталось " . (count($directories)-$i) . " (исп. память: " . round(memory_get_usage()/1024/1024, 2) . "MiB)";
            $log->progress($message);
        }
    }     
    
    unset($newFiles);
    unset($directories);
    unset($scannedDirectories);
    
    echo "\nCompare";
    $log->progress("Сравнение");

    //print_r($files);
    //$dbFilesIds = array_keys($dbFilesSize);
    
    $brokenCounter = 0;
    $relocationCounter = 0; 
    
    $relocatedFiles = '';
    $notfoundFiles = '';
    
    foreach ($dbFilesPaths as $fileId => $dbFilePath) {
        $dbFileSize = $dbFilesSizes[$fileId];
        if (empty($dbFilesDirs[$fileId]) && !isset($filesIndex[$dbFilePath])) {
            $brokenCounter++;
            $dbFileName = basename($dbFilePath);
            $hash = getHash($dbFileName, $dbFileSize);
            if (isset($files[$hash])) {
                $path = $files[$hash];
                $relocationCounter++;
                $db->query(
                    'INSERT IGNORE INTO `files_lost` SET `file_id`=?d, `path`=?',
                    $fileId,
                    $path    
                );
                $relocatedFiles .= "\n'" . $dbFilePath . "' -> '" . $path . "'";
                
                while (isset($dbFilesParents[$fileId])) {
                    $fileId = $dbFilesParents[$fileId];
                    $path = dirname($path);
                    $db->query(
                        'INSERT IGNORE INTO `files_lost` SET `file_id`=?d, `path`=?',
                        $fileId,
                        $path
                    );
                }
            } else {
                $db->query(
                    'UPDATE `files` SET `active`=0 WHERE `file_id`=?d',
                    $fileId
                );
                $notfoundFiles .= "\n" . $dbFilePath . " ($dbFileSize B)";
            }
        }
    }

    $report .= "\nПроверка файлов завершена." 
             . "\nПотерянных файлов: $brokenCounter"
             . "\n\tнайдены: $relocationCounter"
             . "\n\tне найдены: " . ($brokenCounter - $relocationCounter);
    
    if ($relocatedFiles) {
        $report .= "\n\nСписок найденных файлов: $relocatedFiles";
    }
    if ($notfoundFiles) {
        $report .= "\n\nСписок ненайденных файлов: $notfoundFiles";
    }
    
    echo "\nDone\n";
    
    $log->done(Lms_Item_Log::STATUS_DONE, "Готово", trim($report));
    
} catch (Exception $e) {
    Lms_Debug::crit($e->getMessage());
    $log->done(Lms_Item_Log::STATUS_ERROR, "Ошибка: " . $e->getMessage(), trim($report));
}

require_once dirname(__FILE__) . '/include/end.php';