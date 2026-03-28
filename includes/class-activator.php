<?php
/**
 * Plugin activation logic.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OFTUF_Activator {

	/**
	 * Create the submissions table.
	 *
	 * @return void
	 */
	public static function activate() {
		require_once OFTUF_PLUGIN_PATH . 'includes/class-database.php';
		require_once OFTUF_PLUGIN_PATH . 'includes/helpers.php';

		$database = new OFTUF_Database();
		$database->create_table();
		add_option( 'oftuf_allowed_extensions', oftuf_get_default_allowed_extensions() );
		add_option( 'oftuf_max_upload_size', oftuf_get_default_upload_size() );
		oftuf_ensure_private_upload_dir();
	}
}

