<?php

class mcec_db_table extends mcec_class {

    protected $_name = "";

    private $_where  = "";
    private $_sql    = "";


    public function __construct($table_name, $where = false) {
        $this->_name  = $table_name;
        $this->_where = $where;

        parent::__construct();
    }

    public function init() {
        $where = "";
        if(!empty($this->_where)) {

            $where = "WHERE ";

            if(is_array($this->_where)) {

                $filters = "";

                foreach($this->_where as $filter) {
                    $filters .= "AND ({$filter})";
                }

                $filters = substr($filters, 4);

                $where .= $filters;

            } else {
                $where .= '(' . $this->_where . ')';
            }

        }

        $this->_sql = "SELECT * FROM `{$this->_name}` {$where}";
        $this->_result = $this->_db->query($this->_sql);
    }

    public function getRows() {
        $rows = [];

        while($row = mysqli_fetch_row($this->_result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getRowsAssoc() {
        $rows = [];

        while($row = mysqli_fetch_assoc($this->_result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function toArray() {
        $rows = array();

        while($row = $this->getRows()) {
            $rows[ $row[0] ] = $row;
        }

        return $rows;
    }

    public function __toString() {
        echo "SQL: " . $this->getSQL() . "\n\n";

        $rows = array();

        while($row = $this->getRows()) {
            $rows[ $row[0] ] = $row;
        }

        return print_r($rows, true);
    }

    public function getSQL() {
        return $this->_sql;
    }

}
