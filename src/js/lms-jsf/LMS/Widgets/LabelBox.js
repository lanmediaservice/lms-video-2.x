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
LMS.Widgets.LabelBox = Class.create(LMS.Widgets.BlockGeneric, {
    _for : null,
    onCreateElement: function() {
        this.wrapperElement = new Element("LABEL", {
            'id': this.DOMId
        });
        
        this.applyDecorators();
        
        return this.wrapperElement;
    },
    /**
     * Устанавливает связь с чекбоксом (этого не требуется если он находится 
     * внутри блока). На входе модет быть или строка содержащая 
     * DOM-индентификатор чекбокса, или элемент чекбокса или виджет чекбокса,
     * в последних двух случаях DOM-индентификатор будет "добыт" автоматически.
     * @memberOf LMS.Widgets.LabelBox
     * @function
     * @name setFor
     * @param {string/HTMLElement/LMS.Widgets.CheckBox} DOMIdOrElementOrWidget
     * @return void
     */
    setFor: function(DOMIdOrElementOrWidget) { 
        if (Object.isString(DOMIdOrElementOrWidget)) {
            this._for = DOMIdOrElementOrWidget;
        } else if (Object.isElement(DOMIdOrElementOrWidget)) {
            this._for = DOMIdOrElementOrWidget.id;
        } else if (DOMIdOrElementOrWidget && !Object.isUndefined(DOMIdOrElementOrWidget.DOMId)) {
            this._for = DOMIdOrElementOrWidget.DOMId;
        }
        if (this.wrapperElement) {
            this.wrapperElement.writeAttribute('for', this._for);
        }
    }
});

/**
 * @test setUp
 * window.myBox = new LMS.Widgets.LabelBox();
 * myBox.setDOMId("test");
 * myBox.paint();
 */
 
/**
 * @test test1Paint
 * window.myBox = new LMS.Widgets.LabelBox();
 * myBox.DOMId = "test";
 * myBox.addHTML('1:<input id="checkbox1" type="checkbox"><br>2:<input id="checkbox2" type="checkbox"><br>');
 * assertTrue('Painting', myBox.paint());
 * assertEquals("check DOMId", myBox.DOMId, "test");
 * myBox.addHTML(" <b>label for checkbox</b>");
 */
 
/**
 * @test test2SetForCheckBox1
 * myBox.setFor('checkbox1');
 */
 
/**
 * @test test3SetForCheckBox2
 * myBox.setFor($('checkbox2'));
 */