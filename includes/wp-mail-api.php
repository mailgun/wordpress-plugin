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
if (!include __DIR__ . '/mg-filter.php') {
    (new Mailgun)->deactivate_and_die(__DIR__ . '/mg-filter.php');
}

/**
 * mg_api_last_error is a compound getter/setter for the last error that was
 * encountered during a Mailgun API call.
 *
 * @param string $error OPTIONAL
 *
 * @return    string    Last error that occurred.
 *
 * @since    1.5.0
 */
function mg_api_last_error($error = null)
{
    static $last_error;

    if (null === $error) {
        return $last_error;
    }

    do_action('mailgun_error_track', $error);
    $tmp = $last_error;
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
function mg_mutate_to_rcpt_vars_cb($to_addrs)
{
    if (is_string($to_addrs)) {
        $to_addrs = explode(',', $to_addrs);
    }

    if (has_filter('mg_use_recipient_vars_syntax')) {
        $use_rcpt_vars = apply_filters('mg_use_recipient_vars_syntax', null);
        if ($use_rcpt_vars) {

            $idx = 0;
            foreach ($to_addrs as $addr) {
                $rcpt_vars[$addr] = ['batch_msg_id' => $idx];
                $idx++;
            }

            return [
                'to' => '%recipient%',
                'rcpt_vars' => json_encode($rcpt_vars),
            ];
        }
    }

    return [
        'to' => $to_addrs,
        'rcpt_vars' => null,
    ];
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
 *
 */
if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = [])
    {
        $mailgun = get_option('mailgun');
        $region = (defined('MAILGUN_REGION') && MAILGUN_REGION) ? MAILGUN_REGION : $mailgun['region'];
        $apiKey = (defined('MAILGUN_APIKEY') && MAILGUN_APIKEY) ? MAILGUN_APIKEY : $mailgun['apiKey'];
        $domain = (defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN) ? MAILGUN_DOMAIN : $mailgun['domain'];

        if (empty($apiKey) || empty($domain)) {
            return false;
        }

        // If a region is not set via defines or through the options page, default to US region.
        if (!($region)) {
            error_log('[Mailgun] No region configuration was found! Defaulting to US region.');
            $region = 'us';
        }
        
        // Respect WordPress core filters
        $atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

        if (isset($atts['to'])) {
            $to = $atts['to'];
        }

        if (!is_array($to)) {
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

        if (!is_array($attachments)) {
            $attachments = explode("\n", str_replace("\r\n", "\n", $attachments));
        }

        // Headers
        if (empty($headers)) {
            $headers = [];
        } else {
            if (!is_array($headers)) {
                // Explode the headers out, so this function can take both
                // string headers and an array of headers.
                $tempheaders = explode("\n", str_replace("\r\n", "\n", $headers));
            } else {
                $tempheaders = $headers;
            }
            $headers = [];
            $cc = [];
            $bcc = [];

            // If it's actually got contents
            if (!empty($tempheaders)) {
                // Iterate through the raw headers
                foreach ((array)$tempheaders as $header) {
                    if (strpos($header, ':') === false) {
                        if (false !== stripos($header, 'boundary=')) {
                            $parts = preg_split('/boundary=/i', trim($header));
                            $boundary = trim(str_replace(["'", '"'], '', $parts[1]));
                        }
                        continue;
                    }
                    // Explode them out
                    [$name, $content] = explode(':', trim($header), 2);

                    // Cleanup crew
                    $name = trim($name);
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
                                $content_type = trim($type);
                                if (false !== stripos($charset, 'charset=')) {
                                    $charset = trim(str_replace(['charset=', '"'], '', $charset));
                                } elseif (false !== stripos($charset, 'boundary=')) {
                                    $boundary = trim(str_replace(['BOUNDARY=', 'boundary=', '"'], '', $charset));
                                    $charset = '';
                                }
                            } else {
                                $content_type = trim($content);
                            }
                            break;
                        case 'cc':
                            $cc = array_merge((array)$cc, explode(',', $content));
                            break;
                        case 'bcc':
                            $bcc = array_merge((array)$bcc, explode(',', $content));
                            break;
                        default:
                            // Add it to our grand headers array
                            $headers[trim($name)] = trim($content);
                            break;
                    }
                }
            }
        }

        if (!isset($from_name)) {
            $from_name = null;
        }

        if (!isset($from_email)) {
            $from_email = null;
        }

        $from_name = mg_detect_from_name($from_name);
        $from_email = mg_detect_from_address($from_email);
        $fromString = "{$from_name} <{$from_email}>";

        $body = [
            'from' => $fromString,
            'h:Sender' => $from_email,
            'to' => $to,
            'subject' => $subject,
        ];


        $rcpt_data = apply_filters('mg_mutate_to_rcpt_vars', $to);
        if (!is_null($rcpt_data['rcpt_vars'])) {
            $body['recipient-variables'] = $rcpt_data['rcpt_vars'];
        }

        $body['o:tag'] = [];
        $body['o:tracking-clicks'] = !empty($mailgun['track-clicks']) ? $mailgun['track-clicks'] : 'no';
        $body['o:tracking-opens'] = empty($mailgun['track-opens']) ? 'no' : 'yes';

        // this is the wordpress site tag
        if (isset($mailgun['tag'])) {
            $tags = explode(',', str_replace(' ', '', $mailgun['tag']));
            $body['o:tag'] = $tags;
        }

        // campaign-id now refers to a list of tags which will be appended to the site tag
        if (!empty($mailgun['campaign-id'])) {
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

        if (!empty($cc) && is_array($cc)) {
            $body['cc'] = implode(', ', $cc);
        }

        if (!empty($bcc) && is_array($bcc)) {
            $body['bcc'] = implode(', ', $bcc);
        }

        // If we are not given a Content-Type in the supplied headers,
        // write the message body to a file and try to determine the mimetype
        // using get_mime_content_type.
        if (!isset($content_type)) {
            $tmppath = tempnam(get_temp_dir(), 'mg');
            $tmp = fopen($tmppath, 'w+');

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
        } else if ('text/html' === $content_type) {
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
            if (!($phpmailer instanceof PHPMailer\PHPMailer\PHPMailer)) {
                require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
                require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
                require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
                $phpmailer = new PHPMailer\PHPMailer\PHPMailer(true);

                $phpmailer::$validator = static function ($email) {
                    return (bool)is_email($email);
                };
            }

            /**
             * Fires after PHPMailer is initialized.
             *
             * @param PHPMailer $phpmailer The PHPMailer instance (passed by reference).
             */
            do_action_ref_array('phpmailer_init', [&$phpmailer]);

            $plainTextMessage = $phpmailer->AltBody;

            if ($plainTextMessage) {
                $body['text'] = $plainTextMessage;
            }
        }

        // If we don't have a charset from the input headers
        if (!isset($charset)) {
            $charset = get_bloginfo('charset');
        }

        // Set the content-type and charset
        $charset = apply_filters('wp_mail_charset', $charset);
        if (isset($headers['Content-Type'])) {
            if (!strstr($headers['Content-Type'], 'charset')) {
                $headers['Content-Type'] = rtrim($headers['Content-Type'], '; ') . "; charset={$charset}";
            }
        }

        // Set custom headers
        if (!empty($headers)) {
            foreach ((array)$headers as $name => $content) {
                $body["h:{$name}"] = $content;
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

        $data = [
            'body' => $payload,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("api:{$apiKey}"),
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            ],
        ];

        $endpoint = mg_api_get_region($region);
        $endpoint = ($endpoint) ? $endpoint : 'https://api.mailgun.net/v3/';
        $url = $endpoint . "{$domain}/messages";

        // TODO: Mailgun only supports 1000 recipients per request, since we are
        // overriding this function, let's add looping here to handle that
        $response = wp_remote_post($url, $data);
        if (is_wp_error($response)) {
            // Store WP error in last error.
            mg_api_last_error($response->get_error_message());

            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response));

        // Mailgun API should *always* return a `message` field, even when
        // $response_code != 200, so a lack of `message` indicates something
        // is broken.
        if ((int)$response_code != 200 || !isset($response_body->message)) {
            // Store response code and HTTP response message in last error.
            $response_message = wp_remote_retrieve_response_message($response);
            $errmsg = "$response_code - $response_message";
            mg_api_last_error($errmsg);

            return false;
        }

        // Not sure there is any additional checking that needs to be done here, but why not?
        if ($response_body->message !== 'Queued. Thank you.') {
            mg_api_last_error($response_body->message);

            return false;
        }

        return true;
    }
}

function mg_build_payload_from_body($body, $boundary)
{
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

function mg_build_attachments_payload($attachments, $boundary)
{
    $payload = '';

    // If we have attachments, add them to the payload.
    if (!empty($attachments)) {
        $i = 0;
        foreach ($attachments as $attachment) {
            if (!empty($attachment)) {
                $payload .= '--' . $boundary;
                $payload .= "\r\n";
                $payload .= 'Content-Disposition: form-data; name="attachment[' . $i . ']"; filename="' . basename($attachment) . '"' . "\r\n\r\n";
                $payload .= file_get_contents($attachment);
                $payload .= "\r\n";
                $i++;
            }
        }
    } else {
        return null;
    }

    return $payload;
}
