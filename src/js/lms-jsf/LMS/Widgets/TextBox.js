/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: TextBox.js 52 2009-09-20 21:27:13Z macondos $
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
 * Текстовый блок на базе HTML-элемента <SPAN>
 * @class
 * @augments LMS.Widgets.Generic
 */
 
LMS.Widgets.TextBox = Class.create(LMS.Widgets.Generic, {
    onCreateElement: function() {
        this.wrapperElement = new Element("SPAN", {
              'id' : this.DOMId
        }).update(this.value);
        
        this.applyDecorators();
        
        return this.wrapperElement;
    },
    setValue: function(value) { 
        if (this.value != value) {
            if (this.wrapperElement){
                this.wrapperElement.update(value);
            }        
            this.value = value;
            this.emit('valueChanged', this.value, this);
        }
    }
});

/**
 * @test setUp
 * window.myBox = new LMS.Widgets.TextBox();
 * myBox.setDOMId("test");
 * 
 * window.setChange = function()
 * {
 *     onChangePassed = true;
 * }
 * var onChangePassed = false;
 * LMS.Connector.connect(myBox, 'valueChanged', window, 'setChange');
 * 
 * assertTrue('Painting', myBox.paint());
 * assertEquals("check DOMId", myBox.DOMId, "test");
 * myBox.setValue("new <b>value</b>");
 * assertEquals("check value", myBox.getValue(), "new <b>value</b>");
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