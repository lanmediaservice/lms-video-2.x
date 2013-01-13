/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: UI.js 700 2011-06-10 08:40:53Z macondos $
 */

if (!LMS.Cp) {
    LMS.Cp = {};
}

if (!LMS.Cp.Incoming) {
    LMS.Cp.Incoming = {};
}

LMS.Cp.Incoming.UI = {
    incoming: {
        data: null,
        defaultEngines: ['kinopoisk'],
        offset: null,
        pageSize: 20,
        total: 0,
        paginator: null,
        incomingInited: false,
        selected: [],
        autoParseFilesEnabled: false,
        autoSearchEnabled: false,
        autoParseEnabled: false,
        autoImportEnabled: false,
        init: function() 
        {
            this.initPaginator();
            LMS.Connector.connect('routeIncoming', this, 'routeIncoming');
            LMS.Connector.connect('drawIncoming', this, 'drawIncoming');
            LMS.Connector.connect('drawIncomingDetails', this, 'drawIncomingDetails');
            LMS.Connector.connect('drawIncomingInfoForm', this, 'drawInfoForm');
            LMS.Connector.connect('drawLocalSearch', this, 'drawLocalSearch');
            
            LMS.Connector.connect('afterSearchMovie', this, 'afterSearchMovie');
            LMS.Connector.connect('afterParseMovie', this, 'afterParseMovie');
            LMS.Connector.connect('afterParseIncomingFiles', this, 'afterParseFiles');

            LMS.Connector.connect('afterRemoveIncomingPerson', this, 'removeIncomingPerson');
            LMS.Connector.connect('afterInsertIncomingPerson', window.action, 'getIncomingDetails');

            LMS.Connector.connect('afterExpandIncoming', this, 'refresh');
            LMS.Connector.connect('afterCollapseIncoming', this, 'refresh');

            LMS.Connector.connect('afterHideIncoming', this, 'refresh');
            LMS.Connector.connect('afterUnhideIncoming', this, 'unhideIncoming');

            LMS.Connector.connect('afterImportIncoming', this, 'afterImportIncoming');
            LMS.Connector.connect('afterGetAttachInfo', this, 'drawAttachForm');
            LMS.Connector.connect('afterAttachFile', this, 'afterAttachFile');
        },

        refresh: function(forceScan) 
        {
            var showHidden = parseInt($j('#incoming .checkbox.filter-hidden').attr('data-checked'));
            window.action.getIncoming(this.offset, this.pageSize, showHidden, forceScan);
        },

        routeIncoming: function(params)
        {
            ui.gotoTab('incoming');
            if (!this.incomingInited) {
                this.refresh(true);
            }
        },
        
        changeIncomingValueHandler: function()
        {
            var el = $j(this);
            var incomingId = el.attr('data-fid');
            var field = el.attr('data-field');
            if (el.get(0).tagName=='SELECT') {
                var value = [];
                $j("option:selected", el).each(function () {
                    value.push($j(this).text());
                });
            } else {
                var value = el.val();
                el.attr('value', value);
            }
            if (el.parent().is('.narrow')) {
                el.attr('title', value);
            }
            
            window.action.setIncomingField(incomingId, field, value);
        },

        drawIncoming: function(data)
        {
            this.data = data;
            ui.gotoTab('incoming');
            this.offset = parseInt(data.offset);
            this.total = parseInt(data.total);
            this.setupPaginator(false);
            $j('#incoming .incoming-wrapper').html(TEMPLATES.INCOMING.process(data));
            $j('#incoming tr.row [data-field]').change(this.changeIncomingValueHandler);
            
            this.incomingInited = true;
            this.selectNone();
            this.autoParseFilesEnabled = false;
            this.autoSearchEnabled = false;
        },

        unhideIncoming: function(incomingIds)
        {
            for (var i=0; i<incomingIds.length; i++) {
                var row = this.getRowElement(incomingIds[i]);
                row.removeClass('file-hidden');
            }
        },

        toggleIncomingDetails: function(incomingId)
        {
            var row = this.getRowElement(incomingId);
            if (row.hasClass('show-info')) {
                this.getInfoElement(incomingId).remove();
                row.removeClass('show-info');
            } else {
                window.action.getIncomingDetails(incomingId);
            }
        },

        drawIncomingDetails: function(incomingId, data)
        {
            this.getInfoElement(incomingId).remove();
            this.getRowElement(incomingId).after(TEMPLATES.INCOMING_DETAILS.process(data))
                                                                         .addClass('show-info');
            var infoElement = this.getInfoElement(incomingId);
            $j(".tabs", infoElement).tabs();
            $j(".buttonset", infoElement).buttonset();
           /* $j(".button-search", infoElement).button({
                icons: {
                    primary: "ui-icon-search"
                },
                text: false
            });*/
            if (data.search_results) {
                this.drawSearchResults(incomingId, data.search_results, data.parsing_url);
            }
            if (data.parsed_info) {
                this.drawParsedInfo(incomingId, data.parsed_info);
            }
            this.drawInfoForm(incomingId, data.info);

            if (data.files) {
                this.drawFilesForm(incomingId, data);
            }

            var engines = [];
            if (data.search_results.sections && data.search_results.sections.length) {
                for (var i=0; i<data.search_results.sections.length; i++) {
                    var section = data.search_results.sections[i];
                    engines.push(section.name);
                }
            } else {
                engines = this.defaultEngines;
            }
            for (var i=0; i<engines.length; i++) {
                this.onEngineClickHandler(incomingId, engines[i]);
            }
        },

        searchMovie: function(incomingId)
        {
            var infoElement = this.getInfoElement(incomingId);
            var query = $j('.query', infoElement).val();
            var engines = [];
            var wrapperInfo = $j('.wrapper-info', infoElement);
            $j.each(['kinopoisk', 'ozon', 'world-art', 'sharereactor', 'imdb'], function(index, value) { 
                if (wrapperInfo.hasClass('show-' + value)) {
                    engines.push(value);
                }
            });

            if (query && engines.length) {
                window.action.searchMovie(incomingId, query, engines);
            }
        },
        
        afterSearchMovie: function(incomingId, data)
        {
            this.drawSearchResults(incomingId, data);
            if (this.autoSearchEnabled) {
                this.autoSearch();
            }
        },
        
        drawSearchResults: function(incomingId, data, selectedUrls)
        {
            var infoElement = this.getInfoElement(incomingId);

            $j(".search-box-results", infoElement).html(TEMPLATES.INCOMING_DETAILS_SEARCH_RESULTS.process(data));
            var self = this;
            var statusElement = $j("span.status", this.getRowElement(incomingId));
            $j(".search-box-results ul li", infoElement).click(function(){
                if ($j(this).hasClass("selected")) {
                    return;
                    //$j(this).removeClass("selected");
                } else {
                    var replace = !window.ui.ctrlPressed;
                    if (replace) {
                        $j(".search-box-results ul li", infoElement).removeClass("selected");
                    }
                    $j(this).addClass("selected");
                    window.action.setParsingUrl(incomingId, $j(this).attr('data-url'), replace);
                    self.getFile(incomingId).is_result_selected = true;
                    statusElement.addClass('result-selected');
                }
            });
            $j(".search-box-results ul", infoElement).click(function(event){
                if (event.target.nodeName=='UL') {
                    window.action.setParsingUrl(incomingId, null, true);
                    self.deselectSearchItems(incomingId);
                    self.getFile(incomingId).is_result_selected = false;
                    statusElement.removeClass('result-selected');
                }
            });
            //$j(".search-results ul", infoElement).click(this.deselectSearchItems.bind(this));
            $j(".search-box-results ul li", infoElement).dblclick(function(){
                var replace = !window.ui.ctrlPressed;
                window.action.parseMovie(incomingId);
            });
            this.getFile(incomingId).is_searched = true;
            
            var statusElement = $j("span.status", this.getRowElement(incomingId));
            statusElement.addClass('searched');

            
            if (!Object.isUndefined(selectedUrls)) {
                for (var i=0; i<selectedUrls.length; i++) {
                    $j('.search-box-results ul li[data-url="' + selectedUrls[i] + '"]', infoElement).addClass("selected");
                }
            }
            
        },

        afterParseMovie: function(incomingId, data) 
        {
            this.drawParsedInfo(incomingId, data.parsed_info);
            this.drawInfoForm(incomingId, data.info);
            if (this.autoParseEnabled) {
                this.autoParse();
            }
        },

        afterParseFiles: function(incomingId, data) 
        {
            this.drawFilesForm(incomingId, data);
            
            if (data.metainfo) {
                var html = '<span title="${video.info}">${video.label}</span>'.process(data.metainfo);
                $j(".metainfo", this.getRowElement(incomingId)).html(html);
            }

            var html = '<span title="${size}">${LMS.Utils.HumanSize(size)}</span>'.process(data);
            $j(".size", this.getRowElement(incomingId)).html(html);
            
            var file = this.getFile(incomingId);
            file.metainfo = data.metainfo;
            file.size = data.size;
            file.is_metainfo_parsed = true;
            
            if (this.autoParseFilesEnabled) {
                this.autoParseFiles();
            }
        },

        afterAttachFile: function(incomingId) 
        {
            this.removeIncoming(incomingId);
        },

        afterImportIncoming: function(incomingId) 
        {
            this.removeIncoming(incomingId);
            if (this.autoImportEnabled) {
                this.autoImport();
            }
        },

        drawParsedInfo: function(incomingId, data)
        {
            var infoElement = this.getInfoElement(incomingId);
            //console.log(TEMPLATES.INCOMING_PARSED_INFO.process({movie:data}));
            $j(".parsed-info-box", infoElement).html(TEMPLATES.INCOMING_DETAILS_PARSED_INFO.process({movie:data}));
        },
        
        drawLocalSearch: function(incomingId, data)
        {
            data.incoming_id = incomingId;
            var infoElement = this.getInfoElement(incomingId);
            infoElement.find(".local-search-results").html(TEMPLATES.INCOMING_DETAILS_LOCAL_SEARCH_RESULTS.process(data));
        },

        drawInfoForm: function(incomingId, data)
        {
            var statusElement = $j("span.status", this.getRowElement(incomingId));
            var isParsed = false;
            if (!data || $j.isEmptyObject(data)) {
                statusElement.removeClass('parsed');
            } else {
                statusElement.addClass('parsed');
                isParsed = true;
            }
            this.getFile(incomingId).is_parsed = isParsed;
            
            var infoForm = $j(".info-form", this.getInfoElement(incomingId));
            infoForm.html(TEMPLATES.INCOMING_DETAILS_FORM.process({movie:data, incoming_id: incomingId}));
            $j('[data-field]', infoForm).attr('data-fid', incomingId);
            $j('[data-field]', infoForm).change(this.changeIncomingValueHandler);
            $j(".countries", infoForm).countries();
            $j(".genres", infoForm).genres();
            $j(".roles", infoForm).roles();
            $j(".chzn-select", infoForm).chosen();
            $j(".persones-list", infoForm).autoResize();
            
            var query = data.name || data.international_name;
            infoForm.find(".form.poster").each(function(){
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
            
        },

        drawFilesForm: function(incomingId, data)
        {
            data.incoming_id = incomingId;
            var infoElement = this.getInfoElement(incomingId);
            if (infoElement) {
                var filesForm = $j(".files-form", infoElement);
                filesForm.html(TEMPLATES.INCOMING_DETAILS_FILES_FORM.process(data))
                            .attr('data-inited', 1);
                $j('[data-field]', filesForm).attr('data-fid', incomingId);
                $j('[data-field]', filesForm).change(this.changeIncomingValueHandler);
                $j(".form.translation", filesForm).translationCombobox()
                                                  .change(this.changeQualityOrTranslationHandler);
                $j(".form.quality", filesForm).qualityCombobox()
                                              .change(this.changeQualityOrTranslationHandler);
                
                $j('thead .form.quality, thead .form.translation', filesForm).each(function(){
                    var el = $j(this);
                    window.ui.incoming.updateFilePlaceholders(el);
                });
            }
        },

        initFilesForm: function(incomingId)
        {
            var filesForm = $j(".files-form", this.getInfoElement(incomingId));
            if (!filesForm.attr('data-inited')) {
                window.action.parseIncomingFiles(incomingId);
            }
        },
        
        setFilePlaceholders: function(incomingId, el)
        {
            el = $j(el);
            var fieldGroup = el.attr('data-field-group');
            var value = el.val();
            var infoElement = this.getInfoElement(incomingId);
            $j('[data-field-group="' + fieldGroup + '"]', infoElement).attr('placeholder', value);
        },

        updateFilePlaceholders: function(el)
        {
            var field = el.attr('data-field-group');
            var incomingId = el.attr('data-fid');
            var infoElement = this.getInfoElement(incomingId);
            var filesForm = $j(".files-form", infoElement);
            
            var topValue = $j('thead [data-field-group="' + field + '"]', filesForm).val();

            var elements = $j('tbody [data-field-group="' + field + '"]', filesForm);

            var parents = {};
            $j('tbody tr.row', filesForm).each(function() {
                var el = $j(this);
                var parentNum = parseInt(el.attr('data-parent'));
                if (parentNum) {
                    var fileNum = parseInt(el.attr('data-num'));
                    parents[fileNum] = parentNum;
                }
            });
            
            var list = {};
            elements.each(function() {
                var el = $j(this);
                var fileNum = parseInt(el.attr('data-num'));
                list[fileNum] = {
                    'childs' : [],
                    'element' : el
                };
            });
            
            for (var fileNum in list) {
                var parentNum = parents[fileNum];
                if (parentNum) {
                    list[parentNum]['childs'].push(list[fileNum]);
                }
            }
            
            //reset placeholder
            for (var fileNum in list) {
                var item = list[fileNum];
                item.element.attr('placeholder', topValue);
            }

            //inherit placeholder
            for (var fileNum in list) {
                var item = list[fileNum];
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
            window.ui.incoming.updateFilePlaceholders(el);
        },


        deselectSearchItems: function(incomingId)
        {
            var infoElement = this.getInfoElement(incomingId);
            $j(".search-box-results ul li", infoElement).removeClass("selected");
        },

        getInfoElement: function(incomingId)
        {
            return $j('#incoming table.incoming tr.info[fid=' + incomingId + ']');
        },

        getRowElement: function(incomingId)
        {
            return $j('#incoming table.incoming tr.row[fid=' + incomingId + ']');
        },    

        onEngineClickHandler: function(incomingId, engine)
        {
            var infoElement = this.getInfoElement(incomingId);
            var btn = $j('.engine-selector li.' + engine, infoElement);
            var wrapperInfo = $j('.wrapper-info', infoElement);
            if (btn.hasClass('selected')) {
                btn.removeClass('selected');
                wrapperInfo.removeClass('show-' + engine);
            } else {
                btn.addClass('selected');
                wrapperInfo.addClass('show-' + engine);
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
            this.paginator.setDOMId('paginator_incoming');
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
        },
        
        selectHandler: function(incomingId, el)
        {
            if ($j(el).is(':checked')) {
                this.selected.push(incomingId);
                this.selected = this.selected.uniq();
            } else {
                this.selected = this.selected.without(incomingId);
            }
            this.groupOperationCheckVisible();
        },
        
        selectNone: function()
        {
            this.selected = [];
            this.groupOperationCheckVisible();
        },

        groupOperationCheckVisible: function()
        {
            if (this.selected.length) {
                $j('#incoming .group-operations').show();
            } else {
                $j('#incoming .group-operations').hide();
            }
        },
        
        hideSelected: function()
        {
            window.action.hideIncoming(this.selected);
        },
        
        unhideSelected: function()
        {
            window.action.unhideIncoming(this.selected);
        },
        
        autoParseFiles: function()
        {
            this.autoParseFilesEnabled = true;
            for (var i=0; i<this.data.files.length; i++) {
                var file = this.data.files[i];
                if (!file.is_metainfo_parsed && !file.expanded) {
                    window.action.parseIncomingFiles(file.incoming_id);
                    return;
                }
            }
            this.autoParseFilesEnabled = false;
        },

        autoSearch: function()
        {
            this.autoSearchEnabled = true;
            for (var i=0; i<this.data.files.length; i++) {
                var file = this.data.files[i];
                if (!file.is_searched && !file.expanded) {
                    var incomingId = file.incoming_id;
                    window.action.autoSearchMovie(incomingId);
                    var row = this.getRowElement(incomingId);
                    if (!row.hasClass('show-info')) {
                        window.action.getIncomingDetails(incomingId);
                    }
                    return;
                }
            }
            this.autoSearchEnabled = false;
        },

        autoParse: function()
        {
            this.autoParseEnabled = true;
            for (var i=0; i<this.data.files.length; i++) {
                var file = this.data.files[i];
                if (file.is_result_selected && !file.is_parsed && !file.expanded) {
                    window.action.parseMovie(file.incoming_id);
                    return;
                }
            }
            this.autoParseEnabled = false;
        },

        autoImport: function()
        {
            this.autoImportEnabled = true;
            for (var i=0; i<this.data.files.length; i++) {
                var file = this.data.files[i];
                if (file.metainfo && file.is_parsed && !file.is_duplicate) {
                    window.action.importIncoming(file.incoming_id);
                    return;
                }
            }
            this.autoImportEnabled = false;
        },

        beginImport: function(incomingId)
        {
            var file = this.getFile(incomingId);
            
            if ((file.metainfo || confirm("Не спарсена метаинформация. Продолжить импортирование?"))
                && (file.is_parsed || confirm("Не спарсена информация с сайтов. Продолжить импортирование?"))
                && (!file.is_duplicate || confirm("Файл отмечен как дубликат. Продолжить импортирование?"))
            ) {
                window.action.importIncoming(incomingId);
            }            
        },
        
        removeIncoming: function(incomingId)
        {
            this.getInfoElement(incomingId).remove();
            this.getRowElement(incomingId).remove();
            for (var i=0; i<this.data.files.length; i++) {
                if (this.data.files[i].incoming_id == incomingId) {
                    this.data.files.splice(i, 1);
                    break;
                }
            }
        },
        
        removeIncomingPerson: function(incomingId, personIndex)
        {
            $j('li[pid=' + personIndex + ']', this.getInfoElement(incomingId)).remove();
        },
        
        insertPersones: function(incomingId)
        {
            var infoElement = this.getInfoElement(incomingId);
            var role = $j('.roles option:selected', infoElement).text();

            var list = LMS.Text.trim($j('.persones-list', infoElement).val());
            //if (list.length) {
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
                        'role' : role
                    });
                }
                window.action.insertIncomingPerson(incomingId, persones);
            //}
           
        },
        
        getFile: function(incomingId)
        {
            for (var i=0; i<this.data.files.length; i++) {
                var file = this.data.files[i];
                if (file.incoming_id == incomingId) {
                    return file;
                }
            }
        },
        
        beginAttachFile: function(incomingId, movieId)
        {
            window.action.getAttachInfo(incomingId, movieId);
        },
        
        drawAttachForm: function(incomingId, movieId, data)
        {
            var source = this.getFile(incomingId);
            data['source'] = source;
            //console.log(TEMPLATES.ATTACH_FILE_FORM.process(data));
            $j('#attach_file .af-form').html(TEMPLATES.ATTACH_FILE_FORM.process(data));
            if (data.folders.length) {
                var targetPath = data.folders[0].path + '/' + source.basename;
                $j('#attach_file .form.af-target-location').val(targetPath);
            }
            $j('#attach_file').attr('data-mode', 'move')
                              .attr('data-delete', '0')
                              .attr('data-up', '1')
                              .attr('data-fid', incomingId)
                              .attr('data-mid', movieId)
                              .dialog({width: 700});
        },
        
        selectTargetFolderHandler: function(el)
        {
            var targetPath = $j(el).attr('data-path');
            $j('#attach_file .form.af-target-location').val(targetPath);
        },
        
        attachFile: function()
        {
            var attachMode = $j('#attach_file').attr('data-mode');
            var targetPath = null;
            
            if (attachMode=='move') {
                targetPath = $j('#attach_file .form.af-target-location').val();
                if (!targetPath) {
                    LMS.Utils.emit('highlightElement', $j('#attach_file .form.af-target-location').get(0));
                    return;
                }
            }
            var deleteFiles = [];
            if ($j('#attach_file').attr('data-delete')=="1") {
                $j('#attach_file .af-files input.delete:checked').each(function(){
                    var el = $j(this);
                    deleteFiles.push(el.attr('data-fid'));
                });
            }
            
            var incomingId = $j('#attach_file').attr('data-fid');
            var movieId = $j('#attach_file').attr('data-mid');
            var up = $j('#attach_file').attr('data-up');
            window.action.attachFile(incomingId, movieId, targetPath, deleteFiles, up);
            $j('#attach_file').dialog('close');
        }
    }
};