<?php
/**
 * LMS Library
 * 
 * @version $Id: Imdb.php 78 2010-10-23 08:11:19Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package DataParser
 */

class Lms_DataParser_Imdb extends Lms_DataParser_Generic{
    
    static private $version = '$Id$';
    static private $host = 'www.imdb.com';
    
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
        return "/find?q=".urlencode($params['name'])."&s=tt";
    }
    
    static public function constructPathFilm($params)
    {
        return "/title/tt" . sprintf('%07d', $params['id']) . "/";
    }
    
    static public function constructPathPerson($params)
    {
        return "/name/nm" . sprintf('%07d', $params['id']) . "/";
    }

    static public function parseSearchResults(Zend_Http_Response $response, $url, $testMode = false)
    {
        $result = array();
        if ($response->isSuccessful()) {
            $body = Lms_DataParser::compactTags(Lms_DataParser::utfBodyFromResponse($response));
//echo $body;
            if (preg_match('{<link rel="canonical" href="([^"]*/title/tt\d+/)"}i', $body, $matches)) {
                $result['suburls']['film'] = array('imdb', 'film', Lms_DataParser::absolutize($matches[1], '', self::getHost()));
            } else {
                $dirPath = dirname(parse_url($url, PHP_URL_PATH));
                //<tr class="findResult odd"><td class="primary_photo"><a href="/title/tt0133093/?ref_=fn_al_tt_1" ><img src="http://ia.media-imdb.com/images/M/MV5BMjEzNjg1NTg2NV5BMl5BanBnXkFtZTYwNjY3MzQ5._V1_SX32_CR0,0,32,44_.jpg" height="44" width="32" /></a></td><td class="result_text"><a href="/title/tt0133093/?ref_=fn_al_tt_1" >The Matrix</a> (1999) </td></tr>
                //<tr class="findResult odd"><td class="primary_photo"><a href="/title/tt0133093/?ref_=fn_tt_tt_3" ><img src="http://ia.media-imdb.com/images/M/MV5BMjEzNjg1NTg2NV5BMl5BanBnXkFtZTYwNjY3MzQ5._V1_SX32_CR0,0,32,44_.jpg" height="44" width="32" /></a></td><td class="result_text"><a href="/title/tt0133093/?ref_=fn_tt_tt_3">The Matrix</a> (1999) </td></tr>
                if (preg_match_all('{<tr class="findResult[^"]*"><td class="primary_photo"><a href="/title/tt\d+[^"]*"[^>]*><img src="(http[^"]*)"[^>]*/></a></td><td class="result_text"><a href="(/title/tt\d+/)[^"]*"[^>]*>(.*?)</a>.*?(?:\s*\((\d+)\)\s*).*?</td></tr>}i', $body, $data, PREG_SET_ORDER)) {
                    foreach ($data as $row) {
                        $year = isset($row[4])? $row[4] : "";
                        $url = $row[2];
                        $image = $row[1];
                        $result['items'][] = array(
                            "names" => array($row[3]),
                            "year" => $year,
                            "section" => '',
                            "url" => Lms_DataParser::absolutize($url, '', self::getHost()),
                            "image" => $image
                        );
                    }
                }
                    
                $sortarray = array();
                $p = strpos($body,"Popular Titles");
                if ($p!==false) {
                    $ofs[] = array("section" => "Popular","offset" => $p);
                    $sortarray[] = $p;
                }
        
                $p = strpos($body, "Titles (Exact Matches)");
                if ($p!==false) {
                    $ofs[] = array("section" => "Exact","offset" => $p);
                    $sortarray[] = $p;
                }
        
                $p = strpos($body, "Titles (Partial Matches)");
                if ($p!==false) {
                    $ofs[] = array("section" => "Partial","offset" => $p);
                    $sortarray[] = $p;
                }
    
                $p = strpos($body, "Titles (Approx Matches)");
                if ($p!==false) {
                    $ofs[] = array("section" => "Approx","offset" => $p);
                    $sortarray[] = $p;
                }
                $ofs[]["offset"] = strlen($body);
                $sortarray[] = strlen($body);
        
                array_multisort($ofs, SORT_NUMERIC, $sortarray, SORT_NUMERIC);

                preg_match_all('{<a[^>]*href="/title(?:\?|/tt)(\d+)/"[^>]*><img[^>]*src="(http[^"]*?)"}i', $body, $data, PREG_SET_ORDER);
                $thumbNails = array();
                foreach ($data as $row) {
                    $imdbID = $row[1]; 
                    $thumbNails[$imdbID] = $row[2];
                }

                for($i=0; $i<count($ofs)-1; $i++){
                    $subcontents = substr($body, $ofs[$i]["offset"], $ofs[$i+1]["offset"]-$ofs[$i]["offset"]);
                    preg_match_all('/<A HREF="\/title(\?|\/tt)(\d+)\/.*?">([^<]+?)<\/A>(\s+(\(.+?\)))?/i', $subcontents, $data, PREG_SET_ORDER);
                    foreach ($data as $row) {
                        $year = isset($row[5])? str_replace(array('(', ')'), array('', ''), $row[5]) : "";
                        $imdbID = $row[2]; 
                        $image = isset($thumbNails[$imdbID])? $thumbNails[$imdbID] : '';
                        $result['items'][] = array(
                            "names" => array($row[3]),
                            "year" => $year,
                            "section" => $ofs[$i]["section"],
                            "url" => Lms_DataParser::absolutize("/title/tt".$imdbID."/", '', self::getHost()),
                            "image" => $image
                        );
                    }
                }
            }
        } elseif ($response->isRedirect()) {
            if ($filmLocation = $response->getHeader('Location')) {
                $filmLocation = rtrim($filmLocation, '/') . '/';
                $result['suburls']['film'] = array('imdb', 'film', Lms_DataParser::absolutize($filmLocation, '', self::getHost()));
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
    
    static public function parseFilm(Zend_Http_Response $response, $url, $testMode = false)
    {
        $result = array();
        if ($response->isSuccessful()){
            $body = Lms_DataParser::compactTags(Lms_DataParser::utfBodyFromResponse($response));
            //echo $body;
            $dirPath = dirname(parse_url($url, PHP_URL_PATH));
            $ary = array();    // temp
            
            //Imdb ID
            if (preg_match("/tt0*(\d+)/", $url, $matches)) {
                $result['imdb_id'] = $matches[1];
            } else {
                $result['imdb_id'] = null;
            }
                    
            // Titles
            if (preg_match('{<span class="title-extra">(.*?)<i>\(original title\)</i></span>}is', $body, $matches)) {
                $result["names"][] = trim($matches[1]);
            } else if (preg_match('/<TITLE>(.*?)\s+\([1-2][0-9][0-9][0-9].*?\)<\/TITLE>/i', $body, $ary)) {
                @list($t, $s)      = split(' - ', trim($ary[1]), 2);
                if ($t!='IMDb') $result["names"][] = $t;
                if ($s!='IMDb') $result["names"][] = $s;
            } else if (preg_match('{<h1 class="header"[^>]*>(.*?)</}is', $body, $matches)
                || preg_match('{<h1 itemprop="name"[^>]*>(.*?)<}is', $body, $matches)
                || preg_match('{<div class="title_wrapper"><h[^>]*>(.*?)<}is', $body, $matches)
            ) {
                $result["names"][] = trim(strip_tags($matches[1]));
            }

            // Year
            if (preg_match('/<A HREF="\/Sections\/Years\/[1-2][0-9][0-9][0-9]\/?">([1-2][0-9][0-9][0-9])<\/A>/i', $body, $ary)
                || preg_match('{<a href="/year/[1-2][0-9][0-9][0-9]/?">([1-2][0-9][0-9][0-9])</a>}i', $body, $ary)
                || preg_match('{<span class="tv-series-smaller">\(TV\s*(?:Series)?\s*(\d+)}i', $body, $ary)
                || preg_match('{<span>\([^\)]*?(\d+)[^<]*?</span>\s*</h1>}is', $body, $ary)
                || preg_match('{<span class="nobr">\s*\([^\d\)]*(\d+).*?</span></h1>}is', $body, $ary)
                || preg_match('{<span id="titleYear">\(<a href="/year/\d+[^>]*>(\d+)</a>}is', $body, $ary)
            ) {
                $result["year"] = trim($ary[1]);
            }

            // Cover URL
/*            preg_match('/<IMG.*?alt="cover".*?(http:\/\/.+?\.(jpe?g|gif))/i',$body, $ary);
            $result['imdbPosterUrl'] = trim($ary[1]);*/
            if (
                preg_match('{<meta[^>]*property=\'og:image\'[^>]*content="([^"]*)"}si',$body, $matches)
                || preg_match('{<td[^>]*id="img_primary"><a[^>]*href="/media/.*?"[^>]*><img[^>]*src="(.*?)"}i',$body, $matches)
                || preg_match('{<img[^>]+src="([^"]*)"[^>]*itemprop="?\'?image"?\'?[^>]*>}si',$body, $matches)
            ) {
                $poster = Lms_DataParser::absolutize(trim($matches[1]), $dirPath, self::getHost());
                $result["posters"][] = str_replace("http://", "https://", $poster);
            } else if (preg_match('/<IMG.*?alt=\"'.preg_quote($result["names"][0], '/').'\".*?(http:\/\/.+?\.(jpe?g|gif))/i',$body, $ary)) {
                $poster = Lms_DataParser::absolutize((trim($ary[1])), $dirPath, self::getHost());
                $result["posters"][] = str_replace("http://", "https://", $poster);
            } else {
                $result["posters"][] = 'http://i.media-imdb.com/images/intl/en/title_noposter.gif';
            }

            // MPAA Rating
            if (preg_match('/<h5><a href=\"\/mpaa\">MPAA<\/a>:<\/h5>(.*?)<\/div>/si', $body, $ary)
                || preg_match('{<h4>Motion Picture Rating \(<a[^>]*>MPAA</a>\)</h4>(.*?)</span>}is', $body, $ary)
            ) {
                $result['mpaa'] = trim(strip_tags($ary[1]));
            } else {
                $result['mpaa'] = null;
            }

            // Director
            $persones = array();
            if (preg_match('/<h5>Directors?:<\/h5>(.*?)<\/div>/is', $body, $ary)
                || preg_match('{Directors?:\s*</h4>(.*?)</div>}is', $body, $ary)
            ) {
                preg_match_all('/<a[^>]*?href="(\/name\/nm\d+)[^"]*?"[^>]*?>(.*?)<\//i', $ary[1], $ary2, PREG_SET_ORDER);
                foreach($ary2 as $director){
                    $persones[] = array(
                        "url"=> rtrim(Lms_DataParser::absolutize($director[1], $dirPath, self::getHost()), '/') . '/',
                        "names"=>array(trim(strip_tags($director[2]))),
                        "role"=>'director'
                    );
                }
            } 
            // Rating
            if (preg_match('/<h5>User Rating:<\/h5>.*?<div class=\".*?meta\"><b>(\d{1,2}\.\d)\/10<\/b>/i', $body, $ary)
                || preg_match('{<span class="rating-rating">(\d{1,2}\.\d)<span>}i', $body, $ary)
                || preg_match('{<span class="rating-rating"><span class="value"(?:\s+itemprop="ratingValue")?>(\d{1,2}\.\d)</span>}i', $body, $ary)
                || preg_match('{<span[^>]*itemprop="ratingValue"[^>]*>(\d{1,2}[\.,]\d)</span>}i', $body, $ary)
                || preg_match('{<div class="ratingValue"><strong[^>]*><span>(\d{1,2}[\.,]\d)<}i', $body, $ary)
            ) {
                $result['rating'] = trim(str_replace(',', '.', $ary[1]));
            } else {
                $result['rating'] = null;
            }

            if (preg_match('/<a[^>]*?href=\"ratings\"[^>]*?>([\d,\s]*?) votes<\/a>/i', $body, $ary)
                || preg_match('{<span[^>]+itemprop="ratingCount">([\d,\s\xC2\xA0]*?)</span>}is', $body, $ary)
                || preg_match('{"ratingCount"\s*:\s*(\d+),}is', $body, $ary)
            ) {
                $result['rating_count'] = trim(str_replace(array(",", " ", ".", "\xC2\xA0"), "", $ary[1]));
            } else {
                $result['rating_count'] = null;
            }


            // Countries
            $countries = array();
            if (preg_match_all('{<A[^>]*HREF="/(?:Sections/Countries|country|search/title\?country_of_origin)[^"]+?"[^>]*>([^<]+?)</A>}i', $body, $ary, PREG_PATTERN_ORDER)) {
                $result["countries"] = array();
                foreach($ary[1] as $country){
                    $result["countries"][] = trim($country);
                }
            }
            $result['description'] = '';
            if (preg_match('/<h5>Plot:<\/h5>(.*?)(<a|<br>|<\/div>)/is', $body, $ary)) {
                if (!empty($ary[1])) $result['description'] = strip_tags((trim($ary[1])));
            } else if (preg_match('{<p><p>(.*?)</p></p><div class="txt-block">}is', $body, $matches)) {
                $result['description'] = strip_tags((trim($matches[1])));
            } else if (preg_match('{<p itemprop="description">(.*?)</p>}is', $body, $matches)
                || preg_match('{<div[^>]*itemprop="description">(.*?)</}is', $body, $matches)
                || preg_match('{<h2>Storyline</h2><div[^>]*><p><span>(.*?)</span>}is', $body, $matches)
            ) {
                $result['description'] = str_replace('Add a Plot', '', strip_tags((trim($matches[1]))));
            }


            // Genres
            $genres = array();
            if (preg_match_all('/<A HREF="\/Sections\/Genres\/[^\/]+?\/">([^<]+?)<\/A>/i', $body, $ary, PREG_PATTERN_ORDER)
                || preg_match_all('{<a href="/genre/[^\?][^"]*?"[^>]*>([^<]*?)</a>}i', $body, $ary, PREG_PATTERN_ORDER)
                || preg_match_all('{<a[^>]*itemprop="genre"[^>]*>([^<]*)</a>}i', $body, $ary, PREG_PATTERN_ORDER)
            ) {
                foreach($ary[1] as $genre) {
                    if ($genre) $result["genres"][] = trim($genre);
                }
                $result["genres"] = array_values(array_unique($result["genres"]));
            }
            // Cast

            if (preg_match_all('/<a href=\"(\/name\/nm\d+\/)\">([^<]*?)<\/a><\/td><td class=\"ddd\">[\s\.]+?<\/td><td class=\"char\">(.*?)<\/td><\/tr>/i', $body, $ary,PREG_PATTERN_ORDER)
                || preg_match_all('{<a href="(/name/nm\d+/)"[^>]*?>([^<]*?)</a></td><td class="ddd">[\s\.]+?</td><td class="char">(.*?)</td></tr>}i', $body, $ary,PREG_PATTERN_ORDER) 
                || preg_match_all('{<a[^>]*?href="(/name/nm\d+/)[^"]*?"[^>]*?>([^<]*?)</a></td><td class="ellipsis">[\s\.]+?</td><td class="character">(.*?)</td>}is', $body, $ary,PREG_PATTERN_ORDER) 
                || preg_match_all('{<a[^>]*?href="(/name/nm\d+/)[^"]*?"[^>]*?><span[^>]*>([^<]*?)</span></a></td><td class="ellipsis">[\s\.]+?</td><td class="character">(.*?)</td>}is', $body, $ary,PREG_PATTERN_ORDER) 
            ) {
                $count = 0;
                while (isset($ary[1][$count]))
                {
                    $role = trim(preg_replace('{\s+}is', ' ', strip_tags($ary[3][$count])));
                    $actor  = trim(strip_tags($ary[2][$count]));
                    $persones[] = array(
                        "url" => Lms_DataParser::absolutize($ary[1][$count], $dirPath, self::getHost()),
                        "names" => array($actor),
                        "role" => 'actor', 
                        "character" => $role
                    );
                    $count++;
                }
                $persones = array_slice($persones, 0, 30);
            }
            $result["persones"] = $persones;
        } else {
            throw new Lms_DataParser_Exception("Error while parse $url : server returned " .  $response->getStatus());
        }
        $result['version'] = self::getVersion();
        if ($testMode) {
            if (isset($result['rating'])) {
                Lms_DataParser::testRange($result['rating'], 1, 10);
            }
            if (isset($result['rating_count'])) {
                Lms_DataParser::testRange($result['rating_count'], 1, 10000000);
            }
        }
        return $result;
    }

    static public function parsePerson(Zend_Http_Response $response, $url)
    {
        $result = array();
        if ($response->isSuccessful()){
            $body = Lms_DataParser::compactTags(Lms_DataParser::utfBodyFromResponse($response));
            //echo $body;
            $dirPath = dirname(parse_url($url, PHP_URL_PATH));
            $result["names"] = array();
            if (preg_match('{<div id="tn15title">.*?<h1>(.*?)(<span class="pro-link">.*?</span>)?</h1>}i', $body, $matches)
                || preg_match('{<h1 class="header"[^>]*>(.*?)</h1>}is', $body, $matches)
            ){
                $matches[1] = trim(strip_tags($matches[1]));
                $result["names"][] = $matches[1];
            }
            $bornDate = false;
            if (preg_match('{<time itemprop="birthDate" datetime="([^"]*?)">}i', $body, $matches)
                || preg_match('{<time datetime="([^"]*?)" itemprop="birthDate">}i', $body, $matches)
            ) {
                $result["born_date"] = $matches[1];
                $result["born_date"] = preg_replace('{(\D)(\d)(\D)}', '${1}0${2}${3}', $result["born_date"]);
            } else if (preg_match('{<a href="/OnThisDay.*?">(.*?)</a>}i', $body, $matches)
                || preg_match('{<a href="/date/\d+-\d+/?">(.*?)</a>}i', $body, $matches)
                || preg_match('{<a1[^>]*?href="/search/name\?birth_monthday=\d+-\d+[^"]*"[^>]*?>(.*?)</a>}i', $body, $matches)
            ){
                $matches[1] = trim(strip_tags($matches[1]));

                list($p1, $p2) = explode(" ", $matches[1]);
                if (preg_match('{\d+}', $p1)) {
                    $day = $p1;
                    $strMonth = $p2;
                } else {
                    $day = $p2;
                    $strMonth = $p1;
                }

                $months = array(
                    'January' => 1,
                    'February' => 2,
                    'March' => 3,
                    'April' => 4,
                    'May' => 5,
                    'June' => 6,
                    'July' => 7,
                    'August' => 8,
                    'September' => 9,
                    'October' => 10,
                    'November' => 11,
                    'December' => 12
                );                
                $month = $months[$strMonth];
                if (preg_match("/<a href=\"\/BornInYear.*?\">(.*?)<\/a>/i", $body, $matches)
                    || preg_match('{<a[^>]*?href="/search/name\?birth_year.*?"[^>]*?>(.*?)</a>}i', $body, $matches)
                ) {
                    $year = trim(strip_tags($matches[1]));
                    $datearray = array('year' => $year, 'month' => $month, 'day' => $day);
                    $bornDate = new Zend_Date($datearray);
                    $result["born_date"] = $bornDate->toString('yyyy-MM-dd');
                }
            }
            if (preg_match("/<a href=\"\/BornWhere.*?\">(.*?)<\/a>/i", $body, $matches)
                || preg_match('{<a[^>]*?href="/search/name\?birth_place.*?"[^>]*?>(.*?)</a>}i', $body, $matches)
            ) {
                $matches[1] = trim(strip_tags($matches[1]));
                $result["born_place"] = $matches[1];
            }
            
            $result["photos"] = array();
            if (preg_match('{<img id="name-poster".*?src="([^"]*?)"}is', $body, $matches)
                || preg_match("/<a name=\"headshot\".*?><img border=\"0\" src=\"(.*?)\".*?alt=\".*?\"><\/a>/i", $body, $matches)
                || preg_match('{<td[^>]*?id="img_primary"[^>]*?><a[^>]*?href="/media/[^>]*?><img[^>]*?src="([^"]*?)"}i', $body, $matches)
            ) {
                $imgurl = Lms_DataParser::absolutize($matches[1], $dirPath, self::getHost());
                $result["photos"][] = $imgurl;
            }
        } else {
            throw new Lms_DataParser_Exception("Error while parse $url : server returned " .  $response->getStatus());
        }
        $result['version'] = self::getVersion();
        return $result;
    }
}
