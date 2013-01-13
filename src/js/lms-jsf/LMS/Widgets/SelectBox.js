/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: SelectBox.js 55 2009-10-26 09:49:49Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @autor Alex Tatulchenkov <webtota@gmail.com>
 * 
 */
 
 /**
 * Зависимости
 * @requires LMS.Widgets.Generic
 */
JSAN.require('LMS.Widgets.Generic');

/**
 * <select> 
 * @class
 * @augments LMS.Widgets.Generic
 */
LMS.Widgets.SelectBox = Class.create(LMS.Widgets.Generic, {
    size: null,
    multiple: null,
    _attributes: ['size', 'multiple'],
    initialize: function($super) {
        $super();
        this._decorators['forms'] = this.decoratorInitFormElement;
        this.selectOptions = [];
    },
    decoratorInitFormElement : function()
    {
        var self = this;
        this.wrapperElement.onchange = function () {
            self.setValue(this.value);
        }
        this.wrapperElement.ondblclick = function () {
            var value = self.getValue();
            if (Object.isArray(value)){
                value = value[0];
            }
            self.emit('dblClick', value, self);
        }
    },
    onCreateElement: function() { 
        this.wrapperElement = new Element("SELECT", {
              'id': this.DOMId
        });
        this.applyDecorators();
        if (this.selectOptions instanceof Array && this.selectOptions.length) {
            for (var i=0; i<this.selectOptions.length; i++) {
                this.wrapperElement.options[i] = this.selectOptions[i];
            }
        }
        this.wrapperElement.value = this.value;
        return this.wrapperElement;
    },
    setValue: function(value)
    {
        if (this.value != value && !this.multiple) {
            if (this.wrapperElement){
                this.wrapperElement.value = value;
                value = this.wrapperElement.value;
            }
            this.value = value;
            this.emit('valueChanged', this.value, this);
        }
    },
    addItem: function(text, value) {
        var opt = new Option( 
            text, //text
            value,//value
            false,//defaultSelected
            false//selected
        );
        this.selectOptions.push(opt);
        if (this.wrapperElement) {
            this.wrapperElement.options[this.wrapperElement.options.length] = opt;
        }
    },
    removeItem: function(value) {
        for (var i=this.selectOptions.length-1; i>=0; i--) {
            if (this.selectOptions[i].value==value) {
                this.selectOptions.splice(i, 1);
            }
        }
        if (this.wrapperElement) {
            for (var i=this.wrapperElement.options.length-1; i>=0; i--) {
                if (this.wrapperElement.options[i].value==value) {
                    this.wrapperElement.options[i] = null;
                }
            }
        }
    },
    addItems: function(items) {
        for (var value in items) {
            this.addItem(items[value], value);
        }
    },
    cleanItems: function() {
        this.selectOptions = [];
        if (this.wrapperElement) {
            this.wrapperElement.options.length = 0;
        }
        this.setValue(null);
    },
    setReadOnly: function(readOnly){
        var enabled = this.enabled;
        this.readOnly = readOnly;
        this.setEnabled(enabled && !readOnly);
        this.enabled = enabled;
    },
    setEnabled: function($super, enabled){
        $super(enabled && !this.readOnly)
        this.enabled = enabled;
    },
    setSize: function(value)
    {
        this._setAttribute('size', value);
    },
    getSize: function()
    {
        return this.size;
    },
    setMultiple: function(value)
    {
        this._setAttribute('multiple', value);
    },
    getMultiple: function()
    {
        return this.multiple;
    },
    getValue: function($super)
    {
        if (this.multiple) {
            var value = [];
            if (this.wrapperElement) {
                for (var i=0; i<this.wrapperElement.options.length; i++) {
                    if (this.wrapperElement.options[i].selected) {
                        value.push(this.wrapperElement.options[i].value);
                    }
                }
            }
            return value;
        } else {
            return $super();
        }
    }
});

/**
 * @test setUp
 * window.myBox = new LMS.Widgets.SelectBox();
 * myBox.setDOMId("test");
 * myBox.paint();
 */

/**
 * @test testAddItem
 * window.myBox.addItem('text', '1');
 * 
 */

/**
 * @test testAddItem2
 * window.myBox.addItem('Название', '2');
 * //Проверяем можно ли установить value отличным от реально существующих в options select'a 
 * window.myBox.setValue('3');
 * assertNotEquals(window.myBox.getValue(), '3');
 * 
 */

/**
 * @test test1Paint
 * 
 * assertTrue('Painting', window.myBox.paint());
 */

/**
 * @test test2Hide
 * window.myBox.setVisible(false);
 * assertFalse('Hide Test', window.myBox.isVisible());
 */
 
/**
 * @test test3Show
 * window.myBox.setVisible(true);
 * assertTrue('Show Test', window.myBox.isVisible());
 */
 
 /**
 * @test test4Disable
 * window.myBox.setEnabled(false);
 * assertFalse('Disable Test', window.myBox.isEnabled());
 */
 
/**
 * @test test5Enable
 * window.myBox.setEnabled(true);
 * assertTrue('Enable Test', window.myBox.isEnabled());
 */  
 
  /**
 * @test test6SetReadOnly
 * window.myBox.setReadOnly(true);
 * assertTrue('Set Read only Test', window.myBox.isReadOnly());
 */
 
/**
 * @test test7UnsetReadOnly
 * window.myBox.setReadOnly(false);
 * assertFalse('Unset Read only Test', window.myBox.isReadOnly());
 */  