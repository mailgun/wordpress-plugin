=== Mailgun for WordPress ===
Contributors: sivel, Mailgun
Tags: mailgun, smtp, http, api, mail, email
Requires at least: 3.3
Tested up to: 3.5
Stable tag: 1.0
License: GPLv2 or later

Easily send email from your WordPress site through Mailgun using the HTTP API or SMTP.

== Description ==

[Mailgun](http://www.mailgun.com/) is the email automation engine trusted by over 10,000 website and application developers for sending, receiving and tracking emails. By taking advantage of Mailgun's powerful email APIs, developers can spend more time building awesome websites and less time fighting with email servers. Mailgun supports all of the most popular languages including PHP, Ruby, Python, C# and Java.

One particularly useful feature of this plugin is that it provides you with a way to send email when the server you are on does not support SMTP or where outbound SMTP is restricted since the plug-in uses the Mailgun HTTP API for sending email by default. All you need to use the plugin is a [Mailgun account](http://www.mailgun.com/). Mailgun has a free account that lets you send up to 200 emails per day, which is great for testing. Paid subscriptions are available for increased limits.

The current version of this plugin only handles sending emails. Future releases will include additional functionality around processing incoming emails, tracking and analytics. Until then, you can configure how to handle incoming emails and set up analytics in the Mailgun control panel or using the Mailgun API.

== Installation ==

1. Upload the `mailgun` folder to the `/wp-content/plugins/` directory or install directly through the plugin installer
1. Activate the plugin through the 'Plugins' menu in WordPress or by using the link provided by the plugin installer
1. Visit the settings page in the Admin at `Settings -> Mailgun` and configure the plugin with your account details

== Frequently Asked Questions ==

= Testing the configuration fails when using the HTTP API =

Your web server may not allow outbound HTTP connections. Set `Use HTTP API` to "No", and fill out the configuration options to SMTP and test again.

= Testing the configuration fails when using SMTP =

Your web server may not allow outbound SMTP connections on port 465 for secure connections or 587 for unsecured connections. Try changing `Use Secure SMTP` to "No" or "Yes" depending on your current configuration and testing again. If both fail, try setting `Use HTTP API` to "Yes" and testing again.

= Can this be configured globally for WordPress Multisite? =

Yes, using the following constants that can be placed in wp-config.php:

`
MAILGUN_USEAPI   Type: boolean
MAILGUN_APIKEY   Type: string
MAILGUN_DOMAIN   Type: string
MAILGUN_USERNAME Type: string
MAILGUN_PASSWORD Type: string
MAILGUN_SECURE   Type: boolean
`

== Screenshots ==

1. Configuration options for using the Mailgun HTTP API
2. Configuration options for using the Mailgun SMTP servers

== Upgrade Notice ==

= 1.0 =

Re-release to update versioning to start at 1.0 instead of 0.1

= 0.1 =

Initial Release

== ChangeLog ==

= 1.0 (2012-11-27): =
* Re-release to update versioning to start at 1.0 instead of 0.1

= 0.1 (2012-11-21): =
* Initial Release
