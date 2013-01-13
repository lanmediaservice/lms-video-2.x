<?php
/**
 * Видео-каталог
 * (C) 2006-2009 Ilya Spesivtsev, macondos@gmail.com
 *
 * Back-offic'ные задачи
 * Интерфейс администратора
 *
 * @author Ilya Spesivtsev
 * @version 1.07
 */

require_once dirname(__DIR__) . "/app/config.php";

Lms_Application::prepare();

require_once dirname(__DIR__) . "/" . (Lms_Application::getConfig('auth', 'logon.php')?: "logon.php") ;

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
        <title>Администратор видео-каталога</title>
        <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
        <link rel="icon" type="image/png" href="img/favicon.png" />
        
        <script type="text/javascript" src="../js/prototype-1.7.0.0.js"></script>
        <script type="text/javascript" src="../js/jquery-1.6.2.min.js"></script>
        <script>
            var $j = jQuery.noConflict();
        </script>
        <script type="text/javascript" src="../js/scriptaculous/scriptaculous.js"></script>
        <script type="text/javascript" src="../js/scriptaculous/effects.js"></script>
        <script type="text/javascript" src="../jshttprequest/JsHttpRequest.js"></script>
        <script type="text/javascript" src="../js/jhr_controller.js"></script>
        <script type="text/javascript" src="../js/rsh.js?v=2"></script>
        <script type="text/javascript" src="../js/trimpath/template.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/JSAN.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS/Connector.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS/Signalable.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS/Widgets.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS/Widgets/Factory.js"></script>
        <script>
            //<![CDATA[
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

            var API_URL = '../api.php?format=ajax';
            var TEMPLATES = {};
            var SETTINGS = {};
            var REFERENCE = {};
            var LANG = 'ru';
            //less = { env: 'development' };
            JSAN.includePath = ['../js/lms-jsf', '../js', 'js'];
            var EXTERNAL_SEARCH_ENGINES = [<?php if (Lms_Application::getConfig('external_search_engines')){
                $searchEnginesArray = array();
                foreach (Lms_Application::getConfig('external_search_engines') as $searchEngine) {
                    $searchEnginesArray[] = '"' . addslashes($searchEngine) . '"';
                } 
                echo implode(',', $searchEnginesArray);
            }?>];
            //]]>
        </script>

        <script type="text/javascript" src="../js/json2.js"></script>

        <link rel="stylesheet" href="../js/jquery.plugins/fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />
        <script type="text/javascript" src="../js/jquery.plugins/fancybox/jquery.fancybox-1.3.4.js"></script>
        <script type="text/javascript" src="../js/jquery.plugins/fancybox/jquery.easing-1.3.pack.js"></script>
        <script type="text/javascript" src="../js/jquery.plugins/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>

        <script type="text/javascript" src="../js/jquery.plugins/jquery.storage.js"></script>
        <script type="text/javascript" src="../js/jquery.plugins/jquery.single_double_click.js"></script>

        <link href="../js/jquery.plugins/chosen/chosen.css" media="screen" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="../js/jquery.plugins/chosen/chosen.jquery.min.js"></script>

        <script type="text/javascript" src="../js/jquery.plugins/jquery.select-reference.js"></script>

        <script type="text/javascript" src="../js/jquery.plugins/jquery.autoresize.js"></script>
        <script type="text/javascript" src="../js/jquery.plugins/jquery.combobox.js"></script>

        <link href="../js/jquery.plugins/organize-images/organize-images.css" media="screen" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="../js/jquery.plugins/organize-images/jquery.organize-images.js"></script>

        <script type="text/javascript" src="../js/jquery.plugins/jquery.booleanize.js"></script>

        <link href="../js/jquery.plugins/json-tablize/json-tablize.css" media="screen" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="../js/jquery.plugins/json-tablize/jquery.json-tablize.js"></script>

        <script type="text/javascript" src="../js/jquery.plugins/json-matrix/jquery.json-matrix.js"></script>

        <link rel="stylesheet" href="../js/jquery.plugins/tipsy/tipsy.css" type="text/css" media="screen" />
        <script type="text/javascript" src="../js/jquery.plugins/tipsy/jquery.tipsy.js"></script>
        <script>
            $j.fn.tipsy.defaults.opacity = 1;
            $j.fn.tipsy.defaults.gcInterval = 1000;
        </script>

        <script type="text/javascript" src="../js/modernizr-1.5.min.js"></script>

        <script type="text/javascript" src="../js/lms-jsf/LMS/Widgets/Generic.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS/Widgets/BlockGeneric.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS/Widgets/LayerBox.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS/Widgets/PageIndexBox.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS/Widgets/AnchorBox.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS/Widgets/ListItemBox.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS/Widgets/UnorderedListBox.js"></script>
        <script type="text/javascript" src="../js/LMS/Ajax.js"></script>
        <script type="text/javascript" src="../js/LMS/Action.js"></script>
        <script type="text/javascript" src="../js/LMS/UI.js"></script>
        <script type="text/javascript" src="../js/LMS/Router.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS/i18n.js"></script>
        <script type="text/javascript" src="../js/LMS/i18n/ru.js"></script>
        <script type="text/javascript" src="../js/LMS/i18n/ru/Main.js"></script>
        <script type="text/javascript" src="../js/LMS/Text.js"></script>
        <script type="text/javascript" src="../js/LMS/Date.js"></script>
        <script type="text/javascript" src="../js/LMS/DateFormat.js"></script>
        <script type="text/javascript" src="../js/LMS/LiveDatetime.js"></script>
        <script type="text/javascript" src="../js/LMS/Widgets/Overlay.js"></script>
        <script type="text/javascript" src="../js/lms-jsf/LMS/Utils.js"></script>
        <script type="text/javascript">
            var ajax = new LMS.Ajax();
            ajax.setApiUrl(API_URL);
            var action = new LMS.Action();
            action.setQueryMethod(function(requestParams, callback){ajax.exec(requestParams, callback)});
            var ui = new LMS.UI();
            LMS.Connector.connect('userError', ui, 'showUserError');
            LMS.Connector.connect('userMessage', ui, 'showUserMessage');
            LMS.Connector.connect('highlightElement', ui, 'highlightElement');
            JsHttpRequest.JHRController.SysMessenger = function(text, autoHide) {
                ui.showUserError(500, text, 'warn', autoHide);
            }
            
            JsHttpRequest.JHRController.refresh = function(){
		if (!this.created) this.create();
		var el = $j('#' + this.parent_domid);
		if (el) {
                    if (this.loadings_counter>0) {
                        el.delay(1000).fadeIn(1000);
                    } else{
                        el.clearQueue().hide().css('opacity', 0.7);
                    }
		}
            }
            
            
            var router = new LMS.Router();
            
            $j.fn.organizeImages.defaults.imageProxy = '../imageproxy.php';
            $j.fn.organizeImages.defaults.loadImage = 'img/load-image.gif';
            
        </script>
        <script type="text/javascript" src="js/LMS/Cp/Action.js?v=1.2.0"></script>
        <script type="text/javascript" src="js/LMS/Cp/UI.js?v=1.2.0"></script>
        <script type="text/javascript" src="js/LMS/Cp/Incoming/UI.js?v=1.2.0"></script>
        <script type="text/javascript">
            LMS.Cp.Incoming.UI.incoming.pageSize = <?php echo Lms_Application::getConfig('incoming', 'page_size'); ?>;
            
            LMS.Action.addMethods(LMS.Cp.Action);
            LMS.UI.addMethods(LMS.Cp.UI);
            LMS.UI.addMethods(LMS.Cp.Incoming.UI);
        </script>
        <script>
            $j(document).ready(function() {
                window.ui.init();
                if ($j.browser.msie) {
                    $j('body').addClass('msie');
                }
                if ($j.browser.webkit) {
                    $j('body').addClass('webkit');
                }
                if ($j.browser.opera) {
                    $j('body').addClass('opera');
                }
                if ($j.browser.mozilla) {
                    $j('body').addClass('mozilla');
                }
                $j('#help').fancybox({
                    width: 900,
                    height: 620,
                    centerOnScroll: true
                });
            });
        </script>
        <script type="text/javascript">
            //<![CDATA[
            TEMPLATES.INCOMING = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming.jhtml'));?>";
            TEMPLATES.INCOMING_DETAILS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming-details.jhtml'));?>";
            TEMPLATES.INCOMING_DETAILS_SEARCH_RESULTS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming-details-search-results.jhtml'));?>";
            TEMPLATES.INCOMING_DETAILS_PARSED_INFO = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming-details-parsed-info.jhtml'));?>";
            TEMPLATES.INCOMING_DETAILS_FORM = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming-details-form.jhtml'));?>";
            TEMPLATES.INCOMING_DETAILS_FILES_FORM = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming-details-files-form.jhtml'));?>";
            TEMPLATES.INCOMING_DETAILS_LOCAL_SEARCH_RESULTS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/incoming-details-local-search-results.jhtml'));?>";

            TEMPLATES.TASKS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/tasks.jhtml'));?>";

            TEMPLATES.MOVIE = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/movie.jhtml'));?>";
            TEMPLATES.MOVIE_SEARCH_RESULTS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/movie-search-results.jhtml'));?>";
            TEMPLATES.MOVIE_PARSED_INFO = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/movie-parsed-info.jhtml'));?>";
            TEMPLATES.MOVIES = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/movies.jhtml'));?>";
            TEMPLATES.PERSON = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/person.jhtml'));?>";
            TEMPLATES.PERSONES = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/persones.jhtml'));?>";
            TEMPLATES.USER = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/user.jhtml'));?>";
            TEMPLATES.USERS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/users.jhtml'));?>";
            TEMPLATES.IMAGES_SEARCH_RESULTS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/images-search-results.jhtml'));?>";
            TEMPLATES.ATTACH_FILE_FORM = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/attach-file-form.jhtml'));?>";

            TEMPLATES.UPDATES_CHECK = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/updates-check.jhtml'));?>";
            TEMPLATES.UPGRADE_RESULT = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/upgrade-result.jhtml'));?>";

            TEMPLATES.SETTINGS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/settings.jhtml'));?>";
            //]]>
        </script>
        <link href="css/reset.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="css/content.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="css/layout.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="css/form.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="css/menu.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="css/icons.css" media="screen" rel="stylesheet" type="text/css" />
        <link rel="stylesheet/less" type="text/css" href="css/functions.less">
        <link rel="stylesheet/less" type="text/css" href="css/toolbar.less">
        <link rel="stylesheet/less" type="text/css" href="css/paginator.less">
        <link rel="stylesheet/less" type="text/css" href="css/tabs.less">
        <link rel="stylesheet/less" type="text/css" href="css/cp.less">
        <script type="text/javascript" src="../js/less-1.1.3.min.js"></script>        

        <link href="js/jquery-ui/css/smoothness/jquery-ui-1.8.16.custom.css" media="screen" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="js/jquery-ui/jquery-ui-1.8.16.custom.min.js"></script>
        <?php
        if (file_exists(__DIR__ . '/head.after.php')) {
            include_once(__DIR__  . '/head.after.php');
        }
        ?>
    </head>
    <body>
        <div style="display:none" id="user_message"></div>
        <div id="container">
            <div id="header">
            </div>
            <header>
                <div class="menu">
                    <ul class="float_right">
                        <li><strong><?php echo Lms_User::getUser()->getLogin();?></strong></li>
                        <li><a class="iframe" href="http://www.lanmediaservice.com/tour-frames/" target="_blank" id="help">Справка</a></li>
                        <li><a href="../" target="_blank">Каталог</a></li>
                    </ul>
                    <ul class="">
                        <?php if (Lms_Application::getConfig('topmenu_links')): ?>
                            <?php foreach (Lms_Application::getConfig('topmenu_links') as $key=>$menuItem):?>
                                <li>
                                <?php if (@$menuItem['selected']):?>
                                    <span class="selected"><?php echo $menuItem['text'];?></span>
                                <?php else:?>
                                    <a href="<?php echo htmlspecialchars($menuItem['url']);?>" target="_blank"><?php echo $menuItem['text'];?></a>
                                <?php endif;?>
                                </li>
                            <?php endforeach;?>
                        <?php endif; ?>
                    </ul>
                </div>
                <div style="clear:both;"></div>
            </header>            
            
            <div id="content">
                <div id="content-indent">
                    <div class="control-panel" id="control_panel">
                        <div class="tab-selector">
                            <ul>
                                <li class="incoming"><a href="#/incoming">Поступления</a></li>
                                <li class="movies" ><a href="#/movies">Фильмы</a></li>
                                <li class="persones" ><a href="#/persones">Персоналии</a></li>
                                <li class="users" ><a href="#/users">Пользователи</a></li>
                                <li class="settings" ><a href="#/settings">Настройки</a></li>
                                <li class="utils" ><a href="#/utils">Утилиты</a></li>
                                <li class="updates" ><a href="#/updates">Обновления</a></li>
                                <li class="tasks" id="tab-caption-tasks" style="display: none;"><a href="#/tasks">Фоновые задания</a></li>
                            </ul>
                        </div>
                        <div class="tab">
                            <div class="incoming" style="display: none;" id="incoming">
                                <div class="navigation">
                                    <div class="panel" data-mode="collapsed">
                                        <div class="group-operations" style="display: none; float: left">
                                            <a onclick="window.ui.incoming.hideSelected()" class="toolbar-button"><span>Скрыть</span></a>
                                            <a onclick="window.ui.incoming.unhideSelected()" class="toolbar-button"><span>Показать</span></a>
                                        </div>
                                        <a onclick="window.ui.incoming.autoParseFiles()" class="toolbar-button"><span>Автоанализ файлов</span></a>
                                        <a onclick="window.ui.incoming.autoSearch()" class="toolbar-button"><span>Автопоиск</span></a>
                                        <a onclick="window.ui.incoming.autoParse()" class="toolbar-button"><span>Автопарсинг</span></a>
                                        <a onclick="window.ui.incoming.autoImport()" class="toolbar-button"><span>Автоимпорт</span></a>
                                        
                                        <a class="filter-hidden checkbox" data-checked="0" onclick="setTimeout(function(){window.ui.incoming.refresh();}, 0)">Показывать скрытые</a>
                                        <a onclick="window.ui.incoming.refresh(true)" class="toolbar-button"><span>Обновить</span></a>
                                    </div>
                                    <div class="paginator" id="paginator_incoming"></div>
                                </div>
                                <div class="incoming-wrapper"></div>
                            </div>
                            <div class="tasks" style="display: none;" id="tasks">
                                <div class="navigation">
                                    <div class="panel">
                                       <a onclick="window.action.resetFilesTasksTries();window.action.getCurrentStatus();" class="toolbar-button"><span>Сбросить счетчики попыток</span></a>
                                       <a onclick="if (confirm('Отменить все задания?')) {window.action.clearFilesTasks();window.action.getCurrentStatus();}" class="toolbar-button"><span>Отменить все</span></a>
                                       <!--<a class="tasks-autoupdate-switch checkbox" data-checked="1">Автообновление</a>-->
                                       <a onclick="window.action.getCurrentStatus();" class="toolbar-button"><span>Обновить</span></a>
                                    </div>
                                </div>
                                <div id="tasks-list"></div>
                            </div>
                            <div class="movies" style="display: none;" id="movies">
                                <div class="navigation">
                                    <div class="filter panel" data-mode="collapsed">
                                        <a class="filter-switcher" onclick="window.ui.switchFilter($j(this).parents('.filter'));">Фильтр</a>
                                        <div class="filter-form clearfix">
                                            <div class="filter-block">
                                                Название содержит:<br>
                                                <input class="form filter-name" type="text" style="width: 120px">
                                            </div>
                                            <div class="filter-block">
                                                Качество видео:<br>
                                                <select class="form filter-quality" style="width: 120px">
                                                    <option value="">Любое</option>
                                                    <option value="DVDRip">DVDRip (7812)</option><option value="HDRip">HDRip (1345)</option><option value="BDRip">BDRip (1272)</option><option value=""> (1201)</option>
                                                </select>
                                            </div>
                                            <div class="filter-block">
                                                Озвучивание:<br>
                                                <select class="form filter-translation" style="width: 120px">
                                                    <option value="">Любое</option>
                                                    <option value="Профессиональный многоголосый">Профессиональный многоголосый (3343)</option><option value=""> (2479)</option><option value="На языке оригинала">На языке оригинала (1805)</option><option value="Дубляж">Дубляж (905)</option><option value="Профессиональный одноголосый">Профессиональный одноголосый (653)</option><option value="Любительский одноголосый">Любительский одноголосый (630)</option><option value="Профессиональный двухголосый">Профессиональный двухголосый (604)</option><option value="Любительский многоголосый">Любительский многоголосый (219)</option><option value="Профессиональный многоголосый [лицензия]">Профессиональный многоголосый [лицензия] (211)</option><option value="Дубляж [лицензия]">Дубляж [лицензия] (182)</option><option value="Любительский двухголосый">Любительский двухголосый (179)</option><option value="не требуется">не требуется (130)</option><option value="Профессиональное (многоголосое) [лицензия]">Профессиональное (многоголосое) [лицензия] (96)</option><option value="Авторский одноголосый">Авторский одноголосый (95)</option><option value="Субтитры">Субтитры (64)</option><option value="Одноголосый">Одноголосый (62)</option><option value="Профессиональный многоголосый + оригинал">Профессиональный многоголосый + оригинал (57)</option><option value="Любительский (одноголосый)">Любительский (одноголосый) (52)</option><option value="1001 Cinema">1001 Cinema (51)</option><option value="Дублированное [лицензия]">Дублированное [лицензия] (49)</option><option value="Дубляж + Оригинал">Дубляж + Оригинал (30)</option><option value="Одноголосый закадровый">Одноголосый закадровый (29)</option><option value="Любительское (одноголосое)">Любительское (одноголосое) (25)</option><option value="Не определен">Не определен (25)</option><option value="Профессиональный (многоголосый) [лицензия]">Профессиональный (многоголосый) [лицензия] (23)</option><option value="Кубик в кубе">Кубик в кубе (21)</option><option value="LostFilm.TV">LostFilm.TV (17)</option><option value="Гоблин (правильный)">Гоблин (правильный) (16)</option><option value="Профессиональный двухголосый + Оригинал">Профессиональный двухголосый + Оригинал (15)</option><option value="Профессиональный многоголосый (LostFilm)">Профессиональный многоголосый (LostFilm) (15)</option><option value="На языке оригинала [Лицензия]">На языке оригинала [Лицензия] (14)</option><option value="Lostfilm">Lostfilm (14)</option>
                                                </select>
                                            </div>
                                            <div class="filter-block" style="padding-top: 28px;">
                                                <a class="checkbox filter-sortbyname" data-checked="0">Сортировать по названию</a>
                                                <a class="checkbox filter-hidden" data-checked="0">Только скрытые</a>
                                                <a class="toolbar-button" onclick="window.ui.movies.refresh()"><span>Применить</span></a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="paginator" id="paginator_movies"></div>
                                </div>
                                <div class="two-panel">
                                    <div id="movies-list" class="list"></div>
                                    <div id="movie" class="item" data-mode="show-main-form"></div>
                                </div>
                            </div>
                            <div class="persones" style="display: none;" id="persones">
                                <div class="navigation">
                                    <div class="filter panel" data-mode="collapsed">
                                        <a class="filter-switcher" onclick="window.ui.switchFilter($j(this).parents('.filter'));">Фильтр</a>
                                        <div class="filter-form clearfix">
                                            <div class="filter-block">
                                                Имя содержит:<br>
                                                <input class="form filter-name" type="text" style="width: 120px">
                                            </div>
                                            <div class="filter-block" style="padding-top: 28px;">
                                                <a class="filter-sortbyname checkbox" data-checked="0">Сортировать по имени</a>
                                                <a class="toolbar-button" onclick="window.ui.persones.refresh()"><span>Применить</span></a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="paginator" id="paginator_persones"></div>
                                </div>
                                <div class="two-panel">
                                    <div id="persones-list" class="list"></div>
                                    <div id="person" class="item" data-mode="show-main-form"></div>
                                </div>
                            </div>
                            <div class="users" style="display: none;" id="users">
                                <div class="navigation">
                                    <div class="filter panel" data-mode="collapsed">
                                        <a class="filter-switcher" onclick="window.ui.switchFilter($j(this).parents('.filter'));">Фильтр</a>
                                        <div class="filter-form clearfix">
                                            <div class="filter-block">
                                                Логин содержит:<br>
                                                <input class="form filter-login" type="text" style="width: 120px">
                                            </div>
                                            <div class="filter-block">
                                                IP содержит:<br>
                                                <input class="form filter-ip" type="text" style="width: 120px">
                                            </div>
                                            <div class="filter-block" style="padding-top: 28px;">
                                                <a class="filter-sortbyname checkbox" data-checked="0">Сортировать по логину</a>
                                                <a class="toolbar-button" onclick="window.ui.users.refresh()"><span>Применить</span></a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="paginator" id="paginator_users"></div>
                                </div>
                                <div class="two-panel">
                                    <div id="users-list" class="list"></div>
                                    <div id="user" class="item" data-mode="show-main-form"></div>
                                </div>
                            </div>
                            <div class="settings" style="display: none;" id="settings">
                            </div>
                            <div class="utils" style="display: none;" id="utils">
                                <div class="utility ratings-update" data-mode="collapse">
                                    <div class="title" title="Синхронизация и обновление рейтингов IMDb и KinoPoisk">
                                        <a class="minibutton start" onclick="window.ui.updateRatings();$j(this).parent().parent().attr('data-mode', 'expanded')"><span>Старт</span></a>
                                        <a class="minibutton expand" onclick="$j(this).parent().parent().attr('data-mode', 'expanded')"><span>+</span></a>
                                        <a class="minibutton collapse" onclick="$j(this).parent().parent().attr('data-mode', 'collapse')"><span>-</span></a>
                                        Синхронизация внешних рейтингов
                                    </div>
                                    <div class="table-wrapper">
                                        <table class="silver">
                                            <thead>
                                                <tr>
                                                    <th>Дата запуска</th>
                                                    <th>Дата завершения</th>
                                                    <th>Сообщение</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="started_at"></td>
                                                    <td class="ended_at"></td>
                                                    <td class="message"></td>
                                                    <td class="has_report"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div><br>
                                <div class="utility ratings-local-update" data-mode="collapse">
                                    <div class="title" title="Пересчет локальных рейтингов при изменении параметров">
                                        <a class="minibutton start" onclick="window.ui.updateLocalRatings(); $j(this).parent().parent().attr('data-mode', 'expanded')"><span>Старт</span></a>
                                        <a class="minibutton expand" onclick="$j(this).parent().parent().attr('data-mode', 'expanded')"><span>+</span></a>
                                        <a class="minibutton collapse" onclick="$j(this).parent().parent().attr('data-mode', 'collapse')"><span>-</span></a>
                                        Пересчет локальных рейтингов
                                    </div>
                                    <div class="table-wrapper">
                                        <table class="silver">
                                            <thead>
                                                <tr>
                                                    <th>Дата запуска</th>
                                                    <th>Дата завершения</th>
                                                    <th>Сообщение</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="started_at"></td>
                                                    <td class="ended_at"></td>
                                                    <td class="message"></td>
                                                    <td class="has_report"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div><br>
                                <div class="utility persones-fix" data-mode="collapse">
                                    <div class="title" title="Удаление неиспользуемых персоналий и определение и объединение дубликатов">
                                        <a class="minibutton start" onclick="window.ui.fixPersones();$j(this).parent().parent().attr('data-mode', 'expanded')"><span>Старт</span></a>
                                        <a class="minibutton expand" onclick="$j(this).parent().parent().attr('data-mode', 'expanded')"><span>+</span></a>
                                        <a class="minibutton collapse" onclick="$j(this).parent().parent().attr('data-mode', 'collapse')"><span>-</span></a>
                                        Очистка и объединение персоналий
                                    </div>
                                    <div class="table-wrapper">
                                        <table class="silver">
                                            <thead>
                                                <tr>
                                                    <th>Дата запуска</th>
                                                    <th>Дата завершения</th>
                                                    <th>Сообщение</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="started_at"></td>
                                                    <td class="ended_at"></td>
                                                    <td class="message"></td>
                                                    <td class="has_report"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div><br>
                                <div class="utility files-check" data-mode="collapse">
                                    <div class="title" title="Сканирование и исправление файлов">
                                        <a class="minibutton start" onclick="window.ui.checkFiles();$j(this).parent().parent().attr('data-mode', 'expanded')"><span>Старт</span></a>
                                        <a class="minibutton expand" onclick="$j(this).parent().parent().attr('data-mode', 'expanded')"><span>+</span></a>
                                        <a class="minibutton collapse" onclick="$j(this).parent().parent().attr('data-mode', 'collapse')"><span>-</span></a>
                                        Сканирование файлов
                                    </div>
                                    <div class="table-wrapper">
                                        <table class="silver">
                                            <thead>
                                                <tr>
                                                    <th>Дата запуска</th>
                                                    <th>Дата завершения</th>
                                                    <th>Сообщение</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="started_at"></td>
                                                    <td class="ended_at"></td>
                                                    <td class="message"></td>
                                                    <td class="has_report"></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="4">
                                                        На основании последнего сканирования:<br>
                                                        <a class="minibutton" onclick="if (confirm('Исправить ссылки на перемещенные файлы (см. отчет к последнему сканированию)?')) {window.action.relocateLostFiles();}" title="Исправить ссылки на перемещенные файлы"><span>Исправить ссылки</span></a>
                                                        &nbsp;&nbsp;<a class="minibutton" onclick="if (confirm('Скрыть фильмы с битыми файлами (см. отчет к последнему сканированию)?')) {window.action.hideBrokenMovies();}" title="Скрыть фильмы с битыми файлами"><span>Скрыть фильмы</span></a>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="updates" style="display: none;" id="updates">
                                <div class="navigation">
                                    <div class="panel">
                                       <a onclick="window.action.checkUpdates();" class="toolbar-button"><span>Проверить обновления</span></a>
                                        <div class="links">
                                            <a href="http://www.lanmediaservice.com/" target="_blank">Сайт ЛанМедиаСервис</a>
                                            <a href="http://forum.lanmediaservice.com/" target="_blank">Форум</a>
                                            <a href="http://support.lanmediaservice.com/" target="_blank">Техническая поддержка</a>
                                            <a href="https://github.com/lanmediaservice/lms-video-2.x" target="_blank">Репозиторий GitHub</a>
                                        </div>
                                    </div>
                                </div>
                                <div id="updates-info"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer>
                &copy; ООО «ЛанМедиаСервис», 2006&ndash;<?php echo date('Y');?> <br>
            </footer>
        </div>
        <div id="add_image" style="display:none" title="Добавить изображение"  data-mode="by-search">
            <div class="ai-form">
                <ul class="ai-tab-selector">
                    <li class="ai-tab-caption by-search"><a onclick="$j('#add_image').attr('data-mode', 'by-search')">Поиск в Google Images</a></li>
                    <li class="ai-tab-caption by-url"><a onclick="$j('#add_image').attr('data-mode', 'by-url')">Прямая ссылка</a></li>
                </ul>
                <div class="ai-tab by-search" class="by-search">
                    <input type="text" class="form query" style="width:200px" value="16 blocks">
                    + <input type="text" class="form keyword" value="poster" title="дополнительное ключевое слово" style="width:100px">
                    &nbsp;/&nbsp; <select class="form type" style="width:120px" >
                        <option value="">любой</option>
                        <option value="vertical">вертикальный</option>
                        <option value="horizontal">горизонтальный</option>
                    </select>
                    <a class="minibutton" onclick="window.ui.beginSearchGoogleImages()"><span>Найти</span></a>
                    <div class="search-results"></div>
                </div>
                <div class="ai-tab by-url" class="by-url">
                    URL: <input type="text" class="form url">
                    <a class="minibutton" onclick="var input=$j(this).parent().find('.form.url'); window.ui.addImage(input.val()); input.val(''); $j('#add_image').dialog('close');"><span>Добавить</span></a>
                </div>
            </div>
        </div>
        <div id="research_kinopoisk" style="display:none" title="Поиск фильма">
            <div class="rk-form">
                <div class="search-box-form">
                    <input type="text" class="form query" style="width:350px" value="Кикбоксер 4">
                    <a class="minibutton" onclick="window.ui.movies.beginSearchKinopoiskMovie()"><span>Найти</span></a>
                </div>
                <div class="search-results"></div>
            </div>
            <div class="parsed-info"></div>
        </div>
        <div id="attach_file" style="display:none" title="Присоединение/замена файлов" data-mode="single" data-delete="0">
            <div class="af-form">
            </div>
        </div>
        <div id="view_report" style="display:none" title="Просмотр отчета">
            <div class="report">
            </div>
        </div>
        <div id="JHRControllerLoaderBox" style="display: none"><img src="img/wait.gif"></div>
    </body>
</html>
