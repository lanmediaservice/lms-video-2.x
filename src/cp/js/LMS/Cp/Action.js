/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Action.js 700 2011-06-10 08:40:53Z macondos $
 */
 
if (!LMS.Cp) {
    LMS.Cp = {};
}

LMS.Cp.Action = {
 
    getTranslations: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.getTranslations'
            },
            function(result) {
                if (200 == result.status) {
                    REFERENCE.TRANSLATIONS = result.response;
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getQualities: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.getQualities'
            },
            function(result) {
                if (200 == result.status) {
                    REFERENCE.QUALITIES = result.response;
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    getGenres: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.getGenres'
            },
            function(result) {
                if (200 == result.status) {
                    REFERENCE.GENRES = result.response;
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getCountries: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.getCountries'
            },
            function(result) {
                if (200 == result.status) {
                    REFERENCE.COUNTRIES = result.response;
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getRoles: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.getRoles'
            },
            function(result) {
                if (200 == result.status) {
                    REFERENCE.ROLES = result.response;
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getDefaultEngines: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.getDefaultEngines'
            },
            function(result) {
                if (200 == result.status) {
                    LMS.Cp.Incoming.UI.incoming.defaultEngines = result.response;
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    getIncoming: function (offset, size, showHidden, forceScan)
    {
        var self = this;
        this.query({
                'action' : 'Cp.getIncoming',
                'offset' : offset,
                'size' : size,
                'show_hidden' : showHidden,
                'force_scan': !Object.isUndefined(forceScan)? forceScan : false
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawIncoming', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    setIncomingField: function(incomingId, field, value)
    {
        var self = this;
        this.query({
                'action' : 'Cp.setIncomingField',
                'incoming_id' : incomingId,
                'field' : field,
                'value' : value
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    clearIncomingInfo: function(incomingId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.clearIncomingInfo',
                'incoming_id' : incomingId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawIncomingInfoForm', incomingId, {});
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    expandIncoming: function (incomingId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.expandIncoming',
                'incoming_id' : incomingId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterExpandIncoming');
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    collapseIncoming: function (incomingId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.collapseIncoming',
                'incoming_id' : incomingId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterCollapseIncoming');
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    hideIncoming: function (incomingIds)
    {
        var self = this;
        this.query({
                'action' : 'Cp.hideIncoming',
                'incoming_ids' : incomingIds
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterHideIncoming', incomingIds);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    unhideIncoming: function (incomingIds)
    {
        var self = this;
        this.query({
                'action' : 'Cp.unhideIncoming',
                'incoming_ids' : incomingIds
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterUnhideIncoming', incomingIds);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    getIncomingDetails: function (incomingId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.getIncomingDetails',
                'incoming_id' : incomingId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawIncomingDetails', incomingId, result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    searchMovie: function (incomingId, query, engines)
    {
        var self = this;
        this.query({
                'action' : 'Cp.searchMovie',
                'incoming_id' : incomingId,
                'query' : query,
                'engines' : engines
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterSearchMovie', incomingId, result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    searchKinopoiskMovie: function (query)
    {
        var self = this;
        this.query({
                'action' : 'Cp.searchKinopoiskMovie',
                'query' : query
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterSearchKinopoiskMovie', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    autoSearchMovie: function (incomingId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.autoSearchMovie',
                'incoming_id' : incomingId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterSearchMovie', incomingId, result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    setParsingUrl: function(incomingId, url, replace)
    {
        var self = this;
        this.query({
                'action' : 'Cp.setParsingUrl',
                'incoming_id' : incomingId,
                'url' : url,
                'replace' : replace
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    removeIncomingPerson: function(incomingId, personIndex)
    {
        var self = this;
        this.query({
                'action' : 'Cp.removeIncomingPerson',
                'incoming_id' : incomingId,
                'person_index' : personIndex
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterRemoveIncomingPerson', incomingId, personIndex);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    insertIncomingPerson: function(incomingId, persones)
    {
        var self = this;
        this.query({
                'action' : 'Cp.insertIncomingPerson',
                'incoming_id' : incomingId,
                'persones' : persones
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterInsertIncomingPerson', incomingId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    parseMovie: function(incomingId, url, engine)
    {
        var self = this;
        this.query({
                'action' : 'Cp.parseMovie',
                'incoming_id' : incomingId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterParseMovie', incomingId, result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    parseKinopoiskMovie: function(url)
    {
        var self = this;
        this.query({
                'action' : 'Cp.parseKinopoiskMovie',
                'url' : url
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterParseKinopoiskMovie', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    replaceKinopoiskMovie: function(movieId, url)
    {
        var self = this;
        this.query({
                'action' : 'Cp.replaceKinopoiskMovie',
                'movie_id': movieId,
                'url' : url
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterReplaceKinopoiskMovie', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    mergeKinopoiskMovie: function(movieId, url)
    {
        var self = this;
        this.query({
                'action' : 'Cp.mergeKinopoiskMovie',
                'movie_id': movieId,
                'url' : url
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterMergeKinopoiskMovie', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    parsePerson: function(personId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.parsePerson',
                'person_id' : personId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterParsePerson', personId, result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    fixPersones: function()
    {
        var self = this;
        this.query({
                'action' : 'Cp.fixPersones'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterFixPersones', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    parseIncomingFiles: function(incomingId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.parseIncomingFiles',
                'incoming_id' : incomingId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterParseIncomingFiles', incomingId, result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    importIncoming: function(incomingId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.importIncoming',
                'incoming_id' : incomingId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterImportIncoming', incomingId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    getMovies: function (offset, size, sort, order, filter)
    {
        var self = this;
        this.query({
                'action' : 'Cp.getMovies',
                'offset' : offset,
                'size' : size,
                'sort' : sort,
                'order' : order,
                'filter' : filter
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawMovies', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getMovie: function (movieId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.getMovie',
                'movie_id' : movieId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawMovie', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    setMovieField: function(movieId, field, value)
    {
        var self = this;
        this.query({
                'action' : 'Cp.setMovieField',
                'movie_id' : movieId,
                'field' : field,
                'value' : value
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    setFileField: function(fileId, field, value)
    {
        var self = this;
        this.query({
                'action' : 'Cp.setFileField',
                'file_id' : fileId,
                'field' : field,
                'value' : value
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    setParticipantField: function(participantId, field, value)
    {
        var self = this;
        this.query({
                'action' : 'Cp.setParticipantField',
                'participant_id' : participantId,
                'field' : field,
                'value' : value
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    removeParticipant: function(participantId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.removeParticipant',
                'participant_id' : participantId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterRemoveParticipant', participantId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    insertMoviePerson: function(movieId, persones)
    {
        var self = this;
        this.query({
                'action' : 'Cp.insertMoviePerson',
                'movie_id' : movieId,
                'persones' : persones
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterInsertMoviePerson', movieId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    setPersonField: function(personId, field, value)
    {
        var self = this;
        this.query({
                'action' : 'Cp.setPersonField',
                'person_id' : personId,
                'field' : field,
                'value' : value
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    setUserField: function(userId, field, value)
    {
        var self = this;
        this.query({
                'action' : 'Cp.setUserField',
                'user_id' : userId,
                'field' : field,
                'value' : value
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    addFile: function(movieId, path)
    {
        var self = this;
        this.query({
                'action' : 'Cp.addFile',
                'movie_id' : movieId,
                'path' : path
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterAddFile');
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    removeFile: function(fileId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.removeFile',
                'file_id' : fileId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterRemoveFile');
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    reparseFiles: function(movieId, filesIds)
    {
        var self = this;
        this.query({
                'action' : 'Cp.reparseFiles',
                'movie_id' : movieId,
                'files_ids' : filesIds
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterReparseFiles');
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    generateFrames: function(filesIds)
    {
        var self = this;
        this.query({
                'action' : 'Cp.generateFrames',
                'files_ids' : filesIds
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterGenerateFrames');
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    removeMovie: function(movieId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.removeMovie',
                'movie_id' : movieId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterRemoveMovie');
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },


    getPersones: function (offset, size, sort, order, filter)
    {
        var self = this;
        this.query({
                'action' : 'Cp.getPersones',
                'offset' : offset,
                'size' : size,
                'sort' : sort,
                'order' : order,
                'filter' : filter
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawPersones', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getPerson: function (personId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.getPerson',
                'person_id' : personId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawPerson', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getUsers: function (offset, size, sort, order, filter)
    {
        var self = this;
        this.query({
                'action' : 'Cp.getUsers',
                'offset' : offset,
                'size' : size,
                'sort' : sort,
                'order' : order,
                'filter' : filter
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawUsers', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getUser: function (userId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.getUser',
                'user_id' : userId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawUser', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    searchGoogleImages: function (query, type)
    {
        var self = this;
        this.query({
                'action' : 'Cp.searchGoogleImages',
                'query' : query,
                'type' : type
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterSearchGoogleImages', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    getAttachInfo: function (incomingId, movieId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.getAttachInfo',
                'movie_id' : movieId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterGetAttachInfo', incomingId, movieId, result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    attachFile: function (incomingId, movieId, targetPath, deleteFiles, up)
    {
        var self = this;
        this.query({
                'action' : 'Cp.attachFile',
                'incoming_id' : incomingId,
                'movie_id' : movieId,
                'target_path' : targetPath,
                'delete_files' : deleteFiles,
                'up' : up
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterAttachFile', incomingId, movieId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    localSearch: function (incomingId, query)
    {
        var self = this;
        this.query({
                'action' : 'Video.search',
                'query' : query
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawLocalSearch', incomingId, result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    getCurrentStatus: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.getCurrentStatus'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawFilesTasks', result.response);
                    self.emit('drawUtils', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    getCatalogQualitiesAndTranslations: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.getCatalogQualitiesAndTranslations'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterGetCatalogQualitiesAndTranslations', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    updateRatings: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.updateRatings'
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    updateLocalRatings: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.updateLocalRatings'
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    checkFiles: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.checkFiles'
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
        
    getReport: function (logId)
    {
        var self = this;
        this.query({
                'action' : 'Cp.getReport',
                'log_id' : logId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawReport', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
        
    relocateLostFiles: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.relocateLostFiles'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterRelocateLostFiles', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    hideBrokenMovies: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.hideBrokenMovies'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterHideBrokenMovies', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    resetFilesTasksTries: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.resetFilesTasksTries'
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    clearFilesTasks: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.clearFilesTasks'
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    checkUpdates: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.checkUpdates'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterCheckUpdates', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    upgrade: function (confirm)
    {
        var self = this;
        this.query({
                'action' : 'Cp.upgrade',
                'confirm': confirm
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('afterUpgrade', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    getSettings: function ()
    {
        var self = this;
        this.query({
                'action' : 'Cp.getSettings'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawSettings', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    saveSettings: function (data)
    {
        var self = this;
        this.query({
                'action' : 'Cp.saveSettings',
                'data' : data
            },
            function(result) {
                if (200 == result.status) {
                    self.getSettings();
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    }
};