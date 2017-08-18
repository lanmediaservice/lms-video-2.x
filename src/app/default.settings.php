<?php 
/**
 * ��������� ������������ ��-���������
 * 
 * @copyright 2006-2013 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 */

/**
 * ����� ������ ������
 */
error_reporting(E_ALL);

@setlocale(LC_ALL, array('ru_RU.CP1251','ru_RU.cp1251','ru_SU.CP1251','ru','russian')); 
@setlocale(LC_NUMERIC, '');

/**
 * ��������� ��������� ����
 */
date_default_timezone_set(@date_default_timezone_get());

/**
 * ��������� ����� 
 */
$config['title'] = "�����-�������";

/**
 * ��������� �������
 * 
 * ��� �������� true ���� ����������� � ������ 
 * /app/logs/debug.<����>.log � error.<����>.log
 * /app/logs/tasks/debug.<����>.log � error.<����>.log
 * 
 * ����� ��������� ���������� ���� � �����:
 * $config['log']['error'] = '/var/lms-video.error.log';
 */
$config['log']['error'] = true;
$config['log']['debug'] = true;

//�������� ���������� ������� �� Ctrl+~
$config['log']['debug_console'] = false;


/**
 * ������������ ��� ������
 */

$config['databases']['main'] = array(
    'connectUri' => "mysqli://root@localhost/lms?ident_prefix=",
    'initSql' => "SET NAMES cp1251",
    'debug' => 0
);


/**
 * ��������� ������
 */
$config['langs']['supported'] = array('ru'=>'�������');
$config['langs']['default'] = 'ru';


/**
 * ����������� ���������� ������ ��� ������� ���������� �������� 
 */
$config['rating']['count'] = 3;


// ������ ���������� 
// ������� ��������� � �������� "templates/"
$config['template'] = "modern";

//�������������� ������ ����
$config['topmenu_links'] = array(
    array('url'=>'/music/', 'text'=>'������'),
    array('url'=>'/video/', 'text'=>'�����', 'selected'=>true),
    array('url'=>'/forum/', 'text'=>'�����')
);

//�������������� ������ � ������ ������
$config['support_links'] = array(
    array('url'=>'/support/', 'text'=>'������ ������'),
    array('url'=>'mailto:support@isp.com', 'text'=>'�������� ������'),
    array('url'=>'/forum/', 'text'=>'�����')
);

/**
 * ��������� ���������� ��� ����� ����
 */
$config['tmp'] = isset($_ENV['TEMP'])? $_ENV['TEMP'] : '/tmp';

/**
 *����� ����������� 
 */
$config['optimize']['classes_combine'] = 0;
$config['optimize']['js_combine'] = 0;
$config['optimize']['js_compress'] = 0;
$config['optimize']['css_combine'] = 0;
$config['optimize']['css_compress'] = 0;
$config['optimize']['less_combine'] = 0;

/**
 * ��������� ������� ��������
 */
$config['parser_service']['username'] = 'demo';
$config['parser_service']['password'] = 'demo';
$config['parser_service']['url'] = 'http://service.lanmediaservice.com/2/actions.php';

/**
 * ��������� HTTP-������� 
 */
$config['http_client']['maxredirects'] = 5;
$config['http_client']['timeout'] = 60;
$config['http_client']['useragent'] = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5 (.NET CLR 3.5.30729)';
                       
//����������� ���������� ���� ��������, ����� ��������� �����
$config['hit_factor'] = 3;

/**
 * ����� ��� ���������� �� ���������� ������� 
 */
$config['indexing']['stop_words'] = preg_split('{\s+}', 'of the or and in to i ii iii iv v on de la le les no at it na ne vs hd season ����� � �� �� �� �� �� �� �� ���');

/**
 * ��������� ������� ���������� �������� "�� ����" 
 */
$config['thumbnail']['key'] = md5(__FILE__);
$config['thumbnail']['script'] = 'thumbnail.php';
$config['thumbnail']['cache'] = false;
$config['thumbnail']['delete_bad_images_chance'] = 0.1;
$config['thumbnail']['show_bad_images_as_is'] = true;

/**
 * ��������� ����������� � ����������� 
 */
$config['auth']['logon.php'] = 'logon.dist.php';
$config['auth']['register_timeout'] = 60;

/**
 * ��������� ���������� cookies 
 */
$config['auth']['cookie'] = array(
    'crypt' => true,
    'mode' => Lms_Crypt::MODE_ECB,
    'algorithm' => 'blowfish',
    'key' => 'key52345346_change_it'
);

/**
 * ��������� ��������� ������� ��� ������ ��-��������� 
 */
$config['parsing']['default_engines'] = array(
    'kinopoisk' => true,
    'ozon' => false,
    'world-art' => false,
    'sharereactor' => false,
    'imdb' => false,
);

/* 
 * ������������ �������� ����� ������, ����������� ��� ����������� ������
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
 * ��������� ����������� 
 */
$config['incoming']['root_dirs'] = array('/home/');
$config['incoming']['ignore_files'] = array('Thumbs.db', 'desktop.ini', '/^\./', '/\.(zip|rar|txt|pdf)$/');
$config['incoming']['cache_time'] = 600; //����������� ����������� � ��������
$config['incoming']['hide_import'] = false; //��������� ��������
$config['incoming']['force_tasks'] = true; //��������� ����� �������, ������� �������� ����������, ��������� ���������� � �.�.
$config['incoming']['page_size'] = 20; //������ �������� �����������
$config['incoming']['limit'] = 0; //����� ������������ �����������
$config['incoming']['storages'] = array(); //���������� ��� �������� � ��� ��������������� ������

$config['metaparser']['ignore_files'] = array('Thumbs.db', 'desktop.ini', '/^\./', '/\.(zip|rar|txt|pdf)$/');
$config['metaparser']['max_deep'] = 5; //������������ ������� ������������ ���������� ������

/** 
 * ��������� mplayer
 */
$config['mplayer'] = array(
    'bin' => '/usr/local/bin/mplayer',
    'tmp' => $config['tmp']
);
$config['frames']['count'] = 10;//���������� ������������ �������

/**
 * ��������� �������������� ��������� TTH-����� ��� DC++-������ 
 */
$config['files']['tth'] = array(
    'enabled' => false,
    'bin' => false,
);

/**
 * ����� ������ 
 * ------------------------------------------------------------------------------
 * |          |        �����              |             ���������               |
 * ------------------------------------------------------------------------------
 * | source   | /home/media/disk/1/       | /home/media/disk/1/myfilm.avi       |
 * ------------------------------------------------------------------------------
 * | smb      | //mediaserver/films1/     | \\mediaserver\films1\myfilm.avi     |
 * | download | ftp://mediaserver/films1/ | ftp://mediaserver/films1/myfilm.avi |
 * ------------------------------------------------------------------------------
 * �������� ������ �� ������� ��������� ���-������:
 * � �������� ���� � ������ (��������, /home/media/disk/1/myfilm.avi) ������ ���������� 
 * ������ source (��������, /home/media/disk/1/) � ���������� �� download
 * (�������� ftp://mediaserver/films1/), ����� ������� ���������� ���-������
 * ftp://mediaserver/films1/myfilm.avi
 * 
 * ������1:
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
 * � ���������� ������ �� ������� ������ ��� ����� "e:/films/film.avi" 
 * ����� ��������� ��� "\\mediaserver\films2\film.avi" � �.�.
 *
 * ������2:
 * $config['download']['masks'][] = array(
 *   'source' => '/home/media/disk/1/',
 *   'download' => 'ftp://mediaserver/films1/',
 * );
 * $config['download']['masks'][] = array(
 *   'source' => '/home/media/disk/2/',
 *   'download' => '//mediaserver/films2/',
 * );
 * � ���������� ������ �� ftp ��� ����� "/home/media/disk/2/film.avi" 
 * ����� ��������� ��� "ftp://mediaserver/films2/film.avi" � �.�.
 * ������� � ������ �� Samb'e � ������ ������� ����� �������������
 */
$config['download']['masks'] = array();
$config['download']['masks'][] = array(
    'source' => '/home/video/',
    'download' => 'http://mediaserver/download/',
    'smb' => '//mediaserver/video/'
);

//���������� ����� ����������� �� ��� ������������ ���������� (/templates/modern/download.phtml)
$config['download']['license'] = true;

//��������/��������� ������ �� Samba
$config['download']['smb'] = false;

//������������ ������ (��� -> %E0%E1%E2)
$config['download']['escape']['enabled'] = true;
//����� ������������ ������ � � IE
$config['download']['escape']['ie'] = true;
//��������� ��� ������ (CP1251, UTF-8, KOI8-R � �.�.)
$config['download']['escape']['encoding'] = false;
//���� ������ �� ���������� (������������ ������ � ���������� $config['download']['license'])
$config['download']['antileechkey'] = 'secret';

/** 
 * ������ ������ �������������
 */
$config['download']['modes'][1]['smb'] = 1; //������ � Samba
$config['download']['modes'][1]['ftp'] = 1; //������ � FTP

$config['download']['modes'][2]['smb'] = 1;
$config['download']['modes'][2]['ftp'] = 1;

$config['download']['modes'][3]['smb'] = 1;
$config['download']['modes'][3]['ftp'] = 1;

/**
 * ����� �� ����������� ����� � ����� ��� �������� ��������������� ������� �
 * ���������� $config['incoming']['storages'] 
 */
$config['filesystem']['permissions']['directory'] = 0755;
$config['filesystem']['permissions']['file'] = 0644;

/**
 * ������� ��� ������� ls ������� ���� � ������� ISO8601 
 * �� ����������� �� 64-������ ��������
 */
$config['filesystem']['ls_dateformat_in_iso8601'] = false;
/**
 * ��������� ��������� ������ >4�� ��� 32-������ ������
 * �� ����������� �� 64-������ ��������
 */
$config['filesystem']['disable_4gb_support'] = false;

/**
 * ��������� �������� ������� ��-��������� 
 */
$config['filesystem']['encoding']['default'] = 'CP1251';

/**
 * ��������� ��������� �������� ������� ��� ��������� ���������� 
 * ������:
 * $config['filesystem']['directories'][] = array(
 *     'path' => '/media/',
 *     'encoding' => 'UTF-8'
 * );
 */
$config['filesystem']['directories'] = array();

/**
 * �������� ���������� 
 */
$config['update'] = array(
    'backup_path' => false,
    'channel' => 'http://update.lanmediaservice.com/get/lms-video/2.0/',
);

/**
 * ��������� ��� �������������� ������� ���� ����������� � �������� �������
 */
$config['short_translation'] = array();
$config['short_translation']["������"] = 'Dub';
$config['short_translation']["�� ����� ���������"] = 'Original';
$config['short_translation']["���������������� ������������"] = 'MVO';
$config['short_translation']["������������ ������������"] = 'MVO';
$config['short_translation']["�����������"] = 'VO';
$config['short_translation']["������ (����������)"] = 'AVO(������)';
$config['short_translation']["��������"] = 'Sub';

/**
 * ��������� ����������� ������ � ������� modern
 */
//����� ������ ������������ ����� ��������/���������:
$config['download']['selectable'] = array('download'=>false, 'smb'=>true, 'dcpp'=>true);
//��������� ��-���������
$config['download']['defaults'] = array('download'=>true, 'smb'=>true, 'dcpp'=>true);
//����� ��������� ����� �������� ������������:
$config['download']['players']['selectable'] = array('la'=>true, 'mp'=>true, 'mpcpl'=>true, 'bsl'=>true, 'crp'=>true, 'tox'=>true, 'kaf'=>true, 'pls'=>true, 'xspf'=>true);
//�������� ��-���������:
$config['download']['players']['default'] = 'xspf'; 

/**
 * ��������� ��������� ��������
 */
$config['translation_options'] = array();
$config['translation_options'][] = "";
$config['translation_options'][] = "������";
$config['translation_options'][] = "���������������� ������������";
$config['translation_options'][] = "���������������� �����������";
$config['translation_options'][] = "���������������� �����������";
$config['translation_options'][] = "������������ ������������";
$config['translation_options'][] = "������������ �����������";
$config['translation_options'][] = "������������ �����������";
$config['translation_options'][] = "��������";
$config['translation_options'][] = "��������";
$config['translation_options'][] = "LostFilm";
$config['translation_options'][] = "������ (����������)";
$config['translation_options'][] = "������ (�������)";


/**
 * ��������� ��������� ��������
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

//���������� �������� � ��������
//�������� ��� ������� � ��������. 0 - �� ������������.
$config['short_description'] = 0;

/**
 * ��������� ���������� ��������� 
 */
$config['trailers']['download'] = true;

//��������� ��������� �������� ������� ����������
$config['parser_service']['old_kinopoisk_mode'] = true;

$config['symlinks'] = array();