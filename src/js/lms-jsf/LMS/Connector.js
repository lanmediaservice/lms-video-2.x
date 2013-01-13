/** 
 * LMS JavaScript Framework
 * 
 * @version $Id: Connector.js 48 2009-07-15 13:58:55Z macondos $
 * @copyright 2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * 
 */
JSAN.require('LMS');

/**
 * Помощник для соедиения сигналов и слотов
 * Подробнее о технологии сигнал-слот см. в LMS.Signalable
 */

if (!LMS.Connector) {
    LMS.Connector = {};
}

LMS.Connector.connections = {};

LMS.Connector.objects = [];

LMS.Connector.getObjectIndex = function (object)
{
    var index = LMS.Connector.objects.indexOf(object);
    if (index==-1) {
        LMS.Connector.objects.push(object);
        index = LMS.Connector.objects.indexOf(object);
    }
    return index;
}

LMS.Connector.connect = function ()
{
    if (arguments.length==4) {
        var signalObject = arguments[0];
        var signalName = arguments[1];
        var slotObject = arguments[2];
        var slotName = arguments[3];
    }
    if (arguments.length==3) {
        var signalObject = null;
        var signalName = arguments[0];
        var slotObject = arguments[1];
        var slotName = arguments[2];
    }
    
    if (!(signalObject instanceof Object)) {
        signalObject = null;
    }
    
    var signalObjectIndex = LMS.Connector.getObjectIndex(signalObject);
    

    if (!LMS.Connector.connections[signalObjectIndex]) {
        LMS.Connector.connections[signalObjectIndex] = {};
    }
    if (!LMS.Connector.connections[signalObjectIndex][signalName]) {
        LMS.Connector.connections[signalObjectIndex][signalName] = [];
    }
    LMS.Connector.connections[signalObjectIndex][signalName].push(LMS.Connector.pack(slotObject, slotName));
    LMS.Connector.connections[signalObjectIndex][signalName] = LMS.Connector.connections[signalObjectIndex][signalName].uniq();
}

LMS.Connector.disconnect = function ()
{
    if (arguments.length==4) {
        var signalObject = arguments[0];
        var signalName = arguments[1];
        var slotObject = arguments[2];
        var slotName = arguments[3];
    }
    if (arguments.length==3) {
        var signalObject = null;
        var signalName = arguments[0];
        var slotObject = arguments[1];
        var slotName = arguments[2];
    }
    
    if (!(signalObject instanceof Object)) {
        signalObject = null;
    }
    
    var signalObjectIndex = LMS.Connector.getObjectIndex(signalObject);
    

    if (!LMS.Connector.connections[signalObjectIndex]) {
        LMS.Connector.connections[signalObjectIndex] = {};
    }
    if (!LMS.Connector.connections[signalObjectIndex][signalName]) {
        LMS.Connector.connections[signalObjectIndex][signalName] = [];
    }
    var slot = LMS.Connector.pack(slotObject, slotName);
    LMS.Connector.connections[signalObjectIndex][signalName] = LMS.Connector.connections[signalObjectIndex][signalName].without(slot);
}

LMS.Connector.disconnectAll = function(object)
{
    var objectIndex = LMS.Connector.getObjectIndex(object);
    if (LMS.Connector.connections[objectIndex]) {
        delete LMS.Connector.connections[objectIndex];
    }
    for (var signalObjectIndex in LMS.Connector.connections) {
        for (var signalName in LMS.Connector.connections[signalObjectIndex]) {
            for (var i=LMS.Connector.connections[signalObjectIndex][signalName].length-1; i>=0; i--) {
                var connection = LMS.Connector.unpack(LMS.Connector.connections[signalObjectIndex][signalName][i]);
                if (connection[0]==object) {
                    LMS.Connector.connections[signalObjectIndex][signalName].splice(i, 1);
                }
            }
        }
    }
    
}

LMS.Connector.getConnections = function (signalObject, signalName)
{
    var connections = [];
    if (signalObject !== null) {
        var signalObjectIndex = LMS.Connector.getObjectIndex(signalObject);
        LMS.Connector.fillConnections(connections, signalObjectIndex, signalName);
    }
    var signalObjectIndex = LMS.Connector.getObjectIndex(null);
    LMS.Connector.fillConnections(connections, signalObjectIndex, signalName);
    return connections;
}

LMS.Connector.fillConnections = function (connections, signalObjectIndex, signalName) {
    if (LMS.Connector.connections[signalObjectIndex] 
        && LMS.Connector.connections[signalObjectIndex][signalName]
    ) {
        for (var i=0; i<LMS.Connector.connections[signalObjectIndex][signalName].length; i++) {
            connections.push(LMS.Connector.unpack(LMS.Connector.connections[signalObjectIndex][signalName][i]));
        }
    }
}

LMS.Connector.pack = function (slotObject, slotName)
{
    var slotObjectIndex = LMS.Connector.getObjectIndex(slotObject);
    var packed = slotObjectIndex + ':' + slotName;
    return packed;
}

LMS.Connector.unpack = function (packed)
{
    var unpacked = packed.split(':', 2);
    unpacked[0] = LMS.Connector.objects[unpacked[0]];
    return unpacked;
}
