<?php
/**
 * @copyright 2006-2013 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 */

//Часовые пояса см.: http://en.wikipedia.org/wiki/List_of_tz_database_time_zones
//date_default_timezone_set('Europe/Minsk');

$config['databases']['main'] = array(
    'connectUri' => "mysqli://root@localhost/lms?ident_prefix=",
    'initSql' => "SET NAMES cp1251",
    'debug' => 0
);

$config['auth']['cookie']['key'] = md5(__FILE__ . $config['databases']['main']['connectUri']);

/**
 * Для кеширования обращений к файловой системе можно использовать memcached
 */
/*
$config['thumbnail']['cache'] = Zend_Cache::factory(
    'Core',
    'Memcached',
    array(
        'lifetime' => null,
        'automatic_serialization' => true
    ),
    array(
        'servers' => array(array('host' => 'localhost', 'port' => 11211, 'persistent' => true,))
    )
); */