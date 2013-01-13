/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: AnchorBox.js 52 2009-09-20 21:27:13Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * 
 */
 
/** 
 * Зависимости
 * @requires LMS.Widgets.BlockGeneric
 */
JSAN.require('LMS.Widgets.BlockGeneric');

/**
 * Виджет для HTML элемента &lt;A&gt;.
 * Предствляет собой ссылку или "закладку" 
 * @class
 * @augments LMS.Widgets.BlockGeneric
 */
 
LMS.Widgets.AnchorBox = Class.create(LMS.Widgets.BlockGeneric, {
    jumpBlocked: false,
    href: null,
    target: null,
    _attributes: ['href', 'target'],
    onCreateElement: function() {
        this.wrapperElement = new Element("A", {
            'id': this.DOMId
        });
        this.applyDecorators();
        
        var self = this;
        this.wrapperElement.onclick = function () {
            if (self.enabled) {
                self.emit('action', self.getValue(), self);
                return !self.jumpBlocked;
            } else {
                return false;
            }
        };
        return this.wrapperElement;
    },
    setEnabled: function(enabled){
        this.enabled = new Boolean(enabled).valueOf();
        if (this.wrapperElement) {
            if (this.enabled) {
                this.setHref(this.href);
            } else {
                this.wrapperElement.removeAttribute('href');
            }
            
        }
    },
    /**
     * Устанавливает место (окно), в котором будет открываться ссылка
     * На входе должна быть строка. Иначе свойство сбрасывается в null
     * @memberOf LMS.Widgets.AnchorBox
     * @function
     * @name setTarget
     * @param {string} target
     * @return void
     */
    setTarget: function(value){
         this._setAttribute('target', value, LMS.Widgets.IS_STRING);
    },
    /**
     * Возвращает место (окно), в котором будет открываться ссылка
     * @memberOf LMS.Widgets.AnchorBox
     * @function
     * @name getTarget
     * @return {string}
     */
    getTarget: function(){
        return this.target;
    },
    /**
     * Устанавливает URL, связанный со ссылкой
     * На входе должна быть строка. Иначе свойство сбрасывается в null
     * @memberOf LMS.Widgets.AnchorBox
     * @function
     * @name setHref
     * @param {string} href
     * @return void
     */
    setHref: function(value){
         this._setAttribute('href', value, LMS.Widgets.IS_STRING);
    },
    /**
     * Возвращает URL, связанный со ссылкой
     * @memberOf LMS.Widgets.AnchorBox
     * @function
     * @name getHref
     * @return {string}
     */
    getHref: function(){
        return this.href;
    },
    /**
     * Устанавливает режим блокировки перехода по ссылке
     * @memberOf LMS.Widgets.AnchorBox
     * @function
     * @name setJumpBlocked
     * @param {boolean} jumpBlocked
     * @return void
     */
    setJumpBlocked: function(jumpBlocked){
        if (Object.isString(jumpBlocked)) jumpBlocked = parseInt(jumpBlocked);
        this.jumpBlocked = new Boolean(jumpBlocked).valueOf()
    },
    /**
     * Возвращает текущий режим блокировки перехода по ссылке
     * @memberOf LMS.Widgets.AnchorBox
     * @function
     * @name isJumpBlocked
     * @return {string}
     */
    isJumpBlocked: function(){
        return this.jumpBlocked;
    }
});

/**
 * @test setUp
 * window.myBox = new LMS.Widgets.AnchorBox();
 * myBox.setHTML('click&nbsp;me');
 * myBox.setDOMId("test");
 * assertTrue('Painting', myBox.paint());
 * assertEquals("check DOMId", myBox.DOMId, "test");
 * window.myBoxAction = function()
 * {
 *     myBox.addText(' ok');
 * }
 * LMS.Connector.connect(myBox, 'action', window, 'myBoxAction');
 * 
 * myBox.setTarget("_blank");
 * myBox.setJumpBlocked(true);
 * myBox.setHref("http://example.com/");
 */

 
/**
 * @test test1Hide
 * window.myBox.setVisible(false);
 * assertFalse('Hide Test', window.myBox.isVisible());
 */
 
/**
 * @test test2Show
 * window.myBox.setVisible(true);
 * assertTrue('Show Test', window.myBox.isVisible());
 */
 
 /**
 * @test test3Disable
 * window.myBox.setEnabled(false);
 * assertFalse('Disable Test', window.myBox.isEnabled());
 */
 
/**
 * @test test4Enable
 * window.myBox.setEnabled(true);
 * assertTrue('Enable Test', window.myBox.isEnabled());
 */  
 

 /**
 * @test test5SetJumpBlockedOn
 * window.myBox.setJumpBlocked(true);
 * assertTrue('setJumpBlocked Test', window.myBox.isJumpBlocked());
 */
 
/**
 * @test test6SetJumpBlockedOff
 * window.myBox.setJumpBlocked(false);
 * assertFalse('setJumpBlocked Test', window.myBox.isJumpBlocked());
 */  

/**
 * @test test7RealTimeChangeCaption
 * window.myBox.addText(' +');
 */  
 
 