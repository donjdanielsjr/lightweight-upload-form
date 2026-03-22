<?php
/**
 * Upload service.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LUF_Uploader {

	/**
	 * Handle the uploaded file using WordPress APIs.
	 *
	 * @param array $file Uploaded file array.
	 * @return array
	 */
	public function handle_upload( $file ) {
		if ( empty( $file['name'] ) ) {
			return array(
				'success' => true,
				'file'    => null,
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$overrides = array(
			'test_form' => false,
			'mimes'     => luf_get_allowed_mime_types(),
		);

		$uploaded = wp_handle_upload( $file, $overrides );

		if ( isset( $uploaded['error'] ) ) {
			return array(
				'success' => false,
				'message' => sanitize_text_field( $uploaded['error'] ),
			);
		}

		$attachment_id = $this->create_attachment( $uploaded, $file );

		return array(
			'success' => true,
			'file'    => array(
				'url'           => esc_url_raw( $uploaded['url'] ),
				'path'          => isset( $uploaded['file'] ) ? sanitize_text_field( $uploaded['file'] ) : '',
				'type'          => isset( $uploaded['type'] ) ? sanitize_text_field( $uploaded['type'] ) : '',
				'attachment_id' => $attachment_id,
			),
		);
	}

	/**
	 * Create a media attachment record for the uploaded file.
	 *
	 * @param array $uploaded Uploaded result from wp_handle_upload().
	 * @param array $file     Original file array.
	 * @return int
	 */
	protected function create_attachment( $uploaded, $file ) {
		$attachment = array(
			'guid'           => $uploaded['url'],
			'post_mime_type' => $uploaded['type'],
			'post_title'     => sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attachment_id = wp_insert_attachment( $attachment, $uploaded['file'] );

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return 0;
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );

		if ( ! is_wp_error( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		return (int) $attachment_id;
	}
}
