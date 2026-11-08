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
use WCSC\Product_Handler;

/**
 * Class Widget_Variation_Selector
 *
 * Modular Elementor Widget: Product Variation Selector.
 */
class Widget_Variation_Selector extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wcsc_variation_selector';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Product Variation Selector', 'wc-smart-checkout-builder' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-variable';
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
		return array( 'variation', 'swatches', 'attribute', 'size', 'color', 'smart checkout' );
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

		$this->start_controls_section(
			'section_product_source',
			array(
				'label' => esc_html__( 'Product Source', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'source_type',
			array(
				'label'   => esc_html__( 'Product Source', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'   => esc_html__( 'Auto-sync from Product Grid Widget / Current Page', 'wc-smart-checkout-builder' ),
					'manual' => esc_html__( 'Select Specific Variable Product', 'wc-smart-checkout-builder' ),
				),
			)
		);

		$this->add_control(
			'manual_product_id',
			array(
				'label'       => esc_html__( 'Select Variable Product', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'options'     => Product_Handler::get_product_options(),
				'condition'   => array(
					'source_type' => 'manual',
				),
			)
		);

		$this->add_control(
			'display_layout',
			array(
				'label'   => esc_html__( 'Options Display Style', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'pills',
				'options' => array(
					'pills'     => esc_html__( 'Button / Pill Swatches', 'wc-smart-checkout-builder' ),
					'dropdown'  => esc_html__( 'Classic Dropdown Menus', 'wc-smart-checkout-builder' ),
				),
			)
		);

		$this->add_control(
			'show_variation_image',
			array(
				'label'        => esc_html__( 'Show Variation Image Preview', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'No', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_price_preview',
			array(
				'label'        => esc_html__( 'Show Variation Price Preview', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'No', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab controls.
	 */
	protected function register_style_controls() {

		$this->start_controls_section(
			'section_style_swatches',
			array(
				'label' => esc_html__( 'Swatches & Options Styling', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'active_swatch_color',
			array(
				'label'     => esc_html__( 'Active Option Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-swatch-item.is-selected' => 'border-color: {{VALUE}}; background-color: {{VALUE}}; color: #ffffff;',
				),
			)
		);

		$this->add_control(
			'swatch_border_radius',
			array(
				'label'      => esc_html__( 'Option Border Radius', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array(
					'top'      => '6',
					'right'    => '6',
					'bottom'   => '6',
					'left'     => '6',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-swatch-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$product_id = 0;
		if ( 'manual' === $settings['source_type'] && ! empty( $settings['manual_product_id'] ) ) {
			$product_id = absint( $settings['manual_product_id'] );
		} else {
			// Auto find first variable product or page product
			$product_id = Product_Handler::get_current_product_id();
		}

		$product = wc_get_product( $product_id );

		// Fallback for editor if product is empty or simple
		if ( ( ! $product || ! $product->is_type( 'variable' ) ) && function_exists( 'wc_get_products' ) ) {
			$vars = wc_get_products( array(
				'type'   => 'variable',
				'limit'  => 1,
				'status' => 'publish',
			) );
			if ( ! empty( $vars ) ) {
				$product = $vars[0];
				$product_id = $product->get_id();
			}
		}

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			echo '<div class="wcas-checkout-wrapper"><p class="wcsc-notice">' . esc_html__( 'Please select a variable product to render variation options.', 'wc-smart-checkout-builder' ) . '</p></div>';
			return;
		}

		$available_variations = $product->get_available_variations();
		$attributes_data      = $product->get_variation_attributes();
		$variations_json      = esc_attr( wp_json_encode( $available_variations ) );
		$layout_style         = ! empty( $settings['display_layout'] ) ? $settings['display_layout'] : 'pills';
		$show_image           = isset( $settings['show_variation_image'] ) && 'yes' === $settings['show_variation_image'];
		$show_price           = isset( $settings['show_price_preview'] ) && 'yes' === $settings['show_price_preview'];
		?>
		<div class="wcas-checkout-wrapper wcsc-variation-selector-widget"
			data-product-id="<?php echo esc_attr( $product_id ); ?>"
			data-product-variations="<?php echo $variations_json; ?>">

			<div class="wcsc-var-selector-inner">
				<!-- Variation Preview Header -->
				<?php if ( $show_image || $show_price ) : ?>
					<div class="wcsc-var-preview-box">
						<?php if ( $show_image ) : ?>
							<div class="wcsc-var-preview-thumb-wrap">
								<img src="<?php echo esc_url( wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) ); ?>" class="wcsc-var-preview-img" alt="<?php echo esc_attr( $product->get_name() ); ?>" />
							</div>
						<?php endif; ?>

						<div class="wcsc-var-preview-meta">
							<h4 class="wcsc-var-product-title"><?php echo esc_html( $product->get_name() ); ?></h4>
							<?php if ( $show_price ) : ?>
								<div class="wcsc-var-preview-price">
									<?php echo wp_kses_post( $product->get_price_html() ); ?>
								</div>
							<?php endif; ?>
							<div class="wcsc-var-stock-status"></div>
						</div>
					</div>
				<?php endif; ?>

				<!-- Variation Attributes Form -->
				<div class="wcsc-var-attributes-container style-<?php echo esc_attr( $layout_style ); ?>">
					<?php foreach ( $attributes_data as $attribute_name => $options ) : 
						$attr_label = wc_attribute_label( $attribute_name );
						$attr_sanitized = sanitize_title( $attribute_name );
						?>
						<div class="wcsc-var-attr-row" data-attribute-name="<?php echo esc_attr( $attr_sanitized ); ?>">
							<label class="wcsc-var-attr-label">
								<span class="label-title"><?php echo esc_html( $attr_label ); ?>:</span>
								<span class="label-selected-val"></span>
							</label>

							<?php if ( 'dropdown' === $layout_style ) : ?>
								<select class="wcsc-var-dropdown-select" data-attribute-name="<?php echo esc_attr( $attr_sanitized ); ?>">
									<option value=""><?php echo esc_html( sprintf( __( 'Choose %s', 'wc-smart-checkout-builder' ), $attr_label ) ); ?></option>
									<?php foreach ( $options as $option ) : ?>
										<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php else : ?>
								<div class="wcsc-swatches-list">
									<?php foreach ( $options as $option ) : ?>
										<button type="button" class="wcsc-swatch-item" data-value="<?php echo esc_attr( $option ); ?>">
											<?php echo esc_html( $option ); ?>
										</button>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>

			</div>
		</div>
		<?php
	}
}
