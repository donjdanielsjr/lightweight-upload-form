<?php
/**
 * Uninstall handler for Lightweight Upload Form.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$delete_data = get_option( 'luf_delete_data_on_uninstall', false );

if ( ! $delete_data ) {
	return;
}

global $wpdb;

$table_name = $wpdb->prefix . 'luf_submissions';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

delete_option( 'luf_db_version' );
delete_option( 'luf_delete_data_on_uninstall' );
