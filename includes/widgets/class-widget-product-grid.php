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

/**
 * Class Widget_Product_Grid
 *
 * Modular Elementor Widget: Product Selector & Grid (with Tiered Discounts).
 */
class Widget_Product_Grid extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wcsc_product_grid';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Product Selector & Grid (Tiered Discounts)', 'wc-smart-checkout-builder' );
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
		return array( 'wcsc-category', 'woocommerce-elements' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'product grid', 'bundle', 'tiered discount', 'selector', 'smart checkout', 'woocommerce' );
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
	 * Register Content controls.
	 */
	protected function register_content_controls() {

		// ----------------------------------------------------
		// 1. Product Query
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_product_query',
			array(
				'label' => esc_html__( 'Product Query', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'selected_product_ids',
			array(
				'label'       => esc_html__( 'Select Products', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => Product_Handler::get_product_options(),
				'description' => esc_html__( 'Choose Simple and Variable WooCommerce products to display in the grid.', 'wc-smart-checkout-builder' ),
			)
		);

		$this->end_controls_section();

		// ----------------------------------------------------
		// 2. Quantity & Min/Max Rules
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_quantity_rules',
			array(
				'label' => esc_html__( 'Min/Max Quantity Rules', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'min_items_required',
			array(
				'label'       => esc_html__( 'Minimum Items Required', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 100,
				'step'        => 1,
				'default'     => 1,
				'description' => esc_html__( 'Minimum total quantity of items customer must select before checking out.', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'max_items_allowed',
			array(
				'label'       => esc_html__( 'Maximum Items Allowed (0 for unlimited)', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 100,
				'step'        => 1,
				'default'     => 0,
				'description' => esc_html__( 'Optional cap on total quantity allowed in the selection (0 = no maximum).', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'show_quantity_buttons',
			array(
				'label'        => esc_html__( 'Enable (+ / -) Quantity Buttons', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'No', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// ----------------------------------------------------
		// 3. Tiered Discounts Settings (Repeater)
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_tiered_discounts',
			array(
				'label' => esc_html__( 'Tiered Discount Settings', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_tiered_discount',
			array(
				'label'        => esc_html__( 'Enable Tiered Discounts', 'wc-smart-checkout-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wc-smart-checkout-builder' ),
				'label_off'    => esc_html__( 'No', 'wc-smart-checkout-builder' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'min_qty',
			array(
				'label'   => esc_html__( 'Min Item Count', 'wc-smart-checkout-builder' ),
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
					'fixed'      => esc_html__( 'Fixed Amount ($)', 'wc-smart-checkout-builder' ),
				),
			)
		);

		$repeater->add_control(
			'discount_value',
			array(
				'label'   => esc_html__( 'Discount Value', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 0,
				'step'    => 0.1,
			)
		);

		$repeater->add_control(
			'tier_label',
			array(
				'label'       => esc_html__( 'Discount Label', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'e.g. 10% Discount Applied', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'discount_tiers',
			array(
				'label'       => esc_html__( 'Discount Tiers', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'condition'   => array(
					'enable_tiered_discount' => 'yes',
				),
				'default'     => array(
					array(
						'min_qty'        => 2,
						'discount_type'  => 'percentage',
						'discount_value' => 10,
						'tier_label'     => '10% Tier Discount',
					),
					array(
						'min_qty'        => 3,
						'discount_type'  => 'percentage',
						'discount_value' => 15,
						'tier_label'     => '15% Super Saver Discount',
					),
					array(
						'min_qty'        => 5,
						'discount_type'  => 'percentage',
						'discount_value' => 20,
						'tier_label'     => '20% Mega Saver Discount',
					),
				),
				'title_field' => '{{{ min_qty }}}+ Items = {{{ discount_value }}}{{{ discount_type === "percentage" ? "%" : "$" }}} Discount',
			)
		);

		$this->end_controls_section();

		// ----------------------------------------------------
		// 4. Layout & Visual Badges
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_layout_settings',
			array(
				'label' => esc_html__( 'Layout & Badges', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'           => esc_html__( 'Grid Columns', 'wc-smart-checkout-builder' ),
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
				),
				'selectors'       => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-modular-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				),
			)
		);

		$this->add_responsive_control(
			'grid_gap',
			array(
				'label'      => esc_html__( 'Card Spacing (Gap)', 'wc-smart-checkout-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 60 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 20 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-modular-grid' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'badge_text',
			array(
				'label'       => esc_html__( 'Active Selected Badge Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'SELECTED', 'wc-smart-checkout-builder' ),
				'placeholder' => esc_html__( 'SELECTED', 'wc-smart-checkout-builder' ),
			)
		);

		$this->end_controls_section();

		// ----------------------------------------------------
		// 5. Summary Bar & Checkout Scroll
		// ----------------------------------------------------
		$this->start_controls_section(
			'section_cta_and_scroll',
			array(
				'label' => esc_html__( 'Summary Bar & Checkout Scroll', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'scroll_target_id',
			array(
				'label'       => esc_html__( 'Checkout Form Target Selector', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '#wcsc-embedded-checkout',
				'placeholder' => '#wcsc-embedded-checkout, #checkout, or #customer_details',
				'description' => esc_html__( 'CSS ID or selector of the embedded checkout form widget to smooth-scroll to on Order CTA click.', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'cta_button_text',
			array(
				'label'       => esc_html__( 'CTA Button Text', 'wc-smart-checkout-builder' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Order Now', 'wc-smart-checkout-builder' ),
				'placeholder' => esc_html__( 'Order Now', 'wc-smart-checkout-builder' ),
			)
		);

		$this->add_control(
			'summary_bar_position',
			array(
				'label'   => esc_html__( 'Summary Bar Display', 'wc-smart-checkout-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'inline_bottom',
				'options' => array(
					'inline_bottom' => esc_html__( 'Below Grid (Inline)', 'wc-smart-checkout-builder' ),
					'bottom_fixed'  => esc_html__( 'Sticky Bottom Bar', 'wc-smart-checkout-builder' ),
					'none'          => esc_html__( 'Hidden', 'wc-smart-checkout-builder' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register Style controls.
	 */
	protected function register_style_controls() {

		// Card Styling
		$this->start_controls_section(
			'section_style_cards',
			array(
				'label' => esc_html__( 'Product Cards', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_bg_color',
			array(
				'label'     => esc_html__( 'Card Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-grid-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'label'    => esc_html__( 'Border', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcsc-grid-card',
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'wc-smart-checkout-builder' ),
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
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-grid-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_box_shadow',
				'label'    => esc_html__( 'Box Shadow', 'wc-smart-checkout-builder' ),
				'selector' => '{{WRAPPER}} .wcas-checkout-wrapper .wcsc-grid-card',
			)
		);

		$this->end_controls_section();

		// Active Selection State
		$this->start_controls_section(
			'section_style_active_state',
			array(
				'label' => esc_html__( 'Active Selected State', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'active_border_color',
			array(
				'label'     => esc_html__( 'Active Border Highlight Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-grid-card.is-selected' => 'border-color: {{VALUE}} !important; box-shadow: 0 0 0 2px {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'active_bg_tint',
			array(
				'label'     => esc_html__( 'Active Background Tint', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(37, 99, 235, 0.04)',
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-grid-card.is-selected' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_bg_color',
			array(
				'label'     => esc_html__( 'Badge Background Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-card-badge' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_text_color',
			array(
				'label'     => esc_html__( 'Badge Text Color', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-card-badge' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Summary Bar & CTA
		$this->start_controls_section(
			'section_style_summary_bar',
			array(
				'label' => esc_html__( 'Summary Bar & CTA Button', 'wc-smart-checkout-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'cta_btn_bg',
			array(
				'label'     => esc_html__( 'CTA Button Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#16a34a',
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-grid-cta-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'cta_btn_hover_bg',
			array(
				'label'     => esc_html__( 'CTA Button Hover Background', 'wc-smart-checkout-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#15803d',
				'selectors' => array(
					'{{WRAPPER}} .wcas-checkout-wrapper .wcsc-grid-cta-btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$product_ids = ! empty( $settings['selected_product_ids'] ) ? array_map( 'absint', (array) $settings['selected_product_ids'] ) : array();

		// Fallback for editor if empty
		if ( empty( $product_ids ) && function_exists( 'wc_get_products' ) ) {
			$fallback = wc_get_products( array(
				'status' => 'publish',
				'limit'  => 3,
				'type'   => array( 'simple', 'variable' ),
			) );
			foreach ( $fallback as $p ) {
				$product_ids[] = $p->get_id();
			}
		}

		if ( empty( $product_ids ) ) {
			echo '<div class="wcas-checkout-wrapper"><p class="wcsc-notice">' . esc_html__( 'Please select products in the widget settings.', 'wc-smart-checkout-builder' ) . '</p></div>';
			return;
		}

		$min_items      = ! empty( $settings['min_items_required'] ) ? max( 1, (int) $settings['min_items_required'] ) : 1;
		$max_items      = ! empty( $settings['max_items_allowed'] ) ? (int) $settings['max_items_allowed'] : 0;
		$enable_qty     = isset( $settings['show_quantity_buttons'] ) && 'yes' === $settings['show_quantity_buttons'];
		$enable_tier    = isset( $settings['enable_tiered_discount'] ) && 'yes' === $settings['enable_tiered_discount'];
		$badge_text     = ! empty( $settings['badge_text'] ) ? $settings['badge_text'] : __( 'SELECTED', 'wc-smart-checkout-builder' );
		$scroll_target  = ! empty( $settings['scroll_target_id'] ) ? $settings['scroll_target_id'] : '#wcsc-embedded-checkout';
		$cta_text       = ! empty( $settings['cta_button_text'] ) ? $settings['cta_button_text'] : __( 'Order Now', 'wc-smart-checkout-builder' );
		$summary_pos    = ! empty( $settings['summary_bar_position'] ) ? $settings['summary_bar_position'] : 'inline_bottom';
		$discount_tiers = $enable_tier && ! empty( $settings['discount_tiers'] ) ? $settings['discount_tiers'] : array();

		$tiers_json = esc_attr( wp_json_encode( $discount_tiers ) );
		?>
		<div class="wcas-checkout-wrapper wcsc-product-grid-container"
			data-min-items="<?php echo esc_attr( $min_items ); ?>"
			data-max-items="<?php echo esc_attr( $max_items ); ?>"
			data-scroll-target="<?php echo esc_attr( $scroll_target ); ?>"
			data-tiers="<?php echo $tiers_json; ?>">

			<div class="wcsc-modular-grid">
				<?php
				$first = true;
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

					// Auto-select first item by default
					$is_initial_selected = $first;
					$first = false;
					?>
					<div class="wcsc-grid-card <?php echo $is_initial_selected ? 'is-selected' : ''; ?>"
						data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
						data-is-variable="<?php echo $is_variable ? '1' : '0'; ?>"
						data-base-price="<?php echo esc_attr( $raw_price ); ?>">

						<!-- Selected Badge -->
						<div class="wcsc-card-badge"><?php echo esc_html( $badge_text ); ?></div>

						<!-- Checkbox Icon Indicator -->
						<div class="wcsc-card-check">
							<span class="wcsc-check-circle"></span>
						</div>

						<!-- Product Thumbnail -->
						<div class="wcsc-card-img-wrap">
							<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" class="wcsc-card-thumb" />
						</div>

						<!-- Card Content -->
						<div class="wcsc-card-content">
							<h4 class="wcsc-card-title"><?php echo esc_html( $product->get_name() ); ?></h4>
							<div class="wcsc-card-price">
								<?php echo wp_kses_post( $product->get_price_html() ); ?>
							</div>

							<!-- Quantity and Action Buttons -->
							<div class="wcsc-card-footer" onclick="event.stopPropagation();">
								<?php if ( $enable_qty ) : ?>
									<div class="wcsc-qty-stepper">
										<button type="button" class="wcsc-qty-btn wcsc-qty-minus" aria-label="Decrease">&minus;</button>
										<input type="number" class="wcsc-qty-val" value="1" min="1" max="99" />
										<button type="button" class="wcsc-qty-btn wcsc-qty-plus" aria-label="Increase">&#43;</button>
									</div>
								<?php else : ?>
									<input type="hidden" class="wcsc-qty-val" value="1" />
								<?php endif; ?>

								<button type="button" class="wcsc-card-select-btn">
									<span class="btn-text-unselected"><?php esc_html_e( 'Select', 'wc-smart-checkout-builder' ); ?></span>
									<span class="btn-text-selected"><?php esc_html_e( 'Selected', 'wc-smart-checkout-builder' ); ?></span>
								</button>
							</div>
						</div>
					</div>
					<?php
				}
				?>
			</div>

			<!-- Dynamic Summary Bar -->
			<?php if ( 'none' !== $summary_pos ) : ?>
				<div class="wcsc-grid-summary-bar <?php echo esc_attr( 'pos-' . $summary_pos ); ?>">
					<div class="wcsc-summary-inner">
						<div class="wcsc-summary-details">
							<div class="wcsc-summary-count-line">
								<span class="wcsc-total-items-badge">1</span> <?php esc_html_e( 'item(s) selected', 'wc-smart-checkout-builder' ); ?>
								<span class="wcsc-applied-tier-badge" style="display:none;"></span>
							</div>
							<div class="wcsc-summary-price-line">
								<span class="wcsc-subtotal-strike" style="display:none;"></span>
								<span class="wcsc-calculated-price">$0.00</span>
							</div>
							<div class="wcsc-rule-warning" style="display:none;"></div>
						</div>

						<div class="wcsc-summary-cta-wrap">
							<button type="button" class="wcsc-grid-cta-btn">
								<span class="wcsc-cta-text"><?php echo esc_html( $cta_text ); ?></span>
								<span class="wcsc-cta-spinner" style="display:none;"></span>
							</button>
						</div>
					</div>
				</div>
			<?php endif; ?>

		</div>
		<?php
	}
}
