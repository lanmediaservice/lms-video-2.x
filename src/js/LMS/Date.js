LMS.Date = {}

LMS.Date.DEFAULT_UNITS = 'ymdhis';
LMS.Date.YEAR = 31536000;
LMS.Date.MONTH = 2629800;
LMS.Date.WEEK = 604800;
LMS.Date.DAY = 86400;
LMS.Date.HOUR = 3600;
LMS.Date.MIN = 60;
        
LMS.Date.timeAgo = function(date, limitReturnChunks, lang, units, precision)
{
    if (!limitReturnChunks) {
        limitReturnChunks = 1;
    }
    if (!lang) {
        lang = 'en';
    }

    if (!units) {
        units = LMS.Date.DEFAULT_UNITS;
    }

    if (!precision) {
        precision = 0.5;
    }


    var now = Date.now();
    if (date == now) {
        var outputStr = LMS.i18n.translate('now');
    } else {
        var outputStr = LMS.Date.timeDiff(date, now, limitReturnChunks, lang, units, precision);
        if (!outputStr.length) {
            outputStr = LMS.i18n.translate('now');
        } else {
            outputStr += ' ' + LMS.i18n.translate('ago');
        }
    }
    return outputStr.replace(/(^\s+)|(\s+$)/g, "");
}

LMS.Date.timeDiff = function(dateFirst, dateLast, limitReturnChunks, lang, units, precision)
{
    if (!limitReturnChunks) {
        limitReturnChunks = 1;
    }
    if (!lang) {
        lang = 'en';
    }

    if (!units) {
        units = LMS.Date.DEFAULT_UNITS;
    }

    if (!precision) {
        precision = 0.5;
    }
    var timeFirst = dateFirst.valueOf()/1000;
    var timeLast = dateLast.valueOf()/1000;
    
    var diff = Math.abs(timeLast - timeFirst);
    var rest = diff;
    var restChunks = limitReturnChunks;
    
    if (units.indexOf('y')!=-1) {
        var years = Math.floor(rest/LMS.Date.YEAR);
        if (restChunks<=1 && ((rest - years * LMS.Date.YEAR)/diff)>precision) {
            years = 0;
        } else {
            restChunks--;
        }
        rest = rest - years * LMS.Date.YEAR;
    } else {
        years = 0;
    }
    
    if (units.indexOf('m')!=-1) {
        var months = Math.floor(rest / LMS.Date.MONTH);
        if (restChunks<=1 && ((rest - months * LMS.Date.MONTH)/diff)>precision) {
            months = 0;
        } else {
            restChunks--;
        }
        rest = rest - months * LMS.Date.MONTH;
    } else {
        months = 0;
    }
    
    if (units.indexOf('w')!=-1) {
        var weeks = Math.floor(rest / LMS.Date.WEEK);
        if (restChunks<=1 && ((rest - weeks * LMS.Date.WEEK)/diff)>precision) {
            weeks = 0;
        } else {
            restChunks--;
        }
        rest = rest - weeks * LMS.Date.WEEK;
    } else {
        weeks = 0;
    }

    if (units.indexOf('d')!=-1) {
        var days = Math.floor(rest / LMS.Date.DAY);
        if (restChunks<=1 && ((rest - days * LMS.Date.DAY)/diff)>precision) {
            days = 0;
        } else {
            restChunks--;
        }
        rest = rest - days * LMS.Date.DAY;
    } else {
        days = 0;
    }
    
    if (units.indexOf('h')!=-1) {
        var hours = Math.floor(rest / LMS.Date.HOUR);
        if (restChunks<=1 && ((rest - hours * LMS.Date.HOUR)/diff)>precision) {
            hours = 0;
        } else {
            restChunks--;
        }
        rest = rest - hours * LMS.Date.HOUR;
    } else {
        hours = 0;
    }
    
    if (units.indexOf('i')!=-1) {
        var mins = Math.floor(rest / LMS.Date.MIN);
        if (restChunks<=1 && ((rest - mins * LMS.Date.MIN)/diff)>precision) {
            mins = 0;
        } else {
            restChunks--;
        }
        rest = rest - mins * LMS.Date.MIN;
    } else {
        mins = 0;
    }
    
    if (units.indexOf('s')!=-1) {
        var seconds = Math.round(rest);
    } else {
        seconds = 0;
    }
            
    var chunks = {
        'year(s)' : years,
        'month(s)' : months,
        'week(s)' : weeks,
        'day(s)' : days,
        'hour(s)' : hours,
        'min(s)' : mins,
        'second(s)' : seconds
    };
    
    var outputStr = '';
    
    var i = 0;
    for (var chunkName in chunks) {
        var value = chunks[chunkName];
        if (value) {
            var localChunkName = LMS.i18n.translate(chunkName);
            var translatedChunkNames = localChunkName.split(" ");
            var translatedChunkName = LMS.Text.declension(
                value, translatedChunkNames, lang
            );
            outputStr += " " + value + " " + translatedChunkName;
            i++;
            if (i>=limitReturnChunks) {
                break;
            }
        }
    }
    return outputStr.replace(/(^\s+)|(\s+$)/g, "");
}

LMS.Date.datetimeStrToDate = function(str)
{
    return new Date(str.replace(/(\d+)-(\d+)-(\d+)/, '$2/$3/$1'));
}
