<?php
/**
 * Mail notification service.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LUF_Mailer {

	/**
	 * Send admin email notification.
	 *
	 * @param array      $submission Submission data.
	 * @param array|null $file_data  Uploaded file data.
	 * @return bool
	 */
	public function send_notification( $submission, $file_data = null ) {
		$recipient = luf_get_recipient_email();

		if ( empty( $recipient ) ) {
			return false;
		}

		$subject = apply_filters(
			'luf_email_subject',
			sprintf(
				/* translators: %s: site name. */
				__( 'New upload form submission on %s', 'lightweight-upload-form' ),
				wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
			),
			$submission,
			$file_data
		);

		$body_lines = array(
			sprintf( __( 'Name: %s', 'lightweight-upload-form' ), $submission['name'] ),
			sprintf( __( 'Email: %s', 'lightweight-upload-form' ), $submission['email'] ),
			'',
			__( 'Message:', 'lightweight-upload-form' ),
			$submission['message'],
		);

		if ( ! empty( $file_data['url'] ) ) {
			$body_lines[] = '';
			$body_lines[] = sprintf( __( 'Uploaded File: %s', 'lightweight-upload-form' ), $file_data['url'] );
		}

		$headers = array( 'Reply-To: ' . $submission['email'] );

		return wp_mail( $recipient, $subject, implode( "\n", $body_lines ), $headers );
	}

	/**
	 * Send a test email to confirm WordPress mail delivery.
	 *
	 * @return bool
	 */
	public function send_test_email() {
		$recipient = luf_get_recipient_email();

		if ( empty( $recipient ) ) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s: site name. */
			__( 'Test email from %s', 'lightweight-upload-form' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		$body = implode(
			"\n",
			array(
				__( 'This is a test email from the Lightweight Upload Form plugin.', 'lightweight-upload-form' ),
				'',
				sprintf(
					/* translators: %s: admin email address. */
					__( 'It was sent using WordPress to: %s', 'lightweight-upload-form' ),
					$recipient
				),
				sprintf(
					/* translators: %s: site URL. */
					__( 'Site: %s', 'lightweight-upload-form' ),
					home_url( '/' )
				),
			)
		);

		return wp_mail( $recipient, $subject, $body );
	}
}
