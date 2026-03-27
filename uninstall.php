<?php
/**
 * Uninstall handler for OFT Upload Form.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$delete_data = get_option( 'oftuf_delete_data_on_uninstall', false );

if ( ! $delete_data ) {
	return;
}

global $wpdb;

$table_name = $wpdb->prefix . 'oftuf_submissions';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

delete_option( 'oftuf_db_version' );
delete_option( 'oftuf_delete_data_on_uninstall' );
delete_option( 'oftuf_allowed_extensions' );

