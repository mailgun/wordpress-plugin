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

/**
 * wp_mail function to be loaded in to override the core wp_mail function
 * from wp-includes/pluggable.php.
 *
 * Based off of the core wp_mail function, but with modifications required to
 * send email using the Mailgun HTTP API
 *
 * @param string|array $to          Array or comma-separated list of email addresses to send message.
 * @param string       $subject     Email subject
 * @param string       $message     Message contents
 * @param string|array $headers     Optional. Additional headers.
 * @param string|array $attachments Optional. Files to attach.
 *
 * @return bool Whether the email contents were sent successfully.
 *
 * @since 0.1
 */
function wp_mail($to, $subject, $message, $headers = '', $attachments = [])
{
    // Compact the input, apply the filters, and extract them back out
    extract(apply_filters('wp_mail', compact('to', 'subject', 'message', 'headers', 'attachments')));

    $mailgun = get_option('mailgun');
    $apiKey = (defined('MAILGUN_APIKEY') && MAILGUN_APIKEY) ? MAILGUN_APIKEY : $mailgun['apiKey'];
    $domain = (defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN) ? MAILGUN_DOMAIN : $mailgun['domain'];

    if (empty($apiKey) || empty($domain)) {
        return false;
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
            foreach ((array) $tempheaders as $header) {
                if (strpos($header, ':') === false) {
                    if (false !== stripos($header, 'boundary=')) {
                        $parts = preg_split('/boundary=/i', trim($header));
                        $boundary = trim(str_replace(["'", '"'], '', $parts[1]));
                    }
                    continue;
                }
                // Explode them out
                list($name, $content) = explode(':', trim($header), 2);

                // Cleanup crew
                $name = trim($name);
                $content = trim($content);

                switch (strtolower($name)) {
                    // Mainly for legacy -- process a From: header if it's there
                case 'from':
                    if (strpos($content, '<') !== false) {
                        // So... making my life hard again?
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
                        list($type, $charset) = explode(';', $content);
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
                    $cc = array_merge((array) $cc, explode(',', $content));
                    break;
                case 'bcc':
                    $bcc = array_merge((array) $bcc, explode(',', $content));
                    break;
                default:
                    // Add it to our grand headers array
                    $headers[trim($name)] = trim($content);
                    break;
                }
            }
        }
    }

    // From email and name
    // If we don't have a name from the input headers
    if (!isset($from_name)) {
        $from_name = 'WordPress';
    }

    /* If we don't have an email from the input headers default to wordpress@$sitename
     * Some hosts will block outgoing mail from this address if it doesn't exist but
     * there's no easy alternative. Defaulting to admin_email might appear to be another
     * option but some hosts may refuse to relay mail from an unknown domain. See
     * http://trac.wordpress.org/ticket/5007.
     */

    if (!isset($from_email)) {
        // Get the site domain and get rid of www.
        $sitename = strtolower($_SERVER['SERVER_NAME']);
        if (substr($sitename, 0, 4) == 'www.') {
            $sitename = substr($sitename, 4);
        }

        $from_email = 'wordpress@'.$sitename;
    }

    // Plugin authors can override the potentially troublesome default
    $from_email = apply_filters('wp_mail_from', $from_email);
    $from_name = apply_filters('wp_mail_from_name', $from_name);

    $body = [
        'from'    => "{$from_name} <{$from_email}>",
        'to'      => $to,
        'subject' => $subject,
        'text'    => $message,
    ];

    $body['o:tracking-clicks'] = isset($mailgun['track-clicks']) ? $mailgun['track-clicks'] : 'no';
    $body['o:tracking-opens'] = isset($mailgun['track-opens']) ? 'yes' : 'no';

    // this is the wordpress site tag
    if (isset($mailgun['tag'])) {
        $tags = explode(',', str_replace(' ', '', $mailgun['tag']));
        $body['o:tag'] = $tags;
    }

    // campaign-id now refers to a list of tags which will be appended to the site tag
    if (isset($mailgun['campaign-id'])) {
        $tags = explode(',', str_replace(' ', '', $mailgun['campaign-id']));
        if (!isset($body['o:tag'])) {
            $body['o:tag'] = $tags;
        } else {
            $body['o:tag'] .= ','.$tags;
        }
    }

    if (!empty($cc) && is_array($cc)) {
        $body['cc'] = implode(', ', $cc);
    }

    if (!empty($bcc) && is_array($bcc)) {
        $body['bcc'] = implode(', ', $bcc);
    }

    // Set Content-Type and charset
    // If we don't have a content-type from the input headers
    if (!isset($content_type)) {
        $content_type = 'text/plain';
    }

    $content_type = apply_filters('wp_mail_content_type', $content_type);
    if ($content_type == 'text/html') {
        $body['html'] = $message;
        $body['text'] = strip_tags($message);
    }

    // If we don't have a charset from the input headers
    if (!isset($charset)) {
        $charset = get_bloginfo('charset');
    }

    // Set the content-type and charset
    $charset = apply_filters('wp_mail_charset', $charset);
    if (isset($headers['Content-Type'])) {
        if (!strstr($headers['Content-Type'], 'charset')) {
            $headers['Content-Type'] = rtrim($headers['Content-Type'], '; ')."; charset={$charset}";
        }
    }

    // Set custom headers
    if (!empty($headers)) {
        foreach ((array) $headers as $name => $content) {
            $body["h:{$name}"] = $content;
        }

        // TODO: Can we handle this?
        //if ( false !== stripos( $content_type, 'multipart' ) && ! empty($boundary) )
        //  $phpmailer->AddCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
    }

    /*
     * Deconstruct post array and create POST payload.
     * This entire routine is because wp_remote_post does
     * not support files directly.
     */

    // First, generate a boundary for the multipart message.
    $boundary = base_convert(uniqid('boundary', true), 10, 36);

    $payload = null;

    // Iterate through pre-built params and build payload:
    foreach ($body as $key => $value) {
        if (is_array($value)) {
            $parent_key = $key;
            foreach ($value as $key => $value) {
                $payload .= '--'.$boundary;
                $payload .= "\r\n";
                $payload .= 'Content-Disposition: form-data; name="'.$parent_key."\"\r\n\r\n";
                $payload .= $value;
                $payload .= "\r\n";
            }
        } else {
            $payload .= '--'.$boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="'.$key.'"'."\r\n\r\n";
            $payload .= $value;
            $payload .= "\r\n";
        }
    }

    // If we have attachments, add them to the payload.
    if (!empty($attachments)) {
        $i = 0;
        foreach ($attachments as $attachment) {
            if (!empty($attachment)) {
                $payload .= '--'.$boundary;
                $payload .= "\r\n";
                $payload .= 'Content-Disposition: form-data; name="attachment['.$i.']"; filename="'.basename($attachment).'"'."\r\n\r\n";
                $payload .= file_get_contents($attachment);
                $payload .= "\r\n";
                $i++;
            }
        }
    }

    $payload .= '--'.$boundary.'--';

    $data = [
        'body'    => $payload,
        'headers' => ['Authorization'     => 'Basic '.base64_encode("api:{$apiKey}"),
                           'content-type' => 'multipart/form-data; boundary='.$boundary, ],
    ];

    $url = "https://api.mailgun.net/v2/{$domain}/messages";

    // TODO: Mailgun only supports 1000 recipients per request, since we are
    // overriding this function, let's add looping here to handle that
    $response = wp_remote_post($url, $data);
    if (is_wp_error($response)) {
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ((int) $response_code != 200) {
        return false;
    }

    // Not sure there is any additional checking that needs to be done here, but why not?
    $response_body = json_decode(wp_remote_retrieve_body($response));
    if (!isset($response_body->message) || $response_body->message != 'Queued. Thank you.') {
        return false;
    }

    return true;
}
