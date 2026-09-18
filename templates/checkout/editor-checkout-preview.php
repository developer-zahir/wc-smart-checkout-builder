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

// Custom Text Labels (trimmed to 7 keys)
$billing_heading_text      = ! empty( $settings['billing_heading_text'] ) ? esc_html( $settings['billing_heading_text'] ) : esc_html__( 'Customer information', 'wc-smart-checkout-builder' );
$order_review_heading_text = ! empty( $settings['order_review_heading_text'] ) ? esc_html( $settings['order_review_heading_text'] ) : esc_html__( 'Your Order', 'wc-smart-checkout-builder' );
$payment_heading_text      = ! empty( $settings['payment_heading_text'] ) ? esc_html( $settings['payment_heading_text'] ) : esc_html__( 'Payment', 'wc-smart-checkout-builder' );
$product_label_text        = ! empty( $settings['product_label_text'] ) ? esc_html( $settings['product_label_text'] ) : esc_html__( 'Product', 'wc-smart-checkout-builder' );
$subtotal_label_text       = ! empty( $settings['subtotal_label_text'] ) ? esc_html( $settings['subtotal_label_text'] ) : esc_html__( 'Subtotal', 'wc-smart-checkout-builder' );
$shipping_label_text       = ! empty( $settings['shipping_label_text'] ) ? esc_html( $settings['shipping_label_text'] ) : esc_html__( 'Shipping', 'wc-smart-checkout-builder' );
$total_label_text          = ! empty( $settings['total_label_text'] ) ? esc_html( $settings['total_label_text'] ) : esc_html__( 'Total', 'wc-smart-checkout-builder' );

// Section visibility toggles
$show_order_review = ! isset( $settings['show_checkout_order_review'] ) || 'yes' === $settings['show_checkout_order_review'];
$show_shipping     = ! isset( $settings['show_checkout_shipping'] ) || 'yes' === $settings['show_checkout_shipping'];
$show_payment      = ! isset( $settings['show_checkout_payment'] ) || 'yes' === $settings['show_checkout_payment'];
$checkout_layout   = ! empty( $settings['checkout_layout'] ) ? $settings['checkout_layout'] : '2_columns';
$order_button_pos  = ! empty( $settings['order_button_position'] ) ? $settings['order_button_position'] : 'right_column';
if ( 'under_shipping' === $order_button_pos ) {
	$order_button_pos = 'left_column';
} elseif ( 'under_payment' === $order_button_pos || 'under_order_review' === $order_button_pos ) {
	$order_button_pos = 'right_column';
}
if ( '1_column' === $checkout_layout ) {
	$order_button_pos = 'full_width';
}

// Dynamic WooCommerce shipping zones / methods for preview
$preview_shipping_methods = array();
if ( function_exists( 'WC' ) ) {
	$packages = ( WC()->shipping() && method_exists( WC()->shipping(), 'get_packages' ) ) ? WC()->shipping()->get_packages() : array();
	if ( ! empty( $packages ) ) {
		foreach ( $packages as $pkg ) {
			if ( ! empty( $pkg['rates'] ) ) {
				foreach ( $pkg['rates'] as $rate ) {
					$preview_shipping_methods[] = array(
						'id'    => $rate->id,
						'label' => $rate->label,
						'cost'  => (float) $rate->cost + (float) $rate->get_shipping_tax(),
					);
				}
			}
		}
	}

	if ( empty( $preview_shipping_methods ) && class_exists( '\WC_Shipping_Zones' ) ) {
		$zones   = \WC_Shipping_Zones::get_zones();
		$default_zone = new \WC_Shipping_Zone( 0 );
		$zones[] = array( 'shipping_methods' => $default_zone->get_shipping_methods( true ) );

		foreach ( $zones as $zone ) {
			$methods = isset( $zone['shipping_methods'] ) ? $zone['shipping_methods'] : array();
			foreach ( $methods as $method ) {
				if ( method_exists( $method, 'is_enabled' ) && $method->is_enabled() ) {
					$cost = isset( $method->cost ) ? (float) $method->cost : ( isset( $method->settings['cost'] ) ? (float) $method->settings['cost'] : 0 );
					$preview_shipping_methods[] = array(
						'id'    => $method->id . '_' . $method->instance_id,
						'label' => $method->get_title(),
						'cost'  => $cost,
					);
				}
			}
		}
	}
}

if ( empty( $preview_shipping_methods ) ) {
	$preview_shipping_methods = array(
		array(
			'id'    => 'flat_rate_in',
			'label' => __( 'Inside Dhaka', 'wc-smart-checkout-builder' ),
			'cost'  => 60,
		),
		array(
			'id'    => 'flat_rate_out',
			'label' => __( 'Outside Dhaka', 'wc-smart-checkout-builder' ),
			'cost'  => 120,
		),
	);
}

$default_shipping_cost = isset( $preview_shipping_methods[0]['cost'] ) ? (float) $preview_shipping_methods[0]['cost'] : 0;
$product_price_num     = ( $product && is_numeric( $product->get_price() ) ) ? (float) $product->get_price() : 50;
$preview_total         = $product_price_num + $default_shipping_cost;

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

$wrapper_classes = array(
	'wcsc-editor-checkout-preview',
	'wcas-checkout-wrapper',
	'woocommerce',
	'wcas-order-btn-pos-' . sanitize_html_class( str_replace( '_', '-', $order_button_pos ) ),
);
if ( '1_column' === $checkout_layout ) {
	$wrapper_classes[] = 'wcsc-layout-1-col';
	$wrapper_classes[] = 'wcas-layout-one-column';
} else {
	$wrapper_classes[] = 'wcsc-layout-2-col';
	$wrapper_classes[] = 'wcas-layout-two-column';
}
?>

<form name="checkout" class="checkout woocommerce-checkout wcsc-checkout-form" onsubmit="return false;">
	<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" <?php echo \WCSC\Checkout_Handler::render_data_attrs( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $show_order_review || $show_shipping || $show_payment ) : ?>

			<?php
			// Render Order Button markup helper
			$render_order_button_html = function () use ( $anim_class, $order_button_text, $icon_align, $icon_html ) {
				?>
				<div class="wcas-block wcas-block-order-button">
					<div class="form-row place-order">
						<button type="button" class="button alt wp-element-button wcsc-order-now-btn <?php echo esc_attr( $anim_class ); ?>" id="place_order" value="<?php echo esc_attr( wp_strip_all_tags( $order_button_text ) ); ?>" data-value="<?php echo esc_attr( wp_strip_all_tags( $order_button_text ) ); ?>">
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
				<?php
			};
			?>

			<div class="wcas-checkout-column wcas-checkout-column-left">
				<?php /* --- Checkout Form Block (100% full width, 1 field per row) --- */ ?>
				<div class="wcas-block wcas-block-checkout-form">
					<h3 class="wcsc-section-title wcas-block-title"><?php echo esc_html( $billing_heading_text ); ?></h3>
					<div class="col2-set" id="customer_details">
						<div class="col-1">
							<div class="woocommerce-billing-fields">
								<div class="woocommerce-billing-fields__field-wrapper">
									<?php
									// Retrieve checkout fields respecting custom snippets and 3rd party plugins
									$checkout_obj = ( function_exists( 'WC' ) && WC()->checkout() ) ? WC()->checkout() : null;
									if ( ! $checkout_obj && class_exists( '\WC_Checkout' ) ) {
										$checkout_obj = new \WC_Checkout();
									}

									$preview_billing_fields = array();
									if ( $checkout_obj ) {
										$preview_billing_fields = $checkout_obj->get_checkout_fields( 'billing' );
									} elseif ( function_exists( 'WC' ) && isset( WC()->countries ) ) {
										$preview_billing_fields = WC()->countries->get_address_fields( '', 'billing_' );
										$preview_billing_fields = apply_filters( 'woocommerce_billing_fields', $preview_billing_fields );
										$all_filtered           = apply_filters( 'woocommerce_checkout_fields', array( 'billing' => $preview_billing_fields ) );
										$preview_billing_fields = isset( $all_filtered['billing'] ) ? $all_filtered['billing'] : $preview_billing_fields;
									}

									// Strictly ensure woocommerce_checkout_fields filter hook is applied
									if ( ! empty( $preview_billing_fields ) ) {
										$all_filtered = apply_filters( 'woocommerce_checkout_fields', array( 'billing' => $preview_billing_fields ) );
										if ( isset( $all_filtered['billing'] ) && is_array( $all_filtered['billing'] ) ) {
											$preview_billing_fields = $all_filtered['billing'];
										}
									}

									// Fallback if WooCommerce returned empty fields array
									if ( empty( $preview_billing_fields ) ) {
										$preview_billing_fields = array(
											'billing_first_name' => array(
												'type'        => 'text',
												'label'       => __( 'Full Name', 'wc-smart-checkout-builder' ),
												'placeholder' => __( 'John Doe', 'wc-smart-checkout-builder' ),
												'required'    => true,
												'class'       => array( 'form-row-wide' ),
											),
											'billing_phone'      => array(
												'type'        => 'tel',
												'label'       => __( 'Phone Number', 'wc-smart-checkout-builder' ),
												'placeholder' => __( '017XXXXXXXX', 'wc-smart-checkout-builder' ),
												'required'    => true,
												'class'       => array( 'form-row-wide', 'validate-phone' ),
											),
											'billing_address_1'  => array(
												'type'        => 'text',
												'label'       => __( 'Street Address', 'wc-smart-checkout-builder' ),
												'placeholder' => __( 'House, Road, Area details', 'wc-smart-checkout-builder' ),
												'required'    => true,
												'class'       => array( 'form-row-wide', 'address-field' ),
											),
											'billing_city'       => array(
												'type'        => 'text',
												'label'       => __( 'Town / City', 'wc-smart-checkout-builder' ),
												'placeholder' => __( 'Dhaka / Your City', 'wc-smart-checkout-builder' ),
												'required'    => true,
												'class'       => array( 'form-row-wide', 'address-field' ),
											),
										);
									}

									if ( function_exists( 'woocommerce_form_field' ) ) {
										foreach ( $preview_billing_fields as $key => $field ) {
											if ( empty( $field ) || ! is_array( $field ) ) {
												continue;
											}
											woocommerce_form_field( $key, $field, '' );
										}

										// Render order notes if active in order fields
										$preview_order_fields = $checkout_obj ? $checkout_obj->get_checkout_fields( 'order' ) : array();
										if ( empty( $preview_order_fields ) ) {
											$all_filtered         = apply_filters( 'woocommerce_checkout_fields', array() );
											$preview_order_fields = isset( $all_filtered['order'] ) ? $all_filtered['order'] : array();
										}
										if ( ! empty( $preview_order_fields ) && is_array( $preview_order_fields ) ) {
											foreach ( $preview_order_fields as $key => $field ) {
												if ( empty( $field ) || ! is_array( $field ) ) {
													continue;
												}
												woocommerce_form_field( $key, $field, '' );
											}
										}
									} else {
										// Static HTML fallback if woocommerce_form_field unavailable
										foreach ( $preview_billing_fields as $key => $field ) {
											if ( empty( $field ) || ! is_array( $field ) ) {
												continue;
											}
											$label = isset( $field['label'] ) ? $field['label'] : $key;
											$req   = ! empty( $field['required'] ) ? ' <abbr class="required" title="required">*</abbr>' : '';
											$ph    = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
											?>
											<p class="form-row form-row-wide" id="<?php echo esc_attr( $key ); ?>_field">
												<label for="editor_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ) . $req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
												<span class="woocommerce-input-wrapper">
													<input type="text" class="input-text" name="<?php echo esc_attr( $key ); ?>" id="editor_<?php echo esc_attr( $key ); ?>" placeholder="<?php echo esc_attr( $ph ); ?>" value="" readonly />
												</span>
											</p>
											<?php
										}
									}
									?>
								</div>
							</div>
						</div>
						<div class="col-2" style="display:none;">
							<div class="woocommerce-shipping-fields">
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
					<?php /* --- Dedicated Shipping Selection Block --- */ ?>
					<div class="wcas-block wcas-block-shipping">
						<h3 class="wcsc-section-title wcas-block-title wcsc-shipping-heading"><?php echo esc_html( $shipping_label_text ); ?></h3>
						<div class="wcas-shipping-methods-wrapper">
							<ul id="shipping_method" class="woocommerce-shipping-methods">
								<?php foreach ( $preview_shipping_methods as $idx => $method ) : ?>
									<li class="wcsc-shipping-card<?php echo 0 === $idx ? ' is-active' : ''; ?>">
										<input type="radio" 
											name="shipping_method[0]" 
											data-index="0" 
											id="shipping_method_0_<?php echo esc_attr( sanitize_title( $method['id'] ) ); ?>" 
											value="<?php echo esc_attr( $method['id'] ); ?>" 
											class="shipping_method" 
											<?php checked( 0, $idx ); ?> />
										<label for="shipping_method_0_<?php echo esc_attr( sanitize_title( $method['id'] ) ); ?>">
											<?php echo esc_html( $method['label'] ); ?>: <span class="woocommerce-Price-amount amount"><?php echo wc_price( $method['cost'] ); ?></span>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( 'left_column' === $order_button_pos ) : ?>
					<?php $render_order_button_html(); ?>
				<?php endif; ?>
			</div>

			<div class="wcas-checkout-column wcas-checkout-column-right">
				<?php if ( $show_order_review ) : ?>
					<?php /* --- Order Review Block --- */ ?>
					<div class="wcas-block wcas-block-order-review">
						<h3 id="order_review_heading" class="wcsc-section-title wcas-block-title"><?php echo esc_html( $order_review_heading_text ); ?></h3>
						<div id="order_review" class="woocommerce-checkout-review-order">
							<table class="shop_table woocommerce-checkout-review-order-table">
								<thead>
									<tr>
										<th class="product-name"><?php echo esc_html( $product_label_text ); ?></th>
										<th class="product-total"><?php echo esc_html( $subtotal_label_text ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									$show_cart_item_image = ! isset( $settings['show_cart_item_image'] ) || 'yes' === $settings['show_cart_item_image'];
									$preview_img_url      = '';
									if ( $product ) {
										$image_id = 0;
										if ( $product->is_type( 'variable' ) ) {
											$default_attributes   = method_exists( $product, 'get_default_attributes' ) ? $product->get_default_attributes() : array();
											$available_variations = $product->get_available_variations();
											$selected_var         = null;
											if ( ! empty( $default_attributes ) && ! empty( $available_variations ) ) {
												foreach ( $available_variations as $var_data ) {
													$match = true;
													foreach ( $default_attributes as $attr_k => $attr_v ) {
														$vkey = 'attribute_' . $attr_k;
														if ( isset( $var_data['attributes'][ $vkey ] ) && '' !== $var_data['attributes'][ $vkey ] && $var_data['attributes'][ $vkey ] !== $attr_v ) {
															$match = false;
															break;
														}
													}
													if ( $match ) {
														$selected_var = $var_data;
														break;
													}
												}
											}
											if ( ! $selected_var && ! empty( $available_variations ) ) {
												$selected_var = $available_variations[0];
											}
											if ( $selected_var && ! empty( $selected_var['image_id'] ) ) {
												$image_id = $selected_var['image_id'];
											}
										}
										if ( ! $image_id ) {
											$image_id = $product->get_image_id();
										}
										if ( $image_id ) {
											$preview_img_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
										}
									}
									?>
									<tr class="cart_item wcas-order-review-product">
										<td class="product-name">
											<?php if ( $show_cart_item_image && $preview_img_url ) : ?>
												<div class="wcsc-cart-item-with-img">
													<img src="<?php echo esc_url( $preview_img_url ); ?>" class="wcsc-cart-item-image" alt="<?php echo esc_attr( $product_name ); ?>" />
													<span class="wcsc-cart-item-name-text">
														<span class="wcsc-preview-item-title"><?php echo esc_html( $product_name ); ?></span>
														<strong class="product-quantity">&times;&nbsp;1</strong>
													</span>
												</div>
											<?php else : ?>
												<span class="wcsc-cart-item-name-text">
													<span class="wcsc-preview-item-title"><?php echo esc_html( $product_name ); ?></span>
													<strong class="product-quantity">&times;&nbsp;1</strong>
												</span>
											<?php endif; ?>
										</td>
										<td class="product-total">
											<span class="wcsc-preview-item-price"><?php echo wp_kses_post( $price_html ); ?></span>
										</td>
									</tr>
								</tbody>
								<tfoot>
									<tr class="cart-subtotal wcas-order-review-subtotal">
										<th><?php echo esc_html( $subtotal_label_text ); ?></th>
										<td><span class="woocommerce-Price-amount amount wcsc-preview-subtotal"><?php echo wp_kses_post( $price_html ); ?></span></td>
									</tr>
									<tr class="woocommerce-shipping-totals shipping wcas-order-review-shipping">
										<th><?php echo esc_html( $shipping_label_text ); ?></th>
										<td data-title="<?php echo esc_attr( $shipping_label_text ); ?>">
											<span class="woocommerce-Price-amount amount wcsc-preview-shipping"><?php echo wp_kses_post( wc_price( $default_shipping_cost ) ); ?></span>
										</td>
									</tr>
									<tr class="order-total wcas-order-review-total">
										<th><?php echo esc_html( $total_label_text ); ?></th>
										<td><strong><span class="woocommerce-Price-amount amount wcsc-preview-total"><?php echo wp_kses_post( wc_price( $preview_total ) ); ?></span></strong></td>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $show_payment ) : ?>
					<?php /* --- Payment Block --- */ ?>
					<div class="wcas-block wcas-block-payment">
						<h3 class="wcsc-section-title wcas-block-title wcsc-payment-heading"><?php echo esc_html( $payment_heading_text ); ?></h3>
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

				<?php if ( 'right_column' === $order_button_pos ) : ?>
					<?php $render_order_button_html(); ?>
				<?php endif; ?>
			</div>

			<?php if ( 'full_width' === $order_button_pos ) : ?>
				<div class="wcas-checkout-row-full">
					<?php $render_order_button_html(); ?>
				</div>
			<?php endif; ?>

		<?php endif; ?>
	</div>

	<?php
	$sticky_enabled = true;
	if ( isset( $settings['style_enable_mobile_sticky_button'] ) ) {
		$sticky_enabled = ( 'yes' === $settings['style_enable_mobile_sticky_button'] );
	} elseif ( isset( $settings['enable_mobile_sticky_button'] ) ) {
		$sticky_enabled = ( 'yes' === $settings['enable_mobile_sticky_button'] );
	}

	if ( $sticky_enabled ) :
		$sticky_text = ! empty( $settings['style_mobile_sticky_button_text'] ) ? $settings['style_mobile_sticky_button_text'] : ( ! empty( $settings['mobile_sticky_button_text'] ) ? $settings['mobile_sticky_button_text'] : __( 'অর্ডার করুন', 'wc-smart-checkout-builder' ) );
		?>
		<div class="wcsc-mobile-sticky-bar" id="wcsc-mobile-sticky-bar">
			<button type="button" class="wcsc-mobile-sticky-btn">
				<span class="wcsc-sticky-shine"></span>
				<span class="wcsc-sticky-text"><?php echo esc_html( $sticky_text ); ?></span>
			</button>
		</div>
	<?php endif; ?>
</form>
