<?php

/**
 * Plugin Name:  Mailgun
 * Plugin URI:   http://wordpress.org/extend/plugins/mailgun/
 * Description:  Mailgun integration for WordPress
 * Version:      1.5.7
 * Author:       Mailgun
 * Author URI:   http://www.mailgun.com/
 * License:      GPLv2 or later
 * Text Domain:  mailgun
 * Domain Path:  /languages/.
 */

/*
 * mailgun-wordpress-plugin - Sending mail from Wordpress using Mailgun
 * Copyright (C) 2016 Mailgun, et al.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/**
 * mg_smtp_last_error is a compound getter/setter for the last error that was
 * encountered during a Mailgun SMTP conversation.
 *
 * @param string $error OPTIONAL
 *
 * @return string Last error that occurred.
 *
 * @since 1.5.0
 */
function mg_smtp_last_error($error = null)
{
    static $last_error;

    if (null === $error) {
        return $last_error;
    } else {
        $tmp = $last_error;
        $last_error = $error;

        return $tmp;
    }
}

/**
 * Debugging output function for PHPMailer.
 *
 * @param string $str   Log message
 * @param string $level Logging level
 *
 * @return none
 *
 * @since 1.5.7
 */
function phpmailer_debug_output($str, $level)
{
    error_log("PHPMailer [$level] $str");
}

/**
 * Entrypoint for the Mailgun plugin. Sets up the mailing "strategy" -
 * either API or SMTP.
 *
 * Registers handlers for later actions and sets up config variables with
 * Wordpress.
 */
class Mailgun
{
    /**
     * Setup shared functionality for Admin and Front End.
     *
     * @return none
     *
     * @since 0.1
     */
    public function __construct()
    {
        $this->options = get_option('mailgun');
        $this->plugin_file = __FILE__;
        $this->plugin_basename = plugin_basename($this->plugin_file);
        $this->api_endpoint = 'https://api.mailgun.net/v3/';

        // Either override the wp_mail function or configure PHPMailer to use the
        // Mailgun SMTP servers
        if ($this->get_option('useAPI') || (defined('MAILGUN_USEAPI') && MAILGUN_USEAPI)) {
            if (!function_exists('wp_mail')) {
                if (!@include dirname(__FILE__).'/includes/wp-mail.php') {
                    self::deactivate_and_die(dirname(__FILE__).'/includes/wp-mail.php');
                }
            }
        } else {
            add_action('phpmailer_init', array(&$this, 'phpmailer_init'));
            add_action('wp_mail_failed', array(&$this, 'wp_mail_failed'));
        }
    }

    /**
     * Get specific option from the options table.
     *
     * @param string $option Name of option to be used as array key for retrieving the specific value
     *
     * @return mixed
     *
     * @since 0.1
     */
    public function get_option($option, $options = null, $default = false)
    {
        if (is_null($options)) {
            $options = &$this->options;
        }
        if (isset($options[$option])) {
            return $options[$option];
        } else {
            return $default;
        }
    }

    /**
     * Hook into phpmailer to override SMTP based configurations
     * to use the Mailgun SMTP server.
     *
     * @param object $phpmailer The PHPMailer object to modify by reference
     *
     * @return none
     *
     * @since 0.1
     */
    public function phpmailer_init(&$phpmailer)
    {
        $username = (defined('MAILGUN_USERNAME') && MAILGUN_USERNAME) ? MAILGUN_USERNAME : $this->get_option('username');
        $domain = (defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN) ? MAILGUN_DOMAIN : $this->get_option('domain');
        $username = preg_replace('/@.+$/', '', $username)."@{$domain}";
        $secure = (defined('MAILGUN_SECURE') && MAILGUN_SECURE) ? MAILGUN_SECURE : $this->get_option('secure');
        $password = (defined('MAILGUN_PASSWORD') && MAILGUN_PASSWORD) ? MAILGUN_PASSWORD : $this->get_option('password');

        $from_name = (defined('MAILGUN_FROM_NAME') && MAILGUN_FROM_NAME) ? MAILGUN_FROM_NAME : $this->detect_from_name();
        $from_address = (defined('MAILGUN_FROM_ADDRESS') && MAILGUN_FROM_ADDRESS) ? MAILGUN_FROM_ADDRESS : $this->detect_from_address();

        $phpmailer->Mailer = 'smtp';
        $phpmailer->Host = 'smtp.mailgun.org';
        $phpmailer->Port = (bool) $secure ? 465 : 587;
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $username;
        $phpmailer->Password = $password;

        $phpmailer->SMTPSecure = (bool) $secure ? 'ssl' : 'none';
        // Without this line... wp_mail for SMTP-only will always return false. But why? :(
        $phpmailer->Debugoutput = 'phpmailer_debug_output';
        $phpmailer->SMTPDebug = 2;
        $phpmailer->From = $from_address;
        $phpmailer->FromName = $from_name;
    }

    /**
     * Capture and store the failure message from PHPmailer so the user will
     * actually know what is wrong.
     *
     * @param WP_Error $error Error raised by Wordpress/PHPmailer
     *
     * @return none
     *
     * @since 1.5.7
     */
    public function wp_mail_failed($error)
    {
        if (is_wp_error($error)) {
            mg_smtp_last_error($error->get_error_message());
        } else {
            mg_smtp_last_error($error->__toString());
        }
    }

    /**
     * Deactivate this plugin and die.
     *
     * Used to deactivate the plugin when files critical to it's operation can not be loaded
     *
     * @since 0.1
     *
     * @return none
     */
    public function deactivate_and_die($file)
    {
        load_plugin_textdomain('mailgun', false, 'mailgun/languages');
        $message = sprintf(__('Mailgun has been automatically deactivated because the file <strong>%s</strong> is missing. Please reinstall the plugin and reactivate.'), $file);
        if (!function_exists('deactivate_plugins')) {
            include ABSPATH.'wp-admin/includes/plugin.php';
        }
        deactivate_plugins(__FILE__);
        wp_die($message);
    }

    /**
     * Make a Mailgun api call.
     *
     * @param string $endpoint The Mailgun endpoint uri
     *
     * @return array
     *
     * @since 0.1
     */
    public function api_call($uri, $params = array(), $method = 'POST')
    {
        $options = get_option('mailgun');
        $apiKey = (defined('MAILGUN_APIKEY') && MAILGUN_APIKEY) ? MAILGUN_APIKEY : $options['apiKey'];
        $domain = (defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN) ? MAILGUN_DOMAIN : $options['domain'];

        $time = time();
        $url = $this->api_endpoint.$uri;
        $headers = array(
            'Authorization' => 'Basic '.base64_encode("api:{$apiKey}")
        );

        switch ($method) {
        case 'GET':
            $params['sess'] = '';
            $querystring = http_build_query($params);
            $url = $url.'?'.$querystring;
            $params = '';
            break;
        case 'POST':
        case 'PUT':
        case 'DELETE':
            $params['sess'] = '';
            $params['time'] = $time;
            $params['hash'] = sha1(date('U'));
            break;
        }

        // make the request
        $args = array(
            'method'    => $method,
            'body'      => $params,
            'headers'   => $headers,
            'sslverify' => true,
        );

        // make the remote request
        $result = wp_remote_request($url, $args);
        if (!is_wp_error($result)) {
            return $result['body'];
        } else {
            return $result->get_error_message();
        }
    }

    /**
     * Get account associated lists.
     *
     * @return array
     *
     * @since 0.1
     */
    public function get_lists()
    {
        $results = array();

        $lists_json = $this->api_call('lists', array(), 'GET');
        $lists_arr = json_decode($lists_json, true);
        if (isset($lists_arr['items']) && !empty($lists_arr['items'])) {
            $results = $lists_arr['items'];
        }

        return $results;
    }

    /**
     * Handle add list ajax post.
     *
     * @return string json
     *
     * @since 0.1
     */
    public function add_list()
    {
        $response = array();

        $name = isset($_POST['name']) ? $_POST['name'] : null;
        $email = isset($_POST['email']) ? $_POST['email'] : null;

        $list_addresses = $_POST['addresses'];

        if (!empty($list_addresses)) {
            foreach ($list_addresses as $address => $val) {
                $response[] = $this->api_call(
                    "lists/{$address}/members",
                    array(
                        'address' => $email,
                        'name'    => $name,
                    )
                );
            }

            echo json_encode(array('status' => 200, 'message' => 'Thank you!'));
        } else {
            echo json_encode(array('status' => 500, 'message' => 'Uh oh. We weren\'t able to add you to the list'.count($list_addresses) ? 's.' : '. Please try again.'));
        }

        wp_die();
    }

    /**
     * Frontend List Form.
     *
     * @param string $list_address Mailgun address list id
     * @param array  $args         widget arguments
     * @param string $instance     widget instance params
     *
     * @since 0.1
     */
    public function list_form($list_address, $args = array(), $instance = array())
    {
        $widget_class_id = "mailgun-list-widget-{$args['widget_id']}";
        $form_class_id = "list-form-{$args['widget_id']}";

        // List addresses from the plugin config
        $list_addresses = array_map('trim', explode(',', $list_address));

        // All list info from the API; used for list info when more than one list is available to subscribe to
        $all_list_addresses = $this->get_lists(); ?>
        <div class="mailgun-list-widget-front <?php echo $widget_class_id; ?> widget">
            <form class="list-form <?php echo $form_class_id; ?>">
                <div class="mailgun-list-widget-inputs">
                    <?php if (isset($args['list_title'])) : ?>
                        <div class="mailgun-list-title">
                            <h4 class="widget-title">
                                <span><?php echo $args['list_title']; ?></span>
                            </h4>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($args['list_description'])) : ?>
                        <div class="mailgun-list-description">
                            <p class="widget-description">
                                <span><?php echo $args['list_description']; ?></span>
                            </p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($args['collect_name']) && intval($args['collect_name']) === 1) : ?>
                        <p class="mailgun-list-widget-name">
                            <strong>Name:</strong>
                            <input type="text" name="name" />
                        </p>
                    <?php endif; ?>
                    <p class="mailgun-list-widget-email">
                        <strong>Email:</strong>
                        <input type="text" name="email" />
                    </p>
                </div>

                <?php if (count($list_addresses) > 1) : ?>
                    <ul class="mailgun-lists" style="list-style: none;">
                        <?php foreach ($all_list_addresses as $la) : ?>
                            <?php if (!in_array($la['address'], $list_addresses)) {
            continue;
        } ?>
                            <li>
                                <input type="checkbox" class="mailgun-list-name" name="addresses[<?php echo $la['address']; ?>]" /> <?php echo $la['name']; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <input type="hidden" name="addresses[<?php echo $list_addresses[0]; ?>]" value="on" />
                <?php endif; ?>

                <input class="mailgun-list-submit-button" data-form-id="<?php echo $form_class_id; ?>" type="button" value="Subscribe" />
                <input type="hidden" name="mailgun-submission" value="1" />

            </form>
            <div class="widget-list-panel result-panel" style="display:none;">
                <span>Thank you for subscribing!</span>
            </div>
        </div>

        <script>

        jQuery(document).ready(function(){

            jQuery('.mailgun-list-submit-button').on('click', function() {

                var form_id = jQuery(this).data('form-id');

                if(jQuery('.mailgun-list-name').length > 0 && jQuery('.'+form_id+' .mailgun-list-name:checked').length < 1) {
                    alert('Please select a list to subscribe to.');
                    return;
                }

                if(jQuery('.'+form_id+' .mailgun-list-widget-name input') && jQuery('.'+form_id+' .mailgun-list-widget-name input').val() === '') {
                    alert('Please enter your subscription name.');
                    return;
                }

                if(jQuery('.'+form_id+' .mailgun-list-widget-email input').val() === '') {
                    alert('Please enter your subscription email.');
                    return;
                }

                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php?action=add_list'); ?>',
                    action:'add_list',
                    type: 'post',
                    dataType: 'json',
                    data: jQuery('.'+form_id+'').serialize(),
                    success: function(data) {

                        data_msg = data.message;
                        already_exists = false;
                        if(data_msg !== undefined){
                            already_exists = data_msg.indexOf('Address already exists') > -1;
                        }

                        // success
                        if((data.status === 200)) {
                            jQuery('.<?php echo $widget_class_id; ?> .widget-list-panel').css('display', 'none');
                            jQuery('.<?php echo $widget_class_id; ?> .list-form').css('display', 'none');
                            jQuery('.<?php echo $widget_class_id; ?> .result-panel').css('display', 'block');
                        // error
                        } else {
                            alert(data_msg);
                        }
                    }
                });
            });
        });

        </script>

        <?php

    }

    /**
     * Initialize List Form.
     *
     * @param array $atts Form attributes
     *
     * @since 0.1
     */
    public function build_list_form($atts)
    {
        if (isset($atts['id']) && $atts['id'] != '') {
            $args['widget_id'] = md5(rand(10000, 99999) + $atts['id']);

            if (isset($atts['collect_name'])) {
                $args['collect_name'] = true;
            }

            if (isset($atts['title'])) {
                $args['list_title'] = $atts['title'];
            }

            if (isset($atts['description'])) {
                $args['list_description'] = $atts['description'];
            }

            ob_start();
            $this->list_form($atts['id'], $args);
            $output_string = ob_get_contents();
            ob_end_clean();

            return $output_string;
        } else {
            ?>
            <span>Mailgun list ID needed to render form!</span>
            <br />
            <strong>Example :</strong> [mailgun id="[your list id]"]
            <?php

        }
    }

    /**
     * Initialize List Widget.
     *
     * @since 0.1
     */
    public function load_list_widget()
    {
        register_widget('list_widget');
        add_shortcode('mailgun', array(&$this, 'build_list_form'));
    }

    /**
     * Find the sending "From Name" with a similar process used in `wp_mail`.
     *
     * @return string
     *
     * @since 1.5.7
     */
    private function detect_from_name()
    {
        $from_name = null;

        if ($this->get_option('override-from') && !is_null($this->get_option('from-name'))) {
            $from_name = $this->get_option('from-name');
        } else {
            if (is_null($this->get_option('from-name'))) {
                if (function_exists('get_current_site')) {
                    $from_name = get_current_site()->site_name;
                } else {
                    $from_name = 'WordPress';
                }
            } else {
                $from_name = $this->get_option('from-name');
            }
        }

        if (has_filter('wp_mail_from_name')) {
            $from_name = apply_filters(
                'wp_mail_from_name',
                $from_name
            );
        }

        return $from_name;
    }

    /**
     * Find the sending "From Address" with a similar process used in `wp_mail`.
     *
     * @return string
     *
     * @since 1.5.7
     */
    private function detect_from_address()
    {
        $from_addr = null;

        if ($this->get_option('override-from') && !is_null($this->get_option('from-address'))) {
            $from_addr = $this->get_option('from-address');
        } else {
            if (is_null($this->get_option('from-address'))) {
                if (function_exists('get_current_site')) {
                    $from_addr = get_current_site()->domain;
                } else {
                    $sitedomain = strtolower($_SERVER['SERVER_NAME']);
                    if (substr($sitedomain, 0, 4) === 'www.') {
                        $sitedomain = substr($sitedomain, 4);
                    }
                }

                $from_addr = $sitedomain;
            } else {
                $from_addr = $this->get_option('from-address');
            }
        }

        if (has_filter('wp_mail_from')) {
            $from_addr = apply_filters(
                'wp_mail_from',
                $from_addr
            );
        }

        return $from_addr;
    }
}

$mailgun = new Mailgun();

if (@include dirname(__FILE__).'/includes/widget.php') {
    add_action('widgets_init', array(&$mailgun, 'load_list_widget'));
    add_action('wp_ajax_nopriv_add_list', array(&$mailgun, 'add_list'));
    add_action('wp_ajax_add_list', array(&$mailgun, 'add_list'));
}

if (is_admin()) {
    if (@include dirname(__FILE__).'/includes/admin.php') {
        $mailgunAdmin = new MailgunAdmin();
    } else {
        Mailgun::deactivate_and_die(dirname(__FILE__).'/includes/admin.php');
    }
}
