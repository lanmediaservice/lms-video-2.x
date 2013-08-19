<?php

class Lms_Api_Cli_File {

    public static function add()
    {
        $opts = new Zend_Console_Getopt(
            array(
                'help|h'    => 'показать справку',
                'movie-id|m=i'    => 'ID фильма, к которому будет добавлен файл или папка',
                'path|p=s'    => 'путь к файлу или папке (вложенные файлы также будут добавлены)',
                'size|s=s'    => 'размер файла (используется только при отстутствии файла и параметре --skip-errors)',
                'is-dir|d'    => 'признак директории (используется только при отстутствии файла и параметре --skip-errors)',
                
                'quality|q=s'    => 'качество видео (если файл или папка уже будут существовать, параметр все равно применится)',
                'translation|t=s'    => 'перевод(ы) <перевод1[,перевод2[,перевод3...]]> (если файл или папка уже будут существовать, параметр все равно применится)',
                'skip-errors' => 'игнорировать ошибки',
            )
        );
        $opts->parse();
        
        if ($opts->getOption('h')) {
            Lms_Api_Cli::showUsageAndExit($opts, 0);
        }

        $movieId = $opts->getOption('m');
        
        $movie = Lms_Item::create('Movie', $movieId);

        $path = $opts->getOption('p');
        $path = Lms_Application::normalizePath($path);
        
        $db = Lms_Db::get('main');
        $db->transaction();
        
        if (!Lms_Ufs::file_exists($path)) {
            if ($opts->getOption('skip-errors')) {
                $isDir = $opts->getOption('d')? 1 : 0;
                $files = array(array(
                    'path' => $path,
                    'basename' => basename($path),
                    'size' => !$isDir? $opts->getOption('s') : null,
                    'is_dir' => $isDir
                ));
                $movie->updateFilesByStruct($files);
            } else {
                throw new Zend_Console_Getopt_Exception(
                    "Файл $path не найден",
                    $opts->getUsageMessage());
            }
        } else {
            $files = Lms_Item_File::parseFiles($path);
            $movie->updateFilesByStruct($files);
        }
        
        $quality = null;
        if ($opts->getOption('q')) {
            $quality = $opts->getOption('q');
        }
        $translation = array();
        if ($opts->getOption('t')) {
            $translation = preg_split("/(\s*,\s*)/", $opts->getOption('t'));
        }
        
        $filesIds = array();
        foreach ($movie->getFiles() as $file) {
            $filesIds[] = $file->getId();
            if ($file->getPath()==$path) {
                if ($quality = $opts->getOption('q')) {
                    $file->setQuality($quality);
                }
                if ($translation = $opts->getOption('t')) {
                    $translation = preg_split("/(\s*,\s*)/", $translation);
                    $file->setTranslation($translation);
                }
                $file->save();
            }
        }
        $db->commit();

        return implode(',', $filesIds);
    }
}
