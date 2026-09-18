<?php
namespace WCSC;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Plugin
 *
 * Core singleton manager for the WooCommerce Product Variation & Checkout Elementor Widget.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Initialize Checkout Handler.
		Checkout_Handler::init();

		// Elementor Category & Widget Registration.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_categories' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		// Backwards compatibility for older Elementor versions.
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets_legacy' ) );

		// Register Scripts & Styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_frontend_assets' ) );

		// AJAX search products endpoint for Elementor controls.
		add_action( 'wp_ajax_wcsc_search_products', array( $this, 'ajax_search_products' ) );

		// Register Custom Post Type for Landing Pages.
		add_action( 'init', array( $this, 'register_post_type' ) );
		// Ensure Elementor support is enabled for the new CPT.
		add_action( 'init', array( $this, 'add_elementor_support' ), 999 );
		
		// Admin Menu & Settings
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Custom CSS injection
		add_action( 'wp_head', array( $this, 'print_custom_css' ), 100 );

		// Custom Thank You Redirect & Return URL Filter for Tracking Safety
		add_action( 'template_redirect', array( $this, 'custom_thank_you_redirect' ) );
		add_filter( 'woocommerce_get_checkout_order_received_url', array( $this, 'filter_order_received_url' ), 10, 2 );
		add_filter( 'woocommerce_get_return_url', array( $this, 'filter_order_received_url' ), 10, 2 );
		add_filter( 'woocommerce_is_order_received_page', array( $this, 'filter_is_order_received_page' ) );

		// Elementor Editor Top-Level Panel Script (Collapsing Accordions by Default)
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_panel_scripts' ) );

		// Add template fallback for Landing Pages
		add_filter( 'template_include', array( $this, 'landing_page_template_fallback' ), 99 );
	}

	/**
	 * Register custom post type 'wcsc_page' for Landing Pages.
	 */
	public function register_post_type() {
		$cpt_slug = get_option( 'wcsc_cpt_slug', 'landing-page' );
		$cpt_slug = sanitize_title( trim( $cpt_slug ) );
		if ( empty( $cpt_slug ) ) {
			$cpt_slug = 'landing-page';
		}

		$labels = array(
			'name'               => _x( 'Landing Pages', 'post type general name', 'wc-smart-checkout-builder' ),
			'singular_name'      => _x( 'Landing Page', 'post type singular name', 'wc-smart-checkout-builder' ),
			'menu_name'          => _x( 'Landing Pages', 'admin menu', 'wc-smart-checkout-builder' ),
			'name_admin_bar'     => _x( 'Landing Page', 'add new on admin bar', 'wc-smart-checkout-builder' ),
			'add_new'            => _x( 'Add New', 'landing page', 'wc-smart-checkout-builder' ),
			'add_new_item'       => __( 'Add New Page', 'wc-smart-checkout-builder' ),
			'new_item'           => __( 'New Page', 'wc-smart-checkout-builder' ),
			'edit_item'          => __( 'Edit Page', 'wc-smart-checkout-builder' ),
			'view_item'          => __( 'View Page', 'wc-smart-checkout-builder' ),
			'all_items'          => __( 'All Pages', 'wc-smart-checkout-builder' ),
			'search_items'       => __( 'Search Pages', 'wc-smart-checkout-builder' ),
			'not_found'          => __( 'No pages found.', 'wc-smart-checkout-builder' ),
			'not_found_in_trash' => __( 'No pages found in Trash.', 'wc-smart-checkout-builder' )
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Landing pages for Smart Checkout Builder.', 'wc-smart-checkout-builder' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => $cpt_slug, 'with_front' => false ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-cart',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		);

		register_post_type( 'wcsc_page', $args );

		if ( 'yes' === get_option( 'wcsc_flush_rewrite_rules' ) ) {
			flush_rewrite_rules( false );
			delete_option( 'wcsc_flush_rewrite_rules' );
		}
	}

	/**
	 * Enable Elementor support for 'wcsc_page' by default.
	 */
	public function add_elementor_support() {
		$cpt_support = get_option( 'elementor_cpt_support' );

		if ( ! $cpt_support ) {
			$cpt_support = array( 'page', 'post' );
		}

		if ( ! in_array( 'wcsc_page', $cpt_support ) ) {
			$cpt_support[] = 'wcsc_page';
			update_option( 'elementor_cpt_support', $cpt_support );
		}
	}

	/**
	 * Register custom Elementor widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_categories( $elements_manager ) {
		$elements_manager->add_category(
			'wcsc-category',
			array(
				'title' => esc_html__( 'WC Smart Checkout', 'wc-smart-checkout-builder' ),
				'icon'  => 'fa fa-shopping-cart',
			)
		);
	}

	/**
	 * Register the widget (Elementor >= 3.5.0).
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widgets( $widgets_manager ) {
		require_once WCSC_PATH . 'includes/class-elementor-widget.php';
		require_once WCSC_PATH . 'includes/class-thankyou-widget.php';
		$widgets_manager->register( new Elementor_Widget() );
		$widgets_manager->register( new ThankYou_Widget() );
	}

	/**
	 * Register the widget for older Elementor versions (< 3.5.0).
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widgets_legacy( $widgets_manager ) {
		if ( method_exists( $widgets_manager, 'register' ) ) {
			return; // Handled by register_widgets.
		}
		require_once WCSC_PATH . 'includes/class-elementor-widget.php';
		require_once WCSC_PATH . 'includes/class-thankyou-widget.php';
		$widgets_manager->register_widget_type( new Elementor_Widget() );
		$widgets_manager->register_widget_type( new ThankYou_Widget() );
	}

	/**
	 * Register frontend & editor CSS/JS assets.
	 */
	public function register_assets() {
		wp_register_style(
			'wcsc-widget-style',
			WCSC_URL . 'assets/css/widget.css',
			array(),
			WCSC_VERSION
		);

		wp_register_script(
			'wcsc-widget-script',
			WCSC_URL . 'assets/js/widget.js',
			array( 'jquery' ),
			WCSC_VERSION,
			true
		);

		wp_localize_script(
			'wcsc-widget-script',
			'wcsc_params',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'wcsc_checkout_nonce' ),
				'is_elementor_edit' => class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode(),
				'i18n'            => array(
					'select_variation' => esc_html__( 'Please select a variation option.', 'wc-smart-checkout-builder' ),
					'out_of_stock'     => esc_html__( 'Out of stock', 'wc-smart-checkout-builder' ),
					'in_stock'         => esc_html__( 'In stock', 'wc-smart-checkout-builder' ),
					'updating_cart'    => esc_html__( 'Updating checkout...', 'wc-smart-checkout-builder' ),
				),
			)
		);
	}

	/**
	 * Enqueue styles & scripts inside Elementor editor preview.
	 */
	public function enqueue_editor_assets() {
		$this->register_assets();
		wp_enqueue_style( 'wcsc-widget-style' );
		wp_enqueue_script( 'wcsc-widget-script' );
	}

	/**
	 * Enqueue top-level Elementor editor panel scripts.
	 */
	public function enqueue_editor_panel_scripts() {
		wp_enqueue_script(
			'wcsc-editor-panel-script',
			WCSC_URL . 'assets/js/editor.js',
			array( 'jquery' ),
			WCSC_VERSION,
			true
		);
	}

	/**
	 * Enqueue assets on frontend when needed.
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'wcsc-widget-style' );
	}

	/**
	 * AJAX endpoint to search products for Elementor manual selector.
	 */
	public function ajax_search_products() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'wc-smart-checkout-builder' ) ) );
		}

		$keyword = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$results = Product_Handler::search_products( $keyword, 30 );

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Register admin menu for plugin.
	 */
	public function register_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=wcsc_page',
			__( 'Settings', 'wc-smart-checkout-builder' ),
			__( 'Settings', 'wc-smart-checkout-builder' ),
			'manage_options',
			'wcsc-settings',
			array( $this, 'settings_page_html' )
		);
	}

	/**
	 * Register settings fields.
	 */
	public function register_settings() {
		register_setting( 'wcsc_settings_group', 'wcsc_cpt_slug', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_cpt_slug' ),
			'default'           => 'landing-page',
		) );
		register_setting( 'wcsc_settings_group', 'wcsc_enable_thank_you' );
		register_setting( 'wcsc_settings_group', 'wcsc_thank_you_page_id' );
		register_setting( 'wcsc_settings_group', 'wcsc_custom_css' );
	}

	/**
	 * Sanitize custom post type URL slug and mark rewrite rules for flush if changed.
	 *
	 * @param string $slug
	 * @return string
	 */
	public function sanitize_cpt_slug( $slug ) {
		$slug = sanitize_title( trim( $slug ) );
		if ( empty( $slug ) ) {
			$slug = 'landing-page';
		}
		$old_slug = get_option( 'wcsc_cpt_slug', 'landing-page' );
		if ( $old_slug !== $slug ) {
			update_option( 'wcsc_flush_rewrite_rules', 'yes' );
		}
		return $slug;
	}

	/**
	 * Filter WooCommerce is_order_received_page condition to recognize custom thank you page.
	 *
	 * @param bool $is_order_received
	 * @return bool
	 */
	public function filter_is_order_received_page( $is_order_received ) {
		if ( $is_order_received ) {
			return true;
		}
		$enable_thank_you   = get_option( 'wcsc_enable_thank_you' );
		$thank_you_page_id = get_option( 'wcsc_thank_you_page_id' );
		if ( $enable_thank_you && $thank_you_page_id && is_page( $thank_you_page_id ) ) {
			if ( ! empty( $_GET['order_id'] ) || ! empty( $_GET['order-received'] ) ) {
				return true;
			}
		}
		return $is_order_received;
	}

	/**
	 * HTML for Settings Page.
	 */
	public function settings_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$pages         = get_pages();
		$landing_pages = get_posts( array( 'post_type' => 'wcsc_page', 'numberposts' => -1 ) );
		$all_pages     = array_merge( $pages, $landing_pages );
		$selected_id   = get_option( 'wcsc_thank_you_page_id' );
		$edit_url      = $selected_id ? get_edit_post_link( $selected_id ) : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WC Smart Checkout Builder Settings', 'wc-smart-checkout-builder' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wcsc_settings_group' );
				do_settings_sections( 'wcsc_settings_group' );
				?>
				
				<h2 class="title"><?php esc_html_e( 'Landing Page URL Slug / Permalink Settings', 'wc-smart-checkout-builder' ); ?></h2>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="wcsc_cpt_slug"><?php esc_html_e( 'Landing Page URL Slug', 'wc-smart-checkout-builder' ); ?></label>
						</th>
						<td>
							<div style="display: flex; align-items: center; gap: 4px; font-family: monospace; font-size: 14px; flex-wrap: wrap;">
								<span><?php echo esc_html( home_url( '/' ) ); ?></span>
								<input type="text" name="wcsc_cpt_slug" id="wcsc_cpt_slug" value="<?php echo esc_attr( get_option( 'wcsc_cpt_slug', 'landing-page' ) ); ?>" class="regular-text" style="max-width: 180px; font-weight: 600;" placeholder="landing-page" />
								<span>/my-landing-page/</span>
							</div>
							<p class="description" style="margin-top: 6px;">
								<?php esc_html_e( 'Change the URL base slug for your Landing Pages (e.g. "offer", "deal", "order"). Rewrite rules update automatically upon saving without 404 errors.', 'wc-smart-checkout-builder' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<h2 class="title" style="margin-top: 30px;"><?php esc_html_e( 'Thank You Page Settings', 'wc-smart-checkout-builder' ); ?></h2>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Enable Custom Thank You Page', 'wc-smart-checkout-builder' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="wcsc_enable_thank_you" value="1" <?php checked( 1, get_option( 'wcsc_enable_thank_you' ), true ); ?> />
								<?php esc_html_e( 'Redirect customers to a custom thank you page upon order completion', 'wc-smart-checkout-builder' ); ?>
							</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Select Thank You Page', 'wc-smart-checkout-builder' ); ?></th>
						<td>
							<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
								<select name="wcsc_thank_you_page_id" id="wcsc_thank_you_page_id" style="min-width: 260px;">
									<option value=""><?php esc_html_e( '— Select a page —', 'wc-smart-checkout-builder' ); ?></option>
									<?php foreach ( $all_pages as $p ) : ?>
										<option value="<?php echo esc_attr( $p->ID ); ?>" <?php selected( $p->ID, $selected_id ); ?>>
											<?php echo esc_html( $p->post_title . ' (' . $p->post_type . ')' ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<a href="<?php echo esc_url( $edit_url ? $edit_url : '#' ); ?>" 
									id="wcsc-edit-page-btn" 
									class="button button-secondary" 
									target="_blank" 
									style="<?php echo $edit_url ? 'display: inline-flex; align-items: center; gap: 4px;' : 'display: none; align-items: center; gap: 4px;'; ?>">
									<span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px;"></span>
									<?php esc_html_e( 'Edit Page', 'wc-smart-checkout-builder' ); ?>
								</a>
							</div>
							<p class="description"><?php esc_html_e( 'Select the page to redirect customers to after a successful order. Ensure you add the "Thank You / Order Details" Elementor widget to this page.', 'wc-smart-checkout-builder' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title" style="margin-top: 30px;"><?php esc_html_e( 'Custom CSS', 'wc-smart-checkout-builder' ); ?></h2>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Custom CSS Code', 'wc-smart-checkout-builder' ); ?></th>
						<td>
							<textarea name="wcsc_custom_css" id="wcsc_custom_css" rows="10" cols="60" class="large-text code" placeholder="/* Add custom CSS rules here... */"><?php echo esc_textarea( get_option( 'wcsc_custom_css', '' ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Custom CSS will automatically be loaded on frontend pages.', 'wc-smart-checkout-builder' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var select = document.getElementById('wcsc_thank_you_page_id');
			var editBtn = document.getElementById('wcsc-edit-page-btn');
			var adminPostUrl = '<?php echo esc_url( admin_url( 'post.php?action=edit&post=' ) ); ?>';
			if (select && editBtn) {
				select.addEventListener('change', function() {
					var val = this.value;
					if (val) {
						editBtn.href = adminPostUrl + encodeURIComponent(val);
						editBtn.style.display = 'inline-flex';
						editBtn.style.alignItems = 'center';
						editBtn.style.gap = '4px';
					} else {
						editBtn.style.display = 'none';
					}
				});
			}
		});
		</script>
		<?php
	}

	/**
	 * Output custom CSS saved in settings.
	 */
	public function print_custom_css() {
		$custom_css = get_option( 'wcsc_custom_css', '' );
		if ( ! empty( $custom_css ) ) {
			echo "\n<!-- WC Smart Checkout Builder Custom CSS -->\n";
			echo "<style type=\"text/css\" id=\"wcsc-custom-css\">\n";
			echo wp_strip_all_tags( $custom_css ) . "\n";
			echo "</style>\n";
		}
	}

	/**
	 * Filter the order received return URL so that WooCommerce redirects
	 * directly to the custom Thank You page with tracking-safe query parameters.
	 *
	 * @param string    $return_url Default return URL.
	 * @param \WC_Order $order      The WooCommerce order object.
	 * @return string
	 */
	public function filter_order_received_url( $return_url, $order = null ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return $return_url;
		}

		$enable_thank_you   = get_option( 'wcsc_enable_thank_you' );
		$thank_you_page_id = get_option( 'wcsc_thank_you_page_id' );

		if ( empty( $enable_thank_you ) || empty( $thank_you_page_id ) ) {
			return $return_url;
		}

		$page = get_post( $thank_you_page_id );
		if ( ! $page || 'trash' === $page->post_status ) {
			return $return_url;
		}

		$custom_url = get_permalink( $thank_you_page_id );
		if ( ! $custom_url ) {
			return $return_url;
		}

		return add_query_arg(
			array(
				'order_id' => $order->get_id(),
				'key'      => $order->get_order_key(),
			),
			$custom_url
		);
	}

	/**
	 * Custom Thank You Redirect logic for direct visits or fallbacks.
	 */
	public function custom_thank_you_redirect() {
		if ( ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		
		$enable_thank_you   = get_option( 'wcsc_enable_thank_you' );
		$thank_you_page_id = get_option( 'wcsc_thank_you_page_id' );
		
		if ( empty( $enable_thank_you ) || empty( $thank_you_page_id ) ) {
			return;
		}
		
		$page = get_post( $thank_you_page_id );
		if ( ! $page || 'trash' === $page->post_status ) {
			return;
		}

		global $wp;
		
		// Order ID from URL
		$order_id = isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : 0;
		if ( ! $order_id && isset( $_GET['order-received'] ) ) {
			$order_id = absint( $_GET['order-received'] );
		}
		if ( ! $order_id && isset( $_GET['order_id'] ) ) {
			$order_id = absint( $_GET['order_id'] );
		}

		$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		
		if ( ! $order_id || ! $order_key ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_order_key() !== $order_key ) {
			return; // Invalid order or key mismatch. Fallback to native.
		}
		
		$redirect_url = get_permalink( $thank_you_page_id );
		if ( ! $redirect_url ) {
			return;
		}
		
		$redirect_url = add_query_arg(
			array(
				'order_id' => $order_id,
				'key'      => $order_key,
			),
			$redirect_url
		);
		
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Provide a fallback template for Landing Pages if the theme doesn't handle them.
	 * This ensures the_content() is called so Elementor can load successfully.
	 */
	public function landing_page_template_fallback( $template ) {
		if ( is_singular( 'wcsc_page' ) ) {
			$page_template = get_post_meta( get_the_ID(), '_wp_page_template', true );
			if ( in_array( $page_template, array( 'elementor_canvas', 'elementor_header_footer', 'elementor_theme' ) ) ) {
				return $template;
			}
			
			// If no custom template is selected, default to Elementor Canvas
			// to guarantee a blank canvas that doesn't conflict with theme wrappers
			if ( empty( $page_template ) || 'default' === $page_template ) {
				if ( defined( 'ELEMENTOR_PATH' ) ) {
					$canvas_template = ELEMENTOR_PATH . '/modules/page-templates/templates/canvas.php';
					if ( file_exists( $canvas_template ) ) {
						return $canvas_template;
					}
				}
			}
		}
		return $template;
	}
}
