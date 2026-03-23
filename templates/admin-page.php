<?php
/**
 * Admin page template.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap luf-admin-page">
	<h1><?php esc_html_e( 'Submissions', 'lightweight-upload-form' ); ?></h1>

	<p class="luf-admin-actions">
		<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=luf-submissions&luf_export=1' ), 'luf_export_csv' ) ); ?>">
			<?php esc_html_e( 'Export CSV', 'lightweight-upload-form' ); ?>
		</a>
	</p>

	<table class="widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'lightweight-upload-form' ); ?></th>
				<th><?php esc_html_e( 'Email', 'lightweight-upload-form' ); ?></th>
				<th><?php esc_html_e( 'Message', 'lightweight-upload-form' ); ?></th>
				<th><?php esc_html_e( 'File', 'lightweight-upload-form' ); ?></th>
				<th><?php esc_html_e( 'Date', 'lightweight-upload-form' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $submissions ) ) : ?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No submissions found.', 'lightweight-upload-form' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $submissions as $submission ) : ?>
					<tr>
						<td><?php echo esc_html( $submission['name'] ); ?></td>
						<td>
							<a href="mailto:<?php echo esc_attr( $submission['email'] ); ?>">
								<?php echo esc_html( $submission['email'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( wp_trim_words( $submission['message'], 20, '...' ) ); ?></td>
						<td>
							<?php if ( ! empty( $submission['file_url'] ) ) : ?>
								<a href="<?php echo esc_url( $submission['file_url'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( $submission['file_url'] ); ?>
								</a>
								<?php if ( ! empty( $submission['attachment_id'] ) ) : ?>
									<div class="luf-attachment-id">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %d: attachment ID. */
												__( 'Attachment ID: %d', 'lightweight-upload-form' ),
												(int) $submission['attachment_id']
											)
										);
										?>
									</div>
								<?php endif; ?>
							<?php else : ?>
								<?php esc_html_e( 'No file', 'lightweight-upload-form' ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $submission['created_at'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'current'   => $page,
							'total'     => $total_pages,
							'prev_text' => __( '&laquo;', 'lightweight-upload-form' ),
							'next_text' => __( '&raquo;', 'lightweight-upload-form' ),
						)
					)
				);
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
