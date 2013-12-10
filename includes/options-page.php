		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br /></div>
			<span class="alignright"><a target="_blank" href="http://www.mailgun.com/"><img src="https://2e6874288eee3bf7ca22-d122329f808928cff1e9967578106854.ssl.cf1.rackcdn.com/mailgun-logo.png" alt="Mailgun" /></a></span>
			<h2><?php _e( 'Mailgun' , 'mailgun' ); ?></h2>
			<p>A <a target="_blank" href="http://www.mailgun.com/">Mailgun</a> account is required to use this plugin and the Mailgun service.</p>
			<p>If you need to register for an account, you can do so at <a target="_blank" href="http://www.mailgun.com/">http://www.mailgun.com/</a>.</p>
			<form id="mailgun-form" action="options.php" method="post">
				<?php settings_fields( 'mailgun' ); ?>
				<h3><?php _e( 'Configuration' , 'mailgun' ); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php _e( 'Use HTTP API' , 'mailgun' ); ?>
						</th>
						<td>
							<select id="mailgun-api" name="mailgun[useAPI]">
								<option value="1"<?php selected( '1' , $this->get_option( 'useAPI' ) ); ?>><?php _e( 'Yes' , 'mailgun' ); ?></option>
								<option value="0"<?php selected( '0' , $this->get_option( 'useAPI' ) ); ?>><?php _e( 'No' , 'mailgun' ); ?></option>
							</select>
							<p class="description"><?php _e( 'Set this to "No" if your server cannot make outbound HTTP connections or if emails are not being delivered. "No" will cause this plugin to use SMTP. Default "Yes".', 'mailgun' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e( 'Mailgun Domain Name' , 'mailgun' ); ?>
						</th>
						<td>
							<input type="text" class="regular-text" name="mailgun[domain]" value="<?php esc_attr_e( $this->get_option( 'domain' ) ); ?>" placeholder="samples.mailgun.org" />
							<p class="description"><?php _e( 'Your Mailgun Domain Name.', 'mailgun' ); ?></p>
						</td>
					</tr>
					<tr valign="top" class="mailgun-api">
						<th scope="row">
							<?php _e( 'API Key' , 'mailgun' ); ?>
						</th>
						<td>
							<input type="text" class="regular-text" name="mailgun[apiKey]" value="<?php esc_attr_e( $this->get_option( 'apiKey' ) ); ?>" placeholder="key-3ax6xnjp29jd6fds4gc373sgvjxteol0" />
							<p class="description"><?php _e( 'Your Mailgun API key, that starts with and includes "key-". Only valid for use with the API.', 'mailgun' ); ?></p>
						</td>
					</tr>
					<tr valign="top" class="mailgun-smtp">
						<th scope="row">
							<?php _e( 'Username' , 'mailgun' ); ?>
						</th>
						<td>
							<input type="text" class="regular-text" name="mailgun[username]" value="<?php esc_attr_e( $this->get_option( 'username' ) ); ?>" placeholder="postmaster" />
							<p class="description"><?php _e( 'Your Mailgun SMTP username. Only valid for use with SMTP.', 'mailgun' ); ?></p>
						</td>
					</tr>
					<tr valign="top" class="mailgun-smtp">
						<th scope="row">
							<?php _e( 'Password' , 'mailgun' ); ?>
						</th>
						<td>
							<input type="text" class="regular-text" name="mailgun[password]" value="<?php esc_attr_e( $this->get_option( 'password' ) ); ?>" placeholder="my-password" />
							<p class="description"><?php _e( 'Your Mailgun SMTP password that goes with the above username. Only valid for use with SMTP.', 'mailgun' ); ?></p>
						</td>
					</tr>
					<tr valign="top" class="mailgun-smtp">
						<th scope="row">
							<?php _e( 'Use Secure SMTP' , 'mailgun' ); ?>
						</th>
						<td>
							<select name="mailgun[secure]">
								<option value="1"<?php selected( '1' , $this->get_option( 'secure' ) ); ?>><?php _e( 'Yes' , 'mailgun' ); ?></option>
								<option value="0"<?php selected( '0' , $this->get_option( 'secure' ) ); ?>><?php _e( 'No' , 'mailgun' ); ?></option>
							</select>
							<p class="description"><?php _e( 'Set this to "No" if your server cannot establish SSL SMTP connections or if emails are not being delivered. If you set this to "No" your password will be sent in plain text. Only valid for use with SMTP. Default "Yes".', 'mailgun' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e( 'Click Tracking' , 'mailgun' ); ?>
						</th>
						<td>
							<select name="mailgun[track-clicks]">
								<option value="yes"<?php selected( 'yes' , $this->get_option( 'track-clicks' ) ); ?>><?php _e( 'Yes' , 'mailgun' ); ?></option>
								<option value="htmlonly"<?php selected( 'htmlonly' , $this->get_option( 'track-clicks' ) ); ?>><?php _e( 'HTML Only' , 'mailgun' ); ?></option>
								<option value="no"<?php selected( 'no' , $this->get_option( 'track-clicks' ) ); ?>><?php _e( 'No' , 'mailgun' ); ?></option>
							</select>
							<p class="description"><?php _e( 'If enabled, Mailgun will  and track links.', 'mailgun' ); ?> <a href="http://documentation.mailgun.com/user_manual.html#tracking-clicks" target="_blank">Click Tracking Documentation</a></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e( 'Open Tracking' , 'mailgun' ); ?>
						</th>
						<td>
							<select name="mailgun[track-opens]">
								<option value="1"<?php selected( '1' , $this->get_option( 'track-opens' ) ); ?>><?php _e( 'Yes' , 'mailgun' ); ?></option>
								<option value="0"<?php selected( '0' , $this->get_option( 'track-opens' ) ); ?>><?php _e( 'No' , 'mailgun' ); ?></option>
							</select>
							<p class="description"><?php _e( 'If enabled, HTML messages will include an open tracking beacon.', 'mailgun' ); ?> <a href="http://documentation.mailgun.com/user_manual.html#tracking-opens" target="_blank">Open Tracking Documentation</a></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e( 'Campaign ID' , 'mailgun' ); ?>
						</th>
						<td>
							<input type="text" class="regular-text" name="mailgun[campaign-id]" value="<?php esc_attr_e( $this->get_option( 'campaign-id' ) ); ?>" placeholder="campaign-id" />
							<p class="description"><?php _e( 'If added, this campaign will exist on every outbound message. Statistics will be populated in the Mailgun Control Panel. Use a comma to define multiple campaigns.', 'mailgun' ); ?>  <a href="http://documentation.mailgun.com/user_manual.html#campaign-analytics" target="_blank">Campaign Documentation</a></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e( 'Tag' , 'mailgun' ); ?>
						</th>
						<td>
							<input type="text" class="regular-text" name="mailgun[tag]" value="<?php esc_attr_e( $this->get_option( 'tag' ) ); ?>" placeholder="tag" />
							<p class="description"><?php _e( 'If added, this tag will exist on every outbound message. Statistics will be populated in the Mailgun Control Panel. Use a comma to define multiple tags.', 'mailgun' ); ?> <a href="http://documentation.mailgun.com/user_manual.html#tagging" target="_blank">Tagging Documentation</a></p>
						</td>
					</tr>
				</table>
				<p><?php _e( 'Before attempting to test the configuration, please click "Save Changes".', 'mailgun' ); ?></p>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' , 'mailgun' ); ?>" />
					<input type="button" id="mailgun-test" class="button-secondary" value="<?php _e( 'Test Configuration', 'mailgun' ); ?>" />
				</p>
			</form>
		</div>
