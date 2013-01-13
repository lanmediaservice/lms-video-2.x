(function( $ ){
    $.fn.booleanize = function(radioGroup) {
        this.each(function() {
            var input = $(this);
            var readOnly = input.is(':disabled') || input.is('[readonly]');
            if (input.get(0).tagName!='INPUT' || input.data('checkbox')) {
                return;
            }
            var checkbox;
            if (radioGroup) {
                checkbox = $('<input type="radio" name="' + radioGroup + '">');
            } else {
                checkbox = $('<input type="checkbox">');
            }
            if (readOnly) {
                checkbox.prop('disabled', true);
            }
            var value = input.val().length? parseInt(input.val()) : 0;
            if (value) {
                checkbox.prop("checked", true);
            }
            if (!readOnly) {
                checkbox.change(function(){
                    var value = parseInt(input.val());
                    var newValue = parseInt($(this).is(':checked')? 1 : 0);
                    if (newValue!==value) {
                        input.val(newValue);
                        input.change();
                        $j('input[type="radio"][name="' + radioGroup + '"]').change();
                    }
                });
            }
            input.hide();
            input.after(checkbox);
            input.data('checkbox', true);
        });
    };
})(jQuery); 