=== OFT Upload Form ===
Contributors: codex
Tags: contact form, file upload, shortcode
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight contact form plugin with shortcode support, a single file upload field, email notifications, and admin submission storage.

== Description ==

OFT Upload Form provides a single-purpose contact form for sites that do not need a full form builder. It includes:

* Shortcode-based rendering with `[oft_upload_form]`
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
3. Add `[oft_upload_form]` to a page or post.
4. Visit `OFT Upload Form > Submissions` in wp-admin to review saved entries.
5. Visit `OFT Upload Form > Help` for usage guidance and email test tools.

== Frequently Asked Questions ==

= How do I change the recipient email? =

Use the `oftuf_recipient_email` filter.

= How do I change the file size limit? =

Use the `oftuf_max_upload_size` filter. The default is 10 MB.

= Does uninstall delete saved submissions? =

No. Data is preserved unless the `oftuf_delete_data_on_uninstall` option is explicitly enabled.

== Changelog ==

= 1.0.6 =

* Minor release version bump for OFT Upload Form update testing.

= 1.0.5 =

* Renamed the plugin slug, shortcode, text domain, internal prefixes, and update endpoints to the final OFT Upload Form identity.

= 1.0.4 =

* Rebranded the plugin to OFT Upload Form and updated author attribution to One Feature Trap.

= 1.0.3 =

* Minor release version bump for self-hosted update testing.

= 1.0.2 =

* Simplified self-hosted update delivery to use a single `/plugin-downloads/` location for both JSON metadata and zip downloads.

= 1.0.1 =

* Added self-hosted native WordPress plugin updates and plugin update details support.

= 1.0.0 =

* Initial release.




