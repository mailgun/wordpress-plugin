Mailgun for WordPress
=====================

Contributors: Mailgun, sivel, lookahead.io, m35dev
Tags: mailgun, smtp, http, api, mail, email
Requires at least: 3.3
Tested up to: 4.7
Stable tag: 1.5.1
License: GPLv2 or later


Easily send email from your WordPress site through Mailgun using the HTTP API or SMTP.


== Description ==

[Mailgun](http://www.mailgun.com/) is the email automation engine trusted by over 10,000 website and application developers for sending, receiving and tracking emails. By taking advantage of Mailgun's powerful email APIs, developers can spend more time building awesome websites and less time fighting with email servers. Mailgun supports all of the most popular languages including PHP, Ruby, Python, C# and Java.

One particularly useful feature of this plugin is that it provides you with a way to send email when the server you are on does not support SMTP or where outbound SMTP is restricted since the plug-in uses the Mailgun HTTP API for sending email by default. All you need to use the plugin is a [Mailgun account](http://www.mailgun.com/). Mailgun has a free account that lets you send up to 200 emails per day, which is great for testing. Paid subscriptions are available for increased limits.

The latest version of this plugin adds support for Mailgun list subscription. Using the shortcode, you can place a form on an article or page to allow the visitor to subscribe to one or more lists. Using the widget, you can provide subscription functionality in sidebars or anywhere widgets are supported e.g. footers.

The current version of this plugin only handles sending emails, tracking and tagging and list subscription. 


== Installation ==

1. Upload the `mailgun` folder to the `/wp-content/plugins/` directory or install directly through the plugin installer
2. Activate the plugin through the 'Plugins' menu in WordPress or by using the link provided by the plugin installer
3. Visit the settings page in the Admin at `Settings -> Mailgun` and configure the plugin with your account details
4. Click the Test Configuration button to verify that your settings are correct.
5. Click View Available Lists to review shortcode settings for lists in your Mailgun account that you may wish to help users subscribe to


== Frequently Asked Questions ==

- Testing the configuration fails when using the HTTP API =

Your web server may not allow outbound HTTP connections. Set `Use HTTP API` to "No", and fill out the configuration options to SMTP and test again.

- Testing the configuration fails when using SMTP =

Your web server may not allow outbound SMTP connections on port 465 for secure connections or 587 for unsecured connections. Try changing `Use Secure SMTP` to "No" or "Yes" depending on your current configuration and testing again. If both fail, try setting `Use HTTP API` to "Yes" and testing again.

- Can this be configured globally for WordPress Multisite? =

Yes, using the following constants that can be placed in wp-config.php:

```
MAILGUN_USEAPI   Type: boolean
MAILGUN_APIKEY   Type: string
MAILGUN_DOMAIN   Type: string
MAILGUN_USERNAME Type: string
MAILGUN_PASSWORD Type: string
MAILGUN_SECURE   Type: boolean
```


== Screenshots ==

1. Configuration options for using the Mailgun HTTP API
2. Configuration options for using the Mailgun SMTP servers
3. Administration option to View Available Lists for subscription
4. Setting up a Subscription Widget
5. Using a Subscription Code
6. Subscription Form Seen By Site Visitors

