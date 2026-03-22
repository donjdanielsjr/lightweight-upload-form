<?php
/**
 * Plugin activation logic.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LUF_Activator {

	/**
	 * Create the submissions table.
	 *
	 * @return void
	 */
	public static function activate() {
		require_once LUF_PLUGIN_PATH . 'includes/class-database.php';

		$database = new LUF_Database();
		$database->create_table();
	}
}
