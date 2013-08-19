<?php
class Lms_Service_DataParser extends Lms_Logable{
    
    private $_requestClient;
    private $_httpClient;
    private $_serviceUrl = 'http://service.lms.by/2/actions.php';
    private $_serviceUsername;
    private $_servicePassword;
    private $_serviceApp;
    
    public function __construct($requestClient = null, $httpClient= null)
    {
        if ($requestClient) {
            $this->setRequestClient($requestClient);
        }
        if ($httpClient) {
            $this->setHttpClient($httpClient);
        }
    }
    
    public function setRequestClient(Lms_PhpHttpRequest_Client $requestClient)
    {
        $this->_requestClient = $requestClient;
    }
    
    public function setHttpClient(Zend_Http_Client $httpClient)
    {
        $this->_httpClient = $httpClient;
    }
    
    public function setServiceApp($app)
    {
        $this->_serviceApp = $app;
    }
    
    public function setServiceUrl($url)
    {
        $this->_serviceUrl = $url;
    }
    
    public function setAuthData($username, $password)
    {
        $this->_serviceUsername = $username;
        $this->_servicePassword = $password;
    }
    
    function parseUrl($url, $module, $context, $acceptedAttaches = array())
    {
        $res = false;
        $request = array(
            'action' => 'parseUrl',
            'url' => $url,
            'context' => $context,
            'module' => $module,
            'accepted_attaches' => $acceptedAttaches,
            'encoding' => 'CP1251',
            'app' => $this->_serviceApp
        );
        $result = $this->execServiceAction($request);
        if ($result['success']) {
            $res = $result['response'];
        } elseif (in_array($result['response'], array(404, 500))) {
            $response = $this->_httpClient->resetParameters()
                                          ->setUri($url)
                                          ->setMethod(Zend_Http_Client::GET)
                                          ->setHeaders('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
                                          ->setHeaders('Accept-Language', 'ru,en-us;q=0.7,en;q=0.3')
                                          ->setHeaders('Accept-Encoding', 'gzip, deflate')
                                          ->setHeaders('Accept-Charset', 'windows-1251,utf-8;q=0.7,*;q=0.7')
                                          ->setHeaders('Referer', dirname($url))
                                          ->request();
            $request['action'] = 'parseResponse';
            $request['response'] = $response->asString();
            $result = $this->execServiceAction($request);
            if ($result['success']) {
                $res = $result['response'];
            }
        }
        
        if (!$result['success']) {
            Lms_Debug::warn('Service returned ' . $result['response'] . ' ' . $result['message']);
            throw new Lms_Service_DataParser_Exception('Service returned ' . $result['response'] . ' ' . $result['message']);
        }
        
        if ($res && count($acceptedAttaches) && isset($res['suburls'])) {
            foreach ($res['suburls'] as $attachName => $subUrlStruct) {
                if (!isset($res['attaches'][$attachName])) {
                    list($subModule, $subContext, $subUrl) = $subUrlStruct;
                    $res['attaches'][$attachName] = $this->parseUrl($subUrl, $subModule, $subContext);
                }
            }
        }
        return $res;
    }
    
    function execServiceAction($request)
    {
        $params = array();
        $logonRequest = array(
            'action' => 'logon',
            'username' => $this->_serviceUsername,
            'password' => $this->_servicePassword,
            'auth_provider_key' => 'local'
        );
        $logonRequestID = $this->_addParams($logonRequest, $params, 0);
        $requestID = $this->_addParams($request, $params, $logonRequestID);
        $params['view_method'] = 'php';
        //Lms_Debugger::log($params);
        $response = $this->_requestClient->query($this->_serviceUrl, $params);
        //Lms_Debugger::log($response);
        if (strlen($response['text'])) {
            $this->log('Service text output: ' .$response['text']);
        }
        $result = $response['php'];
        
        return $result[$requestID];
    }
    
    function _addParams($inParams, &$outParams, $lastActionNum=0){
        $actionNum = $lastActionNum + 1;
        foreach ($inParams as $paramKey=>$paramValue){
            $outParams[$paramKey][$actionNum] = $paramValue;
        }
        return $actionNum;
    }
    
    function updateRatings($movies)
    {
        $res = false;
        $request = array(
            'action' => 'Utils.updateRatings',
            'movies' => $movies,
            'app' => $this->_serviceApp
        );
        $result = $this->execServiceAction($request);
        if ($result['success']) {
            $res = $result['response'];
        }
        
        if (!$result['success']) {
            Lms_Debug::warn('Service returned ' . $result['response'] . ' ' . $result['message']);
            throw new Lms_Service_DataParser_Exception('Service returned ' . $result['response'] . ' ' . $result['message']);
        }
        return $res;
    }
    
    function searchKinopoiskId($name, $year)
    {
        $res = false;
        $request = array(
            'action' => 'Utils.searchKinopoiskId',
            'name' => $name,
            'year' => $year,
            'encoding' => 'CP1251',
            'app' => $this->_serviceApp
        );
        $result = $this->execServiceAction($request);
        if ($result['success']) {
            $res = $result['response'];
        }
        
        if (!$result['success']) {
            Lms_Debug::warn('Service returned ' . $result['response'] . ' ' . $result['message']);
            throw new Lms_Service_DataParser_Exception('Service returned ' . $result['response'] . ' ' . $result['message']);
        }
        return $res;
    }
}