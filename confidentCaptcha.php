<?php
require_once('wp-plugin.php');
require_once("confidentcaptcha/ConfidentCaptchaClient.php");
require_once("confidentcaptcha/ConfidentCaptchaConfiguration.php");
require_once("confidentcaptcha/ConfidentCaptchaResponses.php");



if (!class_exists('confidentCaptcha')) {
    class confidentCaptcha extends WPPlugin {
        private $saved_error;
        function confidentCaptcha($options_name) {
            $args = func_get_args();
            call_user_func_array(array(&$this, "__construct"), $args);
        }
        function __construct($options_name) {
            parent::__construct($options_name);
            $this->register_default_options();
            $this->require_library();
            $this->register_actions();
            $this->register_filters();
        }
        function register_actions() {

            add_action('init', 'session_start');
            add_action('wp_head', array(&$this, 'register_stylesheets')); 
            add_action('admin_head', array(&$this, 'register_stylesheets'));
            register_activation_hook(WPPlugin::path_to_plugin_directory() . '/wp-confidentCaptcha.php', array(&$this, 'register_default_options')); 
            add_action('admin_init', array(&$this, 'register_settings_group'));
            add_action('wp_enqueue_scripts',array(&$this, 'confidentStyle'));
            if ($this->options['show_in_registration']) {
                if ($this->is_multi_blog())
                    add_action('signup_extra_fields', array(&$this, 'show_confidentCaptcha_in_registration'));
                else
                    add_action('register_form', array(&$this, 'show_confidentCaptcha_in_registration'));
            }
            if($this->options['show_in_lost_password'])
                add_action('lostpassword_form', array(&$this, 'show_confidentCaptcha_in_registration'));
            if($this->options['show_in_login_page'])
                add_action('login_form', array(&$this, 'show_confidentCaptcha_in_registration'));
            if ($this->options['show_in_comments']) {
                add_action('comment_form', array(&$this, 'show_confidentCaptcha_in_comments'));
                add_action('wp_head', array(&$this, 'saved_comment'), 0);
                add_action('preprocess_comment', array(&$this, 'check_comment'), 0);
                add_action('comment_post_redirect', array(&$this, 'relative_redirect'), 0, 2);
            }
            add_filter("plugin_action_links", array(&$this, 'show_settings_link'), 10, 2);
            add_action('admin_menu', array(&$this, 'add_settings_page'));
            add_action('admin_notices', array(&$this, 'missing_keys_notice'));

            //Callback related actions and filters for AJAX verification
            add_filter('query_vars', array(&$this,'callback_rewrite_filter'));
            add_action('parse_request', array(&$this,'callback_rewrite_parse_request'));
        }
        function confidentStyle() {
          wp_register_style('confident-style',plugins_url('confidentCaptcha.css',__FILE__));
          wp_enqueue_style('confident-style');
        }
        function confidentCaptcha_check($errors, $tag = NULL) {
            if (empty($_POST['confidentcaptcha_code']) || $_POST['confidentcaptcha_code'] == '') {
                $errors->add('blank_captcha', $this->options['no_response_error']);
                return $errors;
            }
            $validationData = array (
                'api_key'=>$this->options['api_key'],
                'public_key'=>$this->options['public_key'],
                'library_version'=>'20130514_WordPress_2.5',
                'click_coordinates'=>$_POST['confidentcaptcha_click_coordinates'],
                'code'=>$_POST['confidentcaptcha_code'],
                'confidentCaptchaID'=>$_POST['confidentcaptcha_captcha_id']
            );
            //$response = confidentCaptcha_check_answer($validationData);
            $client = $this->getClient();
            $response = $client->secureServerValidate($_REQUEST);
            if (!$response->wasCaptchaSolved())
               if(!empty($tag)) {
                  $errors['valid'] = false;
                  $errors['reason']['your-message'] = $this->options['incorrect_response_error'];
               } else
                   $errors->add('captcha_wrong', $this->options['incorrect_response_error']);
            return $errors;
        }

        function callback_rewrite_parse_request(&$wp){
            if ( array_key_exists( 'confident_callback', $wp->query_vars ) )
            {
                if(isset($_REQUEST['endpoint'])){
                    $confidentCaptchaClient = new ConfidentCaptchaClient();
                    $return = $confidentCaptchaClient->callback($_REQUEST);
                    header($return[0]);
                    echo $return[1];
                }
            }
        }
        function callback_rewrite_filter($query_vars){
            $query_vars[] = 'confident_callback';
            return $query_vars;
        }
        function register_filters() {
            if ($this->options['show_in_registration']) {
                if ($this->is_multi_blog()) {
                    add_filter('wpmu_validate_user_signup', array(&$this, 'validate_confidentCaptcha_response_wpmu'));
          }
                else  {
                    add_filter('registration_errors', array(&$this, 'validate_confidentCaptcha_response'));
        }
            }
            //add_action('lostpassword_post', array(&$this, 'confidentCaptcha_check_lost_password'));
      if($this->options['show_in_lost_password'])
          add_filter('allow_password_reset', array(&$this, 'confidentCaptcha_check_lost_password'),1);
      if($this->options['show_in_login_page'])
          add_filter('authenticate', array(&$this, 'check_login'),40,3);
        }
        
        function load_textdomain() {
            load_plugin_textdomain('confidentCaptcha', false, 'languages');
        }
        function register_default_options() {
            if ($this->options)
               return;
            $option_defaults = array();
            $old_options = WPPlugin::retrieve_options("confidentCaptcha");
            if ($old_options) {
               $option_defaults['api_key'] = $old_options['api_key'];
               $option_defaults['public_key'] = $old_options['public_key'];
               $option_defaults['show_in_comments'] = $old_options['cc_comments'];
               $option_defaults['show_in_registration'] = $old_options['cc_registration'];
               $option_defaults['bypass_for_registered_users'] = ($old_options['cc_bypass'] == "on") ? 1 : 0;
               $option_defaults['minimum_bypass_level'] = $old_options['cc_bypasslevel'];
               if ($option_defaults['minimum_bypass_level'] == "level_10") {
                  $option_defaults['minimum_bypass_level'] = "activate_plugins";
               }
               $option_defaults['confidentCaptcha_language'] = $old_options['cc_lang'];
               $option_defaults['xhtml_compliance'] = $old_options['cc_xhtml'];
               $option_defaults['comments_tab_index'] = $old_options['cc_tabindex'];
               $option_defaults['registration_tab_index'] = 30;
               $option_defaults['debug_mode'] = 'FALSE';
               $option_defaults['failure_policy'] = 'math';
            }
           
            else {
               $option_defaults['api_key'] = '';
               $option_defaults['public_key'] = '';
               $option_defaults['show_in_comments'] = 1;
               $option_defaults['show_in_registration'] = 1;
         $option_defaults['show_in_lost_password'] = 1;
         $option_defaults['show_in_login_page'] = 0;
               $option_defaults['bypass_for_registered_users'] = 1;
               $option_defaults['minimum_bypass_level'] = 'read';
               $option_defaults['confidentCaptcha_language'] = 'en';
               $option_defaults['xhtml_compliance'] = 0;
               $option_defaults['comments_tab_index'] = 5;
               $option_defaults['registration_tab_index'] = 30;
               $option_defaults['debug_mode'] = 'FALSE';
               $option_defaults['failure_policy'] = 'math';
            }
            WPPlugin::add_options($this->options_name, $option_defaults);
        }
        function require_library() {
            //require_once($this->path_to_plugin_directory() . '/confidentCaptchalib.php');
            require_once($this->path_to_plugin_directory() . '/confidentcaptcha/ConfidentCaptchaConfiguration.php');
            require_once($this->path_to_plugin_directory() . '/confidentcaptcha/ConfidentCaptchaResponses.php');
            require_once($this->path_to_plugin_directory() . '/confidentcaptcha/ConfidentCaptchaClient.php');
        }
        function register_settings_group() {
            register_setting("confidentCaptcha_options_group", 'confidentCaptcha_options', array(&$this, 'validate_options'));
        }
        function register_stylesheets() {
            $path = WPPlugin::url_to_plugin_directory() . '/confidentCaptcha.css';
            echo '<link rel="stylesheet" type="text/css" href="' . $path . '" />';
        }
  function register_js() {
      wp_enqueue_script('jquery');
        }
        function confidentCaptcha_enabled() {
            return ($this->options['show_in_comments'] || $this->options['show_in_registration'] || $this->options['show_in_login_page'] || $this->options['show_in_lost_password'] );
        }
        function keys_missing() {
            return (empty($this->options['public_key']) || empty($this->options['api_key']) || empty($this->options['failure_policy']) || empty($this->options['debug_mode']));
        }
        function create_error_notice($message, $anchor = '') {
            $options_url = admin_url('options-general.php?page=ec-captcha-plugin-wordpress/confidentCaptcha.php') . $anchor;
            $error_message = sprintf(__($message . ' <a href="%s" title="WP-EC Confident CAPTCHA Options">Fix this</a>', 'confidentCaptcha'), $options_url);
            echo '<div class="error"><p><strong>' . $error_message . '</strong></p></div>';
        }
        function missing_keys_notice() {
            if ($this->confidentCaptcha_enabled() && $this->keys_missing()) {
                $this->create_error_notice('You enabled <strong>EC Confident CAPTCHA</strong>, but some of the EC Confident CAPTCHA API information seems to be missing.');
            }
        }
        function validate_dropdown($array, $key, $value) {
            if (in_array($value, $array))
                return $value;
            else
                return $this->options[$key];
        }
        function validate_options($input) {
            $validated['api_key'] = trim($input['api_key']);
            $validated['public_key'] = trim($input['public_key']);
            $validated['show_in_comments'] = (isset($input['show_in_comments']) == 1 ? 1 : 0);
            $validated['show_in_lost_password'] = (isset($input['show_in_lost_password']) == 1 ? 1 : 0);
            $validated['show_in_login_page'] = (isset($input['show_in_login_page']) == 0 ? 0 : 1 );
            $validated['bypass_for_registered_users'] = ($input['bypass_for_registered_users'] == 1 ? 1: 0);
            $capabilities = array ('read', 'edit_posts', 'publish_posts', 'moderate_comments', 'activate_plugins');
            $trueFalse = array('TRUE', 'FALSE');
            $failurePolicy = array('math', 'open', 'closed');
            $validated['minimum_bypass_level'] = $this->validate_dropdown($capabilities, 'minimum_bypass_level', $input['minimum_bypass_level']);
            $validated['comments_tab_index'] = $input['comments_tab_index'] ? $input["comments_tab_index"] : 5;
            $validated['show_in_registration'] = (isset($input['show_in_registration']) == 1 ? 1 : 0);
            $validated['registration_tab_index'] = $input['registration_tab_index'] ? $input["registration_tab_index"] : 30;
            $validated['debug_mode'] = $this->validate_dropdown($trueFalse, 'debug_mode', $input['debug_mode']);
            $validated['failure_policy'] = $this->validate_dropdown($failurePolicy, 'failure_policy', $input['failure_policy']);
            return $validated;
        }
        function show_confidentCaptcha_in_registration($errors) {
            $this->register_stylesheets();
            wp_enqueue_style('confident-style');
        echo '<script src="http://code.jquery.com/jquery-latest.min.js"
        type="text/javascript"></script>';
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
                $use_ssl = true;
            else
                $use_ssl = false;
              if(isset($_GET['rerror'])) {
                 $escaped_error = htmlentities($_GET['rerror'], ENT_QUOTES);
               } else {
                 $escaped_error = "";
               }
           
            if ($this->is_multi_blog()) {
                $error = $errors->get_error_message('captcha');
                echo '<label for="verification">Verification:</label>';
                echo ($error ? '<p class="error">'.$error.'</p>' : '');
                echo $format . $this->get_confidentCaptcha_html($escaped_error, $use_ssl);
            }
            else {
                echo $this->get_confidentCaptcha_html($escaped_error, $use_ssl);
            }
        }
        function validate_confidentCaptcha_response($errors) {
            if (empty($_POST['confidentcaptcha_code']) || $_POST['confidentcaptcha_code'] == '') {
                $errors->add('blank_captcha', $this->options['no_response_error']);
                return $errors;
            }
            $validationData = array (
                'api_key'=>$this->options['api_key'],
                'public_key'=>$this->options['public_key'],
                'library_version'=>'20130514_WordPress_2.5',
        'click_coordinates'=>$_POST['confidentcaptcha_click_coordinates'],
        'code'=>$_POST['confidentcaptcha_code'],
        'confidentCaptchaID'=>$_POST['confidentcaptcha_captcha_id']
            );
            //$response = confidentCaptcha_check_answer($validationData);
            $client = $this->getClient();
            $response = $client->secureServerValidate($_REQUEST);
            if (!$response->wasCaptchaSolved())
               $errors->add('captcha_wrong', $this->options['incorrect_response_error']);
            return $errors;
        }

        function validate_confidentCaptcha_response_wpmu($errors) {            
            if (!$this->is_authority()) {
                if (isset($_POST['blog_id']) || isset($_POST['blogname']))
                    return $errors;
                $validationData = array (
                    'api_key'=>$this->options['api_key'],
                    'public_key'=>$this->options['public_key'],
                    'library_version'=>'20130514_WordPress_2.5',
            'click_coordinates'=>$_POST['confidentcaptcha_click_coordinates'],
            'code'=>$_POST['confidentcaptcha_code'],
          'confidentCaptchaID'=>$_POST['confidentcaptcha_captcha_id']
                );
                //$response = confidentCaptcha_check_answer($validationData);
                $client = $this->getClient();
                $response = $client->secureServerValidate($_REQUEST);
                if (!$response->wasCaptchaSolved()) {
                    $errors->add('captcha_wrong', $this->options['incorrect_response_error']);
                }
                return $errors;
            }
        }
        function hash_comment($id) {
            define ("confidentCaptcha_WP_HASH_SALT", "b7e0638d85f5d7f3694f68e944136d62");
            
            if (function_exists('wp_hash'))
                return wp_hash(confidentCaptcha_WP_HASH_SALT . $id);
            else
                return md5(confidentCaptcha_WP_HASH_SALT . $this->options['public_key'] . $id);
        }
        function get_confidentCaptcha_html($confidentCaptcha_error, $use_ssl=false) {

            return $this->getClient()->createCaptcha();
        }

        function show_confidentCaptcha_in_comments() {
            wp_enqueue_style('confidentCaptchaStylesheet');
            global $user_ID, $email;
        echo '<script src="http://code.jquery.com/jquery-latest.min.js"
        type="text/javascript"></script>';
            if (isset($this->options['bypass_for_registered_users']) && $this->options['bypass_for_registered_users'] && $this->options['minimum_bypass_level'])
                $needed_capability = $this->options['minimum_bypass_level'];

            if ((isset($needed_capability) && $needed_capability && current_user_can($needed_capability)) || !$this->options['show_in_comments'])
                return;

            else {
                if ((isset($_GET['rerror']) && $_GET['rerror'] == 'ConfidentCAPTCHAwassolvedincorrectly'))
                    echo '<p class="confidentCaptcha-error">' . $this->options['incorrect_response_error'] . "</p>";
                add_action('wp_footer', array(&$this, 'save_comment_script'));
                if (isset($this->options['xhtml_compliance'])) {
                    $comment_string = <<<COMMENT_FORM
                        <div id="confidentCaptcha-submit-btn-area">&nbsp;</div>
COMMENT_FORM;
                }
                else {
                    $comment_string = <<<COMMENT_FORM
                        <div id="confidentCaptcha-submit-btn-area">&nbsp;</div>
                        <noscript>
                         <style type='text/css'>#submit {display:none;}</style>
                         <input name="submit" type="submit" id="submit-alt" tabindex="6" value="Submit Comment"/> 
                        </noscript>
COMMENT_FORM;
                }
                $use_ssl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on");
                $escaped_error = htmlentities(isset($_GET['rerror']), ENT_QUOTES);
                echo $this->get_confidentCaptcha_html(isset($escaped_error) ? $escaped_error : null, $use_ssl) . $comment_string;
           }
       return true;
        }
        function save_comment_script() {
            $javascript = <<<JS
                <script type="text/javascript">
                var sub = document.getElementById('submit');
                document.getElementById('confidentCaptcha-submit-btn-area').appendChild (sub);
                document.getElementById('submit').tabIndex = 6;
                if ( typeof _confidentCaptcha_wordpress_savedcomment != 'undefined') {
                        document.getElementById('comment').value = _confidentCaptcha_wordpress_savedcomment;
                }
                document.getElementById('confidentCaptcha_table').style.direction = 'ltr';
                </script>
JS;
            echo $javascript;
        }
        function show_captcha_for_comment() {
            global $user_ID;
            return true;
        }
        function check_comment($comment) {
            global $user_ID;
            if ($this->options['bypass_for_registered_users'] && $this->options['minimum_bypass_level'])
                $needed_capability = $this->options['minimum_bypass_level'];
            if (($needed_capability && current_user_can($needed_capability)) || !$this->options['show_in_comments'])
                return $comment;
            if (empty($_POST['confidentcaptcha_code']) || $_POST['confidentcaptcha_code'] == '') {
               $user = new WP_Error( 'blank_captcha',__('<strong>ERROR</strong>: The captcha field is empty.'));
               return $user;
            }
            if ($this->show_captcha_for_comment() ) {

                error_log("test");
                error_log(print_r($comment, true));

                if ($comment['comment_type'] == '' && $_POST['confidentcaptcha_code'] != "") {
                    error_log("in here");
                    $validationData = array (
                        'api_key'=>$this->options['api_key'],
                        'public_key'=>$this->options['public_key'],
                        'library_version'=>'20130514_WordPress_2.5',
                        'confidentcaptcha_click_coordinates'=>$_POST['confidentcaptcha_click_coordinates'],
                        'confidentcaptcha_code'=>$_POST['confidentcaptcha_code'],
                        'confidentcaptcha_captcha_id'=>$_POST['confidentcaptcha_captcha_id'],
                        'confidentcaptcha_cache_key'=>$_POST['confidentcaptcha_cache_key'],
                        'confidentcaptcha_server_key'=>$_POST['confidentcaptcha_server_key'],
                        'confidentcaptcha_auth_token'=>$_POST['confidentcaptcha_auth_token']
                    );
                    $client = $this->getClient();
                    $response = $client->checkCaptcha($validationData);

                    error_log(print_r($response->wasCaptchaSolved(), true));

                    if($response->wasCaptchaSolved() != "")
                    {
                        return $comment;
                    }
                    else {
                        $this->saved_error = "EC Confident CAPTCHA was solved incorrectly";
                        add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
                        return $comment;
                    }
                }
            }
            return $comment;
        }
    function confidentCaptcha_check_lost_password($user) {
            if (empty($_POST['confidentcaptcha_code']) || $_POST['confidentcaptcha_code'] == '') {
               $user = new WP_Error( 'blank_captcha',__('<strong>ERROR</strong>: The captcha field is empty.'));
               return $user;
            }
            $validationData = array (
                'api_key'=>$this->options['api_key'],
                'public_key'=>$this->options['public_key'],
                'library_version'=>'20130514_WordPress_2.5',
                'confidentcaptcha_click_coordinates'=>$_POST['confidentcaptcha_click_coordinates'],
                'confidentcaptcha_code'=>$_POST['confidentcaptcha_code'],
                'confidentcaptcha_captcha_id'=>$_POST['confidentcaptcha_captcha_id'],
                'confidentcaptcha_cache_key'=>$_POST['confidentcaptcha_cache_key'],
                'confidentcaptcha_server_key'=>$_POST['confidentcaptcha_server_key'],
                'confidentcaptcha_auth_token'=>$_POST['confidentcaptcha_auth_token']
            );
            $client = $this->getClient();
            $response = $client->checkCaptcha($validationData);

            if($response->wasCaptchaSolved() =="")
            {
               $user = new WP_Error( 'captcha_wrong',__('<strong>ERROR</strong>: The captcha was wrong.'));
               return $user;
            }
      return true;
    }
    function check_login($user, $username, $password)
    {
        if( sizeof($_POST) > 0 ){

            if ( empty($username) || empty($password) || $_POST['confidentcaptcha_code'] == "") {

                $error = new WP_Error();
                if ( empty($username) )
                    $error->add('empty_username', __('<strong>ERROR</strong>: The username field is empty.'));

                if ( empty($password) )
                    $error->add('empty_password', __('<strong>ERROR</strong>: The password field is empty.'));

                if ($_POST['confidentcaptcha_code'] == "" || $_POST == "") {
                    $error->add('empty_captcha', __('<strong>ERROR</strong>: The captcha field is empty.'));
                }

                if (isset($_POST['confidentcaptcha_code']) && !empty($_POST['confidentcaptcha_code'])) {
                    $validationData = array (
                        'api_key'=>$this->options['api_key'],
                        'public_key'=>$this->options['public_key'],
                        'library_version'=>'20130514_WordPress_2.5',
                        'confidentcaptcha_click_coordinates'=>$_POST['confidentcaptcha_click_coordinates'],
                        'confidentcaptcha_code'=>$_POST['confidentcaptcha_code'],
                        'confidentcaptcha_captcha_id'=>$_POST['confidentcaptcha_captcha_id'],
                        'confidentcaptcha_cache_key'=>$_POST['confidentcaptcha_cache_key'],
                        'confidentcaptcha_server_key'=>$_POST['confidentcaptcha_server_key'],
                        'confidentcaptcha_auth_token'=>$_POST['confidentcaptcha_auth_token']
                    );

                $client = $this->getClient();
                $response = $client->checkCaptcha($validationData);

                if($response->wasCaptchaSolved() == "")
                {
                    remove_filter('authenticate', 'check_login', 20, 3);
                    $user = new WP_Error( 'captcha_wrong',__('<strong>ERROR</strong>: The captcha was wrong.'));
                    return $user;
                } else {
                    if ( is_a($user, 'WP_User') &&  $_POST['confidentcaptcha_code'] != "") { return $user; }
                }
            }
            remove_filter('authenticate', 'check_login', 20, 3);
            return $error;
        }
            $validationData = array (
                'api_key'=>$this->options['api_key'],
                'public_key'=>$this->options['public_key'],
                'library_version'=>'20130514_WordPress_2.5',
                'confidentcaptcha_click_coordinates'=>$_POST['confidentcaptcha_click_coordinates'],
                'confidentcaptcha_code'=>$_POST['confidentcaptcha_code'],
                'confidentcaptcha_captcha_id'=>$_POST['confidentcaptcha_captcha_id'],
                'confidentcaptcha_cache_key'=>$_POST['confidentcaptcha_cache_key'],
                'confidentcaptcha_server_key'=>$_POST['confidentcaptcha_server_key'],
                'confidentcaptcha_auth_token'=>$_POST['confidentcaptcha_auth_token']
            );
            //$confidentCaptcha_response = confidentCaptcha_check_answer($validationData);
            $client = $this->getClient();
            $response = $client->checkCaptcha($validationData);

            error_log(print_r($response, true));
            if($response->wasCaptchaSolved() == "")
            {
                $error = new WP_Error();
                remove_filter('authenticate', 'check_login', 20, 3);
                $incorrect_captcha = ($this->options['incorrect_response_error'] != '') ? $this->options['incorrect_response_error'] : __('Incorrect CAPTCHA', 'confidentCaptcha');
                $error->add('captcha_error', "<strong>$incorrect_captcha</strong>");
                return new WP_Error('captcha_error', "<strong>$incorrect_captcha</strong>");
            }
            error_log("here1");
            if( version_compare($wp_version,'3','>=') ) { // wp 3.0 +
                if ( is_multisite() ) {
                if ( 1 == $userdata->spam)
                  return new WP_Error('invalid_username', __('<strong>ERROR</strong>: Your account has been marked as a spammer.'));
                if ( !is_super_admin( $userdata->ID ) && isset($userdata->primary_blog) ) {
                  $details = get_blog_details( $userdata->primary_blog );
                  if ( is_object( $details ) && $details->spam == 1 )
                return new WP_Error('blog_suspended', __('Site Suspended.'));
                }
            }
        }
        error_log("here2");
        $userdata = apply_filters('wp_authenticate_user', $userdata, $password);
        if ( is_wp_error($userdata) ) {
          return $userdata;
        }
        if ( !wp_check_password($password, $userdata->user_pass, $userdata->ID) ) {
          return new WP_Error('incorrect_password', sprintf(__('<strong>ERROR</strong>: Incorrect password. <a href="%s" title="Password Lost and Found">Lost your password</a>?'), site_url('wp-login.php?action=lostpassword', 'login')));
        }
        $user =  new WP_User($userdata->ID);
        return $user;
        }
        
    }     
        function relative_redirect($location, $comment) {
            if ($this->saved_error != '') {
                $location = substr($location, 0, strpos($location, '#')) .
                    ((strpos($location, "?") === false) ? "?" : "&") .
                    'rcommentid=' . $comment->comment_ID .
                    '&rerror=' . $this->saved_error .
                    '&rchash=' . $this->hash_comment($comment->comment_ID) .
                    '#commentform';
            }
            return $location;
        }
        function saved_comment() {
            if (!is_single() && !is_page())
                return;

            if(isset($_REQUEST['rcommentid'])) {
               $comment_id = $_REQUEST['rcommentid'];               
            }
            if(isset($_REQUEST['rchash'])) {
                $comment_hash = $_REQUEST['rchash'];
            }
            
            if (empty($comment_id) || empty($comment_hash))
               return;
            if ($comment_hash == $this->hash_comment($comment_id)) {
               $comment = get_comment($comment_id);
               $com = preg_replace('/([\\/\(\)\+\;\'])/e','\'%\'.dechex(ord(\'$1\'))', $comment->comment_content);
               $com = preg_replace('/\\r\\n/m', '\\\n', $com);
               echo "
                <script type='text/javascript'>
                var _confidentCaptcha_wordpress_savedcomment =  '" . $com  ."';
                _confidentCaptcha_wordpress_savedcomment = unescape(_confidentCaptcha_wordpress_savedcomment);
                </script>
                ";
                wp_delete_comment($comment->comment_ID);
            }
        }
        function blog_domain() {
            $uri = parse_url(get_option('siteurl'));
            return $uri['host'];
        }
        function show_settings_link($links, $file) {
            if ($file == plugin_basename($this->path_to_plugin_directory() . '/wp-confidentCaptcha.php')) {
               $settings_title = __('Settings for this Plugin', 'confidentCaptcha');
               $settings = __('Settings', 'confidentCaptcha');
               $settings_link = '<a href="options-general.php?page=ec-captcha-plugin-wordpress/confidentCaptcha.php" title="' . $settings_title . '">' . $settings . '</a>';
               array_unshift($links, $settings_link);
            }
            return $links;
        }
        function add_settings_page() {
            if ($this->environment == Environment::WordPressMU && $this->is_authority())
                add_submenu_page('wpmu-admin.php', 'EC Confident CAPTCHA', 'EC Confident CAPTCHA', 'manage_options', __FILE__, array(&$this, 'show_settings_page'));
            add_options_page('EC Confident CAPTCHA', 'EC Confident CAPTCHA', 'manage_options', __FILE__, array(&$this, 'show_settings_page'));
        }
        function show_settings_page() {
            include("settings.php");
        }
        function build_dropdown($name, $keyvalue, $checked_value) {
            echo '<select name="' . $name . '" id="' . $name . '">' . "\n";
            foreach ($keyvalue as $key => $value) {
                $checked = ($value == $checked_value) ? ' selected="selected" ' : '';
                echo '\t <option value="' . $value . '"' . $checked . ">$key</option> \n";
                $checked = NULL;
            }
            echo "</select> \n";
        }
        function capabilities_dropdown() {
            $capabilities = array (
                __('all registered users', 'confidentCaptcha') => 'read',
                __('edit posts', 'confidentCaptcha') => 'edit_posts',
                __('publish posts', 'confidentCaptcha') => 'publish_posts',
                __('moderate comments', 'confidentCaptcha') => 'moderate_comments',
                __('activate plugins', 'confidentCaptcha') => 'activate_plugins'
            );
            $this->build_dropdown('confidentCaptcha_options[minimum_bypass_level]', $capabilities, $this->options['minimum_bypass_level']);
        }
        function debug_mode_dropdown() {
            $options = array (
                __('No', 'confidentCaptcha') => 'FALSE',
                __('Yes', 'confidentCaptcha') => 'TRUE'
            );

            $this->build_dropdown('confidentCaptcha_options[debug_mode]', $options, $this->options['debug_mode']);
        }
        function fp_dropdown() {
            $options = array (
                __('math', 'confidentCaptcha') => 'math',
                __('open', 'confidentCaptcha') => 'open',
                __('closed', 'confidentCaptcha') => 'closed'
            );

            $this->build_dropdown('confidentCaptcha_options[failure_policy]', $options, $this->options['failure_policy']);
        }

        function getClient(){

            $settingsArray = array('api_key'=>$this->options['api_key'],
                                    'public_key'=>$this->options['public_key'],
                                    'failure_policy'=>$this->options['failure_policy'],
                                    'debug_mode'=>$this->options['debug_mode']);
            $client = new ConfidentCaptchaClient($settingsArray);

            return $client;

        }
        
    }
}
?>
