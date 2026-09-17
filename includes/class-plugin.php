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

		// Register Custom Post Type for Smart Pages.
		add_action( 'init', array( $this, 'register_post_type' ) );
		// Ensure Elementor support is enabled for the new CPT.
		add_action( 'init', array( $this, 'add_elementor_support' ), 999 );
	}

	/**
	 * Register custom post type 'wcsc_page' for Smart Checkout Pages.
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Smart Pages', 'post type general name', 'wc-smart-checkout-builder' ),
			'singular_name'      => _x( 'Smart Page', 'post type singular name', 'wc-smart-checkout-builder' ),
			'menu_name'          => _x( 'Smart Pages', 'admin menu', 'wc-smart-checkout-builder' ),
			'name_admin_bar'     => _x( 'Smart Page', 'add new on admin bar', 'wc-smart-checkout-builder' ),
			'add_new'            => _x( 'Add New', 'smart page', 'wc-smart-checkout-builder' ),
			'add_new_item'       => __( 'Add New Smart Page', 'wc-smart-checkout-builder' ),
			'new_item'           => __( 'New Smart Page', 'wc-smart-checkout-builder' ),
			'edit_item'          => __( 'Edit Smart Page', 'wc-smart-checkout-builder' ),
			'view_item'          => __( 'View Smart Page', 'wc-smart-checkout-builder' ),
			'all_items'          => __( 'All Smart Pages', 'wc-smart-checkout-builder' ),
			'search_items'       => __( 'Search Smart Pages', 'wc-smart-checkout-builder' ),
			'not_found'          => __( 'No smart pages found.', 'wc-smart-checkout-builder' ),
			'not_found_in_trash' => __( 'No smart pages found in Trash.', 'wc-smart-checkout-builder' )
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Custom pages for Smart Checkout Builder.', 'wc-smart-checkout-builder' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'smart-page' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => true,
			'menu_position'      => 58,
			'menu_icon'          => 'dashicons-cart',
			'supports'           => array( 'title', 'editor', 'elementor', 'thumbnail', 'page-attributes' ),
		);

		register_post_type( 'wcsc_page', $args );
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
		$widgets_manager->register( new Elementor_Widget() );
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
		$widgets_manager->register_widget_type( new Elementor_Widget() );
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
				'is_elementor_edit' => class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode(),
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
}
