<?php
/**
 * Plugin Name: OFT Upload Form
 * Plugin URI:  https://onefeaturetrap.com/
 * Description: Lightweight contact form with file upload - live in under a minute, no setup required.
 * Version:     1.3.1
 * Author:      One Feature Trap
 * Author URI:  https://onefeaturetrap.com/
 * Text Domain: oft-upload-form
 * Domain Path: /languages
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OFTUF_VERSION', '1.3.1' );
define( 'OFTUF_PLUGIN_FILE', __FILE__ );
define( 'OFTUF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'OFTUF_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'OFTUF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Reusable self-hosted updater:
// 1. Keep this require in the main plugin file.
// 2. Pass plugin_file, plugin_slug, and plugin_name below.
// 3. Publish updates by uploading a new zip and matching JSON metadata.
require_once OFTUF_PLUGIN_PATH . 'includes/updater/class-oft-plugin-updater.php';
require_once OFTUF_PLUGIN_PATH . 'includes/helpers.php';
require_once OFTUF_PLUGIN_PATH . 'includes/class-activator.php';
require_once OFTUF_PLUGIN_PATH . 'includes/class-deactivator.php';
require_once OFTUF_PLUGIN_PATH . 'includes/class-validator.php';
require_once OFTUF_PLUGIN_PATH . 'includes/class-uploader.php';
require_once OFTUF_PLUGIN_PATH . 'includes/class-mailer.php';
require_once OFTUF_PLUGIN_PATH . 'includes/class-database.php';
require_once OFTUF_PLUGIN_PATH . 'includes/class-form-handler.php';
require_once OFTUF_PLUGIN_PATH . 'includes/class-shortcode.php';
require_once OFTUF_PLUGIN_PATH . 'includes/class-admin.php';
require_once OFTUF_PLUGIN_PATH . 'includes/class-plugin.php';

new OFT_Plugin_Updater(
	array(
		'plugin_file'  => __FILE__,
		'plugin_slug'  => 'oft-upload-form',
		'plugin_name'  => 'OFT Upload Form',
		'metadata_url' => 'https://onefeaturetrap.com/plugin-downloads/oft-upload-form.json',
	)
);

register_activation_hook( __FILE__, array( 'OFTUF_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'OFTUF_Deactivator', 'deactivate' ) );

function oftuf_run_plugin() {
	$plugin = new OFTUF_Plugin();
	$plugin->run();
}

oftuf_run_plugin();


