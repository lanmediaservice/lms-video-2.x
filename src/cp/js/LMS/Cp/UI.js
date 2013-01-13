/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: UI.js 700 2011-06-10 08:40:53Z macondos $
 */

if (!LMS.Cp) {
    LMS.Cp = {};
}

LMS.Cp.UI = {
    
    usersInited: false,
    ctrlPressed: false,
    altPressed: false,
    settingsInited: false,
    settingsUpdates: {},
    
    intensiveStatusMonitoring: false,
    
    init: function() 
    {
        this.initControlPanel();
    },
    
    initControlPanel: function ()
    {
        this.activateMonitoringControlKeys();
        this.incoming.init();
        this.movies.init();
        this.persones.init();
        this.users.init();

        $j('#control_panel a.checkbox').each(function(index, el){
            var chkBox = $j(el);
            
            chkBox.click(function(e){
                if (chkBox.attr('data-checked')=="0") {
                    chkBox.attr('data-checked', "1");
                } else {
                    chkBox.attr('data-checked', "0");
                }
            });
        });

        window.action.getTranslations();
        window.action.getQualities();
        window.action.getCountries();
        window.action.getGenres();
        window.action.getRoles();
        window.action.getDefaultEngines();
        window.action.getCurrentStatus();

        LMS.Connector.connect('routeDefault', this.incoming, 'routeIncoming');

        LMS.Connector.connect('routeTasks', this, 'routeTasks');
        LMS.Connector.connect('drawFilesTasks', this, 'drawFilesTasks');
        LMS.Connector.connect('drawUtils', this, 'drawUtils');
        LMS.Connector.connect('drawReport', this, 'drawReport');
        LMS.Connector.connect('drawSettings', this, 'drawSettings');

        LMS.Connector.connect('routeSettings', this, 'routeSettings');
        LMS.Connector.connect('routeUtils', this, 'routeUtils');
        LMS.Connector.connect('routeUpdates', this, 'routeUpdates');
        
        LMS.Connector.connect('afterSearchGoogleImages', this, 'drawImagesSearchResults');

        LMS.Connector.connect('afterRelocateLostFiles', this, 'afterRelocateLostFiles');
        LMS.Connector.connect('afterHideBrokenMovies', this, 'afterHideBrokenMovies');

        LMS.Connector.connect('afterCheckUpdates', this, 'drawUpdatesCheck');
        LMS.Connector.connect('afterUpgrade', this, 'drawUpgradeResult');


        new PeriodicalExecuter(function() {
            //if ($j('#tasks .tasks-autoupdate-switch').attr('data-checked')=='1') {
                window.action.getCurrentStatus();
            //}
        }, 60);
        new PeriodicalExecuter(function() {
            if (window.ui.intensiveStatusMonitoring) {
                window.action.getCurrentStatus();
            }
        }, 8);
        
        router.init();
    },
    
    activateMonitoringControlKeys: function()
    {
        document.observe("keydown", function(e) {
            if (!e) e = window.event;
            var characterCode = e.which? e.which : e.keyCode;
            switch (characterCode) {
                case 17: //ctrl
                    window.ui.ctrlPressed = true;
                    $j('html').addClass('ctrl-pressed');
                    break;
                case 18: //alt
                    window.ui.altPressed = true;
                    $j('html').addClass('alt-pressed');
                    break;
                default: 
            }
        });
        document.observe("keyup", function(e) {
            if (!e) e = window.event;
            var characterCode = e.which? e.which : e.keyCode;
            switch (characterCode) {
                case 17: //ctrl
                    window.ui.ctrlPressed = false;
                    $j('html').removeClass('ctrl-pressed');
                    break;
                case 18: //alt
                    window.ui.altPressed = false;
                    $j('html').removeClass('alt-pressed');
                    break;
                default: 
            }
        });         
    },
    
    gotoTab: function(tabCode)
    {
        $j('#control_panel > .tab-selector li').removeClass("active");
        $j('#control_panel > .tab-selector li.' + tabCode).addClass("active");

        $j('#control_panel > .tab > div').hide();
        $j('#control_panel > .tab div.' + tabCode).show();
    },


    routeTasks: function(params)
    {
        this.gotoTab('tasks');
    },

    routeSettings: function(params)
    {
        this.gotoTab('settings');
        if (!this.settingsInited) {
            window.action.getSettings();
        }
        var page;
        if (params.page) {
            page = params.page
        } else {
            page = $j('#settings .menu-item:first').attr('data-page');
        } 
        this.selectSettingPage(page);
    },
    
    routeUtils: function(params)
    {
        this.gotoTab('utils');
    },

    routeUpdates: function(params)
    {
        this.gotoTab('updates');
    },

    selectSettingPage: function(page)
    {
        $j('#settings .menu-item').removeClass('selected');
        $j('#settings .menu-item[data-page="'+page+'"]').addClass('selected');
        $j('#settings .content').hide();
        $j('#settings .content.' + page).show();
    }, 
    
    switchFilter: function(el) 
    {
        if (el.attr('data-mode')=='expanded') {
            el.attr('data-mode', 'collapsed');
        } else {
            el.attr('data-mode', 'expanded');
        }
    },
    
    beginSearchGoogleImages: function()
    {
        var query = $j('#add_image .form.query').val();
        if (!query) {
            return;
        }
        var keyword = $j('#add_image .form.keyword').val();
        if (keyword) {
            query += ' ' + keyword;
        }
        var type = $j('#add_image .form.type').val();
        $j('#add_image .search-results').empty();
        window.action.searchGoogleImages(query, type);
    },
    
    drawImagesSearchResults: function(data)
    {
        $j('#add_image .search-results').html(TEMPLATES.IMAGES_SEARCH_RESULTS.process(data));
    },
    
    addImage: function(url)
    {
        var textarea = $j('#add_image').data('bindedTextarea');
        if (textarea && url) {
            var value = textarea.val() + "\n" + url;
            value = value.replace(/[\r\n]+/g, "\n");
            value = LMS.Text.trim(value, "\r\n");
            textarea.val(value)
                    .change();
        }
    },
    
    drawFilesTasks: function(data)
    {
        if (data.files_tasks && data.files_tasks.length) {
            $j('#tab-caption-tasks').show();
            var rest = 0;
            for (var i=0; i<data.files_tasks.length; i++) {
                var fileTask = data.files_tasks[i];
                rest += (parseInt(fileTask.size) - parseInt(fileTask.done));
            }

            var title = $j('#tab-caption-tasks a').html();
            title = title.replace(/\s+\(.*\)/,'');
            title = title + ' (' + LMS.Utils.HumanSize(rest) + ')';
            $j('#tab-caption-tasks a').html(title);

            $j('#tasks-list').html(TEMPLATES.TASKS.process(data));
        } else {
            $j('#tab-caption-tasks').hide();
            $j('#tasks-list').html('');
        }
    },
    
    getSettingParamValue: function(key)
    {
        return $j('#settings [data-param="' + key + '"]:input').val();
    },
    
    getSettingParamType: function(key)
    {
        return $j('#settings [data-param="' + key + '"]:input').attr('data-type');
    },
    
    drawSettings: function(data)
    {
        this.settingsUpdates = {};
        var html = TEMPLATES.SETTINGS.process(data);
        //console.log(html);
        $j('#settings').html(html);
//        var checkboxHandler = function()
//        {
//            var checked = $j(this).is(':checked');
//            $j(this).closest('tr').attr('data-default', checked? '0' : '1');
//        }
//        $j('#settings input.override-switch').each(checkboxHandler);
//        $j('#settings input.override-switch').click(checkboxHandler);

        var self = this;
        $j('#settings [data-param]:input').change(function(){
            var key = $j(this).attr('data-param');
            if (!self.settingsUpdates[key]) {
                self.settingsUpdates[key] = {};
            }
            if ($j(this).is('.default-switch')) {
                var checked = $j(this).is(':checked');
                self.settingsUpdates[key].active = checked? 0 : 1;
                if (!checked) {
                    self.settingsUpdates[key].value = self.getSettingParamValue(key);
                    self.settingsUpdates[key].type = self.getSettingParamType(key);
                }
            } else {
                var type = $j(this).attr('data-type');
//                var value;
//                switch (type) {
//                    case 'array': 
//                        value = $j(this).val().evalJSON();
//                        break;
//                    case 'scalar': 
//                    default: 
//                        value = $j(this).val();
//                        break;
//                }
                self.settingsUpdates[key].type = type;
                self.settingsUpdates[key].value = $j(this).val();
                self.settingsUpdates[key].active = 1;
            }
            var countChanges = Object.keys(self.settingsUpdates).length;
            var title = countChanges + ' ' + LMS.Text.declension(countChanges, ['изменение', 'изменения', 'изменений'], 'ru');
            
            $j('#settings .minibutton.save')
                .attr('title', title)
                .addClass('unsaved');
            var name = $j('#settings .minibutton.save span').html();
            name = name.replace(/\s+\*$/, '');
            name += ' *';
            name = 'Сохранить (' + title + ')';
            $j('#settings .minibutton.save span').html(name);
        });
        var checkboxHandler = function()
        {
            var checked = $j(this).is(':checked');
            $j(this).closest('tr').attr('data-default', checked? '1' : '0');
        }
        $j('#settings td.reset :input').click(checkboxHandler);
        $j('#settings td.reset :input').each(checkboxHandler);
        
//        $j('#settings td.reset label').mouseout(function(){
//            $j(this).closest('tr').attr('data-default', 0);
//        });
        var params = router.getParams();
        if (params.get('page')) {
            this.selectSettingPage(params.get('page'));
        }
        this.settingsInited = true;
    },
    
    saveSettings: function()
    {
        window.action.saveSettings(this.settingsUpdates);
    },
    
    
    drawUtils: function(data)
    {
        this.intensiveStatusMonitoring = false;
        for (var util in data) {
            var wrapperEl = $j('#utils .utility.' + util);
            if (wrapperEl.length) {
                var row = data[util];
                wrapperEl.find('.started_at').html(row.started_at);
                wrapperEl.find('.ended_at').html(row.ended_at);
                var status;
                switch (parseInt(row.status)) {
                    case 1:
                        status = 'В процессе';
                        this.intensiveStatusMonitoring = true;
                    break;
                    case 2:
                        status = 'Готово';
                    break;
                    default:
                        status = 'Ошибка';
                }
                wrapperEl.find('.status').html(status);
                wrapperEl.find('.message').html(row.message);
                if (row.has_report) {
                    wrapperEl.find('.has_report').html('<a onclick="window.action.getReport(' + row.log_id + ')" class="minibutton"><span>Отчет</span></a>');
                } else {
                    wrapperEl.find('.has_report').html('');
                }
            }
        }
    },

    _getSettingsValue:function(config, db, key)
    {
        if (Object.isUndefined(db[key])) {
            return config[key];
        } else {
            return config[db].value;
        }
    },
    
    _isOverrideSettings:function(db, key)
    {
        return !Object.isUndefined(db[key]) && db[key].active;
    },

    updateRatings: function ()
    {
        window.action.updateRatings();
        setTimeout(function(){
            window.action.getCurrentStatus();
        }, 3000);

    },

    updateLocalRatings: function ()
    {
        window.action.updateLocalRatings();
        setTimeout(function(){
            window.action.getCurrentStatus();
        }, 3000);

    },
    
    fixPersones: function ()
    {
        window.action.fixPersones();
        setTimeout(function(){
            window.action.getCurrentStatus();
        }, 3000);

    },

    checkFiles: function ()
    {
        window.action.checkFiles();
        setTimeout(function(){
            window.action.getCurrentStatus();
        }, 3000);

    },

    drawReport: function (data)
    {
        $j('#view_report .report').html(data);
        $j('#view_report').dialog({width: 700});
    },

    afterRelocateLostFiles: function(count) 
    {
        window.ui.showUserMessage('Исправлено путей к файлам: ' + count, true);
    },

    afterHideBrokenMovies: function(count) 
    {
        window.ui.showUserMessage('Скрыто фильмов: ' + count, true);
    },

    drawUpdatesCheck: function(data)
    {
        $j('#updates-info').html(TEMPLATES.UPDATES_CHECK.process(data));
    },

    drawUpgradeResult: function(data)
    {
        $j('#updates-info').html(TEMPLATES.UPGRADE_RESULT.process(data));
    },
    
    movies: {
        offset: null,
        pageSize: 50,
        total: 0,
        paginator: null,
        moviesInited: false,
        currentMovieId: null,
        init: function() 
        {
            this.initPaginator();
            LMS.Connector.connect('routeMovies', this, 'routeMovies');
            LMS.Connector.connect('drawMovie', this, 'drawMovie');
            LMS.Connector.connect('drawMovies', this, 'drawMovies');
            LMS.Connector.connect('afterAddFile', this, 'refreshMovie');
            LMS.Connector.connect('afterRemoveFile', this, 'refreshMovie');
            LMS.Connector.connect('afterRemoveParticipant', this, 'removeParticipant');
            LMS.Connector.connect('afterReparseFiles', this, 'refreshMovie');
            LMS.Connector.connect('afterInsertMoviePerson', this, 'refreshMovie');
            LMS.Connector.connect('afterRemoveMovie', this, 'clearMovie');
            LMS.Connector.connect('afterRemoveMovie', this, 'refresh');
            
            LMS.Connector.connect('afterSearchKinopoiskMovie', this, 'afterSearchKinopoiskMovie');
            LMS.Connector.connect('afterParseKinopoiskMovie', this, 'afterParseKinopoiskMovie');
            LMS.Connector.connect('afterReplaceKinopoiskMovie', this, 'refreshMovie');
            LMS.Connector.connect('afterMergeKinopoiskMovie', this, 'refreshMovie');

            LMS.Connector.connect('afterGetCatalogQualitiesAndTranslations', this, 'drawCatalogQualitiesAndTranslations');
        },
        
        beginSearchKinopoiskMovie: function()
        {
            var query = $j('#research_kinopoisk .form.query').val();
            if (!query) {
                return;
            }
            $j('#research_kinopoisk .search-results').empty();
            $j('#research_kinopoisk .parsed-info').empty();
            window.action.searchKinopoiskMovie(query);
        },
        
        afterSearchKinopoiskMovie: function(data)
        {
            this.drawSearchResults(data);
        },
        
        drawSearchResults: function(data)
        {
            var html = TEMPLATES.MOVIE_SEARCH_RESULTS.process(data);
            $j("#research_kinopoisk .search-results").html(html);
            
            $j("#research_kinopoisk .search-results ul li").click(function(){
                if ($j(this).hasClass("selected")) {
                    return;
                } else {
                    $j("#research_kinopoisk .search-results ul li").removeClass("selected");
                    $j(this).addClass("selected");
                    window.action.parseKinopoiskMovie($j(this).attr('data-url'));
                }
            });

        },

        afterParseKinopoiskMovie: function(data) 
        {
            this.drawParsedInfo(data);
        },
        
        drawParsedInfo: function(data)
        {
            $j("#research_kinopoisk .parsed-info").html(TEMPLATES.MOVIE_PARSED_INFO.process(data));
        },

        beginReplaceKinopoiskMovie: function(url)
        {
            $j('#research_kinopoisk').dialog('close');
            
            var merge = $j('#research_kinopoisk_merge').is(':checked');
            if (merge) {
                window.action.mergeKinopoiskMovie(this.currentMovieId, url);
            } else {
                window.action.replaceKinopoiskMovie(this.currentMovieId, url);
            }
        },

        drawCatalogQualitiesAndTranslations: function(data)
        {
            var select = $j('#movies .filter.panel .form.filter-quality');
            select.empty();
            select.append($j('<option value="">Любое</option>'));
            for (var i=0; i<data.qualities.length; i++) {
                var text = data.qualities[i].name + ' (' + data.qualities[i].count + ')';
                select.append($j('<option value="' + data.qualities[i].name + '">' + text + '</option>'));
            }
            
            var select = $j('#movies .filter.panel .form.filter-translation');
            select.empty();
            select.append($j('<option value="">Любое</option>'));
            for (var i=0; i<data.translations.length; i++) {
                var text = data.translations[i].name + ' (' + data.translations[i].count + ')';
                select.append($j('<option value="' + data.translations[i].name + '">' + text + '</option>'));
            }
        },
        
        refresh: function() 
        {
            var sort, order;
            var filterName = $j('#movies .filter.panel .form.filter-name').val();
            var filterQuality = $j('#movies .filter.panel .form.filter-quality').val();
            var filterTranslation = $j('#movies .filter.panel .form.filter-translation').val();
            var filterHidden = $j('#movies .filter.panel .checkbox.filter-hidden').attr('data-checked')=='1';
            if ($j('#movies .filter.panel .checkbox.filter-sortbyname ').attr('data-checked')=='1') {
                sort = 'name';
                order = 1;
            } else {
                sort = null;
                order = -1;
            }
            var filter = {
                'name': filterName,
                'quality': filterQuality,
                'translation': filterTranslation,
                'hidden': filterHidden
            }
            window.action.getMovies(
                this.offset, 
                this.pageSize,
                sort,
                order,
                filter
            );
        },
        
        refreshMovie: function() 
        {
            window.action.getMovie(this.currentMovieId);
        },

        routeMovies: function(params)
        {
            ui.gotoTab('movies');
            if (!this.moviesInited) {
                $j("#movies .chzn-select").chosen();
                window.action.getCatalogQualitiesAndTranslations();
                this.refresh();
            }
            if (params.id && params.id != this.currentMovieId) {
                window.action.getMovie(params.id);
            }
        },
        
        drawMovies: function(data)
        {
            ui.gotoTab('movies');
            this.offset = parseInt(data.offset);
            this.total = parseInt(data.total);
            this.setupPaginator(false);
            $j('#movies #movies-list').html(TEMPLATES.MOVIES.process(data));
            this.moviesInited = true;
        },

        drawMovie: function(data)
        {
            ui.gotoTab('movies');
            var movieId = data.movie.movie_id;
            $j('#movie').html(TEMPLATES.MOVIE.process(data));
            this.currentMovieId = movieId;
            $j('#movie [data-field][data-table="movies"]').attr('data-mid', movieId)
                                                          .change(this.changeMovieValueHandler);
            
            for (var i=0; i<data.movie.files.length; i++) { 
                var fileId = data.movie.files[i].file_id;
                $j('#movie [data-fid="' + fileId + '"] [data-field][data-table="files"]').attr('data-fid', fileId)
                                                                                         .change(this.changeFileValueHandler);
            }
            
            $j("#movie .form.translation").translationCombobox()
                                          .change(this.changeQualityOrTranslationHandler);
            $j("#movie .form.quality").qualityCombobox()
                                      .change(this.changeQualityOrTranslationHandler);
                                      
            $j('#movie table.files [data-table="movies"]').each(function(){
                var el = $j(this);
                window.ui.movies.updateFilePlaceholders(el);
            });

            for (var i=0; i<data.movie.participants.length; i++) { 
                var participantId = data.movie.participants[i].participant_id;
                $j('#movie table.participants tr[data-pid="' + participantId + '"] [data-field]').attr('data-pid', participantId)
                                                                                                 .change(this.changeParticipantValueHandler);
            }


            $j("#movie .chzn-select").hide();
            $j("#movie .countries").countries();
            $j("#movie .genres").genres();
            $j("#movie .roles").roles();
            $j("#movie .chzn-select").chosen();
            $j("#movie .persones-list").autoResize();
            
            var query = data.movie.name || data.movie.international_name;
            $j("#movie .form.covers").each(function(){
                var textarea = $j(this);
                textarea.organizeImages({
                    onAdd: function(){
                        $j('#add_image .form.query').val(query);
                        $j('#add_image .form.keyword').val('poster');
                        $j('#add_image .form.type').val('vertical');
                        $j('#add_image').data('bindedTextarea', textarea)
                                        .dialog({width: 960});
                        window.ui.beginSearchGoogleImages();
                    }
                });
            });
            $j("#movie .research-kinopoisk").click(function(){
                //$j('#research_kinopoisk .form.query').val(query);
                var query = $j('#movie .form[data-field="name"][data-table="movies"]').val();
                var found = query.match(/\/.*?"(.*?)"/);
                if (found) {
                    query = found[1];
                }
                $j('#research_kinopoisk').dialog({width: 960});
                
                var currentQuery = $j('#research_kinopoisk .form.query').val();
                if (currentQuery!=query) {
                    $j('#research_kinopoisk .form.query').val(query);
                    window.ui.movies.beginSearchKinopoiskMovie();
                }
            });

            $j('#movies a.selected').removeClass('selected');
            $j('#movies a[mid="' + movieId + '"]').addClass('selected');

        },
        
        clearMovie: function()
        {
            $j('#movie').html('');
            this.currentMovieId = null;
        },
        
        changeMovieValueHandler: function()
        {
            var el = $j(this);
            var movieId = el.attr('data-mid');
            var field = el.attr('data-field');
            if (el.get(0).tagName=='SELECT' && el.attr('multiple')) {
                var value = [];
                $j("option:selected", el).each(function () {
                    value.push($j(this).val());
                });
            } else {
                var value = el.val();
                el.attr('value', value);
            }
            if (el.parent().is('.narrow')) {
                el.attr('title', value);
            }
            window.action.setMovieField(movieId, field, value);
        },

        updateFilePlaceholders: function(el)
        {
            var field = el.attr('data-field');
            
            var topValue = $j('#movie [data-field="' + field + '"][data-table="movies"]').val();

            var elements = $j('#movie [data-field="' + field + '"][data-table="files"]');

            var parents = {};
            $j('#movie table.files tr.row').each(function() {
                var el = $j(this);
                var parentFileId = parseInt(el.attr('data-parent'));
                if (parentFileId) {
                    var fileId = parseInt(el.attr('data-fid'));
                    parents[fileId] = parentFileId;
                }
            });
            
            var list = {};
            elements.each(function() {
                var el = $j(this);
                var fileId = parseInt(el.attr('data-fid'));
                list[fileId] = {
                    'childs' : [],
                    'element' : el
                };
            });
            
            for (var fileId in list) {
                var parentFileId = parents[fileId];
                if (parentFileId) {
                    list[parentFileId]['childs'].push(list[fileId]);
                }
            }
            
            //reset placeholder
            for (var fileId in list) {
                var item = list[fileId];
                item.element.attr('placeholder', topValue);
            }

            //inherit placeholder
            for (var fileId in list) {
                var item = list[fileId];
                var value = item.element.val() || item.element.attr('placeholder');
                if (value) {
                    item.childs.each(function(child){
                        child.element.attr('placeholder', value);
                    })
                }
            }
        },
        
        changeQualityOrTranslationHandler: function()
        {
            var el = $j(this);
            window.ui.movies.updateFilePlaceholders(el);
        },

        changeFileValueHandler: function()
        {
            var el = $j(this);
            var fileId = el.attr('data-fid');
            var field = el.attr('data-field');
            if (el.get(0).tagName=='SELECT') {
                var value = [];
                $j("option:selected", el).each(function () {
                    value.push($j(this).val());
                });
            } else {
                var value = el.val();
                el.attr('value', value);
            }
            if (el.parent().is('.narrow')) {
                el.attr('title', value);
            }
            window.action.setFileField(fileId, field, value);
        },
        
        changeParticipantValueHandler: function()
        {
            var el = $j(this);
            var participantId = el.attr('data-pid');
            var field = el.attr('data-field');
            var value = el.val();
            el.attr('value', value);
            window.action.setParticipantField(participantId, field, value);
        },

        insertPersones: function(movieId)
        {
            var roleId = $j('#movie .persones-add .roles option:selected').val();

            var list = $j('#movie .persones-add .persones-list').val();
            var p = list.split(/(?:,|;|\r|\n|\r\n)/);
            var persones = [];
            for (var i=0; i<p.length; i++) {
                var namesString = LMS.Text.trim(p[i], " \t\r\n/\\|()[]");
                var names = namesString.split(/(?:\(|\||\[|\/)/g);
                for (var j=0; j<names.length; j++) {
                    names[j] = LMS.Text.trim( names[j]);
                }
                persones.push({
                    'names': names,
                    'role_id' : roleId
                });
            }
            window.action.insertMoviePerson(movieId, persones);
           
        },

        addFile: function(movieId)
        {
            var path = $j('#movie .form.path.new-file').val();
            if (path) {
                window.action.addFile(movieId, path);
            }
        },
        
        removeParticipant: function(participantId)
        {
            $j('#movie table.participants tr[data-pid="' + participantId + '"]').remove();
        },
        
        beginRemoveFile: function(fileId)
        {
            var filename = $j('#movie table.files [data-fid="' + fileId + '"].form.name').val();
            if (confirm('Удалить ссылку на "' + filename + '"?')) {
                window.action.removeFile(fileId);
            }
        },
        
        beginRemoveMovie: function(movieId)
        {
            if (confirm('Удалить фильм из базы данных')) {
                window.action.removeMovie(movieId);
            }
        },
        
        setOffset: function(offset)
        {
            this.offset = offset;
            this.refresh();
        }, 

        initPaginator: function()
        {
            this.paginator = LMS.Widgets.Factory('PageIndexBox');
            this.paginator.setDOMId('paginator_movies');
            this.paginator.beforePagesText = "";
            this.paginator.prevPageText = "";
            this.paginator.nextPageText = "";
            LMS.Connector.connect(this.paginator, 'valueChanged', this, 'setOffset');
        },

        setupPaginator: function(allowEmitPaginator)
        {
            this.paginator.setPageSize(this.pageSize);
            this.paginator.setCount(this.total);
            this.paginator.setOffset(this.offset, allowEmitPaginator);
            this.paginator.paint();
        }
    },
    
    persones: {
        offset: null,
        pageSize: 50,
        total: 0,
        paginator: null,
        personesInited: false,
        init: function() 
        {
            this.initPaginator();
            LMS.Connector.connect('routePersones', this, 'routePersones');
            LMS.Connector.connect('drawPerson', this, 'drawPerson');
            LMS.Connector.connect('drawPersones', this, 'drawPersones');
            LMS.Connector.connect('afterParsePerson', action, 'getPerson');
        },
        
        refresh: function() 
        {
            var sort, order;
            var filterName = $j('#persones .filter.panel .form.filter-name').val();
            if ($j('#persones .filter.panel .checkbox.filter-sortbyname ').attr('data-checked')=='1') {
                sort = 'name';
                order = 1;
            } else {
                sort = null;
                order = -1;
            }
            var filter = {
                'name': filterName
            }
            window.action.getPersones(
                this.offset, 
                this.pageSize,
                sort,
                order,
                filter
            );
        },
        
        refreshPerson: function() 
        {
            window.action.getPerson(this.currentPersonId);
        },
        
        routePersones: function(params)
        {
            ui.gotoTab('persones');
            if (!this.personesInited) {
                this.refresh();
            }
            if (params.id && params.id != this.currentPersonId) {
                window.action.getPerson(params.id);
            }
        },
        
        highlightCurrentPerson: function()
        {
            $j('#persones a.selected').removeClass('selected');
            if (this.currentPersonId) {
                $j('#persones a[pid="' + this.currentPersonId + '"]').addClass('selected');
            }
        },
        
        drawPersones: function(data)
        {
            ui.gotoTab('persones');
            this.offset = parseInt(data.offset);
            this.total = parseInt(data.total);
            this.setupPaginator(false);
            $j('#persones #persones-list').html(TEMPLATES.PERSONES.process(data));
            this.personesInited = true;
            this.highlightCurrentPerson();
        },

        drawPerson: function(data)
        {
            ui.gotoTab('persones');
            var personId = data.person.person_id;
            $j('#person').html(TEMPLATES.PERSON.process(data));
            $j('#person [data-field]').attr('data-pid', personId);
            $j('#person [data-field]').change(this.changePersonValueHandler);
            
            var query = data.person.international_name || data.person.name;
            $j("#person .form.photos").each(function(){
                var textarea = $j(this);
                textarea.organizeImages({
                    onAdd: function(){
                        $j('#add_image .form.query').val(query);
                        $j('#add_image .form.keyword').val('');
                        $j('#add_image .form.type').val('');
                        $j('#add_image').data('bindedTextarea', textarea)
                                        .dialog({width: 960});
                        window.ui.beginSearchGoogleImages();
                    }
                });
            });
            
            this.currentPersonId = personId;

            this.highlightCurrentPerson();

        },

        changePersonValueHandler: function()
        {
            var el = $j(this);
            var personId = el.attr('data-pid');
            var field = el.attr('data-field');
            var value = el.val();
            el.attr('value', value);
            window.action.setPersonField(personId, field, value);
        },
        
        setOffset: function(offset)
        {
            this.offset = offset;
            this.refresh();
        }, 

        initPaginator: function()
        {
            this.paginator = LMS.Widgets.Factory('PageIndexBox');
            this.paginator.setDOMId('paginator_persones');
            this.paginator.beforePagesText = "";
            this.paginator.prevPageText = "";
            this.paginator.nextPageText = "";
            LMS.Connector.connect(this.paginator, 'valueChanged', this, 'setOffset');
        },

        setupPaginator: function(allowEmitPaginator)
        {
            this.paginator.setPageSize(this.pageSize);
            this.paginator.setCount(this.total);
            this.paginator.setOffset(this.offset, allowEmitPaginator);
            this.paginator.paint();
        }
    },

    users: {
        offset: null,
        pageSize: 50,
        total: 0,
        paginator: null,
        usersInited: false,
        init: function() 
        {
            this.initPaginator();
            LMS.Connector.connect('routeUsers', this, 'routeUsers');
            LMS.Connector.connect('drawUser', this, 'drawUser');
            LMS.Connector.connect('drawUsers', this, 'drawUsers');
        },
        
        refresh: function() 
        {
            var sort, order;
            var filterLogin = $j('#users .filter.panel .form.filter-login').val();
            var filterIp = $j('#users .filter.panel .form.filter-ip').val();
            if ($j('#users .filter.panel .checkbox.filter-sortbyname ').attr('data-checked')=='1') {
                sort = 'login';
                order = 1;
            } else {
                sort = null;
                order = -1;
            }
            var filter = {
                'login': filterLogin,
                'ip': filterIp
            }
            window.action.getUsers(
                this.offset, 
                this.pageSize,
                sort,
                order,
                filter
            );
        },

        refreshUser: function() 
        {
            window.action.getUser(this.currentUserId);
        },

        routeUsers: function(params)
        {
            ui.gotoTab('users');
            if (!this.usersInited) {
                this.refresh();
            }
            if (params.id && params.id != this.currentUserId) {
                window.action.getUser(params.id);
            }
        },
        
        highlightCurrentUser: function()
        {
            $j('#users a.selected').removeClass('selected');
            if (this.currentUserId) {
                $j('#users a[pid="' + this.currentUserId + '"]').addClass('selected');
            }
        },
        
        drawUsers: function(data)
        {
            ui.gotoTab('users');
            this.offset = parseInt(data.offset);
            this.total = parseInt(data.total);
            this.setupPaginator(false);
            $j('#users #users-list').html(TEMPLATES.USERS.process(data));
            this.usersInited = true;
            this.highlightCurrentUser();
        },

        drawUser: function(data)
        {
            ui.gotoTab('users');
            var userId = data.user.ID;
            $j('#user').html(TEMPLATES.USER.process(data));
            $j('#user [data-field]').attr('data-uid', userId);
            $j('#user [data-field]').change(this.changeUserValueHandler);
            this.currentUserId = userId;

            this.highlightCurrentUser();

        },

        changeUserValueHandler: function()
        {
            var el = $j(this);
            var userId = el.attr('data-uid');
            var field = el.attr('data-field');
            var value = el.val();
            el.attr('value', value);
            window.action.setUserField(userId, field, value);
        },
        
        setOffset: function(offset)
        {
            this.offset = offset;
            this.refresh();
        }, 

        initPaginator: function()
        {
            this.paginator = LMS.Widgets.Factory('PageIndexBox');
            this.paginator.setDOMId('paginator_users');
            this.paginator.beforePagesText = "";
            this.paginator.prevPageText = "";
            this.paginator.nextPageText = "";
            LMS.Connector.connect(this.paginator, 'valueChanged', this, 'setOffset');
        },

        setupPaginator: function(allowEmitPaginator)
        {
            this.paginator.setPageSize(this.pageSize);
            this.paginator.setCount(this.total);
            this.paginator.setOffset(this.offset, allowEmitPaginator);
            this.paginator.paint();
        }
    }
    
};