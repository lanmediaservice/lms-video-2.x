<div style="display:none" id="user_message"></div>
<div id="JHRControllerLoaderBox" style="display: none"><img src="templates/<?php echo Lms_Application::getConfig('template');?>/img/wait.gif"></div>
<?php //include_once dirname(__FILE__) . "/misc/help01.php"; ?>
<header>
    <div class="menu">
        <ul class="float_right">
            <?php if ($user->getUserGroup()!=0): ?>
                <li><strong><?php echo $user->getLogin();?></strong></li>
                <li><a href='#/settings'>Настройки</a></li>
                <?php if ($user->isAllowed('movie', 'moderate')): ?>
                    <li><a href="cp/">Панель управления</a></li>
                <?php endif; ?>
                <?php //<li><a id="get_help" href="#help">Справка</a></li> ?>
                <li><a onclick='window.action.logout();'>Выход</a></li>
            <?php else: ?>
                <li><a href='#/settings'>Настройки</a></li>
                <?php //<li><a id="get_help" href="#help">Справка</a></li> ?>
                <li><a onclick='window.action.logout();'>Войти</a></li>
            <?php endif; ?>
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
    <div class="toolbar top">
        <ul id="nav">
            <li class="bestsellers"><a onclick="window.ui.routeTo({}, 'bestsellers');">Бестселлеры</a></li>
            <li class="catalog"><a onclick="window.ui.catalogButtonHandler();">Каталог</a></li>
            <li id="search_submenu_item" class="search" style="display:none;"><a onclick="window.ui.searchButtonHandler();">Результаты поиска</a></li>
        </ul>
        <ul class="search">
            <li>
                <input id="search_query" name="search_text" placeholder="Найти ..." autocomplete="off" value="" type="text">
                <div id="search_suggestion" style="display:none"></div>
            </li>
        </ul>
        <?php if ($user->getUserGroup()!=0): ?>
            <ul class="built-in clickable" id="bookmarks_menu_item">
                <li>
                    <a title="Мои закладки" onclick="window.ui.showBookmarksMenu();"><span class="icon star"></span> &#9660;</a>
                    <ul id="bookmarks" class="right" style="display:none">
                    </ul>
                </li>
            </ul>
        <?php endif; ?>
        <ul class="built-in clickable" id="recently_viewed_menu_item" style="display:none">
            <li>
                <a title="Недавно просмотрено" onclick="window.ui.showRecentlyViewedMenu();"><span class="icon view"></span> &#9660;</a>
                <ul id="recently_viewed" class="right" style="display:none"></ul>
            </li>
        </ul>
    </div>
    <div style="clear:both;"></div>
</header>