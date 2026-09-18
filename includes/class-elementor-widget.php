<?php
namespace WCSC;

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

/**
 * Class Elementor_Widget
 *
 * Elementor Widget for displaying product, variation swatches, and native checkout in one place.
 */
class Elementor_Widget extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wcsc_product_checkout';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Product Variation & Checkout', 'wc-smart-checkout-builder' );
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
		return array( 'woocommerce', 'product', 'variation', 'checkout', 'swatches', 'smart checkout', 'direct checkout' );
	}

	/**
	 * Enqueue widget script and style dependencies.
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
	 * Content tab controls.
	 */
	protected function register_content_controls() {
		// --- Section: Product Selection ---
		$this->start_controls_section(
			'section_product_selection',
			array(
				'label' => esc_html__( 'Product Selection', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'use_current_product',
			array(
				'label'        => esc_html__( 'Use Current Product', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'No', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'product_id',
			array(
				'label'       => esc_html__( 'Select Product', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'options'     => Product_Handler::get_product_options(),
				'default'     => '',
				'condition'   => array(
					'use_current_product!' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// --- Section: Display Elements (Show/Hide) ---
		$this->start_controls_section(
			'section_display_elements',
			array(
				'label' => esc_html__( 'Display Elements', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'        => esc_html__( 'Show Product Title', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label'        => esc_html__( 'Show Product Image', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => esc_html__( 'Show Price', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_stock',
			array(
				'label'        => esc_html__( 'Show Stock Status', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_quantity',
			array(
				'label'        => esc_html__( 'Show Quantity', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_variations',
			array(
				'label'        => esc_html__( 'Show Variation Attributes', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// --- Section: Variation Settings ---
		$this->start_controls_section(
			'section_variation_settings',
			array(
				'label'     => esc_html__( 'Variation Settings', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'show_variations' => 'yes',
				),
			)
		);

		$this->add_control(
			'default_display_type',
			array(
				'label'   => esc_html__( 'Default Attribute Display', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'button' => esc_html__( 'Button / Swatch', 'wc-smart-checkout-builder' ),
					'image'  => esc_html__( 'Image Swatch', 'wc-smart-checkout-builder' ),
				),
				'default' => 'button',
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'attribute_name',
			array(
				'label'       => esc_html__( 'Attribute Name or Slug', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'e.g. Color, Size, pa_color', 'wc-smart-checkout-builder' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'custom_label',
			array(
				'label'       => esc_html__( 'Custom Label / Title', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'e.g. সাইজ নির্বাচন করুন: {value}', 'wc-smart-checkout-builder' ),
				'description' => esc_html__( 'Use {value} or {selected_variation} to dynamically display the currently selected option.', 'wc-smart-checkout-builder' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'display_type',
			array(
				'label'   => esc_html__( 'Display Type', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'button' => esc_html__( 'Button / Text', 'wc-smart-checkout-builder' ),
					'image'  => esc_html__( 'Image', 'wc-smart-checkout-builder' ),
				),
				'default' => 'button',
			)
		);

		$this->add_control(
			'attribute_types',
			array(
				'label'         => esc_html__( 'Per-Attribute Customizations', 'wc-smart-checkout-builder' ),
				'type'          => Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'title_field'   => '{{{ attribute_name }}} — {{{ custom_label ? custom_label : display_type }}}',
				'prevent_empty' => false,
			)
		);

		$this->end_controls_section();

		// --- Section: Checkout Layout ---
		$this->start_controls_section(
			'section_checkout_layout',
			array(
				'label' => esc_html__( 'Checkout Layout', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'checkout_layout',
			array(
				'label'   => esc_html__( 'Checkout Layout', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'2_columns' => esc_html__( '2 Columns', 'wc-smart-checkout-builder' ),
					'1_column'  => esc_html__( '1 Column', 'wc-smart-checkout-builder' ),
				),
				'default' => '2_columns',
			)
		);

		$this->add_responsive_control(
			'checkout_col_gap',
			array(
				'label'      => esc_html__( 'Column Gap', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-product-checkout-widget' => '--wcsc-col-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'checkout_row_gap',
			array(
				'label'      => esc_html__( 'Row Gap', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-product-checkout-widget' => '--wcsc-row-gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcas-checkout-wrapper'        => '--wcsc-row-gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcas-checkout-column'         => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'heading_section_visibility',
			array(
				'label'     => esc_html__( 'Sections Visibility', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_checkout_order_review',
			array(
				'label'        => esc_html__( 'Show Order Review', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_cart_item_image',
			array(
				'label'        => esc_html__( 'Show Product Image', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'No', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition' => array(
					'show_checkout_order_review' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_checkout_shipping',
			array(
				'label'        => esc_html__( 'Show Shipping Methods', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);


		$this->add_control(
			'show_checkout_payment',
			array(
				'label'        => esc_html__( 'Show Payment Gateways', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// --- Section: Checkout Translation (trimmed to 6 keys — req 40) ---
		$this->start_controls_section(
			'section_checkout_translation',
			array(
				'label' => esc_html__( 'Checkout Translation', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'billing_heading_text',
			array(
				'label'   => esc_html__( 'Customer Information Title', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Customer information', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'order_review_heading_text',
			array(
				'label'   => esc_html__( 'Order Review Title', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Your Order', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'product_label_text',
			array(
				'label'   => esc_html__( 'Product Column Label', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Product', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'subtotal_label_text',
			array(
				'label'   => esc_html__( 'Subtotal Label', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Subtotal', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'shipping_label_text',
			array(
				'label'   => esc_html__( 'Shipping Label', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Shipping', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'payment_heading_text',
			array(
				'label'   => esc_html__( 'Payment Title', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Payment', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'total_label_text',
			array(
				'label'   => esc_html__( 'Total Label', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Total', 'wc-smart-checkout-builder' ),
			)
		);

		$this->end_controls_section();

	// --- Section: Order Button ---
	$this->start_controls_section(
		'section_order_button',
		array(
			'label' => esc_html__( 'Order Now Button', 'wc-smart-checkout-builder' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		)
	);

		$this->add_control(
			'order_button_position',
			array(
				'label'   => esc_html__( 'Button Position', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'left_column'  => esc_html__( 'Left Column', 'wc-smart-checkout-builder' ),
					'right_column' => esc_html__( 'Right Column', 'wc-smart-checkout-builder' ),
					'full_width'   => esc_html__( 'Full Width', 'wc-smart-checkout-builder' ),
				),
				'default' => 'right_column',
			)
		);

		$this->add_control(
			'order_button_text',
			array(
				'label'       => esc_html__( 'Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Order Now', 'wc-smart-checkout-builder' ),
				'description' => esc_html__( 'Use {total_price} to display the dynamic total price inside the button.', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'order_button_icon',
			array(
				'label'       => esc_html__( 'Button Icon', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => array(
					'value'   => 'fas fa-shopping-bag',
					'library' => 'fa-solid',
				),
			)
		);

		$this->add_control(
			'order_button_icon_align',
			array(
				'label'     => esc_html__( 'Icon Position', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'left',
				'options'   => array(
					'left'  => esc_html__( 'Before Text', 'wc-smart-checkout-builder' ),
					'right' => esc_html__( 'After Text', 'wc-smart-checkout-builder' ),
				),
				'condition' => array(
					'order_button_icon[value]!' => '',
				),
			)
		);

		$this->add_responsive_control(
			'order_button_icon_indent',
			array(
				'label'      => esc_html__( 'Icon Spacing', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-btn-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'order_button_icon[value]!' => '',
				),
			)
		);

		$this->add_control(
			'order_button_animation',
			array(
				'label'       => esc_html__( 'Button Animation', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'border_run',
				'options'     => array(
					'border_run'          => esc_html__( 'Rotating Border', 'wc-smart-checkout-builder' ),
					'bounce'              => esc_html__( 'Gentle Bounce', 'wc-smart-checkout-builder' ),
					'tada'                => esc_html__( 'Tada / Wobble', 'wc-smart-checkout-builder' ),
					'pulse'               => esc_html__( 'Glow Pulse', 'wc-smart-checkout-builder' ),
					'none'                => esc_html__( 'None', 'wc-smart-checkout-builder' ),
				),
			)
		);

		$this->end_controls_section();

		// --- Section: Phone Number Validation ---
		$this->start_controls_section(
			'section_phone_validation',
			array(
				'label' => esc_html__( 'Phone Number Validation', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_phone_validation',
			array(
				'label'        => esc_html__( 'Enable Phone Validation', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'No', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Validates 11-digit Bangladeshi mobile numbers (013-019, +880, 880). Shows an interactive modal on error.', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'phone_modal_title',
			array(
				'label'       => esc_html__( 'Modal Title', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'সঠিক ফোন নম্বর দিন', 'wc-smart-checkout-builder' ),
				'condition'   => array(
					'enable_phone_validation' => 'yes',
				),
			)
		);

		$this->add_control(
			'phone_modal_message',
			array(
				'label'       => esc_html__( 'Modal Message', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'অনুগ্রহ করে একটি ১১ ডিজিটের বৈধ বাংলাদেশি মোবাইল নম্বর ব্যবহার করুন।', 'wc-smart-checkout-builder' ),
				'condition'   => array(
					'enable_phone_validation' => 'yes',
				),
			)
		);

		$this->add_control(
			'phone_modal_btn_text',
			array(
				'label'       => esc_html__( 'Modal Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'ঠিক আছে', 'wc-smart-checkout-builder' ),
				'condition'   => array(
					'enable_phone_validation' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// --- Section: Mobile Sticky Order Button ---
		$this->start_controls_section(
			'section_mobile_sticky_button',
			array(
				'label' => esc_html__( 'Floating Button', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_mobile_sticky_button',
			array(
				'label'        => esc_html__( 'Enable Floating Button', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'No', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Displays a floating checkout button with shine animation. Smooth-scrolls to checkout.', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'mobile_sticky_button_text',
			array(
				'label'       => esc_html__( 'Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'অর্ডার করুন', 'wc-smart-checkout-builder' ),
				'condition'   => array(
					'enable_mobile_sticky_button' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab controls.
	 */
	protected function register_style_controls() {
		// --- 1. Title Style ---
		$this->start_controls_section(
			'section_style_title',
			array(
				'label'     => esc_html__( 'Product Title', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_title' => 'yes',
				),
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-product-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .wcsc-product-title',
			)
		);

		$this->add_responsive_control(
			'title_align',
			array(
				'label'     => esc_html__( 'Alignment', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcsc-product-title' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-product-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// --- 2. Image Style ---
		$this->start_controls_section(
			'section_style_image',
			array(
				'label'     => esc_html__( 'Product Image', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_image' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'image_width',
			array(
				'label'      => esc_html__( 'Width', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 1200 ),
					'%'  => array( 'min' => 0, 'max' => 100 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-product-image img' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_height',
			array(
				'label'      => esc_html__( 'Height', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 1200 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-product-image img' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'image_object_fit',
			array(
				'label'     => esc_html__( 'Object Fit', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'cover'   => esc_html__( 'Cover', 'wc-smart-checkout-builder' ),
					'contain' => esc_html__( 'Contain', 'wc-smart-checkout-builder' ),
					'fill'    => esc_html__( 'Fill', 'wc-smart-checkout-builder' ),
				),
				'default'   => 'contain',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-product-image img' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'image_border',
				'selector' => '{{WRAPPER}} .wcsc-product-image img',
			)
		);

		$this->add_responsive_control(
			'image_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'default'    => array(
					'top'      => '4',
					'right'    => '4',
					'bottom'   => '4',
					'left'     => '4',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-product-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_align',
			array(
				'label'     => esc_html__( 'Alignment', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcsc-product-image' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-product-image' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// --- 3. Price Style ---
		$this->start_controls_section(
			'section_style_price',
			array(
				'label'     => esc_html__( 'Price', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_price' => 'yes',
				),
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => esc_html__( 'Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-product-price, {{WRAPPER}} .wcsc-product-price .woocommerce-Price-amount' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'sale_price_color',
			array(
				'label'     => esc_html__( 'Sale Price Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-product-price ins .woocommerce-Price-amount' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'price_typography',
				'selector' => '{{WRAPPER}} .wcsc-product-price',
			)
		);

		$this->add_responsive_control(
			'price_align',
			array(
				'label'     => esc_html__( 'Alignment', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcsc-product-price' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'price_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-product-price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// --- 4. Quantity Style ---
		$this->start_controls_section(
			'section_style_quantity',
			array(
				'label'     => esc_html__( 'Quantity Field', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_quantity' => 'yes',
				),
			)
		);

		$this->add_control(
			'qty_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-quantity input.qty, {{WRAPPER}} .wcsc-qty-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'qty_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-quantity input.qty, {{WRAPPER}} .wcsc-qty-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'qty_border',
				'selector' => '{{WRAPPER}} .wcsc-quantity-wrapper',
			)
		);

		$this->add_responsive_control(
			'qty_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'default'    => array(
					'top'      => '4',
					'right'    => '4',
					'bottom'   => '4',
					'left'     => '4',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-quantity-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'qty_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-quantity' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// --- 5. Variation Swatches Style ---
		$this->start_controls_section(
			'section_style_variations',
			array(
				'label'     => esc_html__( 'Variation Swatches', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_variations' => 'yes',
				),
			)
		);

		// Attribute Label Heading & Alignment
		$this->add_control(
			'heading_attr_title_style',
			array(
				'label'     => esc_html__( 'Attribute Title / Heading', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'attr_title_color',
			array(
				'label'     => esc_html__( 'Title Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-attribute-label, {{WRAPPER}} .wcsc-selected-value-label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'attr_title_typography',
				'selector' => '{{WRAPPER}} .wcsc-attribute-header',
			)
		);

		$this->add_responsive_control(
			'attr_header_align',
			array(
				'label'     => esc_html__( 'Header Alignment', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcsc-attribute-header' => 'text-align: {{VALUE}}; justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'attr_title_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-attribute-header' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		// Options Alignment
		$this->add_responsive_control(
			'options_align',
			array(
				'label'     => esc_html__( 'Swatches Alignment', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array( 'title' => esc_html__( 'Left', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-left' ),
					'center'     => array( 'title' => esc_html__( 'Center', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-center' ),
					'flex-end'   => array( 'title' => esc_html__( 'Right', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcsc-options-container' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// --- 5B. Button Variant Style ---
		$this->start_controls_section(
			'section_style_variations_button',
			array(
				'label'     => esc_html__( 'Button Variant Style', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_variations' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'btn_swatch_width',
			array(
				'label'      => esc_html__( 'Button Width', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 600 ),
					'%'  => array( 'min' => 0, 'max' => 100 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-swatch-button' => 'width: {{SIZE}}{{UNIT}}; min-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'btn_swatch_height',
			array(
				'label'      => esc_html__( 'Button Height', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 300 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-swatch-button' => 'height: {{SIZE}}{{UNIT}}; min-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_swatch_typography',
				'selector' => '{{WRAPPER}} .wcsc-swatch-button',
			)
		);

		$this->add_responsive_control(
			'btn_swatch_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-swatch-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'btn_swatch_gap',
			array(
				'label'      => esc_html__( 'Options Gap', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-options-container' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'btn_swatch_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'default'    => array(
					'top'      => '4',
					'right'    => '4',
					'bottom'   => '4',
					'left'     => '4',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-swatch-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_btn_swatch_states' );

		$this->start_controls_tab(
			'tab_btn_swatch_normal',
			array( 'label' => esc_html__( 'Normal', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'btn_swatch_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-swatch-button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_swatch_bg_color',
			array(
				'label'     => esc_html__( 'Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-swatch-button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_swatch_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-swatch-button' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_btn_swatch_hover',
			array( 'label' => esc_html__( 'Hover', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'btn_swatch_text_color_hover',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-swatch-button:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_swatch_bg_color_hover',
			array(
				'label'     => esc_html__( 'Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-swatch-button:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_swatch_border_color_hover',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-swatch-button:hover' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_btn_swatch_active',
			array( 'label' => esc_html__( 'Active', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'btn_swatch_text_color_active',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-swatch-button.is-selected' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_swatch_bg_color_active',
			array(
				'label'     => esc_html__( 'Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-swatch-button.is-selected' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_swatch_border_color_active',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-swatch-button.is-selected' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// --- 5C. Image Variant Style ---
		$this->start_controls_section(
			'section_style_variations_image',
			array(
				'label'     => esc_html__( 'Image Variant Style', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_variations' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'img_swatch_width',
			array(
				'label'      => esc_html__( 'Swatch Width', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 400 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-swatch-image' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'img_swatch_height',
			array(
				'label'      => esc_html__( 'Swatch Height', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 400 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-swatch-image' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'img_swatch_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'default'    => array(
					'top'      => '4',
					'right'    => '4',
					'bottom'   => '4',
					'left'     => '4',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-swatch-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'img_swatch_gap',
			array(
				'label'      => esc_html__( 'Spacing / Gap', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-swatch-image' => 'margin-right: {{SIZE}}{{UNIT}}; margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_img_swatch_states' );

		$this->start_controls_tab(
			'tab_img_swatch_normal',
			array( 'label' => esc_html__( 'Normal', 'wc-smart-checkout-builder' ) )
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'img_swatch_border',
				'selector' => '{{WRAPPER}} .wcsc-swatch-image',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_img_swatch_hover',
			array( 'label' => esc_html__( 'Hover', 'wc-smart-checkout-builder' ) )
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'img_swatch_border_hover',
				'selector' => '{{WRAPPER}} .wcsc-swatch-image:hover',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_img_swatch_active',
			array( 'label' => esc_html__( 'Active', 'wc-smart-checkout-builder' ) )
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'img_swatch_border_active',
				'selector' => '{{WRAPPER}} .wcsc-swatch-image.is-selected',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// --- Section: Global Card / Block Styling ---
		$this->start_controls_section(
			'section_style_global_blocks',
			array(
				'label' => esc_html__( 'Global Card / Block Styling', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_global_card_container',
			array(
				'label' => esc_html__( 'Card / Block Container', 'wc-smart-checkout-builder' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'global_card_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block:not(.wcas-block-order-button)' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'global_card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block:not(.wcas-block-order-button)' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'global_card_border',
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block:not(.wcas-block-order-button)',
			)
		);

		$this->add_responsive_control(
			'global_card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block:not(.wcas-block-order-button)' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'global_card_box_shadow',
				'label'    => esc_html__( 'Box Shadow', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block:not(.wcas-block-order-button)',
			)
		);

		$this->add_responsive_control(
			'global_block_spacing',
			array(
				'label'      => esc_html__( 'Block Spacing / Gap', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'    => array(
					'size' => 24,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}}'                        => '--wcsc-row-gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcas-checkout-wrapper' => '--wcsc-row-gap: {{SIZE}}{{UNIT}}; gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcas-checkout-column'  => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'heading_global_card_title',
			array(
				'label'     => esc_html__( 'Block Title Styling', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'global_title_color',
			array(
				'label'     => esc_html__( 'Title Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block .wcas-block-title, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block .wcsc-section-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'global_title_typography',
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block .wcas-block-title, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block .wcsc-section-title',
			)
		);

		$this->add_responsive_control(
			'global_title_align',
			array(
				'label'     => esc_html__( 'Alignment', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'wc-smart-checkout-builder' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block .wcas-block-title, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block .wcsc-section-title' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'heading_global_title_border',
			array(
				'label'     => esc_html__( 'Title Border', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'global_title_border',
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block .wcas-block-title, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block .wcsc-section-title',
			)
		);

		$this->add_responsive_control(
			'global_title_border_spacing',
			array(
				'label'      => esc_html__( 'Spacing Below Title', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 60 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block .wcas-block-title, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block .wcsc-section-title' => 'margin-bottom: {{SIZE}}{{UNIT}}; padding-bottom: calc({{SIZE}}{{UNIT}} * 0.7);',
				),
			)
		);

		$this->end_controls_section();

		// --- 6. Checkout Section Style ---
		$this->start_controls_section(
			'section_style_checkout',
			array(
				'label' => esc_html__( 'Checkout Form & Table', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_checkout_titles',
			array(
				'label'     => esc_html__( 'Section Headings', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'checkout_heading_color',
			array(
				'label'     => esc_html__( 'Heading Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-section-title, {{WRAPPER}} .woocommerce-billing-fields h3, {{WRAPPER}} #order_review_heading' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'checkout_heading_typography',
				'selector' => '{{WRAPPER}} .wcsc-section-title, {{WRAPPER}} .woocommerce-billing-fields h3, {{WRAPPER}} #order_review_heading',
			)
		);

		// Order Review Table Styling
		$this->add_control(
			'heading_review_table_style',
			array(
				'label'     => esc_html__( 'Order Review Table & Rows', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'table_header_bg',
			array(
				'label'     => esc_html__( 'Table Header Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} table.shop_table th' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'table_header_color',
			array(
				'label'     => esc_html__( 'Table Header Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} table.shop_table th' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'subtotal_row_bg',
			array(
				'label'     => esc_html__( 'Subtotal Row Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} table.shop_table tfoot tr.cart-subtotal, {{WRAPPER}} table.shop_table tfoot tr.cart-subtotal th, {{WRAPPER}} table.shop_table tfoot tr.cart-subtotal td' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'subtotal_row_color',
			array(
				'label'     => esc_html__( 'Subtotal Row Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} table.shop_table tfoot tr.cart-subtotal th, {{WRAPPER}} table.shop_table tfoot tr.cart-subtotal td, {{WRAPPER}} table.shop_table tfoot tr.cart-subtotal .amount' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'total_row_bg',
			array(
				'label'     => esc_html__( 'Total Row Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} table.shop_table tfoot tr.order-total, {{WRAPPER}} table.shop_table tfoot tr.order-total th, {{WRAPPER}} table.shop_table tfoot tr.order-total td' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'total_row_color',
			array(
				'label'     => esc_html__( 'Total Row Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} table.shop_table tfoot tr.order-total th, {{WRAPPER}} table.shop_table tfoot tr.order-total td, {{WRAPPER}} table.shop_table tfoot tr.order-total .amount' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'table_border_color',
			array(
				'label'     => esc_html__( 'Table Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} table.shop_table, {{WRAPPER}} table.shop_table th, {{WRAPPER}} table.shop_table td' => 'border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_section();

		// --- Section: Shipping Cards Styling ---
		$this->start_controls_section(
			'section_style_shipping',
			array(
				'label'     => esc_html__( 'Shipping Cards', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_checkout_shipping' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'shipping_card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'      => '16',
					'right'    => '20',
					'bottom'   => '16',
					'left'     => '20',
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-shipping-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'shipping_card_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-shipping-card' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'shipping_card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'      => '8',
					'right'    => '8',
					'bottom'   => '8',
					'left'     => '8',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-shipping-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'shipping_card_gap',
			array(
				'label'      => esc_html__( 'Gap Between Cards', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-block-shipping ul#shipping_method' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'shipping_card_title_typography',
				'label'    => esc_html__( 'Title / Label Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcsc-shipping-card label',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'shipping_card_price_typography',
				'label'    => esc_html__( 'Price Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcsc-shipping-card label .amount',
			)
		);

		$this->start_controls_tabs( 'tabs_shipping_card_states' );

		// Normal State Tab
		$this->start_controls_tab(
			'tab_shipping_card_normal',
			array( 'label' => esc_html__( 'Normal', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'shipping_card_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'shipping_card_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'shipping_card_radio_accent',
			array(
				'label'     => esc_html__( 'Radio Accent Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card input[type="radio"]' => 'accent-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'shipping_card_title_color',
			array(
				'label'     => esc_html__( 'Title Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'shipping_card_price_color',
			array(
				'label'     => esc_html__( 'Price Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card label .amount' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		// Active State Tab
		$this->start_controls_tab(
			'tab_shipping_card_active',
			array( 'label' => esc_html__( 'Active', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'shipping_card_active_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card.is-active' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'shipping_card_active_border',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card.is-active' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'shipping_card_active_radio_accent',
			array(
				'label'     => esc_html__( 'Radio Accent Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card.is-active input[type="radio"]' => 'accent-color: {{VALUE}}; border-color: {{VALUE}} !important; background: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'shipping_card_active_title_color',
			array(
				'label'     => esc_html__( 'Title Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card.is-active label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'shipping_card_active_price_color',
			array(
				'label'     => esc_html__( 'Price Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card.is-active label .amount' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// --- Section: Order Now Button Styling ---
		$this->start_controls_section(
			'section_style_order_button',
			array(
				'label' => esc_html__( 'Order Now Button', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_order_button_style',
			array(
				'label'     => esc_html__( 'Order Now Button', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'order_button_typography',
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn, {{WRAPPER}} .wcsc-btn-text',
			)
		);

		$this->add_responsive_control(
			'order_button_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'order_button_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'order_button_width',
			array(
				'label'      => esc_html__( 'Width', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 1000 ),
					'%'  => array( 'min' => 0, 'max' => 100 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn' => 'width: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'order_button_height',
			array(
				'label'      => esc_html__( 'Height / Min Height', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array( 'min' => 30, 'max' => 120 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn' => 'min-height: {{SIZE}}{{UNIT}} !important; line-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'order_button_alignment',
			array(
				'label'   => esc_html__( 'Alignment', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'wc-smart-checkout-builder' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'wc-smart-checkout-builder' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'wc-smart-checkout-builder' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .form-row.place-order' => 'text-align: {{VALUE}};',
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'order_button_border',
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn',
			)
		);

		$this->add_responsive_control(
			'order_button_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array(
					'top'      => '4',
					'right'    => '4',
					'bottom'   => '4',
					'left'     => '4',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'order_button_box_shadow',
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn',
			)
		);

		$this->add_control(
			'order_button_beam_color',
			array(
				'label'     => esc_html__( 'Animation Color 1', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-btn-beam-top'    => 'background: linear-gradient(90deg, transparent, {{VALUE}}, transparent) !important;',
					'{{WRAPPER}} .wcsc-btn-beam-bottom' => 'background: linear-gradient(270deg, transparent, {{VALUE}}, transparent) !important;',
				),
				'condition' => array(
					'order_button_animation' => array( 'border_run' ),
				),
			)
		);

		$this->start_controls_tabs( 'tabs_order_button_states' );

		$this->start_controls_tab(
			'tab_order_button_normal',
			array( 'label' => esc_html__( 'Normal', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'order_button_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn, {{WRAPPER}} .wcsc-btn-price, {{WRAPPER}} .wcsc-btn-text' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'order_button_solid_bg',
			array(
				'label'     => esc_html__( 'Button Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn' => 'background-color: {{VALUE}} !important; --wcsc-order-btn-bg: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'order_button_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn',
			)
		);

		$this->add_control(
			'order_button_pulse_color',
			array(
				'label'     => esc_html__( 'Pulse / Glow Animation Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn, {{WRAPPER}} .wcsc-anim-pulse' => '--wcsc-order-btn-bg: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_order_button_hover',
			array( 'label' => esc_html__( 'Hover', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'order_button_text_color_hover',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order:hover, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn:hover, {{WRAPPER}} #place_order:hover, {{WRAPPER}} .wcsc-order-now-btn:hover, {{WRAPPER}} #place_order:hover .wcsc-btn-text, {{WRAPPER}} .wcsc-order-now-btn:hover .wcsc-btn-text' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'order_button_solid_bg_hover',
			array(
				'label'     => esc_html__( 'Button Color (Hover)', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order:hover, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn:hover, {{WRAPPER}} #place_order:hover, {{WRAPPER}} .wcsc-order-now-btn:hover' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'order_button_bg_hover',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order:hover, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn:hover, {{WRAPPER}} #place_order:hover, {{WRAPPER}} .wcsc-order-now-btn:hover',
			)
		);

		$this->add_control(
			'order_button_hover_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order:hover, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn:hover, {{WRAPPER}} #place_order:hover, {{WRAPPER}} .wcsc-order-now-btn:hover' => 'border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'order_button_hover_box_shadow',
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order:hover, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn:hover, {{WRAPPER}} #place_order:hover, {{WRAPPER}} .wcsc-order-now-btn:hover',
			)
		);

		$this->add_control(
			'order_button_hover_transition',
			array(
				'label'     => esc_html__( 'Transition Duration (s)', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array( 'min' => 0.1, 'max' => 2, 'step' => 0.1 ),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button #place_order, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-button .wcsc-order-now-btn, {{WRAPPER}} #place_order, {{WRAPPER}} .wcsc-order-now-btn' => 'transition: all {{SIZE}}s ease;',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$blocks = array(
			'checkout_form' => array(
				'label'          => esc_html__( 'Customer Info Block', 'wc-smart-checkout-builder' ),
				'selector'       => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-checkout-form',
				'title_selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-checkout-form .wcas-block-title',
			),
			'shipping'      => array(
				'label'          => esc_html__( 'Shipping Block', 'wc-smart-checkout-builder' ),
				'selector'       => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping',
				'title_selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping .wcas-block-title',
			),
			'order_summary' => array(
				'label'          => esc_html__( 'Order Review Block', 'wc-smart-checkout-builder' ),
				'selector'       => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-review',
				'title_selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-review .wcas-block-title',
			),
			'payment'       => array(
				'label'          => esc_html__( 'Payment Block', 'wc-smart-checkout-builder' ),
				'selector'       => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment',
				'title_selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment .wcas-block-title',
			),
		);

		foreach ( $blocks as $key => $block ) {
			$this->start_controls_section(
				'section_style_block_' . $key,
				array(
					'label' => $block['label'],
					'tab'   => Controls_Manager::TAB_STYLE,
				)
			);

			$this->add_group_control(
				Group_Control_Background::get_type(),
				array(
					'name'     => 'block_bg_' . $key,
					'label'    => esc_html__( 'Background', 'wc-smart-checkout-builder' ),
					'types'    => array( 'classic', 'gradient' ),
					'selector' => $block['selector'],
				)
			);

			$this->add_responsive_control(
				'block_padding_' . $key,
				array(
					'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', 'em', '%' ),
					'selectors'  => array(
						$block['selector'] => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				)
			);

			$this->add_responsive_control(
				'block_margin_' . $key,
				array(
					'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', 'em', '%' ),
					'selectors'  => array(
						$block['selector'] => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				)
			);

			$this->add_group_control(
				Group_Control_Border::get_type(),
				array(
					'name'     => 'block_border_' . $key,
					'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
					'selector' => $block['selector'],
				)
			);

			$this->add_responsive_control(
				'block_border_radius_' . $key,
				array(
					'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => array( 'px', '%' ),
					'selectors'  => array(
						$block['selector'] => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				)
			);

			$this->add_group_control(
				Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'block_box_shadow_' . $key,
					'label'    => esc_html__( 'Box Shadow', 'wc-smart-checkout-builder' ),
					'selector' => $block['selector'],
				)
			);

			$this->add_control(
				'heading_block_title_' . $key,
				array(
					'label'     => esc_html__( 'Title Styling Override', 'wc-smart-checkout-builder' ),
					'type'      => Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);

			$this->add_control(
				'block_title_color_' . $key,
				array(
					'label'     => esc_html__( 'Title Color', 'wc-smart-checkout-builder' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array(
						$block['title_selector'] => 'color: {{VALUE}};',
					),
				)
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => 'block_title_typography_' . $key,
					'label'    => esc_html__( 'Title Typography', 'wc-smart-checkout-builder' ),
					'selector' => $block['title_selector'],
				)
			);

			$this->add_group_control(
				Group_Control_Border::get_type(),
				array(
					'name'     => 'block_title_border_' . $key,
					'label'    => esc_html__( 'Title Border', 'wc-smart-checkout-builder' ),
					'selector' => $block['title_selector'],
				)
			);

			$this->end_controls_section();
		}

		// --- Section: Floating Button Styling ---
		$this->start_controls_section(
			'section_style_mobile_sticky_button',
			array(
				'label' => esc_html__( 'Floating Button', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'mobile_sticky_typography',
				'label'     => esc_html__( 'Typography', 'wc-smart-checkout-builder' ),
				'selector'  => '{{WRAPPER}} .wcsc-mobile-sticky-btn, {{WRAPPER}} .wcsc-mobile-sticky-btn .wcsc-sticky-text',
			)
		);

		$this->add_responsive_control(
			'mobile_sticky_height',
			array(
				'label'      => esc_html__( 'Height / Min Height', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array( 'min' => 35, 'max' => 90 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-mobile-sticky-btn' => 'min-height: {{SIZE}}{{UNIT}} !important; line-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'mobile_sticky_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-mobile-sticky-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'mobile_sticky_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-mobile-sticky-bar, {{WRAPPER}} .wcsc-mobile-sticky-btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'mobile_sticky_border',
				'selector'  => '{{WRAPPER}} .wcsc-mobile-sticky-btn',
			)
		);

		$this->add_responsive_control(
			'mobile_sticky_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-mobile-sticky-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'      => 'mobile_sticky_box_shadow',
				'selector'  => '{{WRAPPER}} .wcsc-mobile-sticky-bar, {{WRAPPER}} .wcsc-mobile-sticky-btn',
			)
		);

		$this->start_controls_tabs( 'tabs_mobile_sticky_states' );

		$this->start_controls_tab(
			'tab_mobile_sticky_normal',
			array( 'label' => esc_html__( 'Normal', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'mobile_sticky_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-mobile-sticky-btn, {{WRAPPER}} .wcsc-mobile-sticky-btn .wcsc-sticky-text' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'mobile_sticky_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .wcsc-mobile-sticky-btn',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_mobile_sticky_hover',
			array( 'label' => esc_html__( 'Hover', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'mobile_sticky_text_color_hover',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-mobile-sticky-btn:hover, {{WRAPPER}} .wcsc-mobile-sticky-btn:hover .wcsc-sticky-text' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'mobile_sticky_bg_hover',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .wcsc-mobile-sticky-btn:hover',
			)
		);

		$this->add_control(
			'mobile_sticky_hover_transition',
			array(
				'label'     => esc_html__( 'Transition Duration (s)', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array( 'min' => 0.1, 'max' => 2, 'step' => 0.1 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-mobile-sticky-btn' => 'transition: all {{SIZE}}s ease;',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// --- Section: Validation Error Modal Styling ---
		$this->start_controls_section(
			'section_style_phone_modal',
			array(
				'label' => esc_html__( 'Validation Error Modal Style', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'phone_modal_box_bg',
			array(
				'label'     => esc_html__( 'Modal Box Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-phone-modal-box' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'phone_modal_title_color',
			array(
				'label'     => esc_html__( 'Title Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-phone-modal-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'phone_modal_title_typography',
				'label'    => esc_html__( 'Title Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcsc-phone-modal-title',
			)
		);

		$this->add_control(
			'phone_modal_message_color',
			array(
				'label'     => esc_html__( 'Message Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-phone-modal-message' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'phone_modal_btn_bg',
			array(
				'label'     => esc_html__( 'Button Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-phone-modal-close-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'phone_modal_btn_color',
			array(
				'label'     => esc_html__( 'Button Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-phone-modal-close-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// --- 9. Checkout Fields Style ---
		$this->start_controls_section(
			'section_style_checkout_fields',
			array(
				'label' => esc_html__( 'Checkout Input Fields', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$checkout_input_sel = '{{WRAPPER}} .wcas-checkout-wrapper input.input-text, {{WRAPPER}} .wcas-checkout-wrapper input[type="text"], {{WRAPPER}} .wcas-checkout-wrapper input[type="tel"], {{WRAPPER}} .wcas-checkout-wrapper input[type="email"], {{WRAPPER}} .wcas-checkout-wrapper input[type="password"], {{WRAPPER}} .wcas-checkout-wrapper textarea, {{WRAPPER}} .wcas-checkout-wrapper select, {{WRAPPER}} .woocommerce form .form-row input.input-text, {{WRAPPER}} .woocommerce form .form-row input[type="text"], {{WRAPPER}} .woocommerce form .form-row input[type="tel"], {{WRAPPER}} .woocommerce form .form-row input[type="email"], {{WRAPPER}} .woocommerce form .form-row textarea, {{WRAPPER}} .woocommerce form .form-row select';

		$checkout_input_height_sel = '{{WRAPPER}} .wcas-checkout-wrapper input.input-text, {{WRAPPER}} .wcas-checkout-wrapper input[type="text"], {{WRAPPER}} .wcas-checkout-wrapper input[type="tel"], {{WRAPPER}} .wcas-checkout-wrapper input[type="email"], {{WRAPPER}} .wcas-checkout-wrapper select, {{WRAPPER}} .woocommerce form .form-row input.input-text, {{WRAPPER}} .woocommerce form .form-row input[type="text"], {{WRAPPER}} .woocommerce form .form-row input[type="tel"], {{WRAPPER}} .woocommerce form .form-row input[type="email"], {{WRAPPER}} .woocommerce form .form-row select';

		$checkout_input_focus_sel = '{{WRAPPER}} .wcas-checkout-wrapper input.input-text:focus, {{WRAPPER}} .wcas-checkout-wrapper input[type="text"]:focus, {{WRAPPER}} .wcas-checkout-wrapper input[type="tel"]:focus, {{WRAPPER}} .wcas-checkout-wrapper input[type="email"]:focus, {{WRAPPER}} .wcas-checkout-wrapper textarea:focus, {{WRAPPER}} .wcas-checkout-wrapper select:focus, {{WRAPPER}} .woocommerce form .form-row input.input-text:focus, {{WRAPPER}} .woocommerce form .form-row input[type="text"]:focus, {{WRAPPER}} .woocommerce form .form-row input[type="tel"]:focus, {{WRAPPER}} .woocommerce form .form-row input[type="email"]:focus, {{WRAPPER}} .woocommerce form .form-row textarea:focus, {{WRAPPER}} .woocommerce form .form-row select:focus';

		$checkout_input_placeholder_sel = '{{WRAPPER}} .wcas-checkout-wrapper input.input-text::placeholder, {{WRAPPER}} .wcas-checkout-wrapper input[type="text"]::placeholder, {{WRAPPER}} .wcas-checkout-wrapper input[type="tel"]::placeholder, {{WRAPPER}} .wcas-checkout-wrapper input[type="email"]::placeholder, {{WRAPPER}} .wcas-checkout-wrapper textarea::placeholder, {{WRAPPER}} .woocommerce form .form-row input.input-text::placeholder, {{WRAPPER}} .woocommerce form .form-row input[type="text"]::placeholder, {{WRAPPER}} .woocommerce form .form-row input[type="tel"]::placeholder, {{WRAPPER}} .woocommerce form .form-row input[type="email"]::placeholder, {{WRAPPER}} .woocommerce form .form-row textarea::placeholder';

		$checkout_input_error_sel = '{{WRAPPER}} .woocommerce form .form-row.woocommerce-invalid input.input-text, {{WRAPPER}} .woocommerce form .form-row.woocommerce-invalid textarea, {{WRAPPER}} .woocommerce form .form-row.woocommerce-invalid select, {{WRAPPER}} .woocommerce form.checkout input.input-text.wcsc-invalid, {{WRAPPER}} .woocommerce form.checkout textarea.wcsc-invalid, {{WRAPPER}} .woocommerce form.checkout select.wcsc-invalid, {{WRAPPER}} .wcas-checkout-wrapper .woocommerce-invalid input.input-text, {{WRAPPER}} .wcas-checkout-wrapper .woocommerce-invalid textarea, {{WRAPPER}} .wcas-checkout-wrapper .woocommerce-invalid select, {{WRAPPER}} .wcas-checkout-wrapper input.input-text.wcsc-invalid, {{WRAPPER}} .wcas-checkout-wrapper textarea.wcsc-invalid, {{WRAPPER}} .wcas-checkout-wrapper select.wcsc-invalid';

		$checkout_label_sel = '{{WRAPPER}} .woocommerce form .form-row label, {{WRAPPER}} .wcas-checkout-wrapper .form-row label, {{WRAPPER}} .wcas-checkout-wrapper label';

		$this->start_controls_tabs( 'tabs_checkout_fields_style' );

		// Normal Tab
		$this->start_controls_tab(
			'tab_fields_normal',
			array( 'label' => esc_html__( 'Normal', 'wc-smart-checkout-builder' ) )
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'checkout_fields_typography',
				'label'    => esc_html__( 'Typography', 'wc-smart-checkout-builder' ),
				'selector' => $checkout_input_sel,
			)
		);

		$this->add_responsive_control(
			'checkout_fields_height',
			array(
				'label'      => esc_html__( 'Input Height', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array( 'min' => 30, 'max' => 90 ),
				),
				'selectors'  => array(
					$checkout_input_height_sel => 'height: {{SIZE}}{{UNIT}} !important; line-height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'checkout_fields_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$checkout_input_sel => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'checkout_fields_placeholder_color',
			array(
				'label'     => esc_html__( 'Placeholder Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$checkout_input_placeholder_sel => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'checkout_fields_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$checkout_input_sel => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'checkout_fields_border',
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => $checkout_input_sel,
			)
		);

		$this->add_responsive_control(
			'checkout_fields_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					$checkout_input_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'checkout_fields_box_shadow',
				'label'    => esc_html__( 'Box Shadow', 'wc-smart-checkout-builder' ),
				'selector' => $checkout_input_sel,
			)
		);

		$this->add_responsive_control(
			'checkout_fields_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					$checkout_input_sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);
		
		$this->add_responsive_control(
			'checkout_fields_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					$checkout_input_sel => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_tab();

		// Focus Tab
		$this->start_controls_tab(
			'tab_fields_focus',
			array( 'label' => esc_html__( 'Focus', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'checkout_fields_focus_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$checkout_input_focus_sel => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'checkout_fields_focus_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$checkout_input_focus_sel => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'checkout_fields_active_border_color',
			array(
				'label'     => esc_html__( 'Active Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$checkout_input_focus_sel => 'border-color: {{VALUE}} !important; outline-color: {{VALUE}} !important; box-shadow: 0 0 0 1px {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'checkout_fields_focus_box_shadow',
				'label'    => esc_html__( 'Focus Box Shadow', 'wc-smart-checkout-builder' ),
				'selector' => $checkout_input_focus_sel,
			)
		);

		$this->end_controls_tab();

		// Error Tab
		$this->start_controls_tab(
			'tab_fields_error',
			array( 'label' => esc_html__( 'Error', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'checkout_fields_error_text_color',
			array(
				'label'     => esc_html__( 'Error Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$checkout_input_error_sel => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'checkout_fields_error_bg_color',
			array(
				'label'     => esc_html__( 'Error Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$checkout_input_error_sel => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'checkout_fields_error_border_color',
			array(
				'label'     => esc_html__( 'Error Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}}'             => '--wcsc-error-border-color: {{VALUE}} !important;',
					$checkout_input_error_sel => 'border-color: {{VALUE}} !important; box-shadow: 0 0 0 1px {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_tab();

		// Labels Tab
		$this->start_controls_tab(
			'tab_fields_labels',
			array( 'label' => esc_html__( 'Labels', 'wc-smart-checkout-builder' ) )
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'checkout_fields_label_typography',
				'label'    => esc_html__( 'Label Typography', 'wc-smart-checkout-builder' ),
				'selector' => $checkout_label_sel,
			)
		);

		$this->add_control(
			'checkout_fields_label_color',
			array(
				'label'     => esc_html__( 'Label Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$checkout_label_sel => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'checkout_fields_required_color',
			array(
				'label'     => esc_html__( 'Required Indicator (*) Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .woocommerce form .form-row label .required, {{WRAPPER}} .wcas-checkout-wrapper label .required' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'checkout_fields_label_spacing',
			array(
				'label'      => esc_html__( 'Label Spacing', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					$checkout_label_sel => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Plain content for Elementor search indexing and post saving.
	 * Overriding prevents calling checkout hooks and session during Elementor AJAX post save.
	 */
	public function render_plain_content() {
		$settings = $this->get_settings_for_display();
		$product  = Product_Handler::get_product_from_settings( $settings );

		if ( $product ) {
			echo '<h2>' . esc_html( $product->get_name() ) . '</h2>';
			echo '<div>' . wp_kses_post( $product->get_price_html() ) . '</div>';
		}
	}

	/**
	 * Render widget output on frontend and Elementor editor.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$product  = Product_Handler::get_product_from_settings( $settings );

		if ( ! $product ) {
			$is_elementor = false;
			if ( class_exists( '\Elementor\Plugin' ) ) {
				if ( ( isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) || ( isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) ) {
					$is_elementor = true;
				} elseif ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'elementor_ajax' === $_REQUEST['action'] ) {
					$is_elementor = true;
				}
			}

			if ( $is_elementor ) {
				?>
				<div class="wcsc-notice-warning">
					<p><?php esc_html_e( 'No product found. Please select a product from the widget panel.', 'wc-smart-checkout-builder' ); ?></p>
				</div>
				<?php
			}
			return;
		}

		$is_variable          = $product->is_type( 'variable' );
		$product_id           = $product->get_id();
		$show_title           = 'yes' === $settings['show_title'];
		$show_image           = 'yes' === $settings['show_image'];
		$show_price           = 'yes' === $settings['show_price'];
		$show_stock           = isset( $settings['show_stock'] ) ? 'yes' === $settings['show_stock'] : true;
		$show_quantity        = 'yes' === $settings['show_quantity'];
		$show_variations      = 'yes' === $settings['show_variations'] && $is_variable;


		$image_id             = $product->get_image_id();
		$image_url            = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : wc_placeholder_img_src( 'woocommerce_single' );
		$price_html           = $product->get_price_html();
		$attributes_data      = $is_variable ? Variation_Handler::get_product_attributes_data( $product, $settings ) : array();
		$variations_json_data = $is_variable ? Variation_Handler::get_available_variations_json( $product ) : array();

		// Custom text data attributes for client-side enforcement
		$data_attrs = array(
			'data-product-id'       => $product_id,
			'data-product-type'     => $product->get_type(),
			'data-default-price'    => wp_json_encode( $price_html ),
			'data-raw-price'        => wp_strip_all_tags( wc_price( $product->get_price() ) ),
			'data-txt-order'        => ! empty( $settings['order_review_heading_text'] ) ? $settings['order_review_heading_text'] : '',
			'data-txt-billing'      => ! empty( $settings['billing_heading_text'] ) ? $settings['billing_heading_text'] : '',
			'data-txt-product'      => ! empty( $settings['product_label_text'] ) ? $settings['product_label_text'] : '',
			'data-txt-subtotal'     => ! empty( $settings['subtotal_label_text'] ) ? $settings['subtotal_label_text'] : '',
			'data-txt-shipping'     => ! empty( $settings['shipping_label_text'] ) ? $settings['shipping_label_text'] : '',
			'data-txt-payment'      => ! empty( $settings['payment_heading_text'] ) ? $settings['payment_heading_text'] : '',
			'data-txt-total'        => ! empty( $settings['total_label_text'] ) ? $settings['total_label_text'] : '',
		);

		$data_string = '';
		foreach ( $data_attrs as $k => $v ) {
			if ( '' !== $v ) {
				$data_string .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
			}
		}
	$checkout_layout_val = ! empty( $settings['checkout_layout'] ) ? $settings['checkout_layout'] : '2_columns';
		$layout_class_name   = ( '1_column' === $checkout_layout_val ) ? 'wcsc-layout-1-col wcas-layout-one-column' : 'wcsc-layout-2-col wcas-layout-two-column';
		?>
		<div class="wcsc-product-checkout-widget <?php echo esc_attr( $layout_class_name ); ?>"<?php echo $data_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

			<?php if ( $show_image || $show_title || $show_price || $show_stock || $show_variations || $show_quantity ) : ?>
			<div class="wcsc-product-summary-section">
				<?php if ( $show_image ) : ?>
					<div class="wcsc-product-image">
						<img src="<?php echo esc_url( $image_url ); ?>" 
							alt="<?php echo esc_attr( $product->get_name() ); ?>" 
							class="wcsc-main-product-img"
							data-origin-src="<?php echo esc_url( $image_url ); ?>" />
					</div>
				<?php endif; ?>

				<?php if ( $show_title || $show_price || $show_stock || $show_variations || $show_quantity ) : ?>
				<div class="wcsc-product-details">
					<?php if ( $show_title ) : ?>
						<h2 class="wcsc-product-title"><?php echo esc_html( $product->get_name() ); ?></h2>
					<?php endif; ?>

					<?php if ( $show_price ) : ?>
						<div class="wcsc-product-price">
							<?php echo wp_kses_post( $price_html ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $show_stock ) : ?>
						<div class="wcsc-stock-status"></div>
					<?php endif; ?>

					<?php if ( $show_variations && ! empty( $attributes_data ) ) : ?>
						<div class="wcsc-variations-section">
							<?php foreach ( $attributes_data as $attr ) : 
								$attr_name   = $attr['name'];
								$attr_label  = $attr['label'];
								$disp_type   = $attr['display_type'];
								$default_val = $attr['default_value'];

								$custom_label = '';
								if ( ! empty( $settings['attribute_types'] ) && is_array( $settings['attribute_types'] ) ) {
									foreach ( $settings['attribute_types'] as $item ) {
										if ( ! empty( $item['attribute_name'] ) && ( strtolower( trim( $item['attribute_name'] ) ) === strtolower( trim( $attr_name ) ) || sanitize_title( $item['attribute_name'] ) === sanitize_title( $attr_name ) ) ) {
											if ( ! empty( $item['custom_label'] ) ) {
												$custom_label = trim( $item['custom_label'] );
											}
											break;
										}
									}
								}
								if ( empty( $custom_label ) && preg_match( '/\{+(?:value|selected_variation)\}+/i', $attr_label ) ) {
									$custom_label = $attr_label;
								}

								// Find initial default label for value replacement
								$default_label = '';
								if ( ! empty( $attr['options'] ) ) {
									if ( $default_val ) {
										foreach ( $attr['options'] as $opt ) {
											if ( $opt['value'] === $default_val ) {
												$default_label = $opt['label'];
												break;
											}
										}
									}
									if ( empty( $default_label ) && isset( $attr['options'][0]['label'] ) ) {
										$default_label = $attr['options'][0]['label'];
									}
								}

								$label_template_attr = $custom_label ? ' data-label-template="' . esc_attr( $custom_label ) . '"' : '';
								$has_placeholder     = $custom_label && preg_match( '/\{+(?:value|selected_variation)\}+/i', $custom_label );

								if ( $has_placeholder ) {
									$display_label = preg_replace( '/\{+(?:value|selected_variation)\}+/i', $default_label, $custom_label );
								} elseif ( $custom_label ) {
									$display_label = $custom_label;
								} else {
									$display_label = $attr_label;
								}
							?>
								<div class="wcsc-attribute-group" data-attribute-name="<?php echo esc_attr( $attr_name ); ?>" data-display-type="<?php echo esc_attr( $disp_type ); ?>">
									<div class="wcsc-attribute-header"<?php echo $label_template_attr; ?>>
										<span class="wcsc-attribute-label"><?php echo esc_html( $display_label ); ?><?php echo ( $custom_label || empty( $attr_label ) ) ? '' : ':'; ?></span>
										<span class="wcsc-selected-value-label"><?php echo ( ! $custom_label && $default_label ) ? esc_html( $default_label ) : ''; ?></span>
									</div>

									<div class="wcsc-options-container wcsc-type-<?php echo esc_attr( $disp_type ); ?>">
										<?php foreach ( $attr['options'] as $opt ) : 
											$is_active  = ( $default_val && $default_val === $opt['value'] );
											$active_cls = $is_active ? ' is-selected' : '';
										?>
											<?php if ( 'image' === $disp_type ) : ?>
												<button type="button" 
													class="wcsc-swatch wcsc-swatch-image<?php echo esc_attr( $active_cls ); ?>" 
													data-value="<?php echo esc_attr( $opt['value'] ); ?>"
													data-label="<?php echo esc_attr( $opt['label'] ); ?>"
													title="<?php echo esc_attr( $opt['label'] ); ?>">
													<?php if ( ! empty( $opt['image_url'] ) ) : ?>
														<img src="<?php echo esc_url( $opt['image_url'] ); ?>" alt="<?php echo esc_attr( $opt['label'] ); ?>" />
													<?php elseif ( ! empty( $opt['fallback_color'] ) ) : ?>
														<span class="wcsc-swatch-color-fallback" style="background-color: <?php echo esc_attr( $opt['fallback_color'] ); ?>;"></span>
													<?php else : ?>
														<span class="wcsc-swatch-text-fallback"><?php echo esc_html( $opt['label'] ); ?></span>
													<?php endif; ?>
												</button>
											<?php else : ?>
												<button type="button" 
													class="wcsc-swatch wcsc-swatch-button<?php echo esc_attr( $active_cls ); ?>" 
													data-value="<?php echo esc_attr( $opt['value'] ); ?>"
													data-label="<?php echo esc_attr( $opt['label'] ); ?>">
													<?php echo esc_html( $opt['label'] ); ?>
												</button>
											<?php endif; ?>
										<?php endforeach; ?>
									</div>
									<input type="hidden" class="wcsc-attribute-input" name="attribute_<?php echo esc_attr( sanitize_title( $attr_name ) ); ?>" value="<?php echo esc_attr( $default_val ); ?>" />
								</div>
							<?php endforeach; ?>
						</div>

						<!-- Pass Variations JSON Data securely for client-side instant matching -->
						<script type="application/json" class="wcsc-variations-data">
							<?php echo wp_json_encode( $variations_json_data ); ?>
						</script>
					<?php endif; ?>

					<?php if ( $show_quantity ) : ?>
						<div class="wcsc-quantity">
							<span class="wcsc-quantity-label"><?php esc_html_e( 'Quantity:', 'wc-smart-checkout-builder' ); ?></span>
							<div class="wcsc-quantity-wrapper">
								<button type="button" class="wcsc-qty-btn wcsc-qty-minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'wc-smart-checkout-builder' ); ?>">&minus;</button>
								<input type="number" class="qty wcsc-qty-input" step="1" min="1" max="999" name="quantity" value="1" inputmode="numeric" />
								<button type="button" class="wcsc-qty-btn wcsc-qty-plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'wc-smart-checkout-builder' ); ?>">&plus;</button>
							</div>
						</div>
					<?php else : ?>
						<input type="hidden" class="wcsc-qty-input" name="quantity" value="1" />
					<?php endif; ?>

				</div>
				<?php else: ?>
					<?php if ( ! $show_quantity ) : ?>
						<input type="hidden" class="wcsc-qty-input" name="quantity" value="1" />
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<?php else: ?>
				<?php if ( ! $show_quantity ) : ?>
					<input type="hidden" class="wcsc-qty-input" name="quantity" value="1" />
				<?php endif; ?>
			<?php endif; ?>

			<div class="wcsc-checkout-section">
				<?php Checkout_Handler::render_checkout_section( $product, $settings ); ?>
			</div>

		</div>
		<?php
	}
}
