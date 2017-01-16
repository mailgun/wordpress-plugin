Changelog
=========

1.5.7 (2017-01-04):
* Add better support for using recipient variables for batch mailing.
* Clarify wording on `From Address` note
* Detect from name and address for `phpmailer_init` / SMTP now will honour Mailgun "From Name / From Addr" settings
* SMTP configuration test will now provide the error message, if the send fails
* Fix `undefined variable: content_type` error in `wp-mail.php` (https://wordpress.org/support/topic/minor-bug-on-version-version-1-5-6/#post-8634762)
* Fix `undefined index: override-from` error in `wp-mail.php` (https://wordpress.org/support/topic/php-notice-undefined-index-override-from/)

1.5.6 (2016-12-30):
* Fix a very subtle bug causing fatal errors with older PHP versions < 5.5
* Respect `wp_mail_content_type` (#37 - @FPCSJames)

1.5.5 (2016-12-27):
* Restructure the `admin_notices` code
* Restructure the "From Name" / "From Address" code
* Add option to override "From Name" / "From Address" setting set by other plugins
* Fix a bug causing default "From Name" / "From Address" to be always applied in some cases
* Moved plugin header up in entrypoint file (https://wordpress.org/support/topic/plugin-activation-due-to-header/#post-8598062)
* Fixed a bug causing "Override From" to be set to "yes" after upgrades

1.5.4 (2016-12-23):
* Changed some missed bracketed array usages to `array()` syntax
* Fix `wp_mail_from` / `wp_mail_from_name` not working on old PHP / WP versions
* Add a wrapper for using `mime_content_type` / `finfo_file`

1.5.3 (2016-12-22):
* Changed all bracketed array usages to `array()` syntax for older PHP support
* Redesigned `Content-Type` processing code to not make such large assumptions
* Mailgun logo is now loaded over HTTPS
* Fixed undefined variable issue with from email / from name code

1.5.2 (2016-12-22):
* Added option fields for setting a From name and address

1.5.1 (2016-12-21):
* Fixed an issue causing plugin upgrades from <1.5 -> >=1.5 to deactivate

1.5 (2016-12-19):
* Added Catalan language support (@DavidGarciaCat)
* Added Spanish language support (@DavidGarciaCat)
* Added German language support (@lsinger)
* Fixed incorrect SMTP hostname
* Applied PSR standards across codebase
* Applied open tracking bugfix
* Applied tags bugfix
* Applied `Mailgun Lists` admin panel bugfix
* Fixed click tracking dropdown
* Fixed click tracking and open tracking
* Now try to process *all* sent mails as HTML, see L201 wp-mail.php for details
* Mailgun logo now loads on both admin pages ;)
* Now using the Mailgun API v3 endpoint!
* Configuration test will now present either an error from the API or the HTTP response code + message

1.4.1 (2015-12-01):
* Clarify compatibility with WordPress 4.3

1.4 (2015-11-15):
* Added shortcode and widget support for list subscription

1.3.1 (2014-11-19):
* Switched to Semantic Versioning
* Fixed issue with campaigns and tags

1.3 (2014-08-25):
* Added check to ignore empty attachments

1.2 (2014-08-19):
* Fixed errors related to undefined variable. https://github.com/mailgun/wordpress-plugin/pull/3

1.1 (2013-12-09):
* Attachments are now handled properly.
* Added ability to customize tags and campaigns.
* Added ability to toggle URL and open tracking.

1.0 (2012-11-27):
* Re-release to update versioning to start at 1.0 instead of 0.1

0.1 (2012-11-21):
* Initial Release

