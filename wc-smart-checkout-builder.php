<?php
/**
 * Plugin Name:       WC Smart Checkout Builder
 * Plugin URI:        https://github.com/developer-zahir/wc-smart-checkout-builder
 * Description:       A lightweight Elementor widget for selecting WooCommerce simple or variable products, choosing variation attributes with buttons or images, and completing native WooCommerce checkout in-place.
 * Version:           1.1
 * Author:            Developer Zahir
 * Author URI:        https://developerzahir.com
 * Text Domain:       wc-smart-checkout-builder
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 * Elementor tested up to: 3.25
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WCSC_VERSION', '1.1' );
define( 'WCSC_FILE', __FILE__ );
define( 'WCSC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCSC_URL', plugin_dir_url( __FILE__ ) );
define( 'WCSC_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check dependencies and initialize the plugin.
 */
function wcsc_init() {
	// Check if WooCommerce is active.
	$woocommerce_active = class_exists( 'WooCommerce' ) || in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ), true );

	// Check if Elementor is active.
	$elementor_active = did_action( 'elementor/loaded' ) || in_array( 'elementor/elementor.php', apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ), true );

	if ( ! $woocommerce_active || ! $elementor_active ) {
		add_action( 'admin_notices', 'wcsc_missing_dependencies_notice' );
		return;
	}

	// Autoload / include plugin core classes.
	require_once WCSC_PATH . 'includes/class-product-handler.php';
	require_once WCSC_PATH . 'includes/class-variation-handler.php';
	require_once WCSC_PATH . 'includes/class-checkout-handler.php';
	require_once WCSC_PATH . 'includes/class-updater.php';
	require_once WCSC_PATH . 'includes/class-plugin.php';

	// Bootstrap plugin.
	\WCSC\Plugin::instance();

	// Initialize GitHub automatic updater for WordPress dashboard updates.
	if ( is_admin() ) {
		new \WCSC\Updater( WCSC_BASENAME, WCSC_VERSION );
	}
}
add_action( 'plugins_loaded', 'wcsc_init', 20 );

/**
 * Admin notice for missing required plugins.
 */
function wcsc_missing_dependencies_notice() {
	$missing = array();

	if ( ! class_exists( 'WooCommerce' ) ) {
		$missing[] = '<strong>WooCommerce</strong>';
	}
	if ( ! did_action( 'elementor/loaded' ) ) {
		$missing[] = '<strong>Elementor</strong>';
	}

	if ( ! empty( $missing ) ) {
		$message = sprintf(
			/* translators: %s: Comma-separated list of missing plugins */
			__( 'The %1$s plugin requires %2$s to be installed and active.', 'wc-smart-checkout-builder' ),
			'<strong>WooCommerce Product Variation & Checkout Elementor Widget</strong>',
			implode( ' and ', $missing )
		);
		printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
	}
}

/**
 * Load plugin textdomain.
 */
function wcsc_load_textdomain() {
	load_plugin_textdomain( 'wc-smart-checkout-builder', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'wcsc_load_textdomain' );
