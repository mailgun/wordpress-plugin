<?php
/**
 * Plugin Name:  Mailgun
 * Plugin URI:   http://wordpress.org/extend/plugins/mailgun/
 * Description:  Mailgun integration for WordPress
 * Version:      1.0
 * Author:       Matt Martz
 * Author URI:   http://www.mailgun.com/
 * License:      GPLv2 or later
 * Text Domain:  mailgun
 * Domain Path:  /languages/
 */

class Mailgun {

	/**
	 * Setup shared functionality for Admin and Front End
	 *
	 * @return none
	 * @since 0.1
	 */
	function __construct() {
		$this->options = get_option( 'mailgun' );
		$this->plugin_file = __FILE__;
		$this->plugin_basename = plugin_basename( $this->plugin_file );

		// Either override the wp_mail function or configure PHPMailer to use the
		// Mailgun SMTP servers
		if ( $this->get_option( 'useAPI' ) || ( defined( 'MAILGUN_USEAPI' ) && MAILGUN_USEAPI ) ) {
			if ( ! function_exists( 'wp_mail' ) ) {
				if ( ! @include( dirname( __FILE__ ) . '/includes/wp-mail.php' ) )
					Mailgun::deactivate_and_die( dirname( __FILE__ ) . '/includes/wp-mail.php' );
			}
		} else {
			add_action( 'phpmailer_init', array( &$this, 'phpmailer_init' ) );
		}
	}

	/**
	 * Get specific option from the options table
	 *
	 * @param string $option Name of option to be used as array key for retrieving the specific value
	 * @return mixed
	 * @since 0.1
	 */
	function get_option( $option, $options = null ) {
		if ( is_null( $options ) )
			$options = &$this->options;
		if ( isset( $options[$option] ) )
			return $options[$option];
		else
			return false;
	}

	/**
	 * Hook into phpmailer to override SMTP based configurations
	 * to use the Mailgun SMTP server
	 *
	 * @param object $phpmailer The PHPMailer object to modify by reference
	 * @return none
	 * @since 0.1
	 */
	function phpmailer_init( &$phpmailer ) {
		$username = ( defined( 'MAILGUN_USERNAME' ) && MAILGUN_USERNAME ) ? MAILGUN_USERNAME : $this->get_option( 'username' );
		$domain = ( defined( 'MAILGUN_DOMAIN' ) && MAILGUN_DOMAIN ) ? MAILGUN_DOMAIN : $this->get_option( 'domain' );
		$username = preg_replace( '/@.+$/', '', $username ) . "@{$domain}";
		$secure = ( defined( 'MAILGUN_SECURE' ) && MAILGUN_SECURE ) ? MAILGUN_SECURE : $this->get_option('secure');
		$password = ( defined( 'MAILGUN_PASSWORD' ) && MAILGUN_PASSWORD ) ? MAILGUN_PASSWORD : $this->get_option('password');

		$phpmailer->Mailer = 'smtp';
		$phpmailer->SMTPSecure = (bool) $secure ? 'ssl' : 'none';
		$phpmailer->Host = 'smtp.mailgun.org';
		$phpmailer->Port = (bool) $secure ? 465 : 587;
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = $username;
		$phpmailer->Password = $password;
	}

	/**
	 * Deactivate this plugin and die
	 *
	 * Used to deactivate the plugin when files critical to it's operation can not be loaded
	 *
	 * @since 0.1
	 * @return none
	 */
	function deactivate_and_die( $file ) {
		load_plugin_textdomain( 'mailgun', false, 'mailgun/languages' );
		$message = sprintf( __( "Mailgun has been automatically deactivated because the file <strong>%s</strong> is missing. Please reinstall the plugin and reactivate." ), $file );
		if ( ! function_exists( 'deactivate_plugins' ) )
			include( ABSPATH . 'wp-admin/includes/plugin.php' );
		deactivate_plugins( __FILE__ );
		wp_die( $message );
	}

}

if ( is_admin() ) {
	if ( @include( dirname( __FILE__ ) . '/includes/admin.php' ) ) {
		$mailgunAdmin = new MailgunAdmin();
	} else {
		Mailgun::deactivate_and_die( dirname( __FILE__ ) . '/includes/admin.php' );
	}
} else {
	$mailgun = new Mailgun();
}
