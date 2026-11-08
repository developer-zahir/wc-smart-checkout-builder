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

		$default_mode = ( 'wcsc_bundle_checkout' === $this->get_name() ) ? 'bundle' : 'single';

		$this->add_control(
			'checkout_mode',
			array(
				'label'   => esc_html__( 'Checkout Mode', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => $default_mode,
				'options' => array(
					'single' => esc_html__( 'Single Product / Variation', 'wc-smart-checkout-builder' ),
					'bundle' => esc_html__( 'Multi-Product Bundle & Tiered Discounts', 'wc-smart-checkout-builder' ),
				),
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
				'condition'    => array(
					'checkout_mode' => 'single',
				),
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
					'checkout_mode'        => 'single',
					'use_current_product!' => 'yes',
				),
			)
		);

		$this->add_control(
			'bundle_products',
			array(
				'label'       => esc_html__( 'Select Bundle Products', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => Product_Handler::get_product_options(),
				'description' => esc_html__( 'Choose products (simple or variable) to include in this bundle grid.', 'wc-smart-checkout-builder' ),
				'label_block' => true,
				'condition'   => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_layout',
			array(
				'label'          => esc_html__( 'Layout Type', 'wc-smart-checkout-builder' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => 'grid',
				'tablet_default' => 'grid',
				'mobile_default' => 'list',
				'options'        => array(
					'grid'  => esc_html__( 'Grid (Top Image, Bottom Content)', 'wc-smart-checkout-builder' ),
					'list'  => esc_html__( 'List (Left Image, Right Content)', 'wc-smart-checkout-builder' ),
					'table' => esc_html__( 'Compact Table / Quick Select', 'wc-smart-checkout-builder' ),
				),
				'prefix_class'   => 'wcsc-bundle-layout%s-',
				'render_type'    => 'template',
				'condition'      => array(
					'checkout_mode' => 'bundle',
				),
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
					'{{WRAPPER}} .wcsc-bundle-container.wcsc-bundle-layout-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
					'{{WRAPPER}} .wcsc-bundle-grid'                              => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
					'{{WRAPPER}} .wcsc-bundle-container'                         => '--wcsc-bundle-cols: {{VALUE}};',
				),
				'condition'      => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_gap',
			array(
				'label'      => esc_html__( 'Items Gap / Spacing', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 16,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-container' => 'gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcsc-bundle-grid'      => 'gap: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_control(
			'bundle_min_items',
			array(
				'label'       => esc_html__( 'Minimum Items Required', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 1,
				'min'         => 1,
				'condition'   => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_control(
			'bundle_max_items',
			array(
				'label'       => esc_html__( 'Maximum Items Allowed (0 = Unlimited)', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'condition'   => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_control(
			'show_bundle_badge',
			array(
				'label'        => esc_html__( 'Show Selected Badge', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_control(
			'bundle_badge_style',
			array(
				'label'   => esc_html__( 'Selected Badge Style', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'pill',
				'options' => array(
					'pill'   => esc_html__( 'Floating Pill Badge', 'wc-smart-checkout-builder' ),
					'ribbon' => esc_html__( 'Top-Right Corner Rotated Ribbon', 'wc-smart-checkout-builder' ),
				),
				'condition' => array(
					'checkout_mode'     => 'bundle',
					'show_bundle_badge' => 'yes',
				),
			)
		);

		$this->add_control(
			'bundle_badge_position',
			array(
				'label'   => esc_html__( 'Pill Badge Position', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'top_right',
				'options' => array(
					'top_right' => esc_html__( 'Top Right', 'wc-smart-checkout-builder' ),
					'top_left'  => esc_html__( 'Top Left', 'wc-smart-checkout-builder' ),
				),
				'condition' => array(
					'checkout_mode'      => 'bundle',
					'show_bundle_badge'  => 'yes',
					'bundle_badge_style' => 'pill',
				),
			)
		);

		$this->add_control(
			'bundle_badge_text',
			array(
				'label'       => esc_html__( 'Selected Badge Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'SELECTED', 'wc-smart-checkout-builder' ),
				'condition'   => array(
					'checkout_mode'     => 'bundle',
					'show_bundle_badge' => 'yes',
				),
			)
		);

		$this->add_control(
			'bundle_select_btn_text',
			array(
				'label'       => esc_html__( 'Select Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Select', 'wc-smart-checkout-builder' ),
				'condition'   => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_control(
			'bundle_selected_btn_text',
			array(
				'label'       => esc_html__( 'Selected Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Selected', 'wc-smart-checkout-builder' ),
				'condition'   => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->end_controls_section();

		// --- Section: Tiered Discounts (Bundle Mode) ---
		$this->start_controls_section(
			'section_bundle_discounts',
			array(
				'label'     => esc_html__( 'Tiered Quantity Discounts', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_control(
			'enable_tiered_discount',
			array(
				'label'        => esc_html__( 'Enable Tiered Discounts', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$bundle_repeater = new Repeater();

		$bundle_repeater->add_control(
			'min_qty',
			array(
				'label'   => esc_html__( 'Minimum Items (Qty)', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 2,
				'min'     => 1,
			)
		);

		$bundle_repeater->add_control(
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

		$bundle_repeater->add_control(
			'discount_amount',
			array(
				'label'   => esc_html__( 'Discount Value', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 0,
			)
		);

		$bundle_repeater->add_control(
			'tier_badge',
			array(
				'label'   => esc_html__( 'Tier Badge (Optional)', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( '10% OFF', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'discount_tiers',
			array(
				'label'       => esc_html__( 'Discount Rules', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $bundle_repeater->get_controls(),
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

		// --- Section: Summary Bar (Bundle Mode) ---
		$this->start_controls_section(
			'section_bundle_summary',
			array(
				'label'     => esc_html__( 'Summary Bar & CTA', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'checkout_mode' => 'bundle',
				),
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
			'bundle_cta_button_text',
			array(
				'label'       => esc_html__( 'CTA Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Proceed to Checkout', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'heading_summary_timer',
			array(
				'label'     => esc_html__( 'Countdown Urgency Timer', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'enable_summary_timer',
			array(
				'label'        => esc_html__( 'Enable Countdown Timer', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'no',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'summary_timer_label',
			array(
				'label'       => esc_html__( 'Timer Title / Urgency Label', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Hurry! Special offer expires in:', 'wc-smart-checkout-builder' ),
				'condition'   => array(
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_control(
			'summary_timer_hours',
			array(
				'label'       => esc_html__( 'Duration Hours', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'max'         => 72,
				'condition'   => array(
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_control(
			'summary_timer_minutes',
			array(
				'label'       => esc_html__( 'Duration Minutes', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 15,
				'min'         => 0,
				'max'         => 59,
				'condition'   => array(
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_control(
			'summary_timer_seconds',
			array(
				'label'       => esc_html__( 'Duration Seconds', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'max'         => 59,
				'condition'   => array(
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_control(
			'summary_timer_loop',
			array(
				'label'        => esc_html__( 'Auto-Repeat Timer When Expired', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'When countdown reaches 00:00, it automatically loops and restarts the countdown.', 'wc-smart-checkout-builder' ),
				'condition'    => array(
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// --- Section: Display Elements (Show/Hide) ---
		$this->start_controls_section(
			'section_display_elements',
			array(
				'label'     => esc_html__( 'Display Elements', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'checkout_mode' => 'single',
				),
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
					'checkout_mode'   => 'single',
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

		// --- Section: Layout ---
		$this->start_controls_section(
			'section_checkout_layout',
			array(
				'label' => esc_html__( 'Layout', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'checkout_layout',
			array(
				'label'   => esc_html__( 'Layout', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'1_column'  => esc_html__( '1 Column', 'wc-smart-checkout-builder' ),
					'2_columns' => esc_html__( '2 Columns', 'wc-smart-checkout-builder' ),
				),
				'default' => '2_columns',
			)
		);

		$this->add_responsive_control(
			'checkout_grid_columns',
			array(
				'label'           => esc_html__( 'Column Ratio', 'wc-smart-checkout-builder' ),
				'type'            => Controls_Manager::SELECT,
				'options'         => array(
					'1fr 1fr'                                  => esc_html__( '50% / 50% (Equal)', 'wc-smart-checkout-builder' ),
					'60% calc(40% - var(--wcsc-col-gap, 20px))' => esc_html__( '60% / 40%', 'wc-smart-checkout-builder' ),
					'65% calc(35% - var(--wcsc-col-gap, 20px))' => esc_html__( '65% / 35%', 'wc-smart-checkout-builder' ),
					'70% calc(30% - var(--wcsc-col-gap, 20px))' => esc_html__( '70% / 30%', 'wc-smart-checkout-builder' ),
					'calc(40% - var(--wcsc-col-gap, 20px)) 60%' => esc_html__( '40% / 60%', 'wc-smart-checkout-builder' ),
					'1fr'                                      => esc_html__( '1 Column (Stacked)', 'wc-smart-checkout-builder' ),
				),
				'desktop_default' => '1fr 1fr',
				'tablet_default'  => '1fr',
				'mobile_default'  => '1fr',
				'default'         => '1fr 1fr',
				'condition'       => array(
					'checkout_layout' => '2_columns',
				),
				'selectors'       => array(
					'{{WRAPPER}} .wcas-layout-two-column.wcas-checkout-wrapper, {{WRAPPER}} .wcas-layout-two-column .wcas-checkout-wrapper, {{WRAPPER}} .wcsc-layout-2-col.wcas-checkout-wrapper' => 'grid-template-columns: {{VALUE}} !important;',
				),
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
				'default'    => array(
					'size' => 20,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-product-checkout-widget' => '--wcsc-col-gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcas-checkout-wrapper'        => '--wcsc-col-gap: {{SIZE}}{{UNIT}}; column-gap: {{SIZE}}{{UNIT}} !important;',
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
				'default'    => array(
					'size' => 20,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-product-checkout-widget' => '--wcsc-row-gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcas-checkout-wrapper'        => '--wcsc-row-gap: {{SIZE}}{{UNIT}}; row-gap: {{SIZE}}{{UNIT}} !important;',
					'{{WRAPPER}} .wcas-checkout-column'         => 'gap: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_control(
			'heading_block_column_assignments',
			array(
				'label'     => esc_html__( 'Column', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'checkout_layout' => '2_columns',
				),
			)
		);

		$this->add_control(
			'customer_info_column',
			array(
				'label'     => esc_html__( 'Customer Info', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'col_1' => esc_html__( 'Column 1', 'wc-smart-checkout-builder' ),
					'col_2' => esc_html__( 'Column 2', 'wc-smart-checkout-builder' ),
				),
				'default'   => 'col_1',
				'condition' => array(
					'checkout_layout' => '2_columns',
				),
			)
		);

		$this->add_control(
			'shipping_block_column',
			array(
				'label'     => esc_html__( 'Shipping Methods', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'col_1' => esc_html__( 'Column 1', 'wc-smart-checkout-builder' ),
					'col_2' => esc_html__( 'Column 2', 'wc-smart-checkout-builder' ),
				),
				'default'   => 'col_1',
				'condition' => array(
					'checkout_layout'        => '2_columns',
					'show_checkout_shipping' => 'yes',
				),
			)
		);

		$this->add_control(
			'order_review_column',
			array(
				'label'     => esc_html__( 'Order Review', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'col_1' => esc_html__( 'Column 1', 'wc-smart-checkout-builder' ),
					'col_2' => esc_html__( 'Column 2', 'wc-smart-checkout-builder' ),
				),
				'default'   => 'col_2',
				'condition' => array(
					'checkout_layout'            => '2_columns',
					'show_checkout_order_review' => 'yes',
				),
			)
		);

		$this->add_control(
			'payment_block_column',
			array(
				'label'     => esc_html__( 'Payment', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'col_1' => esc_html__( 'Column 1', 'wc-smart-checkout-builder' ),
					'col_2' => esc_html__( 'Column 2', 'wc-smart-checkout-builder' ),
				),
				'default'   => 'col_2',
				'condition' => array(
					'checkout_layout'       => '2_columns',
					'show_checkout_payment' => 'yes',
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
		// --- Section: Checkout Section Header ---
		$this->start_controls_section(
			'section_checkout_header',
			array(
				'label' => esc_html__( 'Checkout Section Header', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_checkout_header',
			array(
				'label'        => esc_html__( 'Show Checkout Header', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'checkout_header_title',
			array(
				'label'       => esc_html__( 'Header Title', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'অর্ডার করুন', 'wc-smart-checkout-builder' ),
				'label_block' => true,
				'condition'   => array(
					'show_checkout_header' => 'yes',
				),
			)
		);

		$this->add_control(
			'checkout_header_desc',
			array(
				'label'       => esc_html__( 'Header Description', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'অর্ডার করতে নিচের ফর্মটি সঠিক ভাবে পূরণ করুন , আমাদের একজন প্রতিনিদি আপনাকে ফোন করে অর্ডারটি কনফার্ম করবেন।', 'wc-smart-checkout-builder' ),
				'rows'        => 3,
				'label_block' => true,
				'condition'   => array(
					'show_checkout_header' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'checkout_header_align',
			array(
				'label'     => esc_html__( 'Alignment', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
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
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-checkout-header' => 'text-align: {{VALUE}};',
				),
				'condition' => array(
					'show_checkout_header' => 'yes',
				),
			)
		);

		$this->end_controls_section();

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
					'left_column'  => esc_html__( 'Column 1', 'wc-smart-checkout-builder' ),
					'right_column' => esc_html__( 'Column 2', 'wc-smart-checkout-builder' ),
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
				'default'     => esc_html__( 'প্রয়োজনীয় তথ্য পূরণ করুন', 'wc-smart-checkout-builder' ),
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
				'default'     => esc_html__( 'অনুগ্রহ করে নিচের তথ্যগুলো সঠিকভাবে প্রদান করুন:', 'wc-smart-checkout-builder' ),
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
				'default'     => esc_html__( 'অর্ডার সম্পূর্ণ করুন', 'wc-smart-checkout-builder' ),
				'condition'   => array(
					'enable_mobile_sticky_button' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// --- Section: Order Bump ---
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
				'render_type'  => 'template',
				'description'  => esc_html__( 'Add special upsell / bump products directly inside the checkout page.', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'order_bump_query_type',
			array(
				'label'       => esc_html__( 'Query Type', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'manual',
				'options'     => array(
					'manual'      => esc_html__( 'Manual Selection', 'wc-smart-checkout-builder' ),
					'related'     => esc_html__( 'Related Products', 'wc-smart-checkout-builder' ),
					'bestselling' => esc_html__( 'Best Selling Products', 'wc-smart-checkout-builder' ),
				),
				'render_type' => 'template',
				'condition'   => array(
					'enable_order_bump' => 'yes',
				),
			)
		);

		$this->add_control(
			'order_bump_product_limit',
			array(
				'label'       => esc_html__( 'Product Limit', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 4,
				'step'        => 1,
				'default'     => 2,
				'render_type' => 'template',
				'condition'   => array(
					'enable_order_bump' => 'yes',
				),
				'description' => esc_html__( 'Maximum number of products to offer (1-4).', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'order_bump_products',
			array(
				'label'       => esc_html__( 'Offer Products', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => Product_Handler::get_bump_product_options(),
				'default'     => array(),
				'render_type' => 'template',
				'condition'   => array(
					'enable_order_bump'     => 'yes',
					'order_bump_query_type' => 'manual',
				),
				'description' => esc_html__( 'Select up to 4 products to offer during checkout.', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_responsive_control(
			'order_bump_layout',
			array(
				'label'           => esc_html__( 'Layout', 'wc-smart-checkout-builder' ),
				'type'            => Controls_Manager::SELECT,
				'desktop_default' => 'list',
				'tablet_default'  => 'list',
				'mobile_default'  => 'list',
				'default'         => 'list',
				'options'         => array(
					'list' => esc_html__( 'List', 'wc-smart-checkout-builder' ),
					'grid' => esc_html__( 'Grid', 'wc-smart-checkout-builder' ),
				),
				'prefix_class'    => 'wcsc-order-bump%s-',
				'render_type'     => 'template',
				'condition'       => array(
					'enable_order_bump' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_columns',
			array(
				'label'           => esc_html__( 'Columns', 'wc-smart-checkout-builder' ),
				'type'            => Controls_Manager::SELECT,
				'desktop_default' => '1',
				'tablet_default'  => '1',
				'mobile_default'  => '1',
				'default'         => '1',
				'options'         => array(
					'1' => esc_html__( '1 Column', 'wc-smart-checkout-builder' ),
					'2' => esc_html__( '2 Columns', 'wc-smart-checkout-builder' ),
					'3' => esc_html__( '3 Columns', 'wc-smart-checkout-builder' ),
					'4' => esc_html__( '4 Columns', 'wc-smart-checkout-builder' ),
				),
				'prefix_class'    => 'wcsc-order-bump-cols%s-',
				'render_type'     => 'template',
				'selectors'       => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-order-bump-list' => 'display: grid !important; grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr)) !important;',
				),
				'condition'       => array(
					'enable_order_bump' => 'yes',
				),
			)
		);

		$this->add_control(
			'order_bump_section_title',
			array(
				'label'       => esc_html__( 'Section Title', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'ধামাকা অফার! সাথে এটাও যুক্ত করুন', 'wc-smart-checkout-builder' ),
				'render_type' => 'template',
				'condition'   => array(
					'enable_order_bump' => 'yes',
				),
			)
		);

		$this->add_control(
			'order_bump_section_subtitle',
			array(
				'label'       => esc_html__( 'Section Subtitle / Short Description', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => '',
				'placeholder' => esc_html__( 'এক ক্লিকে আপনার বিশেষ ছাড়ে প্রোডাক্টটি যুক্ত করুন', 'wc-smart-checkout-builder' ),
				'render_type' => 'template',
				'condition'   => array(
					'enable_order_bump' => 'yes',
				),
			)
		);

		$this->add_control(
			'order_bump_action_text',
			array(
				'label'       => esc_html__( 'Action Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'অর্ডার যুক্ত করুন', 'wc-smart-checkout-builder' ),
				'render_type' => 'template',
				'condition'   => array(
					'enable_order_bump' => 'yes',
				),
			)
		);

		$this->end_controls_section();


	}

	/**
	 * Style tab controls.
	 */
	protected function register_style_controls() {
		// --- Section: Checkout Section & Header Style ---
		$this->start_controls_section(
			'section_style_checkout_section',
			array(
				'label' => esc_html__( 'Checkout Section & Header', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_checkout_box_style',
			array(
				'label' => esc_html__( 'Checkout Section Box', 'wc-smart-checkout-builder' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'checkout_section_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-checkout-section' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'checkout_section_border',
				'selector' => '{{WRAPPER}} .wcsc-checkout-section',
			)
		);

		$this->add_responsive_control(
			'checkout_section_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-checkout-section' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'checkout_section_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-checkout-section' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'checkout_section_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-checkout-section' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'checkout_section_shadow',
				'selector' => '{{WRAPPER}} .wcsc-checkout-section',
			)
		);

		$this->add_control(
			'heading_checkout_header_style',
			array(
				'label'     => esc_html__( 'Header Title & Description', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'checkout_header_bg',
			array(
				'label'     => esc_html__( 'Header Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-checkout-header' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'checkout_header_padding',
			array(
				'label'      => esc_html__( 'Header Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-checkout-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'checkout_header_margin_bottom',
			array(
				'label'      => esc_html__( 'Header Bottom Spacing', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'default'    => array( 'unit' => 'px', 'size' => 24 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-checkout-header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'checkout_header_divider',
			array(
				'label'        => esc_html__( 'Show Bottom Divider Line', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'checkout_header_divider_color',
			array(
				'label'     => esc_html__( 'Divider Line Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e2e8f0',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-checkout-header.has-divider' => 'border-bottom-color: {{VALUE}};',
				),
				'condition' => array(
					'checkout_header_divider' => 'yes',
				),
			)
		);

		$this->add_control(
			'checkout_header_title_color',
			array(
				'label'     => esc_html__( 'Title Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1e293b',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-checkout-header-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'checkout_header_title_typography',
				'selector' => '{{WRAPPER}} .wcsc-checkout-header-title',
			)
		);

		$this->add_responsive_control(
			'checkout_header_title_margin',
			array(
				'label'      => esc_html__( 'Title Bottom Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'default'    => array( 'unit' => 'px', 'size' => 8 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-checkout-header-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'checkout_header_desc_color',
			array(
				'label'     => esc_html__( 'Description Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#64748b',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-checkout-header-desc' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'checkout_header_desc_typography',
				'selector' => '{{WRAPPER}} .wcsc-checkout-header-desc',
			)
		);

		$this->add_responsive_control(
			'checkout_header_desc_margin',
			array(
				'label'      => esc_html__( 'Description Bottom Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'default'    => array( 'unit' => 'px', 'size' => 0 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-checkout-header-desc' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// =========================================================================
		// 1. Variation Swatches (Includes sub-controls for Variant Button Style & Image Variant Style)
		// =========================================================================
		$this->start_controls_section(
			'section_style_variations',
			array(
				'label'     => esc_html__( 'Variation Swatches', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'checkout_mode'   => 'single',
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

		$this->add_control(
			'heading_variant_button_style',
			array(
				'label'     => esc_html__( 'Variant Button Style', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
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
		
				$this->add_group_control(
					Group_Control_Border::get_type(),
					array(
						'name'     => 'btn_swatch_border_normal',
						'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
						'selector' => '{{WRAPPER}} .wcsc-swatch-button',
					)
				);

				$this->add_responsive_control(
					'btn_swatch_radius_normal',
					array(
						'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
						'type'       => Controls_Manager::DIMENSIONS,
						'size_units' => array( 'px', 'em', '%' ),
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
		
				$this->add_group_control(
					Group_Control_Border::get_type(),
					array(
						'name'     => 'btn_swatch_border_hover',
						'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
						'selector' => '{{WRAPPER}} .wcsc-swatch-button:hover',
					)
				);

				$this->add_responsive_control(
					'btn_swatch_radius_hover',
					array(
						'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
						'type'       => Controls_Manager::DIMENSIONS,
						'size_units' => array( 'px', 'em', '%' ),
						'selectors'  => array(
							'{{WRAPPER}} .wcsc-swatch-button:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
		
				$this->add_group_control(
					Group_Control_Border::get_type(),
					array(
						'name'     => 'btn_swatch_border_active',
						'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
						'selector' => '{{WRAPPER}} .wcsc-swatch-button.is-selected',
					)
				);

				$this->add_responsive_control(
					'btn_swatch_radius_active',
					array(
						'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
						'type'       => Controls_Manager::DIMENSIONS,
						'size_units' => array( 'px', 'em', '%' ),
						'selectors'  => array(
							'{{WRAPPER}} .wcsc-swatch-button.is-selected' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						),
					)
				);
		
				$this->end_controls_tab();
		
				$this->end_controls_tabs();

		$this->add_control(
			'heading_variant_image_style',
			array(
				'label'     => esc_html__( 'Image Variant Style', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
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

		// =========================================================================
		// 2. Customer Info Block
		// =========================================================================
		$this->start_controls_section(
			'section_style_block_checkout_form',
			array(
				'label' => esc_html__( 'Customer Info Block', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->register_block_container_controls(
			'checkout_form',
			'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-checkout-form',
			'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-checkout-form .wcas-block-title'
		);

		$checkout_input_sel = '{{WRAPPER}} .wcas-checkout-wrapper input.input-text, {{WRAPPER}} .wcas-checkout-wrapper input[type="text"], {{WRAPPER}} .wcas-checkout-wrapper input[type="tel"], {{WRAPPER}} .wcas-checkout-wrapper input[type="email"], {{WRAPPER}} .wcas-checkout-wrapper input[type="password"], {{WRAPPER}} .wcas-checkout-wrapper textarea, {{WRAPPER}} .wcas-checkout-wrapper select, {{WRAPPER}} .woocommerce form .form-row input.input-text, {{WRAPPER}} .woocommerce form .form-row input[type="text"], {{WRAPPER}} .woocommerce form .form-row input[type="tel"], {{WRAPPER}} .woocommerce form .form-row input[type="email"], {{WRAPPER}} .woocommerce form .form-row textarea, {{WRAPPER}} .woocommerce form .form-row select';
		
				$checkout_input_height_sel = '{{WRAPPER}} .wcas-checkout-wrapper input.input-text, {{WRAPPER}} .wcas-checkout-wrapper input[type="text"], {{WRAPPER}} .wcas-checkout-wrapper input[type="tel"], {{WRAPPER}} .wcas-checkout-wrapper input[type="email"], {{WRAPPER}} .wcas-checkout-wrapper select, {{WRAPPER}} .woocommerce form .form-row input.input-text, {{WRAPPER}} .woocommerce form .form-row input[type="text"], {{WRAPPER}} .woocommerce form .form-row input[type="tel"], {{WRAPPER}} .woocommerce form .form-row input[type="email"], {{WRAPPER}} .woocommerce form .form-row select';
		
				$checkout_input_focus_sel = '{{WRAPPER}} .wcas-checkout-wrapper input.input-text:focus, {{WRAPPER}} .wcas-checkout-wrapper input[type="text"]:focus, {{WRAPPER}} .wcas-checkout-wrapper input[type="tel"]:focus, {{WRAPPER}} .wcas-checkout-wrapper input[type="email"]:focus, {{WRAPPER}} .wcas-checkout-wrapper textarea:focus, {{WRAPPER}} .wcas-checkout-wrapper select:focus, {{WRAPPER}} .woocommerce form .form-row input.input-text:focus, {{WRAPPER}} .woocommerce form .form-row input[type="text"]:focus, {{WRAPPER}} .woocommerce form .form-row input[type="tel"]:focus, {{WRAPPER}} .woocommerce form .form-row input[type="email"]:focus, {{WRAPPER}} .woocommerce form .form-row textarea:focus, {{WRAPPER}} .woocommerce form .form-row select:focus';
		
				$checkout_input_placeholder_sel = '{{WRAPPER}} .wcas-checkout-wrapper input.input-text::placeholder, {{WRAPPER}} .wcas-checkout-wrapper input[type="text"]::placeholder, {{WRAPPER}} .wcas-checkout-wrapper input[type="tel"]::placeholder, {{WRAPPER}} .wcas-checkout-wrapper input[type="email"]::placeholder, {{WRAPPER}} .wcas-checkout-wrapper textarea::placeholder, {{WRAPPER}} .woocommerce form .form-row input.input-text::placeholder, {{WRAPPER}} .woocommerce form .form-row input[type="text"]::placeholder, {{WRAPPER}} .woocommerce form .form-row input[type="tel"]::placeholder, {{WRAPPER}} .woocommerce form .form-row input[type="email"]::placeholder, {{WRAPPER}} .woocommerce form .form-row textarea::placeholder';
		
				$checkout_input_error_sel = '{{WRAPPER}} .woocommerce form .form-row.woocommerce-invalid input.input-text, {{WRAPPER}} .woocommerce form .form-row.woocommerce-invalid textarea, {{WRAPPER}} .woocommerce form .form-row.woocommerce-invalid select, {{WRAPPER}} .woocommerce form.checkout input.input-text.wcsc-invalid, {{WRAPPER}} .woocommerce form.checkout textarea.wcsc-invalid, {{WRAPPER}} .woocommerce form.checkout select.wcsc-invalid, {{WRAPPER}} .wcas-checkout-wrapper .woocommerce-invalid input.input-text, {{WRAPPER}} .wcas-checkout-wrapper .woocommerce-invalid textarea, {{WRAPPER}} .wcas-checkout-wrapper .woocommerce-invalid select, {{WRAPPER}} .wcas-checkout-wrapper input.input-text.wcsc-invalid, {{WRAPPER}} .wcas-checkout-wrapper textarea.wcsc-invalid, {{WRAPPER}} .wcas-checkout-wrapper select.wcsc-invalid';
		
				$checkout_label_sel = '{{WRAPPER}} .woocommerce form .form-row label, {{WRAPPER}} .wcas-checkout-wrapper .form-row label, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-checkout-form label, {{WRAPPER}} .wcas-checkout-wrapper #customer_details label, {{WRAPPER}} .wcas-checkout-wrapper label';
		
				// --- Dedicated Field Labels Controls ---
				$this->add_control(
					'heading_checkout_field_labels',
					array(
						'label'     => esc_html__( 'Field Labels', 'wc-smart-checkout-builder' ),
						'type'      => Controls_Manager::HEADING,
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
		
				$this->add_group_control(
					Group_Control_Typography::get_type(),
					array(
						'name'     => 'checkout_fields_label_typography',
						'label'    => esc_html__( 'Label Typography', 'wc-smart-checkout-builder' ),
						'selector' => $checkout_label_sel,
					)
				);
		
				$this->add_control(
					'checkout_fields_required_color',
					array(
						'label'     => esc_html__( 'Required Indicator Color', 'wc-smart-checkout-builder' ),
						'type'      => Controls_Manager::COLOR,
						'selectors' => array(
							'{{WRAPPER}} .woocommerce form .form-row label .required, {{WRAPPER}} .wcas-checkout-wrapper label .required, {{WRAPPER}} .wcas-checkout-wrapper label abbr.required' => 'color: {{VALUE}} !important;',
						),
					)
				);
		
				$this->add_responsive_control(
					'checkout_fields_label_spacing',
					array(
						'label'      => esc_html__( 'Label Bottom Spacing', 'wc-smart-checkout-builder' ),
						'type'       => Controls_Manager::SLIDER,
						'size_units' => array( 'px', 'em' ),
						'selectors'  => array(
							$checkout_label_sel => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
						),
					)
				);
		
				// --- Input Field Controls ---
				$this->add_control(
					'heading_checkout_inputs',
					array(
						'label'     => esc_html__( 'Input Fields', 'wc-smart-checkout-builder' ),
						'type'      => Controls_Manager::HEADING,
						'separator' => 'before',
					)
				);
		
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
		
				$this->end_controls_tabs();

		$this->end_controls_section();

		// =========================================================================
		// 3. Shipping Block
		// =========================================================================
		$this->start_controls_section(
			'section_style_block_shipping',
			array(
				'label'     => esc_html__( 'Shipping Block', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_checkout_shipping' => 'yes',
				),
			)
		);

		$this->register_block_container_controls(
			'shipping',
			'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping',
			'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping .wcas-block-title'
		);

		$this->end_controls_section();

		// =========================================================================
		// 4. Shipping Card
		// =========================================================================
		$this->start_controls_section(
			'section_style_shipping',
			array(
				'label'     => esc_html__( 'Shipping Card', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_checkout_shipping' => 'yes',
				),
			)
		);

		$shipping_card_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li.wcsc-shipping-card, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li, {{WRAPPER}} .wcsc-shipping-card';
		$shipping_card_hover_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li.wcsc-shipping-card:hover, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li:hover, {{WRAPPER}} .wcsc-shipping-card:hover';
		$shipping_card_active_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li.wcsc-shipping-card.is-active, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li.is-active, {{WRAPPER}} .wcsc-shipping-card.is-active';
		$shipping_radio_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li.wcsc-shipping-card > input[type="radio"], {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li input[type="radio"], {{WRAPPER}} .wcsc-shipping-card input[type="radio"]';
		$shipping_radio_hover_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li.wcsc-shipping-card:hover > input[type="radio"], {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li:hover input[type="radio"], {{WRAPPER}} .wcsc-shipping-card:hover input[type="radio"]';
		$shipping_radio_active_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li.wcsc-shipping-card.is-active > input[type="radio"], {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li.wcsc-shipping-card > input[type="radio"]:checked, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li.is-active input[type="radio"], {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li input[type="radio"]:checked, {{WRAPPER}} .wcsc-shipping-card.is-active input[type="radio"], {{WRAPPER}} .wcsc-shipping-card input[type="radio"]:checked';
		$shipping_radio_dot_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li.wcsc-shipping-card.is-active > input[type="radio"]::after, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li.wcsc-shipping-card > input[type="radio"]:checked::after, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li.is-active input[type="radio"]::after, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-shipping ul#shipping_method li input[type="radio"]:checked::after, {{WRAPPER}} .wcsc-shipping-card.is-active input[type="radio"]::after, {{WRAPPER}} .wcsc-shipping-card input[type="radio"]:checked::after';

		$this->add_responsive_control(
			'shipping_card_min_height',
			array(
				'label'      => esc_html__( 'Min Height', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'vh' ),
				'range'      => array(
					'px' => array( 'min' => 20, 'max' => 150 ),
				),
				'selectors'  => array(
					$shipping_card_sel => 'min-height: {{SIZE}}{{UNIT}} !important; display: flex !important; align-items: center !important;',
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
					$shipping_card_sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'shipping_card_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					$shipping_card_sel => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
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
					'{{WRAPPER}} .wcas-block-shipping ul#shipping_method' => 'gap: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'shipping_card_radio_size',
			array(
				'label'      => esc_html__( 'Radio Outer Size', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 12, 'max' => 36 ),
				),
				'selectors'  => array(
					'{{WRAPPER}}'       => '--wcsc-shipping-radio-size: {{SIZE}}{{UNIT}};',
					$shipping_radio_sel => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important; min-width: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'shipping_card_radio_dot_size',
			array(
				'label'      => esc_html__( 'Radio Dot Size', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 4, 'max' => 20 ),
				),
				'selectors'  => array(
					'{{WRAPPER}}'           => '--wcsc-shipping-radio-dot: {{SIZE}}{{UNIT}};',
					$shipping_radio_dot_sel => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'shipping_card_title_typography',
				'label'    => esc_html__( 'Title / Label Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcsc-shipping-card label, {{WRAPPER}} .wcsc-shipping-card label .amount, {{WRAPPER}} .wcas-block-shipping ul#shipping_method li label, {{WRAPPER}} .wcas-block-shipping ul#shipping_method li label .amount',
			)
		);

		$this->start_controls_tabs( 'tabs_shipping_card_states' );

		// Normal State Tab
		$this->start_controls_tab(
			'tab_shipping_card_normal',
			array( 'label' => esc_html__( 'Normal', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'shipping_card_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card label, {{WRAPPER}} .wcsc-shipping-card label .amount, {{WRAPPER}} .wcas-block-shipping ul#shipping_method li label, {{WRAPPER}} .wcas-block-shipping ul#shipping_method li label .amount' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'shipping_card_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$shipping_card_sel => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'shipping_card_border_normal',
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => $shipping_card_sel,
			)
		);

		$this->add_responsive_control(
			'shipping_card_radius_normal',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'      => '4',
					'right'    => '4',
					'bottom'   => '4',
					'left'     => '4',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					$shipping_card_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_tab();

		// Hover State Tab
		$this->start_controls_tab(
			'tab_shipping_card_hover',
			array( 'label' => esc_html__( 'Hover', 'wc-smart-checkout-builder' ) )
		);

		$this->add_control(
			'shipping_card_hover_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card:hover label, {{WRAPPER}} .wcsc-shipping-card:hover label .amount, {{WRAPPER}} .wcas-block-shipping ul#shipping_method li:hover label, {{WRAPPER}} .wcas-block-shipping ul#shipping_method li:hover label .amount' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'shipping_card_hover_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$shipping_card_hover_sel => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}}'            => '--wcsc-shipping-hover-bg: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'shipping_card_border_hover',
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => $shipping_card_hover_sel,
			)
		);

		$this->add_responsive_control(
			'shipping_card_radius_hover',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					$shipping_card_hover_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
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
			'shipping_card_active_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-shipping-card.is-active label, {{WRAPPER}} .wcsc-shipping-card.is-active label .amount, {{WRAPPER}} .wcas-block-shipping ul#shipping_method li.is-active label, {{WRAPPER}} .wcas-block-shipping ul#shipping_method li.is-active label .amount' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'shipping_card_active_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$shipping_card_active_sel => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}}'             => '--wcsc-shipping-active-bg: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'shipping_card_border_active',
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => $shipping_card_active_sel,
			)
		);

		$this->add_responsive_control(
			'shipping_card_radius_active',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					$shipping_card_active_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// =========================================================================
		// 5. Order Review Block
		// =========================================================================
		$this->start_controls_section(
			'section_style_block_order_summary',
			array(
				'label'     => esc_html__( 'Order Review Block', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_checkout_order_review' => 'yes',
				),
			)
		);

		// Outer Block Container & Section Heading
		$this->register_block_container_controls(
			'order_summary',
			'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-review, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-summary',
			'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-review .wcas-block-title, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-order-summary .wcas-block-title, {{WRAPPER}} .wcas-checkout-wrapper #order_review_heading, {{WRAPPER}} #order_review_heading'
		);

		// ==========================================
		// Table Header (thead)
		// ==========================================
		// Table Header
		// ==========================================
		$this->add_control(
			'heading_table_header_style',
			array(
				'label'     => esc_html__( 'Table Header', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'table_header_bg',
			array(
				'label'     => esc_html__( 'Header Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table thead, {{WRAPPER}} .wcas-block-order-review table.shop_table thead tr, {{WRAPPER}} .wcas-block-order-review table.shop_table thead th, {{WRAPPER}} table.shop_table thead, {{WRAPPER}} table.shop_table thead tr, {{WRAPPER}} table.shop_table thead th' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'table_header_color',
			array(
				'label'     => esc_html__( 'Header Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table thead th, {{WRAPPER}} table.shop_table thead th' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'table_header_typography',
				'label'    => esc_html__( 'Header Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcas-block-order-review table.shop_table thead th, {{WRAPPER}} table.shop_table thead th',
			)
		);

		// ==========================================
		// Table Footer
		// ==========================================
		$this->add_control(
			'heading_table_footer_style',
			array(
				'label'     => esc_html__( 'Table Footer', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'table_footer_bg',
			array(
				'label'     => esc_html__( 'Footer Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table tfoot, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot th, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot td, {{WRAPPER}} table.shop_table tfoot, {{WRAPPER}} table.shop_table tfoot tr, {{WRAPPER}} table.shop_table tfoot th, {{WRAPPER}} table.shop_table tfoot td' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'table_footer_color',
			array(
				'label'     => esc_html__( 'Footer Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table tfoot th, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot td, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot .amount, {{WRAPPER}} table.shop_table tfoot th, {{WRAPPER}} table.shop_table tfoot td, {{WRAPPER}} table.shop_table tfoot .amount' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'table_footer_typography',
				'label'    => esc_html__( 'Footer Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcas-block-order-review table.shop_table tfoot th, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot td, {{WRAPPER}} table.shop_table tfoot th, {{WRAPPER}} table.shop_table tfoot td',
			)
		);

		// ==========================================
		// Individual Rows: Product Rows
		// ==========================================
		$this->add_control(
			'heading_product_rows_style',
			array(
				'label'     => esc_html__( 'Product Rows', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'product_row_bg',
			array(
				'label'     => esc_html__( 'Product Row Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table tbody tr.cart_item, {{WRAPPER}} .wcas-block-order-review table.shop_table tbody tr.cart_item td, {{WRAPPER}} table.shop_table tbody tr.cart_item, {{WRAPPER}} table.shop_table tbody tr.cart_item td' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'product_row_color',
			array(
				'label'     => esc_html__( 'Product Row Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table tbody tr.cart_item td, {{WRAPPER}} .wcas-block-order-review table.shop_table tbody tr.cart_item .product-name, {{WRAPPER}} .wcas-block-order-review table.shop_table tbody tr.cart_item .product-total, {{WRAPPER}} .wcas-block-order-review table.shop_table tbody tr.cart_item .amount, {{WRAPPER}} table.shop_table tbody tr.cart_item td, {{WRAPPER}} table.shop_table tbody tr.cart_item .amount' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'product_row_typography',
				'label'    => esc_html__( 'Product Row Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcas-block-order-review table.shop_table tbody tr.cart_item td, {{WRAPPER}} table.shop_table tbody tr.cart_item td',
			)
		);

		// ==========================================
		// Individual Rows: Subtotal Row
		// ==========================================
		$this->add_control(
			'heading_subtotal_row_style',
			array(
				'label'     => esc_html__( 'Subtotal Row', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'subtotal_row_bg',
			array(
				'label'     => esc_html__( 'Subtotal Row Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.cart-subtotal, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.cart-subtotal th, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.cart-subtotal td, {{WRAPPER}} table.shop_table tfoot tr.cart-subtotal, {{WRAPPER}} table.shop_table tfoot tr.cart-subtotal th, {{WRAPPER}} table.shop_table tfoot tr.cart-subtotal td' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'subtotal_row_color',
			array(
				'label'     => esc_html__( 'Subtotal Row Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.cart-subtotal th, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.cart-subtotal td, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.cart-subtotal .amount, {{WRAPPER}} table.shop_table tfoot tr.cart-subtotal th, {{WRAPPER}} table.shop_table tfoot tr.cart-subtotal td, {{WRAPPER}} table.shop_table tfoot tr.cart-subtotal .amount' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'subtotal_row_typography',
				'label'    => esc_html__( 'Subtotal Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.cart-subtotal th, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.cart-subtotal td, {{WRAPPER}} table.shop_table tfoot tr.cart-subtotal th, {{WRAPPER}} table.shop_table tfoot tr.cart-subtotal td',
			)
		);

		// ==========================================
		// Individual Rows: Shipping Row
		// ==========================================
		$this->add_control(
			'heading_shipping_row_style',
			array(
				'label'     => esc_html__( 'Shipping Row', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'shipping_row_bg',
			array(
				'label'     => esc_html__( 'Shipping Row Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.shipping, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.woocommerce-shipping-totals, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.wcas-order-review-shipping, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.shipping th, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.shipping td, {{WRAPPER}} table.shop_table tfoot tr.shipping, {{WRAPPER}} table.shop_table tfoot tr.woocommerce-shipping-totals, {{WRAPPER}} table.shop_table tfoot tr.shipping th, {{WRAPPER}} table.shop_table tfoot tr.shipping td' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'shipping_row_color',
			array(
				'label'     => esc_html__( 'Shipping Row Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.shipping th, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.shipping td, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.shipping .amount, {{WRAPPER}} table.shop_table tfoot tr.shipping th, {{WRAPPER}} table.shop_table tfoot tr.shipping td, {{WRAPPER}} table.shop_table tfoot tr.shipping .amount' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'shipping_row_typography',
				'label'    => esc_html__( 'Shipping Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.shipping th, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.shipping td, {{WRAPPER}} table.shop_table tfoot tr.shipping th, {{WRAPPER}} table.shop_table tfoot tr.shipping td',
			)
		);

		// ==========================================
		// Individual Rows: Total Row
		// ==========================================
		$this->add_control(
			'heading_total_row_style',
			array(
				'label'     => esc_html__( 'Total Row', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'total_row_bg',
			array(
				'label'     => esc_html__( 'Total Row Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.order-total, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.order-total th, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.order-total td, {{WRAPPER}} table.shop_table tfoot tr.order-total, {{WRAPPER}} table.shop_table tfoot tr.order-total th, {{WRAPPER}} table.shop_table tfoot tr.order-total td' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'total_row_color',
			array(
				'label'     => esc_html__( 'Total Row Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.order-total th, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.order-total td, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.order-total .amount, {{WRAPPER}} table.shop_table tfoot tr.order-total th, {{WRAPPER}} table.shop_table tfoot tr.order-total td, {{WRAPPER}} table.shop_table tfoot tr.order-total .amount' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'total_row_typography',
				'label'    => esc_html__( 'Total Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.order-total th, {{WRAPPER}} .wcas-block-order-review table.shop_table tfoot tr.order-total td, {{WRAPPER}} table.shop_table tfoot tr.order-total th, {{WRAPPER}} table.shop_table tfoot tr.order-total td',
			)
		);

		// ==========================================
		// Row Dividers & Table Borders
		// ==========================================
		$this->add_control(
			'heading_table_dividers_style',
			array(
				'label'     => esc_html__( 'Row Dividers & Table Borders', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'table_border_color',
			array(
				'label'     => esc_html__( 'Table Outer Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table, {{WRAPPER}} table.shop_table' => 'border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'table_row_divider_color',
			array(
				'label'     => esc_html__( 'Row Divider Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table th, {{WRAPPER}} .wcas-block-order-review table.shop_table td, {{WRAPPER}} .wcas-block-order-review table.shop_table tr, {{WRAPPER}} table.shop_table th, {{WRAPPER}} table.shop_table td' => 'border-bottom-color: {{VALUE}} !important; border-top-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'table_row_divider_width',
			array(
				'label'      => esc_html__( 'Row Divider Thickness', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 10,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-block-order-review table.shop_table th, {{WRAPPER}} .wcas-block-order-review table.shop_table td, {{WRAPPER}} table.shop_table th, {{WRAPPER}} table.shop_table td' => 'border-bottom-width: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);		$this->end_controls_section();

		// =========================================================================
		// 6. Payment Block
		// =========================================================================
		$this->start_controls_section(
			'section_style_block_payment',
			array(
				'label'     => esc_html__( 'Payment Block', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_checkout_payment' => 'yes',
				),
			)
		);

		// Outer Block Container & Title Override
		$this->register_block_container_controls(
			'payment',
			'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment',
			'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment .wcas-block-title, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment .wcsc-payment-heading'
		);

		$payment_item_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method, {{WRAPPER}} .wcas-checkout-wrapper ul.payment_methods li.wc_payment_method, {{WRAPPER}} ul.payment_methods li.wc_payment_method, {{WRAPPER}} .wcas-block-payment ul.payment_methods li';
		$payment_item_hover_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method:hover, {{WRAPPER}} .wcas-checkout-wrapper ul.payment_methods li.wc_payment_method:hover, {{WRAPPER}} ul.payment_methods li.wc_payment_method:hover, {{WRAPPER}} .wcas-block-payment ul.payment_methods li:hover';
		$payment_item_active_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method:has(> input[type="radio"]:checked), {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method.is-active, {{WRAPPER}} ul.payment_methods li.wc_payment_method:has(> input[type="radio"]:checked), {{WRAPPER}} ul.payment_methods li.wc_payment_method.is-active, {{WRAPPER}} .wcas-block-payment ul.payment_methods li.is-active';
		$payment_label_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method label, {{WRAPPER}} ul.payment_methods li.wc_payment_method label, {{WRAPPER}} .wcas-block-payment ul.payment_methods li label';
		$payment_label_hover_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method:hover label, {{WRAPPER}} .wcas-checkout-wrapper ul.payment_methods li.wc_payment_method:hover label, {{WRAPPER}} .wcas-block-payment ul.payment_methods li:hover label';
		$payment_label_active_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method > input[type="radio"]:checked ~ label, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method.is-active label, {{WRAPPER}} ul.payment_methods li.wc_payment_method.is-active label, {{WRAPPER}} .wcas-block-payment ul.payment_methods li.is-active label';
		$payment_radio_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method > input[type="radio"], {{WRAPPER}} ul.payment_methods li.wc_payment_method input[type="radio"], {{WRAPPER}} .wcas-block-payment ul.payment_methods li input[type="radio"]';
		$payment_radio_hover_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method:hover > input[type="radio"], {{WRAPPER}} ul.payment_methods li.wc_payment_method:hover input[type="radio"], {{WRAPPER}} .wcas-block-payment ul.payment_methods li:hover input[type="radio"]';
		$payment_radio_active_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method > input[type="radio"]:checked, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method.is-active > input[type="radio"], {{WRAPPER}} ul.payment_methods li.wc_payment_method input[type="radio"]:checked, {{WRAPPER}} .wcas-block-payment ul.payment_methods li.is-active input[type="radio"]';
		$payment_radio_dot_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method > input[type="radio"]:checked::after, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.wc_payment_method.is-active > input[type="radio"]::after, {{WRAPPER}} ul.payment_methods li.wc_payment_method input[type="radio"]:checked::after, {{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment ul.payment_methods li.is-active input[type="radio"]::after';
		$payment_box_sel = '{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment div.payment_box, {{WRAPPER}} div.payment_box';

		// ==========================================
		// Payment Method Cards / Items
		// ==========================================
		$this->add_control(
			'heading_payment_method_items',
			array(
				'label'     => esc_html__( 'Payment Method Cards', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'payment_method_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					$payment_item_sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'payment_method_margin',
			array(
				'label'      => esc_html__( 'Spacing / Margin Between Methods', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					$payment_item_sel => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'payment_method_border',
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => $payment_item_sel,
			)
		);

		$this->add_responsive_control(
			'payment_method_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					$payment_item_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'payment_method_box_shadow',
				'label'    => esc_html__( 'Box Shadow', 'wc-smart-checkout-builder' ),
				'selector' => $payment_item_sel,
			)
		);

		$this->add_responsive_control(
			'payment_radio_size',
			array(
				'label'      => esc_html__( 'Radio Outer Size', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 12, 'max' => 36 ),
				),
				'selectors'  => array(
					'{{WRAPPER}}'       => '--wcsc-payment-radio-size: {{SIZE}}{{UNIT}};',
					$payment_radio_sel => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important; min-width: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'payment_radio_dot_size',
			array(
				'label'      => esc_html__( 'Radio Dot Size', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 4, 'max' => 20 ),
				),
				'selectors'  => array(
					'{{WRAPPER}}'           => '--wcsc-payment-radio-dot: {{SIZE}}{{UNIT}};',
					$payment_radio_dot_sel => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'payment_label_typography',
				'label'    => esc_html__( 'Label Typography', 'wc-smart-checkout-builder' ),
				'selector' => $payment_label_sel,
			)
		);

		$this->start_controls_tabs( 'tabs_payment_method_state' );

		// Normal State Tab
		$this->start_controls_tab(
			'tab_payment_method_normal',
			array(
				'label' => esc_html__( 'Normal', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'payment_method_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_item_sel => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}}'     => '--wcsc-payment-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'payment_method_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_item_sel => 'border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'payment_label_color',
			array(
				'label'     => esc_html__( 'Text / Label Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_label_sel => 'color: {{VALUE}} !important;',
					'{{WRAPPER}}'      => '--wcsc-payment-label-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'payment_radio_border_color',
			array(
				'label'     => esc_html__( 'Radio Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}}'       => '--wcsc-payment-radio-border: {{VALUE}};',
					$payment_radio_sel => 'border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'payment_radio_bg_color',
			array(
				'label'     => esc_html__( 'Radio Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}}'       => '--wcsc-payment-radio-bg: {{VALUE}};',
					$payment_radio_sel => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_tab();

		// Hover State Tab
		$this->start_controls_tab(
			'tab_payment_method_hover',
			array(
				'label' => esc_html__( 'Hover', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'payment_method_hover_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_item_hover_sel => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}}'           => '--wcsc-payment-hover-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'payment_method_hover_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_item_hover_sel => 'border-color: {{VALUE}} !important;',
					'{{WRAPPER}}'           => '--wcsc-payment-hover-border: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'payment_method_hover_text_color',
			array(
				'label'     => esc_html__( 'Text / Label Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_label_hover_sel => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'payment_radio_hover_border_color',
			array(
				'label'     => esc_html__( 'Radio Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_radio_hover_sel => 'border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'payment_radio_hover_bg_color',
			array(
				'label'     => esc_html__( 'Radio Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_radio_hover_sel => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_tab();

		// Selected / Active State Tab
		$this->start_controls_tab(
			'tab_payment_method_active',
			array(
				'label' => esc_html__( 'Active', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'payment_method_active_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_item_active_sel => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}}'            => '--wcsc-payment-active-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'payment_method_active_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_item_active_sel => 'border-color: {{VALUE}} !important;',
					'{{WRAPPER}}'            => '--wcsc-payment-active-border: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'payment_label_active_color',
			array(
				'label'     => esc_html__( 'Text / Label Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_label_active_sel => 'color: {{VALUE}} !important;',
					'{{WRAPPER}}'             => '--wcsc-payment-active-label-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'payment_radio_checked_color',
			array(
				'label'     => esc_html__( 'Radio Selected Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}}'              => '--wcsc-payment-radio-checked: {{VALUE}};',
					$payment_radio_active_sel => 'border-color: {{VALUE}} !important; background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'payment_radio_checked_dot_color',
			array(
				'label'     => esc_html__( 'Checked Dot Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}}'           => '--wcsc-payment-radio-dot-color: {{VALUE}};',
					$payment_radio_dot_sel => 'background-color: {{VALUE}} !important; background: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'payment_method_active_box_shadow',
				'label'    => esc_html__( 'Active Box Shadow', 'wc-smart-checkout-builder' ),
				'selector' => $payment_item_active_sel,
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		// ==========================================
		// Payment Description Box (.payment_box)
		// ==========================================
		$this->add_control(
			'heading_payment_box_style',
			array(
				'label'     => esc_html__( 'Payment Description Box', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'payment_box_bg',
			array(
				'label'     => esc_html__( 'Box Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_box_sel => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'payment_box_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$payment_box_sel => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .wcas-checkout-wrapper .wcas-block-payment div.payment_box p' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'payment_box_typography',
				'label'    => esc_html__( 'Typography', 'wc-smart-checkout-builder' ),
				'selector' => $payment_box_sel . ', ' . $payment_box_sel . ' p',
			)
		);

		$this->add_responsive_control(
			'payment_box_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					$payment_box_sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'payment_box_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					$payment_box_sel => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'payment_box_border',
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => $payment_box_sel,
			)
		);

		$this->add_responsive_control(
			'payment_box_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					$payment_box_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);		$this->end_controls_section();

		// =========================================================================
		// 7. Order Bump Block
		// =========================================================================
		$this->start_controls_section(
			'section_style_order_bump',
			array(
				'label'     => esc_html__( 'Order Bump Block', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'enable_order_bump' => 'yes',
				),
			)
		);

		// Block Spacing (Margin & Padding)
		$this->add_responsive_control(
			'order_bump_block_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-block' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_block_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-block' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'order_bump_block_bg',
				'label'    => esc_html__( 'Background', 'wc-smart-checkout-builder' ),
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .wcsc-order-bump-block',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'order_bump_block_border',
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcsc-order-bump-block',
			)
		);

		$this->add_responsive_control(
			'order_bump_block_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-block' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'order_bump_block_box_shadow',
				'label'    => esc_html__( 'Box Shadow', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcsc-order-bump-block',
			)
		);

		// Container Sizing & Alignment
		$this->add_control(
			'heading_order_bump_container_style',
			array(
				'label'     => esc_html__( 'Container', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'order_bump_container_width',
			array(
				'label'      => esc_html__( 'Container Width / Max-Width', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min' => 150,
						'max' => 1200,
					),
					'%'  => array(
						'min' => 20,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-order-bump-container' => 'max-width: {{SIZE}}{{UNIT}} !important; width: 100%;',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_container_alignment',
			array(
				'label'                => esc_html__( 'Container Alignment', 'wc-smart-checkout-builder' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => array(
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
				'default'              => 'left',
				'selectors'            => array(
					'{{WRAPPER}} .wcas-order-bump-container' => '{{VALUE}}',
				),
				'selectors_dictionary' => array(
					'left'   => 'margin-left: 0 !important; margin-right: auto !important;',
					'center' => 'margin-left: auto !important; margin-right: auto !important;',
					'right'  => 'margin-left: auto !important; margin-right: 0 !important;',
				),
			)
		);

		// Section Heading
		$this->add_control(
			'heading_order_bump_title_style',
			array(
				'label'     => esc_html__( 'Section Heading', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'order_bump_title_align',
			array(
				'label'     => esc_html__( 'Text Alignment', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
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
					'{{WRAPPER}} .wcsc-order-bump-heading' => 'text-align: {{VALUE}} !important; justify-content: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'order_bump_title_typography',
				'label'    => esc_html__( 'Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcsc-order-bump-heading',
			)
		);

		$this->add_control(
			'order_bump_title_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-heading' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_title_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-heading' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_title_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-heading' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'order_bump_title_border',
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcsc-order-bump-heading',
			)
		);

		$this->add_responsive_control(
			'order_bump_title_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-heading' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		// ==========================================
		// Section Subtitle
		// ==========================================
		$this->add_control(
			'heading_order_bump_subtitle_style',
			array(
				'label'     => esc_html__( 'Section Subtitle', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'order_bump_section_subtitle!' => '',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_subtitle_align',
			array(
				'label'     => esc_html__( 'Text Alignment', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
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
					'{{WRAPPER}} .wcsc-order-bump-subtitle' => 'text-align: {{VALUE}} !important; justify-content: {{VALUE}} !important;',
				),
				'condition' => array(
					'order_bump_section_subtitle!' => '',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'order_bump_subtitle_typography',
				'label'     => esc_html__( 'Typography', 'wc-smart-checkout-builder' ),
				'selector'  => '{{WRAPPER}} .wcsc-order-bump-subtitle',
				'condition' => array(
					'order_bump_section_subtitle!' => '',
				),
			)
		);

		$this->add_control(
			'order_bump_subtitle_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-subtitle' => 'color: {{VALUE}} !important;',
				),
				'condition' => array(
					'order_bump_section_subtitle!' => '',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_subtitle_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-subtitle' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
				'condition'  => array(
					'order_bump_section_subtitle!' => '',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_subtitle_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-subtitle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
				'condition'  => array(
					'order_bump_section_subtitle!' => '',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_title_subtitle_gap',
			array(
				'label'      => esc_html__( 'Gap / Spacing Between Title & Subtitle', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-heading' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				),
				'condition'  => array(
					'order_bump_section_subtitle!' => '',
				),
			)
		);

		// Offer Card Container
		$this->add_control(
			'heading_order_bump_card_style',
			array(
				'label'     => esc_html__( 'Offer Card Container', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'order_bump_card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_card_margin',
			array(
				'label'      => esc_html__( 'Spacing Between Cards', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-card' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'order_bump_card_border',
				'label'    => esc_html__( 'Card Border', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcsc-order-bump-card',
			)
		);

		$this->add_responsive_control(
			'order_bump_card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'order_bump_card_box_shadow',
				'selector' => '{{WRAPPER}} .wcsc-order-bump-card',
			)
		);

		// Card State Tabs (Normal & Selected)
		$this->start_controls_tabs( 'tabs_order_bump_card' );

		// Normal Tab
		$this->start_controls_tab(
			'tab_order_bump_card_normal',
			array(
				'label' => esc_html__( 'Normal', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'order_bump_card_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'order_bump_card_border_color_normal',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-card' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		// Selected / Active Tab
		$this->start_controls_tab(
			'tab_order_bump_card_selected',
			array(
				'label' => esc_html__( 'Selected', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'order_bump_card_selected_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-card.is-selected' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'order_bump_card_selected_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-card.is-selected' => 'border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'order_bump_card_selected_box_shadow',
				'selector' => '{{WRAPPER}} .wcsc-order-bump-card.is-selected',
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		// Product Image / Thumbnail
		$this->add_control(
			'heading_order_bump_image_style',
			array(
				'label'     => esc_html__( 'Product Image', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'order_bump_image_width',
			array(
				'label'      => esc_html__( 'Image Width', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min' => 20,
						'max' => 800,
					),
					'%'  => array(
						'min' => 10,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-order-bump-thumb-wrap' => 'width: {{SIZE}}{{UNIT}} !important; min-width: 0 !important;',
					'{{WRAPPER}} .wcsc-order-bump-thumb-wrap' => 'width: {{SIZE}}{{UNIT}} !important; min-width: 0 !important;',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_image_max_width',
			array(
				'label'      => esc_html__( 'Image Max Width', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 20,
						'max' => 1200,
					),
					'%'  => array(
						'min' => 10,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-order-bump-thumb-wrap' => 'max-width: {{SIZE}}{{UNIT}} !important;',
					'{{WRAPPER}} .wcsc-order-bump-thumb-wrap' => 'max-width: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_image_height',
			array(
				'label'      => esc_html__( 'Image Height', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 20,
						'max' => 800,
					),
					'%'  => array(
						'min' => 10,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-order-bump-thumb-wrap' => 'height: {{SIZE}}{{UNIT}} !important;',
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-order-bump-thumb-wrap img' => 'height: {{SIZE}}{{UNIT}} !important;',
					'{{WRAPPER}} .wcsc-order-bump-thumb-wrap' => 'height: {{SIZE}}{{UNIT}} !important;',
					'{{WRAPPER}} .wcsc-order-bump-thumb-wrap img' => 'height: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_image_object_fit',
			array(
				'label'     => esc_html__( 'Object Fit', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => array(
					'cover'      => esc_html__( 'Cover', 'wc-smart-checkout-builder' ),
					'contain'    => esc_html__( 'Contain', 'wc-smart-checkout-builder' ),
					'fill'       => esc_html__( 'Fill', 'wc-smart-checkout-builder' ),
					'scale-down' => esc_html__( 'Scale Down', 'wc-smart-checkout-builder' ),
					'none'       => esc_html__( 'None', 'wc-smart-checkout-builder' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-thumb-wrap img, {{WRAPPER}} .wcsc-order-bump-thumb' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_image_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-order-bump-thumb-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-order-bump-thumb-wrap img, {{WRAPPER}} .wcas-checkout-wrapper .wcsc-order-bump-thumb' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .wcsc-order-bump-thumb-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					'{{WRAPPER}} .wcsc-order-bump-thumb-wrap img, {{WRAPPER}} .wcsc-order-bump-thumb' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		// Product Typography & Colors
		$this->add_control(
			'heading_order_bump_product_style',
			array(
				'label'     => esc_html__( 'Product Title & Price', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'order_bump_product_title_typography',
				'selector' => '{{WRAPPER}} .wcsc-order-bump-title',
			)
		);

		$this->add_control(
			'order_bump_product_title_color',
			array(
				'label'     => esc_html__( 'Title Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'order_bump_product_price_typography',
				'selector' => '{{WRAPPER}} .wcsc-order-bump-price, {{WRAPPER}} .wcsc-order-bump-price .amount',
			)
		);

		$this->add_control(
			'order_bump_product_price_color',
			array(
				'label'     => esc_html__( 'Price Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-price, {{WRAPPER}} .wcsc-order-bump-price ins .amount' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'order_bump_product_del_price_color',
			array(
				'label'     => esc_html__( 'Original / Sale Strikethrough Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-price del, {{WRAPPER}} .wcsc-order-bump-price del .amount' => 'color: {{VALUE}};',
				),
			)
		);

		// Button Styling
		$this->add_control(
			'heading_order_bump_btn_style',
			array(
				'label'     => esc_html__( '"Add to Order" Button', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'order_bump_btn_typography',
				'selector' => '{{WRAPPER}} .wcsc-order-bump-btn',
			)
		);

		$this->add_responsive_control(
			'order_bump_btn_padding',
			array(
				'label'      => esc_html__( 'Button Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'order_bump_btn_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-order-bump-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'order_bump_btn_border',
				'label'    => esc_html__( 'Button Border', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcsc-order-bump-btn',
			)
		);

		// Button State Tabs (Normal / Hover / Active)
		$this->start_controls_tabs( 'tabs_order_bump_btn' );

		// Normal Tab
		$this->start_controls_tab(
			'tab_order_bump_btn_normal',
			array(
				'label' => esc_html__( 'Normal', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'order_bump_btn_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'order_bump_btn_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'order_bump_btn_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-btn' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		// Hover Tab
		$this->start_controls_tab(
			'tab_order_bump_btn_hover',
			array(
				'label' => esc_html__( 'Hover', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'order_bump_btn_hover_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-btn:hover' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'order_bump_btn_hover_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-btn:hover' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'order_bump_btn_hover_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-btn:hover' => 'border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_tab();

		// Active / Added Tab
		$this->start_controls_tab(
			'tab_order_bump_btn_active',
			array(
				'label' => esc_html__( 'Active / Added', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'order_bump_btn_active_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-btn.is-active' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'order_bump_btn_active_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-btn.is-active' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'order_bump_btn_active_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-order-bump-btn.is-active' => 'border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();

		// =========================================================================
		// 8. Order Now Button
		// =========================================================================
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
						'label'     => esc_html__( 'Hover Button Color', 'wc-smart-checkout-builder' ),
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
						'label'     => esc_html__( 'Transition Duration', 'wc-smart-checkout-builder' ),
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

		// =========================================================================
		// 9. Floating Button
		// =========================================================================
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
					'mobile_sticky_hover_border_color',
					array(
						'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
						'type'      => Controls_Manager::COLOR,
						'selectors' => array(
							'{{WRAPPER}} .wcsc-mobile-sticky-btn:hover' => 'border-color: {{VALUE}} !important;',
						),
					)
				);

				$this->add_group_control(
					Group_Control_Box_Shadow::get_type(),
					array(
						'name'     => 'mobile_sticky_hover_box_shadow',
						'selector' => '{{WRAPPER}} .wcsc-mobile-sticky-btn:hover',
					)
				);

				$this->add_control(
					'mobile_sticky_hover_transition',
					array(
						'label'     => esc_html__( 'Transition Duration', 'wc-smart-checkout-builder' ),
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

		// =========================================================================
		// Additional / Extended Style Sections
		// =========================================================================
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

		$this->start_controls_section(
					'section_style_title',
					array(
						'label'     => esc_html__( 'Product Title', 'wc-smart-checkout-builder' ),
						'tab'       => Controls_Manager::TAB_STYLE,
						'condition' => array(
							'checkout_mode' => 'single',
							'show_title'    => 'yes',
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

		$this->start_controls_section(
					'section_style_image',
					array(
						'label'     => esc_html__( 'Product Image', 'wc-smart-checkout-builder' ),
						'tab'       => Controls_Manager::TAB_STYLE,
						'condition' => array(
							'checkout_mode' => 'single',
							'show_image'    => 'yes',
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

		$this->start_controls_section(
					'section_style_price',
					array(
						'label'     => esc_html__( 'Price', 'wc-smart-checkout-builder' ),
						'tab'       => Controls_Manager::TAB_STYLE,
						'condition' => array(
							'checkout_mode' => 'single',
							'show_price'    => 'yes',
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

		$this->start_controls_section(
					'section_style_quantity',
					array(
						'label'     => esc_html__( 'Quantity Field', 'wc-smart-checkout-builder' ),
						'tab'       => Controls_Manager::TAB_STYLE,
						'condition' => array(
							'checkout_mode' => 'single',
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

		// Bundle Mode Specialized Style Controls
		$this->register_bundle_style_controls();
	}

	/**
	 * Style controls specifically for Multi-Product Bundle Mode.
	 */
	protected function register_bundle_style_controls() {
		// --- Section: Bundle Cards / Rows ---
		$this->start_controls_section(
			'section_style_bundle_cards',
			array(
				'label'     => esc_html__( 'Bundle Items / Cards', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_control(
			'bundle_card_bg',
			array(
				'label'     => esc_html__( 'Card Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bundle_card_active_bg',
			array(
				'label'     => esc_html__( 'Selected Card Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f8fbff',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-card.is-selected' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'bundle_card_border',
				'selector' => '{{WRAPPER}} .wcsc-bundle-card',
			)
		);

		$this->add_control(
			'bundle_card_active_border_color',
			array(
				'label'     => esc_html__( 'Selected Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2271b1',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-card.is-selected' => 'border-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'bundle_card_shadow',
				'selector' => '{{WRAPPER}} .wcsc-bundle-card',
			)
		);

		$this->end_controls_section();

		// --- Section: Bundle Product Images ---
		$this->start_controls_section(
			'section_style_bundle_images',
			array(
				'label'     => esc_html__( 'Bundle Product Images', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_img_aspect_ratio',
			array(
				'label'     => esc_html__( 'Aspect Ratio', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '1/1',
				'options'   => array(
					'1/1'    => esc_html__( '1:1 Square (Default)', 'wc-smart-checkout-builder' ),
					'4/3'    => esc_html__( '4:3 Standard', 'wc-smart-checkout-builder' ),
					'3/4'    => esc_html__( '3:4 Portrait', 'wc-smart-checkout-builder' ),
					'16/9'   => esc_html__( '16:9 Landscape', 'wc-smart-checkout-builder' ),
					'auto'   => esc_html__( 'Auto / Original', 'wc-smart-checkout-builder' ),
					'custom' => esc_html__( 'Custom Fixed Height', 'wc-smart-checkout-builder' ),
				),
				'selectors' => array(
					'{{WRAPPER}}'                         => '--wcsc-bundle-img-aspect: {{VALUE}};',
					'{{WRAPPER}} .wcsc-bundle-card-image' => 'aspect-ratio: {{VALUE}} !important; height: auto !important;',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_img_height',
			array(
				'label'      => esc_html__( 'Image Height', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array( 'min' => 50, 'max' => 400 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-card-image' => 'height: {{SIZE}}{{UNIT}} !important; aspect-ratio: unset !important;',
				),
				'condition'  => array(
					'bundle_img_aspect_ratio' => 'custom',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_img_width',
			array(
				'label'      => esc_html__( 'Image Width (List/Table)', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 40, 'max' => 300 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-layout-list .wcsc-bundle-card-image'  => 'width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcsc-bundle-layout-table .wcsc-bundle-card-image' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'bundle_img_fit',
			array(
				'label'     => esc_html__( 'Object Fit', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'contain',
				'options'   => array(
					'contain' => esc_html__( 'Contain', 'wc-smart-checkout-builder' ),
					'cover'   => esc_html__( 'Cover', 'wc-smart-checkout-builder' ),
					'fill'    => esc_html__( 'Fill', 'wc-smart-checkout-builder' ),
				),
				'selectors' => array(
					'{{WRAPPER}} img.wcsc-card-img' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_img_radius',
			array(
				'label'      => esc_html__( 'Image Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-card-image, {{WRAPPER}} img.wcsc-card-img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'bundle_img_bg',
			array(
				'label'     => esc_html__( 'Image Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-card-image' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// --- Section: Bundle Titles & Prices ---
		$this->start_controls_section(
			'section_style_bundle_typography',
			array(
				'label'     => esc_html__( 'Bundle Title & Price', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_control(
			'bundle_title_heading',
			array(
				'label' => esc_html__( 'Product Title', 'wc-smart-checkout-builder' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'bundle_title_typography',
				'selector' => '{{WRAPPER}} .wcsc-bundle-card-title',
			)
		);

		$this->add_control(
			'bundle_title_color',
			array(
				'label'     => esc_html__( 'Title Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-card-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_title_margin_bottom',
			array(
				'label'      => esc_html__( 'Title Bottom Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-card-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'bundle_price_heading',
			array(
				'label'     => esc_html__( 'Product Price', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'bundle_price_typography',
				'selector' => '{{WRAPPER}} .wcsc-bundle-card-price, {{WRAPPER}} .wcsc-bundle-card-price .woocommerce-Price-amount',
			)
		);

		$this->add_control(
			'bundle_price_color',
			array(
				'label'     => esc_html__( 'Price Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-card-price' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bundle_price_sale_color',
			array(
				'label'     => esc_html__( 'Sale Price Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-card-price ins, {{WRAPPER}} .wcsc-bundle-card-price ins .woocommerce-Price-amount' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// --- Section: Bundle Variation Swatches ---
		$this->start_controls_section(
			'section_style_bundle_swatches',
			array(
				'label'     => esc_html__( 'Bundle Variation Swatches', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_control(
			'bundle_attr_label_color',
			array(
				'label'     => esc_html__( 'Attribute Label Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-attr-label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'bundle_attr_label_typography',
				'selector' => '{{WRAPPER}} .wcsc-bundle-attr-label',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'bundle_var_btn_typography',
				'selector' => '{{WRAPPER}} .wcsc-bundle-var-btn',
			)
		);

		$this->add_responsive_control(
			'bundle_var_btn_padding',
			array(
				'label'      => esc_html__( 'Button Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-var-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_var_btn_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-var-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_bundle_var_btn' );

		// Normal
		$this->start_controls_tab(
			'tab_bundle_var_normal',
			array( 'label' => esc_html__( 'Normal', 'wc-smart-checkout-builder' ) )
		);
		$this->add_control(
			'bundle_var_btn_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-var-btn' => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'bundle_var_btn_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-var-btn' => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'bundle_var_btn_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-var-btn' => 'border-color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		// Selected
		$this->start_controls_tab(
			'tab_bundle_var_selected',
			array( 'label' => esc_html__( 'Selected', 'wc-smart-checkout-builder' ) )
		);
		$this->add_control(
			'bundle_var_btn_selected_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-var-btn.is-selected' => 'color: {{VALUE}} !important;',
				),
			)
		);
		$this->add_control(
			'bundle_var_btn_selected_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-var-btn.is-selected' => 'background-color: {{VALUE}} !important;',
				),
			)
		);
		$this->add_control(
			'bundle_var_btn_selected_border',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-var-btn.is-selected' => 'border-color: {{VALUE}} !important;',
				),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// --- Section: Bundle Quantity Stepper ---
		$this->start_controls_section(
			'section_style_bundle_quantity',
			array(
				'label'     => esc_html__( 'Bundle Quantity Stepper', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_qty_height',
			array(
				'label'      => esc_html__( 'Stepper Height', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 24, 'max' => 60 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-qty' => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcsc-bundle-qty-btn' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcsc-bundle-qty-val' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'bundle_qty_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-qty' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bundle_qty_text_color',
			array(
				'label'     => esc_html__( 'Number Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-qty-val' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bundle_qty_btn_color',
			array(
				'label'     => esc_html__( 'Button (+ / -) Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-qty-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'bundle_qty_border',
				'selector' => '{{WRAPPER}} .wcsc-bundle-qty',
			)
		);

		$this->add_responsive_control(
			'bundle_qty_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-qty' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// --- Section: Bundle Select Button ---
		$this->start_controls_section(
			'section_style_bundle_select_btn',
			array(
				'label'     => esc_html__( 'Bundle Select Button', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'bundle_btn_typography',
				'selector' => '{{WRAPPER}} .wcsc-bundle-select-btn',
			)
		);

		$this->add_responsive_control(
			'bundle_btn_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-select-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_btn_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-select-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_bundle_select_btn' );

		// Normal
		$this->start_controls_tab(
			'tab_bundle_btn_normal',
			array( 'label' => esc_html__( 'Normal State', 'wc-smart-checkout-builder' ) )
		);
		$this->add_control(
			'bundle_btn_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-select-btn' => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'bundle_btn_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-select-btn' => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'bundle_btn_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-select-btn' => 'border-color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		// Selected
		$this->start_controls_tab(
			'tab_bundle_btn_selected',
			array( 'label' => esc_html__( 'Selected State', 'wc-smart-checkout-builder' ) )
		);
		$this->add_control(
			'bundle_btn_selected_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-card.is-selected .wcsc-bundle-select-btn' => 'color: {{VALUE}} !important;',
				),
			)
		);
		$this->add_control(
			'bundle_btn_selected_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-card.is-selected .wcsc-bundle-select-btn' => 'background-color: {{VALUE}} !important;',
				),
			)
		);
		$this->add_control(
			'bundle_btn_selected_border',
			array(
				'label'     => esc_html__( 'Border Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-card.is-selected .wcsc-bundle-select-btn' => 'border-color: {{VALUE}} !important;',
				),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// --- Section: Bundle Summary Bar ---
		$this->start_controls_section(
			'section_style_bundle_summary_bar',
			array(
				'label'     => esc_html__( 'Bundle Summary Bar', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'checkout_mode' => 'bundle',
				),
			)
		);

		$this->add_control(
			'bundle_summary_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f0f7ff',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-summary-bar' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'bundle_summary_border',
				'selector' => '{{WRAPPER}} .wcsc-bundle-summary-bar',
			)
		);

		$this->add_responsive_control(
			'bundle_summary_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-summary-bar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_summary_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-summary-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'bundle_summary_text_color',
			array(
				'label'     => esc_html__( 'Info Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-summary-count-line' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bundle_summary_total_color',
			array(
				'label'     => esc_html__( 'Total Amount Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-summary-total-val' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'bundle_summary_total_typography',
				'selector' => '{{WRAPPER}} .wcsc-summary-total-val',
			)
		);

		$this->add_control(
			'bundle_cta_heading',
			array(
				'label'     => esc_html__( 'Summary CTA Button', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'bundle_cta_typography',
				'selector' => '{{WRAPPER}} .wcsc-summary-cta-btn',
			)
		);

		$this->add_control(
			'bundle_cta_color',
			array(
				'label'     => esc_html__( 'Button Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-summary-cta-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bundle_cta_bg',
			array(
				'label'     => esc_html__( 'Button Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-summary-cta-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bundle_cta_hover_bg',
			array(
				'label'     => esc_html__( 'Button Hover Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcsc-summary-cta-btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_cta_radius',
			array(
				'label'      => esc_html__( 'Button Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-summary-cta-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_cta_padding',
			array(
				'label'      => esc_html__( 'Button Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-summary-cta-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		// Countdown Urgency Timer Styles
		$this->add_control(
			'heading_style_summary_timer',
			array(
				'label'     => esc_html__( 'Countdown Urgency Timer Style', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'checkout_mode'        => 'bundle',
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_control(
			'summary_timer_box_bg',
			array(
				'label'     => esc_html__( 'Timer Box Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#fff1f2',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-timer-box' => 'background-color: {{VALUE}};',
				),
				'condition' => array(
					'checkout_mode'        => 'bundle',
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'summary_timer_box_border',
				'selector'  => '{{WRAPPER}} .wcsc-bundle-timer-box',
				'condition' => array(
					'checkout_mode'        => 'bundle',
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'summary_timer_box_radius',
			array(
				'label'      => esc_html__( 'Timer Box Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-timer-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array(
					'checkout_mode'        => 'bundle',
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'summary_timer_box_padding',
			array(
				'label'      => esc_html__( 'Timer Box Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-timer-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array(
					'checkout_mode'        => 'bundle',
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_control(
			'summary_timer_label_color',
			array(
				'label'     => esc_html__( 'Timer Label Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#be123c',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-timer-text' => 'color: {{VALUE}};',
				),
				'condition' => array(
					'checkout_mode'        => 'bundle',
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'summary_timer_label_typography',
				'selector'  => '{{WRAPPER}} .wcsc-timer-text',
				'condition' => array(
					'checkout_mode'        => 'bundle',
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_control(
			'summary_timer_digit_color',
			array(
				'label'     => esc_html__( 'Digit Number Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-timer-digit' => 'color: {{VALUE}};',
				),
				'condition' => array(
					'checkout_mode'        => 'bundle',
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_control(
			'summary_timer_digit_bg',
			array(
				'label'     => esc_html__( 'Digit Box Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e11d48',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-timer-digit' => 'background-color: {{VALUE}};',
				),
				'condition' => array(
					'checkout_mode'        => 'bundle',
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'summary_timer_digit_typography',
				'selector'  => '{{WRAPPER}} .wcsc-timer-digit',
				'condition' => array(
					'checkout_mode'        => 'bundle',
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'summary_timer_digit_radius',
			array(
				'label'      => esc_html__( 'Digit Box Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-timer-digit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array(
					'checkout_mode'        => 'bundle',
					'enable_summary_timer' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}


	/**
	 * Register standard card/block container and title override controls.
	 *
	 * @param string $key            Block identifier (checkout_form, shipping, order_summary, payment).
	 * @param string $selector       CSS selector for container.
	 * @param string $title_selector  CSS selector for title.
	 */
	protected function register_block_container_controls( $key, $selector, $title_selector ) {
		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'block_bg_' . $key,
				'label'    => esc_html__( 'Background', 'wc-smart-checkout-builder' ),
				'types'    => array( 'classic', 'gradient' ),
				'selector' => $selector,
			)
		);

		$this->add_responsive_control(
			'block_padding_' . $key,
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'block_border_' . $key,
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => $selector,
			)
		);

		$this->add_responsive_control(
			'block_border_radius_' . $key,
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'block_box_shadow_' . $key,
				'label'    => esc_html__( 'Box Shadow', 'wc-smart-checkout-builder' ),
				'selector' => $selector,
			)
		);

		$this->add_control(
			'heading_block_title_' . $key,
			array(
				'label'     => esc_html__( 'Section Heading', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'block_title_align_' . $key,
			array(
				'label'     => esc_html__( 'Text Alignment', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
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
					$title_selector => 'text-align: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'block_title_typography_' . $key,
				'label'    => esc_html__( 'Typography', 'wc-smart-checkout-builder' ),
				'selector' => $title_selector,
			)
		);

		$this->add_control(
			'block_title_color_' . $key,
			array(
				'label'     => esc_html__( 'Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$title_selector => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'block_title_margin_' . $key,
			array(
				'label'      => esc_html__( 'Margin', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					$title_selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'block_title_padding_' . $key,
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					$title_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'block_title_border_' . $key,
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => $title_selector,
			)
		);

		$this->add_responsive_control(
			'block_title_border_radius_' . $key,
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					$title_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				),
			)
		);

		// --- Section: Bundle Selected Badge Styling ---
		$this->start_controls_section(
			'section_style_bundle_badge',
			array(
				'label'     => esc_html__( 'Bundle Selected Badge', 'wc-smart-checkout-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'checkout_mode'     => 'bundle',
					'show_bundle_badge' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'bundle_badge_typography',
				'selector' => '{{WRAPPER}} .wcsc-bundle-badge',
			)
		);

		$this->add_control(
			'bundle_badge_text_color',
			array(
				'label'     => esc_html__( 'Badge Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-badge' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'bundle_badge_bg',
			array(
				'label'     => esc_html__( 'Badge Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2271b1',
				'selectors' => array(
					'{{WRAPPER}} .wcsc-bundle-badge' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'bundle_badge_border',
				'selector' => '{{WRAPPER}} .wcsc-bundle-badge',
			)
		);

		$this->add_responsive_control(
			'bundle_badge_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'bundle_badge_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcsc-bundle-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'bundle_badge_box_shadow',
				'selector' => '{{WRAPPER}} .wcsc-bundle-badge',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render feature/trust badges section before checkout form.
	 *
	 * @param array $settings
	 */
	protected function render_checkout_header( $settings ) {
		if ( 'yes' !== ( $settings['show_checkout_header'] ?? 'yes' ) ) {
			return;
		}

		$title = ! empty( $settings['checkout_header_title'] ) ? $settings['checkout_header_title'] : __( 'অর্ডার করুন', 'wc-smart-checkout-builder' );
		$desc  = ! empty( $settings['checkout_header_desc'] ) ? $settings['checkout_header_desc'] : __( 'অর্ডার করতে নিচের ফর্মটি সঠিক ভাবে পূরণ করুন , আমাদের একজন প্রতিনিদি আপনাকে ফোন করে অর্ডারটি কনফার্ম করবেন।', 'wc-smart-checkout-builder' );
		$has_divider   = 'yes' === ( $settings['checkout_header_divider'] ?? 'yes' );
		$divider_class = $has_divider ? ' has-divider' : '';

		if ( empty( $title ) && empty( $desc ) ) {
			return;
		}
		?>
		<div class="wcsc-checkout-header<?php echo esc_attr( $divider_class ); ?>">
			<?php if ( ! empty( $title ) ) : ?>
				<h3 class="wcsc-checkout-header-title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<?php if ( ! empty( $desc ) ) : ?>
				<p class="wcsc-checkout-header-desc"><?php echo wp_kses_post( $desc ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

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
		$checkout_mode = ! empty( $settings['checkout_mode'] ) ? $settings['checkout_mode'] : 'single';

		if ( 'bundle' === $checkout_mode ) {
			$this->render_bundle_mode( $settings );
			return;
		}

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

								$custom_label = ! empty( $attr['custom_label'] ) ? $attr['custom_label'] : '';
								if ( empty( $custom_label ) && ! empty( $settings['attribute_types'] ) && is_array( $settings['attribute_types'] ) ) {
									$attr_clean_label = wc_attribute_label( $attr_name, $product );
									foreach ( $settings['attribute_types'] as $item ) {
										if ( ! empty( $item['attribute_name'] ) ) {
											$it_name = strtolower( trim( $item['attribute_name'] ) );
											$it_slug = sanitize_title( $item['attribute_name'] );
											if (
												$it_name === strtolower( trim( $attr_name ) ) ||
												$it_slug === sanitize_title( $attr_name ) ||
												$it_name === strtolower( trim( $attr_clean_label ) ) ||
												$it_slug === sanitize_title( $attr_clean_label ) ||
												$it_name === strtolower( trim( $attr_label ) )
											) {
												if ( ! empty( $item['custom_label'] ) ) {
													$custom_label = trim( $item['custom_label'] );
												}
												break;
											}
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
				<?php $this->render_checkout_header( $settings ); ?>
				<?php Checkout_Handler::render_checkout_section( $product, $settings ); ?>
			</div>

		</div>
		<?php
	}

	/**
	 * Render Multi-Product Bundle Mode Grid, Tiered Discounts & Checkout.
	 *
	 * @param array $settings
	 */
	protected function render_bundle_mode( $settings ) {
		$product_ids = ! empty( $settings['bundle_products'] ) ? (array) $settings['bundle_products'] : array();
		$product_ids = array_filter( array_map( 'absint', $product_ids ) );

		if ( empty( $product_ids ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				?>
				<div class="wcsc-notice-warning" style="padding: 20px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px; color: #856404; text-align: center;">
					<h3 style="margin: 0 0 8px 0;"><?php esc_html_e( 'Bundle Mode: No Products Selected', 'wc-smart-checkout-builder' ); ?></h3>
					<p style="margin: 0;"><?php esc_html_e( 'Please select products from the "Product Selection" panel in Elementor to display the bundle grid.', 'wc-smart-checkout-builder' ); ?></p>
				</div>
				<?php
			}
			return;
		}

		$min_items = ! empty( $settings['bundle_min_items'] ) ? max( 1, absint( $settings['bundle_min_items'] ) ) : 1;
		$max_items = ! empty( $settings['bundle_max_items'] ) ? absint( $settings['bundle_max_items'] ) : 0;
		$tiers     = ! empty( $settings['discount_tiers'] ) ? (array) $settings['discount_tiers'] : array();
		$badge_txt = ! empty( $settings['bundle_badge_text'] ) ? $settings['bundle_badge_text'] : __( 'SELECTED', 'wc-smart-checkout-builder' );
		$sel_txt   = ! empty( $settings['bundle_select_btn_text'] ) ? $settings['bundle_select_btn_text'] : __( 'Select', 'wc-smart-checkout-builder' );
		$seled_txt = ! empty( $settings['bundle_selected_btn_text'] ) ? $settings['bundle_selected_btn_text'] : __( 'Selected', 'wc-smart-checkout-builder' );
		$cta_text  = ! empty( $settings['bundle_cta_button_text'] ) ? $settings['bundle_cta_button_text'] : __( 'Proceed to Checkout', 'wc-smart-checkout-builder' );
		$layout_desktop = ! empty( $settings['bundle_layout'] ) ? sanitize_key( $settings['bundle_layout'] ) : 'grid';
		$layout_tablet  = ! empty( $settings['bundle_layout_tablet'] ) ? sanitize_key( $settings['bundle_layout_tablet'] ) : $layout_desktop;
		$layout_mobile  = ! empty( $settings['bundle_layout_mobile'] ) ? sanitize_key( $settings['bundle_layout_mobile'] ) : 'list';

		$container_classes = 'wcsc-bundle-container';
		$container_classes .= ' wcsc-bundle-layout-' . $layout_desktop;
		$container_classes .= ' wcsc-view-desktop-' . $layout_desktop;
		$container_classes .= ' wcsc-view-tablet-' . $layout_tablet;
		$container_classes .= ' wcsc-view-mobile-' . $layout_mobile;
		if ( 'grid' === $layout_desktop ) {
			$container_classes .= ' wcsc-bundle-grid';
		}

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
		?>
		<div class="wcsc-product-checkout-widget wcas-checkout-wrapper wcsc-bundle-checkout-wrapper"
			id="wcsc-product-checkout-<?php echo esc_attr( $this->get_id() ); ?>"
			data-widget-id="<?php echo esc_attr( $this->get_id() ); ?>"
			data-min-items="<?php echo esc_attr( $min_items ); ?>"
			data-max-items="<?php echo esc_attr( $max_items ); ?>"
			data-tiers="<?php echo esc_attr( wp_json_encode( $tiers_data ) ); ?>"
			<?php echo Checkout_Handler::render_data_attrs( $settings ); ?>>

			<div class="<?php echo esc_attr( $container_classes ); ?>">
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

					// If variable, default initial variation id & price from first variation
					$init_var_id = 0;
					if ( $is_var && ! empty( $var_json_data ) ) {
						$init_var_id = absint( $var_json_data[0]['variation_id'] ?? 0 );
						if ( ! empty( $var_json_data[0]['display_price'] ) ) {
							$price_val = (float) $var_json_data[0]['display_price'];
						}
					}

					$badge_style       = ! empty( $settings['bundle_badge_style'] ) ? $settings['bundle_badge_style'] : 'pill';
					$badge_pos         = ! empty( $settings['bundle_badge_position'] ) ? $settings['bundle_badge_position'] : 'top_right';
					$card_ribbon_class = ( 'ribbon' === $badge_style ) ? ' has-badge-ribbon' : '';
					$badge_classes     = 'wcsc-bundle-badge wcsc-badge-' . esc_attr( $badge_style ) . ' pos-' . esc_attr( $badge_pos );
					$card_active       = $first_item ? ' is-selected' : '';
					$first_item        = false;
				?>
					<div class="wcsc-bundle-card<?php echo esc_attr( $card_active . $card_ribbon_class ); ?>"
						data-product-id="<?php echo esc_attr( $pid ); ?>"
						data-is-variable="<?php echo $is_var ? 'yes' : 'no'; ?>"
						data-base-price="<?php echo esc_attr( $price_val ); ?>"
						data-current-price="<?php echo esc_attr( $price_val ); ?>"
						data-variation-id="<?php echo esc_attr( $init_var_id ); ?>"
						data-qty="1">

						<?php if ( 'yes' === ( $settings['show_bundle_badge'] ?? 'yes' ) ) : ?>
							<div class="<?php echo esc_attr( $badge_classes ); ?>"><?php echo esc_html( $badge_txt ); ?></div>
						<?php endif; ?>

						<div class="wcsc-bundle-card-image">
							<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $prod->get_name() ); ?>" class="wcsc-card-img" />
						</div>

						<div class="wcsc-bundle-card-body">
							<h4 class="wcsc-bundle-card-title"><?php echo esc_html( $prod->get_name() ); ?></h4>

							<div class="wcsc-bundle-card-price">
								<?php echo $prod->get_price_html(); ?>
							</div>

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

									<script type="application/json" class="wcsc-product-variations-json">
										<?php echo wp_json_encode( $var_json_data ); ?>
									</script>
								</div>
							<?php endif; ?>

							<div class="wcsc-bundle-card-actions">
								<div class="wcsc-bundle-qty">
									<button type="button" class="wcsc-bundle-qty-btn minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'wc-smart-checkout-builder' ); ?>">&minus;</button>
									<input type="number" class="wcsc-bundle-qty-val" value="1" min="1" max="999" />
									<button type="button" class="wcsc-bundle-qty-btn plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'wc-smart-checkout-builder' ); ?>">&plus;</button>
								</div>

								<button type="button" class="wcsc-bundle-select-btn">
									<span class="btn-text-select"><?php echo esc_html( $sel_txt ); ?></span>
									<span class="btn-text-selected"><?php echo esc_html( $seled_txt ); ?></span>
								</button>
							</div>

						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( 'yes' === ( $settings['show_summary_bar'] ?? 'yes' ) ) : ?>
				<div class="wcsc-bundle-summary-bar">
					<?php if ( 'yes' === ( $settings['enable_summary_timer'] ?? 'no' ) ) :
						$timer_label = ! empty( $settings['summary_timer_label'] ) ? $settings['summary_timer_label'] : __( 'Hurry! Special offer expires in:', 'wc-smart-checkout-builder' );
						$timer_h     = max( 0, absint( $settings['summary_timer_hours'] ?? 0 ) );
						$timer_m     = max( 0, min( 59, absint( $settings['summary_timer_minutes'] ?? 15 ) ) );
						$timer_s     = max( 0, min( 59, absint( $settings['summary_timer_seconds'] ?? 0 ) ) );
						$timer_loop  = ( 'yes' === ( $settings['summary_timer_loop'] ?? 'yes' ) ) ? 'yes' : 'no';
					?>
						<div class="wcsc-bundle-timer-box"
							data-hours="<?php echo esc_attr( $timer_h ); ?>"
							data-minutes="<?php echo esc_attr( $timer_m ); ?>"
							data-seconds="<?php echo esc_attr( $timer_s ); ?>"
							data-loop="<?php echo esc_attr( $timer_loop ); ?>">
							<div class="wcsc-timer-label">
								<span class="wcsc-timer-icon">⏳</span>
								<span class="wcsc-timer-text"><?php echo esc_html( $timer_label ); ?></span>
							</div>
							<div class="wcsc-timer-countdown">
								<?php if ( $timer_h > 0 ) : ?>
									<div class="wcsc-timer-unit wcsc-timer-hours">
										<span class="wcsc-timer-digit hrs"><?php echo esc_html( sprintf( '%02d', $timer_h ) ); ?></span>
										<span class="wcsc-timer-unit-label"><?php esc_html_e( 'Hrs', 'wc-smart-checkout-builder' ); ?></span>
									</div>
									<span class="wcsc-timer-sep sep-hrs">:</span>
								<?php endif; ?>
								<div class="wcsc-timer-unit wcsc-timer-mins">
									<span class="wcsc-timer-digit mins"><?php echo esc_html( sprintf( '%02d', $timer_m ) ); ?></span>
									<span class="wcsc-timer-unit-label"><?php esc_html_e( 'Mins', 'wc-smart-checkout-builder' ); ?></span>
								</div>
								<span class="wcsc-timer-sep">:</span>
								<div class="wcsc-timer-unit wcsc-timer-secs">
									<span class="wcsc-timer-digit secs"><?php echo esc_html( sprintf( '%02d', $timer_s ) ); ?></span>
									<span class="wcsc-timer-unit-label"><?php esc_html_e( 'Secs', 'wc-smart-checkout-builder' ); ?></span>
								</div>
							</div>
						</div>
					<?php endif; ?>
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
						<a href="#wcsc-bundle-checkout-section" class="wcsc-summary-cta-btn">
							<span class="wcsc-cta-text"><?php echo esc_html( $cta_text ); ?></span>
							<span class="wcsc-cta-arrow">&darr;</span>
						</a>
					</div>
				</div>
			<?php endif; ?>

			<div id="wcsc-bundle-checkout-section" class="wcsc-checkout-section">
				<?php $this->render_checkout_header( $settings ); ?>
				<?php Checkout_Handler::render_checkout_section( null, $settings ); ?>
			</div>

		</div>
		<?php
	}
}
