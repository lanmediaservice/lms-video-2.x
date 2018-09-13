<?php

require_once __DIR__ . "/config.php";

Lms_Application::prepare();
require_once dirname(dirname(__DIR__)) . "/" . (Lms_Application::getConfig('auth', 'logon.php')?: "logon.php") ;

$user = Lms_User::getUser();
if (!$user->isAllowed("parser", "test")) {
    die('Forbidden');
}


require_once "simpletest/unit_tester.php";
require_once "simpletest/reporter.php";
require_once dirname(__FILE__) . "/test.config.php";

$config['test_enable'] = false;
if (!empty($_POST['action'])) {
    $config['test_enable'] = $_POST['path'];
    switch ($_POST['action']) {
        case "debug":
            $config['debug_test'] = $_POST['path'];
            break;
        case "save_etalon":
            $config['save_etalon'] = $_POST['path'];
            break;
        case "prepare_streams":
            $config['prepare_streams'] = $_POST['path'];
            break;
    }
}


function simpleLogger($message)
{
    echo $message . "<br/>";
    return true;
}

class DataParserTest extends UnitTestCase
{

    function testInterface()
    {
        $response = new Zend_Http_Response(200, array(), '<h1 class="moviename-big">Матрица </h1></td></tr><tr><td><table>tbody><tr><td><span>The Matrix</span>');
        $result1 = Lms_DataParser_Kinopoisk::constructPathSearch(array('name' => 'Matrix'));
        $result2 = Lms_DataParser::constructPath('kinopoisk', 'search', array('name' => 'Matrix'), false);
        $this->assertEqual($result1, $result2);

        try {
            Lms_DataParser::constructPath('not_exists_module', 'search', array('name' => 'Matrix'));
            $this->assertTrue(false, 'Throw Exception if module not found');
        } catch (Lms_DataParser_Exception $e) {
            $this->assertEqual($e->getMessage(), 'Module not_exists_module not found!', 'Throw Exception if module not found');
        }

        try {
            Lms_DataParser::constructPath('kinopoisk', 'not_exists_action', array('name' => 'Matrix'));
            $this->assertTrue(false, 'Throw Exception if action not found');
        } catch (Lms_DataParser_Exception $e) {
            $this->assertEqual($e->getMessage(), 'Construct method Lms_DataParser_Kinopoisk::constructPathNotExistsAction not found!', 'Throw Exception if construct method not found');
        }

        $result1 = Lms_DataParser_Kinopoisk::parseFilm($response, 'http://www.kinopoisk.ru/level/1/film/195223/');
        $result2 = Lms_DataParser::parse('kinopoisk', 'film', $response, 'http://www.kinopoisk.ru/level/1/film/195223/');
        $this->assertEqual($result1, $result2);

        try {
            Lms_DataParser::parse('not_exists_module', 'film', $response, '');
            $this->assertTrue(false, 'Throw Exception if module not found');
        } catch (Lms_DataParser_Exception $e) {
            $this->assertEqual($e->getMessage(), 'Module not_exists_module not found!', 'Throw Exception if module not found');
        }

        try {
            Lms_DataParser::parse('kinopoisk', 'not_exists_context', $response, '');
            $this->assertTrue(false, 'Throw Exception if action not found');
        } catch (Lms_DataParser_Exception $e) {
            $this->assertEqual($e->getMessage(), 'Context method Lms_DataParser_Kinopoisk::parseNotExistsContext not found!', 'Throw Exception if context method not found');
        }
    }

    function testParser()
    {
        $this->init();
        global $config;
        if ($config['test_enable']) {
            @list($enabledModule, $enabledTest) = explode("/", $config['test_enable']);
            @list($saveEtalonModule, $saveEtalonTest) = explode("/", $config['save_etalon']);
            @list($debugModule, $debugTest) = explode("/", $config['debug_test']);
            foreach ($config['parsers'] as $module => $tests) {
                if (!$enabledModule || ($module == $enabledModule)) {
                    foreach ($tests as $testCode => $testConfig) {
                        if (!$enabledTest || ($testCode == $enabledTest)) {
                            $filename = dirname(__FILE__) . "/dataparser/$module/$testCode/datastream.txt";
                            $dataStream = file_get_contents($filename);
                            $response = Zend_Http_Response::fromString($dataStream);
                            try {
                                $info1 = Lms_DataParser::parse($module, $testConfig['context'], $response, $testConfig['url'], Lms_DataParser::TEST_MODE);
                            } catch (Lms_DataParser_Exception $e) {
                                $info1 = null;
                            }
                            $structFilename = dirname(__FILE__) . "/dataparser/$module/$testCode/struct.txt";
                            file_put_contents($structFilename, print_r($info1, true));
                            unset($info1['version']);

                            $etalonFilename = dirname(__FILE__) . "/dataparser/$module/$testCode/etalon.data";
                            if ($config['save_etalon']
                                && (!$saveEtalonModule || ($module == $saveEtalonModule))
                                && (!$saveEtalonTest || ($testCode == $saveEtalonTest))
                            ) {
                                @mkdir(dirname($etalonFilename), 0777, true);
                                file_put_contents($etalonFilename, serialize($info1));
                                file_put_contents($structFilename, print_r($info1, true));
                            }
                            $info2 = unserialize(file_get_contents($etalonFilename));
                            //array_walk_recursive($info2, array($this, 'cp1251ToUtf'));
                            $isEqual = ($info1 == $info2);
                            $this->assertTrue($isEqual, "Module '$module' test '$testCode'");
                            if (!$isEqual
                                || ($config['debug_test']
                                    && (!$debugModule || ($module == $debugModule))
                                    && (!$debugTest || ($testCode == $debugTest)))
                            ) {
                                echo $this->renderVisualCompareArray($info1, $info2, 'Текущий парсинг', 'Эталон', isset($config['diff_only']) ? $config['diff_only'] : false);
                            }
                            /*
                             array_walk_recursive($info1, array($this, 'wordwrap'));
                             array_walk_recursive($info2, array($this, 'wordwrap'));
                             array_walk_recursive($differenceInfo1, array($this, 'wordwrap'));
                             array_walk_recursive($differenceInfo2, array($this, 'wordwrap'));
                             //Раскомментировать для дебага
                             echo "<table style='font-size:8pt;'><tr><td style='border:1px solid #000;' valign='top'><span style='color:green;'>Current</span><pre>";
                             //print_r($info1);
                             echo "</pre>";
                             echo $this->renderHighlightedArray($info1, $differenceInfo1);
                             echo "</td>";
                             echo "<td style='border:1px solid #000;' valign='top'><span style='color:green;'>Etalon</span><pre>";
                             //print_r($info2);
                             echo $this->renderHighlightedArray($info1, $differenceInfo1);
                             echo "</pre></td></tr>";
                             echo "<tr><td style='border:1px solid #000;' valign='top'><span style='color:green;'>Diff 1 2</span><pre>";
                             print_r($differenceInfo1);
                             echo "</pre></td>";
                             echo "<td style='border:1px solid #000;' valign='top'><span style='color:green;'>Diff 2 1</span><pre>";
                             print_r($differenceInfo2);
                             echo "</pre></td></tr>";
                             echo "</table>";*/
                        }
                    }
                }
            }
        }
        $stream = file_get_contents(dirname(__FILE__) . '/dataparser/kinopoisk/film1/datastream.txt');
        $response = Zend_Http_Response::fromString($stream);
    }

    private function arrayDiffAssocRecursive($array1, $array2)
    {
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key])) {
                    $difference[$key] = $value;
                } elseif (!is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->arrayDiffAssocRecursive($value, $array2[$key]);
                    if ($new_diff != FALSE) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (!array_key_exists($key, $array2) || ($array2[$key] != $value)) {
                $difference[$key] = $value;
            }
        }
        return !isset($difference) ? 0 : $difference;
    }

    private function cp1251ToUtf(&$text)
    {
        if (!is_string($text)) return;
        $text = mb_convert_encoding($text, 'UTF-8', 'CP1251');
    }

    private function wordwrap(&$text)
    {
        if (!is_string($text)) return;
        $text = wordwrap($text);
    }

    private function renderVisualCompareArray($array1, $array2, $caption1 = '1', $caption2 = '2', $diffOnly = false)
    {
        $differenceInfo1 = $this->arrayDiffAssocRecursive($array1, $array2);
        $differenceInfo2 = $this->arrayDiffAssocRecursive($array2, $array1);

        $result = '';
        $result .= "<table style='font-size:8pt; font-family:Tahoma, Verdana, Arial;'>";
        $result .= "<tr><td width='50%'>$caption1</td><td width='50%'>$caption2</td></tr>";
        $result .= "<tr>";
        $result .= "<td style='border:1px solid #000;' valign='top'>";
        $result .= $this->renderHighlightedArray($array1, $differenceInfo1, $diffOnly);
        $result .= "</td>";
        $result .= "<td style='border:1px solid #000;' valign='top'>";
        $result .= $this->renderHighlightedArray($array2, $differenceInfo2, $diffOnly);
        $result .= "</td>";
        $result .= "</tr>";
        $result .= "</table>";
        return $result;
    }

    private function renderHighlightedArray($printedArray, $highlightArray = array(), $diffOnly = false)
    {
        $result = '';
        foreach ($printedArray as $key => $value) {
            if (isset($highlightArray[$key])) {
                $renderedKey = "<span style='color:red;'>[" . $key . "]</span>";
            } else {
                $renderedKey = "[$key]";
            }
            if (is_array($value)) {
                $renderedSubArray = $this->renderHighlightedArray($value, isset($highlightArray[$key]) ? $highlightArray[$key] : array(), $diffOnly);
                if (($diffOnly && isset($highlightArray[$key])) || !$diffOnly) {
                    $result .= "$renderedKey => Array:<div style='margin-left:20px'>$renderedSubArray</div>";
                }
            } else {
                if ($value === false) {
                    $value = '<i>false</i>';
                } elseif ($value === null) {
                    $value = '<i>null</i>';
                } else {
                    $value = htmlspecialchars($value);
                }
                if (is_array($highlightArray) && array_key_exists($key, $highlightArray)) {
                    $renderedValue = "<span style='color:red;'>" . $value . "</span>";
                } else {
                    $renderedValue = $value;
                }
                if (($diffOnly && isset($highlightArray[$key])) || !$diffOnly) {
                    $result .= "$renderedKey => $renderedValue <br>";
                }
            }
            $result .= "\n";
        }
        return $result;
    }

    function init()
    {
        global $config;
        if ($config['prepare_streams']) {
            $this->adapter = new Zend_Http_Client_Adapter_Socket();
            $this->adapter->setConfig(array(
                'timeout' => 10,
                'keepalive' => false,
            ));
            @list($prepareModule, $prepareTest) = explode("/", $config['prepare_streams']);
            foreach ($config['parsers'] as $module => $tests) {
                if (!$prepareModule || ($module == $prepareModule)) {
                    foreach ($tests as $testCode => $testConfig) {
                        if (!$prepareTest || ($testCode == $prepareTest)) {
                            $filename = dirname(__FILE__) . "/dataparser/$module/$testCode/datastream.txt";
                            try {
                                $this->maxRedirects = isset($testConfig['maxredirects']) ? $testConfig['maxredirects'] : 0;
                                $dataStream = $this->getRawDataStreamByUrl($testConfig['url']);

                            } catch (Exception $e) {
                                $filename = dirname(__FILE__) . "/dataparser/$module/$testCode/lasterror.txt";
                                $dataStream = $e->getMessage();
                            }
                            @mkdir(dirname($filename), 0777, true);
                            file_put_contents($filename, $dataStream);
                        }
                    }
                }
            }
        }
    }

    private function getRawDataStreamByUrl($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $secure = parse_url($url, PHP_URL_SCHEME) == 'https';
        $port = $secure ? 443 : 80;
        $headers['Accept'] = '*/*';
        $headers['Accept-Language'] = 'ru';
        $headers['Accept-Encoding'] = 'gzip, deflate';
        $headers['User-Agent'] = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)';
        $headers['Referer'] = dirname($url);
        $headers['Host'] = $host;
        $this->adapter->connect($host, $port, $secure);
        $zendUri = Zend_Uri::factory($url);
        $zendUri->setPort($port);
        $this->adapter->write('GET', $zendUri, '1.1', $headers);
        $datastream = $this->adapter->read();
        $this->adapter->close();

        try {
            $response = Zend_Http_Response::fromString($datastream);
            if ($response->isRedirect() && $this->maxRedirects) {
                $this->maxRedirects--;
                return $this->getRawDataStreamByUrl($response->getHeader('Location'));
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        return $datastream;
    }
}

$test = new DataParserTest();
$reporter = new HTMLReporter('windows-1251');
$test->run($reporter);

if (php_sapi_name() != 'cli' && !empty($_SERVER['REMOTE_ADDR'])): ?>
  <hr>
  <form action="" method="post">
    <fieldset>
      <label><input type="radio" name="action" value="test" checked> Тестировать</label><br>
      <label><input type="radio" name="action" value="debug"> Показать данные</label><br>
      <label><input type="radio" name="action" value="prepare_streams"> Обновить исходные
        данные</label><br>
      <label><input type="radio" name="action" value="save_etalon"> Сохранить текущий парсинг как
        эталон</label><br>
    </fieldset>
    <label>
      Область:
      <input type="text" name="path" value="<?php echo empty($_POST['path'])? '/' : htmlspecialchars($_POST['path']); ?>">
      (например: imdb, kinopoisk/, kinopoisk/film1)
    </label>
    <br>
    <input type="submit" value="Выполнить"/>
  </form>
<?php endif; ?>
