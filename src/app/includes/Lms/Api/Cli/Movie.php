<?php

class Lms_Api_Cli_Movie {

    public static function add()
    {
        $opts = new Zend_Console_Getopt(
            array(
                'help|h'    => 'показать справку',
                'kinopoisk-id|k=i' => 'добавить фильм по kinopoisk ID, опции -u -n -i -y -d -c -g -m будут проигнорированы',
                'url|u=s' => 'добавить фильм по url (kinopoisk, imdb, ozon, world-art, sharereactor), можно использовать автослияние <url1[,url2[,url3...]]>, опции -n -i -y -d -c -g -m будут проигнорированы',
                
                'name|n=s'    => 'название',
                'international-name|int-name|i=s' => 'международное название',
                'year|y=s'    => 'год',
                'description|d=s'    => 'описание',
                'countries|c=s'    => '<страна1[,страна2[,страна3...]]>',
                'genres|g=s'    => '<жанр1[,жанр2[,жанр3...]]>',
                'mpaa|m=s'    => 'рейтинг MPAA',
                
                'cover=s'    => 'обложка(и) <url1[,url2[,url3...]]>',
                'hit=s'    => 'счетчик скачиваний',
                'created-by|cid=s'    => 'ID пользователя добавившего фильм',
                'created-at=s'    => 'дата добавления в формате yyyy-mm-dd hh:mm:ss',
                'updated-at=s'    => 'дата обновления в формате yyyy-mm-dd hh:mm:ss',
                'quality|q=s'    => 'качество видео',
                'translation|t=s'    => 'перевод(ы) <перевод1[,перевод2[,перевод3...]]>',
            )
        );
        $opts->parse();
        
        if ($opts->getOption('h')) {
            Lms_Api_Cli::showUsageAndExit($opts, 0);
        }
        $data = array();
        if ($kinopoiskId = $opts->getOption('k')) {
            $url = Lms_Service_Adapter_Kinopoisk::constructPath('film', array('id'=>$kinopoiskId));
            $data = Lms_Service_Movie::parseMovie($url);
        } else if ($url = $opts->getOption('u')) {
            $urls = preg_split('{\s*,\s*}i', $url);
            $forceMerge = true;
            foreach ($urls as $url) {
                $engine = Lms_Service_Movie::getModuleByUrl($url);
                $newData = Lms_Service_Movie::parseMovie($url, $engine);
                $data = Lms_Service_Movie::merge($data, $newData, $engine, $forceMerge);
            }
        } else {
            $data['name'] = $opts->getOption('n');
            $data['international_name'] = $opts->getOption('i');
            $data['year'] = $opts->getOption('y');
            $data['description'] = $opts->getOption('d');
            if ($value = $opts->getOption('c')) {
                $data['countries'] = preg_split("/(\s*,\s*)/", $value);
            }        
            if ($value = $opts->getOption('g')) {
                $data['genres'] = preg_split("/(\s*,\s*)/", $value);
                foreach ($data['genres'] as &$genre) {
                    $genre = ucfirst($genre);
                }
            }        
            $data['mpaa'] = $opts->getOption('m');
        }
        if ($cover = $opts->getOption('cover')) {
            $cover = preg_replace('{\s*,\s*}', "\n", $cover);
            $data['poster'] = $cover;
        } else if (empty($data['poster'])){
            $data['poster'] = '';
        }
        $quality = array();
        if ($opts->getOption('q')) {
            $quality['global'] = $opts->getOption('q');
        }
        $translation = array();
        if ($opts->getOption('t')) {
            $translations = preg_split("/(\s*,\s*)/", $opts->getOption('t'));
            $translation['global'] = $translations;
        }
        
        
        $db = Lms_Db::get('main');
        $db->transaction();

        $movie = Lms_Item_Movie::createFromInfo($data, array(), $quality, $translation);

        if ($hit = $opts->getOption('hit')) {
            $movie->setHit($hit);
        }
        if ($createdBy = $opts->getOption('cid')) {
            $movie->setCreatedBy($createdBy);
        }        
        if ($createdAt = $opts->getOption('created-at')) {
            $movie->setCreatedAt($createdAt);
        }        
        if ($updatedAt = $opts->getOption('updated-at')) {
            $movie->setUpdatedAt($updatedAt);
        } else if ($createdAt) {
            $movie->setUpdatedAt($createdAt);
        }
        $movie->save();
        
        $db->commit();

        return $movie->getId();
    }
    
    public static function delete()
    {
        $opts = new Zend_Console_Getopt(
            array(
                'help|h'    => 'показать справку',
                'movie-id|m=i' => 'ID фильма, который будет удален',
            )
        );
        $opts->parse();
        
        if ($opts->getOption('h')) {
            Lms_Api_Cli::showUsageAndExit($opts, 0);
        }
        $movieId = $opts->getOption('m');
        
        if (!$movieId) {
            throw new Zend_Console_Getopt_Exception(
                "Не указан ID фильма",
                $opts->getUsageMessage());
        }
        
        $movie = Lms_Item::create('Movie', $movieId);

        $db = Lms_Db::get('main');
        $db->transaction();

        $movie->delete();

        $db->commit();
    }
    
    public static function del()
    {
        return self::delete();
    }
    
    public static function merge()
    {
        $opts = new Zend_Console_Getopt(
            array(
                'help|h'    => 'показать справку',
                'movie-id|m=i' => 'ID фильма',
                'mm=s' => 'диапазон ID фильмов, например -m 1-100 (но осторожно с ресурсами времени и памяти), параметры -m -k будут проигнорированы',
                'kinopoisk-id|k=i' => 'kinopoisk ID в качестве источника данных, по-умолчанию используется сохраненный ID (если ID не указан, и не сохранен, то ничего не произойдет)',
                'retries|r=i' => 'повторные соединения при ошибке связи, по-умолчанию 3',
                'force-search' => 'пробовать определить kinopoisk ID по названию и году',
                'skip-errors' => 'пропускать ошибки парсинга',
            )
        );
        $opts->parse();
        
        if ($opts->getOption('h')) {
            Lms_Api_Cli::showUsageAndExit($opts, 0);
        }

        $moviesIds = $opts->getOption('mm');

        $retries = $opts->getOption('r')?: 3;
        
        if (preg_match('{(\d+)\-(\d+)}', $moviesIds, $matches)) {
            $from = $matches[1];
            $to = $matches[2];
            $movies = Lms_Item_Movie::selectSlice($from, $to);
        } else {
            $movieId = $opts->getOption('m');
            if (!$movieId) {
                throw new Zend_Console_Getopt_Exception(
                    "Не указан ID фильма или диапазон",
                    $opts->getUsageMessage());
            }
            $movies = array(Lms_Item::create('Movie', $movieId));
        }
        
        foreach ($movies as $movie) {
            $movieId = $movie->getId();
            file_put_contents('php://stderr', "Movie ID $movieId, KinoPoisk ID ... ");
            $kinopoiskId = !$opts->getOption('mm')? $opts->getOption('k') : null;
            if (!$kinopoiskId) {
                $rating = Lms_Item_Rating::getBySystem($movieId, 'kinopoisk');
                if ($rating) {
                    $kinopoiskId = $rating->getSystemUid();
                }
            }

            if (!$kinopoiskId) {
                if ($opts->getOption('force-search')) {
                    file_put_contents('php://stderr', "search ... ");
                    $ids = Lms_Service_Movie::searchKinopoiskId($movie->getName(), $movie->getYear());
                    $count = count($ids);
                    file_put_contents('php://stderr', "$count results - ");
                    if ($count==1) {
                        $kinopoiskId = $ids[0]; 
                        file_put_contents('php://stderr', "OK ");
                    } else if ($count>1) {
                        file_put_contents('php://stderr', "FAIL (too many), skipped\n");
                    } else if ($count==0) {
                        file_put_contents('php://stderr', "FAIL, skipped\n");
                    }
                } else {
                    file_put_contents('php://stderr', "not found\n");
                }
            }
            
            if ($kinopoiskId) {
                file_put_contents('php://stderr', "$kinopoiskId, parse ...");
                $url = Lms_Service_Adapter_Kinopoisk::constructPath('film', array('id'=>$kinopoiskId));
                $r = $retries;
                $data = null;
                while ($r>0) {
                    try {
                        $data = Lms_Service_Movie::parseMovie($url);
                        break;
                    } catch (Zend_Http_Client_Adapter_Exception $e) {
                        file_put_contents('php://stderr', ".");
                        $r--;
                        if ($r==0) {
                            if ($opts->getOption('skip-errors')) {
                                file_put_contents('php://stderr', " FAIL\n");
                                continue 2;
                            } else {
                                throw $e;
                            }
                        }
                    } catch (Lms_Service_DataParser_Exception $e) {
                        if ($opts->getOption('skip-errors')) {
                            file_put_contents('php://stderr', " FAIL\n");
                            continue 2;
                        } else {
                            throw $e;
                        }
                    }
                }
                file_put_contents('php://stderr', " OK, merge ... ");

                $db = Lms_Db::get('main');
                $db->transaction();

                $merged = $movie->mergeInfo($data);        

                $db->commit();
                $count = count(array_filter($merged));
                $total = count($merged);
                file_put_contents('php://stderr', sprintf("OK (%s%%)\n", round(100*$count/$total)));
            }
        }
    }
    
    public static function search()
    {
        $opts = new Zend_Console_Getopt(
            array(
                'help|h'    => 'показать справку',
                'name|n=s'    => 'название',
                'year|y=s'    => 'год'
            )
        );
        $opts->parse();
        
        if ($opts->getOption('h')) {
            Lms_Api_Cli::showUsageAndExit($opts, 0);
        }

        $name = $opts->getOption('n');
        $year = $opts->getOption('y');

        $kinopoiskId = null;
        file_put_contents('php://stderr', "search ... ");
        $ids = Lms_Service_Movie::searchKinopoiskId($name, $year);
        $count = count($ids);
        file_put_contents('php://stderr', "$count results - ");
        if ($count==1) {
            $kinopoiskId = $ids[0]; 
            file_put_contents('php://stderr', "OK ");
        } else if ($count>1) {
            file_put_contents('php://stderr', "FAIL (too many)\n");
        } else if ($count==0) {
            file_put_contents('php://stderr', "FAIL\n");
        }
        return $kinopoiskId;
    }
}
