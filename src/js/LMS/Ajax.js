/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Ajax.js 700 2011-06-10 08:40:53Z macondos $
 */
 
JSAN.require('LMS.Signalable');

LMS.Ajax = Class.create(LMS.Signalable, {
    items: null,
    apiUrl: 'api.php',
    timer: null,
    initialize: function($super)
    {
        this.items = [];
        //var self = this;
        //new PeriodicalExecuter(function(){self.ajaxQueriesExec()}, 0.05);
        $super();
    },
    setApiUrl: function(url)
    {
        this.apiUrl = url;
    },
    addParams: function(inParams, outParams, actionNum)
    {
        if ('undefined' == typeof actionNum) {
            actionNum = 0;
        }

        for (var paramKey in inParams) {
            var paramValue = inParams[paramKey];
            if ('object' != typeof outParams[paramKey]) {
                outParams[paramKey] = new Object();
            }
            if (Object.isElement(paramValue)) {
                //if uploading file
                outParams[paramKey] = paramValue;
            } else {
                outParams[paramKey][actionNum] = paramValue;
            }
        }
        return actionNum;
    },
    ajaxQueriesExec: function ()
    {
        var time = new Date().getTime();
        if (this.items.length) {
            var params = {};
            var commands = [];
            for (var i = 0; i<this.items.length; i++) {
                    var item = this.items[i];
                    commands.push(item);
            }
            for (var i = 0; i<commands.length; i++) {
                var requestParams = commands[i].requestParams;
                var actionNum = i;
                this.addParams(requestParams, params, actionNum);
            }
            var self = this;
            JsHttpRequest.query(
                this.apiUrl, // backend
                params,
                function(result, errors) {
                    if (errors.length) {
                        self.emit('sysError', '500', errors);
                    }
                    for (var i=0; i<commands.length; i++) {
                        var commandCallback = commands[i].callback;
                        commandCallback(result[i]);
                    }
                },
                true  // do not disable caching
            )
            this.items = [];
        }
    },
    exec: function(requestParams, callback)
    {
        if (this.timer) {
            clearTimeout(this.timer);
        }

        this.items.push({requestParams: requestParams, callback:callback});
        
        var self = this;
        this.timer = setTimeout(function(){self.ajaxQueriesExec()}, 10);
    }

});

