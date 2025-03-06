<?php
/**
 * mailgun-wordpress-plugin - Sending mail from WordPress using Mailgun
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
 *
 * @package Mailgun
 */
class MailgunAdmin extends Mailgun {

    /**
     * @var    array    Array of "safe" option defaults.
     */
    private array $defaults;

    /**
     * @var array
     */
    protected array $options = array();

    /**
     * @var string $hook_suffix
     */
    protected $hook_suffix;

    /**
     * Setup backend functionality in WordPress.
     *
     * @return    void
     */
    public function __construct() {
        parent::__construct();

        $this->init();

        // Load localizations if available
        load_plugin_textdomain('mailgun', false, 'mailgun/languages');

        // Activation hook
        register_activation_hook($this->plugin_file, array( &$this, 'activation' ));

        // Hook into admin_init and register settings and potentially register an admin_notice
        add_action('admin_init', array( &$this, 'admin_init' ));

        // Activate the options page
        add_action('admin_menu', array( &$this, 'admin_menu' ));

        // Register an AJAX action for testing mail sending capabilities
        add_action('wp_ajax_mailgun-test', array( &$this, 'ajax_send_test' ));
    }

    /**
     * Adds the default options during plugin activation.
     *
     * @return    void
     */
    public function activation(): void {
        if ( ! $this->options) {
            $this->options = $this->defaults;
            add_option('mailgun', $this->options);
        }
    }


    /**
     * Initialize the default property.
     *
     * @return    void
     */
    public function init(): void {
        $sitename = sanitize_text_field(strtolower($_SERVER['SERVER_NAME'] ?? site_url()));
        if (substr($sitename, 0, 4) === 'www.') {
            $sitename = substr($sitename, 4);
        }

        $region        = ( defined('MAILGUN_REGION') && MAILGUN_REGION ) ? MAILGUN_REGION : $this->get_option('region');
        $regionDefault = $region ?: 'us';

        $this->defaults = array(
            'region'        => $regionDefault,
            'useAPI'        => '1',
            'apiKey'        => '',
            'domain'        => '',
            'username'      => '',
            'password'      => '',
            'secure'        => '1',
            'sectype'       => 'tls',
            'track-clicks'  => '',
            'track-opens'   => '',
            'campaign-id'   => '',
            'override-from' => '0',
            'tag'           => $sitename,
        );
    }

    /**
     * Add the options page.
     *
     * @return    void
     */
    public function admin_menu(): void {
        if (current_user_can('manage_options')) {
            $this->hook_suffix = add_options_page(
                __('Mailgun', 'mailgun'),
                __('Mailgun', 'mailgun'),
                'manage_options',
                'mailgun',
                array( &$this, 'options_page' )
            );
            add_options_page(
                __('Mailgun Lists', 'mailgun'),
                __('Mailgun Lists', 'mailgun'),
                'manage_options',
                'mailgun-lists',
                array( &$this, 'lists_page' )
            );
            add_action("admin_print_scripts-{$this->hook_suffix}", array( &$this, 'admin_js' ));
            add_filter("plugin_action_links_{$this->plugin_basename}", array( &$this, 'filter_plugin_actions' ));
            add_action("admin_footer-{$this->hook_suffix}", array( &$this, 'admin_footer_js' ));
        }
    }

    /**
     * Enqueue javascript required for the admin settings page.
     *
     * @return    void
     */
    public function admin_js(): void {
        wp_enqueue_script('jquery');
    }

    /**
     * Output JS to footer for enhanced admin page functionality.
     */
    public function admin_footer_js(): void {
        ?>
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
        <script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
        <script type="text/javascript">

            /* <![CDATA[ */
            var mailgunApiOrNot = function () {
                if (jQuery('#mailgun-api').val() == 1) {
                    jQuery('.mailgun-smtp').hide()
                    jQuery('.mailgun-api').show()
                } else {
                    jQuery('.mailgun-api').hide()
                    jQuery('.mailgun-smtp').show()
                }

            }
            var formModified = false
            jQuery().ready(function () {
                mailgunApiOrNot()
                jQuery('#mailgun-api').change(function () {
                    mailgunApiOrNot()
                })
                jQuery('#mailgun-test').click(function (e) {
                    e.preventDefault()
                    if (formModified) {
                        var doTest = confirm('<?php _e('The Mailgun plugin configuration has changed since you last saved. Do you wish to test anyway?\n\nClick "Cancel" and then "Save Changes" if you wish to save your changes.',
                            'mailgun'); ?>')
                        if (!doTest) {
                            return false
                        }
                    }
                    jQuery(this).val('<?php _e('Testing...', 'mailgun'); ?>')
                    jQuery('#mailgun-test-result').text('')
                    jQuery.get(
                        ajaxurl,
                        {
                            action: 'mailgun-test',
                            _wpnonce: '<?php echo esc_attr(wp_create_nonce()); ?>'
                        }
                    )
                        .complete(function () {
                            jQuery('#mailgun-test').val('<?php _e('Test Configuration', 'mailgun'); ?>')
                        })
                        .success(function (data) {
                            if (typeof data.message !== 'undefined' && data.message === 'Failure') {
                                toastr.error('Mailgun ' + data.method + ' Test ' + data.message
                                    + '; status "' + data.error + '"');
                            } else {
                                toastr.success('Mailgun ' + data.method + ' Test ' + data.message
                                    + '; status "' + data.error + '"');
                            }
                        })
                        .error(function () {
                            toastr.error('Mailgun Test <?php _e('Failure', 'mailgun'); ?>')
                        })
                })
                jQuery('#mailgun-form').change(function () {
                    formModified = true
                })
            })
            /* ]]> */
        </script>
        <?php
    }

    /**
     * Output the options page.
     *
     * @return    void
     */
    public function options_page(): void {
        if ( ! @include 'options-page.php') {
            printf(
                __(
                    '<div id="message" class="updated fade"><p>The options page for the <strong>Mailgun</strong> plugin cannot be displayed. The file <strong>%s</strong> is missing.  Please reinstall the plugin.</p></div>',
                    'mailgun'
                ),
                __DIR__ . '/options-page.php'
            );
        }
    }

    /**
     * Output the lists page.
     *
     * @return    void
     */
    public function lists_page(): void {
        if ( ! @include 'lists-page.php') {
            printf(
                __(
                    '<div id="message" class="updated fade"><p>The lists page for the <strong>Mailgun</strong> plugin cannot be displayed. The file <strong>%s</strong> is missing.  Please reinstall the plugin.</p></div>',
                    'mailgun'
                ),
                __DIR__ . '/lists-page.php'
            );
        }
    }

    /**
     * Wrapper function hooked into admin_init to register settings
     * and potentially register an admin notice if the plugin hasn't
     * been configured yet.
     *
     * @return    void
     */
    public function admin_init(): void {
        $this->register_settings();

        add_action('admin_notices', array( &$this, 'admin_notices' ));
    }

    /**
     * Whitelist the mailgun options.
     *
     * @return    void
     */
    public function register_settings(): void {
        register_setting('mailgun', 'mailgun', array( &$this, 'validation' ));
    }

    /**
     * Data validation callback function for options.
     *
     * @param array $options An array of options posted from the options page
     *
     * @return    array
     */
    public function validation( array $options ): array {
        $apiKey   = trim($options['apiKey']);
        $username = trim($options['username']);
        if ( ! empty($apiKey)) {
            $pos = strpos($apiKey, 'api:');
            if ($pos !== false && $pos == 0) {
                $apiKey = substr($apiKey, 4);
            }

            if (1 === preg_match('(\w{32}-\w{8}-\w{8})', $apiKey)) {
                $options['apiKey'] = $apiKey;
            } else {
                $pos = strpos($apiKey, 'key-');
                if ($pos === false || $pos > 4) {
                    $apiKey = "key-{$apiKey}";
                }
                $options['apiKey'] = $apiKey;
            }
        }

        if ( ! empty($username)) {
            $username            = preg_replace('/@.+$/', '', $username);
            $options['username'] = $username;
        }

        foreach ($options as $key => $value) {
            $options[ $key ] = trim($value);
        }

        if (empty($options['override-from'])) {
            $options['override-from'] = $this->defaults['override-from'];
        }

        if (empty($options['sectype'])) {
            $options['sectype'] = $this->defaults['sectype'];
        }

        $this->options = $options;

        return $options;
    }

    /**
     * Function to output an admin notice
     * when plugin settings or constants need to be configured
     *
     * @return    void
     */
    public function admin_notices(): void {
        $screen = get_current_screen();
        if ( ! isset($screen)) {
            return;
        }
        if ( ! current_user_can('manage_options') || $screen->id === $this->hook_suffix) {
            return;
        }

        $smtpPasswordUndefined   = ( ! $this->get_option('password') && ( ! defined('MAILGUN_PASSWORD') || ! MAILGUN_PASSWORD ) );
        $smtpActiveNotConfigured = ( $this->get_option('useAPI') === '0' && $smtpPasswordUndefined );
        $apiRegionUndefined      = ( ! $this->get_option('region') && ( ! defined('MAILGUN_REGION') || ! MAILGUN_REGION ) );
        $apiKeyUndefined         = ( ! $this->get_option('apiKey') && ( ! defined('MAILGUN_APIKEY') || ! MAILGUN_APIKEY ) );
        $apiActiveNotConfigured  = ( $this->get_option('useAPI') === '1' && ( $apiRegionUndefined || $apiKeyUndefined ) );

        if (isset($_SESSION) && ( ! isset($_SESSION['settings_turned_of']) || $_SESSION['settings_turned_of'] === false ) && ( $apiActiveNotConfigured || $smtpActiveNotConfigured )) {
			?>
            <div id='mailgun-warning' class='notice notice-warning is-dismissible'>
                <p>
                    <?php
                    printf(
                        __(
                            'Use HTTP API is turned off or you do not have SMTP credentials. You can configure your Mailgun settings in your wp-config.php file or <a href="%1$s">here</a>',
                            'mailgun'
                        ),
                        menu_page_url('mailgun', false)
                    );
                    ?>
                </p>
            </div>
            <?php $_SESSION['settings_turned_of'] = true; ?>
        <?php } ?>

        <?php
        if ($this->get_option('override-from') === '1' &&
            ( ! $this->get_option('from-name') || ! $this->get_option('from-address') )
        ) {
			?>
            <div id='mailgun-warning' class='notice notice-warning is-dismissible'>
                <p>
                    <strong>
                        <?php _e('Mailgun is almost ready. ', 'mailgun'); ?>
                    </strong>
                    <?php
                    printf(
                        __(
                            '"Override From" option requires that "From Name" and "From Address" be set to work properly! <a href="%1$s">Configure Mailgun now</a>.',
                            'mailgun'
                        ),
                        menu_page_url('mailgun', false)
                    );
                    ?>
                </p>
            </div>
			<?php
        }
    }

    /**
     * Add a settings link to the plugin actions.
     *
     * @param array $links Array of the plugin action links
     *
     * @return    array
     */
    public function filter_plugin_actions( array $links ): array {
        $settings_link = '<a href="' . menu_page_url('mailgun', false) . '">' . __('Settings', 'mailgun') . '</a>';
        array_unshift($links, $settings_link);

        return $links;
    }

    /**
     * AJAX callback function to test mail sending functionality.
     *
     * @return void
     * @throws JsonException
     */
    public function ajax_send_test(): void {
        nocache_headers();
        header('Content-Type: application/json');

        if ( ! current_user_can('manage_options') || ! wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']))) {
            die(
                json_encode(
                    array(
						'message' => __('Unauthorized', 'mailgun'),
						'method'  => null,
						'error'   => __('Unauthorized', 'mailgun'),
                    ),
                    JSON_THROW_ON_ERROR
                )
            );
        }

        $getRegion = ( defined('MAILGUN_REGION') && MAILGUN_REGION ) ? MAILGUN_REGION : $this->get_option('region');
        $useAPI    = ( defined('MAILGUN_USEAPI') && MAILGUN_USEAPI ) ? MAILGUN_USEAPI : $this->get_option('useAPI');
        $secure    = ( defined('MAILGUN_SECURE') && MAILGUN_SECURE ) ? MAILGUN_SECURE : $this->get_option('secure');
        $sectype   = ( defined('MAILGUN_SECTYPE') && MAILGUN_SECTYPE ) ? MAILGUN_SECTYPE : $this->get_option('sectype');
        $replyTo   = ( defined('MAILGUN_REPLY_TO_ADDRESS') && MAILGUN_REPLY_TO_ADDRESS ) ? MAILGUN_REPLY_TO_ADDRESS : $this->get_option('reply_to');

        if ( ! $getRegion) {
            mg_api_last_error(__('Region has not been selected', 'mailgun'));
        } else {
            if ($getRegion === 'us') {
                $region = __('U.S./North America', 'mailgun');
            }
            if ($getRegion === 'eu') {
                $region = __('Europe', 'mailgun');
            }
        }

        if ($useAPI) {
            $method = __('HTTP API', 'mailgun');
        } else {
            $method = ( $secure ) ? __('Secure SMTP', 'mailgun') : __('Insecure SMTP', 'mailgun');
            if ($secure) {
                $method .= sprintf(__(' via %s', 'mailgun'), $sectype);
            }
        }

        $admin_email = get_option('admin_email');
        if ( ! $admin_email) {
            die(
                json_encode(
                    array(
						'message' => __('Admin Email is empty', 'mailgun'),
						'method'  => $method,
						'error'   => __('Admin Email is empty', 'mailgun'),
                    ),
                    JSON_THROW_ON_ERROR
                )
            );
        }

        try {
            $headers = array(
                'Content-Type: text/plain',
                'Reply-To: ' . $replyTo,
            );

            $result = wp_mail(
                $admin_email,
                __('Mailgun WordPress Plugin Test', 'mailgun'),
                sprintf(
                    __(
                        "This is a test email generated by the Mailgun WordPress plugin.\n\nIf you have received this message, the requested test has succeeded.\n\nThe sending region is set to %s.\n\nThe method used to send this email was: %s.",
                        'mailgun'
                    ),
                    $region,
                    $method
                ),
                $headers
            );
        } catch (Throwable $throwable) {
            // Log purpose
        }

        if ($useAPI) {
            if ( ! function_exists('mg_api_last_error')) {
                if ( ! include __DIR__ . '/wp-mail-api.php') {
                    $this->deactivate_and_die(__DIR__ . '/wp-mail-api.php');
                }
            }
            $error_msg = mg_api_last_error();
        } else {
            if ( ! function_exists('mg_smtp_last_error')) {
                if ( ! include __DIR__ . '/wp-mail-smtp.php') {
                    $this->deactivate_and_die(__DIR__ . '/wp-mail-smtp.php');
                }
            }
            $error_msg = mg_smtp_last_error();
        }

        // Admin Email is used as 'to' parameter, but in case of 'Test Configuration' this message is not clear for the user, so replaced with more appropriate one
        if (str_contains($error_msg, "'to'") && str_contains($error_msg, 'is not a valid')) {
            $error_msg = sprintf(
                "Administration Email Address (%s) is not valid and can't be used for test, you can change it at General Setting page",
                $admin_email
            );
        }

        if ($result) {
            die(
                json_encode(
                    array(
						'message' => __('Success', 'mailgun'),
						'method'  => $method,
						'error'   => __('Success', 'mailgun'),
                    ),
                    JSON_THROW_ON_ERROR
                )
            );
        }

        // Error message will always be returned in case of failure, if not - connection wasn't successful
        $error_msg = $error_msg ?: "Can't connect to Mailgun";
        die(
            json_encode(
                array(
					'message' => __('Failure', 'mailgun'),
					'method'  => $method,
					'error'   => $error_msg,
                ),
                JSON_THROW_ON_ERROR
            )
        );
    }
}
