/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: LabelBox.js 49 2009-07-17 08:10:15Z macondos $
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
 * Виджет для HTML элемента &lt;LABEL&gt; 
 * @class
 * @augments LMS.Widgets.BlockGeneric
 */
LMS.Widgets.ListItemBox = Class.create(LMS.Widgets.BlockGeneric, {
    onCreateElement: function() {
        this.wrapperElement = new Element("LI", {
            'id': this.DOMId
        });
        
        this.applyDecorators();
        
        return this.wrapperElement;
    }
});

/**
 * @test setUp
 * window.myBox = new LMS.Widgets.ListItemBox();
 * myBox.setDOMId("test");
 * myBox.paint();
 */
 
/**
 * @test test1Paint
 * window.myBox = new LMS.Widgets.ListItemBox();
 * myBox.DOMId = "test";
 * myBox.addHTML('1234');
 * assertTrue('Painting', myBox.paint());
 * assertEquals("check DOMId", myBox.DOMId, "test");
 */