LMS.Text = {}
        
LMS.Text.declension = function(int, expressions, langCode2)
{
    if (!langCode2) {
        langCode2 = 'en';
    }
    if (expressions.length < 2) {
        expressions[1] = expressions[0];
    }
    if (expressions.length < 3) {
        expressions[2] = expressions[1];
    }
    var result;
    switch (langCode2) {
        case 'en':
            result = int==1? expressions[0] : expressions[1];
            break;
        case 'ru':
        case 'uk':
        case 'be':
            var count = int % 100; 
            if (count >= 5 && count <= 20) { 
                result = expressions[2];
            } else { 
                count = count % 10; 
                if (count == 1) { 
                    result = expressions[0]; 
                } else if (count >= 2 && count <= 4) {
                    result = expressions[1];
                } else { 
                    result = expressions[2];
                }
            } 
            break;
        default: 
            result = expressions[0];
    }
    return result; 
}

LMS.Text.trim = function(str, charlist) 
{
    if (str==null) {
        return '';
    }
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: mdsjack (http://www.mdsjack.bo.it)
    // +   improved by: Alexander Ermolaev (http://snippets.dzone.com/user/AlexanderErmolaev)
    // +      input by: Erkekjetter
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: DxGx
    // +   improved by: Steven Levithan (http://blog.stevenlevithan.com)
    // +    tweaked by: Jack
    // +   bugfixed by: Onno Marsman
    // *     example 1: trim('    Kevin van Zonneveld    ');
    // *     returns 1: 'Kevin van Zonneveld'
    // *     example 2: trim('Hello World', 'Hdle');
    // *     returns 2: 'o Wor'
    // *     example 3: trim(16, 1);
    // *     returns 3: 6

    var whitespace, l = 0, i = 0;
    str += '';
    
    if (!charlist) {
        // default list
        whitespace = " \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000";
    } else {
        // preg_quote custom list
        charlist += '';
        whitespace = charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
    }
    
    l = str.length;
    for (i = 0; i < l; i++) {
        if (whitespace.indexOf(str.charAt(i)) === -1) {
            str = str.substring(i);
            break;
        }
    }
    
    l = str.length;
    for (i = l - 1; i >= 0; i--) {
        if (whitespace.indexOf(str.charAt(i)) === -1) {
            str = str.substring(0, i + 1);
            break;
        }
    }
    
    return whitespace.indexOf(str.charAt(0)) === -1 ? str : '';
}

LMS.Text.basename = function(path, suffix) {
    // Returns the filename component of the path  
    // 
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/basename    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Ash Searle (http://hexmen.com/blog/)
    // +   improved by: Lincoln Ramsay
    // +   improved by: djmix
    // *     example 1: basename('/www/site/home.htm', '.htm');    // *     returns 1: 'home'
    // *     example 2: basename('ecra.php?p=1');
    // *     returns 2: 'ecra.php?p=1'
    var b = path.replace(/^.*[\/\\]/g, '');
     if (typeof(suffix) == 'string' && b.substr(b.length - suffix.length) == suffix) {
        b = b.substr(0, b.length - suffix.length);
    }
 
    return b;
}