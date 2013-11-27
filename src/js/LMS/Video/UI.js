/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: UI.js 700 2011-06-10 08:40:53Z macondos $
 */

if (!LMS.Video) {
    LMS.Video = {};
}

LMS.Video.UI = {
    currentGenre: null,
    currentCountry: null,
    limitNavigationItems: 15,
    limitPages: 10,
    offset: null,
    pageSize: 20,
    total: 0,
    paginator: null,
    continuousScrolling: true,
    pages: [],
    waitLoad: false,
    overlay: null,
    currentOrder: null,
    currentDir: null,
    maxFrameWidth: 500,
    lastSuggestionQuery: null,
    mode: null,
    catalogInited: false,
    mainWrapperInited: false,
    tabs: {},
    level2: 'catalog',
    bookmarks: {movies:[]},
    recentlyViewed: [],
    currentMovieId: null,
    links: {},
    init: function()
    {
        this.initHacks();
        $j('#toolbar [title]').tipsy({delayIn: 200, gravity: 's'});
        $j('.scroll-up[title]').tipsy({gravity: 'e'});
        $j('[title]').tipsy({delayIn: 200});
        this.tabs.catalog = {};
        this.tabs.bestsellers = {};
        this.tabs.search = {};
        this.tabs.movie = {};
        this.tabs.person = {};
        this.tabs.settings = {};
        
        LMS.Connector.connect('routeCatalog', this, 'routeCatalog');
        LMS.Connector.connect('routeDefault', this, 'routeBestsellers');
        LMS.Connector.connect('routeSettings', this, 'routeSettings');
        LMS.Connector.connect('routeMovie', this, 'routeMovie');
        LMS.Connector.connect('routePerson', this, 'routePerson');
        LMS.Connector.connect('routeBestsellers', this, 'routeBestsellers');
        LMS.Connector.connect('routeSearch', this, 'routeSearch');
        
        this.overlay = LMS.Widgets.Factory('Overlay');
        
        if (USER_GROUP!=0) {
            window.action.getBookmarks();
        }
        
        var matches = window.location.hash.match(/#film:(\d+)/);
        if (matches) {
            window.location.hash = '/movies/id/' + matches[1];
        }
        if (window.location.hash.match(/#page:/)) {
            window.location.hash = '';
        }
        router.onBeforeRoute = this.onBeforeRoute.bind(this);
        router.init();
        this.initSuggestion();
        
        this.initRecentViewed();
        this.loadVideoplayerSettings();
        this.loadLinksSettings();
    },
    
    initCatalog: function()
    {
        this.initPaginator()
        this.initNavigator();
        window.action.getRandomMovie();
        window.action.getPopMovies();
        window.action.getLastComments();
        window.action.getLastRatings();
        this.catalogInited = true;
        if (!this.mainWrapperInited) {
            this.initMainWrapper();
        }
    },
    
    initMainWrapper: function()
    {
        this.mainWrapperInited = true;
    },
    
    setTemplate: function(template)
    {
        setCookie('template', template, 'Tue, 24-Mar-2020 20:16:28 GMT');
        window.location.reload();
    },
    
    routeCatalog: function(params)
    {
        this.tabs.catalog.hash = window.location.hash.substring(1);
        
        if (params.country != this.currentCountry) {
            window.action.getGenres(params.country? params.country : 0);
        }
        if (params.genre != this.currentGenre) {
            window.action.getCountries(params.genre? params.genre : 0);
        }
        var offset = this.pageNumberToOffset(params.page? params.page : 1);
        this.getCatalog(
            offset, 
            params.genre? params.genre : null, 
            params.country? params.country : null,
            params.order? params.order : null
        );
    },

    routeBestsellers: function(params)
    {
        this.tabs.bestsellers.hash = window.location.hash.substring(1);
        window.action.getBestsellers();
        if (!this.mainWrapperInited) {
            this.initMainWrapper();
        }
    },

    routeSearch: function(params)
    {
        this.tabs.search.hash = window.location.hash.substring(1);
        window.action.search(params.query);
        if (!this.mainWrapperInited) {
            this.initMainWrapper();
        }
    },

    routeMovie: function(params)
    {
        this.tabs.movie.hash = window.location.hash.substring(1);
        var movieId = params.id;
        var page = params.page? params.page : 'overview';
        if (movieId == this.currentMovieId) {
            this.gotoTab('movie');
            switch (page) {
                case 'comments':
                    this.showMovieComments(movieId);
                    break;
                case 'overview':
                default:
                    this.showMovieOverview();
            }
        } else {
            window.action.getMovie(params.id, page);
        }
    },

    routePerson: function(params)
    {
        this.tabs.person.hash = window.location.hash.substring(1);
        window.action.getPerson(params.id);
    },

    routeSettings: function(params)
    {
        this.gotoTab('settings');
        var title = 'Настройки';
        this.setTitle(title);
        this.setFavicon();
        this.tabs.settings.name = title;
        this.updateBreadcrumb();
        if (params.page) {
            var page = params.page
        } else {
            var page = $j('#settings_wrapper .menu-item:first').attr('data-page');
        }
        this.selectSettingPage(page);
    }, 
    
    localStorageHandler: function(e)
    {
        if (!e) {
            e = window.event; 
        }
        switch (e.key) {
            case 'user.recently_viewed':
                this.loadRecentlyViewed();
                this.drawRecentlyViewed();
                break;
        }
    },
    
    gotoTab: function(tabCode)
    {
        if (tabCode=='catalog' || tabCode=='bestsellers' || tabCode=='search') {
            this.level2 = tabCode;
        }
        if (!this.tabs.current || this.tabs.current!=this.tabs[tabCode]) {
            if (this.tabs.current) {
                this.saveTab();
            }
            this.tabs.current = this.tabs[tabCode];
            this.tabs.current.tabCode = tabCode;
            this._setMode(tabCode);
            if (this.tabs.current) {
                this.loadTab();
            }
            this.updateBreadcrumb();
        }
    },
    
    _setMode: function(mode)
    {
        this.mode = mode;
        $('container').className = 'mode-' + mode;
    },
    
    setTitle: function(title)
    {
        document.title = title + ' - ' + SITE_TITLE;
    },
    
    saveTab: function()
    {
        this.tabs.current.title = document.title;
        this.tabs.current.favicon = this.getFavicon();
        this.tabs.current.scrollY = document.viewport.getScrollOffsets().top
    },
    
    loadTab: function()
    {
        if (!Object.isUndefined(this.tabs.current.hash)) {
            router.routedHash(this.tabs.current.hash);
        }
        if (!Object.isUndefined(this.tabs.current.title)) {
            //wait to update History
            var title = this.tabs.current.title;
            var lastTitle = document.title;
            setTimeout(function() {
                if (lastTitle==document.title) {
                    document.title = title;
                }
            }, 500);
            
        }
        if (!Object.isUndefined(this.tabs.current.favicon)) {
            this.setFavicon(this.tabs.current.favicon);
        }
        if (!Object.isUndefined(this.tabs.current.scrollY)) {
            window.scrollTo(0, this.tabs.current.scrollY);
        }
        this.updateBreadcrumb();
    },
    
    onBeforeRoute: function(location)
    {
        for (var tabCode in this.tabs) {
            if (tabCode!='current' && this.tabs[tabCode].hash==location) {
                this.gotoTab(tabCode);
                return false;
            }
        }
        return true;
    },
    
    breadcrumbLevel2Handler: function()
    {
        if (this.level2=='catalog') {
            this.catalogButtonHandler()
        } else {
            this.gotoTab(this.level2);
        }
    },
    
    updateBreadcrumb: function()
    {
        var breadcrumb = $$('.breadcrumb')[0];
        if (this.tabs.current==this.tabs.catalog
            || this.tabs.current==this.tabs.bestsellers
            || this.tabs.current==this.tabs.search
            || this.tabs.current==this.tabs.settings
        ) {
            var elements = Element.select(breadcrumb, 'li');
            if (elements[2]) {
                elements[2].remove();
            }
            var a = Element.select(breadcrumb, 'a')[1];
            switch (this.tabs.current) {
                case this.tabs.catalog:
                    a.innerHTML = 'Каталог';
                    break;
                case this.tabs.bestsellers:
                    a.innerHTML = 'Бестселлеры';
                    break;
                case this.tabs.search:
                    a.innerHTML = 'Результаты поиска';
                    break;
                case this.tabs.settings:
                    a.innerHTML = 'Настройки';
                    break;
            }
            $j('#nav li').removeClass('selected');
            $j('#nav li.' + this.tabs.current.tabCode).addClass('selected');
            
        } else if (
            this.tabs.current==this.tabs.movie
            || this.tabs.current==this.tabs.person
        ) {
            if (this.tabs.current.name) {
                var elements = Element.select(breadcrumb, 'a');
                if (elements.length==2) {
                    var li = new Element('LI');
                    var a = new Element('A');
                    li.appendChild(a);
                    breadcrumb.appendChild(li);
                } else {
                    a = elements[2];
                }
                a.innerHTML = this.tabs.current.name;
            }
        }
        $j('.breadcrumb li').each(function(i, el){
            $j(el).css('z-index', 10-i);
        });
    },
    
    getCatalog: function(offset, genre, country, order, continuous)
    {
        var existsPage = this.getPageByOffset(offset);
        if (existsPage && genre==this.currentGenre && country==this.currentCountry && order==this.currentOrder) {
            this.gotoTab('catalog');
            Effect.ScrollTo(existsPage.topElement, {duration: 0.15, offset: -20});
        } else {
            if (Object.isUndefined(continuous)) {
                continuous = false;
            }
            if ((!this.continuousScrolling || !continuous) && document.viewport.getScrollOffsets().top>$('catalog_wrapper').cumulativeOffset().top) {
                Effect.ScrollTo($('main'), {duration: 0.15});
            }
            this.currentOrder = order;
            this.currentGenre = genre;
            this.currentCountry = country;
            this.updateGenreWrapper();
            this.updateCountryWrapper();
            this.updateOrder();
            window.action.getCatalog(offset, this.pageSize, genre, country, order, continuous);
            if (!this.catalogInited) {
                this.initCatalog();
            }
        }
    },
    
    drawCatalog: function(data, continuous)
    {
        this.gotoTab('catalog');
        var title = "Каталог";
        
        
        this.total = parseInt(data.total);
            
        var frameHtml = TEMPLATES.CATALOG.process(data);
        var wrapper = $('catalog');
       
        if (!this.continuousScrolling || !continuous || !this.pages.length) {
            this.offset = parseInt(data.offset);
            this.setupPaginator(false);
            wrapper.innerHTML = frameHtml;
            var page = {
                offset: this.offset,
                top: wrapper.cumulativeOffset().top,
                topElement: wrapper,
                title: title
            };
            this.pages = [];
            this.pages.push(page);
            this.continuousScrolling = true;
            this.removePageMarkers();
            this.setTitle(title);
            this.setFavicon();
        } else {
            var tempElement = new Element('TEMP');
            tempElement.innerHTML = frameHtml;
            var childNodes = [];
            for (var i=0; i<tempElement.childNodes.length; i++) {
                childNodes.push(tempElement.childNodes[i]);
            }
            for (var i=0; i<childNodes.length; i++) {
                wrapper.appendChild(childNodes[i]);
            }
            var mid = data.movies[0].movie_id;
            var firstItem = Element.select(wrapper, '.item[mid=' + mid + ']')[0];
            var page = {
                offset: parseInt(data.offset),
                top: firstItem.cumulativeOffset().top,
                topElement: firstItem,
                title: title
            };
            this.pages.push(page);
            this.insertPageMarker(page);
        }
        if (this.pages.length>=this.limitPages) {
            this.continuousScrolling = false;
            if ((parseInt(data.offset)+this.pageSize)<this.total) {
                var nextPages = new Element('A').update('Следующие страницы...');
                nextPages.addClassName('button next-pages');
                var self = this;
                nextPages.onclick = function(){
                    self.getCatalog(parseInt(data.offset) + self.pageSize, self.currentGenre, self.currentCountry, self.currentOrder, false);
                }
                wrapper.appendChild(nextPages);
            }
        }
        this.waitLoad = false;
        this.updateBreadcrumb();
        $j('#catalog [title]').tipsy({delayIn: 200});
    },

    drawLastComments: function(data)
    {
        $j('.tipsy').remove();
        $j('#last_comments').html(TEMPLATES.LAST_COMMENTS.process(data));
        $j('#last_comments .comment-tooltip[title]').tipsy({delayIn: 100, gravity: 'w', html: true});
        $j('#last_comments [title]').tipsy({delayIn: 200});
    },
    
    drawLastRatings: function(data)
    {
        $j('.tipsy').remove();
        $('last_ratings').innerHTML = TEMPLATES.LAST_RATINGS.process(data);
        $j('#last_ratings [title]').tipsy({delayIn: 200});
    },

    drawRandomMovie: function(data)
    {
        $j('.tipsy').remove();
        $('random_movie').innerHTML = TEMPLATES.RANDOM_FILM.process(data);
        $j('#random_movie [title]').tipsy({delayIn: 200});
    },

    drawPopMovies: function(data)
    {
        $j('.tipsy').remove();
        $('pop_movies').innerHTML = TEMPLATES.POP_FILMS.process(data);
        $j('#pop_movies [title]').tipsy({delayIn: 200});
    },

    drawBestsellers: function(data)
    {
        this.gotoTab('bestsellers');
        var title = 'Бестселлеры';
        this.setTitle(title);
        this.setFavicon();
        $('bestsellers').innerHTML = TEMPLATES.BESTSELLERS.process(data);
        this.updateBreadcrumb();
        $j('#bestsellers [title]').tipsy({delayIn: 1000, gravity: 's', html: true, fade: true});
    },

    drawSearch: function(data)
    {
        this.gotoTab('search');
        var title = 'Результаты поиска';
        this.setTitle(title);
        this.setFavicon();
        $('search_results').innerHTML = TEMPLATES.SEARCH.process(data);
        $('search_submenu_item').show();
        this.updateBreadcrumb();
    },

    drawPerson: function(data)
    {
        this.gotoTab('person');
        var title = data.person.name? data.person.name : data.person.international_name;
        this.setTitle(title);
        this.setFavicon(data.person.photos[0]? data.person.photos[0].thumbnail : false);

        this.tabs.person.name = title;
        $('person_wrapper').innerHTML = TEMPLATES.PERSON.process(data);
        
        this.imageViewer($j('#person_wrapper a[rel^="fancybox"]'));
        
        var name = data.person.name? data.person.name : data.person.international_name;
        this.addRecentlyViewedItem(name, router.url('person', {id: data.person.person_id}));
        this.updateBreadcrumb();
    },
    
    drawMovie: function(data, page)
    {
        this.gotoTab('movie');
        window.scrollTo(0,0);
        $('movie_wrapper').innerHTML = TEMPLATES.FILM.process(data);
        if (SETTINGS.DOWNLOAD_PLAYER.SELECTABLE && !$j.Storage.get("videoplayer")) {
            var v = $j('#videoplayer_settings');
            $j('#movie_wrapper a[rel^="videoplayer"]').fancybox({
                'href': '#videoplayer_settings',
                'type': 'inline',
                'onStart': function() {
                    v.show();
                },
                'onClosed': function() {
                    v.hide();
                }
            });
        }
        $j('#movie_wrapper .iframe').fancybox({
            width: 650,
            height: 350,
            centerOnScroll: true
        });
        var self = this;
        setTimeout(function() {
            $j('.persones-wrapper .defer').removeClass('defer');
            self.imageViewer($j("#movie_wrapper a[rel^='fancybox']"));
        }, 500);

        this.slideFrames(0);

        var title = data.movie.name;
        if (data.movie.international_name) {
            title += " / " + data.movie.international_name;
        }
        if (data.movie.international_name) {
            title += " / " + data.movie.year;
        }
        this.setTitle(title);
        this.setFavicon(data.movie.covers.length? data.movie.covers[0].thumbnail : null);
            
        this.tabs.movie.name = data.movie.name;
        
        var movieId = data.movie.movie_id;
        var name = '${name} {if year} (${year}){/if}'.process(data.movie);
        this.addRecentlyViewedItem(name, router.url('movie', {id: movieId}));
        this.updateBreadcrumb();
        if ([2,3,5].indexOf(USER_GROUP)!=-1) {
            $j('#movie_moder_edit_button').attr('href', 'cp/#/movies/id/' + movieId);
            $j('#quality_select').unbind('change').val(data.movie.quality).change(function(){
                window.action.setMovieField(movieId, 'quality', $j(this).val());
            });;
            $j('#translate_select').unbind('change').val(data.movie.translation[0]).change(function(){
                window.action.setMovieField(movieId, 'translation/0', $j(this).val());
            });
        }
        $j('#movie_wrapper :not([rel^="fancybox"])[title]').tipsy({delayIn: 200, html: true});
        
        switch (page) {
            case 'comments':
                this.showMovieComments(movieId);
                break;
            case 'overview':
            default:
                this.showMovieOverview();
        }
        
        this.currentMovieId = movieId;
    },

    drawComments: function(data)
    {
        $('movie_comments').innerHTML = TEMPLATES.FILM_COMMENTS.process(data);
    },

    showMovieOverview: function()
    {
        $j('#movie .tabset li').removeClass('active');
        $j('#movie .tabset li.movie-overview').addClass('active');
        $j('#movie .tabset-body').hide();
        $j('#movie .tabset-body.movie-overview').show();
    },

    showMovieComments: function(movieId)
    {
        $j('#movie .tabset li').removeClass('active');
        $j('#movie .tabset li.movie-comments').addClass('active');
        $j('#movie .tabset-body').hide();
        $j('#movie .tabset-body.movie-comments').show();
        if (!$j('#movie_comments').html().strip()) {
            window.action.getComments(movieId);
        }
    },
    
    drawMoviePerson: function (data, personId)
    {
        $j('#movie div.person-detail .ident').html(TEMPLATES.PERSON.process(data));
        $j('#movie').addClass('show-pesonal-detail');
        Effect.ScrollTo($$('.persones-wrapper')[0], {duration: 0.15});
        $j('#movie .person-preview.active').removeClass('active');
        var personLi = $$('#movie .person-preview[pid=' + personId + ']')[0];
        personLi.addClassName('active');
        var personesUl = $$('#movie .persones')[0];
        var scrollLeft =  personLi.positionedOffset().left - (personesUl.getWidth() - personLi.getWidth())/2;
        $j(personesUl).animate({scrollLeft: scrollLeft}, 'fast')
        
        this.imageViewer($j('#movie div.person-detail a[rel^="fancybox"]'));
    },

    personPreviewClickHandler: function (personId, element, index)
    {
        if ($j('#movie .persones').hasClass('collapsed') && index>=5) {
            this.slidePersones(0);
        } else {
            window.action.getMoviePerson(personId);
        }
    },

    hidePerson: function ()
    {
        $j('#movie .person-preview.active').removeClass('active');
        $('movie').removeClassName('show-pesonal-detail');
    },
        
    slidePersones: function(timeout) 
    {
        var self = this;
        this.slidePersonesTimeout = setTimeout(function(){
             $j('#movie .persones').removeClass('collapsed')
        }, timeout);
    },
    
    cancelSlidePersones: function() 
    {
        if (this.slidePersonesTimeout) {
            clearTimeout(this.slidePersonesTimeout);
            this.slidePersonesTimeout = null;
        }
    },

    updateGenreWrapper: function()
    {
        if (this.currentGenre) {
            var el = $$('#genres_all li[data-id='+this.currentGenre+']', '#genres li[data-id='+this.currentGenre+']')[0];
            var name = el? el.readAttribute('data-name') : '';
        } else {
            var name = 'Любой жанр';
        }
        $('genre').innerHTML = name;
    },
    
    updateCountryWrapper: function()
    {
        if (this.currentCountry) {
            var el = $$('#countries_all li[data-id='+this.currentCountry+']', '#countries li[data-id='+this.currentCountry+']')[0];
            var name = el? el.readAttribute('data-name') : '';
        } else {
            var name = 'Любая страна';
        }
        $('country').innerHTML = name;
    },
    
    initNavigator: function()
    {
        window.action.getGenres(this.currentCountry);
        window.action.getCountries(this.currentGenre);
    },
    
    initRecentViewed: function()
    {
        this.loadRecentlyViewed();
        this.filterRecentlyViewed();
        this.drawRecentlyViewed();
        if (window.addEventListener) {
            window.addEventListener("storage", this.localStorageHandler.bind(this), false);
        } else {
            window.attachEvent("onstorage", this.localStorageHandler.bind(this));
        };
    },
    
    saveRecentlyViewed: function()
    {
        if (!Modernizr.localstorage) {return false;}
        try {
            localStorage.setItem('user.recently_viewed', JSON.stringify(this.recentlyViewed));
        } catch (e){
        }
    },
    
    loadRecentlyViewed: function()
    {
        if (!Modernizr.localstorage) {return false;}
        try {
            var l = localStorage.getItem('user.recently_viewed');
            this.recentlyViewed = l? JSON.parse(l) : [];
        } catch (e){
        }
    },
    
    drawRecentlyViewed: function()
    {
        $('recently_viewed').innerHTML = TEMPLATES.RECENTLY_VIEWED.process({items: this.recentlyViewed});
        $j('#recently_viewed a').click(this.hideFilters.bind(this));
        if (this.recentlyViewed.length) {
            $j('#recently_viewed_menu_item').show();
        }
    },

    filterRecentlyViewed: function()
    {
        var time = new Date().getTime() - 24*3600*1000;
        this.recentlyViewed = this.recentlyViewed.findAll(function(s) {return s.time>time;});
        this.recentlyViewed = this.recentlyViewed.slice(0, 99);
        this.recentlyViewed = this.recentlyViewed.sortBy(function(s) {return -s.time;});
    },

    addRecentlyViewedItem: function(name, url)
    {
        var founded = false;
        this.recentlyViewed.each(function(value, index) {
            if (value.url==url) {
                value.time = new Date().getTime();
                founded = true;
                throw $break;
            }
        }, this);
        if (!founded) {
            var rv = {
                name: name,
                url: url,
                time: new Date().getTime()
            };
            this.recentlyViewed.unshift(rv);
        }
        this.filterRecentlyViewed();
        this.saveRecentlyViewed();
        this.drawRecentlyViewed();
    },
    
    drawBookmarks: function(data)
    {
        this.bookmarks = data;
        $('bookmarks').innerHTML = TEMPLATES.BOOKMARKS.process(data);
        $j('#bookmarks a').click(this.hideFilters.bind(this));
        $j('#bookmarks .bookmark-action[title]').tipsy({delayIn: 200});
    },
    
    unstarBookmark: function(movieId)
    {
        $j('#bookmarks li[mid=' + movieId + ']').remove();
        $j('.bookmark[mid=' + movieId + ']').removeClass('on').attr("title", "Добавить в закладки");
        var i = this.getBookmarkIndex(movieId);
        this.bookmarks.movies.splice(i, 1);
    },

    starBookmark: function(movieId)
    {
        $j('.bookmark[mid=' + movieId + ']').addClass('on').attr("title", "Удалить закладку");
    },

    getBookmarkIndex: function(movieId)
    {
        for (var i=0; i<this.bookmarks.movies.length; i++) {
            if (this.bookmarks.movies[i]['movie_id']==movieId) {
                return i;
                break;
            }
        }
        return -1;
    },

    bookmarkExists: function(movieId)
    {
        return (this.getBookmarkIndex(movieId)!=-1)? true : false;
    },
    
    toogleBookmark: function(movieId)
    {
        if (this.bookmarkExists(movieId)) {
            window.action.deleteBookmark(movieId);
        } else {
            window.action.addBookmark(movieId);
        }
    },

    _filterHandler: function(params)
    {
        var self = this;
        return function() {
            self.hideFilters();
            self.routeTo(params, 'catalog');
        }
    },
    
    _fillNavigation: function(items, wrapper, className)
    {
        wrapper.innerHTML = '';
        for (var i=0; i<items.length; i++) {
            var item = items[i];
            var a = new Element('A');
            a.onclick = this._filterHandler(item.params);
            var span = new Element('SPAN');
            span.addClassName('counter');
            span.innerHTML = item.count;

            var li = new Element('LI', {
                'data-id': item.id,
                'data-name': item.name
            });
            a.appendChild(span);
            a.appendChild(document.createTextNode(item.name));
            li.appendChild(a);
            wrapper.appendChild(li);
        }
        
    },
        
    drawGenres: function(data)
    {
        var self = this;
        data.genres.push({
            name: 'Любой жанр',
            id: null,
            count: ''
        });
        for (var i=0; i<data.genres.length; i++) {
            var params = {};
            params.genre = data.genres[i].id;
            params.page = 1;
            data.genres[i].params = params;
        }
        var allItem = data.genres.pop();
        
        var sortedItems = data.genres.sortBy(function(s) {return -parseInt(s.count);});
        var lastBig = Math.min(this.limitNavigationItems-1, sortedItems.length-1); 
        var threshold = parseInt(sortedItems[lastBig].count);
        
        var items = data.genres.findAll(function(s){return parseInt(s.count) >= threshold || s.id==self.currentGenre;});
        items = items.slice(0, this.limitNavigationItems);
        items.unshift(allItem);
        data.genres.unshift(allItem);
        this._fillNavigation(items, $('genres'));
        if (items.length < data.genres.length) {
            this._fillNavigation(data.genres, $('genres_all'));
            $('genres_switcher').innerHTML = $('genres_switcher').readAttribute('data-default-text');
            $('genres_switcher').show();
        } else {
            $('genres_switcher').hide();
        }
        $('genres').show();
        $('genres_all').hide();
        this.updateGenreWrapper();
    },
    
    drawCountries: function(data)
    {
        var self = this;
        data.countries.push({
            name: 'Любая страна',
            id: null,
            count: ''
        });
        for (var i=0; i<data.countries.length; i++) {
            var params = {};
            params.country = data.countries[i].id;
            params.page = 1;
            data.countries[i].params = params;
        }
        var allItem = data.countries.pop();
        
        var sortedItems = data.countries.sortBy(function(s) {return -parseInt(s.count);});
        var lastBig = Math.min(this.limitNavigationItems-1, sortedItems.length-1); 
        var threshold = parseInt(sortedItems[lastBig].count);
        
        var items = data.countries.findAll(function(s){return parseInt(s.count) >= threshold || s.id==self.currentCountry;});
        items = items.slice(0, this.limitNavigationItems);
        items.unshift(allItem);
        data.countries.unshift(allItem);
        this._fillNavigation(items, $('countries'));
        if (items.length < data.countries.length) {
            this._fillNavigation(data.countries, $('countries_all'));
            $('countries_switcher').innerHTML = $('countries_switcher').readAttribute('data-default-text');
            $('countries_switcher').show();
        } else {
            $('genres_switcher').hide();
        }
        $('countries').show();
        $('countries_all').hide();
        this.updateCountryWrapper();
    },
    
    showGenres: function()
    {
        if ($j('#genres_menu_item').is('.active')) {
            this.overlay.close();
            return;
        }
        this.overlay.close();
        this.overlay.setTargetScroll($('genres_all'));
        this.overlay.show();
        this.overlay.onClose = function() {
            $('genres_wrapper').hide();
            $('genres_menu_item').removeClassName('active');
            $('toolbar').removeClassName('active');
        };
        $('genres_wrapper').show();
        $('genres_menu_item').addClassName('active');
        $('toolbar').addClassName('active');
    },

    showCountries: function()
    {
        if ($j('#countries_menu_item').is('.active')) {
            this.overlay.close();
            return;
        }
        this.overlay.close();
        this.overlay.setTargetScroll($('countries_all'));
        this.overlay.show();
        this.overlay.onClose = function() {
            $('countries_wrapper').hide();
            $('countries_menu_item').removeClassName('active');
            $('toolbar').removeClassName('active');
        };
        $('countries_wrapper').show();
        $('countries_menu_item').addClassName('active');
        $('toolbar').addClassName('active');
    },
    
    showSortMenu: function()
    {
        if ($j('#sort_menu_item').is('.active')) {
            this.overlay.close();
            return;
        }
        this.overlay.close();
        this.overlay.setTargetScroll(false);
        this.overlay.show();
        this.overlay.onClose = function() {
            $('sort_wrapper').hide();
            $('sort_menu_item').removeClassName('active');
            $('toolbar').removeClassName('active');
        };
        $('sort_wrapper').show();
        $('sort_menu_item').addClassName('active');
        $('toolbar').addClassName('active');
    },

    showBookmarksMenu: function()
    {
        if ($j('#bookmarks_menu_item').is('.active')) {
            this.overlay.close();
            return;
        }
        this.overlay.close();
        this.overlay.setTargetScroll($('bookmarks'));
        this.overlay.show();
        this.overlay.onClose = function() {
            $('bookmarks').hide();
            $('bookmarks_menu_item').removeClassName('active');
            $('toolbar').removeClassName('active');
        };
        $('bookmarks').show();
        $('bookmarks_menu_item').addClassName('active');
        $('toolbar').addClassName('active');
    },

    showMovieModerMenu: function()
    {
        if ($j('#movie_moder_menu_item').is('.active')) {
            this.overlay.close();
            return;
        }
        this.overlay.close();
        this.overlay.setTargetScroll(false);
        this.overlay.show();
        this.overlay.onClose = function() {
            $('movie_moder_wrapper').hide();
            $('movie_moder_menu_item').removeClassName('active');
            $('toolbar').removeClassName('active');
        };
        $('movie_moder_wrapper').show();
        $('movie_moder_menu_item').addClassName('active');
        $('toolbar').addClassName('active');
    },


    showRecentlyViewedMenu: function()
    {
        if ($j('#recently_viewed_menu_item').is('.active')) {
            this.overlay.close();
            return;
        }
        this.overlay.close();
        this.overlay.setTargetScroll($('recently_viewed'));
        this.overlay.show();
        this.overlay.onClose = function() {
            $('recently_viewed').hide();
            $('recently_viewed_menu_item').removeClassName('active');
            $j('.toolbar.top').removeClass('active');
        };
        $('recently_viewed').show();
        $('recently_viewed_menu_item').addClassName('active');
        $j('.toolbar.top').addClass('active');
    },

    hideFilters: function()
    {
        this.overlay.close();
    },

    switchFilter: function(el)
    {
        var wrapper = el.parentNode;
        Element.select(wrapper, '.pop').invoke('toggle');
        Element.select(wrapper, '.all').invoke('toggle');
        switch (el.innerHTML) {
            case el.readAttribute('data-default-text'):
                el.innerHTML = el.readAttribute('data-back-text');
                break;
            case el.readAttribute('data-back-text'):
                el.innerHTML = el.readAttribute('data-default-text');
                break;
        }
    },
    
    highlightNavigator: function()
    {
        $$('#genres li.selected', '#genres_more li.selected').invoke('removeClassName', 'selected');
        $$('#genres li[data-id='+this.currentGenre+']', '#genres_more li[data-id='+this.currentGenre+']').invoke('addClassName', 'selected');
        $$('#countries li.selected', '#countries_more li.selected').invoke('removeClassName', 'selected');
        $$('#countries li[data-id='+this.currentCountry+']', '#countries_more li[data-id='+this.currentCountry+']').invoke('addClassName', 'selected');
    },

    routeTo: function(newParams, name, reset)
    {
        if (Object.isUndefined(name)) {
            name = router.getAction();
        }
        if (Object.isUndefined(reset)) {
            reset = false;
        }
        if (reset || name!=router.getAction()) {
            var currentParams = $H();
        } else {
            var currentParams = router.getParams();
        }
       
        var params = currentParams.merge(newParams);

        this.emit('route', name, params);
    },
    
    updateOrder: function()
    {
        var order = this.currentOrder!=null? this.currentOrder : 0;
        var shortText = $j('#sort_wrapper a[data-order="' + order + '"]').attr('data-short-text');
        $('sort').innerHTML = shortText;
    },
    
    setOrder: function(order)
    {
        this.routeTo({order: order, page: 1}, 'catalog');
        this.hideFilters();
    },

    setOffset: function(offset)
    {
        var page = this.offsetToPageNumber(offset);
        this.routeTo({page: page}, 'catalog');
        
        return;
        var params = {};
        params.genre = this.currentGenre;
        params.country = this.currentCountry;
        params.order = this.currentOrder;
        
        var page = this.offsetToPageNumber(offset);
        params.page = page;
        this.emit('route', 'catalog', params);
    }, 

    onOffsetChanged: function(offset)
    {
        var params = {};
        var page = this.offsetToPageNumber(offset);
        params.genre = this.currentGenre;
        params.country = this.currentCountry;
        params.order = this.currentOrder;
        params.page = page;
        var action = 'catalog';
        var hash = router.url(action, params).substring(1);
        router.routedHash(hash);
        this.tabs.catalog.hash = hash;
    }, 

    _mousewheelHandler: function()
    {
        if (this.mode=='catalog') {
            if ($j.browser.opera) {
                if (this.paginator.hasClassName('fixed')) {
                    this.paginator.removeClassName('fixed')
                    $j('.scroll-up').removeClass('visible');
                }
            }
        }
    },
    
    _scrollHandler: function()
    {
        if (this.mode=='catalog') {
            var top = $('catalog').cumulativeOffset().top;
            var scrollTop = document.viewport.getScrollOffsets().top;
            if (scrollTop>top) {
                if ($j.browser.opera) {
                    var s = $j('.scroll-up');
                    if (s.hasClass('visible')) {
                        s.hide().removeClass('visible');
                    }
                    if (this.scrollTimeoutId) {
                        clearTimeout(this.scrollTimeoutId);
                    }
                    this.scrollTimeoutId = setTimeout(function(){
                        if (!s.hasClass('visible')) {
                            s.fadeIn('slow').addClass('visible');
                        }
                    }, 200);
                } else {
                    if (!this.paginator.hasClassName('fixed')) {
                        this.paginator.addClassName('fixed');
                        $j('.scroll-up').fadeIn('slow').addClass('visible');
                    }
                }
            } else {
                if (this.paginator.hasClassName('fixed')) {
                    this.paginator.removeClassName('fixed')
                    $j('.scroll-up').hide().removeClass('visible');
                }
                if ($j.browser.opera && this.scrollTimeoutId) {
                    clearTimeout(this.scrollTimeoutId);
                }
            }

            var lastItem = $$('#catalog .item').pop();
            var scrollBottom = scrollTop + document.viewport.getHeight();
            var lastItemTop = lastItem.cumulativeOffset().top;
            if (lastItemTop<scrollBottom) {
                if (this.continuousScrolling && !this.waitLoad && (this.offset+this.pageSize)<this.total) {
                    this.waitLoad = true;
                    this.getCatalog(this.offset + this.pageSize, this.currentGenre, this.currentCountry, this.currentOrder, true);
                }
            }
            if (this.pages.length>1) {
                var offset = this.pages[0].offset;
                var page = null;
                for (var i=1; i<this.pages.length; i++) {
                    page = this.pages[i];
                    if ((page.top-30)<scrollTop) {
                        offset = page.offset;
                    } else {
                        break;
                    }
                }
                if (this.offset!=offset) {
                    this.offset = offset;
                    this.setupPaginator(false);
                    var page = this.getPageByOffset(offset);
                    this.setTitle(page.title);
                    this.setFavicon();
                }
            }
        } else {
            if ($j.browser.opera) {
                var s = $j('.scroll-up');
                if (s.hasClass('visible')) {
                    s.hide().removeClass('visible');
                }
            }
        }
    },

    initPaginator: function()
    {
        this.paginator = LMS.Widgets.Factory('PageIndexBox');
        this.paginator.setDOMId('paginator');
        this.paginator.beforePagesText = "";
        this.paginator.prevPageText = "";
        this.paginator.nextPageText = "";
        LMS.Connector.connect(this.paginator, 'valueChanged', this, 'setOffset');
        Event.observe(window, 'scroll', this._scrollHandler.bind(this));
    },
    
    setupPaginator: function(allowEmitPaginator)
    {
        this.paginator.setPageSize(this.pageSize);
        this.paginator.setCount(this.total);
        this.paginator.setOffset(this.offset, allowEmitPaginator);
        this.paginator.paint();
        this.onOffsetChanged(this.offset);
    },

    getPageByOffset: function(offset)
    {
        var page = null;
        for (var i=0; i<this.pages.length; i++) {
            page = this.pages[i];
            if (page.offset==offset) {
                return page;
                break;
            }
        }
        return false;
    },

    offsetToPageNumber: function(offset)
    {
        return Math.ceil((offset+1)/this.pageSize);
    },

    pageNumberToOffset: function(page)
    {
        return (page-1) * this.pageSize; 
    },

    insertPageMarker: function(page)
    {
        var marker = new Element('DIV', {'class': 'page-marker'});
        marker.innerHTML = this.offsetToPageNumber(page.offset);

        var top = page.topElement.cumulativeOffset().top;
        marker.setStyle({
            top: top + 'px'
        });
        $('container').insertBefore(marker, $('main'))
    },

    removePageMarkers: function()
    {
        $j('.page-marker').remove();
    },
    
    isFileDownloaded: function(fileId)
    {
        if (!Modernizr.localstorage) {return false;}
        var key = 'files.downloaded.' + fileId;
        return localStorage.getItem(key);
    },
    
    setFileDownloaded: function(fileId)
    {
        $j('.files tr[fid=' + fileId + ']').addClass('downloaded')
        if (!Modernizr.localstorage) {return false;}
        try {
            var key = 'files.downloaded.' + fileId;
            localStorage.setItem(key, true);
        } catch (e){
        }
    },
    
    initSuggestion: function()
    {
        var self = this;
        var s = $j('#search_query');
        
        s.focus(function () {
            if ($('search_suggestion').innerHTML.length>0) {
                setTimeout(function(){
                    $('search_suggestion').show();
                }, 300);
            }
        });
        s.blur(function () {
            setTimeout(function(){
                $('search_suggestion').hide();
            }, 400);
        });
        s.keydown(function (a) {
            if (a.keyCode == 38) {
            /*    j.focus();
                q();*/
                return false
            } else if (a.keyCode == 40) {
                /*j.focus();
                B();*/
                return false
            } else if (a.keyCode == 13) {
                setTimeout(self.search.bind(self), 10);
                s.blur();
                return false
            } else if (a.keyCode == 27) {
                /*I();
                z();*/
                return false
            }
/*            j.focus();
            p = setTimeout(F, 0)*/
            setTimeout(self.getSuggestion.bind(self), 10);
        });        
    },
    
    search: function()
    {
        var query = $j('#search_query').val();
        if (query.length>0) {
            this.routeTo({query: query}, 'search', true);
        }
    },
    
    getSuggestion: function()
    {
        var text = $j('#search_query').val();
        if (text.length>0) {
            if (this.lastSuggestionQuery!=text) {
                window.action.getSuggestion(text);
            }
        } else {
            $('search_suggestion').innerHTML = '';
        }
    },
    
    drawSuggestion: function(data)
    {
        if ((data.movies.length || data.persones.length) && data.query==$j('#search_query').val()) {
            this.lastSuggestionQuery = data.query;
            $('search_suggestion').innerHTML = TEMPLATES.SEARCH_SUGGESTION.process(data);
            $('search_suggestion').show();
        }
    },
    
    catalogButtonHandler: function()
    {
        if (this.tabs.catalog.hash) {
            this.gotoTab('catalog');
        } else {
            this.routeTo({}, 'catalog');
        }
    },
    
    searchButtonHandler: function()
    {
        if (this.tabs.catalog.hash) {
            this.gotoTab('search');
        }
    },
    
    setFavicon: function(url)
    {
        if (!url) {
            url = DEFAULT_FAVICON;
        }
        if (url!=this.getFavicon()) {
            $j('link[rel$=icon]').remove();
            $j('head').append($j('<link rel="shortcut icon" type="image/x-icon"/>').attr('href', url));
        }
    },
    
    getFavicon: function()
    {
        return $j('link[rel$=icon]').attr('href');
    },
    
    _setPersonalRating: function(value)
    {
        var w = 100*(value/10) + '%';
        $j('#personal_rating .inner').width(w);
        $j('#personal_rating .value').text(value? value : '');
    },

    _setLocalRating: function(value, count)
    {
        var w = 100*(value/10) + '%';
        $j('#local_rating .inner').width(w);
        $j('#local_rating .value').text(parseFloat(value).toFixed(1));
        $j('#local_rating .starbar').attr('title', "Локальный рейтинг: " + parseFloat(value).toFixed(1) + " (" + count + " голосов)");
        if (parseFloat(value)) {
            $j('#local_rating').show();
        } else {
            $j('#local_rating').hide();
        }
    },
    
    rateMouseOverHandler: function(rating)
    {
        this._setPersonalRating(rating);
    },
    
    rateMouseOutHandler: function()
    {
        var rating = parseInt($j('#personal_rating').attr('data-value'));
        if (!rating) {
            rating = 0;
        }
        this._setPersonalRating(rating);
    },
    
    updateRating: function(data)
    {
        var rating = parseInt(data.rating_personal_value);
        $j('#personal_rating').attr('data-value', rating);
        this._setPersonalRating(rating);
        if (rating) {
            $j('#personal_rating .remove').show();
        } else {
            $j('#personal_rating .remove').hide();
        }
        this._setLocalRating(data.rating_local_value, data.rating_local_count);
    },
    
    postComment: function(movieId)
    {
        var text = $('comment_text').value;
        window.action.postComment(movieId, text);
    },
    
    beginEditComment: function (commentId)
    {
        var textElement = $j('#movie_comments .message[cid="' +  commentId + '"] .text');
        if (textElement.attr("contenteditable")!="true") {
            textElement.attr("contenteditable", true);
            textElement.data('original_text', this.textizeHtml(textElement.html()));
            var self = this;
            var cancelButton = $j('<a class="minibutton"><span>Отмена</span></a>').click(function(){self.cancelEditComment(commentId)});
            var okButton = $j('<a class="minibutton"><span>Сохранить</span></a>').click(function(){self.saveComment(commentId)});

            var actions = $j('<div class="edit-buttons clearfix"></div>');
            actions.append(cancelButton)
                   .append(okButton)
                   .insertAfter(textElement);
            textElement.focus();
        }
    },

    cancelEditComment: function (commentId)
    {
        var textElement = $j('#movie_comments .message[cid="' +  commentId + '"] .text');
        if (textElement.attr("contenteditable")=="true") {
            textElement.attr("contenteditable", false);
            var text = textElement.data('original_text');
            textElement.html(this.htmlizeText(text));
            $j('#movie_comments .message[cid="' +  commentId + '"] .edit-buttons').remove();
        }
    }, 

    saveComment: function (commentId)
    {
        var textElement = $j('#movie_comments .message[cid="' +  commentId + '"] .text');
        if (textElement.attr("contenteditable")) {
            textElement.attr("contenteditable", false);
            var text = this.textizeHtml(textElement.html());
            textElement.html(this.htmlizeText(text));
            $j('#movie_comments .message[cid="' +  commentId + '"] .edit-buttons').remove();
            window.action.editComment(commentId, text);
        }
    }, 

    deleteComment: function(commentId)
    {
        if (confirm('Удалить комментарий?')) {
            window.action.deleteComment(commentId);
        }
    },
    
    postDeleteComment: function(commentId)
    {
        $j('.message[cid=' + commentId + ']').remove();
    },

    textizeHtml: function (html)
    {
        return html.replace(/<br\/?>/gi, '\r\n')
                   .replace(/<a[^>]*?href=([^>\s]*)[^>]*>(.*?)<\/a>/gi, function(){
                        var href = arguments[2].match(/^https?:/)? arguments[2] : arguments[1];
                        return href.replace(/^["']+/, '').replace(/["']+$/, '').unescapeHTML();
                   })
                   .strip();
    },

    htmlizeText: function (text)
    {
        return text.replace(/(\r\n|\r|\n)/gi, '<br>')
                   .replace(/((?:https?:\/\/|magnet:\?|ed2k:\/\/)[^\s<]+)/gi, '<a href="$1" target="_blank">$1</a>');
    },
    
    highlightElement: function (domId)
    {
        new Effect.Highlight(domId, {startcolor: '#ffebe8', restorecolor: true});
    },

    changePassword: function()
    {
        var oldPassword = $('password_old').value;
        if (!oldPassword) {
            this.highlightElement('password_old');
            return;
        }
        var newPassword = $('password_new').value;
        if (!newPassword) {
            this.highlightElement('password_new');
            return;
        }
        
        if (newPassword!= $('password_repeat').value) {
            this.emit('userError', 400, 'Пароли не совпадают!');
            this.highlightElement('password_repeat');
            return;
        }
        window.action.changePassword(oldPassword, newPassword);
    },
    
    postChangePassword: function()
    {
        this.emit('userMessage', 'Пароль успешно сменен');
        $('password_old').value = '';
        $('password_new').value = '';
        $('password_repeat').value = '';
    },
    
    initHacks: function()
    {
        if ($j.browser.msie && $j.browser.version<=7) {
            $j(window).resize(function() {
                $j('#container').width($j('body').width);
            });
            $j(window).resize();
        }
        if ($j.browser.opera) {
            $j('html').addClass('opera');
        }

    },
    
    selectSettingPage: function(page)
    {
        $j('#settings_wrapper .menu-item').removeClass('selected');
        $j('#settings_wrapper .menu-item[data-page="'+page+'"]').addClass('selected');
        $j('#settings_wrapper .content').hide();
        $j('#settings_wrapper .content.' + page).show();
    },
    
    saveVideoplayerSettings: function()
    {
        var vp = $j('#videoplayer_settings input[name="videoplayer"]:checked').val();
        $j.Storage.set("videoplayer", vp);
        this.emit('userMessage', 'Настройки сохранены', true);
        $j.fancybox.close();
        $j('#movie_wrapper a[rel^="videoplayer"]').unbind('click.fb').each(function() {
            var href = $j(this).attr('href');
            href = href.replace(/p=[^&]+/, 'p=' + vp);
            $j(this).attr('href', href);
        });
    },
    
    loadVideoplayerSettings: function()
    {
        var vp = $j.Storage.get("videoplayer");
        if (vp) {
            $j('#settings_wrapper input[value="' + vp + '"]').attr('checked', 'checked');
        }
    },
    
    saveLinksSettings: function()
    {
        var links = {};
        $j('#links_settings input[name="links"]').each(function(){
            var type = $j(this).val();
            links[type] = $j(this).is(':checked');
        });
        $j.Storage.set("links", JSON.stringify(links));
        this.emit('userMessage', 'Настройки сохранены', true);
    },
    
    loadLinksSettings: function()
    {
        var self = this;
        $j('#settings_wrapper input[name="links"]').each(function(){
            var type = $j(this).val();
            if (self.isLinkTypeEnabled(type)) {
                $j(this).attr('checked', 'checked');
            } else {
                $j(this).removeAttr('checked');
            }
        });
    },
    
    isLinkTypeEnabled: function(type)
    {
        var links = $j.Storage.get("links");
        links = links? JSON.parse(links) : {};
        var res = (!Object.isUndefined(links[type]))? links[type] : SETTINGS.DOWNLOAD_DEFAULTS[type];
        if (res=="0") {
            res = false;
        }
        return res;
    },
    
    imageViewer: function(elements)
    {
        var transitionIn = $j.browser.opera? 'none' : 'elastic';
        var transitionOut = $j.browser.opera? 'none' : 'elastic';
        var changeFade = $j.browser.opera? 0 : 50;
        var changeSpeed = $j.browser.opera? 0 : 100;
        elements.fancybox({
            'easingIn': 'easeOutBack',
            'easingOut': 'easeInBack',
            'cyclic': true,
            'overlayColor' : '#000',
            'overlayOpacity': 0.85,
            'transitionIn': transitionIn,
            'transitionOut': transitionOut,
            'changeFade': changeFade, 
            'changeSpeed': changeSpeed, 
            'padding': 0,
            'type': 'image'
        });
    },
    
    slideCover: function(num)
    {
        var width = $j('#movie_wrapper ul.covers li').width();
        var height = $j('#movie_wrapper ul.covers li:nth-child(' + (num+1)+ ') img').height();
        var marginLeft = - num * width;
        $j('#movie_wrapper ul.covers').css('marginLeft', marginLeft);
        $j('#movie_wrapper div.covers-wrapper').height(height);
        
        $j('#movie_wrapper ul.covers-nav a').removeClass('active');
        $j('#movie_wrapper ul.covers-nav li:nth-child(' + (num+1)+ ') a').addClass('active');
        
    },
    
    slideFrames: function(num) {
        num = parseInt(num);
        if (num>=0) {
            var slider = $j('#movie_wrapper ul.frames-slider > li');
            var max = slider.length - 1;
            if (num > max) {
                num = max;
            }
            
            if (num == max) {
                $j('#movie_wrapper .frames-slider-nav .next').attr('data-index', '');
            } else {
                $j('#movie_wrapper .frames-slider-nav .next').attr('data-index', num + 1);
            }

            if (num <= 0) {
                $j('#movie_wrapper .frames-slider-nav .prev').attr('data-index', '');
            } else {
                $j('#movie_wrapper .frames-slider-nav .prev').attr('data-index', num - 1);
            }
            var ul = slider.eq(num).find('ul');
            var filename = ul.attr('data-filename');
            $j('#movie_wrapper .frames-slider-nav .filename').html(filename);
            
            ul.find('img[data-original]').each(function(){
                var img = $j(this);
                var src = img.attr('data-original');
                img.attr('src', src)
                   .removeAttr('data-original');
            });
            
            var width = 720;
            var marginLeft = - num * width;
            $j('#movie_wrapper ul.frames-slider').css('marginLeft', marginLeft);
        }
    },
    
    hitMovie: function(movieId) 
    {
        setTimeout(function(){
            window.action.hitMovie(movieId);
        }, 1000);
    },
    
    showTrailer: function(url, name)
    {
        var gpuDisable = parseInt($j.Storage.get("flashplayer-gpu-disable"));
        var style;
        if (gpuDisable) {
            style = 'default.txt';
        } else {
            style = 'default-gpu.txt';
        }
        var flashvars = {
            "debug": "1",
            "comment": name,
            "st": "templates/" + TEMPLATE +"/player/styles/" + style,
            "file": url,
            "auto": "play"
        };
        var params = {
            bgcolor: "#000000",
            wmode: "direct", 
            allowFullScreen: "true", 
            allowScriptAccess: "always"
        }; 
        new swfobject.embedSWF("templates/" + TEMPLATE +"/player/uppod.swf", "streamingplayer_object", "720", "400", "9.0.115.0", false, flashvars, params);
        $j.fancybox({
            'href': '#streamingplayer',
            'type': 'inline',
            'overlayColor' : '#000',
            'overlayOpacity': 0.95,
            'changeFade': 0,
            'centerOnScroll': true,
            'autoDimensions': false,
            'scrolling': 'no',
            'width': 720,
            'height': 400,
            'padding': 0,
            'onClosed': function(){
                $j('#streamingplayer_object').replaceWith('<div id="streamingplayer_object"></div>');
            }
        });
        
    } 
    
};
