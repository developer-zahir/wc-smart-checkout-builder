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

		// Custom Thank You Redirect
		add_action( 'template_redirect', array( $this, 'custom_thank_you_redirect' ) );

		// Add template fallback for Landing Pages
		add_filter( 'template_include', array( $this, 'landing_page_template_fallback' ), 99 );
	}

	/**
	 * Register custom post type 'wcsc_page' for Landing Pages.
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Landing Pages', 'post type general name', 'wc-smart-checkout-builder' ),
			'singular_name'      => _x( 'Landing Page', 'post type singular name', 'wc-smart-checkout-builder' ),
			'menu_name'          => _x( 'Landing Pages', 'admin menu', 'wc-smart-checkout-builder' ),
			'name_admin_bar'     => _x( 'Landing Page', 'add new on admin bar', 'wc-smart-checkout-builder' ),
			'add_new'            => _x( 'Add New', 'landing page', 'wc-smart-checkout-builder' ),
			'add_new_item'       => __( 'Add New Landing Page', 'wc-smart-checkout-builder' ),
			'new_item'           => __( 'New Landing Page', 'wc-smart-checkout-builder' ),
			'edit_item'          => __( 'Edit Landing Page', 'wc-smart-checkout-builder' ),
			'view_item'          => __( 'View Landing Page', 'wc-smart-checkout-builder' ),
			'all_items'          => __( 'All Landing Pages', 'wc-smart-checkout-builder' ),
			'search_items'       => __( 'Search Landing Pages', 'wc-smart-checkout-builder' ),
			'not_found'          => __( 'No landing pages found.', 'wc-smart-checkout-builder' ),
			'not_found_in_trash' => __( 'No landing pages found in Trash.', 'wc-smart-checkout-builder' )
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Landing pages for Smart Checkout Builder.', 'wc-smart-checkout-builder' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'landing-page' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-cart',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		);

		register_post_type( 'wcsc_page', $args );

		if ( get_option( 'wcsc_flush_rewrite_rules_v130' ) !== 'yes' ) {
			flush_rewrite_rules();
			update_option( 'wcsc_flush_rewrite_rules_v130', 'yes' );
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
				'title' => esc_html__( 'Smart Checkout', 'wc-smart-checkout-builder' ),
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
			__( 'Thank You Page', 'wc-smart-checkout-builder' ),
			__( 'Thank You Page', 'wc-smart-checkout-builder' ),
			'manage_options',
			'wcsc-thank-you-settings',
			array( $this, 'thank_you_settings_html' )
		);
	}

	/**
	 * Register settings fields.
	 */
	public function register_settings() {
		register_setting( 'wcsc_thank_you_settings', 'wcsc_enable_thank_you' );
		register_setting( 'wcsc_thank_you_settings', 'wcsc_thank_you_page_id' );
	}

	/**
	 * HTML for Thank You Page Settings.
	 */
	public function thank_you_settings_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$pages = get_pages();
		$landing_pages = get_posts( array( 'post_type' => 'wcsc_page', 'numberposts' => -1 ) );
		$all_pages = array_merge( $pages, $landing_pages );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Thank You Page Settings', 'wc-smart-checkout-builder' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wcsc_thank_you_settings' );
				do_settings_sections( 'wcsc_thank_you_settings' );
				?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Enable Custom Thank You Page', 'wc-smart-checkout-builder' ); ?></th>
						<td>
							<input type="checkbox" name="wcsc_enable_thank_you" value="1" <?php checked( 1, get_option( 'wcsc_enable_thank_you' ), true ); ?> />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Select Thank You Page', 'wc-smart-checkout-builder' ); ?></th>
						<td>
							<select name="wcsc_thank_you_page_id">
								<option value=""><?php esc_html_e( '— Select a page —', 'wc-smart-checkout-builder' ); ?></option>
								<?php foreach ( $all_pages as $p ) : ?>
									<option value="<?php echo esc_attr( $p->ID ); ?>" <?php selected( $p->ID, get_option( 'wcsc_thank_you_page_id' ) ); ?>>
										<?php echo esc_html( $p->post_title . ' (' . $p->post_type . ')' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select the page to redirect customers to after a successful order. Ensure you add the "Thank You / Order Details" Elementor widget to this page.', 'wc-smart-checkout-builder' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Custom Thank You Redirect logic.
	 */
	public function custom_thank_you_redirect() {
		if ( ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		
		$enable_thank_you = get_option( 'wcsc_enable_thank_you' );
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
		
		$redirect_url = add_query_arg( array(
			'order_id' => $order_id,
			'key'      => $order_key,
		), $redirect_url );
		
		wp_redirect( $redirect_url );
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
