<?php
/**
 * Видео-каталог
 * (C) 2006-2009 Ilya Spesivtsev, macondos@gmail.com
 *
 * Интерфейс видео-каталога
 *
 * @author Ilya Spesivtsev 
 * @version 1.07
 */
header('Expires: -1');
require_once __DIR__ . "/app/config.php";

Lms_Application::prepare();

require_once __DIR__ . "/" . (Lms_Application::getConfig('auth', 'logon.php')?: "logon.php") ;

function escapeJs($content)
{
    $content = addslashes($content);
    $content = str_replace(array("\r","\n"), array("\\r","\\n"), $content);
    return $content;
}
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo Lms_Application::getConfig('title')?: "Видео-каталог"; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link rel="alternate" type="application/rss+xml" title="Последние поступления" href="rss_films.php" />
<?php $lessFile = __DIR__ . "/templates/" . Lms_Application::getConfig('template') . "/css/styles.less"; ?>
<link rel="stylesheet/less" type="text/css" href="<?php echo "templates/" . Lms_Application::getConfig('template') . "/css/styles.less?v=" . filemtime($lessFile); ?>">
<script language="JavaScript" src="js/less-1.1.3.min.js"></script>
<?php 
    $favicon = "templates/" . Lms_Application::getConfig('template') . "/img/favicon.ico";
    if (file_exists($favicon)): ?>
        <link rel="shortcut icon" type="image/x-icon" href="<?php echo $favicon; ?>" />
<?php endif; ?>
        
<script language="JavaScript" src="js/prototype-1.7.0.0.js"></script>
<script language="JavaScript" src="js/jquery-1.6.2.min.js"></script>
<script>
    var $j = jQuery.noConflict();
</script>
<script language="JavaScript" src="js/scriptaculous/scriptaculous.js"></script>
<script language="JavaScript" src="js/scriptaculous/effects.js"></script>
<script language="JavaScript" src="jshttprequest/JsHttpRequest.js"></script>
<script language="JavaScript" src="js/jhr_controller.js"></script>
<script language="JavaScript" src="js/rsh.js?v=4"></script>
<script language="JavaScript" src="js/trimpath/template.js"></script>
<script language="JavaScript" src="js/lms-jsf/JSAN.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Connector.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Signalable.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/Factory.js"></script>
<script>
    window.dhtmlHistory.create({
        toJSON: function(o) {
            return Object.toJSON(o);
        },
        fromJSON: function(s) {
            return s.evalJSON();
        }
    }); 
            
    JSAN.includePath   = ['js/lms-jsf'];
    JSAN.errorLevel = "warn";
    JSAN.require('LMS.Widgets.Factory'); 
    //Константы
    var USER_GROUP =  <?php echo $user->getUserGroup(); ?>;
    var DEFAULT_FAVICON = "<?php echo $favicon; ?>";
    var SITE_URL = "<?php echo Lms_Application::getConfig('siteurl'); ?>";
    var SITE_TITLE = "<?php echo Lms_Application::getConfig('title')?: "Видео-каталог"; ?>";
    var TEMPLATE = "<?php echo Lms_Application::getConfig('template'); ?>";

    var uid = <?php echo $user->getId(); ?>;
    
    function setCookie (name, value, expires, path, domain, secure) {
        document.cookie = name + "=" + escape(value) +
        ((expires) ? "; expires=" + expires : "") +
        ((path) ? "; path=" + path : "") +
        ((domain) ? "; domain=" + domain : "") +
        ((secure) ? "; secure" : "");
    }

    function getCookie(name) {
        var dc = document.cookie;
        var prefix = name + "=";
        var begin = dc.indexOf("; " + prefix);
        if (begin == -1) {
            begin = dc.indexOf(prefix);
            if (begin != 0) return null;
        } else {
            begin += 2;
        }
        var end = document.cookie.indexOf(";", begin);
        if (end == -1) {
            end = dc.length;
        }
        return unescape(dc.substring(begin + prefix.length, end));
    }

    $j(document).ready(function() {
        window.ui.init();
    });
</script>
    <?php 
        $headFile = dirname(__FILE__) . "/templates/" . Lms_Application::getConfig('template') . "/head.php";
        if (file_exists($headFile)) {
            require_once $headFile;
        }
    ?>
</head>
<!--[if lt IE 7 ]> <body class="ie6"> <![endif]-->
<!--[if IE 7 ]>    <body class="ie7"> <![endif]-->
<!--[if IE 8 ]>    <body class="ie8"> <![endif]-->
<!--[if IE 9 ]>    <body class="ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <body> <!--<![endif]-->
    
<div id="sysmessagebox" style="margin:0px;padding:5px;border:1px solid silver; background-color:#F5F5C0; width:100%; display:none;">
<div style='float:right;'><a href='javascript:Hide("sysmessagebox")'>Закрыть</a></div>
<span id="sysmessage"></span>
</div>

<div id="messagebox" style="margin:0px;padding:5px;border:1px solid silver; background-color:#F5F5C0; width:100%; display:none;">
<div style='float:right;'><a href='javascript:Hide("messagebox")'>Закрыть</a></div>
<span id="message"></span>
</div>
<?php require_once "templates/" . Lms_Application::getConfig('template') . "/main.php"; ?>
</body>
</html>
<?php Lms_Application::close();?>
