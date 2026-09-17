<?php
namespace WCSC;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

/**
 * Class ThankYou_Widget
 *
 * Elementor Widget for displaying custom Thank You / Order Details page.
 */
class ThankYou_Widget extends Widget_Base {

	public function get_name() {
		return 'wcsc_thank_you';
	}

	public function get_title() {
		return esc_html__( 'Thank You / Order Details', 'wc-smart-checkout-builder' );
	}

	public function get_icon() {
		return 'eicon-woocommerce';
	}

	public function get_categories() {
		return array( 'wcsc-category', 'woocommerce-elements' );
	}

	public function get_keywords() {
		return array( 'woocommerce', 'thank you', 'order', 'details', 'checkout' );
	}

	protected function register_controls() {
		
		// --- Style Section: General ---
		$this->start_controls_section(
			'section_style_general',
			array(
				'label' => esc_html__( 'General styling', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'background_color',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-thank-you-wrapper' => 'background-color: {{VALUE}};',
				),
			)
		);
		
		$this->add_responsive_control(
			'padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-thank-you-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
		
		// --- Style Section: Typography ---
		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => esc_html__( 'Typography & Colors', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typography',
				'label'    => esc_html__( 'Heading Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcsc-thank-you-wrapper h2, {{WRAPPER}} .wcsc-thank-you-wrapper h3',
			)
		);

		$this->add_control(
			'heading_color',
			array(
				'label'     => esc_html__( 'Heading Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-thank-you-wrapper h2, {{WRAPPER}} .wcsc-thank-you-wrapper h3' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typography',
				'label'    => esc_html__( 'Text Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcsc-thank-you-wrapper p, {{WRAPPER}} .wcsc-thank-you-wrapper li, {{WRAPPER}} .wcsc-thank-you-wrapper td',
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-thank-you-wrapper p, {{WRAPPER}} .wcsc-thank-you-wrapper li, {{WRAPPER}} .wcsc-thank-you-wrapper td' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		// Detect if we're in the Elementor editor
		$is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

		$order = false;
		
		if ( $order_id && $order_key ) {
			$order = wc_get_order( $order_id );
			if ( $order && $order->get_order_key() !== $order_key ) {
				$order = false; // key mismatch
			}
		}
		
		echo '<div class="wcsc-thank-you-wrapper" style="max-width:800px; margin:0 auto; font-family:inherit;">';
		
		if ( ! $order && $is_editor ) {
			$this->render_dummy_order();
		} elseif ( $order ) {
			$this->render_real_order( $order );
		} else {
			echo '<p>' . esc_html__( 'No order information available.', 'wc-smart-checkout-builder' ) . '</p>';
		}
		
		echo '</div>';
	}
	
	private function render_dummy_order() {
		?>
		<div class="wcsc-order-success-msg" style="background:#e6fffa; color:#234e52; padding:15px; margin-bottom:20px; border-radius:4px;">
			<p style="margin:0;"><strong><?php esc_html_e( 'Thank you. Your order has been received.', 'wc-smart-checkout-builder' ); ?></strong></p>
		</div>
		
		<ul class="wcsc-order-overview" style="list-style:none; padding:0; display:flex; flex-wrap:wrap; gap:20px; margin-bottom:30px;">
			<li>
				<span><?php esc_html_e( 'Order number:', 'wc-smart-checkout-builder' ); ?></span><br/>
				<strong>#12345</strong>
			</li>
			<li>
				<span><?php esc_html_e( 'Date:', 'wc-smart-checkout-builder' ); ?></span><br/>
				<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ) ) ); ?></strong>
			</li>
			<li>
				<span><?php esc_html_e( 'Total:', 'wc-smart-checkout-builder' ); ?></span><br/>
				<strong><?php echo wp_kses_post( wc_price( 1350 ) ); ?></strong>
			</li>
			<li>
				<span><?php esc_html_e( 'Payment method:', 'wc-smart-checkout-builder' ); ?></span><br/>
				<strong><?php esc_html_e( 'Cash on delivery', 'wc-smart-checkout-builder' ); ?></strong>
			</li>
		</ul>

		<h2 style="margin-bottom:15px;"><?php esc_html_e( 'Order details', 'wc-smart-checkout-builder' ); ?></h2>
		<table class="wcsc-order-details-table" style="width:100%; border-collapse:collapse; margin-bottom:30px; text-align:left;">
			<thead>
				<tr style="border-bottom:2px solid #e2e8f0;">
					<th style="padding:10px 0;"><?php esc_html_e( 'Product', 'wc-smart-checkout-builder' ); ?></th>
					<th style="padding:10px 0; text-align:right;"><?php esc_html_e( 'Total', 'wc-smart-checkout-builder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr style="border-bottom:1px solid #e2e8f0;">
					<td style="padding:10px 0;">
						Sample Product Name <strong class="product-quantity">&times;&nbsp;1</strong>
						<br/><small style="color:#718096;"><?php esc_html_e( 'Variation: Large / Black', 'wc-smart-checkout-builder' ); ?></small>
					</td>
					<td style="padding:10px 0; text-align:right;"><?php echo wp_kses_post( wc_price( 1250 ) ); ?></td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<th style="padding:10px 0; text-align:left;"><?php esc_html_e( 'Subtotal:', 'wc-smart-checkout-builder' ); ?></th>
					<td style="padding:10px 0; text-align:right;"><?php echo wp_kses_post( wc_price( 1250 ) ); ?></td>
				</tr>
				<tr>
					<th style="padding:10px 0; text-align:left;"><?php esc_html_e( 'Shipping:', 'wc-smart-checkout-builder' ); ?></th>
					<td style="padding:10px 0; text-align:right;"><?php echo wp_kses_post( wc_price( 100 ) ); ?></td>
				</tr>
				<tr>
					<th style="padding:10px 0; text-align:left;"><?php esc_html_e( 'Total:', 'wc-smart-checkout-builder' ); ?></th>
					<td style="padding:10px 0; text-align:right;"><strong><?php echo wp_kses_post( wc_price( 1350 ) ); ?></strong></td>
				</tr>
			</tfoot>
		</table>

		<div class="wcsc-customer-details" style="display:flex; flex-wrap:wrap; gap:30px;">
			<div style="flex:1; min-width:250px;">
				<h3 style="margin-bottom:10px;"><?php esc_html_e( 'Billing address', 'wc-smart-checkout-builder' ); ?></h3>
				<address style="font-style:normal; line-height:1.6; color:#4a5568;">
					John Doe<br/>
					123 Sample Street<br/>
					Dhaka 1212<br/>
					Bangladesh<br/>
					<a href="tel:01700000000">01700000000</a><br/>
					<a href="mailto:john@example.com">john@example.com</a>
				</address>
			</div>
		</div>
		<?php
	}

	private function render_real_order( $order ) {
		$show_customer_details = $order->has_billing_address() || $order->has_shipping_address();
		?>
		<div class="wcsc-order-success-msg" style="background:#e6fffa; color:#234e52; padding:15px; margin-bottom:20px; border-radius:4px;">
			<p style="margin:0;"><strong><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'wc-smart-checkout-builder' ), $order ); ?></strong></p>
		</div>
		
		<ul class="wcsc-order-overview" style="list-style:none; padding:0; display:flex; flex-wrap:wrap; gap:20px; margin-bottom:30px;">
			<li class="woocommerce-order-overview__order order">
				<span><?php esc_html_e( 'Order number:', 'wc-smart-checkout-builder' ); ?></span><br/>
				<strong><?php echo esc_html( $order->get_order_number() ); ?></strong>
			</li>
			<li class="woocommerce-order-overview__date date">
				<span><?php esc_html_e( 'Date:', 'wc-smart-checkout-builder' ); ?></span><br/>
				<strong><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></strong>
			</li>
			<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
				<li class="woocommerce-order-overview__email email">
					<span><?php esc_html_e( 'Email:', 'wc-smart-checkout-builder' ); ?></span><br/>
					<strong><?php echo esc_html( $order->get_billing_email() ); ?></strong>
				</li>
			<?php endif; ?>
			<li class="woocommerce-order-overview__total total">
				<span><?php esc_html_e( 'Total:', 'wc-smart-checkout-builder' ); ?></span><br/>
				<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
			</li>
			<?php if ( $order->get_payment_method_title() ) : ?>
				<li class="woocommerce-order-overview__payment-method method">
					<span><?php esc_html_e( 'Payment method:', 'wc-smart-checkout-builder' ); ?></span><br/>
					<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
				</li>
			<?php endif; ?>
		</ul>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
		<?php
	}
}
