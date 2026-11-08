<?php
namespace WCSC;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Updater
 *
 * Handles automatic plugin updates from GitHub.
 *
 * UPDATE STRATEGY (3-tier fallback):
 *   1. update.json  — Raw file on main branch. No API rate limits. Always works.
 *      URL: https://raw.githubusercontent.com/developer-zahir/wc-smart-checkout-builder/main/update.json
 *   2. GitHub Releases API — Official release endpoint (60 req/hr unauthenticated).
 *      URL: https://api.github.com/repos/developer-zahir/wc-smart-checkout-builder/releases/latest
 *   3. GitHub Tags API — Last fallback using tags list.
 *      URL: https://api.github.com/repos/developer-zahir/wc-smart-checkout-builder/tags
 *
 * ZIP PACKAGING REQUIREMENT:
 *   The ZIP attached to GitHub Release MUST contain the plugin folder at root:
 *   wc-smart-checkout-builder/
 *   └── wc-smart-checkout-builder.php
 *   └── includes/
 *   └── assets/
 *   └── ...
 *
 * HOW TO RELEASE AN UPDATE:
 *   1. Bump version in wc-smart-checkout-builder.php (header + constant)
 *   2. Update update.json with new version + download_url
 *   3. Build ZIP: Compress-Archive -Path "plugin-folder" -DestinationPath "wc-smart-checkout-builder.zip"
 *   4. git add . && git commit -m "Release vX.X.X" && git push origin main
 *   5. git tag vX.X.X && git push origin vX.X.X
 *   6. gh release create vX.X.X wc-smart-checkout-builder.zip --title "..." --notes "..."
 */
class Updater {

	/**
	 * GitHub repository (owner/repo).
	 *
	 * @var string
	 */
	protected $repository = 'developer-zahir/wc-smart-checkout-builder';

	/**
	 * Plugin slug (folder name).
	 *
	 * @var string
	 */
	protected $plugin_slug = 'wc-smart-checkout-builder';

	/**
	 * Plugin basename (folder/main-file.php).
	 *
	 * @var string
	 */
	protected $plugin_basename;

	/**
	 * Currently installed plugin version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Transient cache key for update data.
	 *
	 * @var string
	 */
	protected $cache_key = 'wcsc_update_data_v2';

	/**
	 * Cache duration in seconds (12 hours).
	 *
	 * @var int
	 */
	protected $cache_seconds = 43200;

	/**
	 * Constructor — registers all WordPress hooks.
	 *
	 * @param string $plugin_basename  e.g. wc-smart-checkout-builder/wc-smart-checkout-builder.php
	 * @param string $version          Currently installed version e.g. 1.9.21
	 */
	public function __construct( $plugin_basename, $version ) {
		$this->plugin_basename = $plugin_basename;
		$this->version         = $version;

		// Core update hooks.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'site_transient_update_plugins', array( $this, 'check_update' ) );

		// Plugin information popup (View version details).
		add_filter( 'plugins_api', array( $this, 'plugin_info_popup' ), 20, 3 );

		// Fix folder name after GitHub ZIP extraction.
		add_filter( 'upgrader_post_install', array( $this, 'fix_folder_after_install' ), 10, 3 );

		// Clear our cache after any plugin update completes.
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache_on_update' ), 10, 2 );

		// Admin: manual "Check Update" link in plugin row.
		add_filter( 'plugin_row_meta', array( $this, 'add_update_check_link' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'handle_manual_check_action' ) );
		add_action( 'admin_notices', array( $this, 'show_update_admin_notice' ) );
	}

	// =========================================================================
	// SECTION 1: Fetch Remote Version Data
	// =========================================================================

	/**
	 * Fetch remote update data using 3-tier fallback strategy.
	 *
	 * @param bool $force Skip cache and force fresh fetch.
	 * @return object|false  stdClass with version, download_url, changelog, etc. Or false on failure.
	 */
	protected function fetch_remote_data( $force = false ) {

		// Return cached result unless forced.
		if ( ! $force ) {
			$cached = get_site_transient( $this->cache_key );
			if ( false !== $cached && is_object( $cached ) && ! empty( $cached->version ) ) {
				return $cached;
			}
		}

		$data = false;

		// === TIER 1: update.json manifest (fastest, no rate limit) ===
		$data = $this->fetch_from_update_json();

		// === TIER 2: GitHub Releases API ===
		if ( ! $data ) {
			$data = $this->fetch_from_releases_api();
		}

		// === TIER 3: GitHub Tags API ===
		if ( ! $data ) {
			$data = $this->fetch_from_tags_api();
		}

		if ( $data ) {
			set_site_transient( $this->cache_key, $data, $this->cache_seconds );
		}

		return $data;
	}

	/**
	 * TIER 1: Fetch from update.json on main branch (no API rate limits).
	 *
	 * @return object|false
	 */
	protected function fetch_from_update_json() {
		$url      = sprintf(
			'https://raw.githubusercontent.com/%s/main/update.json',
			$this->repository
		);
		$response = wp_remote_get( $url, $this->get_request_args() );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body );

		if ( empty( $json ) || ! is_object( $json ) || empty( $json->version ) || empty( $json->download_url ) ) {
			return false;
		}

		return (object) array(
			'version'      => trim( $json->version ),
			'download_url' => esc_url_raw( $json->download_url ),
			'details_url'  => ! empty( $json->details_url ) ? esc_url_raw( $json->details_url ) : sprintf( 'https://github.com/%s', $this->repository ),
			'changelog'    => ! empty( $json->changelog ) ? $json->changelog : '',
			'requires'     => ! empty( $json->requires ) ? $json->requires : '5.8',
			'tested'       => ! empty( $json->tested ) ? $json->tested : '6.7',
			'requires_php' => ! empty( $json->requires_php ) ? $json->requires_php : '7.4',
			'source'       => 'update_json',
		);
	}

	/**
	 * TIER 2: Fetch from GitHub Releases API.
	 *
	 * @return object|false
	 */
	protected function fetch_from_releases_api() {
		$url      = sprintf(
			'https://api.github.com/repos/%s/releases/latest',
			$this->repository
		);
		$response = wp_remote_get( $url, $this->get_request_args() );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body    = wp_remote_retrieve_body( $response );
		$release = json_decode( $body );

		if ( empty( $release ) || ! is_object( $release ) || empty( $release->tag_name ) ) {
			return false;
		}

		$version      = ltrim( $release->tag_name, 'v' );
		$download_url = $this->extract_zip_url_from_assets( $release );

		if ( empty( $download_url ) ) {
			// Fallback to GitHub archive URL (always available).
			$download_url = sprintf(
				'https://github.com/%s/releases/download/%s/wc-smart-checkout-builder.zip',
				$this->repository,
				$release->tag_name
			);
		}

		return (object) array(
			'version'      => $version,
			'download_url' => $download_url,
			'details_url'  => sprintf( 'https://github.com/%s/releases/tag/%s', $this->repository, $release->tag_name ),
			'changelog'    => ! empty( $release->body ) ? $release->body : '',
			'requires'     => '5.8',
			'tested'       => '6.7',
			'requires_php' => '7.4',
			'source'       => 'releases_api',
		);
	}

	/**
	 * TIER 3: Fetch from GitHub Tags API.
	 *
	 * @return object|false
	 */
	protected function fetch_from_tags_api() {
		$url      = sprintf(
			'https://api.github.com/repos/%s/tags',
			$this->repository
		);
		$response = wp_remote_get( $url, $this->get_request_args() );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$tags = json_decode( $body );

		if ( empty( $tags ) || ! is_array( $tags ) || empty( $tags[0]->name ) ) {
			return false;
		}

		$latest_tag   = $tags[0];
		$version      = ltrim( $latest_tag->name, 'v' );
		$download_url = sprintf(
			'https://github.com/%s/releases/download/%s/wc-smart-checkout-builder.zip',
			$this->repository,
			$latest_tag->name
		);

		return (object) array(
			'version'      => $version,
			'download_url' => $download_url,
			'details_url'  => sprintf( 'https://github.com/%s', $this->repository ),
			'changelog'    => sprintf( 'Update to version %s.', $version ),
			'requires'     => '5.8',
			'tested'       => '6.7',
			'requires_php' => '7.4',
			'source'       => 'tags_api',
		);
	}

	/**
	 * Extract the correct ZIP URL from GitHub release assets array.
	 * Looks for wc-smart-checkout-builder.zip specifically.
	 *
	 * @param object $release GitHub release object.
	 * @return string
	 */
	protected function extract_zip_url_from_assets( $release ) {
		if ( empty( $release->assets ) || ! is_array( $release->assets ) ) {
			return '';
		}

		// Exact match first.
		foreach ( $release->assets as $asset ) {
			if ( isset( $asset->name ) && 'wc-smart-checkout-builder.zip' === strtolower( $asset->name ) && ! empty( $asset->browser_download_url ) ) {
				return esc_url_raw( $asset->browser_download_url );
			}
		}

		// Partial match fallback.
		foreach ( $release->assets as $asset ) {
			$name = isset( $asset->name ) ? strtolower( $asset->name ) : '';
			if ( false !== strpos( $name, 'wc-smart-checkout-builder' ) && '.zip' === substr( $name, -4 ) && ! empty( $asset->browser_download_url ) ) {
				return esc_url_raw( $asset->browser_download_url );
			}
		}

		return '';
	}

	/**
	 * Default HTTP request args (with User-Agent for GitHub API compliance).
	 *
	 * @return array
	 */
	protected function get_request_args() {
		return array(
			'timeout'   => 20,
			'sslverify' => true,
			'headers'   => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url() . '; Plugin/WCSC',
			),
		);
	}

	// =========================================================================
	// SECTION 2: WordPress Update Hooks
	// =========================================================================

	/**
	 * Hook into WordPress plugin update transient.
	 * Injects our plugin's update data if a newer version is available.
	 *
	 * @param object $transient WordPress update_plugins transient.
	 * @return object
	 */
	public function check_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new \stdClass();
		}

		if ( ! isset( $transient->checked ) ) {
			$transient->checked = array();
		}
		if ( ! isset( $transient->response ) ) {
			$transient->response = array();
		}
		if ( ! isset( $transient->no_update ) ) {
			$transient->no_update = array();
		}

		// Mark this plugin as checked.
		$transient->checked[ $this->plugin_basename ] = $this->version;

		// Force fresh check only on explicit "Check for Updates" button click.
		$force = $this->is_force_check_request();
		$data  = $this->fetch_remote_data( $force );

		if ( ! $data ) {
			return $transient;
		}

		$update_obj = $this->build_update_object( $data );

		if ( version_compare( $data->version, $this->version, '>' ) ) {
			// Newer version available — add to response.
			$transient->response[ $this->plugin_basename ] = $update_obj;
			unset( $transient->no_update[ $this->plugin_basename ] );
		} else {
			// Already up to date.
			$transient->no_update[ $this->plugin_basename ] = $update_obj;
			unset( $transient->response[ $this->plugin_basename ] );
		}

		return $transient;
	}

	/**
	 * Build the stdClass object WordPress expects for a plugin update.
	 *
	 * @param object $data Remote update data.
	 * @return object
	 */
	protected function build_update_object( $data ) {
		return (object) array(
			'id'            => $this->plugin_basename,
			'slug'          => $this->plugin_slug,
			'plugin'        => $this->plugin_basename,
			'new_version'   => $data->version,
			'url'           => $data->details_url,
			'package'       => $data->download_url,
			'icons'         => array(),
			'banners'       => array(),
			'banners_rtl'   => array(),
			'requires'      => $data->requires,
			'tested'        => $data->tested,
			'requires_php'  => $data->requires_php,
			'compatibility' => new \stdClass(),
		);
	}

	/**
	 * Detect if this request is a forced update check (WordPress "Check Again" button).
	 *
	 * @return bool
	 */
	protected function is_force_check_request() {
		if ( isset( $_GET['force-check'] ) && '1' === $_GET['force-check'] ) {
			return true;
		}
		if ( isset( $_GET['action'] ) && 'wcsc_check_update' === $_GET['action'] ) {
			return true;
		}
		if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'update-plugin', 'update-selected' ), true ) ) {
			return true;
		}
		return false;
	}

	// =========================================================================
	// SECTION 3: Plugin Info Popup
	// =========================================================================

	/**
	 * Provide plugin info for the "View version details" popup in WP Admin.
	 *
	 * @param false|object|array $result
	 * @param string             $action
	 * @param object             $args
	 * @return false|object
	 */
	public function plugin_info_popup( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		$data = $this->fetch_remote_data();
		if ( ! $data ) {
			return $result;
		}

		// Convert changelog from markdown-ish to HTML.
		$changelog_html = wpautop( wp_kses_post( $data->changelog ) );

		return (object) array(
			'name'          => 'WC Smart Checkout Builder',
			'slug'          => $this->plugin_slug,
			'version'       => $data->version,
			'author'        => '<a href="https://github.com/developer-zahir">Developer Zahir</a>',
			'homepage'      => sprintf( 'https://github.com/%s', $this->repository ),
			'requires'      => $data->requires,
			'tested'        => $data->tested,
			'requires_php'  => $data->requires_php,
			'download_link' => $data->download_url,
			'last_updated'  => current_time( 'Y-m-d' ),
			'sections'      => array(
				'description' => '<p>' . esc_html__( 'A powerful Elementor widget for selecting WooCommerce products, handling variations, and embedding a native WooCommerce checkout on any page.', 'wc-smart-checkout-builder' ) . '</p>',
				'changelog'   => ! empty( $changelog_html ) ? $changelog_html : '<p>' . esc_html__( 'Maintenance and improvements.', 'wc-smart-checkout-builder' ) . '</p>',
			),
		);
	}

	// =========================================================================
	// SECTION 4: Post-Install Folder Fix
	// =========================================================================

	/**
	 * After WordPress extracts the ZIP, rename the folder to the correct plugin slug.
	 * GitHub ZIPs are extracted as: developer-zahir-wc-smart-checkout-builder-HASH/
	 * But our ZIP should already contain: wc-smart-checkout-builder/
	 *
	 * @param bool  $response   Install response.
	 * @param array $hook_extra Extra args from upgrader.
	 * @param array $result     Upgrader result.
	 * @return array
	 */
	public function fix_folder_after_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $result;
		}

		$correct_path  = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->plugin_slug;
		$extracted_path = isset( $result['destination'] ) ? untrailingslashit( $result['destination'] ) : '';

		if ( $extracted_path && $extracted_path !== $correct_path ) {
			// Remove existing plugin folder if exists.
			if ( $wp_filesystem->exists( $correct_path ) ) {
				$wp_filesystem->delete( $correct_path, true );
			}

			// Move extracted folder to correct location.
			$moved = $wp_filesystem->move( $extracted_path, $correct_path, true );

			if ( ! $moved ) {
				// Fallback: copy then delete.
				copy_dir( $extracted_path, $correct_path );
				$wp_filesystem->delete( $extracted_path, true );
			}

			$result['destination']      = $correct_path;
			$result['destination_name'] = $this->plugin_slug;
		}

		// Bust all caches after update.
		$this->clear_all_caches();

		return $result;
	}

	// =========================================================================
	// SECTION 5: Cache Management
	// =========================================================================

	/**
	 * Clear all update-related caches after a plugin update completes.
	 *
	 * @param \WP_Upgrader $upgrader
	 * @param array        $options
	 */
	public function clear_cache_on_update( $upgrader, $options ) {
		if ( isset( $options['action'], $options['type'] ) && 'update' === $options['action'] && 'plugin' === $options['type'] ) {
			$this->clear_all_caches();
		}
	}

	/**
	 * Delete our custom cache transient and WordPress core update_plugins transient.
	 */
	protected function clear_all_caches() {
		delete_site_transient( $this->cache_key );
		delete_site_transient( 'update_plugins' );
	}

	// =========================================================================
	// SECTION 6: Manual Update Check (Plugin Row Link)
	// =========================================================================

	/**
	 * Add "Check Update" action link in Plugins list → row meta.
	 *
	 * @param array  $links
	 * @param string $file
	 * @return array
	 */
	public function add_update_check_link( $links, $file ) {
		if ( $file !== $this->plugin_basename ) {
			return $links;
		}

		$url     = wp_nonce_url(
			admin_url( 'plugins.php?action=wcsc_check_update' ),
			'wcsc_check_update_nonce'
		);
		$links[] = sprintf(
			'<a href="%s" style="color:#2271b1;font-weight:600;">%s</a>',
			esc_url( $url ),
			esc_html__( 'Check for Update', 'wc-smart-checkout-builder' )
		);

		return $links;
	}

	/**
	 * Handle the manual update check request (from "Check for Update" link).
	 * Clears cache, force-fetches remote data, injects into WP transient, redirects.
	 */
	public function handle_manual_check_action() {
		if ( ! isset( $_GET['action'] ) || 'wcsc_check_update' !== $_GET['action'] ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to update plugins.', 'wc-smart-checkout-builder' ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wcsc_check_update_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wc-smart-checkout-builder' ) );
		}

		// Bust cache and force a fresh API call.
		$this->clear_all_caches();
		$data = $this->fetch_remote_data( true );

		$status = 'no_response';

		if ( $data ) {
			if ( version_compare( $data->version, $this->version, '>' ) ) {
				$status = 'update_available';

				// Inject update into WordPress transient so it shows immediately.
				$transient = get_site_transient( 'update_plugins' );
				if ( ! is_object( $transient ) ) {
					$transient = new \stdClass();
				}
				$transient = $this->check_update( $transient );
				set_site_transient( 'update_plugins', $transient );
			} else {
				$status = 'up_to_date';
			}
		}

		wp_safe_redirect( admin_url( 'plugins.php?wcsc_update_status=' . $status ) );
		exit;
	}

	/**
	 * Display admin notice after manual update check redirect.
	 */
	public function show_update_admin_notice() {
		if ( ! isset( $_GET['wcsc_update_status'] ) ) {
			return;
		}

		$status = sanitize_key( $_GET['wcsc_update_status'] );

		$messages = array(
			'update_available' => array(
				'class'   => 'notice-warning',
				'message' => sprintf(
					/* translators: %s = link to plugins page */
					esc_html__( 'WC Smart Checkout Builder: A new update is available! %s', 'wc-smart-checkout-builder' ),
					'<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Update now ↓', 'wc-smart-checkout-builder' ) . '</a>'
				),
			),
			'up_to_date' => array(
				'class'   => 'notice-success',
				'message' => esc_html__( 'WC Smart Checkout Builder: You are already running the latest version. ✓', 'wc-smart-checkout-builder' ),
			),
			'no_response' => array(
				'class'   => 'notice-error',
				'message' => esc_html__( 'WC Smart Checkout Builder: Could not reach update server. Please try again later.', 'wc-smart-checkout-builder' ),
			),
		);

		if ( isset( $messages[ $status ] ) ) {
			printf(
				'<div class="notice %s is-dismissible"><p>%s</p></div>',
				esc_attr( $messages[ $status ]['class'] ),
				wp_kses_post( $messages[ $status ]['message'] )
			);
		}
	}
}
