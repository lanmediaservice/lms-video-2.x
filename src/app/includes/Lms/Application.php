<?php
/**
 * Инициализация приложения
 * 
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Application.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Application
{
    
    const TASK_FILES_TASKS = 'files-tasks.php';
    const TASK_FILES_FRAMES = 'files-frames.php';
    const TASK_FILES_METAINFO = 'files-metainfo.php';
    const TASK_FILES_TTH = 'files-tth.php';
    const TASK_FILES_CHECK = 'files-check.php';
    const TASK_PERSONES_FIX = 'persones-fix.php';
    const TASK_PERSONES_PARSING = 'persones-parsing.php';
    const TASK_RATINGS_UPDATE = 'ratings-update.php';
    const TASK_RATINGS_LOCAL_UPDATE = 'ratings-local-update.php';


    private static $_configDefaults;
    
    private static $_config;

    /**
     * @var Lms_Api_Controller
     */
    private static $_apiController = null;
    /**
     * @var Zend_Controller_Front
     */
    private static $_frontController = null;
    /**
     * @var Zend_Translate
     */
    private static $_translate;
    /**
     * @var Zend_Acl
     */
    private static $_acl;
    /**
     * @var Lms_User
     */
    private static $_user;
    /**
     * @var Lms_MultiAuth
     */
    private static $_auth;
    /**
     * @var Zend_Controller_Request_Http
     */
    private static $_request;

    /**
     * Текущий язык
     * @var string
     */
    private static $_lang;
    /**
     * Текущий макет
     * @var string
     */
    private static $_layout;
    /**
     * Базовый URL без учета модификатора языка
     * http://examle.com/root/Url/ru/blah/blah ($_rootUrl = /root/Url)
     * @var string
     */
    private static $_rootUrl;

    /**
     * Массив директорий скриптов шаблона (.phtml)
     * @var array
     */
    private static $_scriptsTemplates;

    /**
     * Массив реальных путей и соответствующих относительных URL
     * публичных файлов шаблона (.css, .js и т.д.)
     * Пример:
     * Array(
     *      [0] => Array
     *          (
     *              [path] => C:/www/english/public/templates/user/ru
     *              [url] => /public/templates/user/ru
     *          )
     *
     *      [1] => Array
     *          (
     *              [path] => C:/www/english/public/templates/user
     *              [url] => /public/templates/user
     *          )
     *
     *      [2] => Array
     *          (
     *              [path] => C:/www/english/public/templates/default.dist/ru
     *              [url] => /public/templates/default.dist/ru
     *          )
     *
     *      [3] => Array
     *          (
     *              [path] => C:/www/english/public/templates/default.dist
     *              [url] => /public/templates/default.dist
     *          )
     *
     *  )
     *
     * @var array
     */
    private static $_publicTemplates;

    /**
     * Время начало работы скрипта
     * @var float
     */
    private static $_mainTimer;
    
    private static $_httpClient;

    private static $_mplayer;

    public static function setRequest()
    {
        self::$_request = new Zend_Controller_Request_Http();
    }
    
    public static function runApi()
    {
        self::setRequest();
        self::prepareApi();
        self::$_apiController->exec();
        self::close();
    }

    public static function prepareApi()
    {
        /**
         * Разъяснение комментариев:
         * self::initYYY()//зависит от XXX
         * Это значит перед запуском, метода YYY, должен отработать метод XXX
         * self::initYYY()//требует XXX
         * Это значит, что для корректной работы сущностей определяемых
         * методом YYY, должен быть проинизиализирован метод XXX (место
         * инициализации не имеет важного значения)
         */

        self::initEnvironmentApi();
        self::initConfig();//зависит от initEnvironment
        self::initDebug();//зависит от initConfig
        self::initErrorHandler();//зависит от initDebug
        self::initDb(); //зависит от initConfig, требует initDebug
        self::initConfigFromDb();//зависит от initDb
        self::initVariables();//зависит от initDb
        self::initApiController();//зависит от initVariables
//        self::initTranslate();//зависит от initApiRequest, initDebug
        self::initAcl();//зависит от initConfig, initVariables, initDb
    }

    public static function prepareCli()
    {
        /**
         * Разъяснение комментариев:
         * self::initYYY()//зависит от XXX
         * Это значит перед запуском, метода YYY, должен отработать метод XXX
         * self::initYYY()//требует XXX
         * Это значит, что для корректной работы сущностей определяемых
         * методом YYY, должен быть проинизиализирован метод XXX (место
         * инициализации не имеет важного значения)
         */

        self::initEnvironmentApi();
        self::initConfig();//зависит от initEnvironment
        self::initDebug();//зависит от initConfig
        self::initErrorHandler();//зависит от initDebug
        self::initDb(); //зависит от initConfig, требует initDebug
        self::initConfigFromDb();//зависит от initDb
        self::initVariables();//зависит от initDb
    }
    
    public static function initApiController()
    {
        self::$_apiController = Lms_Api_Controller::getInstance();
        self::$_apiController->analyzeHttpRequest();
        self::$_lang = self::$_apiController->getLang();
        if (!self::$_lang) {
            self::$_lang = self::$_config['langs']['default'];
        }

    }


    public static function run()
    {
        self::setRequest();
        $response = new Zend_Controller_Response_Http();
        $channel = Zend_Wildfire_Channel_HttpHeaders::getInstance();
        $channel->setRequest(self::$_request);
        $channel->setResponse($response);
        // Start output buffering
        ob_start();
        try { 
            self::prepare();
            Lms_Debug::debug('Request URI: ' . $_SERVER['REQUEST_URI']);
            try {
                self::$_frontController->dispatch(self::$_request);
            } catch (Exception $e) {
                Lms_Debug::crit($e->getMessage());
                Lms_Debug::crit($e->getTraceAsString());
            }
            self::close();
        } catch (Exception $e) {
            Lms_Debug::crit($e->getMessage());
            Lms_Debug::crit($e->getTraceAsString());
        }
        // Flush log data to browser
        $channel->flush();
        $response->sendHeaders();
    }

    public static function prepare()
    {
        /**
         * Разъяснение комментариев:
         * self::initYYY()//зависит от XXX
         * Это значит перед запуском, метода YYY, должен отработать метод XXX
         * self::initYYY()//требует XXX
         * Это значит, что для корректной работы сущностей определяемых
         * методом YYY, должен быть проинизиализирован метод XXX (место
         * инициализации не имеет важного значения)
         */

        self::initEnvironment();
        self::initConfig();//зависит от initEnvironment
        self::initSessions();//зависит от initConfig
        self::initDebug();//зависит от initConfig
        self::initErrorHandler();//зависит от initDebug
        self::initDb(); //зависит от initConfig, требует initDebug
        self::initConfigFromDb();//зависит от initDb
        self::initVariables();//зависит от initDb
        //self::initFrontController();//зависит от initConfig
        //self::initTranslate();//зависит от initFrontController, initDebug
        //self::initRoutes();//зависит от initFrontController
        self::initAcl();//зависит от initConfig, initVariables, initDb
        //self::initView();//зависит от initConfig, initFrontController,
                         //initAcl, initTranslate
    }
    
    public static function initEnvironmentApi()
    {
        ini_set('max_execution_time', 0);
    }

    public static function initEnvironment()
    {
        ini_set('max_execution_time', 1000);
        @header("Content-type:text/html;charset=windows-1251");
        if(get_magic_quotes_runtime())
        {
            // Deactivate
            set_magic_quotes_runtime(false);
        }
        static $alreadyStriped = false;
        if (get_magic_quotes_gpc() || !$alreadyStriped) {
            $_COOKIE = Lms_Array::recursiveStripSlashes($_COOKIE);
            $_GET = Lms_Array::recursiveStripSlashes($_GET);
            $_POST = Lms_Array::recursiveStripSlashes($_POST);
            $_REQUEST = Lms_Array::recursiveStripSlashes($_REQUEST);
            $alreadyStriped = true;
        } 
    }
    
    public static function initConfig($includeOldConfig = false)
    {
        if ($includeOldConfig) {
            include APP_ROOT . "/../config.php";
        }
        if (!$includeOldConfig) {
            include APP_ROOT . "/default.settings.php";
        }
        include APP_ROOT . "/local.settings.php";
        self::$_config = $config;
        self::$_configDefaults = $config;
    }

    public static function initConfigFromDb()
    {
        $db = Lms_Db::get('main');
        $rows = $db->select('SELECT * FROM `config`');
        foreach ($rows as $row) {
            if (!$row['active']) {
                continue;
            }
            switch ($row['type']) {
                case 'array': 
                    $value = unserialize($row['value']);
                    break;
                case 'scalar': 
                default: 
                    $value = $row['value'];
                    break;
            }
            $keys = preg_split('{/}', $row['key']);
            switch (count($keys)) {
                case 1:
                    self::$_config[$keys[0]] = $value;
                    break;
                case 2: 
                    self::$_config[$keys[0]][$keys[1]] = $value;
                    break;
                case 3: 
                    self::$_config[$keys[0]][$keys[1]][$keys[2]] = $value;
                    break;
                case 4: 
                    self::$_config[$keys[0]][$keys[1]][$keys[2]][$keys[3]] = $value;
                    break;
                case 5: 
                    self::$_config[$keys[0]][$keys[1]][$keys[2]][$keys[3]][$keys[4]] = $value;
                    break;
                default: 
                    throw new Lms_Exception("DB-config keys not support deep more 5 subitems");
                    break;
            }
        }
    }
    
    public static function initSessions()
    {
        Zend_Session::start();
    }
    
    public static function initDebug()
    {
        if (self::getConfig('log', 'error')
            || self::getConfig('log', 'debug')
            || self::getConfig('log', 'debug_console')
        ) {
            $logger = new Zend_Log();
            $logger->setEventItem('pid', getmypid());
            $logger->setEventItem('ip', Lms_Ip::getIp());
            
            $logDir = APP_ROOT . '/logs/';
            if (defined('LOGS_SUBDIR')) {
                $logDir .= rtrim(LOGS_SUBDIR, '/') . '/';
            }
            if (self::getConfig('log', 'error')) {
                $logFile = is_string(self::getConfig('log', 'error'))? self::getConfig('log', 'error') : $logDir . 'error.' . date('Y-m-d') . '.log';
                $writer = new Zend_Log_Writer_Stream($logFile);
                $logger->addWriter($writer);
                $format = '%timestamp% %ip%(%pid%) %priorityName% (%priority%): %message%'
                        . PHP_EOL;
                $formatter = new Zend_Log_Formatter_Simple($format);
                $writer->setFormatter($formatter);
                $filter = new Zend_Log_Filter_Priority(Zend_Log::NOTICE);
                $writer->addFilter($filter);
            }
            if (self::getConfig('log', 'debug')) {
                $logFile = is_string(self::getConfig('log', 'debug'))? self::getConfig('log', 'debug') : $logDir . 'debug.' . date('Y-m-d') . '.log';
                $writer = new Zend_Log_Writer_Stream($logFile);
                $logger->addWriter($writer);
                $format = '%timestamp% %ip%(%pid%) %priorityName% (%priority%): %message%'
                        . PHP_EOL;
                $formatter = new Zend_Log_Formatter_Simple($format);
                $writer->setFormatter($formatter);
            }
            
            if (self::getConfig('log', 'debug_console') 
                && php_sapi_name() != 'cli' 
                && !(isset($_GET['format']) && in_array($_GET['format'], array('ajax', 'json', 'php')))
                && (!defined('SKIP_DEBUG_CONSOLE') || !SKIP_DEBUG_CONSOLE)
            ) {
                $writer = new Lms_Log_Writer_Console();
                $format = '%timestamp%: %message%';
                $formatter = new Zend_Log_Formatter_Simple($format);
                $writer->setFormatter($formatter);
                $logger->addWriter($writer);
            }
            
            Lms_Debug::setLogger($logger);
            
            
            
            self::$_mainTimer = new Lms_Timer();
            self::$_mainTimer->start();
        }
    }
    
    public static function initErrorHandler()
    {
        Lms_Debug::initErrorHandler();
    }

    public static function initDb()
    {
        foreach (self::$_config['databases'] as $dbAlias => $dbConfig) {
            Lms_Db::addDb(
                $dbAlias,
                $dbConfig['connectUri'],
                $dbConfig['initSql'],
                $dbConfig['debug']
            );
        }
    }

    public static function initVariables()
    {
        if (self::$_request) {
            self::$_rootUrl = self::$_request->getBaseUrl();
        }
        if (preg_match('{\.php$}i', self::$_rootUrl)) {
            self::$_rootUrl = dirname(self::$_rootUrl);
        }

        Lms_Item::setDb(Lms_Db::get("main"), Lms_Db::get("main"));
        Lms_Item_Preloader::setDb(Lms_Db::get("main"));

        Lms_Item_Struct_Generator::setStoragePath(
            APP_ROOT . '/includes/Lms/Item/Struct'
        );
         

        Lms_Ufs::setInternalEncoding('CP1251');

        Lms_Ufs::setSystemEncoding(self::getConfig('filesystem', 'encoding', 'default'));
        foreach (self::getConfig('filesystem', 'directories') as $directoryConfig) {
            Lms_Ufs::setEncoding($directoryConfig['path'], $directoryConfig['encoding']);
        }
        
        Lms_Ufs::addConfig('ls_dateformat_in_iso8601', self::getConfig('filesystem', 'ls_dateformat_in_iso8601'));
        Lms_Ufs::addConfig('disable_4gb_support', self::getConfig('filesystem', 'disable_4gb_support'));
        
        
        Lms_Text::setEncoding('CP1251');
        Lms_Text::enableMultiByte();
        Lms_Api_Formatter_Ajax::setEncoding('CP1251');
        Lms_Api_Formatter_Json::setEncoding('CP1251');
        
        Lms_Thumbnail::setHttpClient(self::getHttpClient());
        Lms_Thumbnail::setThumbnailScript(rtrim(self::$_rootUrl, '/\\') . '/' . self::getConfig('thumbnail', 'script'), self::getConfig('thumbnail', 'key'));
        Lms_Thumbnail::setImageDir(
            rtrim($_SERVER['DOCUMENT_ROOT'] . self::$_rootUrl, '/\\') . '/media/images'
        );
        Lms_Thumbnail::setThumbnailDir(
            rtrim($_SERVER['DOCUMENT_ROOT'] . self::$_rootUrl, '/\\') . '/media/thumbnails'
        );
        Lms_Thumbnail::setErrorImagePath(rtrim($_SERVER['DOCUMENT_ROOT'] . self::$_rootUrl, '/\\') . '/media/error.png');
        Lms_Thumbnail::setCache(self::getConfig('thumbnail', 'cache'));
        
    }

    public static function initAcl()
    {
        self::$_auth = Lms_MultiAuth::getInstance();

        $cookieManager = new Lms_CookieManager(
            self::$_config['auth']['cookie']['key']
        );
        $authStorage = new Lms_Auth_Storage_Cookie(
            $cookieManager,
            self::$_config['auth']['cookie']
        );
        self::$_auth->setStorage($authStorage);

        self::$_acl = new Zend_Acl();
        self::$_acl->addRole(new Zend_Acl_Role('guest'))
                   ->addRole(new Zend_Acl_Role('user'), 'guest')
                   ->addRole(new Zend_Acl_Role('moder'), 'user')
                   ->addRole(new Zend_Acl_Role('admin'));

        self::$_acl->add(new Zend_Acl_Resource('movie'))
                   ->add(new Zend_Acl_Resource('comment'))
                   ->add(new Zend_Acl_Resource('bookmark'))
                   ->add(new Zend_Acl_Resource('rating'))
                   ->add(new Zend_Acl_Resource('user'))
                   ->add(new Zend_Acl_Resource('settings'))
                   ->add(new Zend_Acl_Resource('image-proxy'));
                   

        self::$_acl->allow('admin')
                   ->allow('moder', array('movie', 'comment', 'image-proxy'))
                   ->allow('user', array('bookmark', 'rating'))
                   ->allow('user', array('comment'), 'post')
                   ->allow('guest', array('movie'), 'view');
                   
        Lms_User::setAcl(self::$_acl);
        self::$_user = Lms_User::getUser();
    } 

    public static function close()
    {
        if (self::getConfig('optimize', 'classes_combine')) {
            Lms_NameScheme_Autoload::compileTo(APP_ROOT . '/includes/All.php');
        }
        
        foreach (self::$_config['databases'] as $dbAlias => $dbConfig) {
            if (Lms_Db::isInstanciated($dbAlias)) {
                $db = Lms_Db::get($dbAlias);
                $sqlStatistics = $db->getStatistics();
                $time = round(1000 * $sqlStatistics['time']);
                $count = $sqlStatistics['count'];
                Lms_Debug::debug(
                    "Database $dbAlias time: $time ms ($count queries)"
                );
            }
        }
        foreach (Lms_Timers::getTimers() as $name => $timer) {
            $time = round(1000 * $timer->getSumTime());
            Lms_Debug::debug(
                'Profiling "' . $name . '": ' . $time . ' ms (' . $timer->getCount() . ')'
            );
        }
        Lms_Debug::debug(
            'Used memory: ' . round(memory_get_usage()/1024) . ' KB'
        );
        self::$_mainTimer->stop();
        $time = round(1000 *self::$_mainTimer->getSumTime());
        Lms_Debug::debug("Execution time: $time ms");
    }
    
    public static function getLang()
    {
        return self::$_lang;
    }

    public static function getTranslate()
    {
        return self::$_translate;
    }

    public static function getRequest()
    {
        return self::$_request;
    }

    public static function getConfig($param = null)
    {
        $params = func_get_args();
        $result = self::$_config;
        foreach($params as $param) {
            if (!array_key_exists($param, $result)) {
                return null;
            }
            $result = $result[$param];
        }
        return $result;
    }

    public static function getConfigDefault($param = null)
    {
        $params = func_get_args();
        $result = self::$_configDefaults;
        foreach($params as $param) {
            if (!array_key_exists($param, $result)) {
                return null;
            }
            $result = $result[$param];
        }
        return $result;
    }
    
    public static function getHttpClient()
    {
        if (!self::$_httpClient) {
            $httpOptions = Lms_Application::getConfig('http_client');
            self::$_httpClient = new Zend_Http_Client(
                null,
                $httpOptions
            );
        }
        return self::$_httpClient;
    }    

    public static function getMplayer()
    {
        if (!self::$_mplayer) {
            self::$_mplayer = new Lms_ExternalBin_Mplayer(self::getConfig('mplayer', 'bin'));
            self::$_mplayer->setTempPath(self::getConfig('mplayer','tmp'));
        }
        return self::$_mplayer;
    }    

    public static function getLeechProtectionCode($array)
    {
        $str = implode("-", $array);
        $str .= self::getConfig('download', 'antileechkey');
        return md5($str);
    }
    
    public static function thumbnail($imgPath, &$width=0, &$height=0, $defer = false, $force = true)
    {
        if (!preg_match('{^https?://}i', $imgPath)) {
            //$imgPath = dirname(APP_ROOT) . '/' . $imgPath;
            $imgPath = rtrim($_SERVER['DOCUMENT_ROOT'] . self::$_rootUrl, '/\\') . '/' . $imgPath;
        }
        return Lms_Thumbnail::thumbnail($imgPath, $width, $height, $tolerance = 0.00, $zoom = true, $force = $force, $deferDownload = $defer, $deferResize = $defer);
    }
    
    public static function getAuthData(&$login, &$pass) 
    {
//        session_start();
        Zend_Session::start();
        if (self::getConfig('auth','cookie','crypt')) {
            $crypter = new Lms_Crypt(
                self::getConfig('auth','cookie','mode'),
                self::getConfig('auth','cookie','algorithm'),
                self::getConfig('auth','cookie','key')
            );
            $login = isset($_SESSION['login'])? $_SESSION['login'] : (isset($_COOKIE['login'])? trim($crypter->decrypt(base64_decode($_COOKIE['login']))) : "");
            $pass = isset($_SESSION['pass'])? $_SESSION['pass'] : (isset($_COOKIE['pass'])? trim($crypter->decrypt(base64_decode($_COOKIE['pass']))) : "");
        } else {
            $login = isset($_SESSION['login']) ? $_SESSION['login'] : (isset($_COOKIE['login'])? $_COOKIE['login'] : "");
            $pass = isset($_SESSION['pass']) ? $_SESSION['pass'] : (isset($_COOKIE['pass'])? $_COOKIE['pass'] : "");
        }
    }

    public static function setAuthData($login, $pass, $remember = true) 
    {
        Zend_Session::start();
        $_SESSION['login'] = $login;
        $_SESSION['pass'] = $pass;
        if ($remember) {
            if (self::getConfig('auth','cookie','crypt')) {
                $crypter = new Lms_Crypt(
                    self::getConfig('auth','cookie','mode'),
                    self::getConfig('auth','cookie','algorithm'),
                    self::getConfig('auth','cookie','key')
                );
                setcookie("login", base64_encode($crypter->encrypt($login)), time()+1209600);
                setcookie("pass", base64_encode($crypter->encrypt($pass)), time()+1209600);
            } else {
                setcookie("login", $login, time()+1209600);
                setcookie("pass", $pass, time()+1209600);
            }
        }
    }
    
    public static function clearAuthData() 
    {
        Zend_Session::start();
        $_SESSION['login'] = '';
        $_SESSION['pass'] = '';
        setcookie("login", '');
        setcookie("pass", '');
    }
    
    public static function _detectNames($names)
    {
        $pureNames["international_name"] = "";
        $pureNames["name"] = "";
        foreach ($names as $name) {
            $eng = 0;
            $rus = 0;
            for ($i = 0; $i < strlen($name);$i++) {
                $num = ord($name{$i});
                if ($num >= 65 && $num <= 122) $eng++;
                if ($num >= 192 && $num <= 255) $rus++;
            }
            if ($rus > $eng) {
                if (strlen($pureNames["name"]) < strlen($name)) $pureNames["name"] = $name;
            } else if (strlen($pureNames["international_name"]) < strlen($name)) $pureNames["international_name"] = $name;
        }
        return $pureNames;
    }    
  

    public static function formatMetainfo($metainfo)
    {
        static $compactCodecs = array(
            'Pulse Code Modulation (PCM)' => 'PCM',
            'MPEG Layer-2 or Layer-1' => 'MP2',
            'Dolby AC3' => 'AC3',
            'Dolby DTS' => 'DTS',
            'MPEG Layer-3' => 'AC3',
            'Advanced Audio Coding' => 'AAC',
            'XviD MPEG-4 (www.xvid.org)' => 'XviD',
            'Intel ITU H.264 Videoconferencing' => 'H.264',
        );
        
        if (!$metainfo || empty($metainfo['playtime_seconds'])) {
            return null;
        }
        $compactMetainfo = array();
        $s = (int) $metainfo['playtime_seconds'];
        $h = (int) floor($s/3600);
        $m = (int) floor(($s-$h*3600)/60);
        $s = (int) ($s - $h*3600 - $m*60);
        $compactMetainfo['playtime_seconds'] = (int) $metainfo['playtime_seconds'];
        $compactMetainfo['playtime'] = sprintf('%02d:%02d:%02d', $h, $m, $s);;
        $compactMetainfo['format'] = $metainfo['video']['streams'][0]['dataformat'];
        foreach ($metainfo['video']['streams'] as $stream) {
            $videoInfos = array();
            $videoInfos[] = "{$stream['resolution_x']}x{$stream['resolution_y']}";
            $videoInfos[] = floatval($stream['frame_rate']) . " fps";
            $codec = isset($compactCodecs[$stream['codec']])? $compactCodecs[$stream['codec']] : $stream['codec'];
            $videoInfos[] = $codec . ($stream['bitrate']>0? ', ' . round($stream['bitrate']/1000) . ' kbps' : '');
            if ($stream['bitrate']>0) {
                $videoInfos[] = round($stream['bitrate']/($stream['resolution_x']*$stream['resolution_y'])/$stream['frame_rate'], 3) . " bit/pixel";
            }
            $compactMetainfo['video']['label'] = "{$stream['resolution_x']}x{$stream['resolution_y']}";
            $compactMetainfo['video']['info'] = implode(", ", $videoInfos);
        }
        foreach ($metainfo['audio']['streams'] as $streamNum => $stream) {
            $audioInfos = array();
            $audioInfos[] = round($stream['sample_rate']/1000, 1) . " kHz";
            $codec = isset($compactCodecs[$stream['codec']])? $compactCodecs[$stream['codec']] : $stream['codec'];
            $audioInfos[] = $codec;
            $audioInfos[] = $stream['channels'] . "ch";
            if ($stream['bitrate']) {
                $audioInfos[] = "" . round($stream['bitrate']/1000) . " kbps";
            }
            if (isset($stream['lang'])) {
                $audioInfos[] = $stream['lang'];
            }
            if (isset($stream['name'])) {
                $audioInfos[] = $stream['name'];
            }
            
            $compactMetainfo['audio'][$streamNum]['label'] = $codec;
            if ($stream['bitrate']) {
                $compactMetainfo['audio'][$streamNum]['label'] .= " " . round($stream['bitrate']/1000) . " kbps";
            }
            if (isset($stream['lang'])) {
                $compactMetainfo['audio'][$streamNum]['label'] .= ", " . $stream['lang'];
            }
            $compactMetainfo['audio'][$streamNum]['info'] = implode(", ", $audioInfos);
        }
        return $compactMetainfo;
    }
    
    public static function getType($path, $isDir)
    {
        $videoExtensions = array('avi', 'mkv', 'mp4', 'mov', 'flv', 'vob', 'ts');
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ($isDir) {
            return 'folder';
        } else if (in_array(strtolower($ext), $videoExtensions)) {
            return 'video';
        } else {
            return 'file';
        }
    }

    public static function normalizePath($path)
    {
        $path = rtrim($path, "\\/");
        return preg_replace('{\\\}', '/', $path);
    }
    
    public static function calcLevel($path)
    {
        $path = self::normalizePath($path);
        return count(preg_split('{/+}', $path));
    }

    public static function isParentDirectory($directory, $subpath)
    {
        $directory = self::normalizePath($directory);
        $subpath = self::normalizePath($subpath);
        
        if (self::isWindows()) {
            return (stripos($subpath, $directory)===0);
        } else {
            return  (strpos($subpath, $directory)===0);
        }
    }
    
    public static function getTargetStorage($threshold = 0.02)
    {
        if (!self::getConfig('incoming', 'storages')) {
            return false;
        }
        $maxFree = 0;
        $storages = array();
        foreach (self::getConfig('incoming', 'storages') as $path) {
            $free =  disk_free_space($path)/disk_total_space($path);
            $storages[$path] = $free;
            if ($free > $maxFree) {
                $maxFree = $free;
            } 
        }
        
        foreach ($storages as $path => $free) {
            if ($free < ($maxFree * (1 - $threshold) )) {
                $storages[$path] = null;
            }
        }
        $storages = array_filter($storages);
        $targetStorage = array_rand($storages);
        return self::normalizePath($targetStorage);
    }
    
    public static function isWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
    
    public static function prepareTextIndex($text, $type, $id, &$trigramValues, &$suggestionValues)
    {
        if (!trim($text)) {
            return;
        }
        static $stopWords, $db;
        if (!$stopWords) {
            $stopWords = Lms_Application::getConfig('indexing', 'stop_words');
        }
        if (!$db) {
            $db = Lms_Db::get('main');
        }

        $trigrams = array();
        $textLength = Lms_Text::length($text);
        if ($textLength>=3) {
            for ($i=0; $i<=$textLength-3; $i++) {
                $trigram = substr($text, $i, 3);
                $trigramValues[] = sprintf(
                    "(%s, %s, %d)",
                    $db->escape(strtolower($trigram)),
                    $db->escape($type), 
                    $id
                );
            }
        }

        preg_match_all('{\w{2,}}', strtolower($text), $words, PREG_PATTERN_ORDER);
        $wordsFiltered = array();
        foreach (array_diff($words[0], $stopWords) as $word) {
            if (!preg_match('{^\d+$}', $word)) {
                $wordsFiltered[] = $word;
            }
        }
        array_unshift($wordsFiltered, strtolower($text));
        //print_r($wordsFiltered);
        foreach ($wordsFiltered as $word) {
            $suggestionValues[] = sprintf(
                "(%s, %s ,%d)",
                $db->escape(trim($word, ' .\'"')),
                $db->escape($type), 
                $id
            );
        }
    }
    public static function runTask($task)
    {
        if (Lms_Application::isWindows()) {
            $script = APP_ROOT . '\\tasks\\' . $task;
            if (is_file(APP_ROOT . '\\tasks\\php-forced.bat')) {
                $php = APP_ROOT . '\\tasks\\php-forced.bat';
            } else {
                $php = APP_ROOT . '\\tasks\\php.bat';
            }
            $cmd = 'start ' . escapeshellarg($task) . ' ' . escapeshellarg($php) . ' ' . $script;
            //Lms_Debug::debug($cmd);
            pclose(popen($cmd, "r"));    
        } else {
            $script = APP_ROOT . '/tasks/' . $task;
            $php = APP_ROOT . '/tasks/php';
            exec(escapeshellarg($php) . ' ' . escapeshellarg($script) . ' >/dev/null 2>&1 &');
        }
    }    
    
    public static function tryRunTasks($fileRelatedOnly = false)
    {
        if (Lms_Application::getConfig('incoming', 'force_tasks')) {
            self::runTask(self::TASK_FILES_TASKS);
            self::runTask(self::TASK_FILES_METAINFO);
            self::runTask(self::TASK_FILES_FRAMES);
            if (!$fileRelatedOnly) {
                self::runTask(self::TASK_PERSONES_PARSING);
            }
        }
    }

    private static function updateSources($filepath, $version, $confirm = false)
    {
        $result = array();
        if (!$confirm) {
            $result[] = 'Режим тестирования (файлы не перезаписываются)';
        }
        try {
            $zip = new ZipArchive();
            $backup = self::normalizePath(self::getConfig('update', 'backup_path'));
            if ($zip->open($filepath) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {  
                    $stat = $zip->statIndex($i);
                    $message = '';
                    if ($stat['size']==0 && preg_match('{/$}', $stat['name'])) {
                        $localDirPath = rtrim(dirname(APP_ROOT) . '/' . $stat['name'], '/');
                        if (!is_dir($localDirPath)) {
                            $message .= "создание директории '{$stat['name']}' ... ";
                            if (mkdir($localDirPath, 0755, true)) {
                                $message .= 'OK';
                            } else {
                                $message .= 'не удалось!';
                            }
                        }
                    } else {
                        $localFilePath = dirname(APP_ROOT) . '/' . $stat['name'];
                        $localDirPath = dirname($localFilePath);
                        $content = $zip->getFromIndex($i);
                        if (is_file($localFilePath)) {
                            if (md5_file($localFilePath)!=md5($content)) {
                                //переписать файл
                                $message .= "обновление файла '{$stat['name']}' ... ";
                                if (is_writable($localFilePath)) {
                                    if ($backup) {
                                        $backupFilePath = $backup . '/' . $stat['name'];
                                        $backupDirPath = dirname($backupFilePath);
                                        if (!is_dir($backupDirPath)) {
                                            mkdir($backupDirPath, 0755, true);
                                        }
                                        if (copy($localFilePath, $backupFilePath)) {;
                                            $message .= 'backup & ';
                                        } else {
                                            throw new Lms_Exception("Ошибка при создании резервной копии '{$stat['name']}' -> '$backupFilePath'");
                                        }
                                    }
                                    if ($confirm) {
                                        if (file_put_contents($localFilePath, $content)!==FALSE) {
                                            $message .= 'OK'; 
                                        } else {
                                            $message .= 'не удалось!'; 
                                        }
                                    } else {
                                        $message .= 'OK'; 
                                    }
                                } else {
                                    $message .= 'не удалось!'; 
                                }
                            }
                        } else {
                            if (!is_dir($localDirPath)) {
                                $message .= "создание директории '{$stat['name']}' ... ";
                                if (mkdir($localDirPath, 0755, true)) {
                                    $message .= 'OK';
                                } else {
                                    $message .= 'не удалось!';
                                }
                                $result[] = $message;
                                $message = '';
                            }
                            $message .= "создание файла '{$stat['name']}' ... ";
                            if (is_writable($localDirPath)) {
                                if ($confirm) {
                                    if (file_put_contents($localFilePath, $content)!==FALSE) {
                                        $message .= 'OK'; 
                                    } else {
                                        $message .= 'не удалось!'; 
                                    }
                                } else {
                                    $message .= 'OK'; 
                                }
                            } else {
                                $message .= 'не удалось!'; 
                            }
                        }
                    }
                    if ($message) {
                        $result[] = $message;
                    }
                } 
                $zip->close();
                
                if ($confirm) {
                    $db = Lms_Db::get('main');
                    $db->query("REPLACE INTO `registry` SET `key`='src_version', `value`=?", $version);
                }              
            } else {
                throw new Lms_Exception("Can't open zip-archive '$filepath'");
            }
        } catch (Lms_Exception $e) {
            Lms_Debug::err($e->getMessage());
            $result[] = 'Ошибка: ' . $e->getMessage();
        }
        return $result;
    }

    private static function updateDb($filepath, $confirm = false)
    {
        $result = array();

        try {
            $db = Lms_Db::get('main');

            $revisionDbNumber = $db->selectCell("SELECT `value` FROM `registry` WHERE `key`='db_version'");
            if (!$revisionDbNumber) {
                $revisionDbNumber = 1;
            }

            Lms_Debug::debug("Current DB Revision = $revisionDbNumber");

            $engine = 'MyISAM';
            $rows = $db->select('SHOW ENGINES');
            foreach ($rows as $row) {
                if ($row['Engine']=='InnoDB') {
                    $engine = 'InnoDB';
                }
            }

            $zip = new ZipArchive();
            if ($zip->open($filepath) === TRUE) {
                $updates = array();
                for ($i = 0; $i < $zip->numFiles; $i++) {  
                    $stat = $zip->statIndex($i);
                    if (preg_match('{update-(\d+)\.sql}', $stat['name'], $matches)) {
                        $revision = (int)$matches[1];
                        if ($revision>$revisionDbNumber) {
                            $updates[$revision] = $zip->getFromIndex($i);
                        }
                    }
                } 
                $zip->close();
                if (count($updates)) {
                    ksort($updates);
                    if (!$confirm) {
                        $result[] = 'Режим тестирования (запросы не выполняются)';
                    }

                    $result[] = 'Права установленные для текущего пользователя:';
                    $grants = $db->selectCol('SHOW GRANTS FOR CURRENT_USER;');
                    foreach ($grants as $grant) {
                        $result[] = $grant;
                    }
                    if (!$confirm) {
                        $result[] = 'Пожалуйста, убедитесь, что прав текущего пользователя достаточно для выполнения следующих SQL-запросов';
                    }

                    foreach ($updates as $revision => $update) {

                        $update = implode("\n", preg_grep('{^\s*(?:#|--).*}', preg_split('/(\r\n|\r|\n)/', $update), PREG_GREP_INVERT));

                        $sqls = preg_split("{;+\s*\n}", $update);

                        $lastSqlNum = $db->selectCell("SELECT `value` FROM `registry` WHERE `key`='db_last_sql_num'");
                        $n = 1;
                        foreach ($sqls as $sql) {
                            $sql = trim($sql);
                            if ($sql) {
                                if ($n>$lastSqlNum) {
                                    if (preg_match('{^CREATE}i', $sql)) {
                                        $sql = preg_replace('{ENGINE=MyISAM}i', "ENGINE=$engine", $sql);
                                    }
                                    if (!self::getConfig('databases', 'main', 'debug')) {
                                        Lms_Debug::debug($sql);
                                    }
                                    $result[] = "Запрос #$n: $sql;";
                                    if ($confirm) {
                                        $res = $db->query($sql);
                                        $result[] = "Результат (затронуто строк): " . $res;
                                        $db->query("REPLACE INTO `registry` SET `key`='db_last_sql_num', `value`=?", $n);
                                    }
                                }
                                $n++;
                            }

                        }
                        if ($confirm) {
                            $db->query("REPLACE INTO `registry` SET `key`='db_version', `value`=?", $revision);
                            $db->query("REPLACE INTO `registry` SET `key`='db_last_sql_num', `value`=?", 0);
                        }
                    }
                } else {
                    $result[] = 'Нет обновлений для базы данных';
                }
            } else {
                throw new Lms_Exception("Can't open zip-archive '$filepath'");
            }
        } catch (Lms_Db_Exception $e) {
            Lms_Debug::err($e->getMessage());
            $result[] = 'Ошибка: ' . $e->getMessage();
            $result[] = 'Обновление базы данных остановлено на этом запросе. Повторное обновление начнется с этого запроса.';
        }
        return $result;
    }

    public static function checkUpdates()
    {
        $db = Lms_Db::get('main');
        $result = array();
        $channel = rtrim(self::getConfig('update', 'channel'), '/') . '/';
        
        $currentVersion = $db->selectCell("SELECT `value` FROM `registry` WHERE `key`='src_version'");
        $result['current'] = $currentVersion;
        
        $httpClient = self::getHttpClient();
        
        $response = $httpClient->resetParameters()
                               ->setUri($channel)
                               ->setMethod(Zend_Http_Client::GET)
                               ->request();
        if (!$response->isSuccessful()) {
            throw new Lms_Exception("Can't get info from $channel");
        }
        
        $lastVersion = trim($response->getBody());
        $result['last'] = $lastVersion;
        
        $result['changelog'] = array();
        if ($currentVersion != $lastVersion) {
            $response = $httpClient->resetParameters()
                                   ->setUri($channel . "changelog/from-$currentVersion-latest.txt")
                                   ->setMethod(Zend_Http_Client::GET)
                                   ->request();
            if ($response->isSuccessful()) {
                $result['changelog'] = unserialize($response->getBody());
                foreach ($result['changelog'] as &$commit) {
                    $commit['message'] = Lms_Text::htmlizeText(Lms_Translate::translate('UTF-8', 'CP1251', $commit['message']));
                }
            }
        }
        
        return $result;
    }
    
    public static function upgrade($confirm = false)
    {
        if (!class_exists('ZipArchive')) {
            throw new Lms_Exception("PHP Zip Extension not found!");
        }
        
        $result = array();
        $channel = rtrim(self::getConfig('update', 'channel'), '/') . '/';
        
        $httpClient = self::getHttpClient();
        
        $response = $httpClient->resetParameters()
                               ->setUri($channel)
                               ->setMethod(Zend_Http_Client::GET)
                               ->request();
        if (!$response->isSuccessful()) {
            throw new Lms_Exception("Can't get info from $channel");
        }
        $version = trim($response->getBody());

        $sourcesFilePath = tempnam(self::getConfig('tmp'), "latest.zip");
        if (!$sourcesFilePath) {
            throw new Lms_Exception("Can't create temp file");
        }
        $url = $channel . 'sources/latest.zip';
        Lms_Debug::debug("Download $url to $sourcesFilePath");
        $response = $httpClient->resetParameters()
                               ->setUri($url)
                               ->setMethod(Zend_Http_Client::GET)
                               ->request();
        if (!$response->isSuccessful()) {
            throw new Lms_Exception("An error occurred while fetching latest.zip from $url: " . $response->getStatus() . ": " . $response->getMessage());
        }
        file_put_contents($sourcesFilePath, $response->getBody());
        
        
        $dbFilePath = tempnam(self::getConfig('tmp'), "sql-updates.zip");
        if (!$dbFilePath) {
            throw new Lms_Exception("Can't create temp file");
        }
        $url = $channel . 'db/sql-updates.zip';
        Lms_Debug::debug("Download $url to $dbFilePath");
        $response = $httpClient->resetParameters()
                               ->setUri($url)
                               ->setMethod(Zend_Http_Client::GET)
                               ->request();
        if (!$response->isSuccessful()) {
            throw new Lms_Exception("An error occurred while fetching latest.zip from $url: " . $response->getStatus() . ": " . $response->getMessage());
        }
        file_put_contents($dbFilePath, $response->getBody());
        
        
        $result['src'] = self::updateSources($sourcesFilePath, $version, $confirm);
        $result['db'] = self::updateDb($dbFilePath, $confirm);
        
        unlink($sourcesFilePath);
        unlink($dbFilePath);

        if (file_exists(APP_ROOT . '/includes/All.php')) {
            unlink(APP_ROOT . '/includes/All.php');
        }

        foreach (glob(APP_ROOT . '/includes/Lms/Item/Struct/*.php') as $filename) {
            unlink($filename);
        }
        return $result;
    }
    
    public static  function pathToLocalizedVideo($url)
    {
        $videoFolder = rtrim(dirname(APP_ROOT)) . '/media/trailers/video';
        $ext = pathinfo($url, PATHINFO_EXTENSION);
        $hash = md5($url);
        
        $path = $videoFolder . "/" . implode("/", str_split(substr($hash, 0, 2))) . "/" . "$hash.{$ext}";
        return $path;
    }
    
    public static function urlToLocalizedVideo($url)
    {
        $path = self::pathToLocalizedVideo($url);
        $localUrl = self::$_rootUrl . str_replace(dirname(APP_ROOT), '', $path);
//        $localUrl = str_replace('\\', '/', $path);
//        $dr = str_replace('\\', '/', realpath(realpath($_SERVER['DOCUMENT_ROOT'])));
//        $localUrl = str_replace($dr, '', $localUrl);
        return $localUrl;
    }

}