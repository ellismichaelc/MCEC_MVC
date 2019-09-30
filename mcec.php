<?php

class mcec {

    private $_cfg = array();
    private $_app = null;
    private $_errors = [];

    protected $_debug = false;
    protected $_db  = null;
    protected static $me = null;
    protected static $app = null;

    // TODO: Eventually put this in DB? mcec Table?
    protected $_prefix = "[mcec]";

    function __construct($app = false) {

        if(!$app) $this->_app = mcec::getApp();
        else {
            mcec::setApp($app);
            $this->_app = mcec::getApp();
        }

        if(!isset($this->_app->_classname)) {
            throw new Exception("Internal error (_classname missing)");
        }

        $this->_cfg['db_host'] = "localhost";
        $this->_cfg['db_name'] = $this->_app->_classname;
        $this->_cfg['db_user'] = "mcecreations";
        $this->_cfg['db_pass'] = "";

        $this->_db  = new mcec_db($this->_cfg);

        mcec::setMe($this);

        if(is_array($this->getArgs())) {
            if(method_exists($this, 'init')) call_user_func_array(array($this, 'init'), $this->getArgs());
           } else {
            if(method_exists($this, 'init')) call_user_func(array($this, 'init'), $this->getArgs());
        }
    }

    public function __call($name, $arguments) {
        if(method_exists($this, $name)) {
            return call_user_func_array([$this, $name], $arguments);
        }

        if(method_exists($this->_app, $name)) {
            return call_user_func_array([$this->_app, $name], $arguments);
        }

        // todo: exception son
        return null;
    }

    public function __get($name) {
        if(isset($this->$name)) return $this->$name;
        if(isset($this->_app->$name)) return $this->_app->$name;

        return null;
    }

    public function getArgs() {
        return $this->_args;
    }

    public function getAppArgs() {
        return $this->_app->_args;
    }

    public function getParam($param, $get_or_post = 'GET') {
        if(strtoupper($get_or_post) == "GET") {
            $arr = $_GET;
        } elseif(strtoupper($get_or_post) == "POST") {
            $arr = $_POST;
        }

        if(isset($arr[ $param ])) return $arr[ $param ];
        else  {

            if($get_or_post == "GET" && $_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST[ $param ])) {
                return $_POST[ $param ];
            }

            return false;
        }
    }

    public function getPostParam($param) {
        return $this->getParam($param, 'POST');
    }

    public function start() {

    }

    public function getMyVersion() {
        return $this->_ver;
    }

    public function getAppVersion() {
        return $this->_app->_ver;
    }

    public static function getMe() {
        return self::$me;
    }

    public static function setMe($me) {
        self::$me = $me;
    }

    public static function getApp() {
        return self::$app;
    }

    public static function setApp($app) {
        self::$app = $app;
    }

    public function log($msg, $level=1) {
        $nice_msg = $this->_prefix . "[" . strtoupper(self::getApp()->_classname) . "][" . date('r') . "] - " . $msg;

        if(self::getApp()->_debug) {
            echo $nice_msg . "\n";
        }

        // TODO: Actually log? Logentries API? Idk? Hi?
    }

    public function debug($on_off = true) {
        self::getApp()->_debug = ($on_off == true);
    }

    public function error($error) {
        if(empty($error)) $error = "Empty/Unknown Error";
        $this->_errors[] = $error;
        return false; // so you can return error()
    }

    public function getErrors() {
        return $this->_errors;
    }

    public function getLastError() {
        return end($this->_errors);
    }

    public function dump($die = false) {
        var_dump($this->getErrors());

        if($die) exit;
    }

    public static function isLocal() {
        if(!isset($_SESSION)) {
            session_start();
        }

        $isLocal = (function() {
            if (isset($_SESSION['IS_LOCAL']) && $_SESSION['IS_LOCAL'] == true) return true;
            if (isset($_SERVER['REMOTE_ADDR']) && substr($_SERVER['REMOTE_ADDR'], 0, 7) == "192.168") return true;
            if (isset($_GET['remote'])) return true;
        })();

        if($isLocal) {
            $_SESSION['IS_LOCAL'] = true;

            return true;
        }
    }

}