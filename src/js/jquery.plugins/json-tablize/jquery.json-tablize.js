(function( $ ){

    $.fn.jsonTablize = function(options) {
        
        options = $.extend({}, $.fn.jsonTablize.defaults, options); 

        var map = options.map;
        var width = options.width;

        function add(textarea)
        {
            var data = textarea.val().evalJSON();
            if (!Object.isArray(data)) {
                data = [];
            }
            if (map) {
                var item = {};
                for (var j = 0; j < map.length; j++) {
                    var key = map[j].key;
                    item[key] = '';
                }
            } else {
                item = '';
            }
            data.push(item);
            textarea.val(Object.toJSON(data));
            textarea.change();
        }

        function remove(textarea, num)
        {
            var data = textarea.val().evalJSON();
            data.splice(num, 1);
            textarea.val(Object.toJSON(data));
            textarea.change();
        }
        
        function up(textarea, num)
        {
            num = parseInt(num);
            var data = textarea.val().evalJSON();
            LMS.Utils.move(data, num, num-1);
            textarea.val(Object.toJSON(data));
            textarea.change();
        }
        
        function down(textarea, num)
        {
            num = parseInt(num);
            var data = textarea.val().evalJSON();
            LMS.Utils.move(data, num, num+1);
            textarea.val(Object.toJSON(data));
            textarea.change();
        }
        
        function fillTable(data, table, textarea, onChange)
        {
            var readOnly = textarea.is(':disabled') || textarea.is('[readonly]');
            table.empty();
            table.append($('<thead></thead>'));
            table.append($('<tbody></tbody>'));
            table.append($('<tfoot></tfoot>'));
            if (map) {
                var tr = $('<tr></tr>');
                for (var j = 0; j < map.length; j++) {
                    var th = $('<th></th>');
                    th.html(map[j].title);
                    tr.append(th);
                }
                if (!readOnly) {
                    tr.append($('<th colspan="3"></th>'));
                }
                $('thead', table).append(tr);
            }
            for (var i = 0; i < data.length; i++) {
                tr = $('<tr></tr>');
                if (map) {
                    for (j = 0; j < map.length; j++) {
                        var td = $('<td></td>');
                        var key = map[j].key;
                        if (!readOnly) {
                            var input = $('<input>');
                            input.val(data[i][key]);
                            input.attr('data-num', i);
                            input.attr('data-key', key);
                            input.change(onChange);
                            if (map[j].type=='number') {
                                input.attr('type', 'number');
                            } else {
                                input.attr('type', 'text');
                            }
                            if (map[j].width) {
                                input.width(map[j].width);
                            } else if (width) {
                                input.width(width);
                            }
                            td.append(input);
                            if (map[j].type=='boolean') {
                                td.css('text-align', 'center');
                                if (map[j].radioGroup) {
                                    input.booleanize(map[j].radioGroup);
                                } else {
                                    input.booleanize();
                                }
                            }
                        } else {
                            if (map[j].type=='boolean') {
                                var input = $('<input>');
                                input.val(data[i][key]);
                                input.attr('readonly', true);
                                td.css('text-align', 'center');
                                td.append(input);
                                if (map[j].radioGroup) {
                                    input.booleanize(map[j].radioGroup + '_readonly');
                                } else {
                                    input.booleanize();
                                }
                            } else {
                                td.html(data[i][key]);
                            }
                        }
                        tr.append(td);
                    }
                } else {
                        var td = $('<td></td>');
                        if (!readOnly) {
                            var input = $('<input>');
                            input.val(data[i]);
                            input.attr('data-num', i);
                            input.attr('type', 'text');
                            input.change(onChange);
                            if (width) {
                                input.width(width);
                            }
                            td.append(input);
                        } else {
                            td.html(data[i]);
                        }
                        tr.append(td);
                }
                if (!readOnly) {
                    td = $('<td></td>');
                    td.addClass('actions');
                    if (i>0) {
                        var btnUp = $('<a title="Вверх" class="pictos">{</a>');
                        btnUp.attr('data-num', i);
                        btnUp.click(function(){
                            var num = $(this).attr('data-num');
                            up(textarea, num);
                        });
                        td.append(btnUp);
                    }
                    tr.append(td);

                    td = $('<td></td>');
                    td.addClass('actions');
                    if (i<(data.length-1)) {
                        var btnDown = $('<a title="Вниз" class="pictos">}</a>');
                        btnDown.attr('data-num', i);
                        btnDown.click(function(){
                            var num = $(this).attr('data-num');
                            console.log(num);
                            down(textarea, num);
                        });
                        td.append(btnDown);
                    }
                    tr.append(td);

                    var btnRemove = $('<a title="Удалить" class="pictos">*</a>');
                    btnRemove.attr('data-num', i);
                    btnRemove.click(function(){
                        var num = $(this).attr('data-num');
                        remove(textarea, num);
                    });
                    td = $('<td></td>').append(btnRemove);
                    td.addClass('actions');
                    tr.append(td);
                }
                $('tbody', table).append(tr);
            }
            if (!readOnly) {
                var btnAdd = $('<a class="minibutton"><span>Добавить</span></a>');
                btnAdd.click(function(){
                    add(textarea);
                });
                $('tfoot', table).append(
                    $('<tr></tr>').append(
                        $('<td></td>').attr('colspan', map? map.length + 3 : 4).append(
                            btnAdd
                        )
                    )
                );
            }
            if ($('tbody', table).is(':empty')) {
                $('tbody', table).append(
                    $('<tr></tr>').append(
                        $('<td></td>').attr('colspan', map? map.length + 3 : 4).html('Не определено')
                    )
                )
            }
        }
        
        function getData(table)
        {
            var data = [];
            $('tbody input', table).each(function(){
                var input = $(this);
                var n = input.attr('data-num');
                if (map) {
                    var key = input.attr('data-key');
                    if (!data[n]) {
                        data[n] = {};
                    }
                    data[n][key] = input.val();
                } else {
                    data[n] = input.val();
                }
            });
            return data;
        }
        
        return this.each(function() {
            var textarea = $(this);
            if (textarea.get(0).tagName!='TEXTAREA' || textarea.data('jsonTablize')) {
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
            if (map) {
                textarea.after($('<div class="table-wrapper">').append(table));
            } else {
                textarea.after(table);
            }
            textarea.data('jsonTablize', true);
        });
    }
    
    $.fn.jsonTablize.defaults = {
        
    }; 
    
})(jQuery); 