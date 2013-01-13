/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: LiveDatetime.js 700 2011-06-10 08:40:53Z macondos $
 */
 
LMS.LiveDatetime = {}; 
LMS.LiveDatetime.MODE_DEFAULT = 1; 
LMS.LiveDatetime.MODE_DATE = 2; 
LMS.LiveDatetime.MODE_AGO = 3; 
LMS.LiveDatetime.update = function()
{
    var livedateElements = $$('.live-datetime');
    for (var i=0; i<livedateElements.length; i++) {
        var el = livedateElements[i];
        var timestamp = el.attributes.time.value;        
        var mode = el.attributes.mode.value;
        el.innerHTML = LMS.LiveDatetime.timestampToStr(timestamp, mode);
    }
}

LMS.LiveDatetime.dateToStr = function(date, mode)
{
    var timestamp = Math.round(date.getTime()/1000);
    return LMS.LiveDatetime.timestampToStr(timestamp, mode);
}

LMS.LiveDatetime.timestampToStr = function(timestamp, mode)
{
    var date = new Date();
    date.setTime(timestamp*1000);
    var dateStr = '';
    switch (true) {
        case (date > Date.today()):
            dateStr = date.format(LMS.i18n.translate('dateformat_today'));
            break;
        case (date > Date.today().addMilliseconds(-86400000)):
            dateStr = date.format(LMS.i18n.translate('dateformat_yesterday'));
            break;
        case (date > Date.thisYear()):
            dateStr = date.format(LMS.i18n.translate('dateformat_thisyear'));
            break;
        default:
            dateStr = date.format(LMS.i18n.translate('dateformat_default'));
            break;
    }
    
    var ta = LMS.Date.timeAgo(date, 1, LANG, 'ymdhi');
    var outStr;
    switch (parseInt(mode)) {
        case LMS.LiveDatetime.MODE_DEFAULT:
            outStr = dateStr + ' (' + ta + ')';
            break;
        case LMS.LiveDatetime.MODE_DATE:
            outStr = dateStr;
            break;
        case LMS.LiveDatetime.MODE_AGO:
            outStr = ta;
            break;
    }
    return outStr;
}


LMS.LiveDatetime.init = function()
{
    document.observe("dom:loaded", function() {
        new PeriodicalExecuter(function(pe) {
            LMS.LiveDatetime.update();
        }, 60);
    });
}

LMS.LiveDatetime.init();