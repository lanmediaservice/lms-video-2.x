JSAN.require('LMS.Widgets');

LMS.Widgets.Factory = function(widgetName) {
    if (widgetName.indexOf('$') == -1) {
        JSAN.require("LMS.Widgets." + widgetName);
        var widgetClass = LMS.Widgets[widgetName];
    } else {
        widgetName = widgetName.substring(1);
        JSAN.require(widgetName);
        var objects = widgetName.split('.');
        var widgetClass = window; 
        for (var i=0; i<objects.length; i++) {
            var objectName = objects[i];
            widgetClass = widgetClass[objectName];
        }
    }
    var args = Array.prototype.slice.call(arguments);  
    args.shift();
    switch(Math.min(5, args.length)) {
      case 0: return new widgetClass();
      case 1: return new widgetClass(args[0]);
      case 2: return new widgetClass(args[0], args[1]);
      case 3: return new widgetClass(args[0], args[1], args[1]);
      case 4: return new widgetClass(args[0], args[1], args[1], args[1]);
      case 5: return new widgetClass(args[0], args[1], args[1], args[1], args[1]);
      default: throw 'Fatal error: LMS.Widgets.Factory support only up to 5 parameters';
    }
};