(function( $ ){

    function isLoadedImage(srcOrImage)
    {
        var newImg;
        if (!(typeof(srcOrImage)=="object")) {
            newImg = new Image();
            newImg.src = srcOrImage;
        } else {
            newImg = srcOrImage;
        }
        var width = newImg.width;
        var height = newImg.height;
        delete newImg;
        return (width && height);
    }
    
    function loadImage(src, img, caption)
    {
        var newImg = new Image();
        newImg.src = src;
        var loadHandler = function() {
            var height = newImg.height;
            var width = newImg.width;
            caption.html(width + 'x' + height);
            if (img.attr('data-original')) {
                img.attr('src', src)
                   .removeAttr('data-original');
            }
            delete newImg;
        }
        if (isLoadedImage(newImg)) {
            loadHandler();
        } else {
            newImg.onload = loadHandler;
        }
    }
    
    $.fn.organizeImages = function(options) {
        
        options = $.extend({}, $.fn.organizeImages.defaults, options); 

        function splitUrls(str)
        {
            return str.split(/[\r\n]+/);
        }

        function urlsIsEqual(urls1, urls2)
        {
            return urls1.join('\n') == urls2.join('\n');
        }

        function fillImagesListFromUrls(urls, ul, onAddCallback, onRemoveCallback, force)
        {
            var a;
            if (typeof(urls)=="string") {
                urls = splitUrls(urls);
            }
            var currentUrls = [];
            ul.find('li').each(function(){
                currentUrls.push($(this).attr('data-url'));
            });
            
            if (!force && urlsIsEqual(urls, currentUrls)) {
                return;
            }
            
            ul.empty();
            var fancyboxGroup = 'fancybox-' + parseInt(Math.random()*10000);
            for (var i=0; i<urls.length; i++) {
                var url = urls[i];
                if (url) {
                    var img = $('<img>');
                    var src = url;
                    if (options.imageProxy) {
                        src = options.imageProxy + '?url=' + encodeURIComponent(url);
                    }
                    if (!$.browser.msie && options.loadImage && !isLoadedImage(src)) {
                        img.attr('src', options.loadImage);
                        img.attr('data-original', src);
                    } else {
                        img.attr('src', src);
                    }
                    var imageCaption = $('<div class="oi-image-caption">');
                    loadImage(src, img, imageCaption);

                    var imgControl = $('<div class="oi-image-control"></div>')
                    imgControl.append('<a title="Ссылка" href="' + url + '" target="_blank"><span class="pictos">j</span></a>');
                    imgControl.append('<a title="Увеличить" href="" onclick="$j(this).parent().parent().find(&quot;a[rel^=&#39;' + fancyboxGroup + '&#39;]&quot;).click();return false"><span class="pictos">`</span></a>');
                    
                    if (onRemoveCallback) {
                        a = $('<a title="Убрать" href="" onclick="return false">');
                        a.click(onRemoveCallback);
                        imgControl.append(a.append('<span class="pictos">*</span>'));
                    }

                    a = $('<a rel="' + fancyboxGroup + '" target="_blank">')
                    a.attr('href', src);

                    var li = $('<li>');
                    li.attr('data-url', url)
                    .append(a.append(img))
                    .append(imgControl)
                    .append(imageCaption);
                    ul.append(li);
                }
            }
            if ($.fn.fancybox) {
                var transitionIn = $j.browser.opera? 'none' : 'elastic';
                var transitionOut = $j.browser.opera? 'none' : 'elastic';
                var changeFade = $j.browser.opera? 0 : 50;
                var changeSpeed = $j.browser.opera? 0 : 100;
                ul.find('a[rel^="fancybox"]').fancybox({
                    'easingIn': 'easeOutBack',
                    'easingOut': 'easeInBack',
                    'cyclic': true,
                    'overlayColor' : '#000',
                    'overlayOpacity': 0.85,
                    'transitionIn': transitionIn,
                    'transitionOut': transitionOut,
                    'changeFade': changeFade, 
                    'changeSpeed': changeSpeed, 
                    'padding': 0,
                    'type': 'image'
                });
            }
            if (onAddCallback) {
                var addImageBtn = $('<a class="oi-add-image-btn" title="Добавить">+</a>');
                addImageBtn.click(onAddCallback);
                ul.append($('<li>').append(addImageBtn));
            }
        }


        return this.each(function() {
            
            var textarea = $(this);
            if (textarea.get(0).tagName!='TEXTAREA' || textarea.data('organizeImages')) {
                return;
            }
            
            var imagesList = $('<ul class="oi-images-list"></ul>');
            
            var onRemove = function(){
                var li = $(this).parent().parent();
                var index = li.index();
                var urls = splitUrls(textarea.val());
                urls.splice(index, 1);
                textarea.val(urls.join("\n")).change();
            }
            
            fillImagesListFromUrls(textarea.val(), imagesList, options.onAdd, onRemove, true);
            
            var imagesOrganizer = $('<div class="oi-container">');


            imagesOrganizer.append(imagesList);

            imagesList.sortable({
                placeholder: "vacant",
                opacity: 0.5,
                update: function() {
                    var urls = [];
                    imagesList.find('li').each(function(){
                        urls.push($(this).attr('data-url'));
                    });
                    textarea.val(urls.join("\n")).change();
                },
                items: "li:not(:last-child)"
            });

            textarea.change(function(){
                fillImagesListFromUrls($(this).val(), imagesList, options.onAdd, onRemove, false);
            });
            
            textarea.hide();
            textarea.after(imagesOrganizer);
            textarea.data('organizeImages', true);

        });
    }
    
    $.fn.organizeImages.defaults = {
        
    }; 
    
})(jQuery); 