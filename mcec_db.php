<?php

class mcec_db extends mysqli {

    private $_cfg;
    private $_db;
    private $_isConnected;

    public function __construct($config) {

        $this->_cfg = $config;

        $this->_db  = @parent::__construct($this->_cfg['db_host'],
                                                $this->_cfg['db_user'],
                                                $this->_cfg['db_pass'],
                                                $this->_cfg['db_name']);

        if (mysqli_connect_errno()) {
            // TODO: Convert this or log or something ..

            $this->_lastError    = "Failed to connect to database: " . mysqli_connect_error();
            $this->_isConnected = false;

            return false;
        }

        $this->_isConnected = true;

        mysqli_set_charset($this, 'utf8');

    }

    public function isConnected() {
        return $this->_isConnected;
    }

    public function getTable($table_name, $where = null) {
        return new mcec_db_table($table_name, $where);
    }

    public function getResultsCallback($query, $callback) {
        $result = $this->query($query);
        if(!$result) return false;
        while($row = $result->fetch_array()) {
            $callback($row);
        }
        return true;
    }

    public function escape($str) {
        return $this->real_escape_string($str);
    }

    public function getError() {
        return mysqli_error($this);
    }
}
