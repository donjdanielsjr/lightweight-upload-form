<?php
/**
 * Frontend form template.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$old = isset( $context['old'] ) ? $context['old'] : array();
?>
<div class="oftuf-form-wrapper">
	<?php if ( ! empty( $context['messages'] ) ) : ?>
		<div class="oftuf-notice oftuf-notice--<?php echo esc_attr( $context['notice_type'] ); ?>" role="alert">
			<?php if ( 1 === count( $context['messages'] ) ) : ?>
				<p class="oftuf-notice__message"><?php echo esc_html( $context['messages'][0] ); ?></p>
			<?php else : ?>
				<ul class="oftuf-notice__list">
					<?php foreach ( $context['messages'] as $message ) : ?>
						<li><?php echo esc_html( $message ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<form class="oftuf-form" action="<?php echo esc_url( $context['action'] ); ?>" method="post" enctype="multipart/form-data">
		<div class="oftuf-field">
			<label for="oftuf-name"><?php esc_html_e( 'Name', 'oft-upload-form' ); ?></label>
			<input id="oftuf-name" name="oftuf_name" type="text" required value="<?php echo isset( $old['name'] ) ? esc_attr( $old['name'] ) : ''; ?>">
		</div>

		<div class="oftuf-field">
			<label for="oftuf-email"><?php esc_html_e( 'Email', 'oft-upload-form' ); ?></label>
			<input id="oftuf-email" name="oftuf_email" type="email" required value="<?php echo isset( $old['email'] ) ? esc_attr( $old['email'] ) : ''; ?>">
		</div>

		<div class="oftuf-field">
			<label for="oftuf-message"><?php esc_html_e( 'Message', 'oft-upload-form' ); ?></label>
			<textarea id="oftuf-message" name="oftuf_message" rows="6" required><?php echo isset( $old['message'] ) ? esc_textarea( $old['message'] ) : ''; ?></textarea>
		</div>

		<?php if ( ! empty( $context['show_upload'] ) ) : ?>
			<div class="oftuf-field">
				<label for="oftuf-file">
					<?php esc_html_e( 'Upload File', 'oft-upload-form' ); ?>
					<?php if ( ! empty( $context['file_required'] ) ) : ?>
						<span class="oftuf-required">*</span>
					<?php endif; ?>
				</label>
				<input id="oftuf-file" name="oftuf_file" type="file" accept="<?php echo esc_attr( $context['accept_attribute'] ); ?>" <?php echo ! empty( $context['file_required'] ) ? 'required' : ''; ?>>
				<p class="oftuf-help">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: list of extensions, 2: max file size. */
							__( 'Allowed file types: %1$s. Maximum size: %2$s.', 'oft-upload-form' ),
							$context['allowed_extensions'],
							$context['max_upload_label']
						)
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<div class="oftuf-honeypot" aria-hidden="true">
			<label for="oftuf-website"><?php esc_html_e( 'Website', 'oft-upload-form' ); ?></label>
			<input id="oftuf-website" name="oftuf_website" type="text" tabindex="-1" autocomplete="off">
		</div>

		<?php wp_nonce_field( 'oftuf_submit_form', 'oftuf_nonce' ); ?>
		<input type="hidden" name="oftuf_action" value="submit_form">
		<input type="hidden" name="oftuf_upload_enabled" value="<?php echo ! empty( $context['show_upload'] ) ? '1' : '0'; ?>">
		<input type="hidden" name="oftuf_redirect_to" value="<?php echo esc_url( $context['redirect_to'] ); ?>">

		<div class="oftuf-actions">
			<button class="oftuf-submit" type="submit"><?php esc_html_e( 'Send', 'oft-upload-form' ); ?></button>
		</div>
	</form>
</div>


