<?php

@set_time_limit(0);
 
if (!defined('LOGS_SUBDIR')) {
    define('LOGS_SUBDIR', 'tasks');
}

if (!defined('SKIP_DEBUG_CONSOLE')) {
    define('SKIP_DEBUG_CONSOLE', true); 
}

require_once dirname(dirname(dirname(__FILE__))) . "/config.php";

Lms_Application::prepareCli();