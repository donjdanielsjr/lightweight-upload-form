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

		$database = new OFTUF_Database();
		$database->create_table();
	}
}

