
JSAN.require('LMS.Signalable');

LMS.Router = Class.create(LMS.Signalable, {
    initialize: function()
    {
        LMS.Connector.connect('route', this, 'route');
        LMS.Connector.connect('routed', this, 'routed');
        dhtmlHistory.initialize();
        dhtmlHistory.addListener(this.listener.bind(this));
    },
  
    init: function()
    {
        var initialLocation = dhtmlHistory.getCurrentLocation();
        if (initialLocation == null) {
            initialLocation = "";
        }
        this.listener(initialLocation, null);
    },

    url: function(action, params)
    {
        var url = '#/' + action;
        if (params) {
            var paramsArray = [];
            $H(params).each(function(pair) {
                if (pair.value!==null) {
                    paramsArray.push('/' + encodeURIComponent(pair.key) + '/' + encodeURIComponent(pair.value));
                }
            });
            url += paramsArray.join('');
        }
        return url;
    },

    route: function(action, params)
    {
        var url = this.url(action, params);
        window.location.hash = url.substring(1);
        //dhtmlHistory.add(hash);
    },

    routed: function(action, params)
    {
        var url = this.url(action, params);
        var hash = url.substring(1);
        this.routedHash(hash);
    },

    routedHash: function(hash)
    {
        var track = dhtmlHistory.getCurrentLocation()!=hash;
        dhtmlHistory.add(hash);
        if (track) {
            this.trackPageview();
        }
    },

    getParams: function()
    {
        var parsedLocation = this.parseLocation(window.location.hash);
        return $H(parsedLocation.params);
    },
    
    getAction: function()
    {
        var parsedLocation = this.parseLocation(window.location.hash);
        return parsedLocation.action;
    },

    parseLocation: function(location)
    {
        var result = {};
        location = location.replace(/^#/, '').replace(/^\/+/, '');
        var parts = location.split('/');
        result.action = parts.shift();
        result.params = {};
        for (var i=0; i<parts.length; i+=2) {
            result.params[parts[i]] = decodeURIComponent(parts[i+1]);
        }
        return result;
    },
    
    onBeforeRoute: function(location)
    {
        return true;
    },

    listener: function(newLocation, historyData)
    {
        if (!this.onBeforeRoute(decodeURIComponent(newLocation))) {
            return;
        }
        var parsedLocation = this.parseLocation(newLocation);
        if (!parsedLocation.action) {
            parsedLocation.action = 'default';
        }
        this.trackPageview();
        var signal = 'route' + parsedLocation.action.charAt(0).toUpperCase() + parsedLocation.action.substr(1);
        this.emit(signal, parsedLocation.params);
    },
    
    trackPageview: function()
    {
        if (typeof _gaq !== "undefined") {
            //console.trace()
            var url = '';
            if (this.getAction()=='search') {
                var params = this.getParams();
                var search = location.search;
                search += search.length? '&' : '?';
                search += 'query=' + encodeURIComponent(params.get('query'));
                url = location.pathname + search;
            } else {
                url = location.pathname + location.search + location.hash;
            }
            setTimeout(function() {
                _gaq.push(['_trackPageview', url]);
            }, 550);
        }
    }
});

