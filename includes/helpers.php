<?php
/**
 * Helper functions for Lightweight Upload Form.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function luf_get_allowed_mime_types() {
	$mime_types = array(
		'pdf'  => 'application/pdf',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
	);

	return (array) apply_filters( 'luf_allowed_mime_types', $mime_types );
}

function luf_get_max_upload_size() {
	$default_size = 5 * 1024 * 1024;

	return (int) apply_filters( 'luf_max_upload_size', $default_size );
}

function luf_is_file_required() {
	return (bool) apply_filters( 'luf_file_required', false );
}

function luf_get_recipient_email() {
	$default_email = get_option( 'admin_email' );

	return sanitize_email( apply_filters( 'luf_recipient_email', $default_email ) );
}

function luf_get_submissions_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'luf_submissions';
}

function luf_format_file_size( $bytes ) {
	$bytes = (int) $bytes;

	if ( $bytes >= MB_IN_BYTES ) {
		return round( $bytes / MB_IN_BYTES, 2 ) . ' MB';
	}

	if ( $bytes >= KB_IN_BYTES ) {
		return round( $bytes / KB_IN_BYTES, 2 ) . ' KB';
	}

	return $bytes . ' B';
}

function luf_get_flash_transient_key( $token ) {
	return 'luf_flash_' . sanitize_key( $token );
}

function luf_get_current_url() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$request_uri = remove_query_arg( 'luf_notice', $request_uri );

	return home_url( $request_uri );
}
