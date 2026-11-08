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
use WCSC\Checkout_Handler;
use WCSC\Product_Handler;

/**
 * Class Widget_Embedded_Checkout
 *
 * Modular Elementor Widget: Embedded Checkout Form.
 */
class Widget_Embedded_Checkout extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wcsc_embedded_checkout';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Embedded Checkout Form', 'wc-smart-checkout-builder' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
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
		return array( 'checkout', 'embedded checkout', 'order form', 'native checkout', 'smart checkout', 'direct checkout' );
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
	 * Content tab controls.
	 */
	protected function register_content_controls() {

		// Section: Checkout Layout
		$this->start_controls_section(
			'section_checkout_layout',
			array(
				'label' => esc_html__( 'Checkout Form Layout', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'checkout_layout',
			array(
				'label'   => esc_html__( 'Columns Layout', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '2_columns',
				'options' => array(
					'1_column'  => esc_html__( '1 Column (Stacked Full Width)', 'wc-smart-checkout-builder' ),
					'2_columns' => esc_html__( '2 Columns (Form Left, Order Review Right)', 'wc-smart-checkout-builder' ),
				),
			)
		);

		$this->add_control(
			'show_checkout_billing',
			array(
				'label'        => esc_html__( 'Show Customer Details Form', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_checkout_shipping',
			array(
				'label'        => esc_html__( 'Show Shipping Methods Block', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_checkout_order_review',
			array(
				'label'        => esc_html__( 'Show Order Review Table', 'wc-smart-checkout-builder' ),
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
				'label'        => esc_html__( 'Show Payment Methods Block', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'Hide', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'bd_phone_validation',
			array(
				'label'        => esc_html__( 'BD 11-Digit Phone Number Validation', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'No', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// Section: Order Button
		$this->start_controls_section(
			'section_order_button_settings',
			array(
				'label' => esc_html__( 'Order Button Settings', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'order_button_text',
			array(
				'label'       => esc_html__( 'Order Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Order Now', 'wc-smart-checkout-builder' ),
				'placeholder' => esc_html__( 'Order Now', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'order_button_animation',
			array(
				'label'   => esc_html__( 'Button Animation Style', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'border_run',
				'options' => array(
					'none'       => esc_html__( 'None (Static)', 'wc-smart-checkout-builder' ),
					'pulse'      => esc_html__( 'Pulse Glow', 'wc-smart-checkout-builder' ),
					'shake'      => esc_html__( 'Shake Attention', 'wc-smart-checkout-builder' ),
					'border_run' => esc_html__( 'Border Running Light', 'wc-smart-checkout-builder' ),
					'bounce'     => esc_html__( 'Bounce', 'wc-smart-checkout-builder' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab controls.
	 */
	protected function register_style_controls() {

		$this->start_controls_section(
			'section_style_button',
			array(
				'label' => esc_html__( 'Order Button Styling', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'btn_bg_color',
			array(
				'label'     => esc_html__( 'Button Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#16a34a',
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper #place_order' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'btn_text_color',
			array(
				'label'     => esc_html__( 'Button Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper #place_order' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typography',
				'label'    => esc_html__( 'Typography', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper #place_order',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		// Get current product or fallback
		$product = wc_get_product( Product_Handler::get_current_product_id() );
		if ( ! $product && function_exists( 'wc_get_products' ) ) {
			$all = wc_get_products( array( 'limit' => 1, 'status' => 'publish' ) );
			if ( ! empty( $all ) ) {
				$product = $all[0];
			}
		}

		?>
		<div id="wcsc-embedded-checkout" class="wcsc-embedded-checkout-section">
			<?php
			// Render using core Checkout_Handler
			Checkout_Handler::render_checkout_section( $product, $settings );
			?>
		</div>
		<?php
	}
}

