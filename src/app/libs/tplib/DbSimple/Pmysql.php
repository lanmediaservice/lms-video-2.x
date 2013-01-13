<?php

class DbSimple_Pmysql extends DbSimple_Mysql
{
    function DbSimple_Pmysql($dsn)
    {
        $p = DbSimple_Generic::parseDSN($dsn);
        if (!is_callable('mysql_pconnect')) {
            return $this->_setLastError("-1", "MySQL extension is not loaded", "mysql_pconnect");
        }
        $ok = $this->link = @mysql_pconnect(
            $p['host'] . (empty($p['port'])? "" : ":".$p['port']),
            $p['user'],
            $p['pass'],
            true
        );
        $this->_resetLastError();
        if (!$ok) return $this->_setDbError('mysql_pconnect()');
        $ok = @mysql_select_db(preg_replace('{^/}s', '', $p['path']), $this->link);
        if (!$ok) return $this->_setDbError('mysql_select_db()');
    }
}