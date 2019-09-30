<?php

class mcec_class extends mcec {

    public function __construct($app = false) {
        if(!isset($this->_args)) $this->_args = func_get_args();

        if(!$app instanceof mcec_app) {
            $app = false;
        }

        parent::__construct($app);
    }

}