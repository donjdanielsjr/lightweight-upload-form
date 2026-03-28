<?php
/**
 * Admin submissions UI.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OFTUF_Admin {

	/**
	 * Database service.
	 *
	 * @var OFTUF_Database
	 */
	protected $database;

	/**
	 * Mailer service.
	 *
	 * @var OFTUF_Mailer
	 */
	protected $mailer;

	/**
	 * Constructor.
	 *
	 * @param OFTUF_Database $database Database service.
	 * @param OFTUF_Mailer   $mailer   Mailer service.
	 */
	public function __construct( $database, $mailer ) {
		$this->database = $database;
		$this->mailer   = $mailer;
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$hook = add_menu_page(
			__( 'Setup', 'oft-upload-form' ),
			__( 'OFT Upload Form', 'oft-upload-form' ),
			'manage_options',
			'oftuf-settings',
			array( $this, 'render_settings_page' ),
			'dashicons-feedback',
			26
		);

		$setup_hook = add_submenu_page(
			'oftuf-settings',
			__( 'Setup', 'oft-upload-form' ),
			__( 'Setup', 'oft-upload-form' ),
			'manage_options',
			'oftuf-settings',
			array( $this, 'render_settings_page' )
		);

		$upload_settings_hook = add_submenu_page(
			'oftuf-settings',
			__( 'Settings', 'oft-upload-form' ),
			__( 'Settings', 'oft-upload-form' ),
			'manage_options',
			'oftuf-upload-settings',
			array( $this, 'render_upload_settings_page' )
		);

		$submissions_hook = add_submenu_page(
			'oftuf-settings',
			__( 'Submissions', 'oft-upload-form' ),
			__( 'Submissions', 'oft-upload-form' ),
			'manage_options',
			'oftuf-submissions',
			array( $this, 'render_page' )
		);

		add_action( 'load-' . $hook, array( $this, 'enqueue_assets' ) );
		add_action( 'load-' . $setup_hook, array( $this, 'enqueue_assets' ) );
		add_action( 'load-' . $upload_settings_hook, array( $this, 'enqueue_assets' ) );
		add_action( 'load-' . $submissions_hook, array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'oftuf-admin',
			OFTUF_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			OFTUF_VERSION
		);

		wp_enqueue_script(
			'oftuf-admin',
			OFTUF_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			OFTUF_VERSION,
			true
		);
	}

	/**
	 * Render the submissions page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'oft-upload-form' ) );
		}

		$bulk_status = isset( $_GET['oftuf_bulk_action'] ) ? sanitize_key( wp_unslash( $_GET['oftuf_bulk_action'] ) ) : '';
		$deleted     = isset( $_GET['oftuf_deleted'] ) ? absint( $_GET['oftuf_deleted'] ) : 0;
		$page        = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page    = 20;
		$total_items = $this->database->count_submissions();
		$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
		$page        = min( $page, $total_pages );
		$offset      = ( $page - 1 ) * $per_page;
		$submissions = $this->database->get_submissions( $per_page, $offset );

		include OFTUF_PLUGIN_PATH . 'templates/admin-page.php';
	}

	/**
	 * Render the help page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'oft-upload-form' ) );
		}

		$test_status = isset( $_GET['oftuf_test_email'] ) ? sanitize_key( wp_unslash( $_GET['oftuf_test_email'] ) ) : '';

		include OFTUF_PLUGIN_PATH . 'templates/settings-page.php';
	}

	/**
	 * Render the upload settings page.
	 *
	 * @return void
	 */
	public function render_upload_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'oft-upload-form' ) );
		}

		$settings_status    = isset( $_GET['oftuf_settings'] ) ? sanitize_key( wp_unslash( $_GET['oftuf_settings'] ) ) : '';
		$allowed_extensions = oftuf_get_allowed_extensions();
		$file_type_labels   = oftuf_get_file_type_labels();
		$upload_size_choices = oftuf_get_available_upload_size_choices();
		$selected_upload_size = oftuf_get_saved_upload_size();
		$server_upload_limit = oftuf_get_server_upload_limit();
		$host_limit_notice = $server_upload_limit > 0 && $server_upload_limit < 25 * MB_IN_BYTES;

		if ( ! isset( $upload_size_choices[ $selected_upload_size ] ) ) {
			$available_sizes      = array_keys( $upload_size_choices );
			$selected_upload_size = ! empty( $available_sizes ) ? max( $available_sizes ) : oftuf_get_default_upload_size();
		}

		include OFTUF_PLUGIN_PATH . 'templates/upload-settings-page.php';
	}

	/**
	 * Export submissions as CSV.
	 *
	 * @return void
	 */
	public function handle_csv_export() {
		if ( ! is_admin() ) {
			return;
		}

		if ( empty( $_GET['page'] ) || 'oftuf-submissions' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		if ( empty( $_GET['oftuf_export'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export submissions.', 'oft-upload-form' ) );
		}

		check_admin_referer( 'oftuf_export_csv' );

		$rows = $this->database->get_submissions( 5000, 0 );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=oftuf-submissions-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );

		// Excel can mis-detect CSV files that begin with an "ID" header as SYLK.
		fwrite( $output, "\xEF\xBB\xBF" );
		fputcsv( $output, array( 'Name', 'Email', 'Message', 'File', 'Created At' ) );

		foreach ( $rows as $row ) {
			fputcsv(
				$output,
				array(
					$this->neutralize_csv_cell( $row['name'] ),
					$this->neutralize_csv_cell( $row['email'] ),
					$this->neutralize_csv_cell( $row['message'] ),
					$this->neutralize_csv_cell( $this->get_submission_csv_file_url( $row ) ),
					$row['created_at'],
				)
			);
		}

		fclose( $output );
		exit;
	}

	/**
	 * Send a test email from the help page.
	 *
	 * @return void
	 */
	public function handle_test_email() {
		if ( ! is_admin() ) {
			return;
		}

		if ( empty( $_GET['page'] ) || 'oftuf-settings' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		if ( empty( $_GET['oftuf_test_email_action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to send a test email.', 'oft-upload-form' ) );
		}

		check_admin_referer( 'oftuf_send_test_email' );

		$sent = $this->mailer->send_test_email();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'oftuf-settings',
					'oftuf_test_email' => $sent ? 'success' : 'error',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Save plugin settings.
	 *
	 * @return void
	 */
	public function handle_settings_save() {
		if ( ! is_admin() ) {
			return;
		}

		if ( empty( $_POST['page'] ) || 'oftuf-upload-settings' !== sanitize_key( wp_unslash( $_POST['page'] ) ) ) {
			return;
		}

		if ( empty( $_POST['oftuf_save_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to save settings.', 'oft-upload-form' ) );
		}

		check_admin_referer( 'oftuf_save_settings' );

		$allowed_extensions = isset( $_POST['oftuf_allowed_extensions'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['oftuf_allowed_extensions'] ) ) : array();
		$allowed_extensions = array_values( array_intersect( $allowed_extensions, array_keys( oftuf_get_all_mime_types() ) ) );
		$upload_size_choices = oftuf_get_available_upload_size_choices();
		$selected_upload_size = isset( $_POST['oftuf_max_upload_size'] ) ? (int) wp_unslash( $_POST['oftuf_max_upload_size'] ) : 0;

		if ( empty( $allowed_extensions ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'           => 'oftuf-upload-settings',
						'oftuf_settings' => 'missing_types',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		if ( ! isset( $upload_size_choices[ $selected_upload_size ] ) ) {
			$selected_upload_size = oftuf_get_default_upload_size();
		}

		update_option( 'oftuf_allowed_extensions', $allowed_extensions );
		update_option( 'oftuf_max_upload_size', $selected_upload_size );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => 'oftuf-upload-settings',
					'oftuf_settings' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle bulk submission actions.
	 *
	 * @return void
	 */
	public function handle_bulk_actions() {
		if ( ! is_admin() ) {
			return;
		}

		if ( empty( $_POST['page'] ) || 'oftuf-submissions' !== sanitize_key( wp_unslash( $_POST['page'] ) ) ) {
			return;
		}

		$action = '';

		if ( ! empty( $_POST['action'] ) && '-1' !== $_POST['action'] ) {
			$action = sanitize_key( wp_unslash( $_POST['action'] ) );
		} elseif ( ! empty( $_POST['action2'] ) && '-1' !== $_POST['action2'] ) {
			$action = sanitize_key( wp_unslash( $_POST['action2'] ) );
		}

		if ( ! in_array( $action, array( 'delete', 'delete_with_attachments' ), true ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to delete submissions.', 'oft-upload-form' ) );
		}

		check_admin_referer( 'oftuf_bulk_submissions_action' );

		$submission_ids = isset( $_POST['submission_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['submission_ids'] ) ) : array();

		if ( empty( $submission_ids ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'              => 'oftuf-submissions',
						'oftuf_bulk_action' => 'none_selected',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		$deleted_attachments = 0;

		if ( 'delete_with_attachments' === $action ) {
			$deleted_attachments = $this->delete_submission_attachments( $submission_ids );
		}

		$deleted_count = $this->database->delete_submissions( $submission_ids );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'              => 'oftuf-submissions',
					'oftuf_bulk_action' => $action,
					'oftuf_deleted'     => $deleted_count,
					'oftuf_attachments' => $deleted_attachments,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle secure admin-only file downloads.
	 *
	 * @return void
	 */
	public function handle_private_download() {
		if ( ! is_admin() || empty( $_GET['oftuf_download'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to download files.', 'oft-upload-form' ) );
		}

		$submission_id = absint( $_GET['oftuf_download'] );

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'oftuf_download_submission_' . $submission_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'oft-upload-form' ) );
		}

		$submissions = $this->database->get_submissions_by_ids( array( $submission_id ) );
		$submission  = ! empty( $submissions ) ? $submissions[0] : null;

		if ( ! $submission || empty( $submission['file_path'] ) || ! file_exists( $submission['file_path'] ) ) {
			wp_die( esc_html__( 'The requested file could not be found.', 'oft-upload-form' ) );
		}

		$file_name = oftuf_get_submission_file_label( $submission );
		$file_name = $file_name ? $file_name : basename( $submission['file_path'] );

		nocache_headers();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file_name ) . '"' );
		header( 'Content-Length: ' . filesize( $submission['file_path'] ) );
		readfile( $submission['file_path'] );
		exit;
	}

	/**
	 * Delete attachments associated with selected submissions.
	 *
	 * @param int[] $submission_ids Submission IDs.
	 * @return int
	 */
	protected function delete_submission_attachments( $submission_ids ) {
		$submissions = $this->database->get_submissions_by_ids( $submission_ids );

		if ( empty( $submissions ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/post.php';

		$deleted = 0;

		foreach ( $submissions as $submission ) {
			if ( ! empty( $submission['file_path'] ) && file_exists( $submission['file_path'] ) ) {
				if ( @unlink( $submission['file_path'] ) ) {
					++$deleted;
				}
			}

			$attachment_id = ! empty( $submission['attachment_id'] ) ? absint( $submission['attachment_id'] ) : 0;

			if ( ! $attachment_id ) {
				continue;
			}

			if ( wp_delete_attachment( $attachment_id, true ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Neutralize spreadsheet formulas in exported CSV content.
	 *
	 * @param string $value Cell value.
	 * @return string
	 */
	protected function neutralize_csv_cell( $value ) {
		$value = (string) $value;

		if ( preg_match( '/^\s*[=\+\-@]/', $value ) ) {
			return "'" . $value;
		}

		return $value;
	}

	/**
	 * Get the CSV-safe file URL for a submission.
	 *
	 * @param array $submission Submission row.
	 * @return string
	 */
	protected function get_submission_csv_file_url( $submission ) {
		if ( ! empty( $submission['file_path'] ) && ! empty( $submission['id'] ) ) {
			return oftuf_get_submission_download_url( $submission['id'] );
		}

		if ( ! empty( $submission['file_url'] ) && wp_http_validate_url( $submission['file_url'] ) ) {
			return $submission['file_url'];
		}

		return '';
	}
}
