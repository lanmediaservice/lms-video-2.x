<?php
require_once(dirname(__FILE__) . '/../autorun.php');

class BadTestCases extends TestSuite {
    function __construct() {
        TestSuite::__construct('Two bad test cases');
        $this->addFile(dirname(__FILE__) . '/support/empty_test_file.php');
    }
}
?>