<?php
/**
 * Frontend form template.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$old = isset( $context['old'] ) ? $context['old'] : array();
?>
<div class="luf-form-wrapper">
	<?php if ( ! empty( $context['messages'] ) ) : ?>
		<div class="luf-notice luf-notice--<?php echo esc_attr( $context['notice_type'] ); ?>" role="alert">
			<ul class="luf-notice__list">
				<?php foreach ( $context['messages'] as $message ) : ?>
					<li><?php echo esc_html( $message ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form class="luf-form" action="<?php echo esc_url( $context['action'] ); ?>" method="post" enctype="multipart/form-data">
		<div class="luf-field">
			<label for="luf-name"><?php esc_html_e( 'Name', 'lightweight-upload-form' ); ?></label>
			<input id="luf-name" name="luf_name" type="text" required value="<?php echo isset( $old['name'] ) ? esc_attr( $old['name'] ) : ''; ?>">
		</div>

		<div class="luf-field">
			<label for="luf-email"><?php esc_html_e( 'Email', 'lightweight-upload-form' ); ?></label>
			<input id="luf-email" name="luf_email" type="email" required value="<?php echo isset( $old['email'] ) ? esc_attr( $old['email'] ) : ''; ?>">
		</div>

		<div class="luf-field">
			<label for="luf-message"><?php esc_html_e( 'Message', 'lightweight-upload-form' ); ?></label>
			<textarea id="luf-message" name="luf_message" rows="6" required><?php echo isset( $old['message'] ) ? esc_textarea( $old['message'] ) : ''; ?></textarea>
		</div>

		<div class="luf-field">
			<label for="luf-file">
				<?php esc_html_e( 'Upload File', 'lightweight-upload-form' ); ?>
				<?php if ( ! empty( $context['file_required'] ) ) : ?>
					<span class="luf-required">*</span>
				<?php endif; ?>
			</label>
			<input id="luf-file" name="luf_file" type="file" accept=".pdf,.jpg,.jpeg,.png" <?php echo ! empty( $context['file_required'] ) ? 'required' : ''; ?>>
			<p class="luf-help">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: list of extensions, 2: max file size. */
						__( 'Allowed file types: %1$s. Maximum size: %2$s.', 'lightweight-upload-form' ),
						$context['allowed_extensions'],
						$context['max_upload_label']
					)
				);
				?>
			</p>
		</div>

		<div class="luf-honeypot" aria-hidden="true">
			<label for="luf-website"><?php esc_html_e( 'Website', 'lightweight-upload-form' ); ?></label>
			<input id="luf-website" name="luf_website" type="text" tabindex="-1" autocomplete="off">
		</div>

		<?php wp_nonce_field( 'luf_submit_form', 'luf_nonce' ); ?>
		<input type="hidden" name="luf_action" value="submit_form">
		<input type="hidden" name="luf_redirect_to" value="<?php echo esc_url( $context['redirect_to'] ); ?>">

		<div class="luf-actions">
			<button class="luf-submit" type="submit"><?php esc_html_e( 'Send Message', 'lightweight-upload-form' ); ?></button>
		</div>
	</form>
</div>
