<?php

class mcec_scraper extends mcec_class {

    // Curl Handler
    private $curl        = null;
    private $curl_config = null;

    // These will hold error information
    public $error_found  = false;
    public $error_string = "";
    public $persist = true;

    // Paths to the data we're scraping!
    private $url_base    = "";
    private $url_path    = "";
    private $url_cookie  = '';
    private $url_agent   = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36';

    // Assumes POST, params each with %s
    private $url_params  = "";
    protected $post_params = []; // will auto switch to POST if set
    protected $login_post_params = []; // will auto switch to POST if set

    // Extra headers
    public $extra_headers = [];

    public function __construct() {
        $this->__init();
    }

    public function __init() {
        $this->_args = func_get_args();
        parent::__construct();

        //$this->url_cookie  = tempnam(sys_get_temp_dir(), '');
        $this->url_cookie = dirname(__FILE__) . '/cookie.txt';

        if(!$this->url_cookie) die("Couldn't create temporary file");

        $this->curl_config = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIESESSION  => true,
            CURLOPT_COOKIEJAR	   => $this->url_cookie,
            CURLOPT_COOKIEFILE     => $this->url_cookie,
            CURLOPT_USERAGENT      => $this->url_agent,
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_SSL_VERIFYPEER => false,
        );
    }

    public function _login() {
        $data = $this->getURL($this->login_url, $this->login_post_params);

        if(strstr($data, $this->error_string)) {
            return $this->error("Found error string in data, couldnt login");
        }

        return $data;
    }

    public function _scrape() {
        $data = $this->getURL($this->url, $this->post_params);

        return $data;
    }

    public function scrape() {
        return $this->start();
    }

    public function initCurl($url = false, $post_params = false) {
        if(!$this->curl_config) $this->__init();

        if(!isset($this->curl) && $this->persist) $this->curl = curl_init();

        // do config stuff
        $config = [];
        if(!empty($post_params)) {
            $config = array(
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($post_params)
            );
        }

        if($url) {
            $this->parseURL($url);
            $config[ CURLOPT_URL ] = $url;
        }

        // set and combine config
        curl_setopt_array($this->curl, $config);
        curl_setopt_array($this->curl, $this->curl_config);

        // if we have more headers to set
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->extra_headers);
    }

    public function closeCurl() {
        if(!$this->persist) curl_close($this->curl);
    }

    public function getResult() {
        return curl_exec($this->curl);
    }

    public function curlError() {
        return curl_error($this->curl);
    }

    public function getURL($url, $post_params = false) {

        $this->initCurl($url, $post_params);

        if(!$result = $this->getResult()) {
            return $this->error("Curl Error: " . $this->curlError());
        }

        $this->closeCurl();


        if(empty($result)) {
            return $this->error("Got an empty result");
        }

        return $result;
    }

    public function parseURL($url) {
        $parse = parse_url($url);

        $this->url_scheme = $parse['scheme'];
        $this->url_base = $parse['host'];
        $this->url_path = $parse['path'];
        $this->url_params = (isset($parse['query']) ? $parse['query'] : '') . (isset($parse['fragment']) ? '#' . $parse['fragment'] : '');
    }

    public function login() {

        $url    = $this->url_base . $this->url_login;
        $config = array(
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => sprintf($this->url_params, $this->login_email, $this->login_pass)
        );

        $result = $this->getURL($url, $config);

        preg_match("/error_string = \"(.*?)\";/", $result, $matches);

        if(isset($matches[1]) && !empty($matches[1])) {
            $this->error_found  = true;
            $this->error_string = trim($matches[1]);

            return false;
        }

        $this->login_done = true;

        return true;

    }

    private function getURL2($url = "", $config = array()) {
        $this->curl = curl_init();

        $config[ CURLOPT_URL ] = $url;

        curl_setopt_array($this->curl, $config);
        curl_setopt_array($this->curl, $this->curl_config);

        if(!$result = curl_exec($this->curl)) {
            $this->error_found  = true;
            $this->error_string = curl_error($this->curl);

            return false;
        }

        curl_close($this->curl);

        return $result;

    }
}