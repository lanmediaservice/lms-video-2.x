JSAN.require('LMS');

 /** 
  * @namespace LMS.i18n namespace for internationalization tools
  */
LMS.i18n = {};

LMS.i18n.data = $H();

LMS.i18n.add = function(data){
    LMS.i18n.data = LMS.i18n.data.merge(data);
};

LMS.i18n.translate = function(string){
    var result = LMS.i18n.data.get(string);
    if (!Object.isUndefined(result)) {
        return result;
    } else {
        return string;
    }
};

LMS.i18n.EXPORT      = [];
LMS.i18n.EXPORT_TAGS = { ':all': LMS.i18n.EXPORT };
LMS.i18n.VERSION     = '0.01';
LMS.i18n.NOT_REPAINT = true;
