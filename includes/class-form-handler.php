<?php
/**
 * Form processing workflow.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OFTUF_Form_Handler {

	/**
	 * Validator service.
	 *
	 * @var OFTUF_Validator
	 */
	protected $validator;

	/**
	 * Upload service.
	 *
	 * @var OFTUF_Uploader
	 */
	protected $uploader;

	/**
	 * Mailer service.
	 *
	 * @var OFTUF_Mailer
	 */
	protected $mailer;

	/**
	 * Database service.
	 *
	 * @var OFTUF_Database
	 */
	protected $database;

	/**
	 * Constructor.
	 *
	 * @param OFTUF_Validator $validator Validator.
	 * @param OFTUF_Uploader  $uploader  Uploader.
	 * @param OFTUF_Mailer    $mailer    Mailer.
	 * @param OFTUF_Database  $database  Database service.
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

		if ( empty( $_POST['oftuf_action'] ) || 'submit_form' !== sanitize_text_field( wp_unslash( $_POST['oftuf_action'] ) ) ) {
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

		$throttle_result = $this->check_throttle( $validation['data'] );

		if ( ! $throttle_result['allowed'] ) {
			$this->redirect_with_notice(
				$redirect_url,
				array(
					'type'     => 'error',
					'messages' => array( $throttle_result['message'] ),
					'old'      => $validation['data'],
				)
			);
		}

		$file_data = null;
		$upload_enabled = ! empty( $_POST['oftuf_upload_enabled'] ) && '0' !== sanitize_text_field( wp_unslash( $_POST['oftuf_upload_enabled'] ) );

		if ( $upload_enabled && ! empty( $_FILES['oftuf_file']['name'] ) ) {
			$upload = $this->uploader->handle_upload( $_FILES['oftuf_file'] );

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
			'file_url'      => $file_data ? $file_data['original_name'] : '',
			'file_path'     => $file_data ? $file_data['path'] : '',
			'attachment_id' => $file_data ? (int) $file_data['attachment_id'] : 0,
		);

		$submission_id = $this->database->insert_submission( $submission );

		if ( ! $submission_id ) {
			$this->redirect_with_notice(
				$redirect_url,
				array(
					'type'     => 'error',
					'messages' => array( __( 'Your submission could not be saved. Please try again.', 'oft-upload-form' ) ),
					'old'      => $validation['data'],
				)
			);
		}

		$this->mailer->send_notification( $submission, $file_data );
		$this->record_submission_for_throttle();

		$this->redirect_with_notice(
			$redirect_url,
			array(
				'type'     => 'success',
				'messages' => array( __( 'Thank you. Your message has been sent successfully.', 'oft-upload-form' ) ),
				'old'      => array(),
			)
		);
	}

	/**
	 * Apply a lightweight IP-based throttle.
	 *
	 * @param array $data Validated submission data.
	 * @return array
	 */
	protected function check_throttle( $data ) {
		$ip = oftuf_get_client_ip();

		if ( '' === $ip ) {
			return array(
				'allowed' => true,
				'message' => '',
			);
		}

		$key      = oftuf_get_throttle_transient_key( $ip );
		$record   = get_transient( $key );
		$record   = is_array( $record ) ? $record : array( 'count' => 0 );
		$limit    = oftuf_get_throttle_limit();
		$window   = oftuf_get_throttle_window();
		$allowed  = (int) $record['count'] < $limit;
		$message  = '';

		if ( ! $allowed ) {
			$message = sprintf(
				/* translators: 1: number of allowed submissions, 2: minutes in the window. */
				__( 'Too many submissions were received from your connection. Please wait and try again. Limit: %1$d submission(s) per %2$d minutes.', 'oft-upload-form' ),
				$limit,
				max( 1, (int) round( $window / MINUTE_IN_SECONDS ) )
			);
		}

		return array(
			'allowed' => $allowed,
			'message' => $message,
		);
	}

	/**
	 * Record the successful submission for throttling.
	 *
	 * @return void
	 */
	protected function record_submission_for_throttle() {
		$ip = oftuf_get_client_ip();

		if ( '' === $ip ) {
			return;
		}

		$key    = oftuf_get_throttle_transient_key( $ip );
		$window = oftuf_get_throttle_window();
		$record = get_transient( $key );
		$record = is_array( $record ) ? $record : array( 'count' => 0 );
		$record['count'] = isset( $record['count'] ) ? (int) $record['count'] + 1 : 1;

		set_transient( $key, $record, $window );
	}

	/**
	 * Get redirect URL after submission.
	 *
	 * @return string
	 */
	protected function get_redirect_url() {
		$redirect_url = ! empty( $_POST['oftuf_redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['oftuf_redirect_to'] ) ) : '';

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

		set_transient( oftuf_get_flash_transient_key( $token ), $payload, MINUTE_IN_SECONDS * 10 );

		wp_safe_redirect(
			add_query_arg(
				array(
					'oftuf_notice' => rawurlencode( $token ),
				),
				$redirect_url
			)
		);
		exit;
	}
}

