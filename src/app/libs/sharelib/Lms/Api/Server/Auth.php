<?php
/**
 * API-функции авторизации
 * 
 * @copyright 2006-2010 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @version $Id: Auth.php 291 2009-12-28 12:55:20Z macondos $
 * @package Api
 */

/** 
 * @package Api
 */
class Lms_Api_Server_Auth extends Lms_Api_Server_Abstract
{

    public static function logon($params)
    {
        $config = Lms_Application::getConfig('auth');
        $authProviderKey = $config['module'];
        $authClassName = 'Lms_Auth_Adapter_' . ucfirst($config['module']);
        $authAdapter = new $authClassName(Lms_Db::get($config['db']));
        $authAdapter->setIdentity($params['username'])
                    ->setCredential($params['password']);
        $auth = Lms_MultiAuth::getInstance();

        if (!$params['remember']) {
            $storage = $auth->getStorage();
            $storage->setCookieExpire(0);
        }
        $result = $auth->authenticate($authAdapter, $config['module']);
        if (!$result->isValid()) {
            $auth->clearIdentity();
            $errors = $result->getMessages();
            $translate = Lms_Application::getTranslate();
            foreach ($errors as &$error) {
                $error = $translate->translate($error);
            }
            
            return new Lms_Api_Response(401, 'Authorization failed', $errors);
        } else {
            return new Lms_Api_Response(200, 'OK');
        }
    }

    public static function logout($params)
    {
        $auth = Lms_MultiAuth::getInstance();
        $auth->clearIdentity();
        return new Lms_Api_Response(200, 'OK');
    }

}