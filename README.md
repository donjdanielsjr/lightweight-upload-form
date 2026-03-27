# OFT Upload Form

## Plugin Version Administration

This document covers release administration for this plugin.

Reusable updater module documentation lives here:

- `includes/updater/README.md`

Update files for this plugin:

- Metadata JSON: `https://onefeaturetrap.com/plugin-downloads/oft-upload-form.json`
- Download zip: `https://onefeaturetrap.com/plugin-downloads/oft-upload-form.zip`

The updater reads the installed version from the plugin header in `oft-upload-form.php` and compares it against the remote `version` value in `oft-upload-form.json`.

## Release Source Of Truth

- `deployment.config.json`
  This file is the release metadata source of truth for deployment builds.
- F5 runs the deployment build script and generates:
  - `deployment/oft-upload-form.zip`
  - `deployment/oft-upload-form.json`
- The deployment script writes JSON as UTF-8 without BOM to avoid update and AJAX response issues.

## Files That Control Versions

- `oft-upload-form.php`
  The plugin header `Version:` is the installed version source used by the updater.
- `oft-upload-form.php`
  The `OFTUF_VERSION` constant should stay in sync with the plugin header.
- `readme.txt`
  `Stable tag:` and the top changelog entry are synced during deployment.
- `deployment.config.json`
  Release metadata used to generate the deployment JSON and sync local version references during deployment.
- `https://onefeaturetrap.com/plugin-downloads/oft-upload-form.zip`
  Release zip downloaded during updates.
- `https://onefeaturetrap.com/plugin-downloads/oft-upload-form.json`
  Generated remote metadata used by WordPress update checks.

## Standard Release Process

1. Finish the code changes for the release.
2. Pick the new version number, for example `1.0.7`.
3. Update `deployment.config.json`:
   - Change `version`
   - Change `last_updated`
   - Change `release_notes`
4. Press F5 or run the deployment task to build:
   - `deployment/oft-upload-form.zip`
   - `deployment/oft-upload-form.json`
5. The deployment script syncs these local files from `deployment.config.json` before packaging:
   - `oft-upload-form.php` plugin header `Version:`
   - `oft-upload-form.php` `OFTUF_VERSION`
   - `readme.txt` `Stable tag:`
   - `readme.txt` top changelog entry for the current version
   - `deployment/oft-upload-form.json` `sections.changelog`
6. Build output uses `oft-upload-form` as the zip root folder.
7. Upload these two files to `/plugin-downloads/` on the server:
   - `oft-upload-form.json`
   - `oft-upload-form.zip`
8. Confirm these public URLs load:
   - `https://onefeaturetrap.com/plugin-downloads/oft-upload-form.zip`
   - `https://onefeaturetrap.com/plugin-downloads/oft-upload-form.json`
9. In wp-admin, force an update check and confirm the release appears.

## Important Version Rule

The remote JSON version must be higher than the installed plugin header version or WordPress will not show an update.

Example:

- Installed version: `1.0.0`
- Remote version: `1.0.7`
- Result: update is shown

If both values match, no update is offered.

## Generated `oft-upload-form.json`

This file is generated from `deployment.config.json` during the deployment build:

```json
{
  "name": "OFT Upload Form",
  "slug": "oft-upload-form",
  "version": "1.0.7",
  "requires": "6.0",
  "tested": "6.8",
  "requires_php": "7.4",
  "last_updated": "2026-03-27",
  "homepage": "https://onefeaturetrap.com/",
  "download_url": "https://onefeaturetrap.com/plugin-downloads/oft-upload-form.zip",
  "sections": {
    "description": "Lightweight contact form plugin with shortcode support, a single file upload field, email notifications, and submission storage.",
    "installation": "Install and activate the plugin.",
    "changelog": "<h4>1.0.7</h4><ul><li>Minor release version bump for OFT Upload Form update testing.</li></ul>"
  },
  "banners": {},
  "icons": {}
}
```

## Testing In wp-admin

## Release Notes Source

- `deployment.config.json` stores release notes as a plain `release_notes` array.
- The deployment script renders those notes into:
  - HTML for `deployment/oft-upload-form.json`
  - the current version entry in `readme.txt`

### Force an update check

- Go to `Dashboard > Updates` and click `Check Again`
- Or go to `Plugins` and use the `Check for updates` link for this plugin
- Or open the debug page and click `Refresh metadata`

### Test the native "View details" modal

1. Publish a valid `oft-upload-form.json`.
2. Ensure the remote version is higher than the installed version.
3. Go to `Plugins`.
4. Open the plugin details modal from the update UI.
5. Confirm the description, installation text, and changelog render from `sections`.

### Simulate a version bump

1. Keep the installed plugin at `1.0.0`.
2. Set remote `oft-upload-form.json` to `1.0.7`.
3. Upload the new release zip.
4. Refresh metadata.
5. Confirm WordPress shows the update.

### Confirm safe failure

The updater should fail quietly. Test these cases:

- Metadata URL returns `404`
- Invalid JSON
- Missing required fields such as `slug`, `version`, or `download_url`
- Mismatched slug

Expected result:

- No fatal errors
- No broken plugin functionality
- No update offer shown

## Admin Debug Page

The plugin adds a small admin-only updater debug page under `Tools`.

It shows:

- Plugin slug
- Installed version
- Metadata URL
- Last fetched metadata
- Whether an update is available

Use `Refresh metadata` to clear the updater cache and fetch the latest metadata.
