<?php
namespace WooFlexiBundle;

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
 * Elementor Widget for WooFlexiBundle Product Grid & Direct Checkout.
 */
class Elementor_Widget extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wooflexi_bundle_grid';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'FlexiBundle Product Grid & Checkout', 'wooflexibundle-builder' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-products-grid';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'wooflexibundle-category', 'woocommerce-elements' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'bundle', 'woocommerce', 'product grid', 'tiered discount', 'checkout', 'variation', 'smart checkout' );
	}

	/**
	 * Enqueue script dependencies.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'wooflexibundle-frontend' );
	}

	/**
	 * Enqueue style dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'wooflexibundle-style' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
	 * Content Controls: Product Query, Min/Max rules, Tiered discounts, Scroll target & CTA.
	 */
	protected function register_content_controls() {

		// ==================== 1. Product Query ====================
		$this->start_controls_section(
			'section_product_query',
			array(
				'label' => esc_html__( 'Product Query', 'wooflexibundle-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'selected_product_ids',
			array(
				'label'       => esc_html__( 'Select Products', 'wooflexibundle-builder' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_product_list(),
				'description' => esc_html__( 'Choose Simple and Variable WooCommerce products to display in the bundle grid.', 'wooflexibundle-builder' ),
			)
		);

		$this->end_controls_section();

		// ==================== 2. Quantity & Checkout Rules ====================
		$this->start_controls_section(
			'section_quantity_rules',
			array(
				'label' => esc_html__( 'Min/Max Quantity Rules', 'wooflexibundle-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'min_items_required',
			array(
				'label'       => esc_html__( 'Minimum Items Required', 'wooflexibundle-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 100,
				'step'        => 1,
				'default'     => 1,
				'description' => esc_html__( 'Minimum total quantity required before allowing checkout.', 'wooflexibundle-builder' ),
			)
		);

		$this->add_control(
			'max_items_allowed',
			array(
				'label'       => esc_html__( 'Maximum Items Allowed (0 for unlimited)', 'wooflexibundle-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 100,
				'step'        => 1,
				'default'     => 0,
				'description' => esc_html__( 'Set a cap on total quantity allowed in the bundle (0 = no maximum).', 'wooflexibundle-builder' ),
			)
		);

		$this->add_control(
			'enable_qty_buttons',
			array(
				'label'        => esc_html__( 'Enable Quantity (+ / -) Buttons', 'wooflexibundle-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wooflexibundle-builder' ),
				'label_off'    => esc_html__( 'No', 'wooflexibundle-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// ==================== 3. Tiered Discount Settings ====================
		$this->start_controls_section(
			'section_tiered_discounts',
			array(
				'label' => esc_html__( 'Tiered Discount Settings', 'wooflexibundle-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'min_qty',
			array(
				'label'   => esc_html__( 'Item Count (Min Qty)', 'wooflexibundle-builder' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 2,
				'min'     => 1,
			)
		);

		$repeater->add_control(
			'discount_type',
			array(
				'label'   => esc_html__( 'Discount Type', 'wooflexibundle-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'percentage',
				'options' => array(
					'percentage' => esc_html__( 'Percentage (%)', 'wooflexibundle-builder' ),
					'fixed'      => esc_html__( 'Fixed Amount ($)', 'wooflexibundle-builder' ),
				),
			)
		);

		$repeater->add_control(
			'discount_value',
			array(
				'label'   => esc_html__( 'Discount Value', 'wooflexibundle-builder' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 0,
				'step'    => 0.1,
			)
		);

		$repeater->add_control(
			'tier_label',
			array(
				'label'       => esc_html__( 'Custom Discount Label', 'wooflexibundle-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'e.g. Buy 2, Get 10% OFF', 'wooflexibundle-builder' ),
			)
		);

		$this->add_control(
			'discount_tiers',
			array(
				'label'       => esc_html__( 'Discount Tiers', 'wooflexibundle-builder' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'min_qty'        => 2,
						'discount_type'  => 'percentage',
						'discount_value' => 10,
						'tier_label'     => '10% Bundle Discount',
					),
					array(
						'min_qty'        => 3,
						'discount_type'  => 'percentage',
						'discount_value' => 15,
						'tier_label'     => '15% Bundle Discount',
					),
					array(
						'min_qty'        => 5,
						'discount_type'  => 'percentage',
						'discount_value' => 20,
						'tier_label'     => '20% Super Saver Discount',
					),
				),
				'title_field' => '{{{ min_qty }}}+ Items = {{{ discount_value }}}{{{ discount_type === "percentage" ? "%" : "$" }}} Discount',
			)
		);

		$this->end_controls_section();

		// ==================== 4. Layout & Badges ====================
		$this->start_controls_section(
			'section_layout_settings',
			array(
				'label' => esc_html__( 'Layout & Badges', 'wooflexibundle-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'           => esc_html__( 'Grid Columns', 'wooflexibundle-builder' ),
				'type'            => Controls_Manager::SELECT,
				'default'         => '3',
				'tablet_default'  => '2',
				'mobile_default'  => '1',
				'options'         => array(
					'1' => '1 Column',
					'2' => '2 Columns',
					'3' => '3 Columns',
					'4' => '4 Columns',
					'5' => '5 Columns',
					'6' => '6 Columns',
				),
				'selectors'       => array(
					'{{WRAPPER}} .wooflexi-product-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				),
			)
		);

		$this->add_responsive_control(
			'grid_gap',
			array(
				'label'      => esc_html__( 'Card Spacing (Gap)', 'wooflexibundle-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 50 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 20 ),
				'selectors'  => array(
					'{{WRAPPER}} .wooflexi-product-grid' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'badge_text',
			array(
				'label'       => esc_html__( 'Selected Badge Text', 'wooflexibundle-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'SELECTED', 'wooflexibundle-builder' ),
				'placeholder' => esc_html__( 'SELECTED', 'wooflexibundle-builder' ),
			)
		);

		$this->end_controls_section();

		// ==================== 5. Scroll Target & CTA ====================
		$this->start_controls_section(
			'section_cta_and_scroll',
			array(
				'label' => esc_html__( 'CTA & Scroll Target', 'wooflexibundle-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'scroll_target_id',
			array(
				'label'       => esc_html__( 'Checkout Scroll Target Selector', 'wooflexibundle-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '#checkout',
				'placeholder' => '#checkout or #customer_details',
				'description' => esc_html__( 'CSS ID or selector of the embedded checkout section to smooth-scroll to upon clicking CTA.', 'wooflexibundle-builder' ),
			)
		);

		$this->add_control(
			'cta_button_text',
			array(
				'label'       => esc_html__( 'CTA Button Text', 'wooflexibundle-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Order Now', 'wooflexibundle-builder' ),
				'placeholder' => esc_html__( 'Order Now', 'wooflexibundle-builder' ),
			)
		);

		$this->add_control(
			'summary_bar_position',
			array(
				'label'   => esc_html__( 'Summary Bar Position', 'wooflexibundle-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'bottom_fixed',
				'options' => array(
					'bottom_fixed'  => esc_html__( 'Bottom Sticky / Fixed Bar', 'wooflexibundle-builder' ),
					'inline_bottom' => esc_html__( 'Below Grid (Inline)', 'wooflexibundle-builder' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style Controls: Card styling, borders, active highlight color, badge, and summary bar.
	 */
	protected function register_style_controls() {

		// ==================== Card Styling ====================
		$this->start_controls_section(
			'section_style_cards',
			array(
				'label' => esc_html__( 'Product Cards', 'wooflexibundle-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_bg_color',
			array(
				'label'     => esc_html__( 'Card Background', 'wooflexibundle-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wooflexi-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'label'    => esc_html__( 'Border', 'wooflexibundle-builder' ),
				'selector' => '{{WRAPPER}} .wooflexi-card',
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wooflexibundle-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array(
					'top'      => '12',
					'right'    => '12',
					'bottom'   => '12',
					'left'     => '12',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wooflexi-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_box_shadow',
				'label'    => esc_html__( 'Box Shadow', 'wooflexibundle-builder' ),
				'selector' => '{{WRAPPER}} .wooflexi-card',
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Card Padding', 'wooflexibundle-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top'      => '16',
					'right'    => '16',
					'bottom'   => '16',
					'left'     => '16',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wooflexi-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ==================== Active / Selected State Styling ====================
		$this->start_controls_section(
			'section_style_active_state',
			array(
				'label' => esc_html__( 'Active / Selected State', 'wooflexibundle-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'active_border_color',
			array(
				'label'     => esc_html__( 'Active Border Highlight Color', 'wooflexibundle-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => array(
					'{{WRAPPER}} .wooflexi-card.is-selected' => 'border-color: {{VALUE}} !important; box-shadow: 0 0 0 2px {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'active_bg_tint',
			array(
				'label'     => esc_html__( 'Active Background Tint', 'wooflexibundle-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(37, 99, 235, 0.04)',
				'selectors' => array(
					'{{WRAPPER}} .wooflexi-card.is-selected' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_bg_color',
			array(
				'label'     => esc_html__( 'Badge Background Color', 'wooflexibundle-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => array(
					'{{WRAPPER}} .wooflexi-badge' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_text_color',
			array(
				'label'     => esc_html__( 'Badge Text Color', 'wooflexibundle-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wooflexi-badge' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// ==================== Summary Bar & CTA Styling ====================
		$this->start_controls_section(
			'section_style_summary_bar',
			array(
				'label' => esc_html__( 'Summary Bar & CTA Button', 'wooflexibundle-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'summary_bar_bg',
			array(
				'label'     => esc_html__( 'Summary Bar Background', 'wooflexibundle-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wooflexi-summary-bar' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'cta_btn_bg',
			array(
				'label'     => esc_html__( 'CTA Button Background', 'wooflexibundle-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#16a34a',
				'selectors' => array(
					'{{WRAPPER}} .wooflexi-cta-button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'cta_btn_hover_bg',
			array(
				'label'     => esc_html__( 'CTA Button Hover Background', 'wooflexibundle-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#15803d',
				'selectors' => array(
					'{{WRAPPER}} .wooflexi-cta-button:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'cta_btn_text_color',
			array(
				'label'     => esc_html__( 'CTA Button Text Color', 'wooflexibundle-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wooflexi-cta-button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'cta_typography',
				'label'    => esc_html__( 'CTA Button Typography', 'wooflexibundle-builder' ),
				'selector' => '{{WRAPPER}} .wooflexi-cta-button',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Helper to get simple & variable WooCommerce products for Select2 control.
	 *
	 * @return array
	 */
	protected function get_product_list() {
		$options = array();

		if ( ! function_exists( 'wc_get_products' ) ) {
			return $options;
		}

		$products = wc_get_products( array(
			'status' => 'publish',
			'limit'  => 100,
			'type'   => array( 'simple', 'variable' ),
		) );

		foreach ( $products as $product ) {
			$title = $product->get_name();
			$sku   = $product->get_sku();
			$type  = $product->get_type();
			$label = $title . ' (#' . $product->get_id() . ' - ' . ucfirst( $type ) . ( $sku ? ' | SKU: ' . $sku : '' ) . ')';
			$options[ $product->get_id() ] = $label;
		}

		return $options;
	}

	/**
	 * Render the widget on the frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$product_ids = ! empty( $settings['selected_product_ids'] ) ? array_map( 'absint', (array) $settings['selected_product_ids'] ) : array();

		// Default fallback if no products selected in editor: get 4 latest.
		if ( empty( $product_ids ) && function_exists( 'wc_get_products' ) ) {
			$fallback_products = wc_get_products( array(
				'status' => 'publish',
				'limit'  => 4,
				'type'   => array( 'simple', 'variable' ),
			) );
			foreach ( $fallback_products as $p ) {
				$product_ids[] = $p->get_id();
			}
		}

		if ( empty( $product_ids ) ) {
			echo '<div class="wooflexi-notice">' . esc_html__( 'Please select products in the widget settings.', 'wooflexibundle-builder' ) . '</div>';
			return;
		}

		$min_items      = ! empty( $settings['min_items_required'] ) ? max( 1, (int) $settings['min_items_required'] ) : 1;
		$max_items      = ! empty( $settings['max_items_allowed'] ) ? (int) $settings['max_items_allowed'] : 0;
		$enable_qty     = isset( $settings['enable_qty_buttons'] ) && 'yes' === $settings['enable_qty_buttons'];
		$badge_text     = ! empty( $settings['badge_text'] ) ? $settings['badge_text'] : __( 'SELECTED', 'wooflexibundle-builder' );
		$scroll_target  = ! empty( $settings['scroll_target_id'] ) ? $settings['scroll_target_id'] : '#checkout';
		$cta_text       = ! empty( $settings['cta_button_text'] ) ? $settings['cta_button_text'] : __( 'Order Now', 'wooflexibundle-builder' );
		$summary_pos    = ! empty( $settings['summary_bar_position'] ) ? $settings['summary_bar_position'] : 'bottom_fixed';
		$discount_tiers = ! empty( $settings['discount_tiers'] ) ? $settings['discount_tiers'] : array();

		// JSON encode discount tiers for frontend script.
		$tiers_json = esc_attr( wp_json_encode( $discount_tiers ) );

		?>
		<div class="wooflexi-bundle-wrapper <?php echo esc_attr( 'pos-' . $summary_pos ); ?>"
			data-min-items="<?php echo esc_attr( $min_items ); ?>"
			data-max-items="<?php echo esc_attr( $max_items ); ?>"
			data-scroll-target="<?php echo esc_attr( $scroll_target ); ?>"
			data-tiers="<?php echo $tiers_json; ?>">

			<!-- Product Grid -->
			<div class="wooflexi-product-grid">
				<?php
				foreach ( $product_ids as $p_id ) {
					$product = wc_get_product( $p_id );
					if ( ! $product || ! $product->is_purchasable() ) {
						continue;
					}

					$is_variable = $product->is_type( 'variable' );
					$img_url     = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' );
					if ( ! $img_url ) {
						$img_url = wc_placeholder_img_src( 'woocommerce_thumbnail' );
					}
					$raw_price = (float) $product->get_price();

					// Prepare variations if variable product.
					$variations_json = '';
					$attributes_data = array();

					if ( $is_variable ) {
						$available_variations = $product->get_available_variations();
						$variations_json      = esc_attr( wp_json_encode( $available_variations ) );
						$attributes_data      = $product->get_variation_attributes();
						// Default price from first variation if available.
						if ( ! empty( $available_variations[0]['display_price'] ) ) {
							$raw_price = (float) $available_variations[0]['display_price'];
						}
					}
					?>
					<div class="wooflexi-card" 
						data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
						data-is-variable="<?php echo $is_variable ? '1' : '0'; ?>"
						data-base-price="<?php echo esc_attr( $raw_price ); ?>"
						<?php if ( $is_variable ) : ?>
							data-product-variations="<?php echo $variations_json; ?>"
						<?php endif; ?>>

						<!-- Active Selected Badge -->
						<div class="wooflexi-badge"><?php echo esc_html( $badge_text ); ?></div>

						<!-- Card Checkbox Trigger -->
						<div class="wooflexi-card-select-trigger">
							<span class="wooflexi-checkbox-icon"></span>
						</div>

						<!-- Product Image -->
						<div class="wooflexi-card-thumb">
							<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" class="wooflexi-img" />
						</div>

						<!-- Product Details -->
						<div class="wooflexi-card-body">
							<h4 class="wooflexi-card-title"><?php echo esc_html( $product->get_name() ); ?></h4>
							
							<div class="wooflexi-card-price">
								<?php echo wp_kses_post( $product->get_price_html() ); ?>
							</div>

							<!-- Variable Product Dropdowns -->
							<?php if ( $is_variable && ! empty( $attributes_data ) ) : ?>
								<div class="wooflexi-variations-form" onclick="event.stopPropagation();">
									<?php foreach ( $attributes_data as $attr_name => $options ) : 
										$attr_label = wc_attribute_label( $attr_name );
										?>
										<div class="wooflexi-variation-row">
											<label class="wooflexi-variation-label"><?php echo esc_html( $attr_label ); ?>:</label>
											<select class="wooflexi-variation-select" data-attribute-name="<?php echo esc_attr( sanitize_title( $attr_name ) ); ?>">
												<option value=""><?php echo esc_html( sprintf( __( 'Choose %s', 'wooflexibundle-builder' ), $attr_label ) ); ?></option>
												<?php foreach ( $options as $opt ) : ?>
													<option value="<?php echo esc_attr( $opt ); ?>">
														<?php echo esc_html( $opt ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<!-- Quantity Control & Toggle -->
							<div class="wooflexi-card-actions" onclick="event.stopPropagation();">
								<?php if ( $enable_qty ) : ?>
									<div class="wooflexi-qty-group">
										<button type="button" class="wooflexi-qty-btn wooflexi-qty-minus" aria-label="Decrease">&minus;</button>
										<input type="number" class="wooflexi-qty-input" value="1" min="1" max="99" />
										<button type="button" class="wooflexi-qty-btn wooflexi-qty-plus" aria-label="Increase">&#43;</button>
									</div>
								<?php else : ?>
									<input type="hidden" class="wooflexi-qty-input" value="1" />
								<?php endif; ?>

								<button type="button" class="wooflexi-toggle-btn">
									<span class="btn-text-select"><?php esc_html_e( 'Select', 'wooflexibundle-builder' ); ?></span>
									<span class="btn-text-selected"><?php esc_html_e( 'Selected', 'wooflexibundle-builder' ); ?></span>
								</button>
							</div>
						</div>
					</div>
					<?php
				}
				?>
			</div>

			<!-- Dynamic Summary / Total Bar -->
			<div class="wooflexi-summary-bar">
				<div class="wooflexi-summary-inner">
					<div class="wooflexi-summary-info">
						<div class="wooflexi-summary-counts">
							<span class="wooflexi-items-count">0</span> <?php esc_html_e( 'items selected', 'wooflexibundle-builder' ); ?>
							<span class="wooflexi-discount-tag" style="display:none;"></span>
						</div>
						<div class="wooflexi-summary-pricing">
							<span class="wooflexi-subtotal-strike" style="display:none;"></span>
							<span class="wooflexi-final-price">$0.00</span>
						</div>
						<div class="wooflexi-rule-notice" style="display:none;"></div>
					</div>

					<div class="wooflexi-summary-action">
						<button type="button" class="wooflexi-cta-button">
							<span class="wooflexi-cta-icon">&#128722;</span>
							<span class="wooflexi-cta-text"><?php echo esc_html( $cta_text ); ?></span>
							<span class="wooflexi-cta-loader" style="display:none;"></span>
						</button>
					</div>
				</div>
			</div>

		</div>
		<?php
	}
}

