<?php
set_time_limit(600);
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Europe/Minsk');

$config['prepare_streams'] = false;
$config['test_enable'] = "/";
$config['save_etalon'] = false;
$config['debug_test'] = false;

$config['parsers']['kinopoisk']['search1'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('kinopoisk', 'search', array('name'=>'Matrix'), true)),
    'context' => 'search_results',
);
$config['parsers']['kinopoisk']['search2'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('kinopoisk', 'search', array('name'=>'Телекинез'), true)),
    'context' => 'search_results',
);
$config['parsers']['kinopoisk']['search3'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('kinopoisk', 'search', array('name'=>'Six Figures Getting Sick'), true)),
    'context' => 'search_results',
);
$config['parsers']['kinopoisk']['search4'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('kinopoisk', 'search', array('name'=>'rocky iii'), true)),
    'context' => 'search_results',
);
$config['parsers']['kinopoisk']['search5'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('kinopoisk', 'search', array('name'=>'Игра престолов'), true)),
    'context' => 'search_results',
);
$config['parsers']['kinopoisk']['film1'] = array(
    'url' => 'https://www.kinopoisk.ru/film/301/',
    'context' => 'film',
);
$config['parsers']['kinopoisk']['film2'] = array(
    'url' => 'https://www.kinopoisk.ru/film/94223/',
    'context' => 'film',
);
$config['parsers']['kinopoisk']['film3'] = array(
    'url' => 'https://www.kinopoisk.ru/film/zherebets-2009-466924/',
    'context' => 'film',
);
$config['parsers']['kinopoisk']['film4'] = array(
    'url' => 'https://www.kinopoisk.ru/film/79925/',
    'context' => 'film',
);

//facebook, twitter with .*?name.*?
$config['parsers']['kinopoisk']['film5'] = array(
    'url' => 'https://www.kinopoisk.ru/film/zdravstvuyte-menya-zovut-doris-2015-845996/',
    'context' => 'film',
);

$config['parsers']['kinopoisk']['person'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('kinopoisk', 'person', array('id'=>'9838'), true)),
    'context' => 'person',
);
$config['parsers']['kinopoisk']['person2'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('kinopoisk', 'person', array('id'=>'27645'), true)),
    'context' => 'person',
);


//=============================================================================
$config['parsers']['imdb']['search1'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('imdb', 'search', array('name'=>'Matrix'), true)),
    'context' => 'search_results',
);
$config['parsers']['imdb']['search2'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('imdb', 'search', array('name'=>'L\'inceste, la conspiration des oreilles bouchees'), true)),
    'context' => 'search_results',
);
$config['parsers']['imdb']['search3'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('imdb', 'search', array('name'=>'rocky iii'), true)),
    'context' => 'search_results',
);

$config['parsers']['imdb']['film1'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('imdb', 'film', array('id'=>133093), true)),
    'context' => 'film',
);
$config['parsers']['imdb']['film2'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('imdb', 'film', array('id'=>1648099), true)),
    'context' => 'film',
);
$config['parsers']['imdb']['film3'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('imdb', 'film', array('id'=>460649), true)),
    'context' => 'film',
);
$config['parsers']['imdb']['film4'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('imdb', 'film', array('id'=>1111929), true)),
    'context' => 'film',
);
$config['parsers']['imdb']['film5'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('imdb', 'film', array('id'=>1980162), true)),
    'context' => 'film',
);

$config['parsers']['imdb']['person'] = array(
    'url' => str_replace('http:', 'https:', Lms_DataParser::constructPath('imdb', 'person', array('id'=>'401'), true)),
    'context' => 'person',
);
