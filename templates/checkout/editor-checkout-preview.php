<?php
/**
 * Editor Checkout Preview Template
 *
 * Renders a high-fidelity visual mock of the WooCommerce checkout form inside the
 * Elementor live editor — using the plugin-owned block architecture — so that
 * styles, typography, and layout can be adjusted in real time without triggering
 * live checkout requests, infinite loading spinners, or accidental orders.
 *
 * The structure mirrors the frontend plugin-owned blocks:
 *   .wcas-checkout-wrapper
 *     ├── .wcas-block.wcas-block-checkout-form  (billing + shipping fields)
 *     ├── .wcas-block.wcas-block-shipping      (shipping method selection)
 *     ├── .wcas-block.wcas-block-order-review  (ordered products / totals)
 *     ├── .wcas-block.wcas-block-payment       (payment gateway methods)
 *     └── .wcas-block.wcas-block-order-button  (place order button)
 *
 * @var array       $settings
 * @var \WC_Product $product
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$order_button_text = ! empty( $settings['order_button_text'] ) ? esc_html( $settings['order_button_text'] ) : esc_html__( 'Order Now', 'wc-smart-checkout-builder' );
$product_name      = $product ? $product->get_name() : esc_html__( 'Sample Product', 'wc-smart-checkout-builder' );
$price_html        = $product ? $product->get_price_html() : wc_price( 50 );
$raw_price_text    = $product ? wp_strip_all_tags( wc_price( $product->get_price() ) ) : '$50.00';

// Custom Text Labels (trimmed to 6 keys — req 40)
$billing_heading_text      = ! empty( $settings['billing_heading_text'] ) ? esc_html( $settings['billing_heading_text'] ) : esc_html__( 'Billing & Shipping', 'wc-smart-checkout-builder' );
$order_review_heading_text = ! empty( $settings['order_review_heading_text'] ) ? esc_html( $settings['order_review_heading_text'] ) : esc_html__( 'Your Order', 'wc-smart-checkout-builder' );
$product_label_text        = ! empty( $settings['product_label_text'] ) ? esc_html( $settings['product_label_text'] ) : esc_html__( 'Product', 'wc-smart-checkout-builder' );
$subtotal_label_text       = ! empty( $settings['subtotal_label_text'] ) ? esc_html( $settings['subtotal_label_text'] ) : esc_html__( 'Subtotal', 'wc-smart-checkout-builder' );
$shipping_label_text       = ! empty( $settings['shipping_label_text'] ) ? esc_html( $settings['shipping_label_text'] ) : esc_html__( 'Shipping', 'wc-smart-checkout-builder' );
$total_label_text          = ! empty( $settings['total_label_text'] ) ? esc_html( $settings['total_label_text'] ) : esc_html__( 'Total', 'wc-smart-checkout-builder' );

// Section visibility toggles
$show_order_review = ! isset( $settings['show_checkout_order_review'] ) || 'yes' === $settings['show_checkout_order_review'];
$show_shipping     = ! isset( $settings['show_checkout_shipping'] ) || 'yes' === $settings['show_checkout_shipping'];
$show_payment      = ! isset( $settings['show_checkout_payment'] ) || 'yes' === $settings['show_checkout_payment'];
$show_order_button = ! isset( $settings['show_checkout_order_button'] ) || 'yes' === $settings['show_checkout_order_button'];
$checkout_layout   = ! empty( $settings['checkout_layout'] ) ? $settings['checkout_layout'] : '2_columns';

// Button Icon & Price
$animation   = ! empty( $settings['order_button_animation'] ) ? sanitize_html_class( $settings['order_button_animation'] ) : 'border_run';
$anim_class  = 'wcsc-anim-' . str_replace( '_', '-', $animation );

// Replace {total_price} in the button text
if ( strpos( $order_button_text, '{total_price}' ) !== false ) {
	$price_span = '<span class="wcsc-btn-price-wrap"><span class="wcsc-btn-price">' . esc_html( $raw_price_text ) . '</span></span>';
	$order_button_text = str_replace( '{total_price}', $price_span, $order_button_text );
}

$icon_align  = ! empty( $settings['order_button_icon_align'] ) ? $settings['order_button_icon_align'] : 'left';

$icon_html = '';
if ( ! empty( $settings['order_button_icon'] ) && class_exists( '\Elementor\Icons_Manager' ) ) {
	ob_start();
	\Elementor\Icons_Manager::render_icon( $settings['order_button_icon'], array( 'aria-hidden' => 'true' ) );
	$rendered_icon = ob_get_clean();
	if ( $rendered_icon ) {
		$icon_html = '<span class="wcsc-btn-icon">' . $rendered_icon . '</span>';
	}
}

$wrapper_classes = array( 'wcsc-editor-checkout-preview', 'wcas-checkout-wrapper', 'woocommerce' );
if ( '1_column' === $checkout_layout ) {
	$wrapper_classes[] = 'wcsc-layout-1-col';
	$wrapper_classes[] = 'wcas-layout-one-column';
} else {
	$wrapper_classes[] = 'wcsc-layout-2-col';
	$wrapper_classes[] = 'wcas-layout-two-column';
}
?>

<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
	<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout" style="display: none;"></div>

	<form name="checkout" class="checkout woocommerce-checkout wcsc-checkout-form" onsubmit="return false;">
		<?php if ( $show_order_review || $show_shipping || $show_payment || $show_order_button ) : ?>

			<?php /* --- Checkout Form Block (billing + shipping fields) --- */ ?>
			<div class="wcas-block wcas-block-checkout-form">
				<div class="col2-set" id="customer_details">
					<div class="col-1">
						<div class="woocommerce-billing-fields">
							<h3 class="wcsc-section-title"><?php echo esc_html( $billing_heading_text ); ?></h3>

							<div class="woocommerce-billing-fields__field-wrapper">
								<p class="form-row form-row-first validate-required" id="billing_first_name_field">
									<label for="editor_billing_first_name"><?php esc_html_e( 'Full Name', 'wc-smart-checkout-builder' ); ?> <abbr class="required" title="required">*</abbr></label>
									<span class="woocommerce-input-wrapper">
										<input type="text" class="input-text" name="billing_first_name" id="editor_billing_first_name" placeholder="<?php esc_attr_e( 'John Doe', 'wc-smart-checkout-builder' ); ?>" value="" readonly />
									</span>
								</p>

								<p class="form-row form-row-last validate-required validate-phone" id="billing_phone_field">
									<label for="editor_billing_phone"><?php esc_html_e( 'Phone Number', 'wc-smart-checkout-builder' ); ?> <abbr class="required" title="required">*</abbr></label>
									<span class="woocommerce-input-wrapper">
										<input type="tel" class="input-text" name="billing_phone" id="editor_billing_phone" placeholder="<?php esc_attr_e( '017XXXXXXXX', 'wc-smart-checkout-builder' ); ?>" value="" readonly />
									</span>
								</p>

								<p class="form-row form-row-wide address-field validate-required" id="billing_address_1_field">
									<label for="editor_billing_address_1"><?php esc_html_e( 'Street Address', 'wc-smart-checkout-builder' ); ?> <abbr class="required" title="required">*</abbr></label>
									<span class="woocommerce-input-wrapper">
										<input type="text" class="input-text" name="billing_address_1" id="editor_billing_address_1" placeholder="<?php esc_attr_e( 'House, Road, Area details', 'wc-smart-checkout-builder' ); ?>" value="" readonly />
									</span>
								</p>

								<p class="form-row form-row-wide address-field validate-required" id="billing_city_field">
									<label for="editor_billing_city"><?php esc_html_e( 'Town / City', 'wc-smart-checkout-builder' ); ?> <abbr class="required" title="required">*</abbr></label>
									<span class="woocommerce-input-wrapper">
										<input type="text" class="input-text" name="billing_city" id="editor_billing_city" placeholder="<?php esc_attr_e( 'Dhaka / Your City', 'wc-smart-checkout-builder' ); ?>" value="" readonly />
									</span>
								</p>

								<p class="form-row form-row-wide notes" id="order_comments_field">
									<label for="editor_order_comments"><?php esc_html_e( 'Order Notes (optional)', 'wc-smart-checkout-builder' ); ?></label>
									<span class="woocommerce-input-wrapper">
										<textarea name="order_comments" class="input-text" id="editor_order_comments" placeholder="<?php esc_attr_e( 'Special delivery notes...', 'wc-smart-checkout-builder' ); ?>" rows="2" readonly></textarea>
									</span>
								</p>
							</div>
						</div>
					</div>
					<div class="col-2">
						<div class="woocommerce-shipping-fields">
							<h3 class="wcsc-section-title"><?php echo esc_html( $billing_heading_text ); ?></h3>
							<div class="woocommerce-shipping-fields__field-wrapper">
								<p class="form-row form-row-wide address-field validate-required" id="shipping_address_1_field">
									<label for="editor_shipping_address_1"><?php esc_html_e( 'Street Address', 'wc-smart-checkout-builder' ); ?> <abbr class="required" title="required">*</abbr></label>
									<span class="woocommerce-input-wrapper">
										<input type="text" class="input-text" name="shipping_address_1" id="editor_shipping_address_1" placeholder="<?php esc_attr_e( 'House, Road, Area details', 'wc-smart-checkout-builder' ); ?>" value="" readonly />
									</span>
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php if ( $show_shipping ) : ?>
				<?php /* --- Shipping Block --- */ ?>
				<div class="wcas-block wcas-block-shipping">
					<h3 class="wcsc-section-title wcsc-shipping-heading"><?php echo esc_html( $shipping_label_text ); ?></h3>
					<ul id="shipping_method" class="woocommerce-shipping-methods">
						<li class="wcsc-shipping-card is-active">
							<label>
								<input type="radio" name="shipping_method[0]" data-index="0" id="shipping_method_0_flat_rate" value="flat_rate" class="shipping_method" checked="checked" />
								<?php esc_html_e( 'Standard Delivery: ', 'wc-smart-checkout-builder' ); ?><span class="woocommerce-Price-amount amount"><?php echo wc_price( 0 ); ?></span>
							</label>
						</li>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $show_order_review ) : ?>
				<?php /* --- Order Review Block --- */ ?>
				<div class="wcas-block wcas-block-order-review">
					<h3 id="order_review_heading" class="wcsc-section-title"><?php echo esc_html( $order_review_heading_text ); ?></h3>
					<div id="order_review" class="woocommerce-checkout-review-order">
						<table class="shop_table woocommerce-checkout-review-order-table">
							<thead>
								<tr>
									<th class="product-name"><?php echo esc_html( $product_label_text ); ?></th>
									<th class="product-total"><?php echo esc_html( $subtotal_label_text ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr class="cart_item">
									<td class="product-name">
										<span class="wcsc-preview-item-title"><?php echo esc_html( $product_name ); ?></span>
										<strong class="product-quantity">&times;&nbsp;1</strong>
									</td>
									<td class="product-total">
										<span class="wcsc-preview-item-price"><?php echo wp_kses_post( $price_html ); ?></span>
									</td>
								</tr>
							</tbody>
							<tfoot>
								<tr class="cart-subtotal">
									<th><?php echo esc_html( $subtotal_label_text ); ?></th>
									<td><span class="woocommerce-Price-amount amount wcsc-preview-subtotal"><?php echo wp_kses_post( $price_html ); ?></span></td>
								</tr>
								<tr class="woocommerce-shipping-totals shipping">
									<th><?php echo esc_html( $shipping_label_text ); ?></th>
									<td><span class="woocommerce-Price-amount amount wcsc-preview-shipping"><?php echo wp_kses_post( wc_price( 70 ) ); ?></span></td>
								</tr>
								<tr class="order-total">
									<th><?php echo esc_html( $total_label_text ); ?></th>
									<td><strong><span class="woocommerce-Price-amount amount wcsc-preview-total"><?php echo wp_kses_post( $price_html ); ?></span></strong></td>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $show_payment ) : ?>
				<?php /* --- Payment Block --- */ ?>
				<div class="wcas-block wcas-block-payment">
					<div id="payment" class="woocommerce-checkout-payment">
						<ul class="wc_payment_methods payment_methods methods">
							<li class="wc_payment_method payment_method_cod">
								<input id="payment_method_cod" type="radio" class="input-radio" name="payment_method" value="cod" checked="checked" />
								<label for="payment_method_cod"><?php esc_html_e( 'Cash on Delivery', 'wc-smart-checkout-builder' ); ?></label>
								<div class="payment_box payment_method_cod">
									<p><?php esc_html_e( 'Pay with cash upon delivery.', 'wc-smart-checkout-builder' ); ?></p>
								</div>
							</li>
						</ul>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $show_order_button ) : ?>
				<?php /* --- Order Button Block --- */ ?>
				<div class="wcas-block wcas-block-order-button">
					<div class="form-row place-order">
						<button type="button" class="button alt wp-element-button wcsc-order-now-btn <?php echo esc_attr( $anim_class ); ?>" id="place_order" value="<?php echo esc_attr( $order_button_text ); ?>" data-value="<?php echo esc_attr( $order_button_text ); ?>">
							<span class="wcsc-btn-beam wcsc-beam-top"></span>
							<span class="wcsc-btn-beam wcsc-beam-bottom"></span>
							<span class="wcsc-btn-content">
								<?php if ( 'left' === $icon_align && $icon_html ) : ?>
									<?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>
								<span class="wcsc-btn-text"><?php echo wp_kses_post( $order_button_text ); ?></span>
								<?php if ( 'right' === $icon_align && $icon_html ) : ?>
									<?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>
							</span>
						</button>
					</div>
				</div>
			<?php endif; ?>

		<?php endif; ?>
	</form>
</div>
