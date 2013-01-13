/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: PageIndexBox.js 54 2009-10-21 06:35:41Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * 
 */
 
/** 
 * @requires LMS.Widgets.LayerBox, LMS.Widgets.Generic
 */

JSAN.require('LMS.Widgets.Generic');

/**
 * Simple memo box
 * @class
 * @augments LMS.Widgets.Generic
 */
 
LMS.Widgets.PageIndexBox = Class.create(LMS.Widgets.Generic, {
    pageSize: 10,
    count : 0,
    currentPage: 0,
    offset: 0,
    sufficses: {3:'K', 6: 'M', 9: 'G'},
    prevPageText: "&larr;",
    nextPageText: "&rarr;",
    beforePagesText: "",
    dotText: ".",
    className: 'paginator',
    onCreateElement: function() 
    {
        JSAN.require('LMS.Widgets.Factory');
        var pagesCount = this.pageSize ? Math.ceil(this.count/this.pageSize) : 0;
        this.currentPage = Math.ceil((this.offset+1)/this.pageSize);
        var wrapper = LMS.Widgets.Factory('LayerBox');
        var ul = LMS.Widgets.Factory('UnorderedListBox');
        if (pagesCount>1){
            wrapper.addHTML(this.beforePagesText);

            var li = LMS.Widgets.Factory('ListItemBox');
            li.setClassName('prev');
            var prevPageElement = LMS.Widgets.Factory('AnchorBox');
            if (this.currentPage>1) {
                var pageNumber = this.currentPage - 1;
                var newOffset = (pageNumber-1) * this.pageSize;
                prevPageElement.setTitle(pageNumber);
                prevPageElement.setValue(newOffset);
                LMS.Connector.connect(prevPageElement, 'action', this, 'setOffset');
            } else {
                prevPageElement.setHref(null);
                li.addClassName('disabled');
            }
            prevPageElement.addHTML(this.prevPageText);
            li.addWidget(prevPageElement);
            ul.addWidget(li);

            var points = [];
            var step = Math.pow(10, Math.floor(this.logB(pagesCount, 10)));
            var beginRange = {};
            var endRange = {};
            var base = 3;
            beginRange[step] = 0;
            endRange[step] = pagesCount;
            for (; step>=1; step = step/10){
                for (var i=beginRange[step]; i<=endRange[step]; i += step) {
                    if (points.indexOf(i)===-1) {
                        if (this.checkPoint(this.currentPage, i, base)) {
                            points.push(i);
                            var nextStep = step/10;
                            if (typeof(beginRange[nextStep])=='undefined') beginRange[nextStep] = pagesCount;
                            if (typeof(endRange[nextStep])=='undefined') endRange[nextStep] = 0;
                            beginRange[nextStep] = Math.max(0, Math.min(i-step, beginRange[nextStep]));
                            endRange[nextStep] = Math.min(pagesCount, Math.max(i+step, endRange[nextStep]));
                        }
                    }
                }
            }
            for (var i=this.currentPage-5; i<=(this.currentPage+5); i++) {
                if (i>0 && i<=pagesCount) {
                    points.push(i);
                }
            }
            if (points.indexOf(1)===-1) points.push(1);
            if (points.indexOf(pagesCount)===-1) points.push(pagesCount);
            points = points.sort(this._sortNumber).without(0).uniq();
            var allpoints = [];
            for (var i=0; i<points.length; i++){
                if (i>0) {
                   var subpoints = this.cutRange(points[i-1], points[i], 9);
                   for (var j=0; j<subpoints.length; j++){
                       allpoints.push({'short': true, 'value': subpoints[j]});
                   }
                }
                allpoints.push({'short': false, 'value': points[i]});
            }

            for (var i=0; i<allpoints.length; i++){
                var li = LMS.Widgets.Factory('ListItemBox');
                li.setClassName('default');
                var pageNumber = allpoints[i].value;
                if (allpoints[i]['short']) {
                    li.setClassName('short');
                    var pageText = this.dotText;
                } else {
                    var pageText = ' ' + this.truncateNumber(pageNumber) + ' ';
                }
                var pageElement = LMS.Widgets.Factory('AnchorBox');
                if (pageNumber!=this.currentPage) {
                    var newOffset = (pageNumber-1) * this.pageSize;
                    pageElement.setTitle(pageNumber);
                    pageElement.setHTML(pageText);
                    pageElement.setValue(newOffset);
                    LMS.Connector.connect(pageElement, 'action', this, 'setOffset');
                } else {
                    li.setClassName('current');
                    pageElement.setHTML(pageText.strip());
                }
                li.addWidget(pageElement);
                ul.addWidget(li);
            }

            var li = LMS.Widgets.Factory('ListItemBox');
            li.setClassName('next');
            var nextPageElement = LMS.Widgets.Factory('AnchorBox');
            if (this.currentPage<pagesCount) {
                var pageNumber = this.currentPage + 1;
                var newOffset = (pageNumber-1) * this.pageSize;
                nextPageElement.setTitle(pageNumber);
                nextPageElement.setValue(newOffset);
                LMS.Connector.connect(nextPageElement, 'action', this, 'setOffset');
            } else {
                nextPageElement.setHref(null);
                li.addClassName('disabled');
            }
            nextPageElement.addHTML(this.nextPageText);
            li.addWidget(nextPageElement);
            ul.addWidget(li);
 
        }

        wrapper.addWidget(ul);

        this.wrapperElement = wrapper.createElement();
        
        this.applyDecorators();
        
        return this.wrapperElement;
    },
    setOffset: function(offset, allowEmit)
    {
        if (Object.isUndefined(allowEmit)) {
            allowEmit = true;
        }
        offset = parseInt(offset)
        if (this.offset!=offset) {
            this.offset = offset;
            if (allowEmit) {
                this.emit('valueChanged', this.offset);
            }
        }
    },
    setCount: function(count)
    {
        count = parseInt(count)
        this.count = count;
    },
    setPageSize: function(pageSize)
    {
        pageSize = parseInt(pageSize)
        this.pageSize = pageSize;
    },
    logB: function (x, base) {
        return Math.log(x)/Math.log(base);
    },
    p: function (x, i, base) {
        if (x==i) return 0;
        return Math.ceil(this.logB(Math.abs(x-i),base));
    },
    r: function (x, base){
        return Math.pow(base, Math.ceil(this.logB(x, base)));
    },
    truncateNumber: function(x) {
        if (!x) {
            return x;
        } else if (!(x % 1000000000)) {
            return (x/1000000000) + '' + this.sufficses[9];
        } else if (!(x % 1000000)) {
            return (x/1000000) + '' + this.sufficses[6];
        } else if (!(x % 1000)) {
            return (x/1000) + '' + this.sufficses[3];
        } else {
            return x;
        }
    },
    _sortNumber: function(a, b) {
        return a - b;
    },
    checkPoint: function (x, i, base){
        y = this.p(x, i, base)-1;
        level = this.r(Math.pow(base, y), 10);
        if ((level<1) || !(i % level)) {
            // alert(i + ' ' + level)
            return true;
        }
        return false;
    },
    truncateE: function(x){
        numberExponent = Math.pow(10, Math.floor(this.logB(x, 10)));
        return numberExponent * Math.round(x / numberExponent);
    },
    cutRange: function(x1, x2, count){
        n = x2-x1;
        if (n<count) return [];
        partSize =  this.truncateE(n/count);
        points = [];
        currentPoint = x1;
        while (currentPoint<(x2-partSize)) {
            currentPoint += partSize;
            points.push(currentPoint);
        }
        return points;
    }
});

/**
 * @test testPaint
 * var myBox = new LMS.Widgets.PageIndexBox('myBox');
 * myBox.setDOMId("test");
 * myBox.setCount(10000000000000);
 * myBox.setPageSize(10);
 * myBox.setOffset(500);
 * LMS.Connector.connect(myBox, 'valueChanged', myBox, 'paint');
 * assertTrue('Painting', myBox.paint());
 */
 
