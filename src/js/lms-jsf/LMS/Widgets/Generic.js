/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: Generic.js 56 2009-10-30 10:50:13Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * 
 */
 

/**
 * Зависимости
 */
JSAN.require('LMS.Widgets');
JSAN.require('LMS.Signalable');
JSAN.require('LMS.Connector');

/**
 * Класс базового виджета. В основном все методы виртуальные 
 * @class
 * @name LMS.Widgets.Generic
 * @augments LMS.Component
 * @property {string} DOMId DOM-идентификатор главного элемента виджета
 * @property {HTMLElement} wrapperElement Главный элемент виджета
 * @property {mixed} value Главная переменная виджета
 * @property {bool} enabled Виджет включен/отключен
 * @property {bool} readOnly Режим только чтения  
 * @property {bool} visible Режим видимости
 * @property {object} styles CSS-стили в виде объекта
 * @property {object} className Имя CSS-класса виджета
 * @property {string} title Всплывающая подсказка
 * @property {function} debugLogger Внешний обработчик отладочной информации
 * @property {function} userMessenger Внешний обработчик информации для пользователя
 */
LMS.Widgets.Generic = Class.create(LMS.Signalable, {
    DOMId : '',
    name: '',
    wrapperElement : null,
    value : null,
    enabled : true,
    readOnly : false,
    visible : true,
    styles : null,
    className : null,
    title: null,
    debugLogger: null,
    userMessenger: null,
    onShowStyles: null,
    onHideStyles: null,
    _eventHandlers : null,
    _decorators : null,
    _attributes: [],
    /*_includes: null,*/
    /**
     * @memberOf LMS.Widgets.Generic
     * @private
     * @constructor
     * @name initialize
     * @return void
     */
    initialize: function($super) 
    {
        this.DOMId = LMS.Widgets._getNewDOMId();
        if (this.styles==null) {
            this.styles = {};
        }
        this.onShowStyles = {display: ''};
        this.onHideStyles = {display: 'none'};
        this._eventHandlers = {};
        this._includes = [];
        this._decorators = {};
        this._decorators['default'] = this.decoratorDefault;
        this._decorators['events'] = this.decoratorInitEvents;
        this._decorators['attributes'] = this.decoratorInitAttributes;
        $super();
    },
    decoratorDefault: function()
    {
        this.setTitle(this.title);
        this.setVisible(this.visible);
        this.setReadOnly(this.readOnly);
        this.setEnabled(this.enabled);
        this.setClassName(this.className);
        this.setStyle(this.styles);
    },
    decoratorInitEvents: function()
    {
        var eventNames = Object.keys(this._eventHandlers);
        for (var i=0; i<eventNames.length; i++) {
            var eventName = eventNames[i];
            this.setEventHandler(eventName, this._eventHandlers[eventName])
        }
    },
    decoratorInitAttributes: function()
    {
        for (var i=0; i<this._attributes.length; i++) {
            var attributeName = this._attributes[i];
            var attributeSetter = 'set' + attributeName.charAt(0).toUpperCase() + attributeName.substring(1);
            this[attributeSetter](this[attributeName]);
        }
    },
    applyDecorators: function(decoratorsKeys)
    {
        if (!decoratorsKeys) {
            decoratorsKeys = Object.keys(this._decorators);
        }
        for (var i=0; i<decoratorsKeys.length; i++) {
            var decoratorKey = decoratorsKeys[i];
            this._decorators[decoratorKey].apply(this);
        }
    },
    /*addSubDataGrid: function(dataGrid) {
        this._includes.push(dataGrid);
    },*/
    /**
     * Возвращает значение главной переменной виджета 
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name getValue
     * @return {mixed}
     */
    getValue: function() 
    { 
        return this.value;
    },
    /**
     * Уcтанавливает значение главной переменной виджета. 
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name setValue
     * @param {mixed} value
     * @return void
     */
    setValue: function(value) 
    { 
        this.value = value;
        this.emit('valueChanged', this.value);
        return this;
    },
    /**
     * Возвращает DOM-идентификатор виджета
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name пetDOMId
     * @return {string}
     */
    getDOMId: function()
    {
        return this.DOMId;
    },
    /**
     * Устанавливает DOM-идентификатор виджета вручную (например, если контейнер
     * будущего элемента уже существует в нужном контексте страницы и нужно на
     * его месте создать виджет)
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name setDOMId
     * @param {string} DOMId
     * @return void
     */
    setDOMId: function(DOMId) 
    { 
        this.DOMId = DOMId;
        return this;
    },
    /**
     * Возвращает Имя виджета
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name getName
     * @return {string}
     */
    getName: function()
    {
        return this.name;
    },
    /**
     * Устанавливает имя виджета
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name setName
     * @param {string} name
     * @return void
     */
    setName: function(name)
    {
        this.name = name;
        return this;
    },
    /**
     * @memberOf LMS.Widgets.Generic
     * @private
     * @function
     * @name onBeforeCreateElement
     * @return void
     */
    _onBeforeCreateElement: function() 
    { 
        if (DEBUG_PERFOMANCE) {
            this.firsttime = new Date().getTime();
        }
    },
    /**
     * Виртуальный метод для создания виджета в виде HTML-элемента 
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name onCreateElement
     * @return {HTMLElement}
     */
    onCreateElement: function() 
    { 
        //virtual
        this.wrapperElement = new Element("DIV");
        return this.wrapperElement;
    },
    /**
     * @memberOf LMS.Widgets.Generic
     * @private
     * @function
     * @name onAfterCreateElement
     * @return void
     */
    _onAfterCreateElement: function() 
    { 
        if (DEBUG_PERFOMANCE) {
            var lasttime = new Date().getTime();
            var time = lasttime - this.firsttime;
            this.debugLog(this.name + ' created in ' + time + 'ms');
        }
    },
    /**
     * Создает и возвращает HTML-элемент виджета
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name createElement
     * @return {HTMLElement}
     */
    createElement: function() 
    { 
        this._onBeforeCreateElement();
        element = this.onCreateElement();
        this._onAfterCreateElement();
        return element;
    },
    /**
     * Перерисовывает виджет. Для этого создает элемент и заменяет им или
     * существующий, уже созданный элемент или элемент документа с
     * DOM-идентификатором равным свойству DOMId. Если элемент 
     * в документе не существует вызов метода завершится неудачей. 
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name paint
     * @return {boolean} true если отрисовка прошла удачно, в противном случае false
     */
    paint: function() 
    {
        if (DEBUG_PERFOMANCE) {
            var firsttime = new Date().getTime();
        }
        var currentNode = this.wrapperElement? this.wrapperElement : $(this.DOMId);
        if (Object.isElement(currentNode)){
            var parentNode = currentNode.parentNode;
            if (Object.isElement(parentNode)){
                var newNode = this.createElement();
                if (Object.isElement(newNode)){
                    parentNode.replaceChild(newNode, currentNode);
                    this.onAfterPaint();
                    if (DEBUG_PERFOMANCE) {
                        var lasttime = new Date().getTime();
                        var time = lasttime - firsttime;
                        this.debugLog(this.name + ' painted in ' + time + 'ms');
                    }
                    return true;
                }
            }
        }
        return false;
    },
    onAfterPaint: function() 
    {
        
    },
    /**
     * Удаление виджета
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name remove
     * @return {boolean} true если удаление элемента прошла удачно, в противном случае false
     */
    remove: function() 
    {
        var currentNode = this.wrapperElement? this.wrapperElement : $(this.DOMId);
        if (Object.isElement(currentNode) && currentNode.remove()){
            return true;
        }
        return false;
    },
    /**
     * Сохраняет новые стили для главного элемента виджета. 
     * Если виджет уже создан применяет стили к главному элементу.
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name setStyle
     * @param {object} styles Стили в виде объекта (например, {display:'none', width:'100px'} )
     * @return void
     */
    setStyle: function(styles) 
    {
        Object.extend(this.styles, styles);
        if (this.wrapperElement) { 
            this.wrapperElement.setStyle(styles);
        }
        return this;
    },
    /**
     * Сохраняет название CSS-класса для главного элемента виджета
     * Если виджет уже создан применяет CSS-класс к главному элементу.
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name setClassName
     * @param {string} className
     * @return void
     */
    setClassName: function(className) 
    {
        this.className = className;
        if (this.wrapperElement && !Object.isUndefined(this.wrapperElement.className)) {
            if (this.className) {
                this.wrapperElement.className = this.className;
            } else {
                this.wrapperElement.removeAttribute('className');
            }
        }
        return this;
    },
    
    hasClassName: function(className) {
        return (this.className && this.className.length > 0 && (this.className == className ||
            new RegExp("(^|\\s)" + className + "(\\s|$)").test(this.className)));
    }, 
    
    addClassName: function(className) 
    {
        if (!this.hasClassName(className)) {
            this.setClassName(this.className + (this.className? ' ' : '') + className); 
        }
        return this;
    },
    
    removeClassName: function(className) 
    {
        if (this.hasClassName(className)) {
            this.setClassName(this.className.replace(
                new RegExp("(^|\\s+)" + className + "(\\s+|$)"), ' ').strip()); 
        }
        return this;
    },
    /**
     * Сохраняет и пытается применить видимость виджета
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name setVisible
     * @param {bool} visible
     * @return void
     */
    setVisible: function(visible)
    {
        this.visible = new Boolean(visible).valueOf();
        if (this.visible) {
            this.setStyle(this.onShowStyles);
        } else {
            this.setStyle(this.onHideStyles);
        }
        return this;
    },
    /**
     * Возвращает текущее значение видимости виджета
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name isVisible
     * @return {bool}
     */
    isVisible: function()
    {
        return new Boolean(this.visible).valueOf();
    },
    /**
     * Сохраняет и пытается применить режим read only виджета
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name setReadOnly
     * @param {bool} readOnly
     * @return void
     */
    setReadOnly: function(readOnly)
    {
        this.readOnly = new Boolean(readOnly).valueOf();
        if (this.wrapperElement && !Object.isUndefined(this.wrapperElement.disabled)) {
            this.wrapperElement.readOnly = this.readOnly;
        }
        return this;
    },
    /**
     * Возвращает текущее значение режима read only виджета
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name isReadOnly
     * @return {bool}
     */
    isReadOnly: function()
    {
        return new Boolean(this.readOnly).valueOf();
    },
    /**
     * Сохраняет и пытается включить/отключить виджет
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name setEnabled
     * @param {bool} readOnly
     * @return void
     */
    setEnabled: function(enabled)
    {
        this.enabled = new Boolean(enabled).valueOf();
        if (this.wrapperElement && !Object.isUndefined(this.wrapperElement.disabled)) {
            this.wrapperElement.disabled = !this.enabled;
        }
        return this;
    },
    /**
     * Возвращает включен/отключен ли виджет
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name isEnabled
     * @return {bool}
     */
    isEnabled: function()
    {
        return new Boolean(this.enabled).valueOf();
    },
    /**
     * Сохраняет и пытается применить свойство title к виджету
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name setTitle
     * @param {string} title
     * @return void
     */
    setTitle: function(title) 
    {
        this.title = title;
        if (this.wrapperElement && !Object.isUndefined(this.wrapperElement.title)) {
            if (this.title) {
                this.wrapperElement.title = this.title;
            } else {
                this.wrapperElement.removeAttribute('title');
            }
        }
        return this;
    },
    /**
     * Возвращает текущее значение свойства title
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name getTitle
     * @return {string}
     */
    getTitle: function()
    {
        return this.title;
    },
    /**
     * Вывод отладочной информации
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name debugLog
     * @param {string} text
     * @return void
     */
    debugLog: function(text) 
    {
        if (this.debugLogger) this.debugLogger(text);
    },
    /**
     * Вывод информации для пользователя
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name userMessage
     * @param {string} text
     * @return void
     */
    userMessage: function(text) 
    {
        if (this.userMessenger) this.userMessenger(text);
    },
    /**
     * Установка внешнего обработчика отладочной информации
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name setDebugLogger
     * @param {function} handler
     * @return void
     */
    setDebugLogger: function(handler)
    {
        this.debugLogger = handler;
        return this;
    },
    /**
     * Установка внешнего обработчика информации для пользователя
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name setUserMessenger
     * @param {function} handler
     * @return void
     */
    setUserMessenger: function(handler)
    {
        this.userMessenger = handler;
        return this;
    },
    /**
     * Привязка события главного элемента виджета к внешнему обработчику
     * @memberOf LMS.Widgets.Generic
     * @function
     * @name setEventHandler
     * @param {string} eventName
     * @param {function} eventHandler
     * @return void
     */
    setEventHandler: function(eventName, eventHandler) 
    {
        this._eventHandlers[eventName] = eventHandler;
        if (this.wrapperElement) {
            if (this.wrapperElement.addEventListener) {
                this.wrapperElement.addEventListener(eventName, eventHandler, false);
            } else {
                this.wrapperElement.attachEvent("on" + eventName, eventHandler);
            }
        }
        return this;
    },
     /**
      * Устанавливает значение аттрибута wrapper'а виджета
      * На входе должны быть число или строка. Иначе свойство сбрасывается в null
      * @name _setAttribute
      * @function
      * @memberOf LMS.Widgets.Generic
      * @param {string} attribute
      * @param {integer/string} value
      * @return void
      */
     _setAttribute: function(attribute, value, isString){
         if (Object.isNumber(value) || Object.isString(value)) {
             this[attribute] = isString? value : parseInt(value);
             if (this.wrapperElement){
                 this.wrapperElement[attribute] = this[attribute];
             }
         } else {
             this[attribute] = null;
             if (this.wrapperElement){
                 this.wrapperElement.removeAttribute(attribute);
             }
         }
        return this;
    },
    getWrapperElement: function()
    {
        return this.wrapperElement;
    },

    t: function(string)
    {
        JSAN.require('LMS.i18n');
        return LMS.i18n.translate(string);
    },
    
    free: function()
    {
        this.onDestroy();
        return this;
    },
    
    onDestroy: function()
    {
        LMS.Connector.disconnectAll(this);
        if (this.wrapperElement && this.wrapperElement.parentNode) {
            this.wrapperElement.remove();
        }
    }
});

/** 
 *  Пример для unit-тестирования
 *  @test testPaint
 *  var myBox = new LMS.Widgets.Generic('myBox');
 *  myBox.DOMId = "test";
 *  assertTrue('Painting', myBox.paint());
 *  assertEquals("check DOMId", myBox.DOMId, "test");
 */
