/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: PageIndexBox.js 54 2009-10-21 06:35:41Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * 
 */
 
JSAN.require('LMS.Widgets.Generic');

LMS.Widgets.GenreBox = Class.create(LMS.Widgets.Generic, {
    genres: null,
    genreBoxes: null,
    onCreateElement: function() 
    {
        JSAN.require('LMS.Widgets.Factory');
        var wrapper = LMS.Widgets.Factory('LayerBox');
        wrapper.setDOMId(this.DOMId);
        this.genreBoxes = {};
        this.genres.each(function(genre){
            var id = genre.key;
            var name = genre.value.name;
            var genreBox = LMS.Widgets.Factory('LabeledCheckBox');
            genreBox.setCaption(name);
            this.genreBoxes[id] = genreBox;
            wrapper.addWidget(genreBox);
            wrapper.addHTML('<BR>');
        },this);


        this.wrapperElement = wrapper.createElement();
        this.applyDecorators(); 
        return this.wrapperElement;
    },
    setGenres: function(genres)
    {
        this.genres = $H(genres);
    }
});