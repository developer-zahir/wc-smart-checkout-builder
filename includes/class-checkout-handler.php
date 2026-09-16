<?php
namespace WCSC;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Checkout_Handler
 *
 * Handles WooCommerce native checkout rendering, cart item synchronization,
 * order button customization, dynamic pricing tags, animations, and text customization.
 */
class Checkout_Handler {

	/**
	 * Active widget settings during render.
	 *
	 * @var array
	 */
	public static $active_widget_settings = array();

	/**
	 * Active product during render.
	 *
	 * @var \WC_Product|null
	 */
	public static $active_product = null;

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_order_button_text', array( __CLASS__, 'filter_order_button_text' ), 20 );
		add_filter( 'woocommerce_order_button_html', array( __CLASS__, 'filter_order_button_html' ), 20 );
		add_filter( 'gettext', array( __CLASS__, 'filter_checkout_gettext' ), 20, 3 );
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'filter_cart_item_name' ), 10, 3 );

		// AJAX endpoints for cart synchronization.
		add_action( 'wp_ajax_wcsc_sync_cart', array( __CLASS__, 'ajax_sync_cart' ) );
		add_action( 'wp_ajax_nopriv_wcsc_sync_cart', array( __CLASS__, 'ajax_sync_cart' ) );

		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_bd_phone_number' ), 10, 2 );
	}

	/**
	 * Check if currently in Elementor editor, preview, or saving context.
	 *
	 * @return bool
	 */
	public static function is_editor_environment() {
		if ( class_exists( '\Elementor\Plugin' ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
				return true;
			}
		}

		// When saving in Elementor via AJAX (elementor_ajax action: save_builder)
		if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'elementor_ajax' === $_REQUEST['action'] ) {
			return true;
		}

		// In WordPress admin area
		if ( is_admin() && ! wp_doing_ajax() ) {
			return true;
		}

		return false;
	}

	/**
	 * Filter the WooCommerce checkout place order button text.
	 *
	 * @param string $button_text
	 * @return string
	 */
	public static function filter_order_button_text( $button_text ) {
		if ( ! empty( self::$active_widget_settings['order_button_text'] ) ) {
			return sanitize_text_field( self::$active_widget_settings['order_button_text'] );
		}
		return $button_text;
	}

	/**
	 * Filter full HTML for the place order button (icon, dynamic price tag, animation classes).
	 *
	 * @param string $button_html
	 * @return string
	 */
	public static function filter_order_button_html( $button_html ) {
		if ( empty( self::$active_widget_settings ) ) {
			return $button_html;
		}

		$settings   = self::$active_widget_settings;
		$btn_text   = ! empty( $settings['order_button_text'] ) ? sanitize_text_field( $settings['order_button_text'] ) : __( 'Order Now', 'wc-smart-checkout-builder' );
		$animation  = ! empty( $settings['order_button_animation'] ) ? sanitize_html_class( $settings['order_button_animation'] ) : 'border_run';
		$anim_class = 'wcsc-anim-' . str_replace( '_', '-', $animation );

		// Price in button
		$price_html = '';
		$show_price = isset( $settings['show_button_price'] ) && 'yes' === $settings['show_button_price'];
		if ( $show_price ) {
			$current_total = '';
			if ( function_exists( 'WC' ) && WC()->cart ) {
				$current_total = WC()->cart->get_total();
			}
			if ( empty( $current_total ) && self::$active_product ) {
				$current_total = wc_price( self::$active_product->get_price() );
			}
			$price_html = '<span class="wcsc-btn-price-wrap"> &mdash; <span class="wcsc-btn-price">' . wp_strip_all_tags( $current_total ) . '</span></span>';
		}

		// Icon
		$icon_html = '';
		if ( ! empty( $settings['order_button_icon'] ) && class_exists( '\Elementor\Icons_Manager' ) ) {
			ob_start();
			\Elementor\Icons_Manager::render_icon( $settings['order_button_icon'], array( 'aria-hidden' => 'true' ) );
			$rendered_icon = ob_get_clean();
			if ( $rendered_icon ) {
				$icon_html = '<span class="wcsc-btn-icon">' . $rendered_icon . '</span>';
			}
		}

		$icon_align = ! empty( $settings['order_button_icon_align'] ) ? $settings['order_button_icon_align'] : 'left';

		$content_inner = '';
		if ( 'left' === $icon_align && $icon_html ) {
			$content_inner .= $icon_html . ' ';
		}
		$content_inner .= '<span class="wcsc-btn-text">' . esc_html( $btn_text ) . '</span>';
		if ( $price_html ) {
			$content_inner .= $price_html;
		}
		if ( 'right' === $icon_align && $icon_html ) {
			$content_inner .= ' ' . $icon_html;
		}

		// Build button with runner beams
		$custom_button = sprintf(
			'<button type="submit" class="button alt wp-element-button wcsc-order-now-btn %1$s" name="woocommerce_checkout_place_order" id="place_order" value="%2$s" data-value="%2$s">' .
			'<span class="wcsc-btn-beam wcsc-beam-top"></span>' .
			'<span class="wcsc-btn-beam wcsc-beam-bottom"></span>' .
			'<span class="wcsc-btn-content">%3$s</span>' .
			'</button>',
			esc_attr( $anim_class ),
			esc_attr( $btn_text ),
			$content_inner
		);

		return $custom_button;
	}

	/**
	 * Filter gettext strings for checkout headings and table labels.
	 *
	 * @param string $translated_text
	 * @param string $text
	 * @param string $domain
	 * @return string
	 */
	public static function filter_checkout_gettext( $translated_text, $text, $domain ) {
		if ( empty( self::$active_widget_settings ) ) {
			return $translated_text;
		}

		$settings = self::$active_widget_settings;

		if ( ! empty( $settings['order_review_heading_text'] ) && in_array( strtolower( $text ), array( 'your order', 'your orders' ), true ) ) {
			return esc_html( $settings['order_review_heading_text'] );
		}

		if ( ! empty( $settings['billing_heading_text'] ) && in_array( strtolower( $text ), array( 'billing details', 'billing & shipping' ), true ) ) {
			return esc_html( $settings['billing_heading_text'] );
		}

		if ( ! empty( $settings['product_label_text'] ) && 'product' === strtolower( $text ) ) {
			return esc_html( $settings['product_label_text'] );
		}

		if ( ! empty( $settings['subtotal_label_text'] ) && 'subtotal' === strtolower( $text ) ) {
			return esc_html( $settings['subtotal_label_text'] );
		}

		if ( ! empty( $settings['shipping_label_text'] ) && 'shipping' === strtolower( $text ) ) {
			return esc_html( $settings['shipping_label_text'] );
		}

		if ( ! empty( $settings['total_label_text'] ) && 'total' === strtolower( $text ) ) {
			return esc_html( $settings['total_label_text'] );
		}

		return $translated_text;
	}

	/**
	 * Filter cart item name to inject thumbnail if enabled.
	 *
	 * @param string $item_name
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 * @return string
	 */
	public static function filter_cart_item_name( $item_name, $cart_item, $cart_item_key ) {
		if ( empty( self::$active_widget_settings ) ) {
			return $item_name;
		}

		if ( isset( self::$active_widget_settings['show_cart_item_image'] ) && 'yes' === self::$active_widget_settings['show_cart_item_image'] ) {
			$product = $cart_item['data'];
			if ( $product ) {
				$thumbnail = $product->get_image( array( 48, 48 ), array( 'class' => 'wcsc-cart-item-image' ) );
				if ( $thumbnail ) {
					$item_name = '<div class="wcsc-cart-item-with-img">' . $thumbnail . '<span class="wcsc-cart-item-name-text">' . $item_name . '</span></div>';
				}
			}
		}

		return $item_name;
	}

	/**
	 * Render the checkout section for the widget.
	 *
	 * @param \WC_Product $product
	 * @param array       $settings
	 */
	public static function render_checkout_section( $product, $settings ) {
		self::$active_widget_settings = $settings;
		self::$active_product         = $product;

		if ( self::is_editor_environment() ) {
			// In Elementor live editor or save builder: render safe preview template.
			$template_path = WCSC_PATH . 'templates/checkout/editor-checkout-preview.php';
			if ( file_exists( $template_path ) ) {
				include $template_path;
			}
			return;
		}

		// Frontend mode: Ensure WooCommerce cart and session exist.
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		// Ensure checkout scripts and styles are enqueued.
		if ( class_exists( 'WC_Frontend_Scripts' ) ) {
			\WC_Frontend_Scripts::load_scripts();
		}
		wp_enqueue_script( 'wc-checkout' );

		// Check if we need to auto-populate the cart with the selected product if cart is empty.
		if ( $product && $product->is_purchasable() ) {
			self::ensure_product_in_cart( $product );
		}

		$wrapper_classes = array( 'wcsc-native-checkout-wrapper', 'woocommerce' );
		if ( ! empty( $settings['checkout_layout'] ) && '1_column' === $settings['checkout_layout'] ) {
			$wrapper_classes[] = 'wcsc-layout-1-col';
		} else {
			$wrapper_classes[] = 'wcsc-layout-2-col';
		}

		if ( isset( $settings['show_checkout_billing'] ) && 'yes' !== $settings['show_checkout_billing'] ) {
			$wrapper_classes[] = 'wcsc-hide-billing';
		}
		if ( isset( $settings['show_checkout_order_review'] ) && 'yes' !== $settings['show_checkout_order_review'] ) {
			$wrapper_classes[] = 'wcsc-hide-order-review';
		}
		if ( isset( $settings['show_checkout_shipping'] ) && 'yes' !== $settings['show_checkout_shipping'] ) {
			$wrapper_classes[] = 'wcsc-hide-shipping';
		}
		if ( isset( $settings['show_checkout_payment'] ) && 'yes' !== $settings['show_checkout_payment'] ) {
			$wrapper_classes[] = 'wcsc-hide-payment';
		}

		if ( ! empty( $settings['bd_phone_validation'] ) && 'yes' === $settings['bd_phone_validation'] ) {
			add_action( 'woocommerce_checkout_billing', array( __CLASS__, 'add_bd_phone_validation_flag' ), 99 );
		}

		$shipping_pos = ! empty( $settings['shipping_method_position'] ) ? $settings['shipping_method_position'] : 'inside_form';
		$button_pos   = ! empty( $settings['order_button_position'] ) ? $settings['order_button_position'] : 'below_form';
		$error_disp   = ! empty( $settings['error_message_display'] ) ? $settings['error_message_display'] : 'inline';
		$show_img     = isset( $settings['show_cart_item_image'] ) && 'yes' === $settings['show_cart_item_image'] ? 'yes' : 'no';

		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" 
			data-shipping-pos="<?php echo esc_attr( $shipping_pos ); ?>"
			data-button-pos="<?php echo esc_attr( $button_pos ); ?>"
			data-error-disp="<?php echo esc_attr( $error_disp ); ?>"
			data-show-img="<?php echo esc_attr( $show_img ); ?>">
			<!-- Loading overlay with modern blur and centered spinner -->
			<div class="wcsc-loading-overlay">
				<div class="wcsc-spinner"></div>
			</div>

			<?php
			if ( function_exists( 'wc_print_notices' ) ) {
				wc_print_notices();
			}

			// Render native WooCommerce checkout template.
			if ( function_exists( 'WC' ) && WC()->checkout() ) {
				$checkout = WC()->checkout();
				if ( function_exists( 'wc_get_template' ) ) {
					wc_get_template( 'checkout/form-checkout.php', array( 'checkout' => $checkout ) );
				} else {
					echo do_shortcode( '[woocommerce_checkout]' );
				}
			} elseif ( shortcode_exists( 'woocommerce_checkout' ) ) {
				echo do_shortcode( '[woocommerce_checkout]' );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Ensure the selected product is present in cart on initial render.
	 *
	 * @param \WC_Product $product
	 */
	protected static function ensure_product_in_cart( $product ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$product_id = $product->get_id();
		$cart       = WC()->cart;
		
		// Clear cart to ensure only the selected product is checked out.
		$cart->empty_cart();

		// If variable product, find default variation or first available variation.
		if ( $product->is_type( 'variable' ) ) {
			$available_variations = $product->get_available_variations();
			if ( ! empty( $available_variations ) ) {
				$first_variation = $available_variations[0];
				$cart->add_to_cart(
					$product_id,
					1,
					$first_variation['variation_id'],
					$first_variation['attributes']
				);
			}
		} else {
			$cart->add_to_cart( $product_id, 1 );
		}
	}

	/**
	 * AJAX handler to synchronize cart when customer selects a variation or quantity.
	 */
	public static function ajax_sync_cart() {
		check_ajax_referer( 'wcsc_checkout_nonce', 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce cart unavailable.', 'wc-smart-checkout-builder' ) ) );
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$quantity     = isset( $_POST['quantity'] ) ? max( 1, absint( $_POST['quantity'] ) ) : 1;
		$attributes   = isset( $_POST['attributes'] ) && is_array( $_POST['attributes'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['attributes'] ) ) : array();

		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_purchasable() ) {
			wp_send_json_error( array( 'message' => __( 'Selected product is not purchasable.', 'wc-smart-checkout-builder' ) ) );
		}

		$cart = WC()->cart;

		// Clear cart to prevent extra products from mixing.
		$cart->empty_cart();

		// Add item to cart.
		$cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes );

		$cart->calculate_totals();

		wp_send_json_success( array(
			'cart_hash'     => $cart->get_cart_hash(),
			'item_count'    => $cart->get_cart_contents_count(),
			'subtotal'      => $cart->get_cart_subtotal(),
			'total'         => $cart->get_total(),
			'raw_total'     => $cart->get_total( 'edit' ),
			'currency_text' => wp_strip_all_tags( $cart->get_total() ),
		) );
	}
	public static function add_bd_phone_validation_flag() {
		echo '<input type="hidden" name="wcsc_bd_phone_validation" value="1" />';
	}

	/**
	 * Validate BD phone number.
	 */
	public static function validate_bd_phone_number( $data, $errors ) {
		if ( isset( $_POST['wcsc_bd_phone_validation'] ) && '1' === $_POST['wcsc_bd_phone_validation'] ) {
			$phone = isset( $data['billing_phone'] ) ? $data['billing_phone'] : '';
			if ( ! empty( $phone ) ) {
				$phone = preg_replace( '/[^0-9]/', '', $phone );
				if ( strlen( $phone ) !== 11 || substr( $phone, 0, 2 ) !== '01' ) {
					$errors->add( 'billing_phone', __( 'সঠিক ১১ ডিজিটের ফোন নাম্বার দিন।', 'wc-smart-checkout-builder' ) );
				}
			}
		}
	}

}
