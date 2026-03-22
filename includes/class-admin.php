<?php
/**
 * Admin submissions UI.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LUF_Admin {

	/**
	 * Database service.
	 *
	 * @var LUF_Database
	 */
	protected $database;

	/**
	 * Constructor.
	 *
	 * @param LUF_Database $database Database service.
	 */
	public function __construct( $database ) {
		$this->database = $database;
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$hook = add_menu_page(
			__( 'Upload Form Submissions', 'lightweight-upload-form' ),
			__( 'Upload Form', 'lightweight-upload-form' ),
			'manage_options',
			'luf-submissions',
			array( $this, 'render_page' ),
			'dashicons-feedback',
			26
		);

		add_action( 'load-' . $hook, array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'luf-admin',
			LUF_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			LUF_VERSION
		);

		wp_enqueue_script(
			'luf-admin',
			LUF_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			LUF_VERSION,
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
			wp_die( esc_html__( 'You do not have permission to access this page.', 'lightweight-upload-form' ) );
		}

		$page        = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page    = 20;
		$total_items = $this->database->count_submissions();
		$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
		$page        = min( $page, $total_pages );
		$offset      = ( $page - 1 ) * $per_page;
		$submissions = $this->database->get_submissions( $per_page, $offset );

		include LUF_PLUGIN_PATH . 'templates/admin-page.php';
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

		if ( empty( $_GET['page'] ) || 'luf-submissions' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		if ( empty( $_GET['luf_export'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export submissions.', 'lightweight-upload-form' ) );
		}

		check_admin_referer( 'luf_export_csv' );

		$rows = $this->database->get_submissions( 5000, 0 );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=luf-submissions-' . gmdate( 'Y-m-d' ) . '.csv' );

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
}
