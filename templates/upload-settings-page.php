<?php
/**
 * Upload settings page template.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap oftuf-admin-page">
	<h1><?php esc_html_e( 'Settings', 'oft-upload-form' ); ?></h1>

	<?php if ( 'saved' === $settings_status ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved.', 'oft-upload-form' ); ?></p>
		</div>
	<?php elseif ( 'missing_types' === $settings_status ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Select at least one file type.', 'oft-upload-form' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="card">
		<h2><?php esc_html_e( 'Upload Settings', 'oft-upload-form' ); ?></h2>
		<p><?php esc_html_e( 'Choose the file types your form accepts. Keep this list as small as possible.', 'oft-upload-form' ); ?></p>
		<p><?php esc_html_e( 'Risk note: Office documents and ZIP files can carry malware or unsafe content. PDFs and images are generally the safest options.', 'oft-upload-form' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=oftuf-upload-settings' ) ); ?>">
			<input type="hidden" name="page" value="oftuf-upload-settings">
			<?php wp_nonce_field( 'oftuf_save_settings' ); ?>
			<input type="hidden" name="oftuf_save_settings" value="1">

			<fieldset>
				<?php foreach ( $file_type_labels as $extension => $label ) : ?>
					<p>
						<label>
							<input type="checkbox" name="oftuf_allowed_extensions[]" value="<?php echo esc_attr( $extension ); ?>" <?php checked( in_array( $extension, $allowed_extensions, true ) ); ?>>
							<?php echo esc_html( $label ); ?>
						</label>
					</p>
				<?php endforeach; ?>
			</fieldset>

			<?php submit_button( __( 'Save Upload Settings', 'oft-upload-form' ) ); ?>
		</form>
	</div>
</div>
