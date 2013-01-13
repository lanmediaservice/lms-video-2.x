#!/usr/local/bin/php -q
<?php
require_once dirname(__FILE__) . '/include/init.php';

ob_start(array('Lms_Api_Cli', 'output'), 80);

try {
    $argv = array_slice($_SERVER['argv'], 1);
    try {
        if (!empty($argv[0]) && !empty($argv[1])) {
            echo Lms_Api_Cli::exec($argv[0], $argv[1]);
        } else {
            $prog = $_SERVER['argv'][0];
            echo "См. справку по доступным методам:\n";
            foreach (array('user', 'movie', 'person', 'file', 'comment', 'bookmark', 'rating', 'hit') as $module) {
                $class = Lms_Api_Cli::getModule($module);
                $methods = get_class_methods($class);
                foreach ($methods as $method) {
                    echo "    $prog $module $method -h\n";
                }
            }
        }
    } catch (Lms_Exception $e) {
        Lms_Debug::crit($e->getMessage());
        Lms_Debug::crit($e->getTraceAsString());
        echo $e->getMessage() . "\n";
        exit(1);
    }
} catch (Zend_Console_Getopt_Exception $e) {
    echo $e->getMessage() . "\n\n";
    Lms_Api_Cli::showUsageAndExit($e, 1);
}

require_once dirname(__FILE__) . '/include/end.php'; 
