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
			__( 'Submissions', 'oft-upload-form' ),
			__( 'OFT Upload Form', 'oft-upload-form' ),
			'manage_options',
			'oftuf-submissions',
			array( $this, 'render_page' ),
			'dashicons-feedback',
			26
		);

		$submissions_hook = add_submenu_page(
			'oftuf-submissions',
			__( 'Submissions', 'oft-upload-form' ),
			__( 'Submissions', 'oft-upload-form' ),
			'manage_options',
			'oftuf-submissions',
			array( $this, 'render_page' )
		);

		$settings_hook = add_submenu_page(
			'oftuf-submissions',
			__( 'Help', 'oft-upload-form' ),
			__( 'Help', 'oft-upload-form' ),
			'manage_options',
			'oftuf-settings',
			array( $this, 'render_settings_page' )
		);

		add_action( 'load-' . $hook, array( $this, 'enqueue_assets' ) );
		add_action( 'load-' . $submissions_hook, array( $this, 'enqueue_assets' ) );
		add_action( 'load-' . $settings_hook, array( $this, 'enqueue_assets' ) );
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
	 * Render the settings page.
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

		fputcsv( $output, array( 'ID', 'Name', 'Email', 'Message', 'File URL', 'Attachment ID', 'Created At' ) );

		foreach ( $rows as $row ) {
			fputcsv(
				$output,
				array(
					$row['id'],
					$row['name'],
					$row['email'],
					$row['message'],
					$row['file_url'],
					$row['attachment_id'],
					$row['created_at'],
				)
			);
		}

		fclose( $output );
		exit;
	}

	/**
	 * Send a test email from the settings page.
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
					'page'           => 'oftuf-settings',
					'oftuf_test_email' => $sent ? 'success' : 'error',
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
						'page'            => 'oftuf-submissions',
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
					'page'            => 'oftuf-submissions',
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
}


