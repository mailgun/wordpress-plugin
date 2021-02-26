<?php

/**
 * Plugin Name:  Mailgun
 * Plugin URI:   http://wordpress.org/extend/plugins/mailgun/
 * Description:  Mailgun integration for WordPress
 * Version:      1.7.6
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
     * @since    0.1
     */
    public function __construct()
    {
        $this->options = get_option('mailgun');
        $this->plugin_file = __FILE__;
        $this->plugin_basename = plugin_basename($this->plugin_file);

        // Either override the wp_mail function or configure PHPMailer to use the
        // Mailgun SMTP servers
        // When using SMTP, we also need to inject a `wp_mail` filter to make "from" settings
        // work properly. Fixes issues with 1.5.7+
        if ($this->get_option('useAPI') || (defined('MAILGUN_USEAPI') && MAILGUN_USEAPI)):
            if (!function_exists('wp_mail')):
                if (!include dirname(__FILE__) . '/includes/wp-mail-api.php'):
                    self::deactivate_and_die(dirname(__FILE__) . '/includes/wp-mail-api.php');
                endif;
            endif;
        else:
            // Using SMTP, include the SMTP filter
            if (!function_exists('mg_smtp_mail_filter')):
                if (!include dirname(__FILE__) . '/includes/wp-mail-smtp.php'):
                    self::deactivate_and_die(dirname(__FILE__) . '/includes/wp-mail-smtp.php');
                endif;
            endif;
            add_filter('wp_mail', 'mg_smtp_mail_filter');
            add_action('phpmailer_init', array(&$this, 'phpmailer_init'));
            add_action('wp_mail_failed', 'wp_mail_failed');
        endif;
    }

    /**
     * Get specific option from the options table.
     *
     * @param    string $option  Name of option to be used as array key for retrieving the specific value
     * @param    array  $options Array to iterate over for specific values
     * @param    bool   $default False if no options are set
     *
     * @return    mixed
     *
     * @since    0.1
     */
    public function get_option($option, $options = null, $default = false)
    {
        if (is_null($options)):
            $options = &$this->options;
        endif;
        if (isset($options[ $option ])):
            return $options[ $option ];
        else:
            return $default;
        endif;
    }

    /**
     * Hook into phpmailer to override SMTP based configurations
     * to use the Mailgun SMTP server.
     *
     * @param    object $phpmailer The PHPMailer object to modify by reference
     *
     * @return    void
     *
     * @since    0.1
     */
    public function phpmailer_init(&$phpmailer)
    {
        $username = (defined('MAILGUN_USERNAME') && MAILGUN_USERNAME) ? MAILGUN_USERNAME : $this->get_option('username');
        $domain = (defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN) ? MAILGUN_DOMAIN : $this->get_option('domain');
        $username = preg_replace('/@.+$/', '', $username) . "@{$domain}";
        $secure = (defined('MAILGUN_SECURE') && MAILGUN_SECURE) ? MAILGUN_SECURE : $this->get_option('secure');
        $sectype = (defined('MAILGUN_SECTYPE') && MAILGUN_SECTYPE) ? MAILGUN_SECTYPE : $this->get_option('sectype');
        $password = (defined('MAILGUN_PASSWORD') && MAILGUN_PASSWORD) ? MAILGUN_PASSWORD : $this->get_option('password');
        $region = (defined('MAILGUN_REGION') && MAILGUN_REGION) ? MAILGUN_REGION : $this->get_option('region');

        $smtp_endpoint = mg_smtp_get_region($region);
        $smtp_endpoint = (bool) $smtp_endpoint ? $smtp_endpoint : 'smtp.mailgun.org';

        $phpmailer->Mailer = 'smtp';
        $phpmailer->Host = $smtp_endpoint;

        if ('ssl' === $sectype):
            // For SSL-only connections, use 465
            $phpmailer->Port = 465;
        else:
            // Otherwise, use 587.
            $phpmailer->Port = 587;
        endif;

        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $username;
        $phpmailer->Password = $password;

        $phpmailer->SMTPSecure = (bool) $secure ? $sectype : '';
        // Without this line... wp_mail for SMTP-only will always return false. But why? :(
        $phpmailer->Debugoutput = 'mg_smtp_debug_output';
        $phpmailer->SMTPDebug = 2;

        // Emit some logging for SMTP connection
        mg_smtp_debug_output(sprintf("PHPMailer configured to send via %s:%s", $phpmailer->Host, $phpmailer->Port),
            'DEBUG');
    }

    /**
     * Deactivate this plugin and die.
     * Deactivate the plugin when files critical to it's operation cannot be loaded
     *
     * @param    $file    Files critical to plugin functionality
     *
     * @return    void
     *
     * @since    0.1
     */
    public function deactivate_and_die($file)
    {
        load_plugin_textdomain('mailgun', false, 'mailgun/languages');
        $message = sprintf(__('Mailgun has been automatically deactivated because the file <strong>%s</strong> is missing. Please reinstall the plugin and reactivate.'),
            $file);
        if (!function_exists('deactivate_plugins')):
            include ABSPATH . 'wp-admin/includes/plugin.php';
        endif;
        deactivate_plugins(__FILE__);
        wp_die($message);
    }

    /**
     * Make a Mailgun api call.
     *
     * @param    string $uri    The endpoint for the Mailgun API
     * @param    array  $params Array of parameters passed to the API
     * @param    string $method The form request type
     *
     * @return    array
     *
     * @since    0.1
     */
    public function api_call($uri, $params = array(), $method = 'POST')
    {
        $options = get_option('mailgun');
        $getRegion = (defined('MAILGUN_REGION') && MAILGUN_REGION) ? MAILGUN_REGION : $options[ 'region' ];
        $apiKey = (defined('MAILGUN_APIKEY') && MAILGUN_APIKEY) ? MAILGUN_APIKEY : $options[ 'apiKey' ];
        $domain = (defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN) ? MAILGUN_DOMAIN : $options[ 'domain' ];

        $region = mg_api_get_region($getRegion);
        $this->api_endpoint = ($region) ? $region : 'https://api.mailgun.net/v3/';

        $time = time();
        $url = $this->api_endpoint . $uri;
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode("api:{$apiKey}"),
        );

        switch ($method) {
            case 'GET':
                $params[ 'sess' ] = '';
                $querystring = http_build_query($params);
                $url = $url . '?' . $querystring;
                $params = '';
                break;
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $params[ 'sess' ] = '';
                $params[ 'time' ] = $time;
                $params[ 'hash' ] = sha1(date('U'));
                break;
        }

        // make the request
        $args = array(
            'method' => $method,
            'body' => $params,
            'headers' => $headers,
            'sslverify' => true,
        );

        // make the remote request
        $result = wp_remote_request($url, $args);
        if (!is_wp_error($result)):
            return $result[ 'body' ];
        else:
            return $result->get_error_message();
        endif;
    }

    /**
     * Get account associated lists.
     *
     * @return    array
     *
     * @since    0.1
     */
    public function get_lists()
    {
        $results = array();

        $lists_json = $this->api_call('lists', array(), 'GET');
        $lists_arr = json_decode($lists_json, true);
        if (isset($lists_arr[ 'items' ]) && !empty($lists_arr[ 'items' ])):
            $results = $lists_arr[ 'items' ];
        endif;

        return $results;
    }

    /**
     * Handle add list ajax post.
     *
     * @return    string    json
     *
     * @since    0.1
     */
    public function add_list()
    {
        $response = array();

        $name = isset($_POST[ 'name' ]) ? $_POST[ 'name' ] : null;
        $email = isset($_POST[ 'email' ]) ? $_POST[ 'email' ] : null;

        $list_addresses = $_POST[ 'addresses' ];

        if (!empty($list_addresses)):
            foreach ($list_addresses as $address => $val):
                $response[] = $this->api_call(
                    "lists/{$address}/members",
                    array(
                        'address' => $email,
                        'name' => $name,
                    )
                );
            endforeach;

            echo json_encode(array('status' => 200, 'message' => 'Thank you!'));
        else:
            echo json_encode(array(
                'status' => 500,
                'message' => 'Uh oh. We weren\'t able to add you to the list' . count($list_addresses) ? 's.' : '. Please try again.'
            ));
        endif;

        wp_die();
    }

    /**
     * Frontend List Form.
     *
     * @param    string $list_address Mailgun address list id
     * @param    array  $args         widget arguments
     * @param    array  $instance     widget instance params
     *
     * @since    0.1
     */
    public function list_form($list_address, $args = array(), $instance = array())
    {
        $widget_class_id = "mailgun-list-widget-{$args['widget_id']}";
        $form_class_id = "list-form-{$args['widget_id']}";

        // List addresses from the plugin config
        $list_addresses = array_map('trim', explode(',', $list_address));

        // All list info from the API; used for list info when more than one list is available to subscribe to
        $all_list_addresses = $this->get_lists();
    ?>
        <div class="mailgun-list-widget-front <?php echo $widget_class_id; ?> widget">
            <form class="list-form <?php echo $form_class_id; ?>">
                <div class="mailgun-list-widget-inputs">
                    <?php if (isset($args[ 'list_title' ])): ?>
                        <div class="mailgun-list-title">
                            <h4 class="widget-title">
                                <span><?php echo $args[ 'list_title' ]; ?></span>
                            </h4>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($args[ 'list_description' ])): ?>
                        <div class="mailgun-list-description">
                            <p class="widget-description">
                                <span><?php echo $args[ 'list_description' ]; ?></span>
                            </p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($args[ 'collect_name' ]) && intval($args[ 'collect_name' ]) === 1): ?>
                        <p class="mailgun-list-widget-name">
                            <strong>Name:</strong>
                            <input type="text" name="name"/>
                        </p>
                    <?php endif; ?>
                    <p class="mailgun-list-widget-email">
                        <strong>Email:</strong>
                        <input type="text" name="email"/>
                    </p>
                </div>

                <?php if (count($list_addresses) > '1'): ?>
                    <ul class="mailgun-lists" style="list-style: none;">
                        <?php
                            foreach ($all_list_addresses as $la):
                                if (!in_array($la[ 'address' ], $list_addresses)):
                                    continue;
                                endif;
                        ?>
                                <li>
                                    <input type="checkbox" class="mailgun-list-name"
                                           name="addresses[<?php echo $la[ 'address' ]; ?>]"/> <?php echo $la[ 'name' ]; ?>
                                </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <input type="hidden" name="addresses[<?php echo $list_addresses[ 0 ]; ?>]" value="on"/>
                <?php endif; ?>

                <input class="mailgun-list-submit-button" data-form-id="<?php echo $form_class_id; ?>" type="button"
                       value="Subscribe"/>
                <input type="hidden" name="mailgun-submission" value="1"/>

            </form>
            <div class="widget-list-panel result-panel" style="display:none;">
                <span>Thank you for subscribing!</span>
            </div>
        </div>

        <script>
          jQuery(document).ready(function () {

            jQuery('.mailgun-list-submit-button').on('click', function () {

              var form_id = jQuery(this).data('form-id')

              if (jQuery('.mailgun-list-name').length > 0 && jQuery('.' + form_id + ' .mailgun-list-name:checked').length < 1) {
                alert('Please select a list to subscribe to.')
                return
              }

              if (jQuery('.' + form_id + ' .mailgun-list-widget-name input') && jQuery('.' + form_id + ' .mailgun-list-widget-name input').val() === '') {
                alert('Please enter your subscription name.')
                return
              }

              if (jQuery('.' + form_id + ' .mailgun-list-widget-email input').val() === '') {
                alert('Please enter your subscription email.')
                return
              }

              jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php?action=add_list'); ?>',
                action: 'add_list',
                type: 'post',
                dataType: 'json',
                data: jQuery('.' + form_id + '').serialize(),
                success: function (data) {

                  data_msg = data.message
                  already_exists = false
                  if (data_msg !== undefined) {
                    already_exists = data_msg.indexOf('Address already exists') > -1
                  }

                  // success
                  if ((data.status === 200)) {
                    jQuery('.<?php echo $widget_class_id; ?> .widget-list-panel').css('display', 'none')
                    jQuery('.<?php echo $widget_class_id; ?> .list-form').css('display', 'none')
                    jQuery('.<?php echo $widget_class_id; ?> .result-panel').css('display', 'block')
                    // error
                  } else {
                    alert(data_msg)
                  }
                }
              })
            })
          })
        </script>

        <?php
    }

    /**
     * Initialize List Form.
     *
     * @param    array $atts Form attributes
     *
     * @return    string
     *
     * @since    0.1
     */
    public function build_list_form($atts)
    {
        if (isset($atts[ 'id' ]) && $atts[ 'id' ] != ''):
            $args[ 'widget_id' ] = md5(rand(10000, 99999) + $atts[ 'id' ]);

            if (isset($atts[ 'collect_name' ])):
                $args[ 'collect_name' ] = true;
            endif;

            if (isset($atts[ 'title' ])):
                $args[ 'list_title' ] = $atts[ 'title' ];
            endif;

            if (isset($atts[ 'description' ])):
                $args[ 'list_description' ] = $atts[ 'description' ];
            endif;

            ob_start();
            $this->list_form($atts[ 'id' ], $args);
            $output_string = ob_get_contents();
            ob_end_clean();

            return $output_string;
        else:
            ?>
            <span>Mailgun list ID needed to render form!</span>
            <br/>
            <strong>Example :</strong> [mailgun id="[your list id]"]
        <?php
        endif;
    }

    /**
     * Initialize List Widget.
     *
     * @since    0.1
     */
    public function load_list_widget()
    {
        register_widget('list_widget');
        add_shortcode('mailgun', array(&$this, 'build_list_form'));
    }
}

$mailgun = new Mailgun();

if (@include dirname(__FILE__) . '/includes/widget.php'):
    add_action('widgets_init', array(&$mailgun, 'load_list_widget'));
    add_action('wp_ajax_nopriv_add_list', array(&$mailgun, 'add_list'));
    add_action('wp_ajax_add_list', array(&$mailgun, 'add_list'));
endif;

if (is_admin()):
    if (@include dirname(__FILE__) . '/includes/admin.php'):
        $mailgunAdmin = new MailgunAdmin();
    else:
        Mailgun::deactivate_and_die(dirname(__FILE__) . '/includes/admin.php');
    endif;
endif;
