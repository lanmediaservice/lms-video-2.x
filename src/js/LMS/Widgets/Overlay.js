/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Overlay.js 700 2011-06-10 08:40:53Z macondos $
 */
 
 JSAN.require('LMS.Widgets.Generic');

LMS.Widgets.Overlay = Class.create(LMS.Widgets.Generic, {
    // properties
    overlayId: '_overlay',
    targetScroll: false,
    scrollStep: 42,
    onWheel: false,
    onCreateElement: function() 
    {
        this.onWheel = this.wheel.bind(this);
        this.setVisible(false);
        
        JSAN.require('LMS.Widgets.Factory');
        
        var wrapper = LMS.Widgets.Factory('LayerBox');
        wrapper.setClassName('transparent-overlay');
        wrapper.setDOMId(this.overlayId);
        
        this.wrapperElement = wrapper.createElement();
        var self = this;
        this.wrapperElement.onclick = function(e){self.overlayClick(e);};
        this.applyDecorators();
        return this.wrapperElement;
    },
    
    setTargetScroll: function(targetScroll, scrollStep)
    {
        this.targetScroll = targetScroll;
        if (!Object.isUndefined(scrollStep)) {
            this.scrollStep = scrollStep;
        }
    },
    
    show: function()
    {
        if (!$(this.overlayId)) {
            document.body.appendChild(this.createElement());
        }
        this.enableOverlay();
        this.setVisible(true);
    },

    enableOverlay: function()
    {
        if (this.targetScroll) {
            this.disableScroll();
        }
    },
    
    disableOverlay: function()
    {
        if (this.targetScroll) {
            this.enableScroll();
        }
    },
    
    overlayClick: function()
    {
        this.close();
    },

    onClose: function()
    {
    },
    
    close: function()
    {
        this.hide();
        this.onClose();
    },
    
    hide: function()
    {
        this.setVisible(false);
        this.disableOverlay();
    },
    
    // Добавляем обработчики
    disableScroll: function() {
        /* Gecko */
        Event.observe(window, 'DOMMouseScroll', this.onWheel);
        /* Opera */
        Event.observe(window, 'mousewheel', this.onWheel);
        /* IE */
        Event.observe(document, 'mousewheel', this.onWheel);
        return false;
    },

    enableScroll: function() {
        /* Gecko */
        Event.stopObserving(window, 'DOMMouseScroll', this.onWheel);
        /* Opera */
        Event.stopObserving(window, 'mousewheel', this.onWheel);
        /* IE */
        Event.stopObserving(document, 'mousewheel', this.onWheel);
        return false;
    },

    wheel: function (event) {
        var delta; // Направление скролла
        // -1 * N - скролл вниз
        // 1 * N - скролл вверх
        event = event || window.event;
        var element = (event.srcElement) ? event.srcElement : (event.target) ? event.target : null;
        // Opera и IE работают со свойством wheelDelta
        if (event.wheelDelta) {
            delta = event.wheelDelta / 120;
            // В Опере значение wheelDelta такое же, но с противоположным знаком
            //if (window.opera) delta = -delta;
        // В реализации Gecko полуим свойство detail
        } else if (event.detail) {
            delta = -event.detail / 3;
        }
        // Запрещаем обработку события браузером по умолчанию
        if (event.preventDefault) {
            event.preventDefault();
        }
        event.returnValue = false;
        this.targetScroll.scrollTop -= delta * this.scrollStep;
        return delta;
    }
});