<?php

class MailgunAdmin extends Mailgun {
	/**
	 * Setup backend functionality in WordPress
	 *
	 * @return none
	 * @since 0.1
	 */
	function __construct() {
		Mailgun::__construct();

		// Load localizations if available
		load_plugin_textdomain( 'mailgun' , false , 'mailgun/languages' );

		// Activation hook
		register_activation_hook( $this->plugin_file, array( &$this, 'init' ) );

		if ( ! defined( 'MAILGUN_USEAPI' ) || ! MAILGUN_USEAPI ) {
			// Hook into admin_init and register settings and potentially register an admin_notice
			add_action( 'admin_init', array( &$this, 'admin_init' ) );

			// Activate the options page
			add_action( 'admin_menu', array( &$this , 'admin_menu' ) );
		}

		// Register an AJAX action for testing mail sending capabilities
		add_action( 'wp_ajax_mailgun-test', array( &$this, 'ajax_send_test' ) );
	}

	/**
	 * Initialize the default options during plugin activation
	 *
	 * @return none
	 * @since 0.1
	 */
	function init() {
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		
		$defaults = array(
			'useAPI' => '1',
			'apiKey' => '',
			'domain' => '',
			'username' => '',
			'password' => '',
			'secure' => '1',
			'track-clicks' => '',
			'track-opens' => '',
			'campaign-id' => '',
			'tag' => $sitename,
		);
		if ( ! $this->options ) {
			$this->options = $defaults;
			add_option( 'mailgun', $this->options );
		}
	}

	/**
	 * Add the options page
	 *
	 * @return none
	 * @since 0.1
	 */
	function admin_menu() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->hook_suffix = add_options_page( __( 'Mailgun', 'mailgun' ), __( 'Mailgun', 'mailgun' ), 'manage_options', 'mailgun', array( &$this , 'options_page' ) );
			add_options_page( __( 'Mailgun Lists', 'mailgun' ), null, 'manage_options', 'mailgun-lists', array( &$this , 'lists_page' ) );
			add_action( "admin_print_scripts-{$this->hook_suffix}" , array( &$this , 'admin_js' ) );
			add_filter( "plugin_action_links_{$this->plugin_basename}" , array( &$this , 'filter_plugin_actions' ) );
			add_action( "admin_footer-{$this->hook_suffix}" , array( &$this , 'admin_footer_js' ) );
		}
	}

	/**
	 * Enqueue javascript required for the admin settings page
	 *
	 * @return none
	 * @since 0.1
	 */
	function admin_js() {
		wp_enqueue_script( 'jquery' );
	}

	/**
	 * Output JS to footer for enhanced admin page functionality
	 *
	 * @since 0.1
	 */
	function admin_footer_js() {
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
			var mailgunApiOrNot = function() {
				if (jQuery("#mailgun-api").val() == 1) {
					jQuery(".mailgun-smtp").hide();
					jQuery(".mailgun-api").show();
				} else {
					jQuery(".mailgun-api").hide();
					jQuery(".mailgun-smtp").show();
				}

			}
			var formModified = false;
			jQuery().ready(function() {
				mailgunApiOrNot();
				jQuery('#mailgun-api').change(function() {
					mailgunApiOrNot();
				});
				jQuery('#mailgun-test').click(function(e) {
					e.preventDefault();
					if ( formModified ) {
						var doTest = confirm('<?php _e( 'The Mailgun plugin configuration has changed since you last saved. Do you wish to test anyway?\n\nClick "Cancel" and then "Save Changes" if you wish to save your changes.', 'mailgun'); ?>');
						if ( ! doTest ) {
							return false;
						}
					}
					jQuery(this).val('<?php _e( 'Testing...', 'mailgun' ); ?>');
					jQuery("#mailgun-test-result").text('');
					jQuery.get(
						ajaxurl,
						{
							action: 'mailgun-test',
							_wpnonce: '<?php echo wp_create_nonce(); ?>'
						}
					)
					.complete(function() {
						jQuery("#mailgun-test").val('<?php _e( 'Test Configuration', 'mailgun' ); ?>');
					})
					.success(function(data) {
						alert('Mailgun ' + data.method + ' Test ' + data.message);
					})
					.error(function() {
						alert('Mailgun Test <?php _e( 'Failure', 'mailgun' ); ?>');
					});
				});
				jQuery("#mailgun-form").change(function() {
					formModified = true;
				});
			});
		/* ]]> */
		</script>
		<?php
	}

	/**
	 * Output the options page
	 *
	 * @return none
	 * @since 0.1
	 */
	function options_page() {
		if ( ! @include( 'options-page.php' ) ) {
			printf( __( '<div id="message" class="updated fade"><p>The options page for the <strong>Mailgun</strong> plugin cannot be displayed. The file <strong>%s</strong> is missing.  Please reinstall the plugin.</p></div>', 'mailgun'), dirname( __FILE__ ) . '/options-page.php' );
		}
	}
	
	/**
	 * Output the lists page
	 *
	 * @return none
	 * @since 0.1
	 */
	function lists_page() {
		if ( ! @include( 'lists-page.php' ) ) {
			printf( __( '<div id="message" class="updated fade"><p>The lists page for the <strong>Mailgun</strong> plugin cannot be displayed. The file <strong>%s</strong> is missing.  Please reinstall the plugin.</p></div>', 'mailgun'), dirname( __FILE__ ) . '/lists-page.php' );
		}
	}	
	
	// /options-general.php?page=mailgun-lists

	/**
	 * Wrapper function hooked into admin_init to register settings
	 * and potentially register an admin notice if the plugin hasn't
	 * been configured yet
	 *
	 * @return none
	 * @since 0.1
	 */
	function admin_init() {
		$this->register_settings();
		$apiKey = $this->get_option( 'apiKey' );
		$useAPI = $this->get_option( 'useAPI' );
		$password = $this->get_option( 'password' );
		if ( ( empty( $apiKey ) && $useAPI == '1' ) || ( empty( $password ) && $useAPI == '0' ) ) {
			add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
		}
	}

	/**
	 * Whitelist the mailgun options
	 *
	 * @since 0.1
	 * @return none
	 */
	function register_settings() {
		register_setting( 'mailgun', 'mailgun', array( &$this, 'validation' ) );
	}

	/**
	 * Data validation callback function for options
	 *
	 * @param array $options An array of options posted from the options page
	 * @return array
	 * @since 0.1
	 */
	function validation( $options ) {
		$apiKey = trim( $options['apiKey'] );
		$username = trim( $options['username'] );
		if ( ! empty( $apiKey ) ) {
			$pos = strpos( $apiKey, 'key-' );
			if ( $pos === false || $pos > 4 )
				$apiKey = "key-{$apiKey}";

			$pos = strpos( $apiKey, 'api:' );
			if ( $pos !== false && $pos == 0 )
				$apiKey = substr( $apiKey, 4 );
			$options['apiKey'] = $apiKey;
		}

		if ( ! empty( $username ) ) {
			$username = preg_replace( '/@.+$/', '', $username );
			$options['username'] = $username;
		}

		foreach ( $options as $key => $value )
			$options[$key] = trim( $value );

		$this->options = $options;
		return $options;
	}

	/**
	 * Function to output an admin notice when the plugin has not
	 * been configured yet
	 *
	 * @return none
	 * @since 0.1
	 */
	function admin_notices() {
		$screen = get_current_screen();
		if ( $screen->id == $this->hook_suffix )
			return;
?>
		<div id='mailgun-warning' class='updated fade'><p><strong><?php _e( 'Mailgun is almost ready. ', 'mailgun' ); ?></strong><?php printf( __( 'You must <a href="%1$s">configure Mailgun</a> for it to work.', 'mailgun' ), menu_page_url( 'mailgun' , false ) ); ?></p></div>
<?php
	}

	/**
	 * Add a settings link to the plugin actions
	 *
	 * @param array $links Array of the plugin action links
	 * @return array
	 * @since 0.1
	 */
	function filter_plugin_actions( $links ) {
		$settings_link = '<a href="' . menu_page_url( 'mailgun', false ) . '">' . __( 'Settings', 'mailgun' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * AJAX callback function to test mail sending functionality
	 *
	 * @return string
	 * @since 0.1
	 */
	function ajax_send_test() {
		nocache_headers();
		header( 'Content-Type: application/json' );

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_GET[ '_wpnonce' ] ) ) {
			die(
				json_encode(
					array(
						'message' => __( 'Unauthorized', 'mailgun' ),
						'method' => null
					)
				)
			);
		}

		$useAPI = ( defined( 'MAILGUN_USEAPI' ) && MAILGUN_USEAPI ) ? MAILGUN_USEAPI : $this->get_option( 'useAPI' );
		$secure = ( defined( 'MAILGUN_SECURE' ) && MAILGUN_SECURE ) ? MAILGUN_SECURE : $this->get_option( 'secure' );
		if ( (bool) $useAPI )
			$method = __( 'HTTP API', 'mailgun' );
		else
			$method = ( (bool) $secure ) ? __( 'Secure SMTP', 'mailgun' ) : __( 'SMTP', 'mailgun' );

		$admin_email = get_option( 'admin_email' );
		$result = wp_mail(
			$admin_email,
			__( 'Mailgun WordPress Plugin Test', 'mailgun' ),
			sprintf( __( "This is a test email generated by the Mailgun WordPress plugin.\n\nIf you have received this message, the requested test has succeeded.\n\nThe method used to send this email was: %s.", 'mailgun' ), $method )
		);

		if ( $result ) {
			die(
				json_encode(
					array(
						'message' => __( 'Success', 'mailgun' ),
						'method'  => $method
					)
				)
			);
		} else {
			die(
				json_encode(
					array(
						'message' => __( 'Failure', 'mailgun' ),
						'method'  => $method
					)
				)
			);
		}
	}
}
