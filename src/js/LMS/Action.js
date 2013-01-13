/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Action.js 700 2011-06-10 08:40:53Z macondos $
 */

JSAN.require('LMS.Signalable'); 

LMS.Action = Class.create(LMS.Signalable, {
    query: null,

    setQueryMethod: function(queryMethod)
    {
        this.query = queryMethod;
    }
});

