/**
 * @requires Prototype Library
 */

JSAN.require('LMS');

 /**
 * @namespace Utils
 */
LMS.Utils = {};


LMS.Utils.HumanSize = function (size, unit, precision, IEC_60027) {

    size = parseInt(size);

    if (unit==null) unit = 'B';

    if (precision==null) precision = 2;

    if (IEC_60027==null) IEC_60027 = true;

    var absSize = Math.abs(size);

    if(absSize == 0) {
        return "0";
    }

    if (IEC_60027) {
        var base = 2;
        var sufficses = {0: '', 10: 'Ki', 20: 'Mi', 30: 'Gi', 40: 'Ti'};
    } else {
        var base = 10;
        var sufficses = {0: '', 3: 'K', 6: 'M', 9: 'G', 12: 'T'};
    }

    var humanSize = size;
    for (var power in sufficses) {
        var x = Math.pow(base, power);
        if(absSize > x) {
            var precisionMultipler = Math.pow(10, precision);
            humanSize = Math.round(precisionMultipler*size/x)/precisionMultipler + ' ' + sufficses[power] + unit ;
        }
    }

    return humanSize;
}

LMS.Utils.getCSSRule = function (ruleName, deleteFlag) {
    ruleName=ruleName.toLowerCase();
    if (document.styleSheets) {
        for (var i=0; i<document.styleSheets.length; i++) {
            var styleSheet=document.styleSheets[i];
            var ii=0;
            var cssRule=false;
            do {
                if (styleSheet.cssRules) {
                    cssRule = styleSheet.cssRules[ii];
                } else {
                    cssRule = styleSheet.rules[ii];
                }
                if (cssRule)  {
                    if (cssRule.selectorText.toLowerCase()==ruleName) {
                        if (deleteFlag=='delete') {
                            if (styleSheet.cssRules) {
                                styleSheet.deleteRule(ii);
                            } else {
                                styleSheet.removeRule(ii);
                            }
                            return true;
                        } else {
                            return cssRule;
                        }
                    }
                }
                ii++;
            } while (cssRule)
      }
   }
   return false;
}

LMS.Utils.killCSSRule = function killCSSRule(ruleName) {
    return LMS.Utils.getCSSRule(ruleName,'delete');
}

LMS.Utils.addCSSRule = function (ruleName) {
    if (document.styleSheets) {
        if (!LMS.Utils.getCSSRule(ruleName)) {
            if (document.styleSheets[0].addRule) {
                document.styleSheets[0].addRule(ruleName, null,0);
            } else {
                document.styleSheets[0].insertRule(ruleName+' { }', 0);
            }
        }
    }
    return LMS.Utils.getCSSRule(ruleName);
}

LMS.Utils.emit = function()
{
    var args = Array.prototype.slice.call(arguments);  
    var signalName = args.shift();
    var connections = LMS.Connector.getConnections(null, signalName);
    for (var i=0; i<connections.length; i++) {
        var slotObject = connections[i][0];
        var slotName = connections[i][1];
        slotObject[slotName].apply(slotObject, args);
    }
}

LMS.Utils.move = function (array, oldIndex, newIndex) {
    if (newIndex >= array.length) {
        var k = newIndex - array.length;
        while ((k--) + 1) {
            array.push(undefined);
        }
    }
    array.splice(newIndex, 0, array.splice(oldIndex, 1)[0]);
    return array;
};