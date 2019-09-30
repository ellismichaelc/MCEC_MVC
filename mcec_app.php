<?php

class mcec_app extends mcec {

    protected $_debug = false;
    protected $_args = false;

    public function __construct() {

        $this->_classname = isset($this->_classname) ? $this->_classname : get_called_class();
        $this->_args = func_get_args();

        if(empty($this->_name)) $this->_name = $this->_classname;

        parent::__construct($this);

        // app start.. basically
        $this->start();
    }



}