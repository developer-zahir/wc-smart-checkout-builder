<?php
/**
 * Plugin Name:       WooFlexiBundle Builder for Elementor
 * Plugin URI:        https://github.com/developer-zahir/wc-smart-checkout-builder
 * Description:       Dynamic product bundle grid with interactive variation selection, tiered discounts, and seamless WooCommerce AJAX checkout integration.
 * Version:           1.0.0
 * Author:            Developer Zahir
 * Author URI:        https://developerzahir.com
 * Text Domain:       wooflexibundle-builder
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

define( 'WOOFLEXIBUNDLE_VERSION', '1.0.0' );
define( 'WOOFLEXIBUNDLE_FILE', __FILE__ );
define( 'WOOFLEXIBUNDLE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOFLEXIBUNDLE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main WooFlexiBundle_Builder Class
 */
final class WooFlexiBundle_Builder {

	/**
	 * Instance
	 *
	 * @var WooFlexiBundle_Builder
	 */
	private static $instance = null;

	/**
	 * Get Instance
	 *
	 * @return WooFlexiBundle_Builder
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize plugin after plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize plugin components and hooks.
	 */
	public function init() {
		// Check for WooCommerce and Elementor.
		if ( ! $this->check_dependencies() ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_dependencies' ) );
			return;
		}

		// Register Elementor Widget.
		add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widget' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_elementor_category' ) );

		// Register and Enqueue Scripts & Styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

		// AJAX Cart Synchronization & Tiered Discount Handler.
		add_action( 'wp_ajax_wooflexi_sync_cart', array( $this, 'ajax_sync_cart' ) );
		add_action( 'wp_ajax_nopriv_wooflexi_sync_cart', array( $this, 'ajax_sync_cart' ) );

		// WooCommerce Fee Calculation for Tiered Discount.
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_tiered_bundle_discount' ), 20, 1 );
	}

	/**
	 * Check if required plugins (WooCommerce and Elementor) are active.
	 *
	 * @return bool
	 */
	public function check_dependencies() {
		$wc_active = class_exists( 'WooCommerce' ) || in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ), true );
		$elementor_active = did_action( 'elementor/loaded' ) || in_array( 'elementor/elementor.php', apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ), true );

		return $wc_active && $elementor_active;
	}

	/**
	 * Admin notice if WooCommerce or Elementor is missing.
	 */
	public function admin_notice_missing_dependencies() {
		$missing = array();
		if ( ! class_exists( 'WooCommerce' ) ) {
			$missing[] = '<strong>WooCommerce</strong>';
		}
		if ( ! did_action( 'elementor/loaded' ) ) {
			$missing[] = '<strong>Elementor</strong>';
		}

		if ( ! empty( $missing ) ) {
			$message = sprintf(
				/* translators: 1: Plugin Name, 2: Missing plugins */
				esc_html__( '%1$s requires %2$s to be installed and active.', 'wooflexibundle-builder' ),
				'<strong>' . esc_html__( 'WooFlexiBundle Builder', 'wooflexibundle-builder' ) . '</strong>',
				implode( ' ' . esc_html__( 'and', 'wooflexibundle-builder' ) . ' ', $missing )
			);
			printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
		}
	}

	/**
	 * Register custom Elementor Category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_elementor_category( $elements_manager ) {
		$elements_manager->add_category(
			'wooflexibundle-category',
			array(
				'title' => esc_html__( 'FlexiBundle Builder', 'wooflexibundle-builder' ),
				'icon'  => 'eicon-cart-medium',
			)
		);
	}

	/**
	 * Register Elementor Widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_elementor_widget( $widgets_manager ) {
		require_once WOOFLEXIBUNDLE_PATH . 'includes/elementor-widget.php';
		$widgets_manager->register( new \WooFlexiBundle\Elementor_Widget() );
	}

	/**
	 * Register frontend CSS and JavaScript assets.
	 */
	public function register_assets() {
		wp_register_style(
			'wooflexibundle-style',
			WOOFLEXIBUNDLE_URL . 'assets/css/style.css',
			array(),
			WOOFLEXIBUNDLE_VERSION
		);

		wp_register_script(
			'wooflexibundle-frontend',
			WOOFLEXIBUNDLE_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			WOOFLEXIBUNDLE_VERSION,
			true
		);

		// Localize script with AJAX URL, nonce, and WooCommerce currency settings.
		wp_localize_script(
			'wooflexibundle-frontend',
			'wooflexi_vars',
			array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'wooflexi_bundle_nonce' ),
				'currency_symbol'     => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
				'currency_pos'        => function_exists( 'get_option' ) ? get_option( 'woocommerce_currency_pos', 'left' ) : 'left',
				'thousand_separator'  => function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : ',',
				'decimal_separator'   => function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '.',
				'decimals'            => function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2,
				'i18n'                => array(
					'select_variation' => esc_html__( 'Please select all variation options before selecting this item.', 'wooflexibundle-builder' ),
					'min_items_alert'  => esc_html__( 'Please select at least %d item(s) to checkout.', 'wooflexibundle-builder' ),
					'max_items_alert'  => esc_html__( 'You can select a maximum of %d item(s).', 'wooflexibundle-builder' ),
					'updating_cart'    => esc_html__( 'Updating checkout...', 'wooflexibundle-builder' ),
					'discount_applied' => esc_html__( '%d%% Discount Applied!', 'wooflexibundle-builder' ),
				),
			)
		);
	}

	/**
	 * AJAX handler to synchronize customer bundle selection with the WooCommerce cart.
	 */
	public function ajax_sync_cart() {
		check_ajax_referer( 'wooflexi_bundle_nonce', 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => esc_html__( 'WooCommerce cart unavailable.', 'wooflexibundle-builder' ) ) );
		}

		$items_raw = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$tiers_raw = isset( $_POST['tiers'] ) ? wp_unslash( $_POST['tiers'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Decode JSON if passed as string.
		$items = is_string( $items_raw ) ? json_decode( $items_raw, true ) : $items_raw;
		$tiers = is_string( $tiers_raw ) ? json_decode( $tiers_raw, true ) : $tiers_raw;

		if ( ! is_array( $items ) ) {
			$items = array();
		}

		$cart = WC()->cart;

		// Clear previous bundle session data.
		if ( WC()->session ) {
			WC()->session->__unset( 'wooflexi_bundle_discount' );
			WC()->session->__unset( 'wooflexi_bundle_tiers' );
		}

		// Empty cart to ensure a direct bundle checkout.
		$cart->empty_cart();

		$total_quantity = 0;

		foreach ( $items as $item ) {
			$product_id   = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
			$variation_id = isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0;
			$quantity     = isset( $item['quantity'] ) ? max( 1, absint( $item['quantity'] ) ) : 1;
			$attributes   = isset( $item['attributes'] ) && is_array( $item['attributes'] ) ? array_map( 'sanitize_text_field', $item['attributes'] ) : array();

			if ( ! $product_id ) {
				continue;
			}

			$product = wc_get_product( $variation_id ? $variation_id : $product_id );
			if ( ! $product || ! $product->is_purchasable() ) {
				continue;
			}

			// Add to WooCommerce cart.
			$cart_item_data = array( 'wooflexi_bundle_item' => true );
			$cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes, $cart_item_data );
			$total_quantity += $quantity;
		}

		// Store discount tiers in WooCommerce session so calculate_fees can apply it.
		if ( ! empty( $tiers ) && is_array( $tiers ) && WC()->session ) {
			WC()->session->set( 'wooflexi_bundle_tiers', $tiers );
		}

		// Recalculate totals with fee hook.
		$cart->calculate_totals();

		wp_send_json_success( array(
			'item_count' => $cart->get_cart_contents_count(),
			'subtotal'   => $cart->get_cart_subtotal(),
			'total'      => $cart->get_total(),
			'raw_total'  => $cart->get_total( 'edit' ),
			'cart_hash'  => $cart->get_cart_hash(),
		) );
	}

	/**
	 * Automatically apply the highest matching tiered discount based on total bundle items.
	 *
	 * @param \WC_Cart $cart
	 */
	public function apply_tiered_bundle_discount( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( ! WC()->session ) {
			return;
		}

		$tiers = WC()->session->get( 'wooflexi_bundle_tiers' );
		if ( empty( $tiers ) || ! is_array( $tiers ) ) {
			return;
		}

		$total_count = $cart->get_cart_contents_count();
		if ( $total_count <= 0 ) {
			return;
		}

		// Find the highest applicable discount tier.
		$applicable_tier = null;

		// Sort tiers descending by min_qty.
		usort( $tiers, function( $a, $b ) {
			$qty_a = isset( $a['min_qty'] ) ? (int) $a['min_qty'] : 0;
			$qty_b = isset( $b['min_qty'] ) ? (int) $b['min_qty'] : 0;
			return $qty_b - $qty_a;
		} );

		foreach ( $tiers as $tier ) {
			$min_qty = isset( $tier['min_qty'] ) ? (int) $tier['min_qty'] : 0;
			if ( $total_count >= $min_qty ) {
				$applicable_tier = $tier;
				break;
			}
		}

		if ( ! $applicable_tier ) {
			return;
		}

		$discount_type  = isset( $applicable_tier['discount_type'] ) ? $applicable_tier['discount_type'] : 'percentage';
		$discount_val   = isset( $applicable_tier['discount_value'] ) ? floatval( $applicable_tier['discount_value'] ) : 0;
		$tier_label     = ! empty( $applicable_tier['tier_label'] ) ? sanitize_text_field( $applicable_tier['tier_label'] ) : '';

		if ( $discount_val <= 0 ) {
			return;
		}

		$cart_subtotal = $cart->get_subtotal();
		$discount_amount = 0;

		if ( 'percentage' === $discount_type ) {
			$discount_amount = ( $cart_subtotal * $discount_val ) / 100;
			if ( empty( $tier_label ) ) {
				$tier_label = sprintf( __( 'Bundle Discount (%s%%)', 'wooflexibundle-builder' ), $discount_val );
			}
		} else {
			$discount_amount = $discount_val;
			if ( empty( $tier_label ) ) {
				$tier_label = __( 'Bundle Discount', 'wooflexibundle-builder' );
			}
		}

		// Cap discount so cart total doesn't become negative.
		if ( $discount_amount > $cart_subtotal ) {
			$discount_amount = $cart_subtotal;
		}

		if ( $discount_amount > 0 ) {
			$cart->add_fee( $tier_label, -$discount_amount, true );
		}
	}
}

// Instantiate plugin singleton.
WooFlexiBundle_Builder::instance();

