/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: UI.js 700 2011-06-10 08:40:53Z macondos $
 */
 
JSAN.require('LMS.Signalable'); 
 
LMS.UI = Class.create(LMS.Signalable, {
    lastMessageId: 0, 
    showUserError: function (code, message, level, autoHide)
    {
        if (!level) {
            level = 'warn';
        }
        var text = 'Ошибка #' + code + (message? ': ' + message: '');
        
        this.showMessage(text, level, !Object.isUndefined(autoHide)? autoHide : false);
    },
    
    showUserMessage: function (message, autoHide)
    {
        this.showMessage(message, 'info', !Object.isUndefined(autoHide)? autoHide : false);
    },
    
    showMessage: function (message, level, autoHide)
    {
        if (Object.isUndefined(autoHide)) {
            autoHide = true;
        }
        var messageElement = $j('<div>');
        messageElement.addClass(level);
        messageElement.html(message);
        var btnClose = $j('<a onclick="$j(this).parent().remove()" style="float:right;cursor:pointer;opacity: 0.5;" class="button-close"></a>');
        messageElement.prepend(btnClose);
        
        $j('#user_message').append(messageElement)
                           .show();
        
        if (autoHide) {
            setTimeout(function(){
                messageElement.fadeOut('slow', function() {
                    messageElement.remove();
                });
            }, 15000);
        }
    }, 
    highlightElement: function (domId)
    {
        new Effect.Highlight(domId, {startcolor: '#ffebe8'});
    },    
    reload: function()
    {
        window.location.reload(true);
        window.location.href = unescape(window.location.pathname);
    },
    
    isEnterKey: function(e)
    {
        if (!e) e = window.event;
        var characterCode;
        if(e.which) {
            characterCode = e.which;
        }  else {
            characterCode = e.keyCode;
        }

        return (characterCode == 13);
    }
});

