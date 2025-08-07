<?php

/*
 * ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * <desenvolvimento@bluepex.com>, 2015
 * ====================================================================
 */


require_once('util.inc');
require_once('cg2_util.inc');

class DatabaseBackend
{
    var $conn = NULL;
    var $temp_tables = array();

    function Connect($host = '', $port = '', $user = '', $password = '', $dbname = '') {}
    function Query($query_str) {}
    function MultiQuery($query_str) {}
    function FetchRow($query_res) {}
    function FetchArray($query_res) {}
    function FetchAssoc($query_res) {}
    function FreeRes($query_res) {}
    function GetErrorString() {}

    /* For compatibility reasons */
    function ConcatString($str1, $str2) {}

    function CreateTemporaryTable($name, $select, $from, $where)
    {
        $cmd = '';

        /* If the user attempts to create the same temp table twice, delete the
         * previous one. MySQL is bitchy about this. */
        if (in_array($name, $this->temp_tables))
            $cmd .= "DROP TABLE $name; ";
        else
            $this->temp_tables[] = $name;

        return $cmd;
    }
}

class PgsqlBackend extends DatabaseBackend
{
    function PgsqlBackend()
    {
        if (!extension_loaded('pgsql')) {
            log_error("pgsql module not loaded");
            throw Exception('pgsql module not loaded');
        }
    }

    function Connect($host = '', $port = '', $user = '', $password = '', $dbname = '')
    {
        $args = array();
        $options = array('host', 'port', 'user', 'password', 'dbname');
        foreach ($options as $option) {
            if (!empty($$option))
                $args[] = $option . '=' . $$option;
        }
        $conn_str = implode(' ', $args);

        $conn = @pg_pconnect($conn_str);
        if (!$conn)
            return false;
        $this->conn = $conn;
        return true;
    }

    function Query($query_str)
    {
        if ($this->conn == NULL)
            return false;

        return @pg_query($this->conn, $query_str);
    }

    function FetchRow($res)
    {
        if ($res == NULL)
            return false;

        return @pg_fetch_row($res);
    }

    function FetchArray($res)
    {
        if ($res == NULL)
            return array();

        return @pg_fetch_array($res);
    }

    function FetchAssoc($res)
    {
        if ($res == NULL)
            return array();

        return @pg_fetch_assoc($res);
    }

    function FreeRes($res)
    {
        @pg_free_result($res);
    }

    function GetErrorString()
    {
        if ($this->conn == NULL)
            return false;

        return @pg_last_error($this->conn);
    }

    function ConcatString($str1, $str2)
    {
        return "$str1 || $str2";
    }

    function CreateTemporaryTable($name, $select, $from, $where)
    {
        $cmd = parent :: CreateTemporaryTable($name, $select, $from, $where);
        return "$cmd $select INTO TEMPORARY TABLE $name $from $where";
    }
}

class MysqlBackend extends DatabaseBackend
{
    public $conn;

    function MysqlBackend()
    {
        if (!extension_loaded('mysqli')) {
            log_error("mysqli module not loaded");
            print("mysqli module not loaded");
            #throw Exception('mysqli module not loaded');
        }
    }

    function Connect($host = '', $port = '', $user = '', $password = '', $dbname = '')
    {
        $this->conn = @mysqli_connect($host, $user, $password, $dbname, $port);
        if (!$this->conn) {
            log_error(sprintf(gettext("Could not connect to database! - %s"), mysqli_connect_error()));
            return;
        }
        return true;
    }

    function Query($query_str)
    {
        if ($this->conn == NULL)
            return false;

        /* mysqli_query doesn't support multiple statements */
        $queries = preg_split("/;+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $query_str);
        foreach ($queries as $statement) {
            $statement = trim($statement);
            if (empty($statement))
                continue;

            $res = @mysqli_query($this->conn, $statement);
            if ($res === false)
                break;
        }

        return $res;
    }

    public function MultiQuery($query_str) {
        if ($this->conn == NULL)
            return false;

        if (mysqli_multi_query($this->conn, $query_str)) {
            do {
                $result = mysqli_store_result($this->conn);
                if ($result) {
                    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                        $res[] = $row;
                    }
                    mysqli_free_result($result);
                }
            } while (mysqli_more_results($this->conn) && mysqli_next_result($this->conn));
        }

        $err = mysqli_error($this->conn);
        return empty($err);
    }

    function FetchRow($res)
    {
        if ($res == false)
            return false;

        return @mysqli_fetch_row($res);
    }

    function FetchArray($res)
    {
        if ($res == false)
            return false;

        return @mysqli_fetch_array($res);
    }

    function FetchAssoc($res)
    {
        if ($res == false)
            return false;

        return @mysqli_fetch_assoc($res);
    }

    function FreeRes($res)
    {
        @mysqli_free_result($res);
    }

    function GetErrorString()
    {
        if ($this->conn == NULL)
            return false;

        return @mysqli_error($this->conn);
    }

    function ConcatString($str1, $str2)
    {
        return "CONCAT($str1, $str2)";
    }

    function CreateTemporaryTable($name, $select, $from, $where)
    {
        $cmd = parent :: CreateTemporaryTable($name, $select, $from, $where);
        return "$cmd CREATE TEMPORARY TABLE $name AS $select $from $where";
    }
}

class NetfilterDatabase
{
    var $backend = NULL;

    function NetfilterDatabase()
    {
        $conf = get_element_config('nf_reports_settings');

        $type = $conf['reports_type'];
        if (!isset($type)) $type = 'mysql';
        $types = array(
            'mysql' => 'MysqlBackend',
            'pgsql' => 'PgsqlBackend',
        );
        $class = $types[$type];

        if ($conf['remote_reports'] == 'on') {
            $fields = array('ip', 'port', 'user', 'password', 'db');
            foreach ($fields as $field)
                $$field = $conf["reports_$field"];
        } else {
            $ip = null;
            $port = null;
            $user = 'webfilter';
            $password = 'webfilter';
            $db = 'webfilter';
        }

        $backend = new $class();

        if(!is_process_running('mysqld'))
            exec("/usr/local/etc/rc.d/mysql-server start");

        $res = $backend->Connect($ip, $port, $user, $password, $db);

        if ($res) {
            $this->backend =& $backend;
        }
        else {
            ob_end_clean();
            log_error("ERROR: Unable to connect to the database");
            $errstr = $backend->GetErrorString();
            if (is_string($errstr) && !is_empty($errstr))
                log_error($errstr);
        }
    }

    function Query($query)
    {
        return $this->backend->Query($query);
    }

    function FetchRow($res)
    {
        return $this->backend->FetchRow($res);
    }

    function FetchArray($res)
    {
        return $this->backend->FetchArray($res);
    }

    function FetchAssoc($res)
    {
        return $this->backend->FetchAssoc($res);
    }

    function FreeRes($res)
    {
        $this->backend->FreeRes($res);
    }

	function AffectedRows()
	{
		return mysqli_affected_rows($this->conn);
	}

    function GetErrorString()
    {
        return $this->backend->GetErrorString();
    }

    function ConcatString($str1, $str2)
    {
        return $this->backend->ConcatString($str1, $str2);
    }

    function CreateTemporaryTable($name, $select, $from, $where)
    {
        return $this->backend->CreateTemporaryTable($name, $select, $from, $where);
    }
}

function NetfilterQuery($query)
{
    static $db = NULL;

    if ($db == NULL)
        $db = new NetfilterDatabase();

    return $db->Query($query);
}
?>
