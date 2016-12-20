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

global $mailgun;

// check mailgun domain & api key
$missing_error = '';
$api_key = $this->get_option('apiKey');
$mailgun_domain = $this->get_option('domain');
if ($api_key != '') {
    if ($mailgun_domain == '') {
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
            <img src="http://www.mailgun.com/static/img/mailgun.svg" alt="Mailgun" style="width: 10em;"/>
        </a>
    </span>

    <h2><?php _e('Mailgun Lists', 'mailgun'); ?></h2>

    <?php settings_fields('mailgun'); ?>

    <h3><?php _e('Available Mailing Lists', 'mailgun'); ?> | <a href="/wp-admin/options-general.php?page=mailgun">Back to settings</a></h3>

    <p><?php _e("{$missing_error}You must use a valid Mailgun domain name and API key to access lists", 'mailgun'); ?></p>

    <div id="mailgun-lists" style="margin-top:20px;">

        <?php if (!empty($lists_arr)) : ?>

            <table class="wp-list-table widefat fixed striped pages">

                <tr>
                    <th>List Address</th>
                    <th>Description</th>
                    <th>Shortcode</th>
                </tr>

                <?php foreach ($lists_arr as $list) : ?>

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
                <?php _e('To allow users to subscribe to multiple lists on a single form, comma-separate the Mailgun list ids.', 'mailgun'); ?></p>
            <p class="description">
                <?php _e('<strong>Example:</strong> [mailgun id="list1@mydomain.com,list2@mydomain.com"]'); ?>
            </p>

        <?php endif; ?>

    </div>
</div>
