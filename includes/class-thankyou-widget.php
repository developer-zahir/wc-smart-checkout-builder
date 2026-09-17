<?php
namespace WCSC;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

class ThankYou_Widget extends Widget_Base {

	public function get_name() { return 'wcsc_thank_you'; }
	public function get_title() { return esc_html__( 'Thank You / Order Details', 'wc-smart-checkout-builder' ); }
	public function get_icon() { return 'eicon-woocommerce'; }
	public function get_categories() { return array( 'wcsc-category', 'woocommerce-elements' ); }
	public function get_keywords() { return array( 'woocommerce', 'thank you', 'order', 'details', 'checkout' ); }

	protected function register_controls() {
		$this->start_controls_section( 'section_content_general', [
			'label' => esc_html__( 'General / Success Message', 'wc-smart-checkout-builder' ),
		] );
		$this->add_control( 'show_success_msg', [
			'label' => esc_html__( 'Show Success Message', 'wc-smart-checkout-builder' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );
		$this->add_control( 'success_heading', [
			'label' => esc_html__( 'Heading Text', 'wc-smart-checkout-builder' ),
			'type' => Controls_Manager::TEXT,
			'default' => esc_html__( 'Thank you. Your order has been received.', 'wc-smart-checkout-builder' ),
			'condition' => [ 'show_success_msg' => 'yes' ],
		] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_content_order_info', [
			'label' => esc_html__( 'Order Information', 'wc-smart-checkout-builder' ),
		] );
		$this->add_control( 'show_order_number', [ 'label' => 'Order Number', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_order_date', [ 'label' => 'Order Date', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_order_status', [ 'label' => 'Order Status', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_payment_method', [ 'label' => 'Payment Method', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_content_addresses', [
			'label' => esc_html__( 'Customer / Addresses', 'wc-smart-checkout-builder' ),
		] );
		$this->add_control( 'show_customer_info', [ 'label' => 'Customer Details Section', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_billing_address', [ 'label' => 'Billing Address', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_shipping_address', [ 'label' => 'Shipping Address', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_content_items', [
			'label' => esc_html__( 'Product Items', 'wc-smart-checkout-builder' ),
		] );
		$this->add_control( 'show_product_image', [ 'label' => 'Product Image', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_product_name', [ 'label' => 'Product Name', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_variation', [ 'label' => 'Variation', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_quantity', [ 'label' => 'Quantity', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_item_price', [ 'label' => 'Item Price', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_item_subtotal', [ 'label' => 'Item Subtotal', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_content_totals', [
			'label' => esc_html__( 'Order Totals', 'wc-smart-checkout-builder' ),
		] );
		$this->add_control( 'show_shipping_total', [ 'label' => 'Shipping Total', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_payment_total', [ 'label' => 'Payment Total', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_order_total', [ 'label' => 'Order Total', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->end_controls_section();

		// Styles
		$this->start_controls_section( 'section_style_success', [ 'label' => 'Success Message', 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'success_typography', 'selector' => '{{WRAPPER}} .wcsc-ty-success-msg' ] );
		$this->add_control( 'success_color', [ 'label' => 'Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-success-msg' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'success_bg', [ 'label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-success-msg' => 'background-color: {{VALUE}};' ] ] );
		$this->add_responsive_control( 'success_padding', [ 'label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-success-msg' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ] );
		$this->add_responsive_control( 'success_margin', [ 'label' => 'Margin', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-success-msg' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ] );
		$this->add_control( 'success_align', [ 'label' => 'Alignment', 'type' => Controls_Manager::CHOOSE, 'options' => [ 'left' => [ 'icon' => 'eicon-text-align-left' ], 'center' => [ 'icon' => 'eicon-text-align-center' ], 'right' => [ 'icon' => 'eicon-text-align-right' ] ], 'selectors' => [ '{{WRAPPER}} .wcsc-ty-success-msg' => 'text-align: {{VALUE}};' ] ] );
		$this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'success_border', 'selector' => '{{WRAPPER}} .wcsc-ty-success-msg' ] );
		$this->add_control( 'success_radius', [ 'label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-success-msg' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;' ] ] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_style_order_info', [ 'label' => 'Order Information', 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'oi_label_typo', 'label' => 'Label Typography', 'selector' => '{{WRAPPER}} .wcsc-ty-order-info dt' ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'oi_value_typo', 'label' => 'Value Typography', 'selector' => '{{WRAPPER}} .wcsc-ty-order-info dd' ] );
		$this->add_control( 'oi_label_color', [ 'label' => 'Label Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-order-info dt' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'oi_value_color', [ 'label' => 'Value Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-order-info dd' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'oi_bg', [ 'label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-order-info' => 'background-color: {{VALUE}};' ] ] );
		$this->add_responsive_control( 'oi_padding', [ 'label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-order-info' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ] );
		$this->add_responsive_control( 'oi_margin', [ 'label' => 'Margin', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-order-info' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ] );
		$this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'oi_border', 'selector' => '{{WRAPPER}} .wcsc-ty-order-info' ] );
		$this->add_control( 'oi_radius', [ 'label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-order-info' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;' ] ] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_style_customer', [ 'label' => 'Customer / Addresses', 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'cust_heading_typo', 'label' => 'Heading Typography', 'selector' => '{{WRAPPER}} .wcsc-ty-address-col h3, {{WRAPPER}} .wcsc-ty-customer-details h2' ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'cust_text_typo', 'label' => 'Text Typography', 'selector' => '{{WRAPPER}} .wcsc-ty-address-col address, {{WRAPPER}} .wcsc-ty-customer-details p' ] );
		$this->add_control( 'cust_heading_color', [ 'label' => 'Heading Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-address-col h3, {{WRAPPER}} .wcsc-ty-customer-details h2' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'cust_text_color', [ 'label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-address-col address, {{WRAPPER}} .wcsc-ty-customer-details p' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'cust_bg', [ 'label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-customer-details' => 'background-color: {{VALUE}};' ] ] );
		$this->add_responsive_control( 'cust_padding', [ 'label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-customer-details' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ] );
		$this->add_responsive_control( 'cust_margin', [ 'label' => 'Margin', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-customer-details' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ] );
		$this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'cust_border', 'selector' => '{{WRAPPER}} .wcsc-ty-customer-details' ] );
		$this->add_control( 'cust_radius', [ 'label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-customer-details' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;' ] ] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_style_items', [ 'label' => 'Product Items', 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'item_img_width', [ 'label' => 'Image Width', 'type' => Controls_Manager::SLIDER, 'range' => [ 'px' => [ 'min' => 20, 'max' => 200 ] ], 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item-img img' => 'width: {{SIZE}}{{UNIT}}; height: auto;' ] ] );
		$this->add_control( 'item_img_radius', [ 'label' => 'Image Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item-img img' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;' ] ] );
		
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'item_name_typo', 'label' => 'Name Typography', 'selector' => '{{WRAPPER}} .wcsc-ty-item-name' ] );
		$this->add_control( 'item_name_color', [ 'label' => 'Name Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item-name' => 'color: {{VALUE}};' ] ] );
		
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'item_meta_typo', 'label' => 'Variation Typography', 'selector' => '{{WRAPPER}} .wcsc-ty-item-meta' ] );
		$this->add_control( 'item_meta_color', [ 'label' => 'Variation Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item-meta' => 'color: {{VALUE}};' ] ] );
		
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'item_qty_typo', 'label' => 'Quantity Typography', 'selector' => '{{WRAPPER}} .wcsc-ty-item-qty' ] );
		$this->add_control( 'item_qty_color', [ 'label' => 'Quantity Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item-qty' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'item_qty_bg', [ 'label' => 'Quantity Background', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item-qty' => 'background-color: {{VALUE}};' ] ] );
		$this->add_responsive_control( 'item_qty_pad', [ 'label' => 'Quantity Padding', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item-qty' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ] );
		$this->add_control( 'item_qty_radius', [ 'label' => 'Quantity Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item-qty' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;' ] ] );

		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'item_price_typo', 'label' => 'Subtotal Typography', 'selector' => '{{WRAPPER}} .wcsc-ty-item-subtotal' ] );
		$this->add_control( 'item_price_color', [ 'label' => 'Subtotal Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item-subtotal' => 'color: {{VALUE}};' ] ] );

		$this->add_control( 'item_bg', [ 'label' => 'Item Background', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item' => 'background-color: {{VALUE}};' ] ] );
		$this->add_responsive_control( 'item_padding', [ 'label' => 'Item Padding', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ] );
		$this->add_responsive_control( 'item_margin', [ 'label' => 'Item Margin', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ] );
		$this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'item_border', 'selector' => '{{WRAPPER}} .wcsc-ty-item' ] );
		$this->add_control( 'item_radius', [ 'label' => 'Item Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-item' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;' ] ] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_style_totals', [ 'label' => 'Order Totals', 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'totals_label_typo', 'label' => 'Label Typography', 'selector' => '{{WRAPPER}} .wcsc-ty-totals th' ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'totals_val_typo', 'label' => 'Value Typography', 'selector' => '{{WRAPPER}} .wcsc-ty-totals td' ] );
		$this->add_control( 'totals_label_color', [ 'label' => 'Label Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-totals th' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'totals_val_color', [ 'label' => 'Value Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-totals td' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'totals_bg', [ 'label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-totals' => 'background-color: {{VALUE}};' ] ] );
		$this->add_responsive_control( 'totals_padding', [ 'label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-totals' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ] );
		$this->add_responsive_control( 'totals_margin', [ 'label' => 'Margin', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-totals' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ] );
		$this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'totals_border', 'selector' => '{{WRAPPER}} .wcsc-ty-totals' ] );
		$this->add_control( 'totals_radius', [ 'label' => 'Border Radius', 'type' => Controls_Manager::DIMENSIONS, 'selectors' => [ '{{WRAPPER}} .wcsc-ty-totals' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;' ] ] );
		$this->end_controls_section();
	}

	protected function render() {
		$is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		$order = false;
		if ( $order_id && $order_key ) {
			$order = wc_get_order( $order_id );
			if ( $order && $order->get_order_key() !== $order_key ) {
				$order = false;
			}
		}

		echo '<div class="wcsc-thank-you-wrapper" style="width: 100%; box-sizing: border-box;">';
		if ( ! $order && $is_editor ) {
			$this->render_dummy_order();
		} elseif ( $order ) {
			$this->render_real_order( $order );
		} else {
			echo '<p>' . esc_html__( 'No valid order found. Please ensure you access this page after a successful checkout.', 'wc-smart-checkout-builder' ) . '</p>';
		}
		echo '</div>';
	}

	private function render_dummy_order() {
		$settings = $this->get_settings_for_display();
		$this->render_html_output(
			$settings,
			'#12345',
			date_i18n( get_option( 'date_format' ) ),
			'Processing',
			'Cash on Delivery',
			'$150.00',
			'$10.00',
			'$160.00',
			[
				[
					'name' => 'Sample T-Shirt',
					'meta' => 'Color: Blue',
					'qty' => 2,
					'price' => '$50.00',
					'subtotal' => '$100.00',
					'img' => wc_placeholder_img_src('thumbnail')
				],
				[
					'name' => 'Sample Hoodie',
					'meta' => 'Size: L',
					'qty' => 1,
					'price' => '$50.00',
					'subtotal' => '$50.00',
					'img' => wc_placeholder_img_src('thumbnail')
				]
			],
			'John Doe<br>123 Fake Street<br>New York, NY 10001<br>USA<br>john@example.com<br>1234567890',
			'John Doe<br>123 Fake Street<br>New York, NY 10001<br>USA'
		);
	}

	private function render_real_order( $order ) {
		$settings = $this->get_settings_for_display();
		
		$items_data = [];
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			
			// Get image
			$img_src = '';
			if ( $product ) {
				$image_id = $product->get_image_id();
				if ( $image_id ) {
					$img_src = wp_get_attachment_image_url( $image_id, 'thumbnail' );
				} else {
					$img_src = wc_placeholder_img_src('thumbnail');
				}
			}

			// Get meta
			$meta_html = '';
			if ( $product && $product->is_type( 'variation' ) ) {
				$meta_html = wc_get_formatted_variation( $product, true );
			}

			$items_data[] = [
				'name'     => $item->get_name(),
				'meta'     => $meta_html,
				'qty'      => $item->get_quantity(),
				'price'    => wc_price( $order->get_item_subtotal( $item, false, true ), array( 'currency' => $order->get_currency() ) ),
				'subtotal' => wc_price( $item->get_subtotal(), array( 'currency' => $order->get_currency() ) ),
				'img'      => $img_src
			];
		}

		$this->render_html_output(
			$settings,
			$order->get_order_number(),
			wc_format_datetime( $order->get_date_created() ),
			wc_get_order_status_name( $order->get_status() ),
			$order->get_payment_method_title(),
			$order->get_subtotal_to_display(),
			$order->get_shipping_to_display(),
			$order->get_formatted_order_total(),
			$items_data,
			$order->get_formatted_billing_address() ?: esc_html__( 'N/A', 'wc-smart-checkout-builder' ),
			$order->get_formatted_shipping_address() ?: esc_html__( 'N/A', 'wc-smart-checkout-builder' )
		);
	}

	private function render_html_output( $settings, $order_num, $date, $status, $payment, $subtotal, $shipping, $total, $items, $billing, $shipping_addr ) {
		// Custom styles for grid layouts since Elementor doesn't perfectly sandbox all custom layouts
		echo '<style>
			.wcsc-ty-order-info { display: flex; flex-wrap: wrap; gap: 20px; list-style: none; padding: 0; }
			.wcsc-ty-order-info li { display: flex; flex-direction: column; }
			.wcsc-ty-order-info dt { font-size: 0.8em; text-transform: uppercase; color: #718096; }
			.wcsc-ty-order-info dd { margin: 0; font-weight: 600; color: #2d3748; }
			.wcsc-ty-customer-details { display: flex; flex-wrap: wrap; gap: 40px; }
			.wcsc-ty-address-col { flex: 1; min-width: 250px; }
			.wcsc-ty-item { display: flex; align-items: center; gap: 15px; border-bottom: 1px solid #e2e8f0; padding: 15px 0; }
			.wcsc-ty-item:last-child { border-bottom: none; }
			.wcsc-ty-item-details { flex: 1; }
			.wcsc-ty-item-name { margin: 0 0 5px; font-weight: 600; }
			.wcsc-ty-item-meta { font-size: 0.85em; color: #718096; margin: 0 0 5px; }
			.wcsc-ty-item-qty { display: inline-block; background: #edf2f7; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; }
			.wcsc-ty-totals { width: 100%; border-collapse: collapse; margin-top: 20px; }
			.wcsc-ty-totals th, .wcsc-ty-totals td { padding: 10px 0; border-bottom: 1px solid #e2e8f0; text-align: left; }
			.wcsc-ty-totals tr:last-child th, .wcsc-ty-totals tr:last-child td { border-bottom: none; font-weight: bold; }
			.wcsc-ty-totals td { text-align: right; }
		</style>';

		if ( 'yes' === $settings['show_success_msg'] ) {
			echo '<div class="wcsc-ty-success-msg">' . esc_html( $settings['success_heading'] ) . '</div>';
		}

		if ( 'yes' === $settings['show_order_number'] || 'yes' === $settings['show_order_date'] || 'yes' === $settings['show_order_status'] || 'yes' === $settings['show_payment_method'] ) {
			echo '<ul class="wcsc-ty-order-info">';
			if ( 'yes' === $settings['show_order_number'] ) echo '<li><dt>' . esc_html__( 'Order number:', 'wc-smart-checkout-builder' ) . '</dt><dd>' . esc_html( $order_num ) . '</dd></li>';
			if ( 'yes' === $settings['show_order_date'] ) echo '<li><dt>' . esc_html__( 'Date:', 'wc-smart-checkout-builder' ) . '</dt><dd>' . esc_html( $date ) . '</dd></li>';
			if ( 'yes' === $settings['show_order_status'] ) echo '<li><dt>' . esc_html__( 'Status:', 'wc-smart-checkout-builder' ) . '</dt><dd>' . esc_html( $status ) . '</dd></li>';
			if ( 'yes' === $settings['show_payment_method'] ) echo '<li><dt>' . esc_html__( 'Payment method:', 'wc-smart-checkout-builder' ) . '</dt><dd>' . esc_html( $payment ) . '</dd></li>';
			echo '</ul>';
		}

		echo '<div class="wcsc-ty-items-list">';
		foreach ( $items as $item ) {
			echo '<div class="wcsc-ty-item">';
			if ( 'yes' === $settings['show_product_image'] && $item['img'] ) {
				echo '<div class="wcsc-ty-item-img"><img src="' . esc_url( $item['img'] ) . '" alt="" /></div>';
			}
			echo '<div class="wcsc-ty-item-details">';
			if ( 'yes' === $settings['show_product_name'] ) {
				echo '<h4 class="wcsc-ty-item-name">' . wp_kses_post( $item['name'] ) . '</h4>';
			}
			if ( 'yes' === $settings['show_variation'] && $item['meta'] ) {
				echo '<div class="wcsc-ty-item-meta">' . wp_kses_post( $item['meta'] ) . '</div>';
			}
			if ( 'yes' === $settings['show_quantity'] ) {
				echo '<div class="wcsc-ty-item-qty">Qty: ' . esc_html( $item['qty'] ) . '</div>';
			}
			echo '</div>';
			
			if ( 'yes' === $settings['show_item_subtotal'] ) {
				echo '<div class="wcsc-ty-item-subtotal">' . wp_kses_post( $item['subtotal'] ) . '</div>';
			}
			echo '</div>';
		}
		echo '</div>';

		if ( 'yes' === $settings['show_shipping_total'] || 'yes' === $settings['show_payment_total'] || 'yes' === $settings['show_order_total'] ) {
			echo '<table class="wcsc-ty-totals">';
			if ( 'yes' === $settings['show_shipping_total'] && $shipping ) {
				echo '<tr><th>' . esc_html__( 'Shipping:', 'wc-smart-checkout-builder' ) . '</th><td>' . wp_kses_post( $shipping ) . '</td></tr>';
			}
			if ( 'yes' === $settings['show_payment_total'] ) {
				echo '<tr><th>' . esc_html__( 'Payment method:', 'wc-smart-checkout-builder' ) . '</th><td>' . esc_html( $payment ) . '</td></tr>';
			}
			if ( 'yes' === $settings['show_order_total'] ) {
				echo '<tr><th>' . esc_html__( 'Total:', 'wc-smart-checkout-builder' ) . '</th><td>' . wp_kses_post( $total ) . '</td></tr>';
			}
			echo '</table>';
		}

		if ( 'yes' === $settings['show_customer_info'] ) {
			echo '<div class="wcsc-ty-customer-details">';
			if ( 'yes' === $settings['show_billing_address'] && $billing ) {
				echo '<div class="wcsc-ty-address-col">';
				echo '<h3>' . esc_html__( 'Billing address', 'wc-smart-checkout-builder' ) . '</h3>';
				echo '<address>' . wp_kses_post( $billing ) . '</address>';
				echo '</div>';
			}
			if ( 'yes' === $settings['show_shipping_address'] && $shipping_addr ) {
				echo '<div class="wcsc-ty-address-col">';
				echo '<h3>' . esc_html__( 'Shipping address', 'wc-smart-checkout-builder' ) . '</h3>';
				echo '<address>' . wp_kses_post( $shipping_addr ) . '</address>';
				echo '</div>';
			}
			echo '</div>';
		}
	}
}
