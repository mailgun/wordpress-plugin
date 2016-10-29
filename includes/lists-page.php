<?php

	global $mailgun;

	// check mailgun domain & api key
	$missing_error = '';
	$api_key = $this->get_option( 'apiKey' );
	$mailgun_domain = $this->get_option( 'domain' );
	if($api_key != ''){
		if($mailgun_domain == ''){
			$missing_error = '<strong style="color:red;">Missing or invalid Mailgun Domain</strong>. ';
		}
	} else {
		$missing_error = '<strong style="color:red;">Missing or invalid API Key</strong>. ';
	}
	
	// import available lists
	$lists_arr = $mailgun->get_lists();
	
?>

<div class="wrap">

	<div id="icon-options-general" class="icon32"><br /></div>

	<span class="alignright">
		<a target="_blank" href="http://www.mailgun.com/">
			<img src="https://2e6874288eee3bf7ca22-d122329f808928cff1e9967578106854.ssl.cf1.rackcdn.com/mailgun-logo.png" alt="Mailgun" />
		</a>
	</span>

	<h2><?php _e( 'Mailgun Lists' , 'mailgun' ); ?></h2>
	
	<?php settings_fields( 'mailgun' ); ?>

	<h3><?php _e( 'Available Mailing Lists' , 'mailgun' ); ?> | <a href="/wp-admin/options-general.php?page=mailgun">Back to settings</a></h3>

	<p><?php _e( "{$missing_error}You must use a valid Mailgun domain name and API key to access lists", 'mailgun' ); ?></p>
	
	<div id="mailgun-lists" style="margin-top:20px;">

		<?php if( !empty($lists_arr) ) : ?>

			<table class="wp-list-table widefat fixed striped pages">

				<tr>
					<th>List Address</th>
					<th>Description</th>
					<th>Shortcode</th>
				</tr>

				<?php foreach($lists_arr as $list) : ?>

					<tr>
						<td><?php echo $list['address']; ?></td>
						<td><?php echo $list['description']; ?></td>
						<td>
							[mailgun id="<?php echo $list['address']; ?>"]
						</td>
					</tr>

				<?php endforeach; ?>

			</table>

			<h3>Multi-list subscription</h3>
			<p>
				<?php _e( 'To allow users to subscribe to multiple lists on a single form, comma-separate the Mailgun list ids.', 'mailgun' ); ?></p>
			<p class="description">
				<?php _e( '<strong>Example:</strong> [mailgun id="list1@mydomain.com,list2@mydomain.com"]'); ?>
			</p>

		<?php endif; ?>

	</div>
</div>
