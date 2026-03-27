<?php
/**
 * Upload service.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OFTUF_Uploader {

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

		$allowed_mimes = oftuf_get_allowed_mime_types();
		$file_name     = sanitize_file_name( $file['name'] );
		$file_info     = wp_check_filetype_and_ext( $file['tmp_name'], $file_name, $allowed_mimes );
		$private_dir   = oftuf_ensure_private_upload_dir();

		if ( is_wp_error( $private_dir ) ) {
			return array(
				'success' => false,
				'message' => $private_dir->get_error_message(),
			);
		}

		if ( empty( $file_info['ext'] ) || empty( $file_info['type'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'The uploaded file type is not allowed.', 'oft-upload-form' ),
			);
		}

		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'The uploaded file could not be processed.', 'oft-upload-form' ),
			);
		}

		$stored_name = wp_unique_filename(
			$private_dir,
			wp_generate_password( 16, false, false ) . '-' . $file_name
		);
		$stored_path = trailingslashit( $private_dir ) . $stored_name;

		if ( ! @move_uploaded_file( $file['tmp_name'], $stored_path ) ) {
			return array(
				'success' => false,
				'message' => __( 'The uploaded file could not be stored.', 'oft-upload-form' ),
			);
		}

		wp_chmod( $stored_path, 0640 );

		return array(
			'success' => true,
			'file'    => array(
				'original_name' => $file_name,
				'path'          => $stored_path,
				'type'          => $file_info['type'],
				'attachment_id' => 0,
			),
		);
	}
}

