<?php
/**
 * Plugin Name: Lightweight Upload Form
 * Plugin URI:  https://example.com/lightweight-upload-form
 * Description: Lightweight contact form plugin with single file upload, email notifications, and submission storage.
 * Version:     1.0.0
 * Author:      Don Daniels
 * Text Domain: lightweight-upload-form
 * Domain Path: /languages
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LUF_VERSION', '1.0.0' );
define( 'LUF_PLUGIN_FILE', __FILE__ );
define( 'LUF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'LUF_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'LUF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once LUF_PLUGIN_PATH . 'includes/helpers.php';
require_once LUF_PLUGIN_PATH . 'includes/class-activator.php';
require_once LUF_PLUGIN_PATH . 'includes/class-deactivator.php';
require_once LUF_PLUGIN_PATH . 'includes/class-validator.php';
require_once LUF_PLUGIN_PATH . 'includes/class-uploader.php';
require_once LUF_PLUGIN_PATH . 'includes/class-mailer.php';
require_once LUF_PLUGIN_PATH . 'includes/class-database.php';
require_once LUF_PLUGIN_PATH . 'includes/class-form-handler.php';
require_once LUF_PLUGIN_PATH . 'includes/class-shortcode.php';
require_once LUF_PLUGIN_PATH . 'includes/class-admin.php';
require_once LUF_PLUGIN_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'LUF_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LUF_Deactivator', 'deactivate' ) );

function luf_run_plugin() {
	$plugin = new LUF_Plugin();
	$plugin->run();
}

luf_run_plugin();
