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
	 * Flag indicating when the plugin's own order button block is actively rendering.
	 *
	 * When false, any calls to `woocommerce_order_button_html` (such as inside
	 * WooCommerce's default checkout/payment.php during AJAX or native rendering)
	 * will return an empty string to guarantee EXACTLY ONE button exists.
	 *
	 * @var bool
	 */
	public static $is_rendering_plugin_order_button = false;

	/**
	 * Flag to ensure order bump renders exactly once at the configured position.
	 *
	 * @var bool
	 */
	public static $order_bump_rendered = false;

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
		add_filter( 'woocommerce_checkout_cart_item_quantity', array( __CLASS__, 'filter_cart_item_quantity' ), 10, 3 );

		// Keep WooCommerce's AJAX "update order review" response from re-injecting the
		// place order button into #payment. The button lives in its own plugin-owned
		// block (req 32), so it must never be duplicated by AJAX updates.
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'filter_update_order_review_fragments' ), 20 );

		// AJAX endpoints for cart synchronization.
		add_action( 'wp_ajax_wcsc_sync_cart', array( __CLASS__, 'ajax_sync_cart' ) );
		add_action( 'wp_ajax_nopriv_wcsc_sync_cart', array( __CLASS__, 'ajax_sync_cart' ) );

		// AJAX endpoints for Order Bump toggle.
		add_action( 'wp_ajax_wcsc_toggle_order_bump', array( __CLASS__, 'ajax_toggle_order_bump' ) );
		add_action( 'wp_ajax_nopriv_wcsc_toggle_order_bump', array( __CLASS__, 'ajax_toggle_order_bump' ) );

		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_bd_phone_number' ), 10, 2 );

		// License verification on order placement.
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate_license_checkout_submission' ) );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_license_after_checkout_validation' ), 5, 2 );
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
	 * Check if current execution context is within the WC Smart Checkout widget.
	 *
	 * Returns true ONLY when:
	 * 1. An active widget render cycle is currently executing in PHP (`self::$active_widget_settings` is set).
	 * 2. An AJAX request (such as `update_order_review` or `checkout`) contains our widget's signature `wcsc_is_smart_checkout`.
	 *
	 * Returns false for standard WooCommerce checkouts, CartFlows steps, and third-party checkout plugins.
	 *
	 * @return bool
	 */
	public static function is_widget_checkout_context() {
		// Active widget rendering in progress
		if ( ! empty( self::$active_widget_settings ) ) {
			return true;
		}

		// AJAX checkout / update_order_review containing our form hidden field
		if ( isset( $_POST['post_data'] ) ) {
			$post_data = (string) $_POST['post_data'];
			if ( strpos( $post_data, 'wcsc_is_smart_checkout' ) !== false ) {
				return true;
			}
		}

		// Direct POST field
		if ( isset( $_POST['wcsc_is_smart_checkout'] ) && ( 'yes' === $_POST['wcsc_is_smart_checkout'] || '1' === (string) $_POST['wcsc_is_smart_checkout'] ) ) {
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
		if ( ! self::is_widget_checkout_context() ) {
			return $button_text;
		}

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
		// If not inside our widget context, leave the default order button completely untouched.
		if ( ! self::is_widget_checkout_context() ) {
			return $button_html;
		}

		// Inside our widget, only render button markup when our own dedicated .wcas-block-order-button is actively rendering.
		if ( ! self::$is_rendering_plugin_order_button || empty( self::$active_widget_settings ) ) {
			return '';
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
		$clean_total = html_entity_decode( wp_strip_all_tags( $current_total ), ENT_QUOTES, 'UTF-8' );
		$clean_total = str_replace( "\xc2\xa0", ' ', $clean_total );
		$price_html  = '<span class="wcsc-btn-price-wrap"><span class="wcsc-btn-price">' . esc_html( $clean_total ) . '</span></span>';

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

		// Check license status.
		$is_licensed = ! class_exists( '\WCSC\License_Manager' ) || \WCSC\License_Manager::is_active();
		$extra_attr  = '';
		if ( ! $is_licensed ) {
			$anim_class .= ' wcsc-license-unauthorized';
			$extra_attr  = ' disabled="disabled" style="opacity: 0.65; cursor: not-allowed; pointer-events: none;"';
		}

		// Build button. Kept inside our plugin-owned Order Button block (not in #payment).
		$raw_btn_template = ! empty( $settings['order_button_text'] ) ? $settings['order_button_text'] : __( 'Order Now', 'wc-smart-checkout-builder' );
		$custom_button = sprintf(
			'<button type="submit" class="button alt wp-element-button wcsc-order-now-btn %1$s" name="woocommerce_checkout_place_order" id="place_order" value="%2$s" data-value="%2$s" data-template-text="%4$s"%5$s>' .
			'<span class="wcsc-btn-beam wcsc-beam-top"></span>' .
			'<span class="wcsc-btn-beam wcsc-beam-bottom"></span>' .
			'<span class="wcsc-btn-content">%3$s</span>' .
			'</button>',
			esc_attr( $anim_class ),
			esc_attr( wp_strip_all_tags( $btn_text ) ), // keep value clean
			$content_inner,
			esc_attr( $raw_btn_template ),
			$extra_attr
		);

		if ( ! $is_licensed ) {
			$unauthorized_notice = '<div class="wcsc-license-unauthorized-notice" style="margin-top: 12px; padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; color: #991b1b; font-size: 13px; line-height: 1.5; text-align: center; font-weight: 500;">' .
				'<strong>' . esc_html__( 'Unauthorized License Key!', 'wc-smart-checkout-builder' ) . '</strong> ' .
				sprintf(
					/* translators: %s: website domain link */
					esc_html__( 'Please purchase a valid license key from %s', 'wc-smart-checkout-builder' ),
					'<a href="https://developerzahir.com" target="_blank" rel="noopener noreferrer" style="color: #b91c1c; font-weight: 700; text-decoration: underline;">developerzahir.com</a>'
				) .
			'</div>';
			$custom_button .= $unauthorized_notice;
		}

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
		if ( ! self::is_widget_checkout_context() || empty( self::$active_widget_settings ) ) {
			return $translated_text;
		}

		$settings = self::$active_widget_settings;

		$mapping = array(
			'customer information' => 'billing_heading_text',
			'billing & shipping'   => 'billing_heading_text',
			'billing details'      => 'billing_heading_text',
			'your order'         => 'order_review_heading_text',
			'your orders'        => 'order_review_heading_text',
			'product'            => 'product_label_text',
			'subtotal'           => 'subtotal_label_text',
			'shipping'           => 'shipping_label_text',
			'payment'            => 'payment_heading_text',
			'total'              => 'total_label_text',
		);

		$lower_text = strtolower( $text );

		if ( isset( $mapping[ $lower_text ] ) && ! empty( $settings[ $mapping[ $lower_text ] ] ) ) {
			return esc_html( $settings[ $mapping[ $lower_text ] ] );
		}

		return $translated_text;
	}

	/**
	 * Filter cart item name to inject thumbnail and inline quantity if enabled.
	 *
	 * @param string $item_name
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 * @return string
	 */
	public static function filter_cart_item_name( $item_name, $cart_item, $cart_item_key ) {
		// Prevent double-wrapping
		if ( strpos( $item_name, 'wcsc-cart-item-with-img' ) !== false || strpos( $item_name, 'wcsc-cart-item-name-text' ) !== false ) {
			return $item_name;
		}

		if ( ! self::is_widget_checkout_context() ) {
			return $item_name;
		}

		$show_image = true;
		if ( ! empty( self::$active_widget_settings ) ) {
			$show_image = ( ! isset( self::$active_widget_settings['show_cart_item_image'] ) || 'yes' === self::$active_widget_settings['show_cart_item_image'] );
		} elseif ( isset( $_POST['post_data'] ) ) {
			parse_str( sanitize_text_field( wp_unslash( $_POST['post_data'] ) ), $post_vars );
			if ( isset( $post_vars['wcsc_show_cart_item_image'] ) && 'no' === $post_vars['wcsc_show_cart_item_image'] ) {
				$show_image = false;
			}
		}

		$qty      = isset( $cart_item['quantity'] ) ? absint( $cart_item['quantity'] ) : 1;
		$qty_html = ' <strong class="product-quantity">&times;&nbsp;' . $qty . '</strong>';

		if ( $show_image ) {
			$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
			if ( $product ) {
				$thumbnail = $product->get_image( array( 48, 48 ), array( 'class' => 'wcsc-cart-item-image' ) );
				if ( $thumbnail ) {
					return '<div class="wcsc-cart-item-with-img">' . $thumbnail . '<span class="wcsc-cart-item-name-text">' . $item_name . $qty_html . '</span></div>';
				}
			}
		}

		return '<span class="wcsc-cart-item-name-text">' . $item_name . $qty_html . '</span>';
	}

	/**
	 * Suppress detached checkout cart item quantity when already rendered inline.
	 *
	 * @param string $quantity_html
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 * @return string
	 */
	public static function filter_cart_item_quantity( $quantity_html, $cart_item, $cart_item_key ) {
		if ( ! self::is_widget_checkout_context() ) {
			return $quantity_html;
		}
		return '';
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
		self::$order_bump_rendered    = false;

		if ( self::is_editor_environment() ) {
			// In Elementor live editor or save builder: render safe preview template.
			$template_path = WCSC_PATH . 'templates/checkout/editor-checkout-preview.php';
			if ( file_exists( $template_path ) ) {
				include $template_path;
			}
			self::$active_widget_settings = null;
			self::$active_product         = null;
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

		// Ensure default shipping method is selected in session and totals are pre-calculated.
		self::ensure_default_shipping_method();

		$checkout = WC()->checkout();
		if ( ! $checkout ) {
			return;
		}

		// ----------------------------------------------------------------------
		// Layout class — Elementor setting is the single source of truth.
		// ----------------------------------------------------------------------
		$layout = ! empty( $settings['checkout_layout'] ) ? $settings['checkout_layout'] : '2_columns';
		if ( '1_column' === $layout ) {
			$layout_class = 'wcas-layout-one-column';
		} else {
			$layout_class = 'wcas-layout-two-column';
		}

		$order_button_pos = ! empty( $settings['order_button_position'] ) ? $settings['order_button_position'] : 'right_column';
		if ( 'under_shipping' === $order_button_pos ) {
			$order_button_pos = 'left_column';
		} elseif ( 'under_payment' === $order_button_pos || 'under_order_review' === $order_button_pos ) {
			$order_button_pos = 'right_column';
		}
		if ( '1_column' === $layout ) {
			$order_button_pos = 'full_width';
		}
		$pos_class = 'wcas-order-btn-pos-' . sanitize_html_class( str_replace( '_', '-', $order_button_pos ) );

		// ----------------------------------------------------------------------
		// Per-block visibility (conditional rendering).
		// Note: The Order Button is MANDATORY and always rendered.
		// ----------------------------------------------------------------------
		$blocks_enabled = array(
			'checkout_form' => true,
			'shipping'      => self::is_enabled( $settings, 'show_checkout_shipping' ) && WC()->cart->needs_shipping(),
			'order_review'  => self::is_enabled( $settings, 'show_checkout_order_review' ),
			'payment'       => self::is_enabled( $settings, 'show_checkout_payment' ),
			'order_button'  => true,
		);

		?>
		<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
			<div class="wcas-checkout-wrapper <?php echo esc_attr( $layout_class . ' ' . $pos_class ); ?>"
				<?php echo self::render_data_attrs( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<div class="wcsc-loading-overlay">
					<div class="wcsc-spinner"></div>
				</div>

				<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
					<?php
					if ( function_exists( 'wc_print_notices' ) ) {
						wc_print_notices();
					}
					?>
				</div>

				<?php if ( '1_column' === $layout ) : ?>
					<div class="wcas-checkout-column wcas-checkout-column-single">
						<?php
						if ( ! empty( $blocks_enabled['checkout_form'] ) ) {
							self::render_checkout_form_block();
						}
						if ( ! empty( $blocks_enabled['shipping'] ) ) {
							self::render_shipping_block();
						}
						if ( ! empty( $blocks_enabled['order_review'] ) ) {
							self::render_order_review_block();
						}
						if ( ! empty( $blocks_enabled['payment'] ) ) {
							self::render_payment_block();
						}
						self::render_order_button_block();
						?>
					</div>
				<?php else : ?>
					<?php
					$customer_info_col  = ! empty( $settings['customer_info_column'] ) ? $settings['customer_info_column'] : 'col_1';
					$shipping_block_col = ! empty( $settings['shipping_block_column'] ) ? $settings['shipping_block_column'] : 'col_1';
					$order_review_col   = ! empty( $settings['order_review_column'] ) ? $settings['order_review_column'] : 'col_2';
					$payment_block_col  = ! empty( $settings['payment_block_column'] ) ? $settings['payment_block_column'] : 'col_2';
					?>
					<div class="wcas-checkout-column wcas-checkout-column-left">
						<?php
						if ( 'col_1' === $customer_info_col && ! empty( $blocks_enabled['checkout_form'] ) ) {
							self::render_checkout_form_block();
						}
						if ( 'col_1' === $shipping_block_col && ! empty( $blocks_enabled['shipping'] ) ) {
							self::render_shipping_block();
						}
						if ( 'col_1' === $order_review_col && ! empty( $blocks_enabled['order_review'] ) ) {
							self::render_order_review_block();
						}
						if ( 'col_1' === $payment_block_col && ! empty( $blocks_enabled['payment'] ) ) {
							self::render_payment_block();
						}
						if ( 'left_column' === $order_button_pos ) {
							self::render_order_button_block();
						}
						?>
					</div>

					<div class="wcas-checkout-column wcas-checkout-column-right">
						<?php
						if ( 'col_2' === $customer_info_col && ! empty( $blocks_enabled['checkout_form'] ) ) {
							self::render_checkout_form_block();
						}
						if ( 'col_2' === $shipping_block_col && ! empty( $blocks_enabled['shipping'] ) ) {
							self::render_shipping_block();
						}
						if ( 'col_2' === $order_review_col && ! empty( $blocks_enabled['order_review'] ) ) {
							self::render_order_review_block();
						}
						if ( 'col_2' === $payment_block_col && ! empty( $blocks_enabled['payment'] ) ) {
							self::render_payment_block();
						}
						if ( 'right_column' === $order_button_pos ) {
							self::render_order_button_block();
						}
						?>
					</div>

					<?php if ( 'full_width' === $order_button_pos ) : ?>
						<div class="wcas-checkout-row-full">
							<?php self::render_order_button_block(); ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<?php
			$modal_title = ! empty( $settings['phone_modal_title'] ) ? $settings['phone_modal_title'] : __( 'প্রয়োজনীয় তথ্য পূরণ করুন', 'wc-smart-checkout-builder' );
			$modal_msg   = ! empty( $settings['phone_modal_message'] ) ? $settings['phone_modal_message'] : __( 'অনুগ্রহ করে নিচের তথ্যগুলো সঠিকভাবে প্রদান করুন:', 'wc-smart-checkout-builder' );
			$modal_btn   = ! empty( $settings['phone_modal_btn_text'] ) ? $settings['phone_modal_btn_text'] : __( 'ঠিক আছে', 'wc-smart-checkout-builder' );
			self::render_phone_modal_html( $modal_title, $modal_msg, $modal_btn );

			$phone_val_enabled = ! isset( $settings['enable_phone_validation'] ) || 'yes' === $settings['enable_phone_validation'];
			if ( $phone_val_enabled ) {
				echo '<input type="hidden" name="wcsc_bd_phone_validation" value="1" />';
			}

			echo '<input type="hidden" name="wcsc_show_cart_item_image" value="' . esc_attr( ( ! isset( $settings['show_cart_item_image'] ) || 'yes' === $settings['show_cart_item_image'] ) ? 'yes' : 'no' ) . '" />';
			echo '<input type="hidden" name="wcsc_is_smart_checkout" value="yes" />';

			$sticky_enabled = ! isset( $settings['enable_mobile_sticky_button'] ) || 'yes' === $settings['enable_mobile_sticky_button'];
			if ( isset( $settings['style_enable_mobile_sticky_button'] ) && 'no' === $settings['style_enable_mobile_sticky_button'] ) {
				$sticky_enabled = false;
			}

			if ( $sticky_enabled ) {
				$sticky_text = __( 'অর্ডার করুন', 'wc-smart-checkout-builder' );
				if ( ! empty( $settings['style_mobile_sticky_button_text'] ) && 'অর্ডার করুন' !== $settings['style_mobile_sticky_button_text'] ) {
					$sticky_text = $settings['style_mobile_sticky_button_text'];
				} elseif ( ! empty( $settings['mobile_sticky_button_text'] ) ) {
					$sticky_text = $settings['mobile_sticky_button_text'];
				}
				self::render_mobile_sticky_button_html( $sticky_text );
			}
			?>
		</form>
		<?php
		self::$active_widget_settings = null;
		self::$active_product         = null;
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
	public static function render_data_attrs( $settings ) {
		$attrs = array(
			'data-txt-order'    => ! empty( $settings['order_review_heading_text'] ) ? $settings['order_review_heading_text'] : '',
			'data-txt-billing'  => ! empty( $settings['billing_heading_text'] ) ? $settings['billing_heading_text'] : '',
			'data-txt-product'  => ! empty( $settings['product_label_text'] ) ? $settings['product_label_text'] : '',
			'data-txt-subtotal' => ! empty( $settings['subtotal_label_text'] ) ? $settings['subtotal_label_text'] : '',
			'data-txt-shipping' => ! empty( $settings['shipping_label_text'] ) ? $settings['shipping_label_text'] : '',
			'data-txt-payment'  => ! empty( $settings['payment_heading_text'] ) ? $settings['payment_heading_text'] : '',
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
		self::render_order_bump_block();
		?>
		<div class="wcas-block wcas-block-checkout-form">
			<h3 class="wcsc-section-title wcas-block-title"><?php echo esc_html( self::get_billing_label() ); ?></h3>
			<?php
			// Native WooCommerce customer details (billing + shipping fields).
			// Rendered 1 field per row, 100% full-width across all layout modes.
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
	 * Get the custom or translated label for Billing & Shipping.
	 *
	 * @return string
	 */
	public static function get_billing_label() {
		return ! empty( self::$active_widget_settings['billing_heading_text'] )
			? sanitize_text_field( self::$active_widget_settings['billing_heading_text'] )
			: esc_html__( 'Customer information', 'wc-smart-checkout-builder' );
	}

	/**
	 * Get the custom or translated label for Payment.
	 *
	 * @return string
	 */
	public static function get_payment_label() {
		return ! empty( self::$active_widget_settings['payment_heading_text'] )
			? sanitize_text_field( self::$active_widget_settings['payment_heading_text'] )
			: esc_html__( 'Payment', 'wc-smart-checkout-builder' );
	}

	/**
	 * Get the custom or translated label for Shipping.
	 *
	 * @return string
	 */
	public static function get_shipping_label() {
		return ! empty( self::$active_widget_settings['shipping_label_text'] )
			? sanitize_text_field( self::$active_widget_settings['shipping_label_text'] )
			: esc_html__( 'Shipping', 'wc-smart-checkout-builder' );
	}

	/**
	 * Get the custom or translated label for Product.
	 *
	 * @return string
	 */
	public static function get_product_label() {
		return ! empty( self::$active_widget_settings['product_label_text'] )
			? sanitize_text_field( self::$active_widget_settings['product_label_text'] )
			: esc_html__( 'Product', 'wc-smart-checkout-builder' );
	}

	/**
	 * Get the custom or translated label for Subtotal.
	 *
	 * @return string
	 */
	public static function get_subtotal_label() {
		return ! empty( self::$active_widget_settings['subtotal_label_text'] )
			? sanitize_text_field( self::$active_widget_settings['subtotal_label_text'] )
			: esc_html__( 'Subtotal', 'wc-smart-checkout-builder' );
	}

	/**
	 * Get the custom or translated label for Total.
	 *
	 * @return string
	 */
	public static function get_total_label() {
		return ! empty( self::$active_widget_settings['total_label_text'] )
			? sanitize_text_field( self::$active_widget_settings['total_label_text'] )
			: esc_html__( 'Total', 'wc-smart-checkout-builder' );
	}

	/**
	 * Ensure the default shipping method is selected in WooCommerce session on page load
	 * and cart totals are calculated so the Order Review shipping charge row and total
	 * are immediately populated without requiring a manual click.
	 */
	public static function ensure_default_shipping_method() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_shipping() ) {
			return;
		}

		// Ensure customer shipping package calculation has run
		$packages = WC()->shipping()->get_packages();
		if ( empty( $packages ) && method_exists( WC()->cart, 'calculate_shipping' ) ) {
			WC()->cart->calculate_shipping();
			$packages = WC()->shipping()->get_packages();
		}

		$chosen_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();
		$needs_recalc   = false;

		if ( ! empty( $packages ) ) {
			foreach ( $packages as $i => $package ) {
				$available_rates = isset( $package['rates'] ) ? $package['rates'] : array();
				if ( empty( $available_rates ) ) {
					continue;
				}

				if ( empty( $chosen_methods[ $i ] ) || ! isset( $available_rates[ $chosen_methods[ $i ] ] ) ) {
					$first_rate           = reset( $available_rates );
					$chosen_methods[ $i ] = $first_rate->id;
					$needs_recalc         = true;
				}
			}
		}

		if ( $needs_recalc && WC()->session ) {
			WC()->session->set( 'chosen_shipping_methods', $chosen_methods );
			WC()->cart->calculate_totals();
		}
	}

	/**
	 * Render WooCommerce shipping method options for the dedicated Shipping block.
	 *
	 * Outputs selectable shipping rates natively using WooCommerce session data.
	 *
	 * @return string
	 */
	public static function render_shipping_methods_html() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_shipping() ) {
			return '';
		}

		$packages = WC()->shipping()->get_packages();
		if ( empty( $packages ) && method_exists( WC()->cart, 'calculate_shipping' ) ) {
			WC()->cart->calculate_shipping();
			$packages = WC()->shipping()->get_packages();
		}
		if ( empty( $packages ) ) {
			return '<p class="woocommerce-shipping-no-methods">' . esc_html__( 'No shipping options available.', 'wc-smart-checkout-builder' ) . '</p>';
		}

		$chosen_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();

		ob_start();
		?>
		<ul id="shipping_method" class="woocommerce-shipping-methods">
			<?php
			foreach ( $packages as $i => $package ) :
				$chosen_method     = isset( $chosen_methods[ $i ] ) ? $chosen_methods[ $i ] : '';
				$available_methods = isset( $package['rates'] ) ? $package['rates'] : array();

				if ( empty( $available_methods ) ) :
					?>
					<li class="wcsc-no-shipping-methods">
						<?php echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', __( 'There are no shipping options available. Please ensure that your address has been entered correctly.', 'woocommerce' ) ) ); ?>
					</li>
					<?php
				else :
					// If no chosen method is set or the chosen method is not in available methods, pick the first
					if ( empty( $chosen_method ) || ! isset( $available_methods[ $chosen_method ] ) ) {
						$first_key     = array_key_first( $available_methods );
						$chosen_method = $first_key;
					}

					foreach ( $available_methods as $method ) :
						$checked   = checked( $method->id, $chosen_method, false );
						$is_active = ( $method->id === $chosen_method ) ? ' is-active' : '';
						?>
						<li class="wcsc-shipping-card<?php echo esc_attr( $is_active ); ?>">
							<input type="radio" 
								name="shipping_method[<?php echo esc_attr( $i ); ?>]" 
								data-index="<?php echo esc_attr( $i ); ?>" 
								id="shipping_method_<?php echo esc_attr( $i ); ?>_<?php echo esc_attr( sanitize_title( $method->id ) ); ?>" 
								value="<?php echo esc_attr( $method->id ); ?>" 
								class="shipping_method" 
								<?php echo $checked; ?> />
							<label for="shipping_method_<?php echo esc_attr( $i ); ?>_<?php echo esc_attr( sanitize_title( $method->id ) ); ?>">
								<?php echo wc_cart_totals_shipping_method_label( $method ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</label>
						</li>
						<?php
					endforeach;
				endif;
			endforeach;
			?>
		</ul>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the real WooCommerce calculated shipping charge HTML for the Order Review table.
	 *
	 * Returns ONLY the calculated cost (e.g. ৳120 or Free!) without any radio buttons
	 * or selection interface.
	 *
	 * @return string
	 */
	public static function get_formatted_shipping_charge_html() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_shipping() ) {
			return '<span class="woocommerce-Price-amount amount">' . wc_price( 0 ) . '</span>';
		}

		$packages       = WC()->shipping()->get_packages();
		$chosen_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();

		$total_shipping_cost = 0;
		$has_methods         = false;

		if ( ! empty( $packages ) ) {
			foreach ( $packages as $i => $package ) {
				$chosen = isset( $chosen_methods[ $i ] ) ? $chosen_methods[ $i ] : '';
				if ( $chosen && isset( $package['rates'][ $chosen ] ) ) {
					$rate                 = $package['rates'][ $chosen ];
					$cost                 = (float) $rate->get_cost();
					$tax                  = (float) $rate->get_shipping_tax();
					$total_shipping_cost += ( $cost + $tax );
					$has_methods          = true;
				} elseif ( ! empty( $package['rates'] ) ) {
					$first_rate           = reset( $package['rates'] );
					$cost                 = (float) $first_rate->get_cost();
					$tax                  = (float) $first_rate->get_shipping_tax();
					$total_shipping_cost += ( $cost + $tax );
					$has_methods          = true;
				}
			}
		}

		if ( $has_methods ) {
			if ( $total_shipping_cost > 0 ) {
				return wc_price( $total_shipping_cost );
			} else {
				return '<span class="woocommerce-Price-amount amount">' . wc_price( 0 ) . '</span>';
			}
		}

		// Fallback to WC()->cart calculated shipping total
		$cart_shipping = WC()->cart->get_cart_shipping_total();
		if ( ! empty( $cart_shipping ) ) {
			return $cart_shipping;
		}

		return '<span class="woocommerce-Price-amount amount">' . wc_price( 0 ) . '</span>';
	}

	/**
	 * Block 2 — Shipping (shipping method selection).
	 *
	 * Rendered as an independent plugin-owned block (.wcas-block-shipping).
	 * This block contains ONLY the method/rate selection UI — never the
	 * shipping cost (which is displayed inside the Order Review block).
	 */
	private static function render_shipping_block() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_shipping() ) {
			return;
		}

		$heading      = self::get_shipping_label();
		$methods_html = self::render_shipping_methods_html();

		if ( empty( $methods_html ) ) {
			return;
		}
		?>
		<div class="wcas-block wcas-block-shipping">
			<h3 class="wcsc-section-title wcas-block-title wcsc-shipping-heading"><?php echo esc_html( $heading ); ?></h3>
			<div class="wcas-shipping-methods-wrapper">
				<?php echo $methods_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the complete WooCommerce order review table.
	 *
	 * Ensures clean plugin-owned wrappers on every row:
	 *   - .wcas-order-review-product
	 *   - .wcas-order-review-subtotal
	 *   - .wcas-order-review-shipping (calculated shipping charge row ONLY, no selection UI)
	 *   - .wcas-order-review-total
	 *
	 * @return string
	 */
	public static function render_order_review_table_html() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return '';
		}

		$product_label  = self::get_product_label();
		$subtotal_label = self::get_subtotal_label();
		$shipping_label = self::get_shipping_label();
		$total_label    = self::get_total_label();

		ob_start();
		?>
		<table class="shop_table woocommerce-checkout-review-order-table">
			<thead>
				<tr>
					<th class="product-name"><?php echo esc_html( $product_label ); ?></th>
					<th class="product-total"><?php echo esc_html( $subtotal_label ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				do_action( 'woocommerce_review_order_before_cart_contents' );

				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

					if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
						?>
						<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item wcas-order-review-product', $cart_item, $cart_item_key ) ); ?>">
							<td class="product-name">
								<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ) . '&nbsp;'; ?>
								<?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf( '&times;&nbsp;%s', $cart_item['quantity'] ) . '</strong>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</td>
							<td class="product-total">
								<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</td>
						</tr>
						<?php
					}
				}

				do_action( 'woocommerce_review_order_after_cart_contents' );
				?>
			</tbody>
			<tfoot>

				<tr class="cart-subtotal wcas-order-review-subtotal">
					<th><?php echo esc_html( $subtotal_label ); ?></th>
					<td><?php wc_cart_totals_subtotal_html(); ?></td>
				</tr>

				<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
					<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
						<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
					</tr>
				<?php endforeach; ?>

				<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
					<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
					<tr class="woocommerce-shipping-totals shipping wcas-order-review-shipping">
						<th><?php echo esc_html( $shipping_label ); ?></th>
						<td data-title="<?php echo esc_attr( $shipping_label ); ?>">
							<?php echo self::get_formatted_shipping_charge_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>
					<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
				<?php endif; ?>

				<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
					<tr class="fee">
						<th><?php echo esc_html( $fee->name ); ?></th>
						<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
					</tr>
				<?php endforeach; ?>

				<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
					<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
						<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
							<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
								<th><?php echo esc_html( $tax->label ); ?></th>
								<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr class="tax-total">
							<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
							<td><?php wc_cart_totals_taxes_total_html(); ?></td>
						</tr>
					<?php endif; ?>
				<?php endif; ?>

				<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

				<tr class="order-total wcas-order-review-total">
					<th><?php echo esc_html( $total_label ); ?></th>
					<td><?php wc_cart_totals_order_total_html(); ?></td>
				</tr>

				<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

			</tfoot>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Block 3 — Order Review (ordered products, subtotal, SHIPPING CHARGE, total).
	 *
	 * The shipping charge (.wcas-order-review-shipping) is displayed inside this
	 * Order Review table as an individual row using real WooCommerce calculations.
	 * The shipping method selection interface NEVER appears here.
	 */
	private static function render_order_review_block() {
		$heading = ! empty( self::$active_widget_settings['order_review_heading_text'] )
			? sanitize_text_field( self::$active_widget_settings['order_review_heading_text'] )
			: esc_html__( 'Your order', 'wc-smart-checkout-builder' );
		?>
		<div class="wcas-block wcas-block-order-review">
			<h3 id="order_review_heading" class="wcsc-section-title wcas-block-title"><?php echo esc_html( $heading ); ?></h3>
			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php echo self::render_order_review_table_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
			<h3 class="wcsc-section-title wcas-block-title wcsc-payment-heading"><?php echo esc_html( self::get_payment_label() ); ?></h3>
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
		$settings  = self::$active_widget_settings;
		$btn_text  = ! empty( $settings['order_button_text'] ) ? sanitize_text_field( $settings['order_button_text'] ) : __( 'Order Now', 'wc-smart-checkout-builder' );
		$btn_value = wp_strip_all_tags( $btn_text );

		$default_button = sprintf(
			'<button type="submit" class="button alt wp-element-button" name="woocommerce_checkout_place_order" id="place_order" value="%s" data-value="%s">%s</button>',
			esc_attr( $btn_value ),
			esc_attr( $btn_value ),
			esc_html__( 'Place order', 'woocommerce' )
		);

		self::$is_rendering_plugin_order_button = true;
		$button_html = apply_filters( 'woocommerce_order_button_html', $default_button );
		self::$is_rendering_plugin_order_button = false;
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
	 * Sanitise WooCommerce's AJAX "update order review" fragments so no blocks
	 * are duplicated or misplaced after checkout recalculation.
	 *
	 * 1. .woocommerce-checkout-review-order-table is updated with our clean table,
	 *    displaying the updated shipping charge row without any method radios.
	 * 2. .wcas-shipping-methods-wrapper is updated with the refreshed shipping methods.
	 * 3. The place order button and .place-order row are stripped from .woocommerce-checkout-payment.
	 *
	 * @param array $fragments
	 * @return array
	 */
	public static function filter_update_order_review_fragments( $fragments ) {
		// STRICT CONTEXT GUARD: If this update_order_review AJAX call is NOT from our widget,
		// DO NOT touch any fragments! Leave standard WooCommerce and CartFlows checkouts untouched.
		if ( ! self::is_widget_checkout_context() ) {
			return $fragments;
		}

		// Update the order review table with real WooCommerce calculations & shipping charge row
		$fragments['.woocommerce-checkout-review-order-table'] = self::render_order_review_table_html();

		// Update the selectable shipping methods in the dedicated Shipping block
		$fragments['.wcas-shipping-methods-wrapper'] = '<div class="wcas-shipping-methods-wrapper">' . self::render_shipping_methods_html() . '</div>';

		// Strip place-order button and container from payment gateway fragment ONLY inside our widget context
		if ( isset( $fragments['.woocommerce-checkout-payment'] ) ) {
			$fragments['.woocommerce-checkout-payment'] = (string) preg_replace(
				'/<div[^>]*\sclass="[^"]*place-order[^"]*"[^>]*>.*?<\/div>\s*/is',
				'',
				$fragments['.woocommerce-checkout-payment']
			);
			$fragments['.woocommerce-checkout-payment'] = (string) preg_replace(
				'/<button[^>]*id="place_order"[^>]*>.*?<\/button>\s*/is',
				'',
				$fragments['.woocommerce-checkout-payment']
			);
		}

		return $fragments;
	}

	/**
	 * Render the universal checkout validation error modal markup.
	 *
	 * @param string $title
	 * @param string $message
	 * @param string $btn_text
	 */
	public static function render_phone_modal_html( $title, $message, $btn_text ) {
		?>
		<div id="wcsc-validation-modal" class="wcsc-phone-modal-backdrop wcsc-validation-modal-backdrop" style="display: none;" role="dialog" aria-modal="true">
			<div class="wcsc-phone-modal-box wcsc-validation-modal-box">
				<div class="wcsc-phone-modal-icon wcsc-validation-modal-icon">
					<svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="12" cy="12" r="10"></circle>
						<line x1="12" y1="8" x2="12" y2="12"></line>
						<line x1="12" y1="16" x2="12.01" y2="16"></line>
					</svg>
				</div>
				<h4 class="wcsc-phone-modal-title wcsc-validation-modal-title"><?php echo esc_html( $title ); ?></h4>
				<div class="wcsc-phone-modal-message wcsc-validation-modal-message">
					<p class="wcsc-modal-intro-text" style="margin: 0 0 8px 0;"><?php echo esc_html( $message ); ?></p>
					<ul class="wcsc-missing-fields-list"></ul>
				</div>
				<button type="button" class="wcsc-phone-modal-close-btn wcsc-validation-modal-close-btn"><?php echo esc_html( $btn_text ); ?></button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the mobile sticky floating order now button.
	 *
	 * @param string $text
	 */
	public static function render_mobile_sticky_button_html( $text ) {
		$text = str_replace( '{total_price}', '', $text );
		?>
		<div class="wcsc-mobile-sticky-bar wcsc-floating-btn-wrap" id="wcsc-mobile-sticky-bar">
			<button type="button" class="wcsc-mobile-sticky-btn wcsc-floating-btn">
				<span class="wcsc-sticky-shine"></span>
				<span class="wcsc-sticky-text"><?php echo esc_html( trim( $text ) ); ?></span>
			</button>
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
				$selected_variation = null;
				$default_attributes = method_exists( $product, 'get_default_attributes' ) ? $product->get_default_attributes() : array();

				if ( ! empty( $default_attributes ) ) {
					foreach ( $available_variations as $variation ) {
						$match = true;
						foreach ( $default_attributes as $attr_key => $attr_val ) {
							$var_attr_key = 'attribute_' . $attr_key;
							if ( isset( $variation['attributes'][ $var_attr_key ] ) && '' !== $variation['attributes'][ $var_attr_key ] && $variation['attributes'][ $var_attr_key ] !== $attr_val ) {
								$match = false;
								break;
							}
						}
						if ( $match ) {
							$selected_variation = $variation;
							break;
						}
					}
				}

				if ( ! $selected_variation ) {
					$selected_variation = $available_variations[0];
				}

				$cart->add_to_cart(
					$product_id,
					1,
					$selected_variation['variation_id'],
					$selected_variation['attributes']
				);
			}
		} else {
			$cart->add_to_cart( $product_id, 1 );
		}
	}

	/**
	 * Render the Order Bump / Offer Products block if enabled.
	 * Fixed permanently above customer info / below variations.
	 */
	public static function render_order_bump_block() {
		if ( self::$order_bump_rendered ) {
			return;
		}

		$settings = self::$active_widget_settings;
		if ( empty( $settings ) || empty( $settings['enable_order_bump'] ) || 'yes' !== $settings['enable_order_bump'] ) {
			return;
		}

		$product_ids = Product_Handler::get_order_bump_product_ids( $settings );

		if ( empty( $product_ids ) ) {
			return;
		}

		self::$order_bump_rendered = true;

		$layout_desktop = ! empty( $settings['order_bump_layout'] ) ? $settings['order_bump_layout'] : 'list';
		$layout_tablet  = ! empty( $settings['order_bump_layout_tablet'] ) ? $settings['order_bump_layout_tablet'] : $layout_desktop;
		$layout_mobile  = ! empty( $settings['order_bump_layout_mobile'] ) ? $settings['order_bump_layout_mobile'] : ( 'grid' === $layout_desktop ? 'list' : $layout_desktop );

		$cols_desktop = ! empty( $settings['order_bump_columns'] ) ? $settings['order_bump_columns'] : '1';
		$cols_tablet  = ! empty( $settings['order_bump_columns_tablet'] ) ? $settings['order_bump_columns_tablet'] : $cols_desktop;
		$cols_mobile  = ! empty( $settings['order_bump_columns_mobile'] ) ? $settings['order_bump_columns_mobile'] : '1';

		$section_title    = ! empty( $settings['order_bump_section_title'] ) ? $settings['order_bump_section_title'] : __( 'ধামাকা অফার! সাথে এটাও যুক্ত করুন', 'wc-smart-checkout-builder' );
		$section_subtitle = ! empty( $settings['order_bump_section_subtitle'] ) ? $settings['order_bump_section_subtitle'] : '';
		$action_text      = ! empty( $settings['order_bump_action_text'] ) ? $settings['order_bump_action_text'] : __( 'অর্ডার যুক্ত করুন', 'wc-smart-checkout-builder' );

		// Get cart product IDs
		$cart_product_ids = array();
		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$cart_product_ids[] = absint( $cart_item['product_id'] );
			}
		}

		?>
		<div class="wcas-block wcsc-order-bump-block wcsc-bump-d-<?php echo esc_attr( $layout_desktop ); ?> wcsc-bump-t-<?php echo esc_attr( $layout_tablet ); ?> wcsc-bump-m-<?php echo esc_attr( $layout_mobile ); ?> wcsc-bump-cols-d-<?php echo esc_attr( $cols_desktop ); ?> wcsc-bump-cols-t-<?php echo esc_attr( $cols_tablet ); ?> wcsc-bump-cols-m-<?php echo esc_attr( $cols_mobile ); ?> wcsc-order-bump-layout-<?php echo esc_attr( $layout_desktop ); ?>">
			<div class="wcas-order-bump-container">
				<?php if ( ! empty( $section_title ) ) : ?>
					<h4 class="wcsc-order-bump-heading"><?php echo esc_html( $section_title ); ?></h4>
				<?php endif; ?>
				<?php if ( ! empty( $section_subtitle ) ) : ?>
					<div class="wcsc-order-bump-subtitle"><?php echo esc_html( $section_subtitle ); ?></div>
				<?php endif; ?>
				<div class="wcsc-order-bump-list">
					<?php
					foreach ( $product_ids as $pid ) :
						$bump_product = wc_get_product( $pid );
						if ( ! $bump_product || ! $bump_product->is_purchasable() ) {
							continue;
						}
						$is_in_cart = in_array( $pid, $cart_product_ids, true );

						// Strict image resolution to prevent wrong or mismatched images
						$image_id = $bump_product->get_image_id();
						if ( ! $image_id && $bump_product->is_type( 'variation' ) ) {
							$image_id = get_post_thumbnail_id( $bump_product->get_parent_id() );
						}
						if ( ! $image_id ) {
							$image_id = get_post_thumbnail_id( $pid );
						}

						$img_url = '';
						if ( $image_id ) {
							$img_src = wp_get_attachment_image_src( $image_id, 'thumbnail' );
							if ( ! empty( $img_src[0] ) ) {
								$img_url = $img_src[0];
							}
						}
						if ( ! $img_url && function_exists( 'get_the_post_thumbnail_url' ) ) {
							$img_url = get_the_post_thumbnail_url( $pid, 'thumbnail' );
						}
						if ( ! $img_url && function_exists( 'wc_placeholder_img_src' ) ) {
							$img_url = wc_placeholder_img_src( 'thumbnail' );
						}
						$price_html = $bump_product->get_price_html();
						?>
						<div class="wcsc-order-bump-card<?php echo $is_in_cart ? ' is-selected' : ''; ?>" data-product-id="<?php echo esc_attr( $pid ); ?>" data-action-text="<?php echo esc_attr( $action_text ); ?>">
							<?php if ( $img_url ) : ?>
								<div class="wcsc-order-bump-thumb-wrap">
									<input type="checkbox" class="wcsc-order-bump-checkbox" id="wcsc-bump-<?php echo esc_attr( $pid ); ?>" data-product-id="<?php echo esc_attr( $pid ); ?>" <?php checked( $is_in_cart, true ); ?> />
									<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $bump_product->get_name() ); ?>" class="wcsc-order-bump-thumb" />
								</div>
							<?php else : ?>
								<input type="checkbox" class="wcsc-order-bump-checkbox" id="wcsc-bump-<?php echo esc_attr( $pid ); ?>" data-product-id="<?php echo esc_attr( $pid ); ?>" <?php checked( $is_in_cart, true ); ?> />
							<?php endif; ?>
							<div class="wcsc-order-bump-details">
								<div class="wcsc-order-bump-title"><?php echo esc_html( $bump_product->get_name() ); ?></div>
								<div class="wcsc-order-bump-price"><?php echo wp_kses_post( $price_html ); ?></div>
							</div>
							<div class="wcsc-order-bump-action">
								<button type="button" class="wcsc-order-bump-btn<?php echo $is_in_cart ? ' is-active' : ''; ?>" data-product-id="<?php echo esc_attr( $pid ); ?>">
									<span class="wcsc-order-bump-btn-icon"><?php echo $is_in_cart ? '✓' : '+'; ?></span>
									<span class="wcsc-order-bump-btn-text"><?php echo $is_in_cart ? esc_html__( 'যুক্ত হয়েছে', 'wc-smart-checkout-builder' ) : esc_html( $action_text ); ?></span>
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
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

		// Preserve selected order bump products if provided
		if ( ! empty( $_POST['bump_product_ids'] ) && is_array( $_POST['bump_product_ids'] ) ) {
			foreach ( $_POST['bump_product_ids'] as $bump_id ) {
				$bump_id = absint( $bump_id );
				if ( $bump_id > 0 && $bump_id !== $product_id ) {
					$cart->add_to_cart( $bump_id, 1 );
				}
			}
		}

		$cart->calculate_totals();

		$clean_total = html_entity_decode( wp_strip_all_tags( $cart->get_total() ), ENT_QUOTES, 'UTF-8' );
		$clean_total = str_replace( "\xc2\xa0", ' ', $clean_total );

		wp_send_json_success( array(
			'cart_hash'     => $cart->get_cart_hash(),
			'item_count'    => $cart->get_cart_contents_count(),
			'subtotal'      => $cart->get_cart_subtotal(),
			'total'         => $cart->get_total(),
			'raw_total'     => $cart->get_total( 'edit' ),
			'currency_text' => $clean_total,
		) );
	}

	/**
	 * AJAX handler to toggle adding/removing an Order Bump product.
	 */
	public static function ajax_toggle_order_bump() {
		check_ajax_referer( 'wcsc_checkout_nonce', 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce cart unavailable.', 'wc-smart-checkout-builder' ) ) );
		}

		$product_id  = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( $_POST['action_type'] ) : 'add';

		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_purchasable() ) {
			wp_send_json_error( array( 'message' => __( 'Selected offer product is not purchasable.', 'wc-smart-checkout-builder' ) ) );
		}

		$cart = WC()->cart;
		$found_cart_item_key = '';

		foreach ( $cart->get_cart() as $key => $item ) {
			if ( absint( $item['product_id'] ) === $product_id ) {
				$found_cart_item_key = $key;
				break;
			}
		}

		if ( 'add' === $action_type ) {
			if ( ! $found_cart_item_key ) {
				$cart->add_to_cart( $product_id, 1 );
			}
		} else {
			if ( $found_cart_item_key ) {
				$cart->remove_cart_item( $found_cart_item_key );
			}
		}

		$cart->calculate_totals();

		$clean_total = html_entity_decode( wp_strip_all_tags( $cart->get_total() ), ENT_QUOTES, 'UTF-8' );
		$clean_total = str_replace( "\xc2\xa0", ' ', $clean_total );

		wp_send_json_success( array(
			'action_type'   => $action_type,
			'product_id'    => $product_id,
			'cart_hash'     => $cart->get_cart_hash(),
			'item_count'    => $cart->get_cart_contents_count(),
			'subtotal'      => $cart->get_cart_subtotal(),
			'total'         => $cart->get_total(),
			'raw_total'     => $cart->get_total( 'edit' ),
			'currency_text' => $clean_total,
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
				$clean = preg_replace( '/[^0-9+]/', '', trim( $phone ) );
				// BD format regex: optional +880 or 880 or 0, followed by 1, then 3-9, then 8 digits
				if ( ! preg_match( '/^(?:\+?880|880|0)?1[3-9]\d{8}$/', $clean ) ) {
					$msg = ! empty( self::$active_widget_settings['phone_modal_message'] )
						? self::$active_widget_settings['phone_modal_message']
						: __( 'অনুগ্রহ করে একটি ১১ ডিজিটের বৈধ বাংলাদেশি মোবাইল নম্বর ব্যবহার করুন।', 'wc-smart-checkout-builder' );
					$errors->add( 'billing_phone', esc_html( $msg ) );
				}
			} else {
				$errors->add( 'billing_phone', __( 'ফোন নম্বর দিন।', 'wc-smart-checkout-builder' ) );
			}
		}
	}

	/**
	 * Prevent checkout processing if license is inactive or unauthorized.
	 */
	public static function validate_license_checkout_submission() {
		if ( ! self::is_widget_checkout_context() ) {
			return;
		}

		if ( class_exists( '\WCSC\License_Manager' ) && ! \WCSC\License_Manager::is_active() ) {
			$msg = sprintf(
				/* translators: %s: website link */
				__( 'Unauthorized License Key! Orders cannot be submitted. Please purchase a valid license key from %s', 'wc-smart-checkout-builder' ),
				'developerzahir.com'
			);
			wc_add_notice( $msg, 'error' );
		}
	}

	/**
	 * Prevent checkout validation if license is inactive or unauthorized.
	 *
	 * @param array     $data
	 * @param \WP_Error $errors
	 */
	public static function validate_license_after_checkout_validation( $data, $errors ) {
		if ( ! self::is_widget_checkout_context() ) {
			return;
		}

		if ( class_exists( '\WCSC\License_Manager' ) && ! \WCSC\License_Manager::is_active() ) {
			$msg = sprintf(
				/* translators: %s: website link */
				__( 'Unauthorized License Key! Orders cannot be submitted. Please purchase a valid license key from %s', 'wc-smart-checkout-builder' ),
				'developerzahir.com'
			);
			$errors->add( 'unauthorized_license', $msg );
		}
	}

}
