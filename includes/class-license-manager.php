<?php
namespace WCSC;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class License_Manager
 *
 * Handles client-side license verification, 24-hour transient caching,
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
	 * Cache TTL: 24 hours in seconds (server-down resilient grace period).
	 */
	const CACHE_TTL = 86400; // 24 * HOUR_IN_SECONDS

	/**
	 * Developer email for unlicensed-usage alerts.
	 */
	const DEVELOPER_EMAIL = 'your-email@example.com';

	/**
	 * Initialize license manager hooks.
	 */
	public static function init() {
		// Routine license status check on init.
		add_action( 'init', array( __CLASS__, 'check_license_status' ) );

		// Custom Webhook receiver for instant revocation from server (no wp-json required).
		add_action( 'init', array( __CLASS__, 'handle_custom_webhook' ) );

		// AJAX endpoints for dashboard license activation / deactivation / connection test.
		add_action( 'wp_ajax_wcsc_activate_license', array( __CLASS__, 'ajax_activate_license' ) );
		add_action( 'wp_ajax_wcsc_deactivate_license', array( __CLASS__, 'ajax_deactivate_license' ) );
		add_action( 'wp_ajax_wcsc_test_license_connection', array( __CLASS__, 'ajax_test_license_connection' ) );

		// Admin notice if license is inactive or blocked.
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notice' ) );
	}

	/**
	 * Subdomain & Domain Normalizer.
	 *
	 * Strips protocols, credentials, port, path, query, fragment, and 'www.'.
	 *
	 * @param string $domain
	 * @return string
	 */
	public static function normalize_domain( $domain ) {
		if ( empty( $domain ) || ! is_string( $domain ) ) {
			return '';
		}
		$domain = strtolower( trim( $domain ) );
		$domain = preg_replace( '~^https?://~i', '', $domain );
		$domain = preg_replace( '~^[^@]+@~', '', $domain );
		$domain = preg_replace( '~[/?#].*$~', '', $domain );
		$domain = preg_replace( '~:\d+$~', '', $domain );
		$domain = preg_replace( '~^www\.~i', '', $domain );
		return trim( $domain, "/ \t\n\r\0\x0B." );
	}

	/**
	 * Get normalized site domain for licensing.
	 *
	 * @return string
	 */
	public static function get_site_domain() {
		$raw_url = home_url();
		$host    = wp_parse_url( $raw_url, PHP_URL_HOST );
		if ( empty( $host ) && isset( $_SERVER['HTTP_HOST'] ) ) {
			$host = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		}
		if ( empty( $host ) && isset( $_SERVER['SERVER_NAME'] ) ) {
			$host = sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) );
		}
		return self::normalize_domain( ! empty( $host ) ? $host : $raw_url );
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
	 * Send an HTTP POST request to the licensing server with IPv4 resolution
	 * enforcement and no-redirect policy to prevent hanging on slow IPv6 DNS
	 * or redirecting to static frontend pages.
	 *
	 * @param array $payload
	 * @param int   $timeout
	 * @return array|\WP_Error
	 */
	public static function send_request( $payload, $timeout = 12 ) {
		$curl_callback = function( $handle ) {
			if ( defined( 'CURLOPT_IPRESOLVE' ) && defined( 'CURL_IPRESOLVE_V4' ) ) {
				@curl_setopt( $handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
			}
		};

		add_action( 'http_api_curl', $curl_callback, 10, 1 );

		$response = wp_remote_post( self::SERVER_URL, array(
			'timeout'     => $timeout,
			'redirection' => 0,
			'sslverify'   => false,
			'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
			'headers'     => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json; charset=utf-8',
			),
			'body'        => wp_json_encode( $payload ),
		) );

		remove_action( 'http_api_curl', $curl_callback, 10 );

		return $response;
	}

	/**
	 * Routine verification check.
	 * Runs on 24-hour transient cache; falls back to last known status if server is unreachable.
	 */
	public static function check_license_status() {
		// Skip routine verification on AJAX requests
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$license_key = self::get_license_key();
		if ( empty( $license_key ) ) {
			return;
		}

		$trans_name = 'tp_license_status_' . md5( $license_key );
		$status     = get_transient( $trans_name );

		if ( false === $status ) {
			$domain  = self::get_site_domain();
			$payload = array(
				'key'    => $license_key,
				'url'    => $domain,
				'plugin' => self::PLUGIN_NAME,
				'action' => 'check',
			);

			$response = self::send_request( $payload, 10 );

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				// Server unreachable or down: fallback gracefully to last known status so the site never breaks.
				$status = get_option( self::OPTION_STATUS, 'active' );
			} else {
				$body   = json_decode( wp_remote_retrieve_body( $response ) );
				$status = ( isset( $body->status ) && is_string( $body->status ) ) ? sanitize_text_field( $body->status ) : 'inactive';
				update_option( self::OPTION_STATUS, $status );
			}

			// Store verification status in 24-hour transient cache.
			set_transient( $trans_name, $status, self::CACHE_TTL );

			// If license status is inactive or blocked, purge all frontend caches immediately.
			if ( 'active' !== $status ) {
				self::clear_caches();
				// Send silent background email alert to developer about unlicensed usage.
				self::send_unlicensed_usage_alert( $domain, $status );
			}
		}
	}

	/**
	 * Send silent background email alert to developer about unlicensed usage.
	 *
	 * @param string $client_domain
	 * @param string $status
	 */
	public static function send_unlicensed_usage_alert( $client_domain, $status ) {
		if ( 'unregistered' === $status ) {
			return;
		}

		// Prevent email spam: Send at most once per 24 hours per client domain.
		$alert_key = 'wcsc_last_alert_sent_' . md5( $client_domain );
		$last_sent = (int) get_option( $alert_key, 0 );
		$now       = time();

		if ( ( $now - $last_sent ) < DAY_IN_SECONDS ) {
			return;
		}

		$admin_email  = get_option( 'admin_email', 'unknown' );
		$trigger_time = current_time( 'mysql' );
		$subject      = sprintf(
			/* translators: 1: Plugin name, 2: Client domain */
			__( '[Unlicensed Usage Alert] %1$s on %2$s', 'wc-smart-checkout-builder' ),
			self::PLUGIN_NAME,
			$client_domain
		);

		$body = sprintf(
			/* translators: 1: Plugin name, 2: Client domain, 3: Admin email, 4: License status, 5: Timestamp */
			__(
				"Attention,\n\n" .
				"Unlicensed or blocked usage was detected for %1$s.\n\n" .
				"Client Domain : %2$s\n" .
				"Admin Email   : %3$s\n" .
				"License Status: %4$s\n" .
				"Detected Time : %5$s\n\n" .
				"This is an automated security notification from the plugin core.",
				'wc-smart-checkout-builder'
			),
			self::PLUGIN_NAME,
			$client_domain,
			$admin_email,
			$status,
			$trigger_time
		);

		try {
			if ( function_exists( 'wp_mail' ) ) {
				@wp_mail( self::DEVELOPER_EMAIL, $subject, $body );
			}
		} catch ( \Throwable $e ) {
			// Silently ignore mail failures to prevent crashing client site.
		}

		// Record that an alert was sent to avoid spamming.
		update_option( $alert_key, $now, false );
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

		$domain  = self::get_site_domain();
		$payload = array(
			'key'    => $license_key,
			'url'    => $domain,
			'plugin' => self::PLUGIN_NAME,
			'action' => 'check',
		);

		$response = self::send_request( $payload, 15 );

		if ( is_wp_error( $response ) ) {
			$err_msg = sprintf(
				__( 'লাইসেন্স সার্ভারে সংযোগ ব্যর্থ: %s। (হোস্টিং cURL বা আউটবাউন্ড কানেকশন ব্লক থাকতে পারে)', 'wc-smart-checkout-builder' ),
				$response->get_error_message()
			);
			update_option( 'wcsc_last_license_error', $err_msg );
			update_option( 'wcsc_last_license_error_time', current_time( 'mysql' ) );

			return array(
				'success' => false,
				'message' => $err_msg,
				'status'  => 'inactive',
				'debug'   => array(
					'http_code'     => 0,
					'error_code'    => $response->get_error_code(),
					'endpoint'      => self::SERVER_URL,
					'client_domain' => $domain,
				),
			);
		}

		$raw_body = wp_remote_retrieve_body( $response );
		$body     = json_decode( $raw_body );
		$code     = wp_remote_retrieve_response_code( $response );

		if ( empty( $body ) || ! is_object( $body ) ) {
			$preview = ! empty( $raw_body ) ? ' (' . wp_strip_all_tags( substr( $raw_body, 0, 160 ) ) . ')' : '';
			$err_msg = sprintf(
				__( 'সার্ভার থেকে সঠিক JSON রেসপন্স পাওয়া যায়নি (HTTP %d)%s। ক্লাউডফ্লেয়ার বা ডব্লিউএএফ ফায়ারওয়াল রিকোয়েস্ট আটকে থাকতে পারে।', 'wc-smart-checkout-builder' ),
				$code,
				$preview
			);
			update_option( 'wcsc_last_license_error', $err_msg );
			update_option( 'wcsc_last_license_error_time', current_time( 'mysql' ) );

			return array(
				'success' => false,
				'message' => $err_msg,
				'status'  => 'inactive',
				'debug'   => array(
					'http_code'      => $code,
					'client_domain'  => $domain,
					'raw_response'   => wp_strip_all_tags( substr( $raw_body, 0, 300 ) ),
				),
			);
		}

		$status = isset( $body->status ) ? sanitize_text_field( $body->status ) : 'invalid';

		update_option( self::OPTION_KEY, $license_key );
		update_option( self::OPTION_STATUS, $status );

		$trans_name = 'tp_license_status_' . md5( $license_key );
		set_transient( $trans_name, $status, self::CACHE_TTL );

		if ( 'active' === $status ) {
			delete_option( 'wcsc_last_license_error' );
			delete_option( 'wcsc_last_license_error_time' );
			self::clear_caches();

			return array(
				'success' => true,
				'message' => __( 'অভিনন্দন! আপনার লাইসেন্স সফলভাবে অ্যাক্টিভ হয়েছে।', 'wc-smart-checkout-builder' ),
				'status'  => 'active',
				'debug'   => array(
					'http_code'      => $code,
					'client_domain'  => $domain,
					'server_status'  => $status,
					'server_message' => isset( $body->message ) ? sanitize_text_field( $body->message ) : 'License is verified and active.',
				),
			);
		}

		self::clear_caches();
		$server_msg = ( isset( $body->message ) && ! empty( $body->message ) )
			? sanitize_text_field( $body->message )
			: __( 'লাইসেন্স কি-টি অবৈধ অথবা সার্ভার থেকে ব্লক করা হয়েছে। অনুগ্রহ করে অ্যাডমিনের সাথে যোগাযোগ করুন।', 'wc-smart-checkout-builder' );

		update_option( 'wcsc_last_license_error', $server_msg );
		update_option( 'wcsc_last_license_error_time', current_time( 'mysql' ) );

		return array(
			'success' => false,
			'message' => $server_msg,
			'status'  => $status,
			'debug'   => array(
				'http_code'      => $code,
				'client_domain'  => $domain,
				'server_url'     => self::SERVER_URL,
				'server_status'  => $status,
				'server_message' => $server_msg,
				'raw_response'   => wp_strip_all_tags( substr( $raw_body, 0, 300 ) ),
			),
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
		delete_option( 'wcsc_last_license_error' );
		delete_option( 'wcsc_last_license_error_time' );
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
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		if ( ! check_ajax_referer( 'wcsc_license_nonce', 'nonce', false ) ) {
			wp_send_json_error( array(
				'message' => __( 'নিরাপত্তা টোকেন (Security Nonce) মেয়াদোত্তীর্ণ বা অবৈধ হয়েছে। অনুগ্রহ করে পেজটি রিফ্রেশ (Refresh) করে আবার চেষ্টা করুন।', 'wc-smart-checkout-builder' ),
				'status'  => 'invalid_nonce',
			) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'আপনার এই অ্যাকশনটি সম্পন্ন করার পর্যাপ্ত অনুমতি (Permission) নেই।', 'wc-smart-checkout-builder' ),
				'status'  => 'forbidden',
			) );
		}

		$key    = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		$result = self::activate_license( $key );

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX handler for license deactivation.
	 */
	public static function ajax_deactivate_license() {
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		if ( ! check_ajax_referer( 'wcsc_license_nonce', 'nonce', false ) ) {
			wp_send_json_error( array(
				'message' => __( 'নিরাপত্তা টোকেন (Security Nonce) মেয়াদোত্তীর্ণ হয়েছে।', 'wc-smart-checkout-builder' ),
				'status'  => 'invalid_nonce',
			) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'অনুমতি নেই।', 'wc-smart-checkout-builder' ),
				'status'  => 'forbidden',
			) );
		}

		$result = self::deactivate_license();
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for live license server connection test.
	 */
	public static function ajax_test_license_connection() {
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		if ( ! check_ajax_referer( 'wcsc_license_nonce', 'nonce', false ) ) {
			wp_send_json_error( array(
				'message' => __( 'নিরাপত্তা টোকেন (Security Nonce) মেয়াদোত্তীর্ণ হয়েছে। পেজটি রিফ্রেশ করুন।', 'wc-smart-checkout-builder' ),
			) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'অনুমতি নেই।', 'wc-smart-checkout-builder' ),
			) );
		}

		$domain     = self::get_site_domain();
		$start_time = microtime( true );

		$passed_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		$ping_key   = ! empty( $passed_key ) ? $passed_key : 'TP-PING-TEST';
		$action     = ! empty( $passed_key ) ? 'check' : 'ping';

		$response = self::send_request( array(
			'key'    => $ping_key,
			'url'    => $domain,
			'plugin' => self::PLUGIN_NAME,
			'action' => $action,
		), 15 );

		$latency = round( ( microtime( true ) - $start_time ) * 1000 );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message'    => sprintf( __( 'সার্ভারে সংযোগ ব্যর্থ: %s', 'wc-smart-checkout-builder' ), $response->get_error_message() ),
				'latency_ms' => $latency,
				'domain'     => $domain,
				'endpoint'   => self::SERVER_URL,
			) );
		}

		$code     = (int) wp_remote_retrieve_response_code( $response );
		$raw_body = wp_remote_retrieve_body( $response );
		$body     = json_decode( $raw_body );

		$status     = ( isset( $body->status ) ) ? sanitize_text_field( $body->status ) : '';
		$server_msg = ( isset( $body->message ) ) ? sanitize_text_field( $body->message ) : '';

		if ( 'active' === $status ) {
			if ( ! empty( $passed_key ) && 'TP-PING-TEST' !== $passed_key ) {
				update_option( self::OPTION_KEY, $passed_key );
				update_option( self::OPTION_STATUS, 'active' );
				delete_option( 'wcsc_last_license_error' );
				delete_option( 'wcsc_last_license_error_time' );
				$trans_name = 'tp_license_status_' . md5( $passed_key );
				set_transient( $trans_name, 'active', self::CACHE_TTL );
				self::clear_caches();
			}
			$diag_msg = sprintf( __( 'সার্ভার সংযোগ সফল (রেসপন্স সময়: %d ms)। লাইসেন্স কি সক্রিয় ও ডাটাবেসে সেভ হয়েছে!', 'wc-smart-checkout-builder' ), $latency );
		} elseif ( 'online' === $status ) {
			$diag_msg = sprintf( __( 'সার্ভার সংযোগ সফল ও সচল (রেসপন্স সময়: %d ms)। লাইসেন্স সার্ভার লাইভ আছে।', 'wc-smart-checkout-builder' ), $latency );
		} elseif ( ! empty( $server_msg ) ) {
			$diag_msg = sprintf( __( 'সার্ভার সংযোগ সফল (রেসপন্স সময়: %d ms)। সার্ভার ফিডব্যাক: %s', 'wc-smart-checkout-builder' ), $latency, $server_msg );
		} else {
			$diag_msg = sprintf( __( 'সার্ভার সংযোগ সফল (HTTP %d, রেসপন্স সময়: %d ms)', 'wc-smart-checkout-builder' ), $code, $latency );
		}

		wp_send_json_success( array(
			'message'    => $diag_msg,
			'status'     => $status,
			'is_active'  => ( 'active' === $status && ! empty( $passed_key ) && 'TP-PING-TEST' !== $passed_key ),
			'http_code'  => $code,
			'latency_ms' => $latency,
			'domain'     => $domain,
			'endpoint'   => self::SERVER_URL,
			'raw_sample' => wp_strip_all_tags( substr( $raw_body, 0, 120 ) ),
		) );
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
