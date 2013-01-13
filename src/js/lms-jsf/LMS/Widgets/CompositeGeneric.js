/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: CompositeGeneric.js 48 2009-07-15 13:58:55Z macondos $
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
 * Базовый класс сложных виджетов (содержащих другие виджеты)
 * @class
 * @name LMS.Widgets.CompositeGeneric
 * @augments LMS.Widgets.Generic
 */
LMS.Widgets.CompositeGeneric = Class.create(LMS.Widgets.Generic, {
    _childWidgets : null,
    /**
     * @memberOf LMS.Widgets.CompositeGeneric
     * @private
     * @constructor
     * @name LMS.Widgets.CompositeGeneric.initialize
     * @return void
     */
    initialize: function($super) {
        $super();
        this._childWidgets = [];
    },
    /**
     * Перекрытие метода. Интерфейс аналогичен LMS.Widgets.Generic.setVisible
     */
    setVisible: function(visible){
        this.visible = new Boolean(visible).valueOf();
        for (var i=0; i<this._childWidgets.length; i++) {
            this._childWidgets[i].setVisible(this.visible);
        }
    },
    /**
     * Перекрытие метода. Интерфейс аналогичен LMS.Widgets.Generic.setReadOnly
     */
    setReadOnly: function(readOnly){
        this.readOnly = new Boolean(readOnly).valueOf();
        for (var i=0; i<this._childWidgets.length; i++) {
            this._childWidgets[i].setReadOnly(this.readOnly);
        }
    },
    /**
     * Перекрытие метода. Интерфейс аналогичен LMS.Widgets.Generic.setEnabled
     */
    setEnabled: function(enabled){
        this.enabled = new Boolean(enabled).valueOf();
        for (var i=0; i<this._childWidgets.length; i++) {
            this._childWidgets[i].setEnabled(this.enabled);
        }
    }
});
