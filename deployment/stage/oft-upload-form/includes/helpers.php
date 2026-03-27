<?php
/**
 * Helper functions for OFT Upload Form.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oftuf_get_allowed_mime_types() {
	$mime_types = array(
		'pdf'  => 'application/pdf',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'txt'  => 'text/plain',
		'zip'  => 'application/zip',
	);

	return (array) apply_filters( 'oftuf_allowed_mime_types', $mime_types );
}

function oftuf_get_max_upload_size() {
	$default_size = 10 * 1024 * 1024;

	return (int) apply_filters( 'oftuf_max_upload_size', $default_size );
}

function oftuf_is_file_required() {
	return (bool) apply_filters( 'oftuf_file_required', false );
}

function oftuf_get_recipient_email() {
	$default_email = get_option( 'admin_email' );

	return sanitize_email( apply_filters( 'oftuf_recipient_email', $default_email ) );
}

function oftuf_get_submissions_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'oftuf_submissions';
}

function oftuf_format_file_size( $bytes ) {
	$bytes = (int) $bytes;

	if ( $bytes >= MB_IN_BYTES ) {
		return round( $bytes / MB_IN_BYTES, 2 ) . ' MB';
	}

	if ( $bytes >= KB_IN_BYTES ) {
		return round( $bytes / KB_IN_BYTES, 2 ) . ' KB';
	}

	return $bytes . ' B';
}

function oftuf_get_flash_transient_key( $token ) {
	return 'oftuf_flash_' . sanitize_key( $token );
}

function oftuf_get_current_url() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$request_uri = remove_query_arg( 'oftuf_notice', $request_uri );

	return home_url( $request_uri );
}

