<?php

if (!defined('SKIP_DEBUG_CONSOLE')) {
    define('SKIP_DEBUG_CONSOLE', true); 
}

header('Expires: -1');
require_once __DIR__ . "/app/config.php";

Lms_Application::prepare();

$fileId = (isset($_REQUEST['f'])) ? (int) $_REQUEST['f'] : null;
$userId = (isset($_REQUEST['u'])) ? (int) $_REQUEST['u'] : null;
$verificationCode = $_REQUEST["v"];
    
try {
    if (Lms_Application::getLeechProtectionCode(array($fileId, $userId))!=$verificationCode) {
        throw new Lms_Exception("Verification code failed");
    }
    $file = Lms_Item::create('File', $fileId);
    $downloadLink = "<a href=\"" . $file->getDownloadLink() . "\">" . $file->getName() . "</a><br>";
    include __DIR__ . '/templates/' . Lms_Application::getConfig('template') . '/download.phtml';
    
} catch (Exception $e) {
    echo $e->getMessage();
    Lms_Debug::crit($e->getMessage());
    Lms_Debug::crit($e->getTraceAsString());
}
