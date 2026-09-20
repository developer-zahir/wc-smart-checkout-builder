<?php
namespace WCSC;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class License_Manager
 *
 * Handles client-side license verification, 6-hour transient caching,
 * instant webhook cache purging on revocation, and admin activation controls.
 */
class License_Manager {

	/**
	 * Remote license server API endpoint (Custom API without wp-json).
	 */
	const SERVER_URL = 'https://app.developerzahir.com/tp-server/v1/check';

	/**
	 * Product identifier sent to the licensing server.
	 */
	const PLUGIN_NAME = 'WC Smart Checkout Builder';

	/**
	 * Option name for storing the license key.
	 */
	const OPTION_KEY = 'wcsc_license_key';

	/**
	 * Option name for storing the last known verification status.
	 */
	const OPTION_STATUS = 'wcsc_license_status';

	/**
	 * Cache TTL: 6 hours in seconds.
	 */
	const CACHE_TTL = 21600; // 6 * HOUR_IN_SECONDS

	/**
	 * Initialize license manager hooks.
	 */
	public static function init() {
		// Routine license status check on init.
		add_action( 'init', array( __CLASS__, 'check_license_status' ) );

		// Custom Webhook receiver for instant revocation from server (no wp-json required).
		add_action( 'init', array( __CLASS__, 'handle_custom_webhook' ) );

		// AJAX endpoints for dashboard license activation / deactivation.
		add_action( 'wp_ajax_wcsc_activate_license', array( __CLASS__, 'ajax_activate_license' ) );
		add_action( 'wp_ajax_wcsc_deactivate_license', array( __CLASS__, 'ajax_deactivate_license' ) );

		// Admin notice if license is inactive or blocked.
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notice' ) );
	}

	/**
	 * Get current stored license key.
	 *
	 * @return string
	 */
	public static function get_license_key() {
		return trim( (string) get_option( self::OPTION_KEY, '' ) );
	}

	/**
	 * Get current license status ('active', 'inactive', 'invalid', 'unregistered').
	 *
	 * @return string
	 */
	public static function get_license_status() {
		$key = self::get_license_key();
		if ( empty( $key ) ) {
			return 'unregistered';
		}

		$trans_name = 'tp_license_status_' . md5( $key );
		$status     = get_transient( $trans_name );

		if ( false !== $status && ! empty( $status ) ) {
			return sanitize_text_field( $status );
		}

		return (string) get_option( self::OPTION_STATUS, 'inactive' );
	}

	/**
	 * Check if license is currently active.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return 'active' === self::get_license_status();
	}

	/**
	 * Routine verification check.
	 * Runs on 6-hour transient cache; falls back to last known status if server is unreachable.
	 */
	public static function check_license_status() {
		$license_key = self::get_license_key();
		if ( empty( $license_key ) ) {
			return;
		}

		$trans_name = 'tp_license_status_' . md5( $license_key );
		$status     = get_transient( $trans_name );

		if ( false === $status ) {
			$domain = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : home_url();

			$response = wp_remote_post( self::SERVER_URL, array(
				'timeout'   => 10,
				'sslverify' => false,
				'body'      => array(
					'key'    => $license_key,
					'url'    => $domain,
					'plugin' => self::PLUGIN_NAME,
					'action' => 'check',
				),
			) );

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				// Server unreachable or down: fallback gracefully to last known status so the site never breaks.
				$status = get_option( self::OPTION_STATUS, 'active' );
			} else {
				$body   = json_decode( wp_remote_retrieve_body( $response ) );
				$status = isset( $body->status ) ? sanitize_text_field( $body->status ) : 'inactive';
				update_option( self::OPTION_STATUS, $status );
			}

			// Store verification status in 6-hour transient cache.
			set_transient( $trans_name, $status, self::CACHE_TTL );

			// If license status is inactive or blocked, purge all frontend caches immediately.
			if ( 'active' !== $status ) {
				self::clear_caches();
			}
		}
	}

	/**
	 * Activate license manually or on dashboard save.
	 * Sends 'action' => 'activate' to server, which triggers owner email notification.
	 *
	 * @param string $license_key
	 * @return array
	 */
	public static function activate_license( $license_key ) {
		$license_key = sanitize_text_field( trim( $license_key ) );
		if ( empty( $license_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'অনুগ্রহ করে একটি বৈধ লাইসেন্স কি প্রবেশ করান।', 'wc-smart-checkout-builder' ),
				'status'  => 'unregistered',
			);
		}

		$domain = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : home_url();

		$response = wp_remote_post( self::SERVER_URL, array(
			'timeout'    => 8,
			'sslverify'  => false,
			'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
			'headers'    => array(
				'Accept' => 'application/json',
			),
			'body'       => array(
				'key'    => $license_key,
				'url'    => $domain,
				'plugin' => self::PLUGIN_NAME,
				'action' => 'activate',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'লাইসেন্স সার্ভারে সংযোগ ব্যর্থ: %s', 'wc-smart-checkout-builder' ), $response->get_error_message() ),
				'status'  => 'inactive',
			);
		}

		$raw_body = wp_remote_retrieve_body( $response );
		$body     = json_decode( $raw_body );
		$code     = wp_remote_retrieve_response_code( $response );

		if ( 200 !== (int) $code && ( empty( $body ) || ! isset( $body->message ) ) ) {
			$preview = ! empty( $raw_body ) ? ' (' . wp_strip_all_tags( substr( $raw_body, 0, 100 ) ) . ')' : '';
			return array(
				'success' => false,
				'message' => sprintf( __( 'সার্ভার রেসপন্স ত্রুটি (HTTP %d)%s', 'wc-smart-checkout-builder' ), $code, $preview ),
				'status'  => 'inactive',
			);
		}

		$status = isset( $body->status ) ? sanitize_text_field( $body->status ) : 'invalid';

		update_option( self::OPTION_KEY, $license_key );
		update_option( self::OPTION_STATUS, $status );

		$trans_name = 'tp_license_status_' . md5( $license_key );
		set_transient( $trans_name, $status, self::CACHE_TTL );

		if ( 'active' === $status ) {
			self::clear_caches();
			return array(
				'success' => true,
				'message' => __( 'অভিনন্দন! আপনার লাইসেন্স সফলভাবে অ্যাক্টিভ হয়েছে।', 'wc-smart-checkout-builder' ),
				'status'  => 'active',
			);
		}

		self::clear_caches();
		$server_msg = ( isset( $body->message ) && ! empty( $body->message ) )
			? sanitize_text_field( $body->message )
			: __( 'লাইসেন্স কি-টি অবৈধ অথবা সার্ভার থেকে ব্লক করা হয়েছে। অনুগ্রহ করে অ্যাডমিনের সাথে যোগাযোগ করুন।', 'wc-smart-checkout-builder' );

		return array(
			'success' => false,
			'message' => $server_msg,
			'status'  => $status,
		);
	}

	/**
	 * Deactivate license locally.
	 *
	 * @return array
	 */
	public static function deactivate_license() {
		$key = self::get_license_key();
		if ( ! empty( $key ) ) {
			delete_transient( 'tp_license_status_' . md5( $key ) );
		}

		delete_option( self::OPTION_KEY );
		update_option( self::OPTION_STATUS, 'inactive' );
		self::clear_caches();

		return array(
			'success' => true,
			'message' => __( 'লাইসেন্স সফলভাবে ডি-অ্যাক্টিভ করা হয়েছে।', 'wc-smart-checkout-builder' ),
			'status'  => 'inactive',
		);
	}

	/**
	 * AJAX handler for license activation.
	 */
	public static function ajax_activate_license() {
		if ( ob_get_length() ) {
			ob_clean();
		}

		check_ajax_referer( 'wcsc_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'অনুমতি নেই।', 'wc-smart-checkout-builder' ) ) );
		}

		$key    = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		$result = self::activate_license( $key );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX handler for license deactivation.
	 */
	public static function ajax_deactivate_license() {
		if ( ob_get_length() ) {
			ob_clean();
		}

		check_ajax_referer( 'wcsc_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'অনুমতি নেই।', 'wc-smart-checkout-builder' ) ) );
		}

		$result = self::deactivate_license();
		wp_send_json_success( $result );
	}

	/**
	 * Custom Webhook endpoint receiver without wp-json.
	 * Listens to: /tp-client/v1/update-license or ?tp_action=update_license
	 * Allows the licensing server to instantly signal this client site to purge cache and block access.
	 */
	public static function handle_custom_webhook() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$is_path_match  = false !== strpos( $request_uri, '/tp-client/v1/update-license' );
		$is_query_match = ( isset( $_GET['tp_action'] ) && 'update_license' === $_GET['tp_action'] );

		if ( ! $is_path_match && ! $is_query_match ) {
			return;
		}

		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
			return;
		}

		// Read payload (supports JSON or standard POST form data)
		$raw_input = file_get_contents( 'php://input' );
		$data      = json_decode( $raw_input, true );
		if ( ! is_array( $data ) ) {
			$data = $_POST;
		}

		$received_key = isset( $data['key'] ) ? sanitize_text_field( $data['key'] ) : '';
		$local_key    = self::get_license_key();

		if ( ! empty( $received_key ) && $received_key === $local_key ) {
			delete_transient( 'tp_license_status_' . md5( $local_key ) );
			update_option( self::OPTION_STATUS, 'inactive' );
			self::clear_caches();

			wp_send_json( array(
				'status'  => 'success',
				'message' => 'Cache Cleared and License Inactivated',
			), 200 );
		}

		wp_send_json( array(
			'status'  => 'ignored',
			'message' => 'Key mismatch or empty',
		), 200 );
	}

	/**
	 * Purge all major WordPress caching plugins safely without fatal errors.
	 */
	public static function clear_caches() {
		try {
			// LiteSpeed Cache
			if ( function_exists( 'litespeed_purge_all' ) ) {
				@litespeed_purge_all();
			}
			// W3 Total Cache
			if ( function_exists( 'w3tc_flush_all' ) ) {
				@w3tc_flush_all();
			}
			// WP Rocket
			if ( function_exists( 'rocket_clean_domain' ) ) {
				@rocket_clean_domain();
			}
			// SiteGround Optimizer
			if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
				@sg_cachepress_purge_cache();
			}
			// Autoptimize
			if ( class_exists( '\autoptimizeCache' ) && method_exists( '\autoptimizeCache', 'clearall' ) ) {
				@\autoptimizeCache::clearall();
			}
			// WP Super Cache
			if ( function_exists( 'wp_cache_clean_cache' ) ) {
				global $file_prefix;
				@wp_cache_clean_cache( $file_prefix, true );
			}
		} catch ( \Exception $e ) {
			// Fail silently if caching plugins are missing or throw errors.
		}
	}

	/**
	 * Render admin notification banner if license is missing or blocked.
	 */
	public static function render_admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = self::get_license_status();
		if ( 'active' === $status ) {
			return;
		}

		$current_screen = get_current_screen();
		// Do not duplicate if already on settings page
		$is_settings_page = ( isset( $_GET['page'] ) && 'wcsc-settings' === $_GET['page'] );

		$settings_url = admin_url( 'edit.php?post_type=wcsc_page&page=wcsc-settings#tab-license' );

		if ( 'unregistered' === $status ) {
			if ( ! $is_settings_page ) {
				?>
				<div class="notice notice-warning is-dismissible wcsc-notice">
					<p>
						<strong><?php esc_html_e( 'WC Smart Checkout Builder:', 'wc-smart-checkout-builder' ); ?></strong>
						<?php esc_html_e( 'আপনার প্লাগিনটি এখনও অ্যাক্টিভ করা হয়নি। প্লাগিনের সম্পূর্ণ সুবিধা পেতে অনুগ্রহ করে আপনার লাইসেন্স কি প্রবেশ করান।', 'wc-smart-checkout-builder' ); ?>
						<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary" style="margin-left: 10px;"><?php esc_html_e( 'লাইসেন্স অ্যাক্টিভ করুন', 'wc-smart-checkout-builder' ); ?></a>
					</p>
				</div>
				<?php
			}
		} else {
			?>
			<div class="notice notice-error wcsc-notice">
				<p>
					<strong><?php esc_html_e( 'License Alert:', 'wc-smart-checkout-builder' ); ?></strong>
					<?php esc_html_e( 'WC Smart Checkout Builder প্লাগিনের লাইসেন্সটি ইনঅ্যাক্টিভ অথবা সার্ভার থেকে ব্লক করা হয়েছে। অনুগ্রহ করে সাপোর্টে যোগাযোগ করুন।', 'wc-smart-checkout-builder' ); ?>
					<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-secondary" style="margin-left: 10px;"><?php esc_html_e( 'লাইসেন্স সেটিংস', 'wc-smart-checkout-builder' ); ?></a>
				</p>
			</div>
			<?php
		}
	}
}
