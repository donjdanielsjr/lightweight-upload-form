=== Lightweight Upload Form ===
Contributors: codex
Tags: contact form, file upload, shortcode
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight contact form plugin with shortcode support, a single file upload field, email notifications, and admin submission storage.

== Description ==

Lightweight Upload Form provides a single-purpose contact form for sites that do not need a full form builder. It includes:

* Shortcode-based rendering with `[lightweight_upload_form]`
* Name, email, message, and one file upload field
* Nonce validation and honeypot anti-spam
* Configurable upload size and allowed file types
* Admin email notifications
* Submission storage in a custom database table
* Native wp-admin submissions screen with CSV export and bulk delete tools
* Help screen with usage guidance, email delivery notes, and test email support

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins menu in WordPress.
3. Add `[lightweight_upload_form]` to a page or post.
4. Visit `Upload Form > Submissions` in wp-admin to review saved entries.
5. Visit `Upload Form > Help` for usage guidance and email test tools.

== Frequently Asked Questions ==

= How do I change the recipient email? =

Use the `luf_recipient_email` filter.

= How do I change the file size limit? =

Use the `luf_max_upload_size` filter. The default is 10 MB.

= Does uninstall delete saved submissions? =

No. Data is preserved unless the `luf_delete_data_on_uninstall` option is explicitly enabled.

== Changelog ==

= 1.0.0 =

* Initial release.
