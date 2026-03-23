<?php
/**
 * Settings page template.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap luf-admin-page">
	<h1><?php esc_html_e( 'Help', 'lightweight-upload-form' ); ?></h1>

	<?php if ( 'success' === $test_status ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Test email sent. Check your inbox for the message.', 'lightweight-upload-form' ); ?></p>
		</div>
	<?php elseif ( 'error' === $test_status ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'The test email could not be sent. Your site may need an SMTP email service.', 'lightweight-upload-form' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="card">
		<h2><?php esc_html_e( 'How to Use', 'lightweight-upload-form' ); ?></h2>
		<p><?php esc_html_e( 'Add the shortcode below to any page or post where you want the form to appear.', 'lightweight-upload-form' ); ?></p>
		<code>[lightweight_upload_form]</code>
		<p>
			<?php
			printf(
				/* translators: 1: opening submissions link, 2: closing link tag. */
				wp_kses(
					__( 'New submissions are saved in %1$sUpload Form > Submissions%2$s.', 'lightweight-upload-form' ),
					array(
						'a' => array(
							'href' => array(),
						),
					)
				),
				'<a href="' . esc_url( admin_url( 'admin.php?page=luf-submissions' ) ) . '">',
				'</a>'
			);
			?>
		</p>
	</div>

	<div class="card">
		<h2><?php esc_html_e( 'Email Delivery', 'lightweight-upload-form' ); ?></h2>
		<p><?php esc_html_e( 'This plugin uses WordPress\'s built-in email system.', 'lightweight-upload-form' ); ?></p>
		<p><?php esc_html_e( 'On most hosting providers, emails will send automatically.', 'lightweight-upload-form' ); ?></p>
		<p><?php esc_html_e( 'If emails are not arriving, you may need to connect your site to an SMTP email service.', 'lightweight-upload-form' ); ?></p>
		<p><?php esc_html_e( 'This helps improve delivery reliability and reduces the chance of messages going to spam.', 'lightweight-upload-form' ); ?></p>
		<p>
			<?php
			printf(
				/* translators: 1: opening admin settings link, 2: closing link tag, 3: admin email address. */
				wp_kses(
					__( 'Test emails are sent to the email address set in %1$sGeneral Settings%2$s: %3$s', 'lightweight-upload-form' ),
					array(
						'a' => array(
							'href' => array(),
						),
					)
				),
				'<a href="' . esc_url( admin_url( 'options-general.php' ) ) . '">',
				'</a>',
				esc_html( luf_get_recipient_email() )
			);
			?>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=luf-settings&luf_test_email_action=1' ), 'luf_send_test_email' ) ); ?>">
				<?php esc_html_e( 'Send Test Email', 'lightweight-upload-form' ); ?>
			</a>
		</p>
	</div>
</div>
