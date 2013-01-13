/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Action.js 700 2011-06-10 08:40:53Z macondos $
 */
 
if (!LMS.Video) {
    LMS.Video = {};
}

LMS.Video.Action = {

    getCatalog: function (offset, size, genre, country, order, continuous)
    {
        var self = this;
        this.query({
                'action' : 'Video.getCatalog',
                'offset' : offset,
                'size' : size,
                'genre' : genre? genre : null, 
                'country' : country? country : null,
                'order' : order? order : null
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawCatalog', result.response, continuous);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getBestsellers: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.getBestsellers'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawBestsellers', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    search: function (query)
    {
        var self = this;
        this.query({
                'action' : 'Video.search',
                'query' : query
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawSearch', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getBookmarks: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.getBookmarks'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawBookmarks', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    deleteBookmark: function (movieId)
    {
        var self = this;
        this.query({
                'action' : 'Video.deleteBookmark',
                'movie_id': movieId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('unstarBookmark', movieId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    addBookmark: function (movieId)
    {
        var self = this;
        this.query({
                'action' : 'Video.addBookmark',
                'movie_id': movieId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawBookmarks', result.response);
                    self.emit('starBookmark', movieId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    setRating: function (movieId, rating)
    {
        var self = this;
        this.query({
                'action' : 'Video.setRating',
                'movie_id': movieId,
                'rating' : rating
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('updateRating', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    getGenres: function (country)
    {
        var self = this;
        this.query({
                'action' : 'Video.getGenres',
                'country' : country
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawGenres', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getCountries: function (genre)
    {
        var self = this;
        this.query({
                'action' : 'Video.getCountries',
                'genre' : genre
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawCountries', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getLastComments: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.getLastComments'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawLastComments', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getLastRatings: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.getLastRatings'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawLastRatings', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },


    getRandomMovie: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.getRandomMovie'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawRandomMovie', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getPopMovies: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.getPopMovies'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawPopMovies', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getMovie: function (movieId, page)
    {
        var self = this;
        this.query({
                'action' : 'Video.getMovie',
                'movie_id' : movieId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawMovie', result.response, page);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getMoviePerson: function (personId)
    {
        var self = this;
        this.query({
                'action' : 'Video.getPerson',
                'person_id' : personId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawMoviePerson', result.response, personId);
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
                'action' : 'Video.getPerson',
                'person_id' : personId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawPerson', result.response, personId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getComments: function (movieId)
    {
        var self = this;
        this.query({
                'action' : 'Video.getComments',
                'movie_id' : movieId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawComments', result.response, movieId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getSuggestion: function (text)
    {
        $j.ajax({
            url: "suggestion.php",
            data: ({q : text}),
            cache: true,
            success: function(result) {
                try {
                    var data = result.evalJSON().json.shift();
                    if (data.status=='200') {
                        LMS.Utils.emit('drawSuggestion', data.response);
                    }
                } catch (err){
                    
                }
            }
        });
    },
    
    postComment: function (movieId, text)
    {
        var self = this;
        this.query({
                'action' : 'Video.postComment',
                'movie_id' : movieId,
                'text' : text
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawComments', result.response, movieId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    deleteComment: function (commentId)
    {
        var self = this;
        this.query({
                'action' : 'Video.deleteComment',
                'comment_id' : commentId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('postDeleteComment', commentId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    editComment: function (commentId, text)
    {
        var self = this;
        this.query({
                'action' : 'Video.editComment',
                'comment_id' : commentId,
                'text': text
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('postEditComment', commentId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    changePassword: function (oldPassword, newPassword)
    {
        var self = this;
        this.query({
                'action' : 'Video.changePassword',
                'password_old' : oldPassword,
                'password_new' : newPassword
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('postChangePassword');
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    sendOpinionAndChangeTemplate: function (text, template)
    {
        var self = this;
        this.query({
                'action' : 'Video.sendOpinion',
                'text' : text
            },
            function(result) {
                if (200 == result.status) {
                    ui.setTemplate(template);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    setMovieField: function (movieId, field, value)
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

    hitMovie: function (movieId)
    {
        var self = this;
        this.query({
                'action' : 'Video.hitMovie',
                'movie_id' : movieId
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    logout: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.logout'
            },
            function(result) {
                if (200 == result.status) {
                    window.location = window.location.protocol + "//" + window.location.host + window.location.pathname + "?exit=1";
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    }


};