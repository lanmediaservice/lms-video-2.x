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
 
LMS.Widgets.PageIndexBoxOld = Class.create(LMS.Widgets.Generic, {
    pageSize: 10,
    count : 0,
    currentPage: 0,
    offset: 0,
    sufficses: {3:'K', 6: 'M', 9: 'G'},
    prevPageText: "<span>&larr;</span>",
    nextPageText: "<span>&rarr;</span>",
    beforePagesText: "",
    dotText: ".",
    onCreateElement: function() 
    {
        JSAN.require('LMS.Widgets.Factory');
        var pagesCount = this.pageSize ? Math.ceil(this.count/this.pageSize) : 0;
        this.currentPage = Math.ceil((this.offset+1)/this.pageSize);
        var wrapper = LMS.Widgets.Factory('LayerBox');
        wrapper.setDOMId(this.DOMId);
        wrapper.setClassName('paginator');
        if (pagesCount>1){

            if (this.currentPage>1) {
                var prevPageElement = LMS.Widgets.Factory('AnchorBox');
                var pageNumber = this.currentPage - 1;
                var newOffset = (pageNumber-1) * this.pageSize;
                prevPageElement.setTitle(pageNumber);
                prevPageElement.setHref('javascript:void(0)');
                prevPageElement.setValue(newOffset);
                LMS.Connector.connect(prevPageElement, 'action', this, 'setOffset');
                prevPageElement.addHTML(this.prevPageText);
            } else {
                var prevPageElement = LMS.Widgets.Factory('TextBox');
                prevPageElement.setValue(this.prevPageText);
            }
            prevPageElement.setClassName('arrow');
             
            if (this.currentPage<pagesCount) {
                var nextPageElement = LMS.Widgets.Factory('AnchorBox');
                var pageNumber = this.currentPage + 1;
                var newOffset = (pageNumber-1) * this.pageSize;
                nextPageElement.setTitle(pageNumber);
                nextPageElement.setHref('javascript:void(0)');
                nextPageElement.setValue(newOffset);
                LMS.Connector.connect(nextPageElement, 'action', this, 'setOffset');
                nextPageElement.addHTML(this.nextPageText);
            } else {
                var nextPageElement = LMS.Widgets.Factory('TextBox');
                nextPageElement.setValue(this.nextPageText);
            }
            nextPageElement.setClassName('arrow');

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
            if (points.indexOf(1)===-1) points.push(1);
            if (points.indexOf(pagesCount)===-1) points.push(pagesCount);
            points = points.sort(this._sortNumber).without(0);
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

            wrapper.addHTML(this.beforePagesText);
            wrapper.addWidget(prevPageElement);
            wrapper.addText('\xA0');
            wrapper.addWidget(nextPageElement);
            wrapper.addHTML('<BR>');

            for (var i=0; i<allpoints.length; i++){
                var pageNumber = allpoints[i].value;
                if (allpoints[i]['short']) {
                    var pageText = this.dotText;
                } else {
                    var pageText = this.truncateNumber(pageNumber);
                }
                if (pageNumber!=this.currentPage) {
                    var newOffset = (pageNumber-1) * this.pageSize;
                    var pageElement = LMS.Widgets.Factory('AnchorBox');
                    if (allpoints[i]['short']) {
                        pageElement.setTitle(pageNumber);
                        pageElement.setClassName('short');
                    }
                    pageElement.setHTML(pageText);
                    pageElement.setValue(newOffset);
                    pageElement.setHref('javascript:void(0)');
                    LMS.Connector.connect(pageElement, 'action', this, 'setOffset');

                    wrapper.addWidget(pageElement);
                } else {
                    var pageElement = LMS.Widgets.Factory('TextBox');
                    pageElement.setClassName('current');
                    pageElement.setValue(pageText);
                    wrapper.addWidget(pageElement);
                }
            }


 
        }
        return wrapper.createElement();
    },
    setOffset: function(offset)
    {
        if (this.offset!=offset) {
            this.offset = offset;
            this.emit('valueChanged', this.offset);
        }
    },
    setCount: function(count)
    {
        this.count = count;
    },
    setPageSize: function(pageSize)
    {
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
 * var myBox = new LMS.Widgets.PageIndexBoxOld('myBox');
 * myBox.setDOMId("test");
 * myBox.setCount(10000000000000);
 * myBox.setPageSize(10);
 * myBox.setOffset(500);
 * LMS.Connector.connect(myBox, 'valueChanged', myBox, 'paint');
 * assertTrue('Painting', myBox.paint());
 */
 
