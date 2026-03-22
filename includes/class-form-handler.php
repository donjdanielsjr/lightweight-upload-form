<?php
/**
 * Form processing workflow.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LUF_Form_Handler {

	/**
	 * Validator service.
	 *
	 * @var LUF_Validator
	 */
	protected $validator;

	/**
	 * Upload service.
	 *
	 * @var LUF_Uploader
	 */
	protected $uploader;

	/**
	 * Mailer service.
	 *
	 * @var LUF_Mailer
	 */
	protected $mailer;

	/**
	 * Database service.
	 *
	 * @var LUF_Database
	 */
	protected $database;

	/**
	 * Constructor.
	 *
	 * @param LUF_Validator $validator Validator.
	 * @param LUF_Uploader  $uploader  Uploader.
	 * @param LUF_Mailer    $mailer    Mailer.
	 * @param LUF_Database  $database  Database service.
	 */
	public function __construct( $validator, $uploader, $mailer, $database ) {
		$this->validator = $validator;
		$this->uploader  = $uploader;
		$this->mailer    = $mailer;
		$this->database  = $database;
	}

	/**
	 * Process posted form requests.
	 *
	 * @return void
	 */
	public function handle_submission() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			return;
		}

		if ( empty( $_POST['luf_action'] ) || 'submit_form' !== sanitize_text_field( wp_unslash( $_POST['luf_action'] ) ) ) {
			return;
		}

		$redirect_url = $this->get_redirect_url();
		$validation   = $this->validator->validate( $_POST, $_FILES );

		if ( ! $validation['is_valid'] ) {
			$this->redirect_with_notice(
				$redirect_url,
				array(
					'type'     => 'error',
					'messages' => $validation['errors'],
					'old'      => $validation['data'],
				)
			);
		}

		$file_data = null;

		if ( ! empty( $_FILES['luf_file']['name'] ) ) {
			$upload = $this->uploader->handle_upload( $_FILES['luf_file'] );

			if ( ! $upload['success'] ) {
				$this->redirect_with_notice(
					$redirect_url,
					array(
						'type'     => 'error',
						'messages' => array( $upload['message'] ),
						'old'      => $validation['data'],
					)
				);
			}

			$file_data = $upload['file'];
		}

		$submission = array(
			'name'          => $validation['data']['name'],
			'email'         => $validation['data']['email'],
			'message'       => $validation['data']['message'],
			'file_url'      => $file_data ? $file_data['url'] : '',
			'file_path'     => $file_data ? $file_data['path'] : '',
			'attachment_id' => $file_data ? (int) $file_data['attachment_id'] : 0,
		);

		$submission_id = $this->database->insert_submission( $submission );

		if ( ! $submission_id ) {
			$this->redirect_with_notice(
				$redirect_url,
				array(
					'type'     => 'error',
					'messages' => array( __( 'Your submission could not be saved. Please try again.', 'lightweight-upload-form' ) ),
					'old'      => $validation['data'],
				)
			);
		}

		$this->mailer->send_notification( $submission, $file_data );

		$this->redirect_with_notice(
			$redirect_url,
			array(
				'type'     => 'success',
				'messages' => array( __( 'Thank you. Your message has been sent successfully.', 'lightweight-upload-form' ) ),
				'old'      => array(),
			)
		);
	}

	/**
	 * Get redirect URL after submission.
	 *
	 * @return string
	 */
	protected function get_redirect_url() {
		$redirect_url = ! empty( $_POST['luf_redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['luf_redirect_to'] ) ) : '';

		if ( empty( $redirect_url ) ) {
			$redirect_url = wp_get_referer();
		}

		if ( empty( $redirect_url ) ) {
			$redirect_url = home_url( '/' );
		}

		return $redirect_url;
	}

	/**
	 * Store flash data and redirect.
	 *
	 * @param string $redirect_url Redirect URL.
	 * @param array  $payload      Flash payload.
	 * @return void
	 */
	protected function redirect_with_notice( $redirect_url, $payload ) {
		$token = wp_generate_password( 12, false, false );

		set_transient( luf_get_flash_transient_key( $token ), $payload, MINUTE_IN_SECONDS * 10 );

		wp_safe_redirect(
			add_query_arg(
				array(
					'luf_notice' => rawurlencode( $token ),
				),
				$redirect_url
			)
		);
		exit;
	}
}
