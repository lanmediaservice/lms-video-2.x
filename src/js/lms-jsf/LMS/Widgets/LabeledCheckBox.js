/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: LabeledCheckBox.js 51 2009-09-19 09:45:54Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * 
 */
 
/** 
 * Зависимости
 * @requires LMS.Widgets.CheckBox, LMS.Widgets.LabelBox, LMS.Widgets.CompositeGeneric, LMS.Widgets.TextBox
 */
JSAN.require('LMS.Widgets.CompositeGeneric');
JSAN.require('LMS.Widgets.Factory');
 

/**
 * Simple lable box (for checkbox)
 * @class
 * @augments LMS.Widgets.Generic
 */
 LMS.Widgets.LabeledCheckBox = Class.create(LMS.Widgets.CompositeGeneric, {
    // properties
    checkBox : null,
    labelTextBox : null,
    caption: 'Label',
    initialize: function($super) 
    {
        $super();
        this.checkBox = LMS.Widgets.Factory('CheckBox');
        this.labelTextBox = LMS.Widgets.Factory('TextBox');
    },
    onCreateElement: function() { 
        var wrapperWidget = LMS.Widgets.Factory('LabelBox');
        wrapperWidget.setDOMId(this.DOMId);
        
        this.checkBox.setValue(this.value);
        LMS.Connector.connect(this.checkBox, 'valueChanged', this, 'onChange');
        
        this.labelTextBox.setValue(this.caption);
        
        wrapperWidget.addWidget(this.checkBox);
        wrapperWidget.addText('\xA0');
        wrapperWidget.addWidget(this.labelTextBox);
        
        this._childWidgets.push(wrapperWidget);
        this._childWidgets.push(this.checkBox);
        this._childWidgets.push(this.labelTextBox);

        this.wrapperElement = wrapperWidget.createElement();
        
        this.applyDecorators();

        return this.wrapperElement;
    },
    onChange: function(value) {
        this.emit('valueChanged', value, this);
    },
    setValue: function(value) {
        this.checkBox.setValue(value);
    },
    getValue: function() {
        return this.checkBox.getValue();
    },
    /**
     * Уcтанавливает значение текста рядом с "флажком"
     * @memberOf LMS.Widgets.LabeledCheckBox
     * @function
     * @name setCaption
     * @param {string} value
     * @return void
     */
    setCaption: function(caption) { 
        this.caption = caption.toString();
        if (this.labelTextBox) {
            this.labelTextBox.setValue(this.caption);
        }
    },
    /**
     * Возвращает значение текста рядом с "флажком" 
     * @memberOf LMS.Widgets.LabeledCheckBox
     * @function
     * @name getCaption
     * @return {string}
     */
    getCaption: function() { 
        return this.caption;
    }
});

/**
 * @test setUp
 * window.myBox = new LMS.Widgets.LabeledCheckBox();
 * myBox.setDOMId("test");
 * myBox.paint();
 */

/**
 * @test test1Paint
 * window.myBox = new LMS.Widgets.LabeledCheckBox();
 * myBox.setDOMId("test");
 * window.setChange = function(value)
 * {
 *     onChangePassed = true;
 * }
 * var onChangePassed = false;
 * LMS.Connector.connect(myBox, 'valueChanged', window, 'setChange');
 * 
 * myBox.setCaption('My check box');
 * 
 * assertTrue('Painting', myBox.paint());
 * assertEquals("check DOMId", myBox.DOMId, "test");
 * 
 * myBox.setTitle('Title title title');
 * 
 * myBox.setValue(0);
 * assertFalse("check value 1", myBox.getValue());
 * myBox.setValue(1);
 * assertTrue("check value 2", myBox.getValue());
 * 
 * myBox.setValue(false);
 * assertFalse("check value 3", myBox.getValue());
 * myBox.setValue(true);
 * assertTrue("check value 4", myBox.getValue());
 * 
 * myBox.setValue('0');
 * assertFalse("check value 5", myBox.getValue());
 * myBox.setValue('1');
 * assertTrue("check value 6", myBox.getValue());
 * 
 * assertTrue('OnChage Test', onChangePassed);
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