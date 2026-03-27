<?php
/**
 * Plugin deactivation logic.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OFTUF_Deactivator {

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Intentionally left minimal. Data is preserved on deactivation.
	}
}

