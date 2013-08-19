<?php

class Lms_Api_Cli_Rating {

    public static function add()
    {
        $opts = new Zend_Console_Getopt(
            array(
                'help|h'    => 'показать справку',
                'user-name|u=s'    => 'имя пользователя (логин)',
                'uid=i'    => 'ID пользователя, параметр -u будет проигнорирован',
                'rating|r=i'    => 'рейтинг 1..10',
                'created-at|c=s'    => 'дата создания в формате yyyy-mm-dd hh:mm:ss',
                'movie-id|m=i'    => 'ID фильма',
                'force-update'    => 'пересчитать локальный рейтинг фильма сразу (не рекомендуется), см. также команду "... rating update -h"',
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

        $rating = $opts->getOption('r');
        if ($rating<1 || $rating>10) {
            throw new Zend_Console_Getopt_Exception(
                "Параметр -r должен быть в пределах от 1 до 10",
                $opts->getUsageMessage());
        }
        
        $movieUserRatingId = Lms_Item_MovieUserRating::replaceRating($movieId, $rating, $userId, $opts->getOption('c'));

        if ($opts->getOption('force-update')) {
            Lms_Item_Movie::updateLocalRating($movieId);
        }
        
        
        return $movieUserRatingId;
    }
    
    public static function update()
    {
        $opts = new Zend_Console_Getopt(
            array(
                'help|h'    => 'показать справку',
                'all|a'    => 'пересчитать локальные рейтинги всех фильмов, параметр -m будет проигнорирован',
                'movie-id|m=i'    => 'ID фильма, для которого необходимо пересчитать локальный рейтинг',
            )
        );
        $opts->parse();
        
        if ($opts->getOption('h')) {
            Lms_Api_Cli::showUsageAndExit($opts, 0);
        }
        
        if ($opts->getOption('a')) {
            Lms_Item_Rating::updateLocalRatings();
        } else if ($movieId = $opts->getOption('m')) {
            Lms_Item_Movie::updateLocalRating($movieId);
        } else {
            throw new Zend_Console_Getopt_Exception(
                "Необходимо задать параметр -m или -a",
                $opts->getUsageMessage());
        }
        return;
        
    }
}
