/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: BlockGeneric.js 48 2009-07-15 13:58:55Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * 
 */
 
/** 
 * Зависимости
 * @requires LMS.Widgets.Generic
 */
JSAN.require('LMS.Widgets.Generic');

/**
 * Базовый класс для блочных элементов
 * @class
 * @name LMS.Widgets.BlockGeneric
 * @augments LMS.Widgets.Generic
 */
LMS.Widgets.BlockGeneric = Class.create(LMS.Widgets.Generic, {
    _childs : null,
    /**
    * @memberOf LMS.Widgets.BlockGeneric
    * @private
    * @constructor
    * @name initialize
    * @return void
    */
    initialize: function($super) 
    {
        $super();
        this._decorators['childs'] = this.decoratorInitChilds;
        this._childs = [];
    },
    decoratorInitChilds : function()
    {
        for (var i=0; i<this._childs.length; i++) {
            this.wrapperElement.appendChild(this._childs[i]);
        }
        return this;
    },
    /**
     * Виртуальный метод сбора коллекции элементов для включения в блок
     * @memberOf LMS.Widgets.BlockGeneric
     * @private
     * @function
     * @name _appendChild
     * @param {HTMLElement} element
     * @return void
     */
    _appendChild: function(element) {
        this._childs.push(element);
        if (this.wrapperElement) {
            this.wrapperElement.appendChild(element);
        }
        return this;
    },
    /**
     * Виртуальный метод сброса коллекции элементов
     * @memberOf LMS.Widgets.BlockGeneric
     * @private
     * @function
     * @name reset
     * @return void
     */
    reset: function() {
        this._childs = [];
        if (this.wrapperElement && Object.isElement(this.wrapperElement)) {
            //this.wrapperElement.childElements().invoke('remove');
            this.wrapperElement.innerHTML = '';
        }
        return this;
    },
    /**
     * Добавление в блок виджета
     * @memberOf LMS.Widgets.BlockGeneric
     * @function
     * @name LMS.Widgets.BlockGeneric.addWidget
     * @param {LMS.Widgets.Generic} widget
     * @return void
     */
    addWidget: function(widget) { 
        this._appendChild(widget.createElement());
        return this;
    },
    /**
     * Добавление в блок текста (plain/text). Если на входе текст будет в виде 
     * HTML-синтаксиса - он будет показан как есть в текстовом виде.
     * Для добавления текста с HTML-форматированием служат методы 
     * addHTML и setHTML
     * @memberOf LMS.Widgets.BlockGeneric
     * @function
     * @name addText
     * @param {string} text
     * @return void
     */
    addText: function(text) { 
        this._appendChild(document.createTextNode(text));
        return this;
    },
    /**
     * Добавление в блок текста с HTML-форматированием (text/html).
     * Эквиваленто работе c innerHTML.
     * @memberOf LMS.Widgets.BlockGeneric
     * @function
     * @name LMS.Widgets.BlockGeneric.addHTML
     * @param {string} htmlText
     * @return void
     */
    addHTML: function(htmlText) { 
        var tempElement = new Element('TEMP');
        tempElement.innerHTML = htmlText;
        var childNodes = [];
        for (var i=0; i<tempElement.childNodes.length; i++) {
            childNodes.push(tempElement.childNodes[i]);
        }
        for (var i=0; i<childNodes.length; i++) {
            this._appendChild(childNodes[i]);
        }
        return this;
    },
    /**
     * Сброс всех элементов и добавление в блок текста с HTML-форматированием
     * (text/html). Эквиваленто работе c innerHTML.
     * @memberOf LMS.Widgets.BlockGeneric
     * @function
     * @name setHTML
     * @param {string} htmlText
     * @return void
     */
    setHTML: function(htmlText) { 
        this.reset();
        this.addHTML(htmlText)
        return this;
    },
    /**
     * Добавление в блок HTML-элемента
     * @memberOf LMS.Widgets.BlockGeneric
     * @function
     * @name addElement
     * @param {HTMLElement} element
     * @return void
     */
    addElement: function(element) { 
        this._appendChild(element);
        return this;
    }
});
