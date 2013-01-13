/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: CheckBox.js 52 2009-09-20 21:27:13Z macondos $
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
 * "Флажок" на базе HTML-элемента <INPUT type=checkbox> 
 * @class
 * @augments LMS.Widgets.Generic
 */
LMS.Widgets.CheckBox = Class.create(LMS.Widgets.Generic, {
    initialize: function($super) 
    {
        $super();
        this._decorators['forms'] = this.decoratorInitFormElement;
    },
    decoratorInitFormElement : function()
    {
        var self = this;
        this.wrapperElement.onclick = function () {
            self.setValue(this.checked);
        }
    },
    onCreateElement: function() { 
        this.wrapperElement = new Element("INPUT", {
              'id'      : this.DOMId,
              'type'    : 'checkbox',
              'checked' : this.value
        });
        
        this.applyDecorators();
        
        return this.wrapperElement;
    },
    setValue: function(value) {
        if (Object.isString(value)) {
            value = parseInt(value);
        }
        value = new Boolean(value).valueOf()
        if (this.value != value) {
            this.value = value;
            if (this.wrapperElement){
                this.wrapperElement.checked = value;
            }
            this.emit('valueChanged', this.value, this);
        }
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
    }
});

/**
 * @test setUp
 * window.myBox = new LMS.Widgets.CheckBox();
 * myBox.setDOMId("test");
 * myBox.paint();
 */

/**
 * @test test1Paint
 * window.myBox = new LMS.Widgets.CheckBox();
 * myBox.setDOMId("test");
 * 
 * window.setChange = function()
 * {
 *     onChangePassed = true;
 * }
 * var onChangePassed = false;
 * LMS.Connector.connect(myBox, 'valueChanged', window, 'setChange');

 * assertTrue('Painting', myBox.paint());
 * assertEquals("check DOMId", myBox.DOMId, "test");
 * 
 * myBox.setTitle('Title title title');
 * 
 * myBox.setValue(0);
 * assertFalse("check value", myBox.getValue());
 * myBox.setValue(1);
 * assertTrue("check value", myBox.getValue());
 * 
 * myBox.setValue(false);
 * assertFalse("check value", myBox.getValue());
 * myBox.setValue(true);
 * assertTrue("check value", myBox.getValue());
 * 
 * myBox.setValue('0');
 * assertFalse("check value", myBox.getValue());
 * myBox.setValue('1');
 * assertTrue("check value", myBox.getValue());
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