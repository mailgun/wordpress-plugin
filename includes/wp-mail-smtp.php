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

// Include MG filter functions
if (!include __DIR__ .'/mg-filter.php') {
    (new Mailgun)->deactivate_and_die(__DIR__ .'/mg-filter.php');
}

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
    }

    $tmp = $last_error;
    $last_error = $error;

    return $tmp;
}

/**
 * Debugging output function for PHPMailer.
 *
 * @param string $str   Log message
 * @param string $level Logging level
 *
 * @return void
 *
 * @since 1.5.7
 */
function mg_smtp_debug_output(string $str, $level)
{
    if (defined('MG_DEBUG_SMTP') && MG_DEBUG_SMTP) {
        error_log("PHPMailer [$level] $str");
    }
}

/**
 * Capture and store the failure message from PHPmailer so the user will
 * actually know what is wrong.
 *
 * @param WP_Error $error Error raised by WordPress/PHPmailer
 *
 * @return void
 *
 * @since 1.5.7
 */
function wp_mail_failed($error)
{
    if (is_wp_error($error)) {
        mg_smtp_last_error($error->get_error_message());
    } else {
        mg_smtp_last_error($error->__toString());
    }
}

/**
 * Provides a `wp_mail` compatible filter for SMTP sends through the
 * WordPress PHPmailer transport.
 *
 * @param array $args Compacted array of arguments.
 *
 * @return array Compacted array of arguments.
 *
 * @since 1.5.8
 */
function mg_smtp_mail_filter(array $args)
{
    // Extract the arguments from array to ($to, $subject, $message, $headers, $attachments)
    extract($args, EXTR_OVERWRITE);

    // $headers and $attachments are optional - make sure they exist
    $headers = (!isset($headers)) ? '' : $headers;
    $attachments = (!isset($attachments)) ? array() : $attachments;

    $mg_opts = get_option('mailgun');
    $mg_headers = mg_parse_headers($headers);

    // Filter the `From:` header
    $from_header = (isset($mg_headers['From'])) ? $mg_headers['From'][0] : null;

    list($from_name, $from_addr) = [null, null];
    if (!is_null($from_header)) {
        $content = $from_header['value'];
        $boundary = $from_header['boundary'];
        $parts = $from_header['parts'];

        if (strpos($content, '<') !== false) {
            $from_name = substr($content, 0, strpos($content, '<') - 1);
            $from_name = str_replace('"', '', $from_name);
            $from_name = trim($from_name);

            $from_addr = substr($content, strpos($content, '<') + 1);
            $from_addr = str_replace('>', '', $from_addr);
            $from_addr = trim($from_addr);
        } else {
            $from_addr = trim($content);
        }
    }

    if (!isset($from_name)) {
        $from_name = null;
    }

    if (!isset($from_addr)) {
        $from_addr = null;
    }

    $from_name = mg_detect_from_name($from_name);
    $from_addr = mg_detect_from_address($from_addr);

    $from_header['value'] = sprintf('%s <%s>', $from_name, $from_addr);
    $mg_headers['From'] = array($from_header);

    // Header compaction
    $headers = mg_dump_headers($mg_headers);

    return compact('to', 'subject', 'message', 'headers', 'attachments');
}

