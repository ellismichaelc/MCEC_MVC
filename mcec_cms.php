<?php

/*
 * NGINX CONFIG FOR CMS
 * Serve existing files, redirect all other requests here
 *
  location / {
    try_files $uri $uri/ @catchall;
  }

  location @catchall {
    rewrite ^/(.*)$ /index.php?__catchall__=/$1 last;
  }
 */

class mcec_cms extends mcec_app {

    public $nonauth_module = 'login';

    public $default_module = 'default';
    public $default_action = 'default';

    public $error_module = 'error';
    public $error_action = 'default';

    public $requested_module = '';
    public $requested_action = '';
    public $requested_view   = '';

    public $module = '';
    public $action = '';

    public $loaded_modules = [];

    public function __construct() {

        if(!isset($_SESSION)) session_start();

        // get and then unset our catch all variable from nginx
        $url = $this->getParam('__catchall__');
        unset($_GET['__catchall__']);

        // check for hash, which shouldn't exist, but just in case
        $has_hash = strpos($url, '#');
        $hash = $has_hash ? substr($url, $has_hash) : '';

        if($has_hash) {
            $url = substr($url, 0, $has_hash);
        }

        // check for and trim slash
        if($url[0] == "/") $url = substr($url, 1);

        // here we do some stuff.
        // we are separating the url, such as /module/action/var1/val1/var2/val2
        // would end up going to module_mod, action_act, and _GET[var1] = val1, _GET[var2] = val2, etc.

        $vars  = [];
        $count = 0;

        $parts = explode("/", $url);

        foreach($parts as $key => $part) {
            if(empty($part)) continue;

            $count++;

            if($count == 1) {
                $this->requested_module = $part;
            }

            if($count == 2) {
                $this->requested_action = $part;
            }

            if($count == 3) {
                $this->requested_view = $part;
            }

            if(count($parts) % 2 == 1) {
                // odd

                if($count > 3) {
                    if ($count % 2 == 1) {
                        $vars[$next_val] = $part;
                    } else {
                        $next_val = $part;
                    }
                }
            } else {
                // even

                if($count > 2) {
                    if ($count % 2 == 0) {
                        $vars[$next_val] = $part;
                    } else {
                        $next_val = $part;
                    }
                }
            }

        }

        $_GET = array_merge($vars, $_GET);

        parent::__construct();
    }

    public function isAuthorized() {
        $auth     = $this->cookieVar('user_id');
        $password = $this->cookieVar('user_token');

        $user_id = $auth;
        $user_info = false;

        if(!empty($user_id)) {
            $this->_db->getResultsCallback("SELECT * FROM `users` WHERE `id`='{$user_id}' AND `password`='{$password}' LIMIT 1;",
                function ($row) use (&$user_info) {

                    // TODO: set last login, etc

                    $user_info = $row;
                });
        }

        $this->user_info = $user_info;

        if(!empty($auth)) {
            return true;
        }

        return false;
    }

    public function deAuthorize() {
        $this->unsetCookieVar('user_id');
        $this->unsetCookieVar('user_info');

        return true;
    }

    public function loadModule($module) {
        $module_name = 'module_' . $module;

        if(class_exists($module_name)) {
            $this->module_name = $module;

            $this->_module = $this->loadCachedModule($module_name);

            return $this->_module;
        } else return false;
    }

    // make a new instance of, or load existing stored instance, of module
    public function loadCachedModule($module_name) {

        if(!isset($this->loaded_modules[ $module_name ])) {
            $this->module = $module_name;
            $this->_module = new $module_name($this);

            $this->loaded_modules[ $module_name ] = $this->_module;
        }

        return $this->loaded_modules[ $module_name ];

    }

    function evalVariables($content) {
        return preg_replace_callback(
            "/\[%([\w_\.]+)%\]/",
            function($m) {
                $var_name = $m[1];
                $var_val = "";

                if (isset($this->_module->$var_name)) {
                    $var_val = $this->_module->$var_name;
                } elseif(isset($this->$var_name)) {
                    $var_val  = $this->$var_name;
                } else {
                    $var_val = "Cannot find variable: {$var_name}";
                    //throw new Exception("Cannot find variable: {$var_name}");
                }

                if(is_string($var_val)) {
                    return $var_val;
                }

                if(is_array($var_val)) {
                    // todo: check for multi dimensional, nicer print, etc

                    return print_r($var_val, true);
                }

                if(is_callable($var_val)) {
                    return $var_val($this);
                }

                return "";
            },
            $content);
    }

    public function cookieVar($name, $value = false) {
        if(!$value) {
            if(!isset($_SESSION[ $name ])) return false;
            return $_SESSION[ $name ];
        }

        if(!isset($_SESSION)) session_start();
        $_SESSION[ $name ] = $value;
    }

    public function unsetCookieVar($name) {
        $_SESSION[ $name ] = null;

        return true;
    }

    public function forwardTo($module, $include_request = true) {
        if($include_request) $ref = '?r=' . urlencode($_SERVER['REQUEST_URI']);
        else $ref = '';

        if($ref == "?r=%2F") $ref = '';

        header("location: /{$module}{$ref}");
    }

    public function setAction($action_name) {
        $this->action_name = $action_name;
        $this->action = 'action_' . $action_name;
    }

    // hi, this is where it all starts.
    public function init() {

        // complex sets of logic ahead
        // you wouldn't understand.

        if(empty($this->requested_action)) {
            // set action to default action if empty
            $this->action = $this->default_action;
        } else {
            $this->action = $this->requested_action;
        }

        if(empty($this->requested_module)) {
            // load default module
            $mod = $this->loadModule($this->default_module);
        } else {
            // clean up module and action
            $mod = $this->loadModule($this->requested_module);

            // check if action exists within module
            if($mod && empty($this->action)) {
                if(method_exists($mod, $this->requested_action)) {
                    $this->action = $this->requested_action;
                } else {
                    $mod = false;
                }
            }

            if(!$mod) {
                // load the error module
                $mod = $this->loadModule($this->error_module);

                // set action to error action
                $this->action = $this->error_action;
            }
        }

        if(!$mod) {
            return;
        }

        if(isset($mod->use_auth)) {
            if ($mod->use_auth) {
                $authorized = $this->isAuthorized();

                if(!$authorized && $mod->require_auth) $this->forwardTo($this->nonauth_module);
            }
        }

        // store action for later in case we are hungry
        $this->setAction($this->action);

        // TODO:
        // check for authorization
        // incorporate some type of router functionality

        // load view, if it exists, if not, output stdout maybe?

        $this->_module->start();
    }
}