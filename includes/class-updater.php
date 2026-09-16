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
		add_filter( 'plugins_api', array( $this, 'plugin_popup_information' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'rename_github_folder_after_update' ), 10, 3 );
	}

	/**
	 * Fetch latest release from GitHub API.
	 *
	 * @param bool $force_check
	 * @return object|false
	 */
	protected function get_github_release( $force_check = false ) {
		if ( ! $force_check ) {
			$cached = get_site_transient( $this->cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$url = sprintf( 'https://api.github.com/repos/%s/releases/latest', $this->repository );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github.v3+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( ! empty( $data ) && is_object( $data ) && ! empty( $data->tag_name ) ) {
			set_site_transient( $this->cache_key, $data, 6 * HOUR_IN_SECONDS );
			return $data;
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
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Allow manual force checking
		$force = isset( $_GET['force-check'] ) && '1' === $_GET['force-check'];
		$release = $this->get_github_release( $force );

		if ( ! $release ) {
			return $transient;
		}

		$new_version = ltrim( $release->tag_name, 'v' );

		// Determine zip download package
		$download_package = $release->zipball_url;
		if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( ! empty( $asset->browser_download_url ) && substr( $asset->browser_download_url, -4 ) === '.zip' ) {
					$download_package = $asset->browser_download_url;
					break;
				}
			}
		}

		$update_data = (object) array(
			'id'            => 'wc-smart-checkout-builder',
			'slug'          => $this->plugin_slug,
			'plugin'        => $this->plugin_basename,
			'new_version'   => $new_version,
			'url'           => sprintf( 'https://github.com/%s', $this->repository ),
			'package'       => $download_package,
			'icons'         => array(),
			'banners'       => array(),
			'banners_rtl'   => array(),
			'tested'        => '6.7',
			'requires_php'  => '7.4',
			'compatibility' => new \stdClass(),
		);

		if ( version_compare( $new_version, $this->version, '>' ) ) {
			$transient->response[ $this->plugin_basename ] = $update_data;
		} else {
			$transient->no_update[ $this->plugin_basename ] = $update_data;
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
				if ( ! empty( $asset->browser_download_url ) && substr( $asset->browser_download_url, -4 ) === '.zip' ) {
					$download_package = $asset->browser_download_url;
					break;
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
				'description' => esc_html__( 'A lightweight Elementor widget for selecting WooCommerce simple or variable products, choosing variation attributes with buttons or images, and completing native WooCommerce checkout in-place.', 'wc-smart-checkout-builder' ),
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

		$proper_destination = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
		$current_destination = untrailingslashit( $result['destination'] );

		if ( $current_destination !== $proper_destination ) {
			$wp_filesystem->move( $current_destination, $proper_destination );
			$result['destination'] = $proper_destination;
		}

		return $result;
	}
}
