<?php
/**
 * Mail notification service.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OFTUF_Mailer {

	/**
	 * Send admin email notification.
	 *
	 * @param array      $submission Submission data.
	 * @param array|null $file_data  Uploaded file data.
	 * @return bool
	 */
	public function send_notification( $submission, $file_data = null ) {
		$recipient = oftuf_get_recipient_email();

		if ( empty( $recipient ) ) {
			return false;
		}

		$subject = apply_filters(
			'oftuf_email_subject',
			sprintf(
				/* translators: %s: submitter name. */
				__( 'New OFT Upload Form: %s', 'oft-upload-form' ),
				$submission['name']
			),
			$submission,
			$file_data
		);

		$body_lines = array(
			sprintf( __( 'Name: %s', 'oft-upload-form' ), $submission['name'] ),
			sprintf( __( 'Email: %s', 'oft-upload-form' ), $submission['email'] ),
			'',
			__( 'Message:', 'oft-upload-form' ),
			$submission['message'],
		);

		if ( ! empty( $file_data['original_name'] ) ) {
			$download_url = ! empty( $submission['id'] ) ? oftuf_get_submission_download_url( $submission['id'] ) : '';

			$body_lines[] = '';

			if ( ! empty( $download_url ) ) {
				$body_lines[] = sprintf( __( 'Admin (Only) Download Link (%1$s): %2$s', 'oft-upload-form' ), $file_data['original_name'], $download_url );
			} else {
				$body_lines[] = sprintf( __( 'Uploaded File: %s', 'oft-upload-form' ), $file_data['original_name'] );
			}
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
		$recipient = oftuf_get_recipient_email();

		if ( empty( $recipient ) ) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s: site name. */
			__( 'Test email from %s', 'oft-upload-form' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		$body = implode(
			"\n",
			array(
				__( 'This is a test email from the OFT Upload Form plugin.', 'oft-upload-form' ),
				'',
				sprintf(
					/* translators: %s: admin email address. */
					__( 'It was sent using WordPress to: %s', 'oft-upload-form' ),
					$recipient
				),
				sprintf(
					/* translators: %s: site URL. */
					__( 'Site: %s', 'oft-upload-form' ),
					home_url( '/' )
				),
			)
		);

		return wp_mail( $recipient, $subject, $body );
	}
}

