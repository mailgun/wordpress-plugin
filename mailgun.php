<?php
/**
 * Plugin Name:  Mailgun
 * Plugin URI:   http://wordpress.org/extend/plugins/mailgun/
 * Description:  Mailgun integration for WordPress
 * Version:      1.9.3
 * Tested up to: 6.1
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
 * WordPress.
 */
class Mailgun
{
    /**
     * @var Mailgun $instance
     */
    private static $instance;

    /**
     * @var false|mixed|null
     */
    private $options;

    /**
     * @var string
     */
    protected $plugin_file;

    /**
     * @var string
     */
    protected $plugin_basename;

    /**
     * @var string
     */
    protected $assetsDir;

    /**
     * Setup shared functionality for Admin and Front End.
     *
     */
    public function __construct()
    {
        $this->options = get_option('mailgun');
        $this->plugin_file = __FILE__;
        $this->plugin_basename = plugin_basename($this->plugin_file);
        $this->assetsDir = plugin_dir_url($this->plugin_file) . 'assets/';

        // Either override the wp_mail function or configure PHPMailer to use the
        // Mailgun SMTP servers
        // When using SMTP, we also need to inject a `wp_mail` filter to make "from" settings
        // work properly. Fixed issues with 1.5.7+
        if ($this->get_option('useAPI') || (defined('MAILGUN_USEAPI') && MAILGUN_USEAPI)) {
            if (!function_exists('wp_mail')) {
                if (!include __DIR__ . '/includes/wp-mail-api.php') {
                    $this->deactivate_and_die(__DIR__ . '/includes/wp-mail-api.php');
                }
            }
        } else {
            // Using SMTP, include the SMTP filter
            if (!function_exists('mg_smtp_mail_filter')) {
                if (!include __DIR__ . '/includes/wp-mail-smtp.php') {
                    $this->deactivate_and_die(__DIR__ . '/includes/wp-mail-smtp.php');
                }
            }
            add_filter('wp_mail', 'mg_smtp_mail_filter');
            add_action('phpmailer_init', [&$this, 'phpmailer_init']);
            add_action('wp_mail_failed', 'wp_mail_failed');
        }
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get specific option from the options table.
     *
     * @param string     $option  Name of option to be used as array key for retrieving the specific value
     * @param array|null $options Array to iterate over for specific values
     * @param bool       $default False if no options are set
     *
     * @return    mixed
     *
     */
    public function get_option(string $option, ?array $options = null, bool $default = false)
    {
        if (is_null($options)) {
            $options = &$this->options;
        }

        if (isset($options[$option])) {
            return $options[$option];
        }

        return $default;
    }

    /**
     * Hook into phpmailer to override SMTP based configurations
     * to use the Mailgun SMTP server.
     *
     * @param    object $phpmailer The PHPMailer object to modify by reference
     *
     * @return    void
     *
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

        if ('ssl' === $sectype) {
            // For SSL-only connections, use 465
            $phpmailer->Port = 465;
        } else {
            // Otherwise, use 587.
            $phpmailer->Port = 587;
        }

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
     */
    public function deactivate_and_die($file)
    {
        load_plugin_textdomain('mailgun', false, 'mailgun/languages');
        $message = sprintf(__('Mailgun has been automatically deactivated because the file <strong>%s</strong> is missing. Please reinstall the plugin and reactivate.'),
            $file);
        if (!function_exists('deactivate_plugins')) {
            include ABSPATH . 'wp-admin/includes/plugin.php';
        }
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
     * @return    string
     *
     */
    public function api_call($uri, $params = [], $method = 'POST'): string
    {
        $options = get_option('mailgun');
        $getRegion = (defined('MAILGUN_REGION') && MAILGUN_REGION) ? MAILGUN_REGION : $options[ 'region' ];
        $apiKey = (defined('MAILGUN_APIKEY') && MAILGUN_APIKEY) ? MAILGUN_APIKEY : $options[ 'apiKey' ];
        $domain = (defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN) ? MAILGUN_DOMAIN : $options[ 'domain' ];

        $region = mg_api_get_region($getRegion);
        $this->api_endpoint = ($region) ?: 'https://api.mailgun.net/v3/';

        $time = time();
        $url = $this->api_endpoint . $uri;
        $headers = [
            'Authorization' => 'Basic ' . base64_encode("api:{$apiKey}"),
        ];

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
        $args = [
            'method' => $method,
            'body' => $params,
            'headers' => $headers,
            'sslverify' => true,
        ];

        // make the remote request
        $result = wp_remote_request($url, $args);
        if (!is_wp_error($result)) {
            return $result['body'];
        }

        if (is_callable($result)) {
            return $result->get_error_message();
        }

        if (is_array($result)) {
            if (isset($result['response'])) {
                return $result['response']['message'] ?? '';
            }
        }

        return '';

    }

    /**
     * Get account associated lists.
     *
     * @return    array
     *
     * @throws JsonException
     */
    public function get_lists(): array
    {
        $results = [];

        $lists_json = $this->api_call('lists', [], 'GET');

        $lists_arr = json_decode($lists_json, true, 512, JSON_THROW_ON_ERROR);
        if (isset($lists_arr[ 'items' ]) && !empty($lists_arr[ 'items' ])) {
            $results = $lists_arr['items'];
        }

        return $results;
    }

    /**
     * Handle add list ajax post.
     *
     * @return    void    json
     *
     * @throws JsonException
     */
    public function add_list()
    {
        $name = sanitize_text_field($_POST['name'] ?? null);
        $email = sanitize_text_field($_POST['email'] ?? null);

        $list_addresses = sanitize_text_field($_POST['addresses']);

        if (!empty($list_addresses)) {
            $result = [];
            foreach ($list_addresses as $address => $val) {
                $result[] = $this->api_call(
                    "lists/{$address}/members",
                    [
                        'address' => $email,
                        'name' => $name,
                    ]
                );
            }
            $message = 'Thank you!';
            if ($result) {
                $message = 'Something went wrong';
                $response = json_decode($result[0], true);
                if (is_array($response) && isset($response['message'])) {
                    $message = $response['message'];
                }

            }
            echo json_encode([
                'status' => 200,
                'message' => $message
            ], JSON_THROW_ON_ERROR);
        } else {
            echo json_encode([
                'status' => 500,
                'message' => 'Uh oh. We weren\'t able to add you to the list' . count($list_addresses) ? 's.' : '. Please try again.'
            ], JSON_THROW_ON_ERROR);
        }
        wp_die();
    }

    /**
     * Frontend List Form.
     *
     * @param string $list_address Mailgun address list id
     * @param array  $args         widget arguments
     * @param array  $instance     widget instance params
     *
     * @throws JsonException
     */
    public function list_form(string $list_address, array $args = [], array $instance = [])
    {
        $widgetId = $args['widget_id'] ?? 0;
        $widget_class_id = "mailgun-list-widget-{$widgetId}";
        $form_class_id = "list-form-{$widgetId}";

        // List addresses from the plugin config
        $list_addresses = array_map('trim', explode(',', $list_address));

        // All list info from the API; used for list info when more than one list is available to subscribe to
        $all_list_addresses = $this->get_lists();
    ?>
        <div class="mailgun-list-widget-front <?php echo esc_attr($widget_class_id); ?> widget">
            <form class="list-form <?php echo esc_attr($form_class_id); ?>">
                <div class="mailgun-list-widget-inputs">
                    <?php if (isset($args[ 'list_title' ])): ?>
                        <div class="mailgun-list-title">
                            <h4 class="widget-title">
                                <span><?php echo wp_kses_data($args[ 'list_title' ]); ?></span>
                            </h4>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($args[ 'list_description' ])): ?>
                        <div class="mailgun-list-description">
                            <p class="widget-description">
                                <span><?php echo wp_kses_data($args[ 'list_description' ]); ?></span>
                            </p>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($args[ 'collect_name' ]) && (int)$args['collect_name'] === 1): ?>
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
                                           name="addresses[<?php echo esc_attr($la[ 'address' ]); ?>]"/> <?php echo esc_attr($la[ 'name' ] ?: $la[ 'address' ]); ?>
                                </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <input type="hidden" name="addresses[<?php echo esc_attr($list_addresses[ 0 ]); ?>]" value="on"/>
                <?php endif; ?>

                <input class="mailgun-list-submit-button" data-form-id="<?php echo esc_attr($form_class_id); ?>" type="button"
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
                    jQuery('.<?php echo esc_attr($widget_class_id); ?> .widget-list-panel').css('display', 'none')
                    jQuery('.<?php echo esc_attr($widget_class_id); ?> .list-form').css('display', 'none')
                    jQuery('.<?php echo esc_attr($widget_class_id); ?> .result-panel').css('display', 'block')
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
     * @param array $atts Form attributes
     *
     * @return    string
     *
     * @throws JsonException
     */
    public function build_list_form(array $atts): string
    {
        if (isset($atts['id']) && $atts['id'] != '') {
            $args['widget_id'] = md5(rand(10000, 99999) . $atts['id']);

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
            return ob_get_clean();
        }

        return '<span>Mailgun list ID needed to render form!</span>
        <br/>
        <strong>Example :</strong> [mailgun id="[your list id]"]';
    }

    /**
     * Initialize List Widget.
     */
    public function load_list_widget()
    {
        register_widget('list_widget');
        add_shortcode('mailgun', [&$this, 'build_list_form']);
    }

    /**
     * @return string
     */
    public function getAssetsPath(): string
    {
        return $this->assetsDir;
    }
}

$mailgun = Mailgun::getInstance();

if (@include __DIR__ . '/includes/widget.php') {
    add_action('widgets_init', [&$mailgun, 'load_list_widget']);
    add_action('wp_ajax_nopriv_add_list', [&$mailgun, 'add_list']);
    add_action('wp_ajax_add_list', [&$mailgun, 'add_list']);
}

if (is_admin()) {
    if (@include __DIR__ . '/includes/admin.php') {
        $mailgunAdmin = new MailgunAdmin();
    } else {
        $mailgun->deactivate_and_die(__DIR__ . '/includes/admin.php');
    }
}
