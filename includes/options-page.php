<?php

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

$mailgun_domain_const = ((defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN) ? MAILGUN_DOMAIN : null);
$mailgun_domain = $mailgun_domain_const ? $mailgun_domain_const : $this->get_option('domain');

$mailgun_region_const = ((defined('MAILGUN_REGION') && MAILGUN_REGION) ? MAILGUN_REGION : null);
$mailgun_region = $mailgun_region_const ? $mailgun_region_const : $this->get_option('region');

$mailgun_api_key_const = ((defined('MAILGUN_APIKEY') && MAILGUN_APIKEY) ? MAILGUN_APIKEY : null);
$mailgun_api_key = $mailgun_api_key_const ? $mailgun_api_key_const : $this->get_option('apiKey');

$mailgun_username_const = ((defined('MAILGUN_USERNAME') && MAILGUN_USERNAME) ? MAILGUN_USERNAME : null);
$mailgun_username = $mailgun_username_const ? $mailgun_username_const : $this->get_option('username');

$mailgun_password_const = ((defined('MAILGUN_PASSWORD') && MAILGUN_PASSWORD) ? MAILGUN_PASSWORD : null);
$mailgun_password = $mailgun_password_const ? $mailgun_password_const : $this->get_option('password');

$mailgun_sectype_const = ((defined('MAILGUN_SECTYPE') && MAILGUN_SECTYPE) ? MAILGUN_SECTYPE : null);
$mailgun_sectype = $mailgun_sectype_const ? $mailgun_sectype_const : $this->get_option('sectype');

$mailgun_from_name_const = ((defined('MAILGUN_FROM_NAME') && MAILGUN_FROM_NAME) ? MAILGUN_FROM_NAME : null);
$mailgun_from_name = $mailgun_from_name_const ? $mailgun_from_name_const : $this->get_option('from-name');

$mailgun_from_address_const = ((defined('MAILGUN_FROM_ADDRESS') && MAILGUN_FROM_ADDRESS) ? MAILGUN_FROM_ADDRESS : null);
$mailgun_from_address = $mailgun_from_address_const ? $mailgun_from_address_const : $this->get_option('from-address');

$mailgun_secure_const = (defined('MAILGUN_SECURE') ? MAILGUN_SECURE : null);
$mailgun_secure = !is_null($mailgun_secure_const) ? ((string)(1 * $mailgun_secure_const)) : $this->get_option('secure');

$mailgun_use_api_const = (defined('MAILGUN_USEAPI') ? MAILGUN_USEAPI : null);
$mailgun_use_api = !is_null($mailgun_use_api_const) ? ((string)(1 * $mailgun_use_api_const)) : $this->get_option('useAPI');


?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br/></div>
	<span class="alignright">
				<a target="_blank" href="http://www.mailgun.com/">
					<img src="https://assets.mailgun.com/img/mailgun.svg" alt="Mailgun" style="width:10em;"/>
				</a>
			</span>
	<h2><?php _e('Mailgun', 'mailgun'); ?></h2>

	<p>
		<?php
			$url = 'https://www.mailgun.com';
			$link = sprintf(
				wp_kses(
					__('A <a href="%1$s" target="%2$s">Mailgun</a> account is required to use this plugin and the Mailgun service.', 'mailgun'),
					array('a' => array(
							'href' => array(),
							'target' => array()
						)
					)
				), esc_url($url), '_blank'
			);
			echo $link;
		?>
	</p>

	<p>
		<?php
			$url = 'https://signup.mailgun.com/new/signup';
			$link = sprintf(
				wp_kses(
					__('If you need to register for an account, you can do so at <a href="%1$s" target="%2$s">Mailgun.com</a>.', 'mailgun'),
					array('a' => array(
							'href' => array(),
							'target' => array()
						)
					)
				), esc_url($url), '_blank'
			);
			echo $link;
		?>
	</p>

	<h3><?php _e('Configuration', 'mailgun'); ?></h3>
	<form id="mailgun-form" action="options.php" method="post">
		<?php settings_fields('mailgun'); ?>

		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<?php _e('Select Your Region', 'mailgun'); ?>
				</th>
				<td>
					<?php if ($mailgun_region_const): ?>
						<input type="hidden" name="mailgun[region]" value="<?php echo $mailgun_region ?>">
					<?php endif ?>

					<select id="mailgun-region" name="mailgun[region]" <?php echo $mailgun_region_const ? 'disabled="disabled"' : '' ?>>
						<option value="us"<?php selected('us', $mailgun_region); ?>><?php _e('U.S./North America', 'mailgun') ?></option>
						<option value="eu"<?php selected('eu', $mailgun_region); ?>><?php _e('Europe', 'mailgun') ?></option>
					</select>
					<p class="description">
						<?php
							_e('Choose a region - U.S./North America or Europe - from which to send email, and to store your customer data. Please note that your sending domain must be set up in whichever region you choose.', 'mailgun');
						?>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Use HTTP API', 'mailgun'); ?>
				</th>
				<td>
					<?php if (!is_null($mailgun_use_api_const)): ?>
						<input type="hidden" name="mailgun[useAPI]" value="<?php echo $mailgun_use_api ?>">
					<?php endif ?>

					<select id="mailgun-api" name="mailgun[useAPI]" <?php echo !is_null($mailgun_use_api_const) ? 'disabled="disabled"' : '' ?>>
						<option value="1"<?php selected('1', $mailgun_use_api); ?>><?php _e('Yes', 'mailgun'); ?></option>
						<option value="0"<?php selected('0', $mailgun_use_api); ?>><?php _e('No', 'mailgun'); ?></option>
					</select>
					<p class="description">
						<?php
							_e('Set this to "No" if your server cannot make outbound HTTP connections or if emails are not being delivered. "No" will cause this plugin to use SMTP. Default "Yes".', 'mailgun');
						?>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Mailgun Domain Name', 'mailgun'); ?>
				</th>
				<td>
					<input type="text" class="regular-text"
						   name="mailgun[domain]"
						   value="<?php esc_attr_e($mailgun_domain); ?>"
						   placeholder="samples.mailgun.org"
						   <?php echo $mailgun_domain_const ? 'readonly="readonly"' : '' ?>
					/>
					<p class="description">
						<?php _e('Your Mailgun Domain Name.', 'mailgun'); ?>
					</p>
				</td>
			</tr>
			<tr valign="top" class="mailgun-api">
				<th scope="row">
					<?php _e('API Key', 'mailgun'); ?>
				</th>
				<td>
					<input type="text" class="regular-text" name="mailgun[apiKey]"
						   value="<?php esc_attr_e($mailgun_api_key); ?>"
						   placeholder="key-3ax6xnjp29jd6fds4gc373sgvjxteol0"
						   <?php echo $mailgun_api_key_const ? 'readonly="readonly"' : '' ?>
					/>
					<p class="description">
						<?php
							_e('Your Mailgun API key. Only valid for use with the API.', 'mailgun');
						?>
					</p>
				</td>
			</tr>
			<tr valign="top" class="mailgun-smtp">
				<th scope="row">
					<?php _e('Username', 'mailgun'); ?>
				</th>
				<td>
					<input type="text" class="regular-text"
						   name="mailgun[username]"
						   value="<?php esc_attr_e($mailgun_username); ?>"
						   placeholder="postmaster"
						   <?php echo $mailgun_username_const ? 'readonly="readonly"' : '' ?>
					/>
					<p class="description">
						<?php
							_e('Your Mailgun SMTP username. Only valid for use with SMTP.', 'mailgun');
						?>
					</p>
				</td>
			</tr>
			<tr valign="top" class="mailgun-smtp">
				<th scope="row">
					<?php _e('Password', 'mailgun'); ?>
				</th>
				<td>
					<input type="text" class="regular-text"
						   name="mailgun[password]"
						   value="<?php esc_attr_e($mailgun_password); ?>"
						   placeholder="my-password"
						   <?php echo $mailgun_password_const ? 'readonly="readonly"' : '' ?>
					/>
					<p class="description">
						<?php
							_e('Your Mailgun SMTP password that goes with the above username. Only valid for use with SMTP.', 'mailgun');
						?>
					</p>
				</td>
			</tr>
			<tr valign="top" class="mailgun-smtp">
				<th scope="row">
					<?php _e('Use Secure SMTP', 'mailgun'); ?>
				</th>
				<td>
					<?php if (!is_null($mailgun_secure_const)): ?>
						<input type="hidden" name="mailgun[secure]" value="<?php echo $mailgun_secure ?>">
					<?php endif ?>

					<select name="mailgun[secure]" <?php echo !is_null($mailgun_secure_const) ? 'disabled="disabled"' : '' ?>>
						<option value="1"<?php selected('1', $mailgun_secure); ?>><?php _e('Yes', 'mailgun'); ?></option>
						<option value="0"<?php selected('0', $mailgun_secure); ?>><?php _e('No', 'mailgun'); ?></option>
					</select>
					<p class="description">
						<?php
							_e('Set this to "No" if your server cannot establish SSL SMTP connections or if emails are not being delivered. If you set this to "No" your password will be sent in plain text. Only valid for use with SMTP. Default "Yes".', 'mailgun');
						?>
					</p>
				</td>
			</tr>
			<tr valign="top" class="mailgun-smtp">
				<th scope="row">
					<?php _e('Security Type', 'mailgun'); ?>
				</th>
				<td>
					<?php if ($mailgun_sectype_const): ?>
						<input type="hidden" name="mailgun[sectype]" value="<?php echo $mailgun_sectype ?>">
					<?php endif ?>

					<select name="mailgun[sectype]" <?php echo $mailgun_sectype_const ? 'disabled="disabled"' : '' ?>>
						<option value="ssl"<?php selected('ssl', $mailgun_sectype); ?>>SSL</option>
						<option value="tls"<?php selected('tls', $mailgun_sectype); ?>>TLS</option>
					</select>
					<p class="description">
						<?php
							_e('Leave this at "TLS" unless mail sending fails. This option only matters for Secure SMTP. Default "TLS".', 'mailgun');
						?>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Click Tracking', 'mailgun'); ?>
				</th>
				<td>
					<select name="mailgun[track-clicks]">
						<option value="htmlonly"<?php selected('htmlonly', $this->get_option('track-clicks')); ?>><?php _e('HTML Only', 'mailgun'); ?></option>
						<option value="yes"<?php selected('yes', $this->get_option('track-clicks')); ?>><?php _e('Yes', 'mailgun'); ?></option>
						<option value="no"<?php selected('no', $this->get_option('track-clicks')); ?>><?php _e('No', 'mailgun'); ?></option>
					</select>
					<p class="description">
						<?php
							$url = 'https://documentation.mailgun.com/en/latest/user_manual.html#tracking-clicks';
							$link = sprintf(
								wp_kses(
									__('If enabled, Mailgun will track links. <a href="%1$s" target="%2$s">Open Tracking Documentation</a>.', 'mailgun'),
									array('a' => array(
										'href' => array(),
										'target' => array()
										)
									)
								), esc_url($url), '_blank'
							);
							echo $link;
						?>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Open Tracking', 'mailgun'); ?>
				</th>
				<td>
					<select name="mailgun[track-opens]">
						<option value="1"<?php selected('1', $this->get_option('track-opens')); ?>><?php _e('Yes', 'mailgun'); ?></option>
						<option value="0"<?php selected('0', $this->get_option('track-opens')); ?>><?php _e('No', 'mailgun'); ?></option>
					</select>
					<p class="description">
						<?php
							$url = 'https://documentation.mailgun.com/en/latest/user_manual.html#tracking-opens';
							$link = sprintf(
								wp_kses(
									__('If enabled, HTML messages will include an open tracking beacon. <a href="%1$s" target="%2$s">Open Tracking Documentation</a>.', 'mailgun'),
									array('a' => array(
										'href' => array(),
										'target' => array()
										)
									)
								), esc_url($url), '_blank'
							);
							echo $link;
						?>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('From Address', 'mailgun'); ?>
				</th>
				<td>
					<input type="text"
						   class="regular-text"
						   name="mailgun[from-address]"
						   value="<?php esc_attr_e($mailgun_from_address); ?>"
						   placeholder="wordpress@mydomain.com"
						   <?php echo $mailgun_from_address_const ? 'readonly="readonly"' : '' ?>
					/>
					<p class="description">
						<?php
							_e('The &lt;address@mydomain.com&gt; part of the sender information (<code>"Excited User &lt;user@samples.mailgun.org&gt;"</code>). This address will appear as the `From` address on sent mail. <strong>It is recommended that the @mydomain portion matches your Mailgun sending domain.</strong>', 'mailgun');
						?>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('From Name', 'mailgun'); ?>
				</th>
				<td>
					<input type="text" class="regular-text"
						   name="mailgun[from-name]"
						   value="<?php esc_attr_e($mailgun_from_name); ?>"
						   placeholder="WordPress"
						   <?php echo $mailgun_from_name_const ? 'readonly="readonly"' : '' ?>
					/>
					<p class="description">
						<?php
							_e('The "User Name" part of the sender information (<code>"Excited User &lt;user@samples.mailgun.org&gt;"</code>).', 'mailgun');
						?>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Override "From" Details', 'mailgun'); ?>
				</th>
				<td>
					<select name="mailgun[override-from]">
						<option value="1"<?php selected('1', $this->get_option('override-from', null, '0')); ?>><?php _e('Yes', 'mailgun'); ?></option>
						<option value="0"<?php selected('0', $this->get_option('override-from', null, '0')); ?>><?php _e('No', 'mailgun'); ?></option>
					</select>
					<p class="description">
						<?php
							_e('If enabled, all emails will be sent with the above "From Name" and "From Address", regardless of values set by other plugins. Useful for cases where other plugins don\'t play nice with our "From Name" / "From Address" setting.', 'mailgun');
						?>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Tag', 'mailgun'); ?>
				</th>
				<td>
					<input type="text" class="regular-text"
						   name="mailgun[campaign-id]"
						   value="<?php esc_attr_e($this->get_option('campaign-id')); ?>"
						   placeholder="tag"
					/>
					<p class="description">
						<?php
							_e('If added, this tag will exist on every outbound message. Statistics will be populated in the Mailgun Control Panel. Use a comma to define multiple tags. ', 'mailgun');
							_e('Learn more about', 'mailgun');

							$url1 = 'https://documentation.mailgun.com/en/latest/user_manual.html#tracking-messages';
							$url2 = 'https://documentation.mailgun.com/en/latest/user_manual.html#tagging';
							$link = sprintf(
								wp_kses(
									__('<a href="%1$s" target="%3$s">Tracking</a> and <a href="%2$s" target="%3$s">Tagging</a>', 'mailgun'),
									array('a' => array(
										'href' => array(),
										'target' => array()
										)
									)
								), esc_url($url1), esc_url($url2), '_blank'
							);
							echo $link;
						?>
					</p>
				</td>
			</tr>
		</table>
		<h3><?php _e('Lists', 'mailgun'); ?></h3>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<?php _e('Shortcode', 'mailgun'); ?>
				</th>
				<td>
					<div>
						<code>[mailgun id="<em>{mailgun list id}</em>" collect_name="true"]</code>
					</div>
					<div>
						<p class="description">
							<?php
								_e('Use the shortcode above to associate a widget instance with a mailgun list', 'mailgun');
							?>
						</p>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Lists', 'mailgun'); ?>
				</th>
				<td>
					<?php
						$url = '?page=mailgun-lists';

						$link = sprintf(
							wp_kses(
								__('<a href="%1$s" target="%2$s">View available lists</a>.', 'mailgun'),
								array('a' => array(
									'href' => array(),
									)
								)
							), esc_url($url)
						);
						echo $link;
					?>
				</td>
			</tr>
		</table>
		<p>
			<?php
				_e('Before attempting to test the configuration, please click "Save Changes".', 'mailgun');
			?>
		</p>
		<p class="submit">
			<input type="submit"
				   class="button-primary"
				   value="<?php _e('Save Changes', 'mailgun'); ?>"
			/>
			<input type="button"
				   id="mailgun-test"
				   class="button-secondary"
				   value="<?php _e('Test Configuration', 'mailgun'); ?>"
			/>
		</p>
	</form>
</div>
