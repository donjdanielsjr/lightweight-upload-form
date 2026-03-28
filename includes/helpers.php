<?php
/**
 * Helper functions for OFT Upload Form.
 *
 * @package OFTUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oftuf_get_file_type_labels() {
	return array(
		'pdf'  => __( 'PDF', 'oft-upload-form' ),
		'jpg'  => __( 'JPG', 'oft-upload-form' ),
		'jpeg' => __( 'JPEG', 'oft-upload-form' ),
		'png'  => __( 'PNG', 'oft-upload-form' ),
		'doc'  => __( 'DOC', 'oft-upload-form' ),
		'docx' => __( 'DOCX', 'oft-upload-form' ),
		'txt'  => __( 'TXT', 'oft-upload-form' ),
		'zip'  => __( 'ZIP', 'oft-upload-form' ),
	);
}

function oftuf_get_default_allowed_extensions() {
	return array( 'pdf', 'jpg', 'jpeg', 'png', 'txt' );
}

function oftuf_get_all_mime_types() {
	return array(
		'pdf'  => 'application/pdf',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'txt'  => 'text/plain',
		'zip'  => 'application/zip',
	);
}

function oftuf_get_allowed_extensions() {
	$saved_extensions = get_option( 'oftuf_allowed_extensions', oftuf_get_default_allowed_extensions() );
	$saved_extensions = array_values(
		array_intersect(
			array_map( 'sanitize_key', (array) $saved_extensions ),
			array_keys( oftuf_get_all_mime_types() )
		)
	);

	if ( empty( $saved_extensions ) ) {
		$saved_extensions = oftuf_get_default_allowed_extensions();
	}

	return $saved_extensions;
}

function oftuf_get_allowed_mime_types() {
	$mime_types         = oftuf_get_all_mime_types();
	$allowed_extensions = oftuf_get_allowed_extensions();
	$mime_types         = array_intersect_key( $mime_types, array_flip( $allowed_extensions ) );

	return (array) apply_filters( 'oftuf_allowed_mime_types', $mime_types );
}

function oftuf_parse_size_to_bytes( $size ) {
	$size = trim( (string) $size );

	if ( '' === $size ) {
		return 0;
	}

	$unit  = strtolower( substr( $size, -1 ) );
	$value = (float) $size;

	switch ( $unit ) {
		case 'g':
			return (int) round( $value * GB_IN_BYTES );
		case 'm':
			return (int) round( $value * MB_IN_BYTES );
		case 'k':
			return (int) round( $value * KB_IN_BYTES );
		default:
			return (int) round( $value );
	}
}

function oftuf_get_server_upload_limit() {
	$limits = array_filter(
		array(
			oftuf_parse_size_to_bytes( ini_get( 'upload_max_filesize' ) ),
			oftuf_parse_size_to_bytes( ini_get( 'post_max_size' ) ),
		)
	);

	if ( empty( $limits ) ) {
		return 0;
	}

	return min( $limits );
}

function oftuf_get_upload_size_choice_values() {
	return array( 2, 5, 10, 15, 25 );
}

function oftuf_get_available_upload_size_choices() {
	$plugin_cap   = 25 * MB_IN_BYTES;
	$server_limit = oftuf_get_server_upload_limit();
	$effective_max = $server_limit > 0 ? min( $plugin_cap, $server_limit ) : $plugin_cap;
	$choices      = array();

	foreach ( oftuf_get_upload_size_choice_values() as $size_mb ) {
		$size_bytes = $size_mb * MB_IN_BYTES;

		if ( $size_bytes <= $effective_max ) {
			$choices[ $size_bytes ] = sprintf(
				/* translators: %d: size in megabytes. */
				__( '%d MB', 'oft-upload-form' ),
				$size_mb
			);
		}
	}

	if ( empty( $choices ) && $effective_max > 0 ) {
		$choices[ $effective_max ] = oftuf_format_file_size( $effective_max );
	}

	return $choices;
}

function oftuf_get_default_upload_size() {
	$choices = array_keys( oftuf_get_available_upload_size_choices() );

	if ( empty( $choices ) ) {
		return 10 * 1024 * 1024;
	}

	$preferred = 10 * MB_IN_BYTES;

	if ( in_array( $preferred, $choices, true ) ) {
		return $preferred;
	}

	return max( $choices );
}

function oftuf_get_saved_upload_size() {
	$choices    = oftuf_get_available_upload_size_choices();
	$saved_size = (int) get_option( 'oftuf_max_upload_size', oftuf_get_default_upload_size() );

	if ( isset( $choices[ $saved_size ] ) ) {
		return $saved_size;
	}

	if ( empty( $choices ) ) {
		return oftuf_get_default_upload_size();
	}

	$valid_sizes = array_keys( $choices );
	rsort( $valid_sizes, SORT_NUMERIC );

	foreach ( $valid_sizes as $valid_size ) {
		if ( $saved_size >= $valid_size ) {
			return $valid_size;
		}
	}

	return min( $valid_sizes );
}

function oftuf_get_effective_max_upload_size() {
	$saved_size   = oftuf_get_saved_upload_size();
	$server_limit = oftuf_get_server_upload_limit();

	if ( $server_limit > 0 ) {
		return min( $saved_size, $server_limit );
	}

	return $saved_size;
}

function oftuf_get_max_upload_size() {
	return (int) apply_filters( 'oftuf_max_upload_size', oftuf_get_effective_max_upload_size() );
}

function oftuf_get_max_upload_size_label() {
	return oftuf_format_file_size( oftuf_get_max_upload_size() );
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

function oftuf_get_private_upload_dir() {
	$upload_dir = wp_upload_dir();

	return trailingslashit( $upload_dir['basedir'] ) . 'oftuf-private';
}

function oftuf_ensure_private_upload_dir() {
	$directory = oftuf_get_private_upload_dir();

	if ( ! wp_mkdir_p( $directory ) ) {
		return new WP_Error( 'oftuf_private_dir_failed', __( 'The private upload directory could not be created.', 'oft-upload-form' ) );
	}

	$index_file = trailingslashit( $directory ) . 'index.php';
	if ( ! file_exists( $index_file ) ) {
		file_put_contents( $index_file, "<?php\n// Silence is golden.\n" );
	}

	$htaccess_file = trailingslashit( $directory ) . '.htaccess';
	if ( ! file_exists( $htaccess_file ) ) {
		file_put_contents( $htaccess_file, "Deny from all\n" );
	}

	$web_config_file = trailingslashit( $directory ) . 'web.config';
	if ( ! file_exists( $web_config_file ) ) {
		file_put_contents( $web_config_file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n  <system.webServer>\n    <authorization>\n      <deny users=\"*\" />\n    </authorization>\n  </system.webServer>\n</configuration>\n" );
	}

	return $directory;
}

function oftuf_get_submission_download_url( $submission_id ) {
	return wp_nonce_url(
		add_query_arg(
			array(
				'oftuf_download' => absint( $submission_id ),
			),
			admin_url( 'admin.php' )
		),
		'oftuf_download_submission_' . absint( $submission_id )
	);
}

function oftuf_get_submission_file_label( $submission ) {
	if ( ! empty( $submission['file_url'] ) && ! wp_http_validate_url( $submission['file_url'] ) ) {
		return $submission['file_url'];
	}

	if ( ! empty( $submission['file_path'] ) ) {
		return basename( $submission['file_path'] );
	}

	if ( ! empty( $submission['file_url'] ) ) {
		return $submission['file_url'];
	}

	return '';
}

function oftuf_get_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';

	return sanitize_text_field( $ip );
}

function oftuf_get_throttle_limit() {
	return max( 1, (int) apply_filters( 'oftuf_throttle_limit', 3 ) );
}

function oftuf_get_throttle_window() {
	return max( MINUTE_IN_SECONDS, (int) apply_filters( 'oftuf_throttle_window', 10 * MINUTE_IN_SECONDS ) );
}

function oftuf_get_throttle_transient_key( $ip ) {
	return 'oftuf_rate_' . md5( $ip );
}

function oftuf_get_current_url() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$request_uri = remove_query_arg( 'oftuf_notice', $request_uri );

	return home_url( $request_uri );
}

