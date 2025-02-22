<?php
/**
 * Mailgun-wordpress-plugin - Sending mail from Wordpress using Mailgun
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

// Include MG filter functions
if ( ! include __DIR__ . '/mg-filter.php') {
    ( new Mailgun() )->deactivate_and_die(__DIR__ . '/mg-filter.php');
}

/**
 * g_api_last_error is a compound getter/setter for the last error that was
 * encountered during a Mailgun API call.
 *
 * @param string|null $error OPTIONAL
 * @return string|null    Last error that occurred.
 * @since    1.5.0
 */
function mg_api_last_error( string $error = null ): ?string {
    static $last_error;

    if (null === $error) {
        return $last_error;
    }

    do_action('mailgun_error_track', $error);
    $tmp        = $last_error;
    $last_error = $error;

    return $tmp;
}

/*
 * Wordpress filter to mutate a `To` header to use recipient variables.
 * Uses the `mg_use_recipient_vars_syntax` filter to apply the actual
 * change. Otherwise, just a list of `To` addresses will be returned.
 *
 * @param string|array $to_addrs Array or comma-separated list of email addresses to mutate.
 *
 * @return array Array containing list of `To` addresses and recipient vars array
 *
 * @since 1.5.7
 */
add_filter('mg_mutate_to_rcpt_vars', 'mg_mutate_to_rcpt_vars_cb');

/**
 * @param string|array $to_addrs Array or comma-separated list of email addresses to mutate.
 * @return array
 * @throws JsonException
 */
function mg_mutate_to_rcpt_vars_cb( $to_addrs ): array {
    if (is_string($to_addrs)) {
        $to_addrs = explode(',', $to_addrs);
    }

    if (has_filter('mg_use_recipient_vars_syntax')) {
        $rcpt_vars     = array();
        $use_rcpt_vars = apply_filters('mg_use_recipient_vars_syntax', null);
        if ($use_rcpt_vars) {

            $idx = 0;
            foreach ($to_addrs as $addr) {
                $rcpt_vars[ $addr ] = array( 'batch_msg_id' => $idx );
                ++$idx;
            }

            return array(
                'to'        => '%recipient%',
                'rcpt_vars' => json_encode($rcpt_vars, JSON_THROW_ON_ERROR),
            );
        }
    }

    return array(
        'to'        => $to_addrs,
        'rcpt_vars' => null,
    );
}

/**
 * wp_mail function to be loaded in to override the core wp_mail function
 * from wp-includes/pluggable.php.
 *
 * Based off of the core wp_mail function, but with modifications required to
 * send email using the Mailgun HTTP API
 *
 * @param string|array                   $to          Array or comma-separated list of email addresses to send message.
 * @param string                         $subject     Email subject
 * @param string                         $message     Message contents
 * @param string|array                   $headers     Optional. Additional headers.
 * @param string|array                   $attachments Optional. Files to attach.
 *
 * @return    bool    Whether the email contents were sent successfully.
 *
 * @global PHPMailer\PHPMailer\PHPMailer $phpmailer
 */
if ( ! function_exists('wp_mail')) {

    /**
     * @param string $to
     * @param string $subject
     * @param mixed  $message
     * @param array  $headers
     * @param array  $attachments
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     */
    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
        $mailgun = get_option('mailgun');
        $region  = ( defined('MAILGUN_REGION') && MAILGUN_REGION ) ? MAILGUN_REGION : $mailgun['region'];
        $apiKey  = ( defined('MAILGUN_APIKEY') && MAILGUN_APIKEY ) ? MAILGUN_APIKEY : $mailgun['apiKey'];
        $domain  = ( defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN ) ? MAILGUN_DOMAIN : $mailgun['domain'];

        if (empty($apiKey) || empty($domain)) {
            return false;
        }

        // If a region is not set via defines or through the options page, default to US region.
        if ( ! ( $region )) {
            error_log('[Mailgun] No region configuration was found! Defaulting to US region.');
            $region = 'us';
        }

        // Respect WordPress core filters
        $atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

        if (isset($atts['to'])) {
            $to = $atts['to'];
        }

        if ( ! is_array($to)) {
            $to = explode(',', $to);
        }

        if (isset($atts['subject'])) {
            $subject = $atts['subject'];
        }

        if (isset($atts['message'])) {
            $message = $atts['message'];
        }

        if (isset($atts['headers'])) {
            $headers = $atts['headers'];
        }

        if (isset($atts['attachments'])) {
            $attachments = $atts['attachments'];
        }

        if ( ! is_array($attachments)) {
            $attachments = explode("\n", str_replace("\r\n", "\n", $attachments));
        }

        $cc  = array();
        $bcc = array();

        // Headers
        if (empty($headers)) {
            $headers = array();
        } else {
            if ( ! is_array($headers)) {
                // Explode the headers out, so this function can take both
                // string headers and an array of headers.
                $tempheaders = explode("\n", str_replace("\r\n", "\n", $headers));
            } else {
                $tempheaders = $headers;
            }
            $headers = array();
            $cc      = array();
            $bcc     = array();

            // If it's actually got contents
            if ( ! empty($tempheaders)) {
                // Iterate through the raw headers
                foreach ( (array) $tempheaders as $header) {
                    if (strpos($header, ':') === false) {
                        if (false !== stripos($header, 'boundary=')) {
                            $parts    = preg_split('/boundary=/i', trim($header));
                            $boundary = trim(str_replace(array( "'", '"' ), '', $parts[1]));
                        }
                        continue;
                    }
                    // Explode them out
                    [$name, $content] = explode(':', trim($header), 2);

                    // Cleanup crew
                    $name    = trim($name);
                    $content = trim($content);

                    switch (strtolower($name)) {
                        // Mainly for legacy -- process a From: header if it's there
                        case 'from':
                            if (strpos($content, '<') !== false) {
                                $from_name = substr($content, 0, strpos($content, '<') - 1);
                                $from_name = str_replace('"', '', $from_name);
                                $from_name = trim($from_name);

                                $from_email = substr($content, strpos($content, '<') + 1);
                                $from_email = str_replace('>', '', $from_email);
                                $from_email = trim($from_email);
                            } else {
                                $from_email = trim($content);
                            }
                            break;
                        case 'content-type':
                            if (strpos($content, ';') !== false) {
                                [$type, $charset] = explode(';', $content);
                                $content_type     = trim($type);
                                if (false !== stripos($charset, 'charset=')) {
                                    $charset = trim(str_replace(array( 'charset=', '"' ), '', $charset));
                                } elseif (false !== stripos($charset, 'boundary=')) {
                                    $boundary = trim(str_replace(array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset));
                                    $charset  = '';
                                }
                            } else {
                                $content_type = trim($content);
                            }
                            break;
                        case 'cc':
                            $cc = array_merge( (array) $cc, explode(',', $content));
                            break;
                        case 'bcc':
                            $bcc = array_merge( (array) $bcc, explode(',', $content));
                            break;
                        default:
                            // Add it to our grand headers array
                            $headers[ trim($name) ] = trim($content);
                            break;
                    }
                }
            }
        }

        if ( ! isset($from_name)) {
            $from_name = null;
        }

        if ( ! isset($from_email)) {
            $from_email = null;
        }

        $from_name  = mg_detect_from_name($from_name);
        $from_email = mg_detect_from_address($from_email);
        $fromString = "{$from_name} <{$from_email}>";

        $body = array(
            'from'     => $fromString,
            'h:Sender' => $from_email,
            'to'       => $to,
            'subject'  => $subject,
        );

        $rcpt_data = apply_filters('mg_mutate_to_rcpt_vars', $to);
        if ( ! is_null($rcpt_data['rcpt_vars'])) {
            $body['recipient-variables'] = $rcpt_data['rcpt_vars'];
        }

        $body['o:tag'] = array();
        if (defined('MAILGUN_TRACK_CLICKS')) {
            $trackClicks = MAILGUN_TRACK_CLICKS;
        } else {
            $trackClicks = ! empty($mailgun['track-clicks']) ? $mailgun['track-clicks'] : 'no';
        }
        if (defined('MAILGUN_TRACK_OPENS')) {
            $trackOpens = MAILGUN_TRACK_OPENS;
        } else {
            $trackOpens = empty($mailgun['track-opens']) ? 'no' : 'yes';
        }

        if (isset($mailgun['suppress_clicks']) && $mailgun['suppress_clicks'] === 'yes') {
            $passwordResetSubject = __('Password Reset Request', 'mailgun') ?: __( 'Password Reset Request', 'woocommerce' );
            if ( ! empty($passwordResetSubject) && stripos($subject, $passwordResetSubject) !== false) {
                $trackClicks = 'no';
            }
        }

        $body['o:tracking-clicks'] = $trackClicks;
        $body['o:tracking-opens']  = $trackOpens;

        // this is the wordpress site tag
        if (isset($mailgun['tag'])) {
            $tags          = explode(',', str_replace(' ', '', $mailgun['tag']));
            $body['o:tag'] = $tags;
        }

        // campaign-id now refers to a list of tags which will be appended to the site tag
        if ( ! empty($mailgun['campaign-id'])) {
            $tags = explode(',', str_replace(' ', '', $mailgun['campaign-id']));
            if (empty($body['o:tag'])) {
                $body['o:tag'] = $tags;
            } elseif (is_array($body['o:tag'])) {
                $body['o:tag'] = array_merge($body['o:tag'], $tags);
            } else {
                $body['o:tag'] .= ',' . implode(',', $tags);
            }
        }

        /**
         * Filter tags.
         *
         * @param array  $tags        Mailgun tags.
         * @param string $to          To address.
         * @param string $subject     Subject line.
         * @param string $message     Message content.
         * @param array  $headers     Headers array.
         * @param array  $attachments Attachments array.
         * @param string $region      Mailgun region.
         * @param string $domain      Mailgun domain.
         *
         * @return array              Mailgun tags.
         */
        $body['o:tag'] = apply_filters('mailgun_tags', $body['o:tag'], $to, $subject, $message, $headers, $attachments, $region, $domain);

        if ( ! empty($cc) && is_array($cc)) {
            $body['cc'] = implode(', ', $cc);
        }

        if ( ! empty($bcc) && is_array($bcc)) {
            $body['bcc'] = implode(', ', $bcc);
        }

        // If we are not given a Content-Type in the supplied headers,
        // write the message body to a file and try to determine the mimetype
        // using get_mime_content_type.
        if ( ! isset($content_type)) {
            $tmppath = tempnam(get_temp_dir(), 'mg');
            $tmp     = fopen($tmppath, 'w+');

            fwrite($tmp, $message);
            fclose($tmp);

            $content_type = get_mime_content_type($tmppath, 'text/plain');

            unlink($tmppath);
        }

        // Allow external content type filter to function normally
        if (has_filter('wp_mail_content_type')) {
            $content_type = apply_filters(
                'wp_mail_content_type',
                $content_type
            );
        }

        if ('text/plain' === $content_type) {
            $body['text'] = $message;
        } elseif ('text/html' === $content_type) {
            $body['html'] = $message;
        } else {
            $body['text'] = $message;
            $body['html'] = $message;
        }

        // Some plugins, such as WooCommerce (@see WC_Email::handle_multipart()), to handle multipart/alternative with html
        // and plaintext messages hooks into phpmailer_init action to override AltBody property directly in $phpmailer,
        // so we should allow them to do this, and then get overridden plain text body from $phpmailer.
        // Partly, this logic is taken from original wp_mail function.
        if (false !== stripos($content_type, 'multipart')) {
            global $phpmailer;

            // (Re)create it, if it's gone missing.
            if ( ! ( $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer )) {
                require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
                require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
                require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
                $phpmailer = new PHPMailer\PHPMailer\PHPMailer(true);

                $phpmailer::$validator = static function ( $email ) {
                    return (bool) is_email($email);
                };
            }

            /**
             * Fires after PHPMailer is initialized.
             *
             * @param PHPMailer $phpmailer The PHPMailer instance (passed by reference).
             */
            do_action_ref_array('phpmailer_init', array( &$phpmailer ));

            $plainTextMessage = $phpmailer->AltBody;

            if ($plainTextMessage) {
                $body['text'] = $plainTextMessage;
            }
        }

        // If we don't have a charset from the input headers
        if ( ! isset($charset)) {
            $charset = get_bloginfo('charset');
        }

        // Set the content-type and charset
        $charset = apply_filters('wp_mail_charset', $charset);
        if (isset($headers['Content-Type'])) {
            if ( ! strstr($headers['Content-Type'], 'charset')) {
                $headers['Content-Type'] = rtrim($headers['Content-Type'], '; ') . "; charset={$charset}";
            }
        }

        $replyTo = ( defined('MAILGUN_REPLY_TO_ADDRESS') && MAILGUN_REPLY_TO_ADDRESS ) ? MAILGUN_REPLY_TO_ADDRESS : get_option('reply_to');
        if ( ! empty($replyTo)) {
            $headers['Reply-To'] = $replyTo;
        }

        // Set custom headers
        if ( ! empty($headers)) {
            foreach ( (array) $headers as $name => $content) {
                $body[ "h:{$name}" ] = $content;
            }
        }

        /*
         * Deconstruct post array and create POST payload.
         * This entire routine is because wp_remote_post does
         * not support files directly.
         */

        $payload = '';

        // First, generate a boundary for the multipart message.
        $boundary = sha1(uniqid('', true));

        // Allow other plugins to apply body changes before creating the payload.
        $body = apply_filters('mg_mutate_message_body', $body);
        if (($body_payload = mg_build_payload_from_body($body, $boundary)) != null) {
            $payload .= $body_payload;
        }

        // Allow other plugins to apply attachment changes before writing to the payload.
        $attachments = apply_filters('mg_mutate_attachments', $attachments);
        if (($attachment_payload = mg_build_attachments_payload($attachments, $boundary)) != null) {
            $payload .= $attachment_payload;
        }

        $payload .= '--' . $boundary . '--';

        $data = array(
            'body'    => $payload,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode("api:{$apiKey}"),
                'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
            ),
        );

        $endpoint = mg_api_get_region($region);
        $endpoint = ( $endpoint ) ?: 'https://api.mailgun.net/v3/';
        $url      = $endpoint . "{$domain}/messages";

        $isFallbackNeeded = false;
        try {
            $response = wp_remote_post($url, $data);
            if (is_wp_error($response)) {
                // Store WP error in last error.
                mg_api_last_error($response->get_error_message());

                $isFallbackNeeded = true;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response));

            if ( (int) $response_code !== 200 || ! isset($response_body->message)) {
                // Store response code and HTTP response message in last error.
                $response_message = wp_remote_retrieve_response_message($response);
                $errmsg           = "$response_code - $response_message";
                mg_api_last_error($errmsg);

                $isFallbackNeeded = true;
            }
            if ($response_body->message !== 'Queued. Thank you.') {
                mg_api_last_error($response_body->message);

                $isFallbackNeeded = true;
            }
        } catch (Throwable $throwable) {
            $isFallbackNeeded = true;
        }

        // Email Fallback

        if ($isFallbackNeeded) {
            global $phpmailer;

            // (Re)create it, if it's gone missing.
            if ( ! ( $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer )) {
                require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
                require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
                require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
                $phpmailer = new PHPMailer\PHPMailer\PHPMailer(true);

                $phpmailer::$validator = static function ( $email ) {
                    return (bool) is_email($email);
                };
            }

            // Empty out the values that may be set.
            $phpmailer->clearAllRecipients();
            $phpmailer->clearAttachments();
            $phpmailer->clearCustomHeaders();
            $phpmailer->clearReplyTos();
            $phpmailer->Body    = '';
            $phpmailer->AltBody = '';

            // Set "From" name and email.

            // If we don't have a name from the input headers.
            if ( ! isset($from_name)) {
                $from_name = 'WordPress';
            }

            /*
             * If we don't have an email from the input headers, default to wordpress@$sitename
             * Some hosts will block outgoing mail from this address if it doesn't exist,
             * but there's no easy alternative. Defaulting to admin_email might appear to be
             * another option, but some hosts may refuse to relay mail from an unknown domain.
             * See https://core.trac.wordpress.org/ticket/5007.
             */
            if ( ! isset($from_email)) {
                // Get the site domain and get rid of www.
                $sitename   = wp_parse_url(network_home_url(), PHP_URL_HOST);
                $from_email = 'wordpress@';

                if (null !== $sitename) {
                    if (str_starts_with($sitename, 'www.')) {
                        $sitename = substr($sitename, 4);
                    }

                    $from_email .= $sitename;
                }
            }

            /**
             * Filters the email address to send from.
             *
             * @param string $from_email Email address to send from.
             * @since 2.2.0
             */
            $from_email = apply_filters('wp_mail_from', $from_email);

            /**
             * Filters the name to associate with the "from" email address.
             *
             * @param string $from_name Name associated with the "from" email address.
             * @since 2.3.0
             */
            $from_name = apply_filters('wp_mail_from_name', $from_name);

            try {
                $phpmailer->setFrom($from_email, $from_name, false);
            } catch (PHPMailer\PHPMailer\Exception $e) {
                $mail_error_data                             = compact('to', 'subject', 'message', 'headers', 'attachments');
                $mail_error_data['phpmailer_exception_code'] = $e->getCode();

                /** This filter is documented in wp-includes/pluggable.php */
                do_action('wp_mail_failed', new WP_Error('wp_mail_failed', $e->getMessage(), $mail_error_data));

                return false;
            }

            // Set mail's subject and body.
            $phpmailer->Subject = $subject;
            $phpmailer->Body    = $message;

            // Set destination addresses, using appropriate methods for handling addresses.
            $address_headers = compact('to', 'cc', 'bcc', 'replyTo');

            foreach ($address_headers as $address_header => $addresses) {
                if (empty($addresses)) {
                    continue;
                }

                foreach ( (array) $addresses as $address) {
                    try {
                        // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>".
                        $recipient_name = '';

                        if (preg_match('/(.*)<(.+)>/', $address, $matches)) {
                            if (count($matches) === 3) {
                                $recipient_name = $matches[1];
                                $address        = $matches[2];
                            }
                        }

                        switch ($address_header) {
                            case 'to':
                                $phpmailer->addAddress($address, $recipient_name);
                                break;
                            case 'cc':
                                $phpmailer->addCc($address, $recipient_name);
                                break;
                            case 'bcc':
                                $phpmailer->addBcc($address, $recipient_name);
                                break;
                            case 'reply_to':
                                $phpmailer->addReplyTo($address, $recipient_name);
                                break;
                        }
                    } catch (PHPMailer\PHPMailer\Exception $e) {
                        continue;
                    }
                }
            }

            // Set to use PHP's mail().
            $phpmailer->isMail();

            // Set Content-Type and charset.

            // If we don't have a Content-Type from the input headers.
            if ( ! isset($content_type)) {
                $content_type = 'text/plain';
            }

            /**
             * Filters the wp_mail() content type.
             *
             * @param string $content_type Default wp_mail() content type.
             * @since 2.3.0
             */
            $content_type = apply_filters('wp_mail_content_type', $content_type);

            $phpmailer->ContentType = $content_type;

            // Set whether it's plaintext, depending on $content_type.
            if ('text/html' === $content_type) {
                $phpmailer->isHTML(true);
            }

            // If we don't have a charset from the input headers.
            if ( ! isset($charset)) {
                $charset = get_bloginfo('charset');
            }

            /**
             * Filters the default wp_mail() charset.
             *
             * @param string $charset Default email charset.
             * @since 2.3.0
             */
            $phpmailer->CharSet = apply_filters('wp_mail_charset', $charset);

            // Set custom headers.
            if ( ! empty($headers)) {
                foreach ( (array) $headers as $name => $content) {
                    // Only add custom headers not added automatically by PHPMailer.
                    if ( ! in_array($name, array( 'MIME-Version', 'X-Mailer' ), true)) {
                        try {
                            $phpmailer->addCustomHeader(sprintf('%1$s: %2$s', $name, $content));
                        } catch (PHPMailer\PHPMailer\Exception $e) {
                            continue;
                        }
                    }
                }

                if (false !== stripos($content_type, 'multipart') && ! empty($boundary)) {
                    $phpmailer->addCustomHeader(sprintf('Content-Type: %s; boundary="%s"', $content_type, $boundary));
                }
            }

            if ( ! empty($attachments)) {
                foreach ($attachments as $filename => $attachment) {
                    $filename = is_string($filename) ? $filename : '';

                    try {
                        $phpmailer->addAttachment($attachment, $filename);
                    } catch (PHPMailer\PHPMailer\Exception $e) {
                        continue;
                    }
                }
            }

            /**
             * Fires after PHPMailer is initialized.
             *
             * @param PHPMailer $phpmailer The PHPMailer instance (passed by reference).
             * @since 2.2.0
             */
            do_action_ref_array('phpmailer_init', array( &$phpmailer ));

            $mail_data = compact('to', 'subject', 'message', 'headers', 'attachments');

            // Send!
            try {
                $send = $phpmailer->send();

                /**
                 * Fires after PHPMailer has successfully sent an email.
                 * The firing of this action does not necessarily mean that the recipient(s) received the
                 * email successfully. It only means that the `send` method above was able to
                 * process the request without any errors.
                 *
                 * @param array $mail_data {
                 *     An array containing the email recipient(s), subject, message, headers, and attachments.
                 * @type string[] $to Email addresses to send message.
                 * @type string $subject Email subject.
                 * @type string $message Message contents.
                 * @type string[] $headers Additional headers.
                 * @type string[] $attachments Paths to files to attach.
                 * }
                 * @since 5.9.0
                 */
                do_action('wp_mail_succeeded', $mail_data);

                return $send;
            } catch (PHPMailer\PHPMailer\Exception $e) {
                $mail_data['phpmailer_exception_code'] = $e->getCode();

                /**
                 * Fires after a PHPMailer\PHPMailer\Exception is caught.
                 *
                 * @param WP_Error $error A WP_Error object with the PHPMailer\PHPMailer\Exception message, and an array
                 *                        containing the mail recipient, subject, message, headers, and attachments.
                 * @since 4.4.0
                 */
                do_action('wp_mail_failed', new WP_Error('wp_mail_failed', $e->getMessage(), $mail_data));

                return false;
            }
        }

        return true;
    }
}

/**
 * @param array $body
 * @param mixed $boundary
 * @return string
 */
function mg_build_payload_from_body( $body, $boundary ): string {
    $payload = '';

    // Iterate through pre-built params and build payload:
    foreach ($body as $key => $value) {
        if (is_array($value)) {
            $parent_key = $key;
            foreach ($value as $key => $value) {
                $payload .= '--' . $boundary;
                $payload .= "\r\n";
                $payload .= 'Content-Disposition: form-data; name="' . $parent_key . "\"\r\n\r\n";
                $payload .= $value;
                $payload .= "\r\n";
            }
        } else {
            $payload .= '--' . $boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="' . $key . '"' . "\r\n\r\n";
            $payload .= $value;
            $payload .= "\r\n";
        }
    }

    return $payload;
}

/**
 * @param array $attachments
 * @param mixed $boundary
 * @return string|null
 */
function mg_build_attachments_payload( $attachments, $boundary ): ?string {
    $payload = '';

    if (empty($attachments)) {
        return null;
    }
    // If we have attachments, add them to the payload.
    $i = 0;
    foreach ($attachments as $attachment) {
        if ( ! empty($attachment)) {
            $payload .= '--' . $boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="attachment[' . $i . ']"; filename="' . basename($attachment) . '"' . "\r\n\r\n";
            $payload .= file_get_contents($attachment);
            $payload .= "\r\n";
            ++$i;
        }
    }

    return $payload;
}
