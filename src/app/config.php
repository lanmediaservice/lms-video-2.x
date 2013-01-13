<?php
/**
 * Файл конфигурации путей
 * 
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: config.dist.php 700 2011-06-10 08:40:53Z macondos $
 */


define('APP_ROOT', dirname(__FILE__));

// Пропишите следующие 3 параметра:
// Путь к third-party библиотекам
$tplibIncludes = dirname(__FILE__) . '/libs/tplib';
// Путь к общей библиотеке
$libIncludes = dirname(__FILE__) . '/libs/lib';
// Путь библиотекам ShareLib
$shareLibIncludes = dirname(__FILE__) . '/libs/sharelib';
//Путь классам проекта
$includes = APP_ROOT . '/includes';


/**
 * Код ниже редактировать не следует
 */
$includePaths = explode(PATH_SEPARATOR, get_include_path());

if (array_search($tplibIncludes, $includePaths) === false) {
    array_unshift($includePaths, $tplibIncludes);
}

if (array_search($libIncludes, $includePaths) === false) {
    array_unshift($includePaths, $libIncludes);
}
if (array_search($shareLibIncludes, $includePaths) === false) {
    array_unshift($includePaths, $shareLibIncludes);
}

if (array_search($includes, $includePaths) === false) {
    array_unshift($includePaths, $includes);
}
set_include_path(implode(PATH_SEPARATOR, $includePaths));

/**
 * Запуск механизма автозагрузки классов
 */
require_once "Lms/NameScheme/Autoload.php";

/**
 * Загрузка кеша классов
 */
if (@fopen('All.php', 'r', true)) {
    include_once('All.php');
}
