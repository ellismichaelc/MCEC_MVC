<?php

class mcec_cms_module extends mcec_class {

    public $use_auth = false;
    public $require_auth = false;

    public $default_view_path = ''; // generated automatically
    public $custom_view_path = '';

    public $default_template_path = ''; // generated automatically
    public $default_template_name = 'default';
    public $custom_template_path = '';

    public $display_stdout = false;
    public $display_action_return = false;

    public $use_template = false;
    public $use_view = false;

    public $template_name = '';
    public $view_name = '';

    public $view_folder = 'views';
    public $view_ext = '.html';


    function __construct($app) {
        $this->module_init();

        parent::__construct($app);
    }

    function module_init() {

    }

    public function setView($name) {
        $this->custom_view_path = $name;
    }

    public function setTemplate($name) {
        $this->custom_template_path = $name;
    }


    public function useTemplate($template = 'default') {
        if($template == false) {
            $this->use_template = false;
            $this->template_name = '';

            return;
        }

        $this->setTemplate($template);
    }

    public function useView($name = '', $use_template = '') {
        $this->use_view = true;

        if($use_template !== false) {
            $this->use_template = true;

            if(!empty($use_template)) {
                $this->setTemplate($use_template);
            }
        }

        if(!empty($name) && $name !== true) {
            $this->setView($name);
        }
    }

    public function useAuth() {
        $this->use_auth = true;
    }

    public function requireAuth() {
        $this->require_auth = true;

        $this->useAuth();
    }

    public function getView($name) {
        $path = "views/{$name}.html";

        if(!file_exists($path)) {
            return "Cant find view: {$path}";
        }

        $view_contents = file_get_contents($path);
        $view_contents = $this->evalVariables($view_contents);

        return $view_contents;
    }

    public function getViewContents() {
        // if we are using a view
        if($this->use_view) {
            // alright now pass that output through our view and template and whatnot
            $this->default_view_path = $this->view_folder . "/view_" . $this->module_name . "_" . $this->action_name . $this->view_ext;

            if($this->action_name == $this->default_action && !file_exists($this->default_view_path)) {
                $this->default_view_path = $this->view_folder . "/view_" . $this->module_name . $this->view_ext;
            }

            if (!empty($this->custom_view_path)) {
                $custom_view_path = $this->view_folder . "/view_" . $this->custom_view_path . $this->view_ext;
                $this->view_path = $custom_view_path;
            } else {

                $this->view_path = $this->default_view_path;
            }

            if (file_exists($this->view_path)) {
                $contents = file_get_contents($this->view_path);

                $this->content = $this->evalVariables($contents);
            } else {
                //throw new Exception("Cant find view: " . $this->view_path);
                return false;
            }
        } else {
            $this->content = $this->all_output;
        }

        return true;
    }

    public function start() {

        // catch all output as stdout
        ob_start();

        // check for methods
        $post_action_name = $this->action . '_POST';

        $this->action_exists    = method_exists($this, $this->action);
        $this->preaction_exists = method_exists($this, '__preaction'); //
        $this->POST_action_exists = method_exists($this, $post_action_name);

        if($this->POST_action_exists && strtolower($_SERVER['REQUEST_METHOD']) == "post") {
            $this->action = $post_action_name;
            $this->action_name = $this->action_name . '_POST';
        }

        // call pre-action, if it exists
        if($this->preaction_exists) $this->preaction_return = $this->__preaction();

        // call action
        $func = $this->action;
        $this->action_return = $this->$func();

        // get all stdout and end buffer
        $this->stdout = ob_get_contents();
        ob_end_clean();

        // lets do some 'smart' detection of what to display
        if(!empty($this->stdout)) {
            $this->display_stdout = true;
        }

        // todo: make this debug only
        if(!empty($this->action_return)) {
            $this->display_action_return = true;
        }

        $this->all_output = "";
        $this->json = "";

        if($this->display_stdout) {
            $this->all_output .= $this->stdout;
        }

        if(is_array($this->action_return)) {
            $json = json_encode($this->action_return);
            $this->json = $json;
        }

        if($this->display_action_return) {
            if(is_array($this->action_return)) {
                $this->all_output .= $json;
            } else {
                $this->all_output .= $this->action_return;
            }
        }

        if(!$this->getViewContents()) {
            // view doesnt exist

            $this->forwardTo('default');
        }

        if($this->use_template) {
            $this->default_template_path = $this->view_folder . "/template_" . $this->default_template_name . $this->view_ext;

            if (!empty($this->custom_template_path)) {
                $custom_template_path = $this->view_folder . "/template_" . $this->custom_template_path . $this->view_ext;
                $this->template_path = $custom_template_path;
            } else {
                $this->template_path = $this->default_template_path;
            }

            if (file_exists($this->template_path)) {
                $template_contents = file_get_contents($this->template_path);

                $this->template_contents = $this->evalVariables($template_contents);

                echo $this->template_contents;
            } else {
                throw new Exception("Cant find template: " . $this->template_path);
            }
        } else {
            echo $this->content;
        }
    }
}