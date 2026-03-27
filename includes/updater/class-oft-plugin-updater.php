<?php
/**
 * Reusable self-hosted plugin updater for One Feature Trap plugins.
 *
 * Reuse in another plugin:
 * 1. Copy the /includes/updater directory into that plugin.
 * 2. Require this file from the main plugin bootstrap.
 * 3. Instantiate OFT_Plugin_Updater with plugin_file, plugin_slug, and plugin_name.
 *
 * Publish updates:
 * 1. Upload a zip to /plugin-downloads/{plugin-slug}.zip.
 * 2. Publish metadata JSON to /plugin-updates/{plugin-slug}/info.json.
 * 3. Bump the version in the plugin header and the remote info.json version.
 *
 * @package LightweightUploadForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OFT_Plugin_Updater {

	/**
	 * Default metadata host for reusable updater instances.
	 *
	 * @var string
	 */
	const DEFAULT_METADATA_BASE = 'https://onefeaturetrap.com/plugin-updates/';

	/**
	 * Plugin main file.
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Plugin basename used by WordPress updates.
	 *
	 * @var string
	 */
	protected $plugin_basename;

	/**
	 * Plugin slug used in metadata and details modal requests.
	 *
	 * @var string
	 */
	protected $plugin_slug;

	/**
	 * Human readable plugin name.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * Remote metadata endpoint.
	 *
	 * @var string
	 */
	protected $metadata_url;

	/**
	 * Transient key used for cached metadata.
	 *
	 * @var string
	 */
	protected $cache_key;

	/**
	 * Cache duration in seconds.
	 *
	 * @var int
	 */
	protected $cache_ttl;

	/**
	 * Installed plugin version from the plugin header.
	 *
	 * @var string
	 */
	protected $installed_version;

	/**
	 * Debug page slug.
	 *
	 * @var string
	 */
	protected $debug_page_slug;

	/**
	 * Configure the updater and register hooks.
	 *
	 * @param array $config Plugin-specific updater config.
	 */
	public function __construct( $config ) {
		$defaults = array(
			'plugin_file'  => '',
			'plugin_slug'  => '',
			'plugin_name'  => '',
			'metadata_url' => '',
			'cache_key'    => '',
			'cache_ttl'    => 6 * HOUR_IN_SECONDS,
		);

		$config = wp_parse_args( is_array( $config ) ? $config : array(), $defaults );

		$this->plugin_file      = isset( $config['plugin_file'] ) ? (string) $config['plugin_file'] : '';
		$this->plugin_slug      = sanitize_key( $config['plugin_slug'] );
		$this->plugin_name      = isset( $config['plugin_name'] ) ? sanitize_text_field( $config['plugin_name'] ) : '';
		$this->plugin_basename  = $this->plugin_file ? plugin_basename( $this->plugin_file ) : '';
		$this->metadata_url     = ! empty( $config['metadata_url'] ) ? esc_url_raw( $config['metadata_url'] ) : $this->build_metadata_url( $this->plugin_slug );
		$this->cache_key        = ! empty( $config['cache_key'] ) ? sanitize_key( $config['cache_key'] ) : 'oft_updater_' . $this->plugin_slug;
		$this->cache_ttl        = max( MINUTE_IN_SECONDS, absint( $config['cache_ttl'] ) );
		$this->installed_version = $this->detect_installed_version();
		$this->debug_page_slug  = 'oft-plugin-updater-' . $this->plugin_slug;

		if ( empty( $this->plugin_file ) || empty( $this->plugin_slug ) || empty( $this->plugin_name ) || empty( $this->plugin_basename ) ) {
			return;
		}

		if ( empty( $this->installed_version ) ) {
			return;
		}

		add_filter( 'site_transient_update_plugins', array( $this, 'inject_update' ) );
		add_filter( 'plugins_api', array( $this, 'filter_plugins_api' ), 20, 3 );
		add_action( 'admin_menu', array( $this, 'register_debug_page' ) );
		add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'add_check_updates_link' ) );
		add_action( 'admin_init', array( $this, 'handle_manual_update_check' ) );
		add_action( 'admin_notices', array( $this, 'maybe_render_update_checked_notice' ) );
		add_action( 'upgrader_process_complete', array( $this, 'purge_cache_after_update' ), 10, 2 );
	}

	/**
	 * Build the default metadata endpoint from the plugin slug.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return string
	 */
	protected function build_metadata_url( $plugin_slug ) {
		if ( empty( $plugin_slug ) ) {
			return '';
		}

		return trailingslashit( self::DEFAULT_METADATA_BASE . rawurlencode( $plugin_slug ) ) . 'info.json';
	}

	/**
	 * Read the installed version from the plugin header.
	 *
	 * @return string
	 */
	protected function detect_installed_version() {
		if ( empty( $this->plugin_file ) || ! file_exists( $this->plugin_file ) ) {
			return '';
		}

		$headers = get_file_data(
			$this->plugin_file,
			array(
				'Version' => 'Version',
			),
			'plugin'
		);

		return ! empty( $headers['Version'] ) ? (string) $headers['Version'] : '';
	}

	/**
	 * Inject update data into the native WordPress plugin updates UI.
	 *
	 * @param object $transient Plugin update transient.
	 * @return object
	 */
	public function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new stdClass();
		}

		$metadata = $this->get_metadata();

		if ( ! $metadata ) {
			return $transient;
		}

		$plugin_update = $this->build_update_payload( $metadata );

		if ( ! $plugin_update ) {
			return $transient;
		}

		if ( version_compare( $metadata['version'], $this->installed_version, '>' ) ) {
			if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
				$transient->response = array();
			}

			$transient->response[ $this->plugin_basename ] = $plugin_update;
			return $transient;
		}

		if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
			$transient->no_update = array();
		}

		$transient->no_update[ $this->plugin_basename ] = $plugin_update;

		return $transient;
	}

	/**
	 * Supply plugin information to the "View details" modal.
	 *
	 * @param false|object|array $result Existing result.
	 * @param string             $action API action.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object|array
	 */
	public function filter_plugins_api( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || $this->plugin_slug !== $args->slug ) {
			return $result;
		}

		$metadata = $this->get_metadata();

		if ( ! $metadata ) {
			return $result;
		}

		return $this->build_plugin_information( $metadata );
	}

	/**
	 * Register a minimal debug page under Tools.
	 *
	 * @return void
	 */
	public function register_debug_page() {
		add_management_page(
			sprintf( '%s Update Debug', $this->plugin_name ),
			sprintf( '%s Update Debug', $this->plugin_name ),
			'manage_options',
			$this->debug_page_slug,
			array( $this, 'render_debug_page' )
		);
	}

	/**
	 * Add a direct link to manually refresh update data from the plugins list.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_check_updates_link( $links ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $links;
		}

		$check_url = wp_nonce_url(
			add_query_arg(
				array(
					'oft_check_updates' => 1,
					'plugin'            => rawurlencode( $this->plugin_basename ),
				),
				admin_url( 'plugins.php' )
			),
			'oft_check_updates_' . $this->plugin_slug
		);

		array_unshift(
			$links,
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $check_url ),
				esc_html( 'Check for updates' )
			)
		);

		return $links;
	}

	/**
	 * Handle a manual update check from the plugins screen.
	 *
	 * @return void
	 */
	public function handle_manual_update_check() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $_GET['oft_check_updates'] ) || empty( $_GET['plugin'] ) ) {
			return;
		}

		$plugin = sanitize_text_field( wp_unslash( $_GET['plugin'] ) );

		if ( $this->plugin_basename !== $plugin ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'oft_check_updates_' . $this->plugin_slug ) ) {
			return;
		}

		delete_site_transient( $this->cache_key );
		delete_site_transient( 'update_plugins' );

		if ( function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
		}

		$redirect_args = array(
			'oft_update_checked' => 1,
		);

		if ( isset( $_GET['plugin_status'] ) ) {
			$redirect_args['plugin_status'] = sanitize_text_field( wp_unslash( $_GET['plugin_status'] ) );
		}

		if ( isset( $_GET['paged'] ) ) {
			$redirect_args['paged'] = absint( $_GET['paged'] );
		}

		if ( isset( $_GET['s'] ) ) {
			$redirect_args['s'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}

		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'plugins.php' ) ) );
		exit;
	}

	/**
	 * Show a simple notice after manually refreshing update data.
	 *
	 * @return void
	 */
	public function maybe_render_update_checked_notice() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) || empty( $_GET['oft_update_checked'] ) ) {
			return;
		}
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $this->plugin_name . ' update data refreshed. If a newer version exists, WordPress will show the normal update link on the Plugins screen.' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Clear cached metadata after this plugin finishes updating.
	 *
	 * @param WP_Upgrader $upgrader_object Upgrader instance.
	 * @param array       $options         Upgrade options.
	 * @return void
	 */
	public function purge_cache_after_update( $upgrader_object, $options ) {
		if ( empty( $options['action'] ) || empty( $options['type'] ) ) {
			return;
		}

		if ( 'update' !== $options['action'] || 'plugin' !== $options['type'] ) {
			return;
		}

		if ( empty( $options['plugins'] ) || ! is_array( $options['plugins'] ) ) {
			return;
		}

		if ( in_array( $this->plugin_basename, $options['plugins'], true ) ) {
			delete_site_transient( $this->cache_key );
		}
	}

	/**
	 * Render admin-only updater debug output.
	 *
	 * @return void
	 */
	public function render_debug_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['oft_refresh'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'oft_updater_refresh_' . $this->plugin_slug ) ) {
			delete_site_transient( $this->cache_key );
		}

		$metadata     = $this->get_metadata();
		$cache_record = $this->get_cache_record();
		$has_update   = $metadata && version_compare( $metadata['version'], $this->installed_version, '>' );
		$refresh_url  = wp_nonce_url(
			add_query_arg(
				array(
					'page'        => $this->debug_page_slug,
					'oft_refresh' => 1,
				),
				admin_url( 'tools.php' )
			),
			'oft_updater_refresh_' . $this->plugin_slug
		);
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->plugin_name . ' Update Debug' ); ?></h1>
			<p>
				<a class="button" href="<?php echo esc_url( $refresh_url ); ?>">Refresh metadata</a>
			</p>
			<table class="widefat striped" style="max-width: 960px">
				<tbody>
					<tr>
						<th scope="row">Plugin slug</th>
						<td><?php echo esc_html( $this->plugin_slug ); ?></td>
					</tr>
					<tr>
						<th scope="row">Installed version</th>
						<td><?php echo esc_html( $this->installed_version ? $this->installed_version : 'Unknown' ); ?></td>
					</tr>
					<tr>
						<th scope="row">Metadata URL</th>
						<td><code><?php echo esc_html( $this->metadata_url ); ?></code></td>
					</tr>
					<tr>
						<th scope="row">Update available</th>
						<td><?php echo esc_html( $has_update ? 'Yes' : 'No' ); ?></td>
					</tr>
					<tr>
						<th scope="row">Last fetched metadata</th>
						<td>
							<?php if ( ! empty( $cache_record['fetched_at'] ) ) : ?>
								<p><strong>Fetched:</strong> <?php echo esc_html( gmdate( 'Y-m-d H:i:s', (int) $cache_record['fetched_at'] ) . ' UTC' ); ?></p>
							<?php endif; ?>
							<pre style="white-space: pre-wrap; max-width: 900px; overflow: auto;"><?php echo esc_html( wp_json_encode( $metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ?: 'No cached metadata available.' ); ?></pre>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Get cached metadata or fetch it from the remote endpoint.
	 *
	 * @return array|false
	 */
	protected function get_metadata() {
		$cache_record = $this->get_cache_record();

		if ( ! empty( $cache_record['data'] ) && is_array( $cache_record['data'] ) ) {
			return $cache_record['data'];
		}

		$metadata = $this->fetch_remote_metadata();

		if ( ! $metadata ) {
			return false;
		}

		$this->store_cache_record(
			array(
				'fetched_at' => time(),
				'data'       => $metadata,
			)
		);

		return $metadata;
	}

	/**
	 * Read the current transient payload.
	 *
	 * @return array
	 */
	protected function get_cache_record() {
		$cache_record = get_site_transient( $this->cache_key );

		return is_array( $cache_record ) ? $cache_record : array();
	}

	/**
	 * Persist normalized metadata.
	 *
	 * @param array $cache_record Cache payload.
	 * @return void
	 */
	protected function store_cache_record( $cache_record ) {
		set_site_transient( $this->cache_key, $cache_record, $this->cache_ttl );
	}

	/**
	 * Fetch and validate the remote metadata JSON.
	 *
	 * @return array|false
	 */
	protected function fetch_remote_metadata() {
		if ( empty( $this->metadata_url ) ) {
			return false;
		}

		$response = wp_remote_get(
			$this->metadata_url,
			array(
				'timeout'    => 10,
				'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return false;
		}

		$metadata = json_decode( $body, true );

		if ( ! is_array( $metadata ) ) {
			return false;
		}

		return $this->normalize_metadata( $metadata );
	}

	/**
	 * Normalize metadata into the structure expected by WordPress.
	 *
	 * @param array $metadata Raw remote metadata.
	 * @return array|false
	 */
	protected function normalize_metadata( $metadata ) {
		$required_fields = array( 'name', 'slug', 'version', 'download_url' );

		foreach ( $required_fields as $field ) {
			if ( empty( $metadata[ $field ] ) || ! is_string( $metadata[ $field ] ) ) {
				return false;
			}
		}

		$normalized = array(
			'name'         => sanitize_text_field( $metadata['name'] ),
			'slug'         => sanitize_key( $metadata['slug'] ),
			'version'      => sanitize_text_field( $metadata['version'] ),
			'requires'     => isset( $metadata['requires'] ) ? sanitize_text_field( $metadata['requires'] ) : '',
			'tested'       => isset( $metadata['tested'] ) ? sanitize_text_field( $metadata['tested'] ) : '',
			'requires_php' => isset( $metadata['requires_php'] ) ? sanitize_text_field( $metadata['requires_php'] ) : '',
			'last_updated' => isset( $metadata['last_updated'] ) ? sanitize_text_field( $metadata['last_updated'] ) : '',
			'homepage'     => isset( $metadata['homepage'] ) ? esc_url_raw( $metadata['homepage'] ) : '',
			'download_url' => esc_url_raw( $metadata['download_url'] ),
			'sections'     => isset( $metadata['sections'] ) && is_array( $metadata['sections'] ) ? $metadata['sections'] : array(),
			'banners'      => isset( $metadata['banners'] ) && is_array( $metadata['banners'] ) ? $metadata['banners'] : array(),
			'icons'        => isset( $metadata['icons'] ) && is_array( $metadata['icons'] ) ? $metadata['icons'] : array(),
		);

		if ( $this->plugin_slug !== $normalized['slug'] ) {
			return false;
		}

		return $normalized;
	}

	/**
	 * Build the update payload expected inside the plugin update transient.
	 *
	 * @param array $metadata Normalized metadata.
	 * @return object|false
	 */
	protected function build_update_payload( $metadata ) {
		if ( empty( $metadata['download_url'] ) || empty( $metadata['version'] ) ) {
			return false;
		}

		$payload              = new stdClass();
		$payload->id          = $this->plugin_basename;
		$payload->slug        = $this->plugin_slug;
		$payload->plugin      = $this->plugin_basename;
		$payload->new_version = $metadata['version'];
		$payload->url         = ! empty( $metadata['homepage'] ) ? $metadata['homepage'] : '';
		$payload->package     = $metadata['download_url'];
		$payload->tested      = $metadata['tested'];
		$payload->requires    = $metadata['requires'];
		$payload->requires_php = $metadata['requires_php'];
		$payload->icons       = $metadata['icons'];
		$payload->banners     = $metadata['banners'];

		return $payload;
	}

	/**
	 * Build plugin details payload for the "View details" modal.
	 *
	 * @param array $metadata Normalized metadata.
	 * @return object
	 */
	protected function build_plugin_information( $metadata ) {
		$info                = new stdClass();
		$info->name          = $metadata['name'];
		$info->slug          = $metadata['slug'];
		$info->version       = $metadata['version'];
		$info->author        = ! empty( $metadata['homepage'] ) ? sprintf( '<a href="%1$s">%2$s</a>', esc_url( $metadata['homepage'] ), esc_html( $metadata['name'] ) ) : $metadata['name'];
		$info->author_profile = ! empty( $metadata['homepage'] ) ? $metadata['homepage'] : '';
		$info->homepage      = $metadata['homepage'];
		$info->requires      = $metadata['requires'];
		$info->tested        = $metadata['tested'];
		$info->requires_php  = $metadata['requires_php'];
		$info->last_updated  = $metadata['last_updated'];
		$info->download_link = $metadata['download_url'];
		$info->sections      = $metadata['sections'];
		$info->banners       = $metadata['banners'];
		$info->icons         = $metadata['icons'];

		return $info;
	}
}
