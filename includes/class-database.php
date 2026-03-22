<?php
/**
 * Database access layer.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LUF_Database {

	/**
	 * Database schema version.
	 *
	 * @var string
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Create or update the submissions table.
	 *
	 * @return void
	 */
	public function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = luf_get_submissions_table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			email varchar(255) NOT NULL,
			message longtext NOT NULL,
			file_url text NULL,
			file_path text NULL,
			attachment_id bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );
		update_option( 'luf_db_version', self::DB_VERSION );
	}

	/**
	 * Insert a submission row.
	 *
	 * @param array $submission Submission data.
	 * @return int
	 */
	public function insert_submission( $submission ) {
		global $wpdb;

		$table_name = luf_get_submissions_table_name();
		$result     = $wpdb->insert(
			$table_name,
			array(
				'name'          => $submission['name'],
				'email'         => $submission['email'],
				'message'       => $submission['message'],
				'file_url'      => $submission['file_url'],
				'file_path'     => $submission['file_path'],
				'attachment_id' => $submission['attachment_id'],
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Retrieve submissions for the admin screen.
	 *
	 * @param int $limit  Number of rows to fetch.
	 * @param int $offset Offset for pagination.
	 * @return array
	 */
	public function get_submissions( $limit = 50, $offset = 0 ) {
		global $wpdb;

		$table_name = luf_get_submissions_table_name();
		$query      = $wpdb->prepare(
			"SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$limit,
			$offset
		);

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Count all submissions.
	 *
	 * @return int
	 */
	public function count_submissions() {
		global $wpdb;

		$table_name = luf_get_submissions_table_name();

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
