<?php
/**
 * Setup page template.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap oftuf-admin-page">
	<h1><?php esc_html_e( 'Setup', 'oft-upload-form' ); ?></h1>

	<?php if ( 'success' === $test_status ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Test email sent. Check your inbox for the message.', 'oft-upload-form' ); ?></p>
		</div>
	<?php elseif ( 'error' === $test_status ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'The test email could not be sent. Your site may need an SMTP email service.', 'oft-upload-form' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="card">
		<h2><?php esc_html_e( 'How to Use', 'oft-upload-form' ); ?></h2>
		<p><?php esc_html_e( 'Choose the shortcode that fits the kind of contact form you want to show.', 'oft-upload-form' ); ?></p>
		<p><strong><?php esc_html_e( 'Contact form with upload', 'oft-upload-form' ); ?></strong></p>
		<code>[oft_upload_form]</code>
		<p><?php esc_html_e( 'Use this when you want visitors to send a message and attach a file.', 'oft-upload-form' ); ?></p>
		<p><strong><?php esc_html_e( 'Contact form without upload', 'oft-upload-form' ); ?></strong></p>
		<code>[oft_upload_form upload="no"]</code>
		<p><?php esc_html_e( 'Use this when you only want a simple contact form with name, email, and message fields.', 'oft-upload-form' ); ?></p>
		<p>
			<?php
			printf(
				/* translators: 1: opening submissions link, 2: closing link tag. */
				wp_kses(
					__( 'New submissions are saved in %1$sOFT Upload Form > Submissions%2$s.', 'oft-upload-form' ),
					array(
						'a' => array(
							'href' => array(),
						),
					)
				),
				'<a href="' . esc_url( admin_url( 'admin.php?page=oftuf-submissions' ) ) . '">',
				'</a>'
			);
			?>
		</p>
	</div>

	<div class="card">
		<h2><?php esc_html_e( 'Email Delivery', 'oft-upload-form' ); ?></h2>
		<p><?php esc_html_e( 'This plugin uses WordPress\'s built-in email system.', 'oft-upload-form' ); ?></p>
		<p><?php esc_html_e( 'On most hosting providers, emails will send automatically.', 'oft-upload-form' ); ?></p>
		<p><?php esc_html_e( 'If emails are not arriving, you may need to connect your site to an SMTP email service.', 'oft-upload-form' ); ?></p>
		<p><?php esc_html_e( 'This helps improve delivery reliability and reduces the chance of messages going to spam.', 'oft-upload-form' ); ?></p>
		<p><?php esc_html_e( 'Uploaded files are stored privately and can only be downloaded from wp-admin by administrators.', 'oft-upload-form' ); ?></p>
		<p>
			<?php
			printf(
				/* translators: 1: opening admin settings link, 2: closing link tag, 3: admin email address. */
				wp_kses(
					__( 'Test emails are sent to the email address set in %1$sGeneral Settings%2$s: %3$s', 'oft-upload-form' ),
					array(
						'a' => array(
							'href' => array(),
						),
					)
				),
				'<a href="' . esc_url( admin_url( 'options-general.php' ) ) . '">',
				'</a>',
				esc_html( oftuf_get_recipient_email() )
			);
			?>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=oftuf-settings&oftuf_test_email_action=1' ), 'oftuf_send_test_email' ) ); ?>">
				<?php esc_html_e( 'Send Test Email', 'oft-upload-form' ); ?>
			</a>
		</p>
	</div>
</div>
