/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: Signalable.js 48 2009-07-15 13:58:55Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * 
 */

JSAN.require('LMS');
JSAN.require('LMS.Connector');

/**
 * Класс-предок для использования технлогии сигнал-слот
 * 
 * Идея позаимствована из Qt4 Signals and Slots:
 * http://doc.crossplatform.ru/qt/4.4.3/signalsandslots.html
 * Для генерации сигнала в нужный момент объект должен вызвать метод
 * this.emit('названиеСигнала', некоторые, параметры);
 * Сигнал может (если хочет) кто-то слушать. Для
 * этого в вышестоящем объекте сигнал нужно связать со слотом:
 * LMS.Connector.connect(signalObject, 'названиеСигнала', slotObject, 'названиеСлота');
 * 
 * Следует отметить, что slotObject.названиеСлота является самым обычным методом класса
 * Например такой код:
 * LMS.Connector.connect(signalObject, 'названиеСигнала', window, 'alert');
 * просто покажет alert того что передал сигнал
 * 
 * Количество "приемников" сигнала неограничено, поэтому такой код:
 * LMS.Connector.connect(signalObject, 'названиеСигнала', window, 'alert');
 * LMS.Connector.connect(signalObject, 'названиеСигнала', slotObject, 'названиеСлота');
 * Сначала покажет alert, а затем выполнит метод slotObject.названиеСлота()
 * 
 * Таким образом упрощенно технологию сигнал-слот можно представить так:
 * 1. Некие объекты испускают сингалы
 * 2. Другие (или те же) объекты реализуют определенные действия
 * 3. Вышестоящий объект организует их взаимодействие
 */
 
LMS.Signalable = Class.create({
/*    connections: null,
    initialize: function()
    {
        this.connections = {};
    },
    connect: function(signalName, slotObject, slotName)
    {
        if (!this.connections[signalName]) {
            this.connections[signalName] = [];
        }
        this.connections[signalName].push([slotObject, slotName]);
    },*/
    emit: function()
    {
        var args = Array.prototype.slice.call(arguments);  
        var signalName = args.shift();
        var connections = LMS.Connector.getConnections(this, signalName);
        for (var i=0; i<connections.length; i++) {
            var slotObject = connections[i][0];
            var slotName = connections[i][1];
            slotObject[slotName].apply(slotObject, args);
        }
        return this;
    },
    connect: function(signalName, slotObject, slotName) 
    {
        LMS.Connector.connect(this, signalName, slotObject, slotName);
        return this;
    }
});

