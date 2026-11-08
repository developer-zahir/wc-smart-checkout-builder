<?php
namespace WCSC\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Repeater;
use WCSC\Product_Handler;
use WCSC\Variation_Handler;
use WCSC\Checkout_Handler;

/**
 * Class Widget_Bundle_Checkout
 *
 * All-in-One Elementor Widget:
 * 1. Multi-product Bundle Grid with interactive inline variation swatches/buttons.
 * 2. Tiered Quantity Discounts with real-time dynamic summary bar.
 * 3. Order Bump (exact same functionality as single product checkout).
 * 4. High-converting embedded WooCommerce Checkout Form with direct AJAX submission.
 */
class Widget_Bundle_Checkout extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wcsc_bundle_checkout';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Bundle Smart Checkout', 'wc-smart-checkout-builder' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-cart-medium';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'wcsc-category', 'woocommerce-elements' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'bundle', 'checkout', 'tiered discount', 'variation', 'order bump', 'smart checkout', 'all in one' );
	}

	/**
	 * Enqueue widget script dependencies.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'wcsc-widget-script' );
	}

	/**
	 * Enqueue widget style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'wcsc-widget-style' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
	 * Register Content Controls.
	 */
	protected function register_content_controls() {
		// ==========================================
		// SECTION 1: Product Bundle Query & Rules
		// ==========================================
		$this->start_controls_section(
			'section_bundle_products',
			array(
				'label' => esc_html__( 'Product Bundle Selection', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'bundle_products',
			array(
				'label'       => esc_html__( 'Select Bundle Products', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => Product_Handler::get_product_options(),
				'description' => esc_html__( 'Select simple and/or variable WooCommerce products to include in this bundle.', 'wc-smart-checkout-builder' ),
				'label_block' => true,
			)
		);

		$this->add_responsive_control(
			'bundle_columns',
			array(
				'label'          => esc_html__( 'Grid Columns', 'wc-smart-checkout-builder' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1 Column',
					'2' => '2 Columns',
					'3' => '3 Columns',
					'4' => '4 Columns',
				),
				'selectors'      => array(
					'{{WRAPPER}} .wcsc-bundle-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
				),
			)
		);

		$this->add_control(
			'min_items',
			array(
				'label'       => esc_html__( 'Minimum Items Required', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 1,
				'min'         => 1,
				'description' => esc_html__( 'Minimum total quantity required before the customer can proceed to checkout.', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'max_items',
			array(
				'label'       => esc_html__( 'Maximum Items Allowed (0 = Unlimited)', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'description' => esc_html__( 'Maximum total quantity the customer can select.', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'show_product_image',
			array(
				'label'        => esc_html__( 'Show Product Image', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_product_title',
			array(
				'label'        => esc_html__( 'Show Product Title', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_product_price',
			array(
				'label'        => esc_html__( 'Show Product Price', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_quantity',
			array(
				'label'        => esc_html__( 'Show Quantity Stepper (+ / -)', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'badge_text',
			array(
				'label'       => esc_html__( 'Selected Badge Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'SELECTED', 'wc-smart-checkout-builder' ),
				'placeholder' => esc_html__( 'SELECTED', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'select_button_text',
			array(
				'label'       => esc_html__( 'Card Select Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Select', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'selected_button_text',
			array(
				'label'       => esc_html__( 'Card Selected Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Selected', 'wc-smart-checkout-builder' ),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION 2: Tiered Discounts
		// ==========================================
		$this->start_controls_section(
			'section_bundle_discounts',
			array(
				'label' => esc_html__( 'Tiered Quantity Discounts', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_tiered_discount',
			array(
				'label'        => esc_html__( 'Enable Tiered Discounts', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Automatically applies dynamic discount fee in WooCommerce cart based on total selected quantity.', 'wc-smart-checkout-builder' ),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'min_qty',
			array(
				'label'   => esc_html__( 'Minimum Items (Qty)', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 2,
				'min'     => 1,
			)
		);

		$repeater->add_control(
			'discount_type',
			array(
				'label'   => esc_html__( 'Discount Type', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'percentage',
				'options' => array(
					'percentage' => esc_html__( 'Percentage (%)', 'wc-smart-checkout-builder' ),
					'fixed'      => esc_html__( 'Fixed Amount', 'wc-smart-checkout-builder' ),
				),
			)
		);

		$repeater->add_control(
			'discount_amount',
			array(
				'label'   => esc_html__( 'Discount Value', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 0,
			)
		);

		$repeater->add_control(
			'tier_badge',
			array(
				'label'       => esc_html__( 'Tier Badge / Label (Optional)', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( '10% OFF', 'wc-smart-checkout-builder' ),
				'placeholder' => esc_html__( 'e.g. 10% OFF', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'discount_tiers',
			array(
				'label'       => esc_html__( 'Discount Rules', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'min_qty'         => 1,
						'discount_type'   => 'percentage',
						'discount_amount' => 0,
						'tier_badge'      => '',
					),
					array(
						'min_qty'         => 2,
						'discount_type'   => 'percentage',
						'discount_amount' => 10,
						'tier_badge'      => '10% OFF',
					),
					array(
						'min_qty'         => 3,
						'discount_type'   => 'percentage',
						'discount_amount' => 15,
						'tier_badge'      => '15% OFF - POPULAR',
					),
					array(
						'min_qty'         => 5,
						'discount_type'   => 'percentage',
						'discount_amount' => 20,
						'tier_badge'      => '20% OFF - BEST VALUE',
					),
				),
				'title_field' => '{{{ min_qty }}} Items = {{{ discount_amount }}}{{{ discount_type == "percentage" ? "%" : "$" }}} Discount',
				'condition'   => array(
					'enable_tiered_discount' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION 3: Summary Bar & CTA
		// ==========================================
		$this->start_controls_section(
			'section_bundle_summary',
			array(
				'label' => esc_html__( 'Summary Bar & CTA', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_summary_bar',
			array(
				'label'        => esc_html__( 'Show Dynamic Summary Bar', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'cta_button_text',
			array(
				'label'       => esc_html__( 'CTA Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Proceed to Checkout', 'wc-smart-checkout-builder' ),
				'placeholder' => esc_html__( 'Proceed to Checkout', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'cta_scroll_target',
			array(
				'label'       => esc_html__( 'Scroll Target Selector', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '#wcsc-bundle-checkout-form',
				'description' => esc_html__( 'Smooth-scrolls to the embedded checkout section below when clicked.', 'wc-smart-checkout-builder' ),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION 4: Order Bump (Exact same as original widget)
		// ==========================================
		$this->start_controls_section(
			'section_order_bump',
			array(
				'label' => esc_html__( 'Order Bump', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_order_bump',
			array(
				'label'        => esc_html__( 'Enable Order Bump', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'No', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->add_control(
			'order_bump_query_type',
			array(
				'label'     => esc_html__( 'Product Query Type', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'manual',
				'options'   => array(
					'manual'   => esc_html__( 'Manual Selection', 'wc-smart-checkout-builder' ),
					'category' => esc_html__( 'By Category', 'wc-smart-checkout-builder' ),
				),
				'condition' => array(
					'enable_order_bump' => 'yes',
				),
			)
		);

		$this->add_control(
			'order_bump_products',
			array(
				'label'       => esc_html__( 'Select Bump Product', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => Product_Handler::get_bump_product_options(),
				'condition'   => array(
					'enable_order_bump'     => 'yes',
					'order_bump_query_type' => 'manual',
				),
				'label_block' => true,
			)
		);

		$this->add_control(
			'order_bump_layout',
			array(
				'label'     => esc_html__( 'Layout Style', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'classic',
				'options'   => array(
					'classic' => esc_html__( 'Classic Card', 'wc-smart-checkout-builder' ),
					'compact' => esc_html__( 'Compact Box', 'wc-smart-checkout-builder' ),
					'inline'  => esc_html__( 'Inline Bar', 'wc-smart-checkout-builder' ),
				),
				'condition' => array(
					'enable_order_bump' => 'yes',
				),
			)
		);

		$this->add_control(
			'order_bump_section_title',
			array(
				'label'     => esc_html__( 'Bump Section Title', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Special Offer: Add This To Your Order!', 'wc-smart-checkout-builder' ),
				'condition' => array(
					'enable_order_bump' => 'yes',
				),
			)
		);

		$this->add_control(
			'order_bump_section_subtitle',
			array(
				'label'     => esc_html__( 'Bump Section Subtitle', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Exclusive one-time deal available right now', 'wc-smart-checkout-builder' ),
				'condition' => array(
					'enable_order_bump' => 'yes',
				),
			)
		);

		$this->add_control(
			'order_bump_action_text',
			array(
				'label'     => esc_html__( 'Action Button Text', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Yes, I want this special offer!', 'wc-smart-checkout-builder' ),
				'condition' => array(
					'enable_order_bump' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION 5: Checkout Form & Settings
		// ==========================================
		$this->start_controls_section(
			'section_checkout_settings',
			array(
				'label' => esc_html__( 'Checkout Form Settings', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'checkout_layout',
			array(
				'label'   => esc_html__( 'Checkout Layout', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '2col_50_50',
				'options' => array(
					'1col'        => esc_html__( '1 Column (Stacked)', 'wc-smart-checkout-builder' ),
					'2col_50_50'  => esc_html__( '2 Columns (50% / 50%)', 'wc-smart-checkout-builder' ),
					'2col_60_40'  => esc_html__( '2 Columns (60% / 40%)', 'wc-smart-checkout-builder' ),
					'2col_70_30'  => esc_html__( '2 Columns (70% / 30%)', 'wc-smart-checkout-builder' ),
				),
			)
		);

		$this->add_control(
			'billing_heading_text',
			array(
				'label'   => esc_html__( 'Billing Details Heading', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Billing & Shipping Information', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'shipping_label_text',
			array(
				'label'   => esc_html__( 'Shipping Method Heading', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Shipping Method', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'payment_heading_text',
			array(
				'label'   => esc_html__( 'Payment Heading', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Payment Method', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'order_button_text',
			array(
				'label'       => esc_html__( 'Place Order Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Complete Order', 'wc-smart-checkout-builder' ),
				'placeholder' => esc_html__( 'Complete Order', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'order_button_subtitle',
			array(
				'label'       => esc_html__( 'Button Subtitle Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( '100% Secure Checkout & Guaranteed Delivery', 'wc-smart-checkout-builder' ),
				'placeholder' => esc_html__( '100% Secure Checkout & Guaranteed Delivery', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'enable_order_button_pulse',
			array(
				'label'        => esc_html__( 'Order Button Pulse Animation', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'enable_bd_phone_validation',
			array(
				'label'        => esc_html__( 'Enable BD Phone Validation (11 digits)', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'enable_mobile_sticky_button',
			array(
				'label'        => esc_html__( 'Enable Mobile Sticky Order Button', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'no',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register Style Controls.
	 */
	protected function register_style_controls() {
		// ==========================================
		// STYLE SECTION 1: Product Cards
		// ==========================================
		$this->start_controls_section(
			'section_style_bundle_cards',
			array(
				'label' => esc_html__( 'Product Bundle Cards', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_bg',
			array(
				'label'     => esc_html__( 'Card Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .wcsc-bundle-card',
			)
		);

		$this->add_responsive_control(
			'card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'    => '10',
					'right'  => '10',
					'bottom' => '10',
					'left'   => '10',
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'    => '16',
					'right'  => '16',
					'bottom' => '16',
					'left'   => '16',
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .wcsc-bundle-card',
			)
		);

		$this->add_control(
			'heading_card_active',
			array(
				'label'     => esc_html__( 'Active / Selected Card Style', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'card_active_border_color',
			array(
				'label'     => esc_html__( 'Active Border Highlight Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2271b1',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-card.is-selected' => 'border-color: {{VALUE}} !important; border-width: 2px;',
				),
			)
		);

		$this->add_control(
			'card_active_badge_bg',
			array(
				'label'     => esc_html__( 'Selected Badge Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2271b1',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-badge' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_active_badge_color',
			array(
				'label'     => esc_html__( 'Selected Badge Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-badge' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE SECTION 2: Inline Variation Buttons
		// ==========================================
		$this->start_controls_section(
			'section_style_bundle_variations',
			array(
				'label' => esc_html__( 'Inline Variation Buttons', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'variation_btn_typography',
				'selector' => '{{WRAPPER}} .wcsc-bundle-var-btn',
			)
		);

		$this->add_control(
			'var_btn_color',
			array(
				'label'     => esc_html__( 'Button Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#333333',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-var-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'var_btn_bg',
			array(
				'label'     => esc_html__( 'Button Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f8f9fa',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-var-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'var_btn_border_color',
			array(
				'label'     => esc_html__( 'Button Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#dcdcde',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-var-btn' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'var_btn_selected_bg',
			array(
				'label'     => esc_html__( 'Active Selected Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2271b1',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-var-btn.is-selected' => 'background-color: {{VALUE}} !important; color: #ffffff !important; border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE SECTION 3: Summary Bar & CTA
		// ==========================================
		$this->start_controls_section(
			'section_style_bundle_summary',
			array(
				'label' => esc_html__( 'Summary Bar & CTA Button', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'summary_bar_bg',
			array(
				'label'     => esc_html__( 'Summary Bar Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f0f6fc',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-summary-bar' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'summary_cta_bg',
			array(
				'label'     => esc_html__( 'CTA Button Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2271b1',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-summary-cta-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'summary_cta_color',
			array(
				'label'     => esc_html__( 'CTA Button Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-summary-cta-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'summary_cta_typography',
				'selector' => '{{WRAPPER}} .wcsc-summary-cta-btn',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE SECTION 4: Place Order Button Style
		// ==========================================
		$this->start_controls_section(
			'section_style_order_btn',
			array(
				'label' => esc_html__( 'Place Order Button', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'order_btn_bg',
			array(
				'label'     => esc_html__( 'Button Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#008a00',
				'selectors' => array(
					'{{WRAPPER}} #place_order, {{WRAPPER}} .wcas-place-order-btn' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'order_btn_color',
			array(
				'label'     => esc_html__( 'Button Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} #place_order, {{WRAPPER}} .wcas-place-order-btn' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'order_btn_typography',
				'selector' => '{{WRAPPER}} #place_order, {{WRAPPER}} .wcas-place-order-btn',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$product_ids = ! empty( $settings['bundle_products'] ) ? (array) $settings['bundle_products'] : array();
		$product_ids = array_filter( array_map( 'absint', $product_ids ) );

		if ( empty( $product_ids ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				?>
				<div class="wcsc-notice-warning" style="padding: 20px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px; color: #856404; text-align: center;">
					<h3 style="margin: 0 0 8px 0;"><?php esc_html_e( 'Bundle Smart Checkout: No Products Selected', 'wc-smart-checkout-builder' ); ?></h3>
					<p style="margin: 0;"><?php esc_html_e( 'Please select products from the "Product Bundle Selection" panel in Elementor to display the bundle grid.', 'wc-smart-checkout-builder' ); ?></p>
				</div>
				<?php
			}
			return;
		}

		$min_items = ! empty( $settings['min_items'] ) ? max( 1, absint( $settings['min_items'] ) ) : 1;
		$max_items = ! empty( $settings['max_items'] ) ? absint( $settings['max_items'] ) : 0;
		$tiers     = ! empty( $settings['discount_tiers'] ) ? (array) $settings['discount_tiers'] : array();
		$badge_txt = ! empty( $settings['badge_text'] ) ? $settings['badge_text'] : __( 'SELECTED', 'wc-smart-checkout-builder' );
		$sel_txt   = ! empty( $settings['select_button_text'] ) ? $settings['select_button_text'] : __( 'Select', 'wc-smart-checkout-builder' );
		$seled_txt = ! empty( $settings['selected_button_text'] ) ? $settings['selected_button_text'] : __( 'Selected', 'wc-smart-checkout-builder' );
		$cta_text  = ! empty( $settings['cta_button_text'] ) ? $settings['cta_button_text'] : __( 'Proceed to Checkout', 'wc-smart-checkout-builder' );
		$target_id = ! empty( $settings['cta_scroll_target'] ) ? $settings['cta_scroll_target'] : '#wcsc-bundle-checkout-form';

		// Formatted discount tiers data
		$tiers_data = array();
		if ( 'yes' === ( $settings['enable_tiered_discount'] ?? 'yes' ) && is_array( $tiers ) ) {
			foreach ( $tiers as $tier ) {
				$tiers_data[] = array(
					'min_qty'         => absint( $tier['min_qty'] ?? 1 ),
					'discount_type'   => sanitize_key( $tier['discount_type'] ?? 'percentage' ),
					'discount_amount' => floatval( $tier['discount_amount'] ?? 0 ),
					'tier_badge'      => sanitize_text_field( $tier['tier_badge'] ?? '' ),
				);
			}
		}

		// Main widget wrapper (strictly scoped under .wcas-checkout-wrapper and .wcsc-product-checkout-widget)
		?>
		<div class="wcsc-product-checkout-widget wcas-checkout-wrapper wcsc-bundle-checkout-wrapper"
			id="wcsc-bundle-checkout-<?php echo esc_attr( $this->get_id() ); ?>"
			data-widget-id="<?php echo esc_attr( $this->get_id() ); ?>"
			data-min-items="<?php echo esc_attr( $min_items ); ?>"
			data-max-items="<?php echo esc_attr( $max_items ); ?>"
			data-tiers="<?php echo esc_attr( wp_json_encode( $tiers_data ) ); ?>"
			<?php echo Checkout_Handler::render_data_attrs( $settings ); ?>>

			<!-- ========================================== -->
			<!-- PART 1: Product Bundle Grid Cards         -->
			<!-- ========================================== -->
			<div class="wcsc-bundle-grid">
				<?php
				$first_item = true;
				foreach ( $product_ids as $pid ) :
					$prod = wc_get_product( $pid );
					if ( ! $prod || ! $prod->is_purchasable() ) {
						continue;
					}

					$is_var        = $prod->is_type( 'variable' );
					$img_id        = $prod->get_image_id();
					$img_url       = $img_id ? wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src();
					$price_val     = (float) $prod->get_price();
					$var_json_data = $is_var ? Variation_Handler::get_available_variations_json( $prod ) : array();
					$attr_data     = $is_var ? Variation_Handler::get_product_attributes_data( $prod, $settings ) : array();

					// Default first item active or selected
					$card_active = $first_item ? ' is-selected' : '';
					$first_item  = false;
				?>
					<div class="wcsc-bundle-card<?php echo esc_attr( $card_active ); ?>"
						data-product-id="<?php echo esc_attr( $pid ); ?>"
						data-is-variable="<?php echo $is_var ? 'yes' : 'no'; ?>"
						data-base-price="<?php echo esc_attr( $price_val ); ?>"
						data-current-price="<?php echo esc_attr( $price_val ); ?>"
						data-variation-id="0"
						data-qty="1">

						<!-- Corner Active Badge -->
						<div class="wcsc-bundle-badge"><?php echo esc_html( $badge_txt ); ?></div>

						<!-- Product Image -->
						<?php if ( 'yes' === ( $settings['show_product_image'] ?? 'yes' ) ) : ?>
							<div class="wcsc-bundle-card-image">
								<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $prod->get_name() ); ?>" class="wcsc-card-img" />
							</div>
						<?php endif; ?>

						<!-- Product Content -->
						<div class="wcsc-bundle-card-body">
							<?php if ( 'yes' === ( $settings['show_product_title'] ?? 'yes' ) ) : ?>
								<h4 class="wcsc-bundle-card-title"><?php echo esc_html( $prod->get_name() ); ?></h4>
							<?php endif; ?>

							<?php if ( 'yes' === ( $settings['show_product_price'] ?? 'yes' ) ) : ?>
								<div class="wcsc-bundle-card-price">
									<?php echo $prod->get_price_html(); ?>
								</div>
							<?php endif; ?>

							<!-- Inline Variation Buttons for Variable Products -->
							<?php if ( $is_var && ! empty( $attr_data ) ) : ?>
								<div class="wcsc-bundle-variations-wrapper">
									<?php foreach ( $attr_data as $attr_name => $attr_info ) : ?>
										<div class="wcsc-bundle-attr-row" data-attribute="<?php echo esc_attr( sanitize_title( $attr_name ) ); ?>">
											<span class="wcsc-bundle-attr-label"><?php echo esc_html( $attr_info['label'] ); ?>:</span>
											<div class="wcsc-bundle-var-buttons">
												<?php
												$first_opt = true;
												foreach ( $attr_info['options'] as $opt ) :
													$opt_active = $first_opt ? ' is-selected' : '';
													$first_opt  = false;
												?>
													<button type="button"
														class="wcsc-bundle-var-btn<?php echo esc_attr( $opt_active ); ?>"
														data-attr="<?php echo esc_attr( sanitize_title( $attr_name ) ); ?>"
														data-value="<?php echo esc_attr( $opt['value'] ); ?>">
														<?php echo esc_html( $opt['label'] ); ?>
													</button>
												<?php endforeach; ?>
											</div>
										</div>
									<?php endforeach; ?>

									<!-- Product Variations JSON data -->
									<script type="application/json" class="wcsc-product-variations-json">
										<?php echo wp_json_encode( $var_json_data ); ?>
									</script>
								</div>
							<?php endif; ?>

							<!-- Quantity Stepper & Select Button Row -->
							<div class="wcsc-bundle-card-actions">
								<?php if ( 'yes' === ( $settings['show_quantity'] ?? 'yes' ) ) : ?>
									<div class="wcsc-bundle-qty">
										<button type="button" class="wcsc-bundle-qty-btn minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'wc-smart-checkout-builder' ); ?>">&minus;</button>
										<input type="number" class="wcsc-bundle-qty-val" value="1" min="1" max="999" />
										<button type="button" class="wcsc-bundle-qty-btn plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'wc-smart-checkout-builder' ); ?>">&plus;</button>
									</div>
								<?php endif; ?>

								<button type="button" class="wcsc-bundle-select-btn">
									<span class="btn-text-select"><?php echo esc_html( $sel_txt ); ?></span>
									<span class="btn-text-selected"><?php echo esc_html( $seled_txt ); ?></span>
								</button>
							</div>

						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- ========================================== -->
			<!-- PART 2: Dynamic Tiered Summary Bar        -->
			<!-- ========================================== -->
			<?php if ( 'yes' === ( $settings['show_summary_bar'] ?? 'yes' ) ) : ?>
				<div class="wcsc-bundle-summary-bar">
					<div class="wcsc-summary-info">
						<div class="wcsc-summary-count-line">
							<strong class="wcsc-summary-count">1</strong> <?php esc_html_e( 'item(s) selected', 'wc-smart-checkout-builder' ); ?>
						</div>
						<div class="wcsc-summary-savings" style="display: none;">
							<span class="wcsc-savings-badge"></span>
							<span class="wcsc-savings-amount"></span>
						</div>
					</div>
					<div class="wcsc-summary-actions">
						<div class="wcsc-summary-total-box">
							<span class="wcsc-total-label"><?php esc_html_e( 'Total:', 'wc-smart-checkout-builder' ); ?></span>
							<span class="wcsc-summary-total-val">—</span>
						</div>
						<a href="<?php echo esc_attr( $target_id ); ?>" class="wcsc-summary-cta-btn">
							<span class="wcsc-cta-text"><?php echo esc_html( $cta_text ); ?></span>
							<span class="wcsc-cta-arrow">&darr;</span>
						</a>
					</div>
				</div>
			<?php endif; ?>

			<!-- ========================================== -->
			<!-- PART 3: Order Bump & Native Checkout Form -->
			<!-- ========================================== -->
			<div id="wcsc-bundle-checkout-form" class="wcsc-checkout-section">
				<?php
				// Renders Order Bump + Checkout Form Blocks seamlessly using Checkout_Handler
				Checkout_Handler::render_checkout_section( null, $settings );
				?>
			</div>

		</div>
		<?php
	}
}
