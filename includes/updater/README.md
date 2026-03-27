# OFT Plugin Updater

## Purpose

`OFT_Plugin_Updater` is a small reusable self-hosted updater module for WordPress plugins.

It integrates with the native WordPress plugin update UI and does not require:

- License keys
- Subscriptions
- Accounts
- External update services

## Module Files

- `includes/updater/class-oft-plugin-updater.php`
  Reusable updater class
- `includes/updater/examples/info.json`
  Starter metadata template you can copy and publish to your server

## What It Does

- Detects the installed plugin version from the plugin header
- Fetches remote metadata with `wp_remote_get()`
- Caches metadata in a WordPress site transient
- Injects available updates into `site_transient_update_plugins`
- Supplies plugin details for the native "View details" modal through `plugins_api`
- Provides a minimal admin debug page
- Fails quietly if the remote request or JSON is invalid

## Metadata URL Options

Default metadata URL:

- `https://onefeaturetrap.com/plugin-updates/{plugin-slug}/info.json`

Default download URL pattern:

- `https://onefeaturetrap.com/plugin-downloads/{plugin-slug}.zip`

If you do not pass `metadata_url`, the updater builds the default URL automatically from the plugin slug.

For a simpler single-folder setup, which is what this plugin now uses, pass `metadata_url` and keep both files in `/plugin-downloads/`, for example:

- `https://onefeaturetrap.com/plugin-downloads/{plugin-slug}.json`
- `https://onefeaturetrap.com/plugin-downloads/{plugin-slug}.zip`

## Required Config

```php
new OFT_Plugin_Updater(
	array(
		'plugin_file'  => __FILE__,
		'plugin_slug'  => 'your-plugin-slug',
		'plugin_name'  => 'Your Plugin Name',
		'metadata_url' => 'https://onefeaturetrap.com/plugin-downloads/your-plugin-slug.json',
	)
);
```

Required keys:

- `plugin_file`
- `plugin_slug`
- `plugin_name`

## Optional Config

You can also pass:

- `metadata_url`
- `cache_key`
- `cache_ttl`

Defaults when not overridden:

- `metadata_url`: `https://onefeaturetrap.com/plugin-updates/{plugin-slug}/info.json`
- `cache_key`: `oft_updater_{plugin-slug}`
- `cache_ttl`: `6 * HOUR_IN_SECONDS`

## How To Reuse In Another Plugin

1. Copy the `includes/updater/` folder into the other plugin.
2. Require the updater file from that plugin's main bootstrap file.
3. Instantiate `OFT_Plugin_Updater` with plugin-specific values only.

Example:

```php
require_once plugin_dir_path( __FILE__ ) . 'includes/updater/class-oft-plugin-updater.php';

new OFT_Plugin_Updater(
	array(
		'plugin_file'  => __FILE__,
		'plugin_slug'  => 'another-plugin',
		'plugin_name'  => 'Another Plugin',
		'metadata_url' => 'https://onefeaturetrap.com/plugin-downloads/another-plugin.json',
	)
);
```

## Required Remote JSON Format

```json
{
  "name": "Plugin Name",
  "slug": "plugin-slug",
  "version": "1.2.0",
  "requires": "6.0",
  "tested": "6.8",
  "requires_php": "7.4",
  "last_updated": "2026-03-27",
  "homepage": "https://onefeaturetrap.com/",
  "download_url": "https://onefeaturetrap.com/plugin-downloads/plugin-slug.zip",
  "sections": {
    "description": "Short plugin description.",
    "installation": "Install and activate the plugin.",
    "changelog": "<h4>1.2.0</h4><ul><li>Example change</li></ul>"
  },
  "banners": {},
  "icons": {}
}
```

Required JSON fields:

- `name`
- `slug`
- `version`
- `download_url`

The remote `slug` must match the configured `plugin_slug`.

## Reuse Checklist

Before using the updater in another plugin:

1. Make sure the plugin folder name and slug match.
2. Make sure the plugin header contains a valid `Version:`.
3. Require `includes/updater/class-oft-plugin-updater.php`.
4. Instantiate the updater with the correct slug and plugin name.
5. Publish a valid JSON metadata file.
6. Publish a zip file whose root folder matches the installed plugin folder.

## Admin Debug Page

The updater adds:

- a plugin action link named `Check for updates`
- an admin-only debug page under `Tools`

It shows:

- Plugin slug
- Installed version
- Metadata URL
- Last fetched metadata
- Whether an update is available

Use it to clear cache and confirm the updater is reading the expected metadata.

## Cache Behavior

- Metadata is cached in a site transient
- Default cache duration is 6 hours
- Cache is cleared automatically after plugin updates
- Cache can be cleared manually from the debug page

## Failure Behavior

If any of the following happen, the updater does nothing and should not break the plugin:

- Request failure
- Non-200 response
- Empty response body
- Invalid JSON
- Missing required JSON fields
- Slug mismatch

## Notes

- Keep the updater generic. Do not hardcode plugin-specific values into the class.
- Keep plugin-specific values in the config array only.
- If the metadata host changes in the future, update the class or pass `metadata_url` per plugin.

