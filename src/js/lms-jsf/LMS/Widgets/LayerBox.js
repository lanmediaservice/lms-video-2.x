/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: LayerBox.js 54 2009-10-21 06:35:41Z macondos $
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
 * Слой на базе HTML-элемента &lt;DIV&gt;
 * @class
 * @name LMS.Widgets.LayerBox
 * @augments LMS.Widgets.BlockGeneric
 */
 
LMS.Widgets.LayerBox = Class.create(LMS.Widgets.BlockGeneric, {
    onCreateElement: function() {
        this.wrapperElement = new Element("DIV", {
            'id': this.DOMId
        });
        
        this.applyDecorators();
        
        var self = this;
        this.wrapperElement.onclick = function () {
            self.emit('click', self.getValue(), self);
        };

        return this.wrapperElement;
    }
});

/**
 * @test testPaint
 * var myBox = new LMS.Widgets.LayerBox();
 * myBox.setDOMId("test");
 * myBox.setHTML('1<b>2</b>3');
 * myBox.addText('4<b>5</b>6');
 * assertTrue('Painting', myBox.paint());
 * myBox.addElement(new Element("BR"));
 * myBox.addHTML('<br>');
 * myBox.addHTML('7<b>8</b>9');
 * myBox.addText('1<b>2</b>3');
 */
