<?php

class Lms_Api_Cli_Hit {

    public static function add()
    {
        $opts = new Zend_Console_Getopt(
            array(
                'help|h'    => 'показать справку',
                'movie-id|m=i'    => 'ID фильма',
                'user-name|u=s'    => 'имя пользователя (логин)',
                'uid=i'    => 'ID пользователя, параметр -u будет проигнорирован',
                'ip=s'    => 'IP пользователя',
                'created-at|c=s'    => 'дата в формате yyyy-mm-dd hh:mm:ss',
                'force-update'    => 'пересчитать количество скачиваний фильма сразу (не рекомендуется), см. также команду "... hit update -h"',
            )
        );
        $opts->parse();
        
        if ($opts->getOption('h')) {
            Lms_Api_Cli::showUsageAndExit($opts, 0);
        }

        if ($value = $opts->getOption('uid')) {
            $userId = $value;
        } else {
            $username = $opts->getOption('u');
            if (!$username) {
                throw new Zend_Console_Getopt_Exception(
                    "Не указан параметр -u или --uid",
                    $opts->getUsageMessage());
            }
            $user = Lms_Item_User::getByUserName($username);
            if (!$user) {
                throw new Zend_Console_Getopt_Exception(
                    "Пользователь '$username' не существует",
                    $opts->getUsageMessage());
            }
            $userId = $user->getId();
        }
        $movieId = $opts->getOption('m');
        if (!$movieId) {
            throw new Zend_Console_Getopt_Exception(
                "Не указан параметр -m",
                $opts->getUsageMessage());
        }

        $ip = $opts->getOption('ip');
        
        $hit = Lms_Item_Hit::select($movieId, $userId, $ip);
        if (!$hit) {
            $hit = Lms_Item::create('Hit');
            $hit->setMovieId($movieId)
                ->setUserId($userId)
                ->setIp($opts->getOption('ip'));
        }
        $hit->setCreatedAt($opts->getOption('c'))
            ->save();
        
        if ($opts->getOption('force-update')) {
            $movie = Lms_Item::create('Movie', $movieId);
            $movie->updateHit()
                  ->save();
        }
        
        
        return $hit->getId();
    }
    
    public static function update()
    {
        $opts = new Zend_Console_Getopt(
            array(
                'help|h'    => 'показать справку',
                'all|a'    => 'пересчитать количество скачиваний всех фильмов, параметр -m будет проигнорирован',
                'movie-id|m=i'    => 'ID фильма, для которого необходимо пересчитать количество скачиваний',
            )
        );
        $opts->parse();
        
        if ($opts->getOption('h')) {
            Lms_Api_Cli::showUsageAndExit($opts, 0);
        }
        
        if ($opts->getOption('a')) {
            Lms_Item_Hit::updateMoviesHit();
        } else if ($movieId = $opts->getOption('m')) {
            $movie = Lms_Item::create('Movie', $movieId);
            $movie->updateHit()
                  ->save();
        } else {
            throw new Zend_Console_Getopt_Exception(
                "Необходимо задать параметр -m или -a",
                $opts->getUsageMessage());
        }
        return;
    }
    
}
