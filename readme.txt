=== OFT Upload Form ===
Contributors: codex
Tags: contact form, file upload, shortcode
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.5.1
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

= 1.5.1 =

* Adds a file size setting so you can choose the upload limit that fits your form without editing code.
* Makes it easier to balance convenience and control by only showing size options your hosting setup can support.

= 1.5.0 =

* Adds a file size setting so you can choose the upload limit that fits your form without editing code.
* Makes it easier to balance convenience and control by only showing size options your hosting setup can support.

= 1.4.0 =

* Adds CSV export so you can quickly download and work with your saved submissions in Excel or other spreadsheet apps.
* Makes it easier to review, share, and organize form entries outside WordPress.

= 1.3.0 =

* Adds a simple contact form option without file uploads for a cleaner, more streamlined form experience.
* Makes it easier to use the plugin for basic contact forms when you do not need visitors to attach files.

= 1.2.0 =

* Security-focused update with stronger protection across the plugin.
* Form submissions are checked and filtered to help block spam, invalid entries, and unsafe uploads.
* Uploaded files are now kept private and can only be downloaded by site administrators inside WordPress.
* You can now choose which file types visitors are allowed to upload, making it easier to keep your form aligned with what your site actually needs.
* Extra protections were added to reduce abuse and keep exported submission data safer to open.

= 1.1.0 =

* Adds built-in update support so future improvements can be delivered more smoothly through WordPress.
* Makes it easier to keep the plugin current with less manual work.

= 1.0.0 =

* Initial version.
