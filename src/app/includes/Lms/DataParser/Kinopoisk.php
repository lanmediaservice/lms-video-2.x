<?php
/**
 * LMS Library
 * 
 * @version $Id: Kinopoisk.php 75 2010-10-12 08:52:20Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package DataParser
 */

class Lms_DataParser_Kinopoisk extends Lms_DataParser_Generic{
    
    static private $version = '$Id$';
    static private $host = 'www.kinopoisk.ru';
    
    static public function getHost()
    {
        return self::$host;
    }

    static public function getVersion()
    {
        return self::$version;
    }
    
    static public function constructPathSearch($params)
    {
        $query = urlencode($params['name']);
        return "https://www.kinopoisk.ru/index.php?kp_query=$query"; 
        return "https://www.kinopoisk.ru/search/" . (isset($params['type'])? $params['type'] . "/" : "")  . "?text=" . urlencode($params['name']);
    }
    
    static public function constructPathFilm($params)
    {
        return "/film/{$params['id']}/";
    }
    
    static public function constructPathPerson($params)
    {
        return "/name/{$params['id']}/";
        //return "/level/4/people/{$params['id']}/";
    }

    public static function getMovieIdFromUrl($url)
    {
        if (preg_match('{/film/[^/]*?(\d+)/}', $url, $matches)) {
            return $matches[1];
        } else {
            throw new Lms_Exception("Invalid kinopoisk url: $url");
        }
    }

    public static function getPersonIdFromUrl($url)
    {
        if (preg_match('{/level/4/people/(\d+)}', $url, $matches)
            || preg_match('{/name/(\d+)}', $url, $matches)
        ) {
            return $matches[1];
        } else {
            throw new Lms_Exception("Invalid kinopoisk url: $url");
        }
    }

    static public function parseSearchResults(Zend_Http_Response $response, $url, $testMode = false)
    {
        if (preg_match('{error.kinopoisk.ru}i', $url)) {
            throw new Lms_DataParser_BannedException('You have been banned on kinopoisk.ru! Try change IP.');
        }
        $result = array();
        $result['items'] = array();
        if ($response->isSuccessful()){
            $body = Lms_DataParser::compactTags(Lms_DataParser::utfBodyFromResponse($response));

            if (preg_match('{<link rel="canonical" href="([^"]*/film/\d+/)"}i', $body, $matches)) {
                $result['suburls']['film'] = array('kinopoisk', 'film', Lms_DataParser::absolutize(str_replace("/sr/1", "", $matches[1]), '', self::getHost()));
            } else {
                //echo $body;
                $dirPath = dirname(parse_url($url, PHP_URL_PATH));
                $regExp = '{<a class="all" href="(/level/1/film/\d+/)sr/1/">(.*?)</a>(</b>)?,\xC2\xA0<a href="/level/10/.*?year.*?/\d+/".*?>(.*?)</a></td></tr><tr><td></td><td>.*?<font color="#999999">(.*?)</font>}is';
                if (preg_match_all($regExp, $body, $matches, PREG_SET_ORDER)) {
                   foreach ($matches as $match) {
                        $result['items'][] = array(
                            "names" => array(
                                trim($match[2]), 
                                trim(str_replace("...", "", $match[5]))
                            ),
                            "url" => Lms_DataParser::absolutize($match[1], $dirPath, self::getHost()),
                            "year" => $match[4]
                        );
                    }
                }
                //new design
                $regExp = '{<div[^>]*class="[^"]*element[^"]*">.*?<a href="(?:http://www.kinopoisk.ru)?(/level/1/film/\d+/)sr/1/"[^>]*><img[^>]*(?:src|title)="([^"]*/images/.*?)"[^>]*></a></p><div class="info"><p class="name"><a href="(?:http://www.kinopoisk.ru)?/level/1/film/\d+/sr/1/"[^>]*>(.*?)</a>(?:, )?(?:<span class="year">(.*?)</span>)?</p><span class="gray">([^<]*?)</span>(?P<info>.*?)(?:<span class="num">[^<]*</span></div>|</div></div></div>)}is';
                preg_match_all($regExp, $body, $matches, PREG_SET_ORDER);
                if (!$matches) {
                    $regExp = '{<div[^>]*class="[^"]*element[^"]*">.*?<a href="(/film/[^/]*?\d+/)sr/1/"[^>]*><img[^>]*(?:src|title)="([^"]*/images/.*?)"[^>]*></a></p><div class="info"><p class="name"><a href="/film/[^/]*?\d+/sr/1/"[^>]*>(.*?)</a>(?:, )?(?:<span class="year">(.*?)</span>)?</p><span class="gray">([^<]*?)</span>(?P<info>.*?)(?:<span class="num">[^<]*</span></div>|</div></div></div>)}is';
                    preg_match_all($regExp, $body, $matches, PREG_SET_ORDER);
                }

                foreach ($matches as $match) {
                    $movieId = self::getMovieIdFromUrl($match[1]);
                    $path = self::constructPathFilm(array('id'=>$movieId));
                    preg_match('{^(.*?)</div>}is', $match['info'], $submatches);
                    $info = $submatches[1];
                    preg_match('{<span class="gray">(?P<country>.*?)(?:,\s+)?(?:<i class="director">реж. (?P<director>.*?)</i>)?<br />\((?P<genre>.*?)\)\s*</span><span class="gray">(?P<actors>.*?)</span>}si', $match['info'], $infoMatches);
                    preg_match('{<div class="rating[^>]*>(?P<rating>[\d\.\,\s]+)</div>}i', $match[0], $infoMatches2);
                    $result['items'][] = array(
                        "names" => array(
                            trim(strip_tags($match[3])), 
                            trim(preg_replace('{(,\s+)?\d+ мин}i','',strip_tags($match[5])))
                        ),
                        "url" => Lms_DataParser::absolutize($path, $dirPath, self::getHost()),
                        "image" => Lms_DataParser::absolutize($match[2], $dirPath, self::getHost()),
                        "info" => strip_tags($info),
                        "year" => strip_tags($match[4]),
                        "country" => isset($infoMatches['country'])? trim($infoMatches['country']) : null,
                        "director" => isset($infoMatches['director'])? trim(strip_tags($infoMatches['director'])) : null,
                        "genre" => isset($infoMatches['genre'])? trim($infoMatches['genre']) : null,
                        "actors" => isset($infoMatches['actors'])? trim(strip_tags($infoMatches['actors'])) : null,
                        "rating" => isset($infoMatches2['rating'])? $infoMatches2['rating'] : null,
                    );
                }

                //yandex design
                $regExp = '{<div[^>]*(?:film-snippet_type_movie|film-list_type_serp|film-snippet_type_show)[^>]*>(.*?film-snippet__tags.*?</div>)</div></div>}is';
                preg_match_all($regExp, $body, $matches, PREG_PATTERN_ORDER);
                foreach ($matches[1] as $snippet) {
                    $url = preg_match('{<a[^>]*itemprop="url"[^>]*href="(.*?)">}is', $snippet, $submatches)? $submatches[1] : '-';
                    $movieId = self::getMovieIdFromUrl($url);
                    $path = self::constructPathFilm(array('id'=>$movieId));
                    $image = '';
                    if (preg_match('{<img[^>]*film-snippet__image[^>]*src="(.*?)"}is', $snippet, $submatches)) {
                        $image = Lms_DataParser::absolutize($submatches[1], $dirPath, self::getHost());
                    }

                    $name = preg_match('{<meta itemprop="name" content="(.*?)"}is', $snippet, $submatches)? trim($submatches[1]) : '-';
                    $altName = preg_match('{<meta itemprop="alternateName" content="(.*?)"}is', $snippet, $submatches)? trim($submatches[1]) : '-';
                    $genre = preg_match('{<div[^>]*film-snippet__tags[^>]*>(.*?)</div>}is', $snippet, $submatches)? trim($submatches[1]) : '-';
                    $rating = preg_match('{<div[^>]*rating-button__rating[^>]*>(.*?)</div>}is', $snippet, $submatches)? trim($submatches[1]) : '-';
                    $info = preg_match('{<div[^>]*film-snippet__info[^>]*>(.*?)</div>}is', $snippet, $submatches)? trim($submatches[1]) : '-';
                    $infos = preg_split('{\s*,\s*}', $info);
                    $year = array_pop($infos);
                    $country = join(', ', $infos);
                    $result['items'][] = array(
                        "names" => array($name, $altName),
                        "url" => Lms_DataParser::absolutize($path, $dirPath, self::getHost()),
                        "image" => $image,
                        "info" => $info,
                        "year" => $year,
                        "country" => $country,
                        "genre" => $genre,
                        "rating" =>$rating,
                        "director" => "",
                        "actors" => "",
                    );
                }

            }
        } elseif ($response->isRedirect()) {
            if ($filmLocation = $response->getHeader('Location')) {
                if (preg_match('{error.kinopoisk.ru}i', $filmLocation)) {
                    throw new Lms_DataParser_BannedException('You have been banned on kinopoisk.ru! Try change IP.');
                }
                $result['suburls']['film'] = array('kinopoisk', 'film', Lms_DataParser::absolutize(str_replace("/sr/1", "", $filmLocation), '', self::getHost()));
            }
        } else {
            throw new Lms_DataParser_Exception("Error while parse $url : server returned " .  $response->getStatus());
        }
        $result['version'] = self::getVersion();
        if ($testMode) {
            if (isset($result['items']) && is_array($result['items'])) {
                array_splice($result['items'], 2);
            }
        }

        return $result;
    }

    static private function parseLine($type, $body)
    {
        if (preg_match('{<td class="film-info__type">' . $type .  '</td><td class="film-info__value">(.*?)</td>}is', $body, $matches)) {
            return trim(strip_tags($matches[1]), " \t\r\n\xC2\xA0");
        } else {
            return '';
        }
    }

    static public function parseFilm(Zend_Http_Response $response, $url, $testMode = false)
    {
        if (preg_match('{error.kinopoisk.ru}i', $url)) {
            throw new Lms_DataParser_BannedException('You have been banned on kinopoisk.ru! Try change IP.');
        }
        $result = array();
        if ($response->isSuccessful()){
            $body = Lms_DataParser::compactTags(Lms_DataParser::utfBodyFromResponse($response));
            //echo $body;
            $result['kinopoisk_id'] = self::getMovieIdFromUrl($url);
            $dirPath = dirname(parse_url($url, PHP_URL_PATH));

            if (preg_match('{<div class="film-header__movie-type">Сериал</div>}i', $body)) {
                $result["type"] = "series";
            } 

            if (preg_match('{<h1 class="moviename-big" itemprop="name">(.*?)</h1>(?:<span itemprop="alternativeHeadline">(.*?)</span>)}is', $body, $matches)
                || preg_match("/<H1.*?class=\"moviename-big\".*?>(.*?)<\/H1><\/td><\/tr>.*?<tr>.*?<td>.*?<table.*?>.*?<tr>.*?<td[^>]*?>.*?<span.*?>(.*?)<\/span>/is", $body, $matches)
            ){
                if (preg_match('{\(сериал}i', $matches[1])) {
                    $result["type"] = "series";
                } 
                $n1 = trim(preg_replace("/(?:\xC2\xA0|\s)+\(.*?\)/i","",strip_tags($matches[1])));
                $n1 = trim(str_replace("\xC2\xA0"," ",strip_tags($n1)));
                $n2 = trim(str_replace("\xC2\xA0"," ",strip_tags($matches[2])));
                if ($n1)  $result["names"][] = $n1;
                if ($n2)  $result["names"][] = $n2;
            } else if (preg_match('{<div[^>]*itemprop="name">(.*?)</div>}is', $body, $matches)){
                //yandex design
                $result["names"][] = trim(strip_tags($matches[1]));
                if (preg_match('{<div[^>]*itemprop="alternateName">(.*?)</div>}is', $body, $matches)) {
                    $result["names"][] = trim(strip_tags($matches[1]));
                }
                $result["names"] = array_unique($result["names"]);
            } else {
                throw new Lms_DataParser_ParseException('Error parse page');
            }


            $result["posters"] = array();
            if (preg_match('{<td.*?valign="top">(<a.*?>)?<img src="([^"]*/images/.*?)".*?border=0 alt=".*?" style="[^"]*"\s*/?>(</a>)?<br\s*/?><br\s*/?>}i', $body, $matches)
                || preg_match('{<td.*?valign="top">.*?(<a.*?>)?<img[^>]*?src="([^"]*/images/film/.*?)"[^>]*?>(</a>)?<br\s*/?><br\s*/?>}i', $body, $matches)
                || preg_match('{<td.*?valign="top">.*?(<a.*?>)?<img[^>]*?src="([^"]*/images/film/.*?)"[^>]*?>(</a>)?</div>}i', $body, $matches)
                || preg_match('{</span><a[^>]*?><img[^>]*?src="(([^"]*?))"[^>]*?itemprop="image"}i', $body, $matches)
            ) {
                $result["posters"][] = Lms_DataParser::absolutize($matches[2], $dirPath, self::getHost());
                if (preg_match('{openImgPopup\(\'([^\']*?)\'\);}i', $matches[0], $submatches)) {
                    $result["posters_big"] = array(Lms_DataParser::absolutize($submatches[1], $dirPath, self::getHost()));
                }
            } else if (preg_match('{<img class="image image_picture film-meta__image[^>]*srcset="[^>]*, ([^>]*) 2x}i', $body, $matches)) {
                $result["posters"][] = Lms_DataParser::absolutize($matches[1], $dirPath, self::getHost());
            }

            if (preg_match('{год</td><td[^>]*?>(?:<div[^>]*?>)?<a href=".*?year.*?">(\d+?)</a>}i', $body, $matches)
                || preg_match('{год</b><i><a href=\"/level/10/m_act%5Byear%5D/\d+/\">(\d+)</a>(.*?<a.*?>.*?</a>)?</i><u></u></li>}i', $body, $matches)
            ) {
                $result["year"] = trim(strip_tags($matches[1]));
            } elseif ($year = self::parseLine('Год производства', $body)) {
                $result["year"] = $year;
            }

            $result["countries"] = array();
            if (preg_match("{страна</td><td[^>]*?>(.*?)</td></tr>}i", $body, $matches)
                || preg_match("{страна</b><i>(.*?)</i><u></u></li>}i", $body, $matches)
            ) {
                $countries = explode(", ", preg_replace("/(\(.*?\))/i","",strip_tags($matches[1])));
                foreach($countries as $country){
                    $result["countries"][] = $country;
                }
            } elseif ($value = self::parseLine('Страна', $body)) {
                $countries = explode(", ", $value);
                foreach($countries as $country){
                    $result["countries"][] = trim($country);
                }
            }
            

            
            if (preg_match("/жанр<\/td><td[^>]*?>(.*?)<\/td><\/tr>/i", $body, $matches)
                || preg_match("{жанр</b><i>(.*?)</i><u></u></li>}i", $body, $matches)
            ) {
                $matches[1] = preg_replace('{<a[^>]*?href="/film/[^/]+/keywords/"[^>]*?>[^<]*?</a>}i', '', $matches[1]);
                $genres = explode(",", strip_tags($matches[1]));
                $result["genres"] = array();
                foreach($genres as $genre){
                    if ($genre && preg_match('{[a-zA-Zа-яА-Я]+}i', $genre)) $result["genres"][] = trim($genre);
                }
            } else if (preg_match_all('{<a[^>]*button_movie-tag[^>]*/catalogue/\?genres[^>]*>(.*?)</a>}is', $body, $matches, PREG_PATTERN_ORDER)) {
                foreach ($matches[1] as $match) {
                    $result["genres"][] = trim(strip_tags($match));
                }
            }


            if (preg_match("/слоган<\/td><td[^>]*?>(.*?)<\/td><\/tr>/i", $body, $matches)
                || preg_match("{слоган</b><i>(.*?)</i><u></u></li>}i", $body, $matches)
            ) {
                $result["tagline"] = trim(strip_tags($matches[1]),"\t «»");
                if ($result["tagline"]=='-') {
                    unset($result["tagline"]);
                }
            }

            if ($value = self::parseLine('Слоган', $body)) {
                $result["tagline"] = trim(strip_tags($value),"\t «»");
                if ($result["tagline"]=='-') {
                    unset($result["tagline"]);
                }
            }

            if (preg_match("/бюджет<\/td><td[^>]*?>(.*?)<\/td><\/tr>/si", $body, $matches)
                || preg_match("{бюджет</b><i>(.*?)</i><u></u></li>}si", $body, $matches)
            ) {
                $result["budget"] = trim(strip_tags(preg_replace('{<script.*?</script>}is', '', $matches[1])), "\r\n \xC2\xA0");
            } else if ($value = self::parseLine('Бюджет', $body)) {
                $result["budget"] = trim($value);
            }


            if (preg_match("/сборы в США<\/td><td[^>]*?>(.*?)<\/td><\/tr>/si", $body, $matches)
                || preg_match("{сборы в США</b><i>(.*?)</i><u></u></li>}si", $body, $matches)
            ) {
                $result["gross"]["usa"] = trim(strip_tags(preg_replace('{(<script.*?</script>|сборы)}is', '', $matches[1])), "\r\n \xC2\xA0");
            }
            if (preg_match("/сборы в России<\/td><td[^>]*?>(.*?)<\/td><\/tr>/si", $body, $matches)
                || preg_match("{сборы в России</b><i>(.*?)</i><u></u></li>}si", $body, $matches)
            ) {
                $result["gross"]["russia"] = trim(strip_tags(preg_replace('{(<script.*?</script>|сборы)}is', '', $matches[1])), "\r\n \xC2\xA0");
            }
            if (preg_match("/сборы в мире<\/td><td[^>]*?>(.*?)<\/td><\/tr>/si", $body, $matches)
                || preg_match("{сборы в мире</b><i>(.*?)</i><u></u></li>}si", $body, $matches)
            ) {
                $result["gross"]["worldwide"] = trim(preg_replace('{\+.*?=}', '', strip_tags(preg_replace('{(<script.*?</script>|<a[^>]*>сборы</a>)}is', '', $matches[1]))), "\r\n \xC2\xA0");
            }

            $regions = array(
                'США' => 'usa',
                'Россия' => 'russia',
            );
            if (preg_match_all('{<span class="film-info__box-office">(?:<span.*?title="(.*?)".*?</span>)?(.*?)</span>}is', $body, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $region = 'worldwide';
                    if (isset($match[1])) {
                        $r = $match[1];
                        if (isset($regions[$r])) {
                            $region = $regions[$r];
                        } 
                    }
                    $result["gross"][$region] = trim(strip_tags($match[2]));
                }
            }


            if (preg_match("/MPAA<\/td><td[^>]*?>(.*?)<\/td><\/tr>/is", $body, $matches)
                || preg_match("{MPAA</b><i>(.*?)</i><u></u></li>}is", $body, $matches)
            ) {
                if (preg_match('{<a[^>]*/rn/(.*?)/[^>]*>}', $matches[1], $matches2)) {
                    $result["mpaa"] = $matches2[1];
                }
            }

            if (preg_match('{<div class="ageLimit age(\d+)">}', $body, $matches)) {
                    $result["age_limit"] = $matches[1];
            }


            if (preg_match("/время<\/td><td[^>]*?>(\d+).*?<\/td><\/tr>/si", $body, $matches)
                || preg_match("{время</b><i>(\d+).*?</i><u></u></li>}si", $body, $matches)
            ) {
                $result["duration"] = trim(strip_tags($matches[1]));
            } else if ($value = self::parseLine('Время', $body)) {
                if (preg_match('{(?:(?P<h>\d+)\s*ч.*?)?(?P<m>\d+)\s*м}is', $value, $matches)) {
                    $d = $matches['m'] + intval($matches['h']) * 60;
                    $result["duration"] = $d;
                }
            }

            if ($value = self::parseLine('Возраст', $body)) {
                $result["age_rating"] = trim($value);
            }


            $persones = array();
            if (preg_match_all("/(режисcер|режиссер|сценарий|продюсер|оператор|композитор|художник|монтаж)<\/td><td[^>]*?>(.*?)<\/td><\/tr>/i", $body, $amatches, PREG_SET_ORDER)
                || preg_match_all("{(режисcер|режиссер|сценарий|продюсер|оператор|композитор|художник|монтаж)</b><i>(.*?)</i><u></u></li>}i", $body, $amatches, PREG_SET_ORDER)
            ) {
                foreach($amatches as $matches){
                    $tmp = explode(",",$matches[2]);
                    foreach ($tmp as $value){
                        if (preg_match("/href=\"(.*?)\"/i",$value,$matches2)) {
                            $url = Lms_DataParser::absolutize($matches2[1], $dirPath, self::getHost());
                            $url = str_replace('/level/4/people', '/name', $url);
                        }
                        $value = trim(strip_tags($value));
                        if (!in_array(trim($value),array("-","..."))) $persones[] = array("url"=>$url,"names"=>array($value),"role"=>$matches[1]);
                    }
                }
            }
            $body = preg_replace('{Роли дублировали:.*?(/актеры фильма|</ul>)}si','',$body);
            
            
            $personesIndex = array();
            if (preg_match_all('{<li[^>]*><a href=\"([^"]*?/name/[^"]*?)"[^>]*>([^<]*?)</a></li>}i', $body, $matches, PREG_SET_ORDER)
                || preg_match_all('{<tr><td[^>]*?align=right><a href=\"(.*?people.*?)".*?>(.*?)</a></td></tr>}i', $body, $matches, PREG_SET_ORDER)
                || preg_match_all('{<span[^>]*><a href=\"([^"]*?people[^"]*?)"[^>]*>([^<]*?)</a></span>}i', $body, $matches, PREG_SET_ORDER)
                || preg_match_all('{<span[^>]*><a href=\"([^"]*?/name/[^"]*?)"[^>]*>([^<]*?)</a></span>}i', $body, $matches, PREG_SET_ORDER)
            ) {
                foreach ($matches as $value){
                    $value[2] = trim($value[2]);
                    $url = Lms_DataParser::absolutize($value[1], $dirPath, self::getHost());
                    if (isset($personesIndex[$url])) {
                        continue;
                    }
                    $persones[] = array("url"=>$url,"names"=>array($value[2]),"role"=>"актер");
                    $personesIndex[$url] = true;
                }
            }
            
            $personesBlocks = array(
                'Режиссёр' => 'режиссер',
                'Сценарист' => 'сценарий',
                'Продюсер' => 'продюсер',
                'Оператор' => 'оператор',
                'Композитор' => 'композитор',
                'Художник' => 'художник',
                'Монтажёр' => 'монтаж',
                'Актёр' => 'актер',
            );
            $personesIndex = array();
            foreach ($personesBlocks as $title => $roleName) {
                if (preg_match_all('{<div class="kinoisland.*?kinoisland__title">' . $title . '</div>(.*?)</div></div></div></div>}is', $body, $matches, PREG_PATTERN_ORDER)) {
                    foreach ($matches[1] as $block) {
                        preg_match_all('{<a[^>]*itemprop="name"[^>]*href="(.*?)">(.*?)</a>}is', $block, $submatches, PREG_SET_ORDER);
                        foreach ($submatches as $submatch) {
                            $url = Lms_DataParser::absolutize($submatch[1], $dirPath, self::getHost());
                            if (isset($personesIndex[$roleName][$url])) {
                                continue;
                            }
                            if (isset($personesIndex[$roleName]) && count($personesIndex[$roleName])>=3) {
                                break;
                            }
                            $persones[] = array("url" => $url, "names" => array($submatch[2]), "role" => $roleName);
                            $personesIndex[$roleName][$url] = true;
                        }
                    }
                }
            }

            if (preg_match_all('{<div[^>]*actors-table__actor.*?<a[^>]*itemprop="name"[^>]*href="(.*?)">(.*?)</a>}is', $body, $matches, PREG_SET_ORDER)) {
                $roleName = 'актер';
                foreach ($matches as $match) {
                    $url = Lms_DataParser::absolutize($match[1], $dirPath, self::getHost());
                    if (isset($personesIndex[$roleName][$url]) || (isset($personesIndex[$roleName]) && count($personesIndex[$roleName])>=10)) {
                        continue;
                    }
                    $persones[] = array("url" => $url, "names" => array($match[2]), "role" => $roleName);
                    $personesIndex[$roleName][$url] = true;
                }
            }

            $roles = array(
                'movie-directors__person' => 'режиссер',
                'movie-actors__person' => 'актер'
            );
            foreach ($roles as $code => $roleName) {
                if (preg_match_all('{<div class="person ' . $code .'[^>]*>.*?<a[^>]*itemprop="name"[^>]*href="(.*?)">(.*?)</a></div>}is', $body, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $value) {
                        $url = Lms_DataParser::absolutize($value[1], $dirPath, self::getHost());
                        if (isset($personesIndex[$roleName][$url])) {
                            continue;
                        }
                        $persones[] = array("url" => $url,"names"=>array($value[2]), "role"=>$roleName);
                        $personesIndex[$roleName][$url] = true;
                    } 
                }
            }

            
            $result["persones"] = $persones;
             
            $result["description"] = '';
            if (preg_match("/view_sinopsys\/ok/i", $body, $matches)){
                //$result['suburls']['film'] = array('kinopoisk', 'sinopsys', Lms_DataParser::absolutize($url . "view_sinopsys/ok/", $dirPath, self::getHost()));
            } else if (preg_match("{<table cellspacing=0 cellpadding=0 border=0 width=100%>.*?<td colspan=3 style=\"padding:10px;padding-left:20px;\" class=\"news\">(.*?)</td></tr>}is", $body, $matches)) {
                $result["description"] = trim(strip_tags($matches[1], '<p><br>'));
            } else if (preg_match('{<table cellspacing="0" cellpadding="0" border="0" width="100%">(?:<!-- -->)?<tr><td colspan=3 style="padding[^"]*" class="news">(.*?)</td></tr>}is', $body, $matches)) {
                $result["description"] = trim(strip_tags($matches[1], '<p><br>'));
            } else if (preg_match('{<table width=100% cellpadding=0 cellspacing=0>.*?<td colspan=3 style="padding[^"]*" class="news">(.*?)</td></tr>}is', $body, $matches)) {
                $result["description"] = trim(strip_tags($matches[1], '<p><br>'));
            } else if (preg_match('{<table[^>]*width="100%"[^>]*>.*?<td colspan=3 style="padding[^"]*" class="news">(.*?)</td></tr>}is', $body, $matches)) {
                $result["description"] = trim(strip_tags($matches[1], '<p><br>'));
            } else if (preg_match('{<div class="kinoisland__content" itemprop="description">(.*?)</div>}is', $body, $matches)) {
                $result["description"] = trim(strip_tags($matches[1], '<p><br>'));
            }

            if (preg_match('{<a href="/level/83/film/\d+/"[^>]*?>([\d\.\,]+)</a><span[^>]*?>.*?\(([\d\xC2\xA0\s]+)\).*?</div>}i', $body, $matches)
                || preg_match('{<a href="/level/83/film/\d+/"[^>]*?>(?:<span[^>]*?>)?([\d\.\,]+)(?:</span>)?<span[^>]*?>[^<]*?([\d\xC2\xA0\s]+)</span></a>}i', $body, $matches)
                || preg_match('{<a href="/film/\d+/votes/"[^>]*?>(?:<span[^>]*?>)?([\d\.\,]+)(?:</span>)?<span[^>]*?>[^<]*?([\d\xC2\xA0\s]+)</span>}i', $body, $matches)
            ) {
                $result["rating"]['kinopoisk']['value'] = floatval($matches[1]);
                $result["rating"]['kinopoisk']['count'] = (int) preg_replace('{(\xC2|\xA0|\s)}i','', $matches[2]);
            }
            if (preg_match('{<div[^>]*?>IMDB: ([\d\.\,]+) \(([\d\s]+)\)</div>}i', $body, $matches)) {
                $result["rating"]['imdb']['value'] = floatval($matches[1]);
                $result["rating"]['imdb']['count'] = (int)preg_replace('{[^\d]}', '', $matches[2]);
            }
            if (preg_match('{<div class="rating-button__rating" itemprop="ratingValue">(.*?)</div>.*?<meta itemprop="reviewCount" content="(.*?)"}is', $body, $matches)
                || preg_match('{<span class="rating_ball">(.*?)</span>.*?<meta itemprop="ratingCount" content="(.*?)"}is', $body, $matches)
            ) {
                $result["rating"]['kinopoisk']['value'] = floatval($matches[1]);
                $result["rating"]['kinopoisk']['count'] = $matches[2];
            }
            if ($value = self::parseLine('IMDb', $body)) {
                $result["rating"]['imdb']['value'] = floatval($value);
            }
            if (preg_match('{<div class="film-meta-data-item__title">IMDb</div><div class="film-meta-data-item__total">(.*?)оц}i', $body, $matches)) {
                $result["rating"]['imdb']['count'] = intval(preg_replace('{\s}', '', strip_tags($value)));
            }


            if (preg_match('{(?:getTrailer|getFlashPlayerPromo)\("[^"]*?"\s*,\s*"([^"]*?)"\s*,\s*([^,]*?)\s*,\s*"(\d+)"\s*,\s*"(\d+)"\s*(,\s*"([^"]*?)")?}i', $body, $matches)) {
                $domain = (isset($matches[6]) && $matches[6])? $matches[6] : 'tr';
                $result['trailer']['video'] = "http://{$domain}.kinopoisk.ru/trailers/flv/" . $matches[1];
                $p = trim($matches[2], '"\"');
                if ($p) {
                    $result['trailer']['preview'] = "http://{$domain}.kinopoisk.ru/trailers/flv/$p";
                }
                $result['trailer']['width'] = $matches[3];
                $result['trailer']['height'] = $matches[4];
                if (preg_match('{<a href="/level/16/film/\d+/t/[^"]*"[^>]*>(.*?)</a>}', $body, $matches)) {
                    $result['trailer']['name'] = trim($matches[1]);
                }
                if (preg_match('{div[^>]*id="tr_preview"[^>]*background: url\((.*?)\)}i', $body, $matches)) {
                    $result['trailer']['preview'] = trim($matches[1]);
                }
            }


            if (preg_match('{GetTrailerPreview\((\{.*?\})\);}si', $body, $matches)) {
                $matches[1] = preg_replace('{//.*?$}im', '', $matches[1]);
                $matches[1] = preg_replace('{Math\.max\((\d+), \d+\)}i', '"$1"', $matches[1]);
                $trailerParams = Zend_Json::decode($matches[1]);
                $dom = $trailerParams['trailerDom']? $trailerParams['trailerDom'] . '.kinopoisk.ru' : 'kp.cdn.yandex.net';
                if (preg_match('{function getTrailersDomain\(\)\{return \'([^\']+)\'}i', $body, $m)) {
                    $dom = $m[1];
                }

                $result['trailer']['video'] = "http://$dom/" . $trailerParams['trailerFile'];
                $result['trailer']['preview'] = "http://$dom/" . $trailerParams['previewFile'];
                $result['trailer']['width'] = $trailerParams['trailerW'];
                $result['trailer']['height'] = $trailerParams['trailerH'];
                if (preg_match('{<a href="/level/16/film/\d+/t/[^"]*"[^>]*>(.*?)</a>}', $body, $matches)
                    || preg_match('{<a href="/film/\d+/video/t/[^"]*"[^>]*>(.*?)</a>}', $body, $matches)
                    || preg_match('{<a href="/film/\d+/video/\d+/?"[^>]*>(.*?)</a>}', $body, $matches)
                ) {
                    $result['trailer']['name'] = trim($matches[1]);
                }
            }
        } else {
            throw new Lms_DataParser_Exception("Error while parse $url : server returned " .  $response->getStatus());
        }
        $result['version'] = self::getVersion();
        
        if ($testMode) {
            if (isset($result['rating']['kinopoisk']['value'])) {
                Lms_DataParser::testRange($result['rating']['kinopoisk']['value'], 1, 10);
            }
            if (isset($result['rating']['imdb']['value'])) {
                Lms_DataParser::testRange($result['rating']['imdb']['value'], 1, 10);
            }
            if (isset($result['rating']['kinopoisk']['count'])) {
                Lms_DataParser::testRange($result['rating']['kinopoisk']['count'], 1, 10000000);
            }
            if (isset($result['rating']['imdb']['count'])) {
                Lms_DataParser::testRange($result['rating']['imdb']['count'], 1, 10000000);
            }
            if (isset($result['gross']['usa'])) {
                $result['gross']['usa'] = preg_replace('{\d}', '9', $result['gross']['usa']);
            }
            if (isset($result['gross']['russia'])) {
                $result['gross']['russia'] = preg_replace('{\d}', '9', $result['gross']['russia']);
            }
            if (isset($result['gross']['worldwide'])) {
                $result['gross']['worldwide'] = preg_replace('{\d}', '9', $result['gross']['worldwide']);
            }
        }
        return $result;
    }

    static public function parsePerson(Zend_Http_Response $response, $url, $testMode = false)
    {
        if (preg_match('{error.kinopoisk.ru}i', $url)) {
            throw new Lms_DataParser_BannedException('You have been banned on kinopoisk.ru! Try change IP.');
        }
        $result = array();
        if ($response->isSuccessful()){
            $body = Lms_DataParser::compactTags(Lms_DataParser::utfBodyFromResponse($response));
            //echo $body;
            $result['kinopoisk_id'] = self::getPersonIdFromUrl($url);

            $dirPath = dirname(parse_url($url, PHP_URL_PATH));
            $result["names"] = array();
            if (preg_match("/<H1.*?class=\"moviename-big\"[^>]*>(.*?)<\/H1>/is", $body, $matches)){
                $matches[1] = str_replace("\xC2\xA0", " ", $matches[1]);
                $matches[1] = trim(strip_tags($matches[1]));
                if ($matches[1]) {
                    $result["names"][] = $matches[1];
                }
            }
            if (preg_match('{<span class="person-header__name" itemprop="name">(.*?)</span>}is', $body, $matches)){
                $result["names"][] = trim(strip_tags($matches[1]));
            }
            if (preg_match('{<span class="person-header__original-name">(.*?)</span>}is', $body, $matches)){
                $result["names"][] = trim(strip_tags($matches[1]));
            }

            if (preg_match('{tr><td><H1.*?class="moviename-big"[^>]*>.*?</H1></td></tr><tr><td><table.*?border=0><tr><td><span style="font-size:13px;color:#666">(.*?)</span></td>}i', $body, $matches)
                || preg_match('{</h1><span itemprop="alternativeHeadline">(.*?)</span>}is', $body, $matches)
                || preg_match('{</h1><span itemprop="alternateName">(.*?)</span>}is', $body, $matches)
            ){
                $matches[1] = str_replace("\xC2\xA0", " ", $matches[1]);
                $matches[1] = trim(strip_tags($matches[1]));
                if ($matches[1]) {
                    $result["names"][] = $matches[1];
                }
            }
            
            if (preg_match("/дата рождения<\/td>.*?<td class=\"desc-data\".*?>.*?<table cellpaddin=0 cellspacing=0>.*?<tr>.*?<td>(.*?)<\/td>/is", $body, $matches)
                || preg_match("{дата рождения</b><i>(.*?)</i><u></u></li>}i", $body, $matches)
                || preg_match("{дата рождения</td><td[^>]*?>(.*?)</td></tr>}i", $body, $matches)
                || preg_match('{Дата рождения</td><td class="person-header__info-value">(.*?)</td></tr>}is', $body, $matches)
            ){
                $matches[1] = preg_replace('{(\|\().*?$}', '', $matches[1]);
//                list($matches[1]) = explode("|",$matches[1]);
                $matches[1] = trim(strip_tags($matches[1]));
                if (preg_match('{(?:(\d+)\s+(.+),\s+)?(\d{4})}i', $matches[1], $dateMatches)) {
                    $day = trim($dateMatches[1]); 
                    $strMonth = trim($dateMatches[2]); 
                    $year = trim($dateMatches[3]); 
                    $months = array(
                        'января' => 1,
                        'февраля' => 2,
                        'марта' => 3,
                        'апреля' => 4,
                        'мая' => 5,
                        'июня' => 6,
                        'июля' => 7,
                        'августа' => 8,
                        'сентября' => 9,
                        'октября' => 10,
                        'ноября' => 11,
                        'декабря' => 12
                    );
                    $month = isset($months[$strMonth])? $months[$strMonth] : null;
                    if ($year) {
                        $result["born_date"] = $year;
                    }
                    if ($year && $month && $day) {
                        $datearray = array('year' => $year, 'month' => $month, 'day' => $day);
                        $bornDate = new Zend_Date($datearray);
                        $result["born_date"] = $bornDate->toString('yyyy-MM-dd');
                    }
                }
                
            }
        
            if (preg_match("/место рождения<\/td><td class=\"desc-data\">(.*?)<\/td><\/tr>/i", $body, $matches)
                || preg_match("{место рождения</b><i>(.*?)</i><u></u></li>}i", $body, $matches)
                || preg_match("{место рождения</td><td[^>]*>(.*?)</td></tr>}i", $body, $matches)
                || preg_match('{Место рождения</td><td class="person-header__info-value">(.*?)</td></tr>}i', $body, $matches)
            ) {
                $matches[1] = trim(strip_tags($matches[1]));
                if ($matches[1]!='-') $result["born_place"] = $matches[1];
            }
        
            if (preg_match("/карьера<\/td><td class=\"desc-data\">(.*?)<\/td><\/tr>/i", $body, $matches)
                || preg_match("{карьера</b><i>(.*?)</i><u></u></li>}i", $body, $matches)
                || preg_match("{карьера</td><td[^>]*>(.*?)</td></tr>}is", $body, $matches)
                || preg_match('{Карьера</td><td class="person-header__info-value">(.*?)</td></tr>}i', $body, $matches)
            ) {
                $matches[1] = trim(strip_tags($matches[1]));
                $result["profile"] = $matches[1];
            }
        
            if (preg_match("/<H2 class=\"chapter\">Биография<\/H2><\/td><\/tr>.*?<tr><td colspan=3 style=\"padding-left:20px\" class=news>(.*?)<\/td><\/tr>/is", $body, $matches)){
                $result["about"] = trim($matches[1]);
            }            
                    
            $result["photos"] = array();
            if (preg_match("/<img[^>]*? src='([^']*\/images\/actor\/.*?)' width=\"\d+\" height=\"\d+\".*?>/i", $body, $matches)
                || preg_match('{<img[^>]*?src=\'([^\']*/images/actor/[^\']*)\'[^>]*>}i', $body, $matches)
                || preg_match('{<img[^>]*?src="([^\']*/images/actor[^\"]*)"[^>]*>}i', $body, $matches)
                || preg_match('{<div class="person-header__image"><img[^>]*srcset="[^>]*, (.*?) 2x}i', $body, $matches)
            ) {
                $imgurl = Lms_DataParser::absolutize($matches[1], $dirPath, self::getHost());
                $result["photos"][] = $imgurl;
            }
            
        } else {
            throw new Lms_DataParser_Exception("Error while parse $url : server returned " .  $response->getStatus());
        }
        $result['version'] = self::getVersion();
        
        if ($testMode) {
            if (isset($result['profile'])) {
                $parts = explode(', ', $result['profile']);
                sort($parts);
                $result['profile'] = implode(', ', $parts);
            }
        }
        return $result;
    }
}