<?php

class Lms_Api_Cli_Person {

    public static function add()
    {
        $opts = new Zend_Console_Getopt(
            array(
                'help|h'    => 'показать справку',
                'person-id|p=i' => 'ID существующей персоналии, опции -u -n -i --photos будут проигнорированы, опция имеет смысл только с параметрами -m -r',
                
                'url|u=s' => 'добавить персоналию по url (kinopoisk, imdb, ozon, world-art), опции -n -i --photos будут проигнорированы',
                
                'name|n=s'    => 'имя[,международное имя]',
                'info|i=s'    => 'информация, опция будет проигнорирована если персоналия будет найдена в базе данных по имени или url',
                'photos=s'    => 'фотографии <url1[,url2[,url3...]]>, опция будет проигнорирована если персоналия будет найдена в базе данных по имени или url',
                
                'movie-id|m=i'    => 'добавить персоналию к фильму с данным ID',
                'role|r=s'    => 'роль (режиссер/актер/..), опция используется только с параметром -m',
                'character|c=s'    => 'персонаж, опция используется только с параметром -m',
            )
        );
        $opts->parse();
        
        if ($opts->getOption('h')) {
            Lms_Api_Cli::showUsageAndExit($opts, 0);
        }
        
        if ($personId = $opts->getOption('p')) {
            $personItem = Lms_Item::create('Person', $personId);
        } else {
            $names = array();
            if ($value = $opts->getOption('n')) {
                $names = preg_split("/(\s*,\s*)/", $value);
            }
            $url = $opts->getOption('u');


            $personItem = Lms_Item_Person::getByMisc($names, $url);
            if (!$personItem) {
                $personItem = Lms_Item_Person::getByMiscOrCreate($names, $url);
                if ($value = $opts->getOption('i')) {
                    $personItem->setInfo($value);
                }
                if ($value = $opts->getOption('photos')) {
                    $photos = preg_replace('{\s*,\s*}', "\n", $value);
                    $personItem->setPhotos($photos);
                }
                $personItem->save();
            }
        }
        if ($movieId = $opts->getOption('m')) {
            $movie = Lms_Item::create('Movie', $movieId);
            $role = $opts->getOption('r');
            if (!$role) {
                throw new Zend_Console_Getopt_Exception(
                    "Не указана роль персоналии (-r)",
                    $opts->getUsageMessage());
            }
            $roleItem = Lms_Item_Role::getByNameOrCreate($role);

            $item = Lms_Item::create('Participant');
            $item->setMovieId($movie->getId())
                 ->setRoleId($roleItem->getId())
                 ->setPersonId($personItem->getId());
            if ($value = $opts->getOption('с')) {
                $item->setCharacter($value);
            }
            $item->save();
        }
        
        return $personItem->getId();
    }
    
    public static function delete()
    {
        $opts = new Zend_Console_Getopt(
            array(
                'help|h'    => 'показать справку',
                'person-id|p=i' => 'ID персоналии, которая будет удалена',
            )
        );
        $opts->parse();
        
        if ($opts->getOption('h')) {
            Lms_Api_Cli::showUsageAndExit($opts, 0);
        }
        $personId = $opts->getOption('p');
        
        if (!$personId) {
            throw new Zend_Console_Getopt_Exception(
                "Не указан ID персоналии",
                $opts->getUsageMessage());
        }
        
        $person = Lms_Item::create('Person', $personId);

        $db = Lms_Db::get('main');
        $db->transaction();

        $person->delete();

        $db->commit();
    }
    
    public static function del()
    {
        return self::delete();
    }
}
