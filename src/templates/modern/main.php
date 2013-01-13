<div id="container">
    <?php require_once "header.php"; ?>
    <div id="main">
        <div id="toolbar" class="toolbar">
            <ul class="breadcrumb">
                <li class="first-child">
                    <a href="#" title="В начало"><span class="icon home"></span></a>
                </li>
                <li>
                    <a id="breadcrumb_level_2" onclick="window.ui.breadcrumbLevel2Handler()">Каталог</a>
                </li>
            </ul>
            <?php if ($user->isAllowed('movie', 'moderate')): ?>
            <ul class="built-in clickable" id="movie_moder_menu_item">
                <li>
                    <a onclick="window.ui.showMovieModerMenu();" title="Редактирование"><span class="icon change"></span></a>
                    <div id="movie_moder_wrapper" class="" style="display:none">
                        <table>
                            <tr>
                                <td>Качество: </td>
                                <td>
                                    <div class="selectbox-wrapper">
                                        <input id="quality_select" type="text" autocomplete="off">
                                        <select id="quality_options" style="display: none" size="15">
                                            <option>
                                                <?php echo implode("</option><option>", Lms_Application::getConfig('quality_options')); ?>
                                            </option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Перевод:</td>
                                <td>
                                    <div class="selectbox-wrapper">
                                        <input id="translate_select" type="text" autocomplete="off">
                                        <select id="translate_options" style="display: none" size="15">
                                            <option>
                                                <?php echo implode("</option><option>", Lms_Application::getConfig('translation_options')); ?>
                                            </option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td style="text-align: right"><a id="movie_moder_edit_button" class="minibutton" target="_blank"><span>Редактировать</span></a></td>
                            </tr>
                        </table>
                    </div>
                </li>
            </ul>
            <script>
            (function( $ ){
                $.fn.comboBox = function() {
                    this.each(function() {
                        var timeout;
                        var input = $('input', $(this));
                        var select = $('select', $(this));
                        input.click(function(){
                            select.show();
                            if (timeout) clearTimeout(timeout);
                        }).focus(function(){
                            select.show();
                            if (timeout) clearTimeout(timeout);
                        }).blur(function(){
                            timeout = setTimeout(function(){
                                select.hide();
                            }, 100);
                        });
                        select.focus(function(){
                            if (timeout) clearTimeout(timeout);
                        }).click(function(){
                            input.val(select.val()).focus();
                            input.change();
                            setTimeout(function(){
                                select.hide();
                            }, 100);
                        }).blur(function(){
                            timeout = setTimeout(function(){
                                select.hide();
                            }, 100);
                        });
                    });
                };
            })(jQuery); 
            $j(document).ready(function() {
                $j('.selectbox-wrapper').comboBox();
            });
            </script>            
            <?php endif; ?>
            <ul class="built-in clickable" id="sort_menu_item">
                <li>
                    <a onclick="window.ui.showSortMenu();" title="Сортировка"><span id="sort">Последние</span> &#9660;</a>
                    <ul id="sort_wrapper" class="right" style="display:none">
                        <li><a onclick="window.ui.setOrder(0)" data-order="0" data-dir="desc" data-short-text="Последние">по дате добавления</a></li>
                        <li><a onclick="window.ui.setOrder(1)" data-order="1" data-dir="desc" data-short-text="Новинки">по году выпуска</a></li>
                        <li><a onclick="window.ui.setOrder(2)" data-order="2" data-dir="desc" data-short-text="Лучшие (IMDb)">по рейтингу IMDb</a></li>
                        <li><a onclick="window.ui.setOrder(9)" data-order="9" data-dir="desc" data-short-text="Лучшие (KinoPoisk)">по рейтингу KinoPoisk</a></li>
                        <li><a onclick="window.ui.setOrder(3)" data-order="3" data-dir="desc" data-short-text="Лучшие (локально)">по локальному рейтингу</a></li>
                        <li><a onclick="window.ui.setOrder(4)" data-order="4" data-dir="desc" data-short-text="Лучшие (мои)">по персональному рейтингу</a></li>
                        <li><a onclick="window.ui.setOrder(8)" data-order="8" data-dir="desc" data-short-text="В центре внимания">по относительной популярности</a></li>
                        <li><a onclick="window.ui.setOrder(6)" data-order="6" data-dir="desc" data-short-text="Хиты">по абсолютной популярности</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="built-in clickable" id="countries_menu_item">
                <li>
                    <a onclick="window.ui.showCountries();" title="Страна"><span id="country">Любая страна</span> &#9660;</a>
                    <div id="countries_wrapper" class="filter-wrapper" style="display:none">
                        <ul id="countries" class="filter pop"></ul>
                        <ul id="countries_all" class="filter all" style="display:none"></ul>
                        <a id="countries_switcher" class="switcher" onclick="window.ui.switchFilter(this)" style="display:none" data-default-text="Показать все" data-back-text="Назад">Показать все</a>
                    </div>
                </li>
            </ul>
            <ul class="built-in clickable" id="genres_menu_item">
                <li>
                    <a onclick="window.ui.showGenres();" title="Жанр"><span id="genre">Любой жанр</span> &#9660;</a>
                    <div id="genres_wrapper" class="filter-wrapper" style="display:none">
                        <ul id="genres" class="filter pop"></ul>
                        <ul id="genres_all" class="filter all" style="display:none"></ul>
                        <a id="genres_switcher" class="switcher" onclick="window.ui.switchFilter(this)" style="display:none" data-default-text="Показать все" data-back-text="Назад">Показать все</a>
                    </div>
                </li>
            </ul>
        </div>
        <div id="main_wrapper" class="wrapper">
            <div class="sidebar a">
                <div id="random_movie" class="inside random-movie"></div>
                <div id="pop_movies" class="inside"></div>
                <div id="last_comments" class="inside"></div>
                <div id="last_ratings" class="inside"></div>
            </div>  
            <div class="content" id="catalog_wrapper">
                <div class="paginator" id="paginator"></div>
                <div id="catalog" ></div>
            </div>  
            <div class="content" id="bestsellers"></div>
            <div class="content" id="search_results"></div>
        </div>
        <div id="movie_wrapper" class="wrapper"></div>
        <div id="person_wrapper" class="wrapper"></div>
        <div id="settings_wrapper" class="wrapper">
            <div class="sidebar a">
                <ul class="menu-vertical">
                    <?php if ($user->getUserGroup()!=0): ?>
                        <li class="menu-item selected" data-page="password-change"><a href="#/settings/page/password-change">Смена пароля</a></li>
                    <?php endif;?>
                    <?php if (count(array_filter(Lms_Application::getConfig('download', 'selectable')))): ?>
                        <li class="menu-item" data-page="links"><a href="#/settings/page/links">Ссылки</a></li>
                    <?php endif; ?>
                    <?php if (Lms_Application::getConfig('download', 'smb') 
                            && Lms_Application::getConfig('download', 'modes', $user->getMode(), 'smb') 
                            && (Lms_Application::getConfig('download', 'players', 'selectable') 
                                && count(array_filter(Lms_Application::getConfig('download', 'players', 'selectable'))))
                    ): ?>
                        <li class="menu-item" data-page="videoplayer"><a href="#/settings/page/videoplayer">Проигрыватель видео</a></li>
                    <?php endif; ?>
                </ul>
            </div>  
            <div class="password-change content">
                <table>
                    <tr><td>Старый пароль</td><td><input id="password_old" type='password'></td></tr>
                    <tr><td>Новый пароль</td><td><input id="password_new" type='password'></td></tr>
                    <tr><td>Повторите новый пароль</td><td><input id="password_repeat" type='password'></td></tr>
                </table>
                <a class="minibutton" onclick="window.ui.changePassword()"><span>Сменить</span></a>
            </div>
            <div id="videoplayer_settings" class="videoplayer content" style="display: none">
                <ul>
                    <?php if (Lms_Application::getConfig('download', 'players', 'selectable', 'la')): ?>
                        <li><label><input type="radio" name="videoplayer" value="la"> <img border='0' height='24' width='24' src='templates/<?php echo Lms_Application::getConfig('template');?>/img/24/la.gif'> Light Alloy</label></li>
                    <?php endif;?>
                    <?php if (Lms_Application::getConfig('download', 'players', 'selectable', 'mp')): ?>
                        <li><label><input type="radio" name="videoplayer" value="mp"> <img border='0' height='24' width='24' src='templates/<?php echo Lms_Application::getConfig('template');?>/img/24/mp.gif'> Windows Media Player</label></li>
                    <?php endif;?>
                    <?php if (Lms_Application::getConfig('download', 'players', 'selectable', 'mpcpl')): ?>
                        <li><label><input type="radio" name="videoplayer" value="mpcpl"> <img border='0' height='24' width='24' src='templates/<?php echo Lms_Application::getConfig('template');?>/img/24/mpcpl.gif'> Media Player Classic</label></li>
                    <?php endif;?>
                    <?php if (Lms_Application::getConfig('download', 'players', 'selectable', 'bsl')): ?>
                        <li><label><input type="radio" name="videoplayer" value="bsl"> <img border='0' height='24' width='24' src='templates/<?php echo Lms_Application::getConfig('template');?>/img/24/bsl.gif'> BSPlayer</label></li>
                    <?php endif;?>
                    <?php if (Lms_Application::getConfig('download', 'players', 'selectable', 'crp')): ?>
                        <li><label><input type="radio" name="videoplayer" value="crp"> <img border='0' height='24' width='24' src='templates/<?php echo Lms_Application::getConfig('template');?>/img/24/mls.gif'> Crystal Player</label></li>
                    <?php endif;?>
                    <?php if (Lms_Application::getConfig('download', 'players', 'selectable', 'tox')): ?>
                        <li><label><input type="radio" name="videoplayer" value="tox"> <img border='0' height='24' width='24' src='templates/<?php echo Lms_Application::getConfig('template');?>/img/24/tox.gif'> xine</label></li>
                    <?php endif;?>
                    <?php if (Lms_Application::getConfig('download', 'players', 'selectable', 'kaf')): ?>
                        <li><label><input type="radio" name="videoplayer" value="kaf"> <img border='0' height='24' width='24' src='templates/<?php echo Lms_Application::getConfig('template');?>/img/24/kaf.gif'> kaffeine</label></li>
                    <?php endif;?>
                    <?php if (Lms_Application::getConfig('download', 'players', 'selectable', 'pls')): ?>
                        <li><label><input type="radio" name="videoplayer" value="pls"> <img border='0' height='24' width='24' src='templates/<?php echo Lms_Application::getConfig('template');?>/img/24/pls.gif'> Winamp/Mplayer</label></li>
                    <?php endif;?>
                    <?php if (Lms_Application::getConfig('download', 'players', 'selectable', 'xspf')): ?>
                        <li><label><input type="radio" name="videoplayer" value="xspf"> <img border='0' height='24' width='24' src='templates/<?php echo Lms_Application::getConfig('template');?>/img/24/vlc.gif'> VLC media player</label></li>
                    <?php endif;?>
                </ul>
                <a class="minibutton" onclick="window.ui.saveVideoplayerSettings()"><span>Сохранить выбор</span></a>
            </div>
            <div id="links_settings" class="links content" style="display: none">
                <ul>
                    <?php if (Lms_Application::getConfig('download', 'selectable', 'download')): ?>
                        <li><label><input type="checkbox" name="links" value="download"> Скачивание</label></li>
                    <?php endif;?>
                    <?php if (Lms_Application::getConfig('download', 'selectable', 'smb')): ?>
                        <li><label><input type="checkbox" name="links" value="smb"> Просмотр</label></li>
                    <?php endif;?>
                    <?php if (Lms_Application::getConfig('download', 'selectable', 'dcpp')): ?>
                        <li><label><input type="checkbox" name="links" value="dcpp"> DirectConnect (DC++)</label></li>
                    <?php endif;?>
                </ul>
                <a class="minibutton" onclick="window.ui.saveLinksSettings()"><span>Сохранить</span></a>
            </div>
        </div>
    </div>
    <a class="scroll-up" style="display:none" onclick="Effect.ScrollTo($('container'), {duration: 0.15});" title="Наверх"><span class="icon up"></span></a>
    <?php require_once "footer.php"; ?>
</div>
<div style="display:none;">
    <div id="streamingplayer">
        <div id="streamingplayer_object"></div>
    </div>
</div>

<?php if (Lms_Application::getConfig('ga', 'account')): ?>
    <script type="text/javascript">
    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', '<?php echo Lms_Application::getConfig('ga', 'account');?>']);
    <?php if (Lms_Application::getConfig('ga', 'domain_name')):?>
        _gaq.push(['_setDomainName', '<?php echo Lms_Application::getConfig('ga', 'domain_name');?>']);
    <?php endif; ?>
    _gaq.push(['_trackPageview']);
    (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            <?php if (Lms_Application::getConfig('ga', 'js')):?>
                ga.src = '<?php echo Lms_Application::getConfig('ga', 'js');?>';
            <?php else:?>
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            <?php endif;?>
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();
    </script>
<?php endif; ?>


<?php
if (file_exists(dirname(__FILE__) . '/main.after.php')) {
    include_once(dirname(__FILE__) . '/main.after.php');
}
?>