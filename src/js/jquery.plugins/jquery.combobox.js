(function( $ ){
    $.fn.comboBox = function() {
        this.each(function() {
            var timeout;
            var input = $('input', $(this));
            var select = $('select', $(this));
            input.click(function(){
                select.show();
                if (timeout) clearTimeout(timeout);
            }).focus(function(){
                select.show();
                if (timeout) clearTimeout(timeout);
            }).blur(function(){
                timeout = setTimeout(function(){
                    select.hide();
                }, 100);
            });
            select.focus(function(){
                if (timeout) clearTimeout(timeout);
            }).click(function(){
                input.val(select.val()).focus();
                input.change();
                setTimeout(function(){
                    select.hide();
                }, 100);
            }).blur(function(){
                timeout = setTimeout(function(){
                    select.hide();
                }, 100);
            });
        });
    };
})(jQuery); 