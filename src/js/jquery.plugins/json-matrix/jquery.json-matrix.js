(function( $ ){

    $.fn.jsonMatrix = function(options) {
        
        options = $.extend({}, $.fn.jsonMatrix.defaults, options); 

        var map = options.map;
        var width = options.width;

        
        
        function fillTable(data, table, textarea, onChange)
        {
            var readOnly = textarea.is(':disabled') || textarea.is('[readonly]');
            table.empty();
            table.append($('<thead></thead>'));
            table.append($('<tbody></tbody>'));
            if (map.cols) {
                var tr = $('<tr></tr>');
                if (map.rows) {
                    tr.append($('<th></th>'));
                }
                for (var j = 0; j < map.cols.length; j++) {
                    var th = $('<th></th>');
                    th.html(map.cols[j].title);
                    tr.append(th);
                }
                $('thead', table).append(tr);
            }
            tr = $('<tr></tr>');
            for (j = 0; j < map.cols.length; j++) {
                var td = $('<td></td>');
                var mapOption = map.cols[j];
                var key = mapOption.key;
                
                if (!readOnly) {
                    var input = $('<input>');
                    input.val(data[key]);
                    input.attr('data-key', key);
                    input.change(onChange);
                    if (mapOption.type=='number') {
                        input.attr('type', 'number');
                    } else {
                        input.attr('type', 'text');
                    }
                    if (mapOption.width) {
                        input.width(mapOption.width);
                    } else if (width) {
                        input.width(width);
                    }
                    td.append(input);
                    if (mapOption.type=='boolean') {
                        td.css('text-align', 'center');
                        if (mapOption.radioGroup) {
                            input.booleanize(mapOption.radioGroup);
                        } else {
                            input.booleanize();
                        }
                    }
                } else {
                    if (mapOption.type=='boolean') {
                        td.css('text-align', 'center');
                        input = $('<input>');
                        input.val(data[key]);
                        input.attr('readonly', true);
                        td.append(input);
                        input.booleanize();
                    } else {
                        td.html(data[key]);
                    }
                }
                tr.append(td);
            }
            $('tbody', table).append(tr);
        }
        
        function getData(table)
        {
            var data = {};
            $('tbody input[data-key]', table).each(function(){
                var input = $(this);
                var key = input.attr('data-key');
                data[key] = input.val();
            });
            return data;
        }
        
        return this.each(function() {
            var textarea = $(this);
            if (textarea.get(0).tagName!='TEXTAREA' || textarea.data('jsonMatrix')) {
                return;
            }
            
            var table = $('<table></table>');
            table.addClass('json');
            table.addClass('silver');
            
            
            function onChange()
            {
                var data = getData(table);
                textarea.val(Object.toJSON(data));
                textarea.change();
            }
            
            var data = textarea.val().evalJSON();
            fillTable(data, table, textarea, onChange);

            textarea.change(function(){
                if (Object.toJSON(getData(table))!=$(this).val()) {
                    fillTable($(this).val().evalJSON(), table, textarea, onChange);
                }
            });
            
            
            textarea.hide();
            textarea.after($('<div class="table-wrapper">').append(table));
            textarea.data('jsonMatrix', true);
        });
    }
    
    $.fn.jsonMatrix.defaults = {
        
    }; 
    
})(jQuery); 