<?php
namespace WCSC;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Checkout_Handler
 *
 * Renders the WooCommerce checkout using a plugin-owned presentation layer.
 *
 * WooCommerce is still responsible for ALL checkout logic (calculations,
 * customer data, shipping, payment, validation, order creation). Our plugin
 * only controls the LAYOUT / presentation layer by wrapping every major
 * checkout section in its own unique, independently controllable block:
 *
 *   .wcas-checkout-wrapper
 *     ├── .wcas-block.wcas-block-checkout-form  (billing + shipping fields)
 *     ├── .wcas-block.wcas-block-shipping      (shipping method selection)
 *     ├── .wcas-block.wcas-block-order-review  (ordered products / totals)
 *     ├── .wcas-block.wcas-block-payment       (payment gateway methods)
 *     └── .wcas-block.wcas-block-order-button  (place order button)
 *
 * Each block is:
 *   - rendered conditionally (disabled blocks are not output at all — no empty wrappers)
 *   - a direct child of the layout container (independently positionable via CSS)
 *   - styleable through our own wrapper only (theme CSS is isolated)
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
	 * Cached native review-order HTML.
	 *
	 * Captured once per render cycle so that both the Shipping block
	 * (which extracts #shipping_method radios) and the Order Review
	 * block (which keeps the shipping COST row) share the same source.
	 *
	 * @var string|null
	 */
	private static $review_order_html = null;

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_order_button_text', array( __CLASS__, 'filter_order_button_text' ), 20 );
		add_filter( 'woocommerce_order_button_html', array( __CLASS__, 'filter_order_button_html' ), 20 );
		add_filter( 'gettext', array( __CLASS__, 'filter_checkout_gettext' ), 20, 3 );
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'filter_cart_item_name' ), 10, 3 );

		// Keep WooCommerce's AJAX "update order review" response from re-injecting the
		// place order button into #payment. The button lives in its own plugin-owned
		// block (req 32), so it must never be duplicated by AJAX updates.
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'filter_update_order_review_fragments' ), 20 );

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
			if ( ( isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) || ( isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) ) {
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

		// Price replacement for {total_price}
		$current_total = '';
		if ( function_exists( 'WC' ) && WC()->cart ) {
			$current_total = WC()->cart->get_total();
		}
		if ( empty( $current_total ) && self::$active_product ) {
			$current_total = wc_price( self::$active_product->get_price() );
		}
		$price_html = '<span class="wcsc-btn-price-wrap"><span class="wcsc-btn-price">' . wp_strip_all_tags( $current_total ) . '</span></span>';

		// Replace {total_price} in the button text
		if ( strpos( $btn_text, '{total_price}' ) !== false ) {
			$btn_text = str_replace( '{total_price}', $price_html, $btn_text );
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

		// The button text may already contain the price HTML now
		$content_inner .= '<span class="wcsc-btn-text">' . $btn_text . '</span>';

		if ( 'right' === $icon_align && $icon_html ) {
			$content_inner .= ' ' . $icon_html;
		}

		// Build button. Kept inside our plugin-owned Order Button block (not in #payment).
		$custom_button = sprintf(
			'<button type="submit" class="button alt wp-element-button wcsc-order-now-btn %1$s" name="woocommerce_checkout_place_order" id="place_order" value="%2$s" data-value="%2$s">' .
			'<span class="wcsc-btn-beam wcsc-beam-top"></span>' .
			'<span class="wcsc-btn-beam wcsc-beam-bottom"></span>' .
			'<span class="wcsc-btn-content">%3$s</span>' .
			'</button>',
			esc_attr( $anim_class ),
			esc_attr( wp_strip_all_tags( $btn_text ) ), // keep value clean
			$content_inner
		);

		return $custom_button;
	}

	/**
	 * Filter gettext strings for checkout headings and table labels.
	 *
	 * Only the 6 checkout translation controls are honoured (req 40). Field labels
	 * such as First Name, Last Name, Phone, etc. intentionally come from
	 * WooCommerce / site configuration and are NOT translatable here.
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

		$mapping = array(
			'billing & shipping' => 'billing_heading_text',
			'billing details'    => 'billing_heading_text',
			'your order'         => 'order_review_heading_text',
			'your orders'        => 'order_review_heading_text',
			'product'            => 'product_label_text',
			'subtotal'           => 'subtotal_label_text',
			'shipping'           => 'shipping_label_text',
			'total'              => 'total_label_text',
		);

		$lower_text = strtolower( $text );

		if ( isset( $mapping[ $lower_text ] ) && ! empty( $settings[ $mapping[ $lower_text ] ] ) ) {
			return esc_html( $settings[ $mapping[ $lower_text ] ] );
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
	 * Outputs a plugin-owned, independently controllable block structure. Each
	 * major checkout section is wrapped in its own `.wcas-block` so that layout,
	 * visibility, and styling are controlled by the plugin — never by WooCommerce
	 * or theme layout containers.
	 *
	 * @param \WC_Product $product
	 * @param array       $settings
	 */
	public static function render_checkout_section( $product, $settings ) {
		self::$active_widget_settings = $settings;
		self::$active_product         = $product;
		self::$review_order_html      = null;

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

		$checkout = WC()->checkout();
		if ( ! $checkout ) {
			return;
		}

		// ----------------------------------------------------------------------
		// Layout class — Elementor setting is the single source of truth (req 44).
		// Only one layout class is ever emitted.
		// ----------------------------------------------------------------------
		$layout = ! empty( $settings['checkout_layout'] ) ? $settings['checkout_layout'] : '2_columns';
		if ( '1_column' === $layout ) {
			$layout_class = 'wcas-layout-one-column';
		} else {
			$layout_class = 'wcas-layout-two-column';
		}

		// ----------------------------------------------------------------------
		// Per-block visibility (conditional rendering — req 35/36).
		// A block is only rendered when enabled AND meaningful. When a block is
		// disabled its wrapper is omitted entirely (no empty wrappers / gaps).
		// The Checkout Form block is always rendered (billing toggle removed — req 41).
		// ----------------------------------------------------------------------
		$blocks_enabled = array(
			'checkout_form' => true,
			'shipping'      => self::is_enabled( $settings, 'show_checkout_shipping' ) && WC()->cart->needs_shipping(),
			'order_review'  => self::is_enabled( $settings, 'show_checkout_order_review' ),
			'payment'       => self::is_enabled( $settings, 'show_checkout_payment' ),
			'order_button'  => self::is_enabled( $settings, 'show_checkout_order_button' ),
		);

		// Default DOM order (desktop). Visual order per device is controlled through
		// Elementor's responsive "Block Order" controls (CSS `order`), so the DOM
		// order here is a sensible fallback only.
		$block_order = array( 'checkout_form', 'shipping', 'order_review', 'payment', 'order_button' );

		?>
		<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
			<div class="wcas-checkout-wrapper <?php echo esc_attr( $layout_class ); ?>"
				<?php echo self::render_data_attrs( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<div class="wcsc-loading-overlay">
					<div class="wcsc-spinner"></div>
				</div>

				<?php
				if ( function_exists( 'wc_print_notices' ) ) {
					wc_print_notices();
				}

				// Render each enabled block. Native WooCommerce functionality is used
				// for the content of every block; the wrapper/layout is plugin-owned.
				foreach ( $block_order as $block_name ) {
					if ( empty( $blocks_enabled[ $block_name ] ) ) {
						continue;
					}

					switch ( $block_name ) {
						case 'checkout_form':
							self::render_checkout_form_block();
							break;
						case 'shipping':
							self::render_shipping_block();
							break;
						case 'order_review':
							self::render_order_review_block();
							break;
						case 'payment':
							self::render_payment_block();
							break;
						case 'order_button':
							self::render_order_button_block();
							break;
					}
				}
				?>
			</div>
		</form>
		<?php
	}

	/**
	 * Determine whether a toggle control is enabled (default: enabled).
	 *
	 * @param array  $settings
	 * @param string $key
	 * @return bool
	 */
	private static function is_enabled( $settings, $key ) {
		return ! isset( $settings[ $key ] ) || 'yes' === $settings[ $key ];
	}

	/**
	 * Build data attributes passed to the frontend for text customisation.
	 *
	 * @param array $settings
	 * @return string
	 */
	private static function render_data_attrs( $settings ) {
		$attrs = array(
			'data-txt-order'    => ! empty( $settings['order_review_heading_text'] ) ? $settings['order_review_heading_text'] : '',
			'data-txt-billing'  => ! empty( $settings['billing_heading_text'] ) ? $settings['billing_heading_text'] : '',
			'data-txt-product'  => ! empty( $settings['product_label_text'] ) ? $settings['product_label_text'] : '',
			'data-txt-subtotal' => ! empty( $settings['subtotal_label_text'] ) ? $settings['subtotal_label_text'] : '',
			'data-txt-shipping' => ! empty( $settings['shipping_label_text'] ) ? $settings['shipping_label_text'] : '',
			'data-txt-total'    => ! empty( $settings['total_label_text'] ) ? $settings['total_label_text'] : '',
		);

		$html = '';
		foreach ( $attrs as $key => $value ) {
			if ( '' !== $value ) {
				$html .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}
		return $html;
	}

	/**
	 * Block 1 — Checkout Form (native billing + shipping fields).
	 */
	private static function render_checkout_form_block() {
		?>
		<div class="wcas-block wcas-block-checkout-form">
			<?php
			// Native WooCommerce customer details (billing + shipping fields).
			// do_action callbacks render the native form-billing.php / form-shipping.php
			// templates so all field names, validation and processing keep working.
			?>
			<div class="col2-set" id="customer_details">
				<div class="col-1">
					<?php do_action( 'woocommerce_checkout_billing' ); ?>
				</div>
				<div class="col-2">
					<?php do_action( 'woocommerce_checkout_shipping' ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Capture the native WooCommerce review-order HTML once per render cycle.
	 *
	 * Both the Shipping block and the Order Review block need this output:
	 * - Shipping block extracts #shipping_method radios from it
	 * - Order Review block strips #shipping_method (keeping the shipping COST row)
	 *
	 * @return string
	 */
	private static function capture_review_order_html() {
		if ( self::$review_order_html !== null ) {
			return self::$review_order_html;
		}

		self::$review_order_html = '';

		$checkout = WC()->checkout();
		if ( ! $checkout || ! function_exists( 'wc_get_template' ) ) {
			return '';
		}

		ob_start();
		wc_get_template( 'checkout/review-order.php', array( 'checkout' => $checkout ) );
		self::$review_order_html = (string) ob_get_clean();

		return self::$review_order_html;
	}

	/**
	 * Block 2 — Shipping (shipping method selection).
	 *
	 * The #shipping_method radios are extracted from the native review-order
	 * template and rendered here as an independent plugin-owned block.
	 * This block contains ONLY the method/location selection UI — never the
	 * shipping cost (that lives in the Order Review block).
	 */
	private static function render_shipping_block() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_shipping() ) {
			return;
		}

		$review_html = self::capture_review_order_html();

		// Extract the selectable shipping methods list from the review order HTML.
		// In WooCommerce checkout context, #shipping_method radios are part of the
		// review-order.php shipping totals row.
		$method_list = '';
		if ( preg_match( '/<ul[^>]*id="shipping_method"[^>]*>.*?<\/ul>/is', $review_html, $match ) ) {
			$method_list = $match[0];
		}

		if ( '' === $method_list ) {
			// No selectable methods to show — do NOT render an empty wrapper (req 35/36).
			return;
		}

		$heading = ! empty( self::$active_widget_settings['shipping_label_text'] )
			? sanitize_text_field( self::$active_widget_settings['shipping_label_text'] )
			: esc_html__( 'Shipping', 'wc-smart-checkout-builder' );
		?>
		<div class="wcas-block wcas-block-shipping">
			<h3 class="wcsc-section-title wcsc-shipping-heading"><?php echo esc_html( $heading ); ?></h3>
			<?php echo $method_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
	}

	/**
	 * Block 3 — Order Review (ordered products, subtotal, SHIPPING CHARGE, total).
	 *
	 * The shipping method radios (#shipping_method) are stripped from this output
	 * so they are NOT duplicated here — they live in the dedicated Shipping block.
	 * The SHIPPING COST row is preserved so WooCommerce's calculated shipping
	 * charge is displayed inside the Order Review.
	 *
	 * The order review table retains the `woocommerce-checkout-review-order`
	 * class so WooCommerce's AJAX "update order review" response continues
	 * to replace it correctly.
	 */
	private static function render_order_review_block() {
		$checkout = WC()->checkout();
		$heading  = ! empty( self::$active_widget_settings['order_review_heading_text'] )
			? sanitize_text_field( self::$active_widget_settings['order_review_heading_text'] )
			: esc_html__( 'Your order', 'wc-smart-checkout-builder' );

		// Use the cached review-order HTML (shared with the Shipping block).
		$review_html = self::capture_review_order_html();

		// Strip the #shipping_method radios from the review order HTML so they
		// are not duplicated here. The SHIPPING COST row remains intact.
		$review_html = self::strip_shipping_method_radios( $review_html );
		?>
		<div class="wcas-block wcas-block-order-review">
			<h3 id="order_review_heading" class="wcsc-section-title"><?php echo esc_html( $heading ); ?></h3>
			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php echo $review_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Block 4 — Payment (payment gateway methods only).
	 *
	 * The place order button is intentionally NOT rendered here — it lives in
	 * the dedicated Order Button block (req 32). Only the available payment
	 * gateways are output, wrapped in the native `#payment` element so
	 * WooCommerce's AJAX updates still target the correct selector.
	 */
	private static function render_payment_block() {
		$checkout = WC()->checkout();
		?>
		<div class="wcas-block wcas-block-payment">
			<?php
			do_action( 'woocommerce_review_order_before_payment' );

			if ( function_exists( 'WC' ) && WC()->cart && WC()->cart->needs_payment() ) {
				$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
				WC()->payment_gateways()->set_current_gateway( $available_gateways );
				?>
				<div id="payment" class="woocommerce-checkout-payment">
					<?php if ( ! empty( $available_gateways ) ) : ?>
						<ul class="wc_payment_methods payment_methods methods">
							<?php foreach ( $available_gateways as $gateway ) : ?>
								<?php wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) ); ?>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<ul class="wc_payment_methods payment_methods methods">
							<li>
								<?php
								$message = WC()->customer->get_billing_country()
									? esc_html__( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' )
									: esc_html__( 'Please fill in your details above to see available payment methods.', 'woocommerce' );
								wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', $message ), 'notice' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
								?>
							</li>
						</ul>
					<?php endif; ?>
				</div>
				<?php
			}

			do_action( 'woocommerce_review_order_after_payment' );
			?>
		</div>
		<?php
	}

	/**
	 * Block 5 — Order Button (place order button + terms + nonce).
	 *
	 * This block is completely independent from the Payment block (req 32). The
	 * button markup is produced through the native `woocommerce_order_button_html`
	 * filter so the plugin's custom button (icon, price, animation) is used, while
	 * the rest of the place-order form row (noscript, terms, nonce) is rendered
	 * here so submission keeps working exactly as WooCommerce expects.
	 */
	private static function render_order_button_block() {
		$settings = self::$active_widget_settings;
		$btn_text = ! empty( $settings['order_button_text'] ) ? sanitize_text_field( $settings['order_button_text'] ) : __( 'Order Now', 'wc-smart-checkout-builder' );
		$btn_value = wp_strip_all_tags( $btn_text );

		$default_button = sprintf(
			'<button type="submit" class="button alt wp-element-button" name="woocommerce_checkout_place_order" id="place_order" value="%s" data-value="%s">%s</button>',
			esc_attr( $btn_value ),
			esc_attr( $btn_value ),
			esc_html__( 'Place order', 'woocommerce' )
		);

		$button_html = apply_filters( 'woocommerce_order_button_html', $default_button );
		?>
		<div class="wcas-block wcas-block-order-button">
			<div class="form-row place-order">
				<noscript>
					<?php esc_html_e( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the Update Totals button before placing your order.', 'woocommerce' ); ?>
					<br/><button type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'woocommerce' ); ?>"><?php esc_html_e( 'Update totals', 'woocommerce' ); ?></button>
				</noscript>

				<?php
				if ( function_exists( 'wc_get_template' ) && function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) && wc_terms_and_conditions_checkbox_enabled() ) {
					wc_get_template( 'checkout/terms.php' );
				}
				?>

				<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

				<?php echo $button_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

				<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Strip the #shipping_method radios from captured review-order HTML so they
	 * are not duplicated inside the Order Review block. The SHIPPING COST row
	 * (the calculated charge from WooCommerce) remains intact.
	 *
	 * @param string $html
	 * @return string
	 */
	private static function strip_shipping_method_radios( $html ) {
		return (string) preg_replace( '/<ul[^>]*id="shipping_method"[^>]*>.*?<\/ul>\s*/is', '', (string) $html );
	}

	/**
	 * Sanitise WooCommerce's AJAX "update order review" fragments so no blocks
	 * are duplicated after checkout recalculation.
	 *
	 * 1. The place order button is stripped from the .woocommerce-checkout-payment
	 *    fragment (it lives in the plugin-owned Order Button block — req 32/39).
	 * 2. The #shipping_method radios are stripped from the #order_review fragment
	 *    (they live in the plugin-owned Shipping block — req 30/34).
	 *    The shipping COST row is preserved in #order_review.
	 *
	 * @param array $fragments
	 * @return array
	 */
	public static function filter_update_order_review_fragments( $fragments ) {
		if ( isset( $fragments['.woocommerce-checkout-payment'] ) ) {
			$fragments['.woocommerce-checkout-payment'] = (string) preg_replace(
				'/<div[^>]*\sclass="[^"]*form-row[^"]*place-order[^"]*"[^>]*>.*?<\/div>\s*/is',
				'',
				$fragments['.woocommerce-checkout-payment']
			);
		}

		// Strip shipping METHOD radios from the order review AJAX fragment so
		// they are not duplicated inside #order_review. The shipping COST row
		// is preserved.
		foreach ( array( '#order_review', '.woocommerce-checkout-review-order' ) as $key ) {
			if ( isset( $fragments[ $key ] ) ) {
				$fragments[ $key ] = self::strip_shipping_method_radios( $fragments[ $key ] );
			}
		}

		return $fragments;
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
