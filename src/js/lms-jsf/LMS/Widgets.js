/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: Widgets.js 48 2009-07-15 13:58:55Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * 
 */

JSAN.require('LMS');

 /** 
  * @namespace Lms namespace for misc classes
  */
LMS.Widgets = {};

LMS.Widgets.EXPORT      = [];
LMS.Widgets.EXPORT_TAGS = { ':all': LMS.Widgets.EXPORT };
LMS.Widgets.VERSION     = '0.01';

/**
 * По-умолчанию отладчик производительности отключен 
 */
if ('undefined'==typeof(DEBUG_PERFOMANCE)) {
    DEBUG_PERFOMANCE = false;
}

/**
 * Счетчик DOM-идентификаторов
 * @private
 */
LMS.Widgets._DOMIdIndex = 0;


/**
 * 
 * @name LMS._getNewDOMId
 * @private
 * @function
 * @return {string} Новый DOM-идентификатор
 * 
 */
LMS.Widgets._getNewDOMId = function(){
    return 'lms_' + (++LMS.Widgets._DOMIdIndex);
}

LMS.Widgets.IS_STRING = 1;

LMS.Widgets.thisWidget = function(elementOrEvent){
    //TODO: implement
    if (elementOrEvent instanceof Event) {
        var element = elementOrEvent.target || elementOrEvent.srcElement;
    } else {
        var element = elementOrEvent;
    }
}