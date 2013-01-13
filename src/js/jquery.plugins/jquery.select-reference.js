(function( $ ){
   
    $.fn.countries = function() {
        return this.each(function(){
            var select = $(this);
            var list = [];
            $("option:selected", select).each(function () {
                list.push($(this).text());
            });
            select.empty();
            for (var i=0; i<list.length; i++) {
                for (var j=0; j<REFERENCE.COUNTRIES.length; j++) {
                    var country = REFERENCE.COUNTRIES[j];
                    if (list[i]==country.name) {
                        select.append($('<option value="' + country.country_id + '" selected="selected">' + country.name + '</option>'));
                    }
                }
            }
            for (var i=0; i<REFERENCE.COUNTRIES.length; i++) {
                var country = REFERENCE.COUNTRIES[i];
                if (list.indexOf(country.name)==-1) {
                    select.append($('<option value="' + country.country_id + '">' + country.name + '</option>'));
                }
            }
    });
    }

    $.fn.genres = function() {
        return this.each(function(){
            var select = $(this);
            var list = [];
            $("option:selected", select).each(function () {
                list.push($(this).text());
            });
            select.empty();
            for (var i=0; i<list.length; i++) {
                for (var j=0; j<REFERENCE.GENRES.length; j++) {
                    var genre = REFERENCE.GENRES[j];
                    if (list[i]==genre.name) {
                        select.append($('<option value="' + genre.genre_id + '" selected="selected">' + genre.name + '</option>'));
                    }
                }
            }
            for (var i=0; i<REFERENCE.GENRES.length; i++) {
                var genre = REFERENCE.GENRES[i];
                if (list.indexOf(genre.name)==-1) {
                    select.append($('<option value="' + genre.genre_id + '">' + genre.name + '</option>'));
                }
            }
        });
    }

    $.fn.roles = function() {
        return this.each(function(){
            var select = $(this);
            var value = select.attr('data-value');
            select.empty();
            for (var i=0; i<REFERENCE.ROLES.length; i++) {
                var role = REFERENCE.ROLES[i];
                select.append($('<option value="' + role.role_id + '">' + role.name + '</option>'));
            }
            select.val(value);
        });
    }

    $.fn.translationCombobox = function() {
        return this.each(function(){
            if (!window.TRANSLATION_SELECT) {
                var select = $('<select class="combobox-select translation" style="display: none" size="15">');
                for (var i=0; i<REFERENCE.TRANSLATIONS.length; i++) {
                    var text = REFERENCE.TRANSLATIONS[i];
                    select.append($('<option value="' + text + '">' + text + '</option>'));
                }
                $('body').append(select);
                window.TRANSLATION_SELECT = select;
            }
            var input = $(this);
            input.bindComboBox(window.TRANSLATION_SELECT);
        });
    }

    $.fn.qualityCombobox = function() {
        return this.each(function(){
            if (!window.QUALITY_SELECT) {
                var select = $('<select class="combobox-select quality" style="display: none" size="15">');
                for (var i=0; i<REFERENCE.QUALITIES.length; i++) {
                    var text = REFERENCE.QUALITIES[i];
                    select.append($('<option value="' + text + '">' + text + '</option>'));
                }
                $('body').append(select);
                window.QUALITY_SELECT = select;
            }
            var input = $(this);
            input.bindComboBox(window.QUALITY_SELECT);
        });
    }

    $.fn.bindComboBox = function(select) {
        var switchTime = 100;
        
        var selectHideHandler = function(){
            select.hide();
        }

        var clearHideTimeout = function(){
            if (select.data('timeout')) {
                clearTimeout(select.data('timeout'));
            }
        }
        var deferHide = function(){
            clearHideTimeout();
            select.data('timeout', setTimeout(selectHideHandler, switchTime));
        }
        this.each(function() {
            var input = $(this);
            var inputInHandler = function(){
                clearHideTimeout();
                select.show();
                var offset = input.offset();
                offset.top += input.innerHeight()+2;
//                offset.left += 1;
                select.offset(offset);
                select.data('input', input);
            }
            var inputOutHandler = function(){
                deferHide();
            }
            input.click(inputInHandler)
                 .focus(inputInHandler)
                 .blur(inputOutHandler);
            
            select.focus(function(){
                clearHideTimeout();
            }).click(function(){
                var input = select.data('input');
                if (input.val()!=select.val()) {
                    input.val(select.val()).focus();
                    input.change();
                }
                input.blur();
                deferHide();
            }).blur(function(){
                deferHide();
            });
        });
    };
})(jQuery); 