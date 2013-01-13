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
JSAN.require('LMS.Widgets.Factory');

/**
 * Виджет для HTML элемента &lt;LABEL&gt; 
 * @class
 * @augments LMS.Widgets.BlockGeneric
 */
LMS.Widgets.UnorderedListBox = Class.create(LMS.Widgets.BlockGeneric, {
    onCreateElement: function() {
        this.wrapperElement = new Element("UL", {
            'id': this.DOMId
        });
        
        this.applyDecorators();
        
        return this.wrapperElement;
    }
});

/**
 * @test setUp
 * window.myBox = new LMS.Widgets.UnorderedListBox();
 * myBox.setDOMId("test");
 * myBox.paint();
 */
 
/**
 * @test test1Paint
 * window.myBox = new LMS.Widgets.UnorderedListBox();
 * myBox.DOMId = "test";
 * var li1 = LMS.Widgets.Factory('ListItemBox');
 * li1.addHTML('first item');
 * myBox.addWidget(li1);
 * var li2 = LMS.Widgets.Factory('ListItemBox');
 * li2.addHTML('second item');
 * myBox.addWidget(li2);
 * assertTrue('Painting', myBox.paint());
 * assertEquals("check DOMId", myBox.DOMId, "test");
 */