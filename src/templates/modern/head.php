<script type="text/javascript">
    //<![CDATA[
    var API_URL = 'api.php?format=ajax';
    var TEMPLATES = {};
    var SETTINGS = {};
    var LANG = 'ru';
    less = { env: 'development' };
    JSAN.includePath = ['js/lms-jsf', 'js'];
    SETTINGS.DOWNLOAD_DEFAULTS = <?php echo Zend_Json::encode(Lms_Application::getConfig('download', 'defaults'));?>;
    SETTINGS.DOWNLOAD_PLAYER = {
        SELECTABLE : <?php echo count(array_filter(Lms_Application::getConfig('download', 'players', 'selectable')))? 1 : 0; ?>,
        DEFAULT: "<?php echo Lms_Application::getConfig('download', 'players', 'default');?>"
    };
    //]]>
</script>
<link rel="stylesheet" href="<?php echo "templates/" . Lms_Application::getConfig('template') . "/css/reset.css" ?>">
<link rel="stylesheet" href="<?php echo "templates/" . Lms_Application::getConfig('template') . "/css/content.css?v=20" ?>">
<link rel="stylesheet" href="<?php echo "templates/" . Lms_Application::getConfig('template') . "/css/icons.css?v=20" ?>">
<link rel="stylesheet" href="<?php echo "templates/" . Lms_Application::getConfig('template') . "/css/layout.css?v=20" ?>">
<link rel="stylesheet" href="<?php echo "templates/" . Lms_Application::getConfig('template') . "/css/form.css" ?>">
<link rel="stylesheet" href="<?php echo "templates/" . Lms_Application::getConfig('template') . "/css/menu.css" ?>">
<link rel="stylesheet" href="<?php echo "templates/" . Lms_Application::getConfig('template') . "/css/overlay.css" ?>">

<script type="text/javascript" src="js/json2.js"></script>

<link rel="stylesheet" href="js/jquery.plugins/fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />
<script type="text/javascript" src="js/jquery.plugins/fancybox/jquery.fancybox-1.3.4.js"></script>
<script type="text/javascript" src="js/jquery.plugins/fancybox/jquery.easing-1.3.pack.js"></script>
<script type="text/javascript" src="js/jquery.plugins/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>

<link rel="stylesheet" href="js/jquery.plugins/tipsy/tipsy.css" type="text/css" media="screen" />
<script type="text/javascript" src="js/jquery.plugins/tipsy/jquery.tipsy.js"></script>
<script>
    $j.fn.tipsy.defaults.opacity = 1;
    $j.fn.tipsy.defaults.gcInterval = 1000;
</script>

<script type="text/javascript" src="js/jquery.plugins/jquery.placeholder.min.js"></script>
<script>
    $j(document).ready(function(){ 
        $j('input[placeholder], textarea[placeholder]').placeholder();
    })
</script>

<script type="text/javascript" src="js/jquery.plugins/jquery.storage.js"></script>

<script language="JavaScript" src="js/modernizr-1.5.min.js"></script>

<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/Generic.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/BlockGeneric.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/LayerBox.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/PageIndexBox.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/AnchorBox.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/ListItemBox.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Widgets/UnorderedListBox.js"></script>
<script language="JavaScript" src="js/LMS/Ajax.js"></script>
<script language="JavaScript" src="js/LMS/Action.js"></script>
<script language="JavaScript" src="js/LMS/UI.js?v=20"></script>
<script language="JavaScript" src="js/LMS/Router.js?v=20"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/i18n.js"></script>
<script language="JavaScript" src="js/LMS/i18n/ru.js"></script>
<script language="JavaScript" src="js/LMS/i18n/ru/Main.js"></script>
<script language="JavaScript" src="js/LMS/Text.js"></script>
<script language="JavaScript" src="js/LMS/Date.js"></script>
<script language="JavaScript" src="js/LMS/DateFormat.js"></script>
<script language="JavaScript" src="js/LMS/LiveDatetime.js"></script>
<script language="JavaScript" src="js/LMS/Widgets/Overlay.js"></script>
<script language="JavaScript" src="js/lms-jsf/LMS/Utils.js"></script>
<script type="text/javascript">
    var ajax = new LMS.Ajax();
    ajax.setApiUrl(API_URL);
    var action = new LMS.Action();
    action.setQueryMethod(function(requestParams, callback){ajax.exec(requestParams, callback)});
    var ui = new LMS.UI();
    LMS.Connector.connect('userError', ui, 'showUserError');
    LMS.Connector.connect('userMessage', ui, 'showUserMessage');
    LMS.Connector.connect('highlightElement', ui, 'highlightElement');
    JsHttpRequest.JHRController.SysMessenger = function(text) {
        ui.showUserError(500, text, 'warn', true);
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
</script>
<script language="JavaScript" src="js/LMS/Video/Action.js?v=2.0.0"></script>
<script language="JavaScript" src="js/LMS/Video/UI.js?v=2.0.0"></script>
<script type="text/javascript">
    LMS.Action.addMethods(LMS.Video.Action);
    LMS.UI.addMethods(LMS.Video.UI);
    LMS.Connector.connect('drawCatalog', ui, 'drawCatalog');
    LMS.Connector.connect('drawBookmarks', ui, 'drawBookmarks');
    LMS.Connector.connect('drawGenres', ui, 'drawGenres');
    LMS.Connector.connect('drawCountries', ui, 'drawCountries');
    LMS.Connector.connect('drawLastComments', ui, 'drawLastComments');
    LMS.Connector.connect('drawLastRatings', ui, 'drawLastRatings');
    LMS.Connector.connect('drawRandomMovie', ui, 'drawRandomMovie');
    LMS.Connector.connect('drawPopMovies', ui, 'drawPopMovies');
    LMS.Connector.connect('drawMovie', ui, 'drawMovie');
    LMS.Connector.connect('drawMoviePerson', ui, 'drawMoviePerson');
    LMS.Connector.connect('drawComments', ui, 'drawComments');
    LMS.Connector.connect('drawSuggestion', ui, 'drawSuggestion');
    LMS.Connector.connect('drawBestsellers', ui, 'drawBestsellers');
    LMS.Connector.connect('drawSearch', ui, 'drawSearch');
    LMS.Connector.connect('drawPerson', ui, 'drawPerson');
    LMS.Connector.connect('unstarBookmark', ui, 'unstarBookmark');
    LMS.Connector.connect('starBookmark', ui, 'starBookmark');
    LMS.Connector.connect('updateRating', ui, 'updateRating');
    LMS.Connector.connect('postDeleteComment', ui, 'postDeleteComment');
    LMS.Connector.connect('postChangePassword', ui, 'postChangePassword');
</script>
<script>
    function Init() {
        window.ui.init();
    }    
</script>
<script type="text/javascript">
    //<![CDATA[
    TEMPLATES.CATALOG = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/catalog.jhtml'));?>";
    TEMPLATES.BOOKMARKS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/bookmarks.jhtml'));?>";
    TEMPLATES.RECENTLY_VIEWED = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/recently-viewed.jhtml'));?>";
    TEMPLATES.LAST_COMMENTS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/last-comments.jhtml'));?>";
    TEMPLATES.LAST_RATINGS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/last-ratings.jhtml'));?>";
    TEMPLATES.RANDOM_FILM = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/random-film.jhtml'));?>";
    TEMPLATES.POP_FILMS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/pop-films.jhtml'));?>";
    TEMPLATES.FILM = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/film.jhtml'));?>";
    TEMPLATES.PERSON = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/person.jhtml'));?>";
    TEMPLATES.FILM_COMMENTS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/film-comments.jhtml'));?>";
    TEMPLATES.SEARCH_SUGGESTION = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/search-suggestion.jhtml'));?>";
    TEMPLATES.BESTSELLERS = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/bestsellers.jhtml'));?>";
    TEMPLATES.SEARCH = "<?php echo escapeJs(file_get_contents(dirname(__FILE__) . '/jhtml/search.jhtml'));?>";
    //]]>
</script>
<script src="<?php echo "templates/" . Lms_Application::getConfig('template') . "/player/swfobject.js" ?>"></script>

<?php
if (file_exists(dirname(__FILE__) . '/head.after.php')) {
    include_once(dirname(__FILE__) . '/head.after.php');
}
?>