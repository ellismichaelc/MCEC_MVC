<?php

class mcec_gmail extends mcec_class {

    protected $imap = false;
    protected $_config = array();
    protected $lastError = "";

    public function __construct($user, $pass) {
        $this->_config = array('host' => "imap.gmail.com:993",
            'user' => $user,
            'pass' => $pass,
            'opts' => "/imap/ssl/novalidate-cert",
            'inbox' => "INBOX");

        parent::__construct();
    }

    public function connect() {
        //$user = $this->_config['user'];
        //$user = str_replace("@", "-", $this->_config['user']);
        //$user = str_replace(".", "_", $user);

        $mbstr = '{' . $this->_config['host'] . $this->_config['opts'] . '}' . $this->_config['inbox'];

        try {
            $this->imap = @imap_open($mbstr, $this->_config['user'], $this->_config['pass']);
        } catch(Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }

        if(!$this->imap) return false;

        return true;

    }

    public function getLastError() {
        return imap_last_error() . $this->lastError;
    }


}