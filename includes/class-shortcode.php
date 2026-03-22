<?php
/**
 * Frontend shortcode renderer.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LUF_Shortcode {

	/**
	 * Register shortcode.
	 *
	 * @return void
	 */
	public function register_shortcode() {
		add_shortcode( 'lightweight_upload_form', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render shortcode output.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts = array() ) {
		$this->enqueue_assets();

		$flash = $this->get_flash_data();
		$url   = luf_get_current_url();

		$context = array(
			'action'             => esc_url( $url ),
			'redirect_to'        => esc_url( $url ),
			'max_upload_size'    => luf_get_max_upload_size(),
			'max_upload_label'   => luf_format_file_size( luf_get_max_upload_size() ),
			'allowed_extensions' => implode( ', ', array_keys( luf_get_allowed_mime_types() ) ),
			'file_required'      => luf_is_file_required(),
			'notice_type'        => isset( $flash['type'] ) ? $flash['type'] : '',
			'messages'           => isset( $flash['messages'] ) ? (array) $flash['messages'] : array(),
			'old'                => isset( $flash['old'] ) ? (array) $flash['old'] : array(),
		);

		ob_start();
		include LUF_PLUGIN_PATH . 'templates/form.php';
		return ob_get_clean();
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	protected function enqueue_assets() {
		wp_enqueue_style(
			'luf-frontend',
			LUF_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			LUF_VERSION
		);

		wp_enqueue_script(
			'luf-frontend',
			LUF_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			LUF_VERSION,
			true
		);
	}

	/**
	 * Read and clear flash data.
	 *
	 * @return array
	 */
	protected function get_flash_data() {
		if ( empty( $_GET['luf_notice'] ) ) {
			return array();
		}

		$token = sanitize_key( wp_unslash( $_GET['luf_notice'] ) );
		$key   = luf_get_flash_transient_key( $token );
		$data  = get_transient( $key );

		if ( false !== $data ) {
			delete_transient( $key );
		}

		return is_array( $data ) ? $data : array();
	}
}
