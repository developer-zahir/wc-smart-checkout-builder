<?php
namespace WCSC;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Updater
 *
 * Handles automatic plugin updates directly from the GitHub repository.
 * Allows seamless 1-click updates directly from the WordPress Admin Dashboard.
 */
class Updater {

	/**
	 * GitHub repository (owner/repo).
	 *
	 * @var string
	 */
	protected $repository = 'developer-zahir/wc-smart-checkout-builder';

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $plugin_slug = 'wc-smart-checkout-builder';

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	protected $plugin_basename;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Cache key for transient.
	 *
	 * @var string
	 */
	protected $cache_key = 'wcsc_github_release_data';

	/**
	 * Constructor.
	 *
	 * @param string $plugin_basename
	 * @param string $version
	 */
	public function __construct( $plugin_basename, $version ) {
		$this->plugin_basename = $plugin_basename;
		$this->version         = $version;

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_plugin_update' ) );
		add_filter( 'site_transient_update_plugins', array( $this, 'check_for_plugin_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_popup_information' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'rename_github_folder_after_update' ), 10, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'on_update_completed' ), 10, 2 );
		
		// Add manual update check link and handlers
		add_filter( 'plugin_row_meta', array( $this, 'add_check_update_link' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'handle_manual_update_check' ) );
		add_action( 'admin_notices', array( $this, 'display_update_notices' ) );
	}

	/**
	 * Fetch latest release/version data from GitHub.
	 *
	 * Uses raw file check on main first (bypassing GitHub API rate limits),
	 * then falls back to GitHub Releases API and Tags API.
	 *
	 * @param bool $force_check
	 * @return object|false
	 */
	protected function get_github_release( $force_check = false ) {
		if ( ! $force_check ) {
			$cached = get_site_transient( $this->cache_key );
			if ( false !== $cached && is_object( $cached ) && ! empty( $cached->tag_name ) ) {
				return $cached;
			}
		}

		$request_args = array(
			'timeout'   => 20,
			'sslverify' => false,
			'headers'   => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/' . ( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : '6.7' ) . '; ' . ( function_exists( 'home_url' ) ? home_url() : 'https://developerzahir.com' ),
			),
		);

		$release_data = false;

		// 1. Check official GitHub Releases first
		$rel_url = sprintf( 'https://api.github.com/repos/%s/releases/latest', $this->repository );
		$rel_res = wp_remote_get( $rel_url, $request_args );

		if ( ! is_wp_error( $rel_res ) && 200 === wp_remote_retrieve_response_code( $rel_res ) ) {
			$body = wp_remote_retrieve_body( $rel_res );
			$data = json_decode( $body );

			if ( ! empty( $data ) && is_object( $data ) && ! empty( $data->tag_name ) ) {
				$release_data = $data;
			}
		}

		// 2. Direct raw check on main branch (never rate-limited by GitHub API)
		if ( ! $release_data ) {
			$raw_url = sprintf( 'https://raw.githubusercontent.com/%s/main/wc-smart-checkout-builder.php', $this->repository );
			$raw_res = wp_remote_get( $raw_url, array( 'timeout' => 15, 'sslverify' => false ) );
			if ( ! is_wp_error( $raw_res ) && 200 === wp_remote_retrieve_response_code( $raw_res ) ) {
				$raw_body = wp_remote_retrieve_body( $raw_res );
				if ( preg_match( '/^[ \t\/*#@]*Version:\s*([0-9.]+)/mi', $raw_body, $matches ) ) {
					$remote_ver   = trim( $matches[1] );
					$tag_name     = 'v' . $remote_ver;
					$zip_url      = sprintf( 'https://github.com/%s/releases/download/%s/wc-smart-checkout-builder.zip', $this->repository, $tag_name );
					$raw_zip_url  = sprintf( 'https://github.com/%s/raw/%s/wc-smart-checkout-builder.zip', $this->repository, $tag_name );
					$fallback_zip = sprintf( 'https://github.com/%s/archive/refs/tags/%s.zip', $this->repository, $tag_name );

					$release_data = (object) array(
						'tag_name'    => $tag_name,
						'zipball_url' => $fallback_zip,
						'assets'      => array(
							(object) array(
								'name'                 => 'wc-smart-checkout-builder.zip',
								'browser_download_url' => $zip_url,
							),
							(object) array(
								'name'                 => 'wc-smart-checkout-builder.zip',
								'browser_download_url' => $raw_zip_url,
							),
						),
						'body'        => sprintf( 'Update to version %s.', $remote_ver ),
					);
				}
			}
		}

		// 3. Fallback to GitHub Tags API
		if ( ! $release_data ) {
			$tags_url      = sprintf( 'https://api.github.com/repos/%s/tags', $this->repository );
			$tags_response = wp_remote_get( $tags_url, $request_args );

			if ( ! is_wp_error( $tags_response ) && 200 === wp_remote_retrieve_response_code( $tags_response ) ) {
				$tags_body = wp_remote_retrieve_body( $tags_response );
				$tags_data = json_decode( $tags_body );

				if ( ! empty( $tags_data ) && is_array( $tags_data ) && ! empty( $tags_data[0]->name ) ) {
					$latest_tag    = $tags_data[0];
					$tag_ver       = ltrim( $latest_tag->name, 'v' );
					$tag_asset_url = sprintf( 'https://github.com/%s/releases/download/%s/wc-smart-checkout-builder.zip', $this->repository, $latest_tag->name );
					$release_data  = (object) array(
						'tag_name'    => $latest_tag->name,
						'zipball_url' => $latest_tag->zipball_url,
						'assets'      => array(
							(object) array(
								'name'                 => 'wc-smart-checkout-builder.zip',
								'browser_download_url' => $tag_asset_url,
							),
						),
						'body'        => sprintf( 'Update to version %s.', $tag_ver ),
					);
				}
			}
		}

		if ( $release_data ) {
			// Cache for 30 minutes
			set_site_transient( $this->cache_key, $release_data, 30 * MINUTE_IN_SECONDS );
			return $release_data;
		}

		return false;
	}

	/**
	 * Check for updates against GitHub release.
	 *
	 * @param object $transient
	 * @return object
	 */
	public function check_for_plugin_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new \stdClass();
		}

		// Allow manual force checking or AJAX update action
		$force = ( isset( $_GET['force-check'] ) && '1' === $_GET['force-check'] )
			|| ( isset( $_GET['action'] ) && 'wcsc_check_update' === $_GET['action'] )
			|| ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'update-plugin' === $_REQUEST['action'] );
		$release = $this->get_github_release( $force );

		if ( ! $release ) {
			return $transient;
		}

		$new_version = ltrim( $release->tag_name, 'v' );

		// Determine zip download package (strictly wc-smart-checkout-builder.zip)
		$download_package = ! empty( $release->zipball_url ) ? $release->zipball_url : '';
		if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				$asset_name = isset( $asset->name ) ? strtolower( $asset->name ) : '';
				if ( 'wc-smart-checkout-builder.zip' === $asset_name && ! empty( $asset->browser_download_url ) ) {
					$download_package = $asset->browser_download_url;
					break;
				}
			}
			if ( ( empty( $download_package ) || $download_package === $release->zipball_url ) ) {
				foreach ( $release->assets as $asset ) {
					$asset_name = isset( $asset->name ) ? strtolower( $asset->name ) : '';
					if ( false !== strpos( $asset_name, 'wc-smart-checkout-builder' ) && substr( $asset_name, -4 ) === '.zip' && ! empty( $asset->browser_download_url ) ) {
						$download_package = $asset->browser_download_url;
						break;
					}
				}
			}
		}

		$update_data = (object) array(
			'id'            => $this->plugin_basename,
			'slug'          => $this->plugin_slug,
			'plugin'        => $this->plugin_basename,
			'new_version'   => $new_version,
			'url'           => sprintf( 'https://github.com/%s', $this->repository ),
			'package'       => $download_package,
			'icons'         => array(),
			'banners'       => array(),
			'banners_rtl'   => array(),
			'requires'      => '5.8',
			'tested'        => '6.7',
			'requires_php'  => '7.4',
			'compatibility' => new \stdClass(),
		);

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
			$transient->no_update = array();
		}
		if ( ! isset( $transient->checked ) || ! is_array( $transient->checked ) ) {
			$transient->checked = array();
		}

		$transient->checked[ $this->plugin_basename ] = $this->version;

		if ( version_compare( $new_version, $this->version, '>' ) ) {
			$transient->response[ $this->plugin_basename ] = $update_data;
			unset( $transient->no_update[ $this->plugin_basename ] );
		} else {
			$transient->no_update[ $this->plugin_basename ] = $update_data;
			unset( $transient->response[ $this->plugin_basename ] );
		}

		return $transient;
	}

	/**
	 * Display plugin release information popup in WordPress Admin.
	 *
	 * @param false|object|array $result
	 * @param string             $action
	 * @param object             $args
	 * @return false|object
	 */
	public function plugin_popup_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		$release = $this->get_github_release();
		if ( ! $release ) {
			return $result;
		}

		$new_version = ltrim( $release->tag_name, 'v' );
		$changelog   = ! empty( $release->body ) ? nl2br( esc_html( $release->body ) ) : esc_html__( 'Maintenance and improvements.', 'wc-smart-checkout-builder' );

		$download_package = $release->zipball_url;
		if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				$asset_name = isset( $asset->name ) ? strtolower( $asset->name ) : '';
				if ( 'wc-smart-checkout-builder.zip' === $asset_name && ! empty( $asset->browser_download_url ) ) {
					$download_package = $asset->browser_download_url;
					break;
				}
			}
			if ( ( empty( $download_package ) || $download_package === $release->zipball_url ) ) {
				foreach ( $release->assets as $asset ) {
					$asset_name = isset( $asset->name ) ? strtolower( $asset->name ) : '';
					if ( false !== strpos( $asset_name, 'wc-smart-checkout-builder' ) && substr( $asset_name, -4 ) === '.zip' && ! empty( $asset->browser_download_url ) ) {
						$download_package = $asset->browser_download_url;
						break;
					}
				}
			}
		}

		return (object) array(
			'name'          => 'WooCommerce Product Variation & Checkout Elementor Widget',
			'slug'          => $this->plugin_slug,
			'version'       => $new_version,
			'author'        => '<a href="https://github.com/developer-zahir">Md Zahirul Islam</a>',
			'homepage'      => sprintf( 'https://github.com/%s', $this->repository ),
			'requires'      => '5.8',
			'tested'        => '6.7',
			'requires_php'  => '7.4',
			'download_link' => $download_package,
			'sections'      => array(
				'description' => esc_html__( 'A lightweight Elementor widget for selecting WooCommerce products and checking out.', 'wc-smart-checkout-builder' ),
				'changelog'   => $changelog,
			),
		);
	}

	/**
	 * Rename GitHub extracted folder (e.g. developer-zahir-wc-smart-checkout-builder-xxx)
	 * back to the standard plugin slug directory (wc-smart-checkout-builder).
	 *
	 * @param bool  $true
	 * @param array $hook_extra
	 * @param array $result
	 * @return array
	 */
	public function rename_github_folder_after_update( $true, $hook_extra, $result ) {
		global $wp_filesystem;

		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $result;
		}

		$proper_destination  = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
		$current_destination = isset( $result['destination'] ) ? untrailingslashit( $result['destination'] ) : '';

		if ( $current_destination && $current_destination !== $proper_destination ) {
			if ( $wp_filesystem->exists( $proper_destination ) ) {
				$wp_filesystem->delete( $proper_destination, true );
			}
			$moved = $wp_filesystem->move( $current_destination, $proper_destination, true );
			if ( ! $moved && function_exists( 'copy_dir' ) ) {
				copy_dir( $current_destination, $proper_destination );
				$wp_filesystem->delete( $current_destination, true );
			}
			$result['destination']      = $proper_destination;
			$result['destination_name'] = $this->plugin_slug;
		}

		// Clear transients immediately so WordPress sees the updated version
		delete_site_transient( $this->cache_key );
		delete_site_transient( 'update_plugins' );

		return $result;
	}

	/**
	 * Automatically clear update cache when any plugin update completes.
	 *
	 * @param \WP_Upgrader $upgrader_object
	 * @param array        $options
	 */
	public function on_update_completed( $upgrader_object, $options ) {
		if ( isset( $options['action'] ) && 'update' === $options['action'] && isset( $options['type'] ) && 'plugin' === $options['type'] ) {
			delete_site_transient( $this->cache_key );
			delete_site_transient( 'update_plugins' );
		}
	}

	/**
	 * Add "Check Update" link to plugin row meta.
	 *
	 * @param array  $links
	 * @param string $file
	 * @return array
	 */
	public function add_check_update_link( $links, $file ) {
		if ( $file === $this->plugin_basename ) {
			$check_url = wp_nonce_url( admin_url( 'plugins.php?action=wcsc_check_update' ), 'wcsc_check_update_nonce' );
			$links[] = '<a href="' . esc_url( $check_url ) . '" style="color: #2271b1; font-weight: 600;">' . esc_html__( 'Check Update', 'wc-smart-checkout-builder' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Handle the manual update check action.
	 */
	public function handle_manual_update_check() {
		if ( isset( $_GET['action'] ) && 'wcsc_check_update' === $_GET['action'] ) {
			if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wcsc_check_update_nonce' ) ) {
				// Clear update transients to force a fresh check
				delete_site_transient( $this->cache_key );
				delete_site_transient( 'update_plugins' );
				
				// Force a new fetch
				$release = $this->get_github_release( true );
				
				$status = 'checked';
				if ( $release ) {
					$new_version = ltrim( $release->tag_name, 'v' );
					if ( version_compare( $new_version, $this->version, '>' ) ) {
						$status = 'available';

						// Force inject into WordPress update_plugins transient
						$current_transient = get_site_transient( 'update_plugins' );
						if ( ! is_object( $current_transient ) ) {
							$current_transient = new \stdClass();
						}
						$current_transient = $this->check_for_plugin_update( $current_transient );
						set_site_transient( 'update_plugins', $current_transient );
					} else {
						$status = 'latest';
					}
				}

				// Redirect back to plugins page with message
				wp_safe_redirect( admin_url( 'plugins.php?wcsc_update_status=' . $status ) );
				exit;
			}
		}
	}
	
	/**
	 * Display admin notices based on update check.
	 */
	public function display_update_notices() {
		if ( isset( $_GET['wcsc_update_status'] ) ) {
			$status = sanitize_text_field( $_GET['wcsc_update_status'] );
			
			if ( 'available' === $status ) {
				?>
				<div class="notice notice-info is-dismissible">
					<p><strong>WC Smart Checkout Builder:</strong> A new update is available! Please check the plugin list below to update.</p>
				</div>
				<?php
			} elseif ( 'latest' === $status ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><strong>WC Smart Checkout Builder:</strong> You are using the latest version.</p>
				</div>
				<?php
			} else {
				?>
				<div class="notice notice-success is-dismissible">
					<p><strong>WC Smart Checkout Builder:</strong> Successfully checked for updates.</p>
				</div>
				<?php
			}
		}
	}
}
