<?php
/**
 * Admin page template.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap oftuf-admin-page">
	<h1><?php esc_html_e( 'Submissions', 'oft-upload-form' ); ?></h1>

	<?php $deleted_attachments = isset( $_GET['oftuf_attachments'] ) ? absint( $_GET['oftuf_attachments'] ) : 0; ?>

	<?php if ( 'delete' === $bulk_status && $deleted > 0 ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of deleted submissions. */
						_n( '%d submission deleted.', '%d submissions deleted.', $deleted, 'oft-upload-form' ),
						$deleted
					)
				);
				?>
			</p>
		</div>
	<?php elseif ( 'delete_with_attachments' === $bulk_status && $deleted > 0 ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: number of deleted submissions, 2: number of deleted files. */
						_n(
							'%1$d submission deleted. %2$d file deleted.',
							'%1$d submissions deleted. %2$d files deleted.',
							$deleted,
							'oft-upload-form'
						),
						$deleted,
						$deleted_attachments
					)
				);
				?>
			</p>
		</div>
	<?php elseif ( 'none_selected' === $bulk_status ) : ?>
		<div class="notice notice-warning is-dismissible">
			<p><?php esc_html_e( 'Select at least one submission to delete.', 'oft-upload-form' ); ?></p>
		</div>
	<?php endif; ?>

	<p class="oftuf-admin-actions">
		<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=oftuf-submissions&oftuf_export=1' ), 'oftuf_export_csv' ) ); ?>">
			<?php esc_html_e( 'Export CSV', 'oft-upload-form' ); ?>
		</a>
	</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=oftuf-submissions' ) ); ?>">
		<input type="hidden" name="page" value="oftuf-submissions">
		<?php wp_nonce_field( 'oftuf_bulk_submissions_action' ); ?>

		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<label class="screen-reader-text" for="bulk-action-selector-top"><?php esc_html_e( 'Select bulk action', 'oft-upload-form' ); ?></label>
				<select name="action" id="bulk-action-selector-top">
					<option value="-1"><?php esc_html_e( 'Bulk actions', 'oft-upload-form' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'oft-upload-form' ); ?></option>
					<option value="delete_with_attachments"><?php esc_html_e( 'Delete with files', 'oft-upload-form' ); ?></option>
				</select>
				<?php submit_button( __( 'Apply', 'oft-upload-form' ), 'action', 'doaction', false ); ?>
			</div>
		</div>

		<table class="widefat fixed striped">
			<thead>
				<tr>
					<td class="check-column">
						<input id="cb-select-all-1" type="checkbox" class="oftuf-select-all">
					</td>
					<th><?php esc_html_e( 'Name', 'oft-upload-form' ); ?></th>
					<th><?php esc_html_e( 'Email', 'oft-upload-form' ); ?></th>
					<th><?php esc_html_e( 'Message', 'oft-upload-form' ); ?></th>
					<th><?php esc_html_e( 'File', 'oft-upload-form' ); ?></th>
					<th><?php esc_html_e( 'Date', 'oft-upload-form' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $submissions ) ) : ?>
					<tr>
						<td colspan="6"><?php esc_html_e( 'No submissions found.', 'oft-upload-form' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $submissions as $submission ) : ?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="submission_ids[]" value="<?php echo esc_attr( $submission['id'] ); ?>" class="oftuf-submission-checkbox">
							</th>
							<td><?php echo esc_html( $submission['name'] ); ?></td>
							<td>
								<a href="mailto:<?php echo esc_attr( $submission['email'] ); ?>">
									<?php echo esc_html( $submission['email'] ); ?>
								</a>
							</td>
							<td><?php echo esc_html( wp_trim_words( $submission['message'], 20, '...' ) ); ?></td>
							<td>
								<?php if ( ! empty( $submission['file_path'] ) ) : ?>
									<a href="<?php echo esc_url( oftuf_get_submission_download_url( $submission['id'] ) ); ?>">
										<?php echo esc_html( oftuf_get_submission_file_label( $submission ) ); ?>
									</a>
								<?php elseif ( ! empty( $submission['file_url'] ) ) : ?>
									<a href="<?php echo esc_url( $submission['file_url'] ); ?>" target="_blank" rel="noopener noreferrer">
										<?php echo esc_html( $submission['file_url'] ); ?>
									</a>
									<?php if ( ! empty( $submission['attachment_id'] ) ) : ?>
										<div class="oftuf-attachment-id">
											<?php
											echo esc_html(
												sprintf(
													/* translators: %d: attachment ID. */
													__( 'Attachment ID: %d', 'oft-upload-form' ),
													(int) $submission['attachment_id']
												)
											);
											?>
										</div>
									<?php endif; ?>
								<?php else : ?>
									<?php esc_html_e( 'No file', 'oft-upload-form' ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $submission['created_at'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<div class="tablenav bottom">
			<div class="alignleft actions bulkactions">
				<label class="screen-reader-text" for="bulk-action-selector-bottom"><?php esc_html_e( 'Select bulk action', 'oft-upload-form' ); ?></label>
				<select name="action2" id="bulk-action-selector-bottom">
					<option value="-1"><?php esc_html_e( 'Bulk actions', 'oft-upload-form' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'oft-upload-form' ); ?></option>
					<option value="delete_with_attachments"><?php esc_html_e( 'Delete with files', 'oft-upload-form' ); ?></option>
				</select>
				<?php submit_button( __( 'Apply', 'oft-upload-form' ), 'action', 'doaction2', false ); ?>
			</div>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'current'   => $page,
								'total'     => $total_pages,
								'prev_text' => __( '&laquo;', 'oft-upload-form' ),
								'next_text' => __( '&raquo;', 'oft-upload-form' ),
							)
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>
	</form>
</div>


