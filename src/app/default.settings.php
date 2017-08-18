<?php 
/**
 * Настройки конфигурации по-умолчанию
 * 
 * @copyright 2006-2013 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 */

/**
 * Режим вывода ошибок
 */
error_reporting(E_ALL);

@setlocale(LC_ALL, array('ru_RU.CP1251','ru_RU.cp1251','ru_SU.CP1251','ru','russian')); 
@setlocale(LC_NUMERIC, '');

/**
 * Установка временной зоны
 */
date_default_timezone_set(@date_default_timezone_get());

/**
 * Заголовок сайта 
 */
$config['title'] = "Видео-каталог";

/**
 * Настройки отладки
 * 
 * При значении true логи сохраняются в файлах 
 * /app/logs/debug.<дата>.log и error.<дата>.log
 * /app/logs/tasks/debug.<дата>.log и error.<дата>.log
 * 
 * Можно указывать конкретный путь к файлу:
 * $config['log']['error'] = '/var/lms-video.error.log';
 */
$config['log']['error'] = true;
$config['log']['debug'] = true;

//Включить отладочную консоль по Ctrl+~
$config['log']['debug_console'] = false;


/**
 * Конфигурация баз данных
 */

$config['databases']['main'] = array(
    'connectUri' => "mysqli://root@localhost/lms?ident_prefix=",
    'initSql' => "SET NAMES cp1251",
    'debug' => 0
);


/**
 * Настройка языков
 */
$config['langs']['supported'] = array('ru'=>'Русский');
$config['langs']['default'] = 'ru';


/**
 * Минимальное количество оценок для расчета локального рейтинга 
 */
$config['rating']['count'] = 3;


// Шаблон оформления 
// Шаблоны находятся в каталоге "templates/"
$config['template'] = "modern";

//Дополнительные пункты меню
$config['topmenu_links'] = array(
    array('url'=>'/music/', 'text'=>'Музыка'),
    array('url'=>'/video/', 'text'=>'Видео', 'selected'=>true),
    array('url'=>'/forum/', 'text'=>'Форум')
);

//Дополнительные пункты в нижнем футере
$config['support_links'] = array(
    array('url'=>'/support/', 'text'=>'Задать вопрос'),
    array('url'=>'mailto:support@isp.com', 'text'=>'Написать письмо'),
    array('url'=>'/forum/', 'text'=>'Форум')
);

/**
 * Временная директория для общих нужд
 */
$config['tmp'] = isset($_ENV['TEMP'])? $_ENV['TEMP'] : '/tmp';

/**
 *Опции оптимизации 
 */
$config['optimize']['classes_combine'] = 0;
$config['optimize']['js_combine'] = 0;
$config['optimize']['js_compress'] = 0;
$config['optimize']['css_combine'] = 0;
$config['optimize']['css_compress'] = 0;
$config['optimize']['less_combine'] = 0;

/**
 * Настройки сервиса парсинга
 */
$config['parser_service']['username'] = 'demo';
$config['parser_service']['password'] = 'demo';
$config['parser_service']['url'] = 'http://service.lanmediaservice.com/2/actions.php';

/**
 * Настройки HTTP-клиента 
 */
$config['http_client']['maxredirects'] = 5;
$config['http_client']['timeout'] = 60;
$config['http_client']['useragent'] = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5 (.NET CLR 3.5.30729)';
                       
//Коэффициент скачиваний выше среднего, чтобы считаться хитом
$config['hit_factor'] = 3;

/**
 * Слова для исключения из поискового индекса 
 */
$config['indexing']['stop_words'] = preg_split('{\s+}', 'of the or and in to i ii iii iv v on de la le les no at it na ne vs hd season сезон в на не из от по до за или');

/**
 * Настройки скрипта уменьшения картинок "на лету" 
 */
$config['thumbnail']['key'] = md5(__FILE__);
$config['thumbnail']['script'] = 'thumbnail.php';
$config['thumbnail']['cache'] = false;
$config['thumbnail']['delete_bad_images_chance'] = 0.1;
$config['thumbnail']['show_bad_images_as_is'] = true;

/**
 * Настройки авторизации и регистрации 
 */
$config['auth']['logon.php'] = 'logon.dist.php';
$config['auth']['register_timeout'] = 60;

/**
 * Настройки шифрования cookies 
 */
$config['auth']['cookie'] = array(
    'crypt' => true,
    'mode' => Lms_Crypt::MODE_ECB,
    'algorithm' => 'blowfish',
    'key' => 'key52345346_change_it'
);

/**
 * Настройки поисковых движков для поиска по-умолчанию 
 */
$config['parsing']['default_engines'] = array(
    'kinopoisk' => true,
    'ozon' => false,
    'world-art' => false,
    'sharereactor' => false,
    'imdb' => false,
);

/* 
 * Коэффициенты качества полей данных, учитываемое при автослиянии данных
 */
$config['automerge'] = array();
$config['automerge']['manual'] = array(
    'default' => 10,
);
$config['automerge']['imdb'] = array(
    'default' => 0.9,
    'description' => 0.1,
    'rating_imdb_value' => 2,
    'rating_imdb_count' => 2,
    'mpaa' => 2,
    'poster' => 2,
);

$config['automerge']['kinopoisk'] = array(
    'default' => 1,
    'poster' => 2.5,
    'genres' => 2,
    'countries' => 2,
    'name' => 2,
    'persones' => 2,
);

$config['automerge']['ozon'] = array(
    'default' => 1,
    'genres' => 0,
    'countries' => 0.1,
    'description' => 2,
);

$config['automerge']['world-art'] = array(
    'default' => 1,
    'genres' => 2.1,
    'description' => 2.1,
);

/**
 * Настройки поступлений 
 */
$config['incoming']['root_dirs'] = array('/home/');
$config['incoming']['ignore_files'] = array('Thumbs.db', 'desktop.ini', '/^\./', '/\.(zip|rar|txt|pdf)$/');
$config['incoming']['cache_time'] = 600; //кеширование поступлений в секундах
$config['incoming']['hide_import'] = false; //добавлять скрытыми
$config['incoming']['force_tasks'] = true; //запускать после импорта, скрипты парсинга персоналий, генерации скриншотов и т.д.
$config['incoming']['page_size'] = 20; //размер страницы поступлений
$config['incoming']['limit'] = 0; //лимит сканирования поступлений
$config['incoming']['storages'] = array(); //директории для переноса в них импортированных файлов

$config['metaparser']['ignore_files'] = array('Thumbs.db', 'desktop.ini', '/^\./', '/\.(zip|rar|txt|pdf)$/');
$config['metaparser']['max_deep'] = 5; //максимальная глубина сканировании директорий фильма

/** 
 * Настройки mplayer
 */
$config['mplayer'] = array(
    'bin' => '/usr/local/bin/mplayer',
    'tmp' => $config['tmp']
);
$config['frames']['count'] = 10;//количество генерируемых фреймов

/**
 * Настройки автоматической генерации TTH-хешей для DC++-ссылок 
 */
$config['files']['tth'] = array(
    'enabled' => false,
    'bin' => false,
);

/**
 * Маски замены 
 * ------------------------------------------------------------------------------
 * |          |        Маска              |             Результат               |
 * ------------------------------------------------------------------------------
 * | source   | /home/media/disk/1/       | /home/media/disk/1/myfilm.avi       |
 * ------------------------------------------------------------------------------
 * | smb      | //mediaserver/films1/     | \\mediaserver\films1\myfilm.avi     |
 * | download | ftp://mediaserver/films1/ | ftp://mediaserver/films1/myfilm.avi |
 * ------------------------------------------------------------------------------
 * Алгоритм работы на примере получения фтп-ссылки:
 * в исходном пути к фильму (например, /home/media/disk/1/myfilm.avi) ищется совпадение 
 * строки source (например, /home/media/disk/1/) и заменяется на download
 * (например ftp://mediaserver/films1/), таким образом образуется фтп-ссылка
 * ftp://mediaserver/films1/myfilm.avi
 * 
 * Пример1:
 * $config['download']['masks'][] = array(
 *   'source' => 'd:/films/',
 *   'download' => 'ftp://mediaserver/films1/',
 *   'smb' => '//mediaserver/films1/'
 * );
 * $config['download']['masks'][] = array(
 *   'source' => 'e:/films/',
 *   'download' => 'ftp://mediaserver/films2/',
 *   'smb' => '//mediaserver/films2/'
 * );
 * В результате ссылка на сетевой ресурс для файла "e:/films/film.avi" 
 * будет выглядеть как "\\mediaserver\films2\film.avi" и т.д.
 *
 * Пример2:
 * $config['download']['masks'][] = array(
 *   'source' => '/home/media/disk/1/',
 *   'download' => 'ftp://mediaserver/films1/',
 * );
 * $config['download']['masks'][] = array(
 *   'source' => '/home/media/disk/2/',
 *   'download' => '//mediaserver/films2/',
 * );
 * В результате ссылка на ftp для файла "/home/media/disk/2/film.avi" 
 * будет выглядеть как "ftp://mediaserver/films2/film.avi" и т.д.
 * Доступа к файлам по Samb'e в данном примере будет отсутствовать
 */
$config['download']['masks'] = array();
$config['download']['masks'][] = array(
    'source' => '/home/video/',
    'download' => 'http://mediaserver/download/',
    'smb' => '//mediaserver/video/'
);

//Показывать перед скачиванием по фтп лицензионное соглашение (/templates/modern/download.phtml)
$config['download']['license'] = true;

//Включить/отключить доступ по Samba
$config['download']['smb'] = false;

//Экранировать ссылки (абв -> %E0%E1%E2)
$config['download']['escape']['enabled'] = true;
//Также экранировать ссылки и в IE
$config['download']['escape']['ie'] = true;
//Кодировка для ссылок (CP1251, UTF-8, KOI8-R и т.д.)
$config['download']['escape']['encoding'] = false;
//Ключ защиты от скачивания (используется только с параметром $config['download']['license'])
$config['download']['antileechkey'] = 'secret';

/** 
 * Режимы работы пользователей
 */
$config['download']['modes'][1]['smb'] = 1; //Доступ к Samba
$config['download']['modes'][1]['ftp'] = 1; //Доступ к FTP

$config['download']['modes'][2]['smb'] = 1;
$config['download']['modes'][2]['ftp'] = 1;

$config['download']['modes'][3]['smb'] = 1;
$config['download']['modes'][3]['ftp'] = 1;

/**
 * Права на создаваемые папки и файлы при переносе импортированных фильмов в
 * директории $config['incoming']['storages'] 
 */
$config['filesystem']['permissions']['directory'] = 0755;
$config['filesystem']['permissions']['file'] = 0644;

/**
 * Указать что команда ls выводит дату в формате ISO8601 
 * Не учитывается на 64-битных системах
 */
$config['filesystem']['ls_dateformat_in_iso8601'] = false;
/**
 * Отключить поддержку файлов >4Гб для 32-битных систем
 * Не учитывается на 64-битных системах
 */
$config['filesystem']['disable_4gb_support'] = false;

/**
 * Кодировка файловой системы по-умолчанию 
 */
$config['filesystem']['encoding']['default'] = 'CP1251';

/**
 * Настройки кодировки файловой системы для отдельных директорий 
 * Пример:
 * $config['filesystem']['directories'][] = array(
 *     'path' => '/media/',
 *     'encoding' => 'UTF-8'
 * );
 */
$config['filesystem']['directories'] = array();

/**
 * Настойки обновлений 
 */
$config['update'] = array(
    'backup_path' => false,
    'channel' => 'http://update.lanmediaservice.com/get/lms-video/2.0/',
);

/**
 * Настройки для преобразования полного поля озвучивания в короткий вариант
 */
$config['short_translation'] = array();
$config['short_translation']["Дубляж"] = 'Dub';
$config['short_translation']["На языке оригинала"] = 'Original';
$config['short_translation']["Профессиональный многоголосый"] = 'MVO';
$config['short_translation']["Любительский многоголосый"] = 'MVO';
$config['short_translation']["Одноголосый"] = 'VO';
$config['short_translation']["Гоблин (правильный)"] = 'AVO(Гоблин)';
$config['short_translation']["Субтитры"] = 'Sub';

/**
 * Настройки отображения ссылок в шаблоне modern
 */
//какие ссылки пользователь может включать/отключать:
$config['download']['selectable'] = array('download'=>false, 'smb'=>true, 'dcpp'=>true);
//установки по-умолчанию
$config['download']['defaults'] = array('download'=>true, 'smb'=>true, 'dcpp'=>true);
//какие плейлисты может выбирать пользователь:
$config['download']['players']['selectable'] = array('la'=>true, 'mp'=>true, 'mpcpl'=>true, 'bsl'=>true, 'crp'=>true, 'tox'=>true, 'kaf'=>true, 'pls'=>true, 'xspf'=>true);
//плейлист по-умолчанию:
$config['download']['players']['default'] = 'xspf'; 

/**
 * Настройки вариантов перевода
 */
$config['translation_options'] = array();
$config['translation_options'][] = "";
$config['translation_options'][] = "Дубляж";
$config['translation_options'][] = "Профессиональный многоголосый";
$config['translation_options'][] = "Профессиональный двухголосый";
$config['translation_options'][] = "Профессиональный одноголосый";
$config['translation_options'][] = "Любительский многоголосый";
$config['translation_options'][] = "Любительский двухголосый";
$config['translation_options'][] = "Любительский одноголосый";
$config['translation_options'][] = "Оригинал";
$config['translation_options'][] = "Субтитры";
$config['translation_options'][] = "LostFilm";
$config['translation_options'][] = "Гоблин (правильный)";
$config['translation_options'][] = "Гоблин (смешной)";


/**
 * Настройки вариантов качества
 */
$config['quality_options'] = array();
$config['quality_options'][] = "";
$config['quality_options'][] = "DVDRip";
$config['quality_options'][] = "HDRip";
$config['quality_options'][] = "BDRip";
$config['quality_options'][] = "HDDVDRip";
$config['quality_options'][] = "HDTVRip";
$config['quality_options'][] = "WEBDLRip";
$config['quality_options'][] = "WebRip";
$config['quality_options'][] = "DVDScr";
$config['quality_options'][] = "VHSrip";
$config['quality_options'][] = "SATRip";
$config['quality_options'][] = "TVRip";
$config['quality_options'][] = "Telecine";
$config['quality_options'][] = "Telesync";
$config['quality_options'][] = "CamRip";

//Количество символов в коротком
//описании для фильмов в каталоге. 0 - не использовать.
$config['short_description'] = 0;

/**
 * Разрешить скачивание трейлеров 
 */
$config['trailers']['download'] = true;

//временное включение парсинга старого кинопоиска
$config['parser_service']['old_kinopoisk_mode'] = true;

$config['symlinks'] = array();