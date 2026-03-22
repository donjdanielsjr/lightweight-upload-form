<?php
/**
 * Validation service for frontend submissions.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LUF_Validator {

	/**
	 * Validate form submission.
	 *
	 * @param array $post_data Posted form data.
	 * @param array $files     Uploaded file data.
	 * @return array
	 */
	public function validate( $post_data, $files ) {
		$errors = array();
		$data   = array(
			'name'    => isset( $post_data['luf_name'] ) ? sanitize_text_field( wp_unslash( $post_data['luf_name'] ) ) : '',
			'email'   => isset( $post_data['luf_email'] ) ? sanitize_email( wp_unslash( $post_data['luf_email'] ) ) : '',
			'message' => isset( $post_data['luf_message'] ) ? sanitize_textarea_field( wp_unslash( $post_data['luf_message'] ) ) : '',
		);

		if ( empty( $post_data['luf_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $post_data['luf_nonce'] ) ), 'luf_submit_form' ) ) {
			$errors[] = __( 'Security check failed. Please refresh the page and try again.', 'lightweight-upload-form' );
		}

		if ( ! empty( $post_data['luf_website'] ) ) {
			$errors[] = __( 'Spam detection triggered.', 'lightweight-upload-form' );
		}

		if ( '' === $data['name'] ) {
			$errors[] = __( 'Name is required.', 'lightweight-upload-form' );
		}

		if ( '' === $data['email'] ) {
			$errors[] = __( 'Email is required.', 'lightweight-upload-form' );
		} elseif ( ! is_email( $data['email'] ) ) {
			$errors[] = __( 'Please enter a valid email address.', 'lightweight-upload-form' );
		}

		if ( '' === $data['message'] ) {
			$errors[] = __( 'Message is required.', 'lightweight-upload-form' );
		}

		$file = isset( $files['luf_file'] ) ? $files['luf_file'] : null;

		if ( luf_is_file_required() && ( ! $file || empty( $file['name'] ) ) ) {
			$errors[] = __( 'A file upload is required.', 'lightweight-upload-form' );
		}

		if ( $file && ! empty( $file['name'] ) ) {
			$file_validation = $this->validate_file( $file );

			if ( ! empty( $file_validation['errors'] ) ) {
				$errors = array_merge( $errors, $file_validation['errors'] );
			}
		}

		return array(
			'is_valid' => empty( $errors ),
			'errors'   => $errors,
			'data'     => $data,
		);
	}

	/**
	 * Validate the uploaded file.
	 *
	 * @param array $file Uploaded file array.
	 * @return array
	 */
	protected function validate_file( $file ) {
		$errors        = array();
		$allowed_mimes = luf_get_allowed_mime_types();
		$max_size      = luf_get_max_upload_size();
		$file_name     = isset( $file['name'] ) ? sanitize_file_name( $file['name'] ) : '';
		$file_size     = isset( $file['size'] ) ? (int) $file['size'] : 0;
		$file_error    = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;

		if ( UPLOAD_ERR_OK !== $file_error ) {
			$errors[] = __( 'The uploaded file could not be processed.', 'lightweight-upload-form' );
		}

		if ( $file_size > $max_size ) {
			$errors[] = sprintf(
				/* translators: %s: file size limit. */
				__( 'The uploaded file exceeds the maximum size of %s.', 'lightweight-upload-form' ),
				luf_format_file_size( $max_size )
			);
		}

		if ( UPLOAD_ERR_OK === $file_error && ! empty( $file['tmp_name'] ) ) {
			$file_info = wp_check_filetype_and_ext( $file['tmp_name'], $file_name, $allowed_mimes );

			if ( empty( $file_info['ext'] ) || empty( $file_info['type'] ) ) {
				$errors[] = __( 'The uploaded file type is not allowed.', 'lightweight-upload-form' );
			}
		}

		return array(
			'errors' => $errors,
		);
	}
}
