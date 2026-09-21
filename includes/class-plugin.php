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
		add_action( 'admin_init', array( $this, 'suppress_third_party_admin_notices' ), 1 );
		add_filter( 'plugin_action_links_' . WCSC_BASENAME, array( $this, 'add_plugin_action_links' ) );

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
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$document = \Elementor\Plugin::$instance->documents->get( $post_id );
		if ( ! $document ) {
			return;
		}

		if ( $document->has_widget( 'wcsc_product_checkout' ) || $document->has_widget( 'wcsc_thank_you' ) ) {
			wp_enqueue_style( 'wcsc-widget-style' );
		}
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
	 * Suppress third-party admin notice banners on the plugin settings page.
	 */
	public function suppress_third_party_admin_notices() {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( 'wcsc-settings' === $page ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'user_admin_notices' );
			remove_all_actions( 'network_admin_notices' );
		}
	}

	/**
	 * Add direct 'Settings' link to plugin row on plugins.php.
	 *
	 * @param array $links
	 * @return array
	 */
	public function add_plugin_action_links( $links ) {
		$settings_url  = admin_url( 'edit.php?post_type=wcsc_page&page=wcsc-settings' );
		$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'wc-smart-checkout-builder' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
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
		register_setting( 'wcsc_settings_group', 'wcsc_license_key', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_license_key' ),
		) );
	}

	/**
	 * Sanitize and auto-verify license key on settings save.
	 *
	 * @param string $key
	 * @return string
	 */
	public function sanitize_license_key( $key ) {
		$key     = sanitize_text_field( trim( $key ) );
		$old_key = License_Manager::get_license_key();

		if ( ! empty( $key ) && ( $key !== $old_key || 'active' !== License_Manager::get_license_status() ) ) {
			License_Manager::activate_license( $key );
		} elseif ( empty( $key ) && ! empty( $old_key ) ) {
			License_Manager::deactivate_license();
		}

		return $key;
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
		<div class="wrap wcsc-admin-dashboard-wrap" style="margin: 20px 20px 0 0; max-width: 100%;">
			<style>
				.wcsc-admin-dashboard-wrap {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					color: #1e293b;
				}
				.wcsc-dashboard-header {
					background: #0f172a;
					color: #ffffff;
					padding: 28px 32px;
					border-radius: 0;
					display: flex;
					align-items: center;
					justify-content: space-between;
					flex-wrap: wrap;
					gap: 16px;
					border-bottom: 3px solid #2563eb;
				}
				.wcsc-header-left {
					display: flex;
					align-items: center;
					gap: 16px;
				}
				.wcsc-header-icon {
					width: 44px;
					height: 44px;
					background: #2563eb;
					display: flex;
					align-items: center;
					justify-content: center;
					border-radius: 0;
					color: #ffffff;
					font-size: 24px;
				}
				.wcsc-header-icon .dashicons {
					font-size: 26px;
					width: 26px;
					height: 26px;
				}
				.wcsc-header-title {
					margin: 0;
					font-size: 22px;
					font-weight: 700;
					letter-spacing: -0.01em;
					color: #ffffff;
					display: flex;
					align-items: center;
					gap: 10px;
				}
				.wcsc-badge {
					font-size: 11px;
					font-weight: 600;
					padding: 2px 8px;
					background: rgba(37, 99, 235, 0.3);
					border: 1px solid #3b82f6;
					color: #93c5fd;
					border-radius: 0;
					text-transform: uppercase;
					letter-spacing: 0.05em;
				}
				.wcsc-header-subtitle {
					margin: 4px 0 0 0;
					color: #94a3b8;
					font-size: 13px;
				}
				.wcsc-tabs-nav {
					display: flex;
					background: #f8fafc;
					border-left: 1px solid #e2e8f0;
					border-right: 1px solid #e2e8f0;
					border-bottom: 1px solid #e2e8f0;
					border-radius: 0;
					margin: 0;
					padding: 0;
					list-style: none;
					gap: 0;
				}
				.wcsc-tab-btn {
					padding: 14px 24px;
					font-size: 14px;
					font-weight: 600;
					color: #64748b;
					background: transparent;
					border: none;
					border-right: 1px solid #e2e8f0;
					border-bottom: 3px solid transparent;
					border-radius: 0;
					cursor: pointer;
					display: inline-flex;
					align-items: center;
					gap: 8px;
					transition: all 0.15s ease;
					outline: none;
				}
				.wcsc-tab-btn:hover {
					color: #0f172a;
					background: #f1f5f9;
				}
				.wcsc-tab-btn.active {
					color: #2563eb;
					background: #ffffff;
					border-bottom-color: #2563eb;
				}
				.wcsc-dashboard-body {
					background: #ffffff;
					border: 1px solid #e2e8f0;
					border-top: none;
					border-radius: 0;
					padding: 32px;
					box-sizing: border-box;
				}
				.wcsc-tab-pane {
					display: none;
				}
				.wcsc-tab-pane.active {
					display: block;
				}
				.wcsc-section-card {
					margin-bottom: 24px;
				}
				.wcsc-card-heading {
					margin: 0 0 6px 0;
					font-size: 17px;
					font-weight: 700;
					color: #0f172a;
				}
				.wcsc-card-desc {
					margin: 0 0 20px 0;
					font-size: 13px;
					color: #64748b;
					line-height: 1.5;
				}
				.wcsc-form-group {
					margin-bottom: 22px;
				}
				.wcsc-form-label {
					display: block;
					font-size: 14px;
					font-weight: 600;
					color: #1e293b;
					margin-bottom: 8px;
				}
				.wcsc-url-preview-box {
					display: flex;
					align-items: center;
					background: #f8fafc;
					border: 1px solid #cbd5e1;
					border-radius: 0;
					padding: 10px 14px;
					font-family: monospace;
					font-size: 14px;
					flex-wrap: wrap;
					gap: 4px;
					max-width:fit-content;
				}
				.wcsc-url-preview-box input[type="text"] {
					border: 1px solid #3b82f6;
					border-radius: 0;
					padding: 5px 10px;
					font-family: monospace;
					font-size: 14px;
					font-weight: 700;
					color: #1e293b;
					background: #ffffff;
					outline: none;
				}
				.wcsc-select-field {
					border: 1px solid #cbd5e1;
					border-radius: 0;
					padding: 8px 12px;
					font-size: 14px;
					min-width: 300px;
					color: #1e293b;
					background: #ffffff;
				}
				.wcsc-code-textarea {
					width: 100%;
					font-family: Consolas, Monaco, "Courier New", monospace;
					font-size: 13px;
					line-height: 1.6;
					background: #0f172a;
					color: #38bdf8;
					border: 1px solid #1e293b;
					border-radius: 0;
					padding: 16px;
					box-sizing: border-box;
				}
				.wcsc-save-btn {
					background: #2563eb !important;
					border-color: #2563eb !important;
					color: #ffffff !important;
					font-size: 14px !important;
					font-weight: 600 !important;
					padding: 8px 24px !important;
					border-radius: 0 !important;
					cursor: pointer;
					text-shadow: none !important;
					box-shadow: none !important;
					transition: background 0.15s ease !important;
				}
				.wcsc-save-btn:hover {
					background: #1d4ed8 !important;
					border-color: #1d4ed8 !important;
				}
				.wcsc-button-secondary {
					display: inline-flex;
					align-items: center;
					gap: 6px;
					background: #f1f5f9;
					border: 1px solid #cbd5e1;
					color: #334155;
					padding: 8px 14px;
					font-size: 13px;
					font-weight: 600;
					border-radius: 0;
					text-decoration: none;
				}
				.wcsc-button-secondary:hover {
					background: #e2e8f0;
					color: #0f172a;
				}
				.wcsc-alert-box {
					background: #eff6ff;
					border-left: 4px solid #3b82f6;
					border-radius: 0;
					padding: 14px 18px;
					margin-top: 12px;
					font-size: 13px;
					color: #1e40af;
					line-height: 1.5;
				}
				/* Suppress generic WP and third-party notices */
				.notice:not(.wcsc-notice),
				.updated:not(.wcsc-notice),
				.error:not(.wcsc-notice),
				.is-dismissible:not(.wcsc-notice),
				div.notice,
				div.updated,
				div.error {
					display: none !important;
				}
			</style>

			<?php if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) : ?>
				<div class="wcsc-alert-box wcsc-notice" style="background: #ecfdf5; border-left: 4px solid #10b981; color: #065f46; margin: 15px 0 20px 0; font-size: 14px; font-weight: 600;">
					✓ <?php esc_html_e( 'সেটিংস সফলভাবে সংরক্ষিত হয়েছে।', 'wc-smart-checkout-builder' ); ?>
				</div>
			<?php endif; ?>

			<div class="wcsc-dashboard-header">
				<div class="wcsc-header-left">
					<div class="wcsc-header-icon">
						<span class="dashicons dashicons-cart"></span>
					</div>
					<div>
						<h1 class="wcsc-header-title">
							<?php esc_html_e( 'WC Smart Checkout Builder', 'wc-smart-checkout-builder' ); ?>
							<span class="wcsc-badge"><?php echo esc_html( WCSC_VERSION ); ?></span>
						</h1>
						<p class="wcsc-header-subtitle"><?php esc_html_e( 'Manage permalinks, post-purchase redirection, and custom checkout styling.', 'wc-smart-checkout-builder' ); ?></p>
					</div>
				</div>
			</div>

			<div class="wcsc-tabs-nav">
				<button type="button" class="wcsc-tab-btn active" data-tab="tab-general">
					<span class="dashicons dashicons-admin-links"></span>
					<?php esc_html_e( 'Permalinks & Slug', 'wc-smart-checkout-builder' ); ?>
				</button>
				<button type="button" class="wcsc-tab-btn" data-tab="tab-thankyou">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'Thank You Page', 'wc-smart-checkout-builder' ); ?>
				</button>
				<button type="button" class="wcsc-tab-btn" data-tab="tab-custom-css">
					<span class="dashicons dashicons-editor-code"></span>
					<?php esc_html_e( 'Custom CSS', 'wc-smart-checkout-builder' ); ?>
				</button>
				<button type="button" class="wcsc-tab-btn" data-tab="tab-license">
					<span class="dashicons dashicons-admin-network"></span>
					<?php esc_html_e( 'License Activation', 'wc-smart-checkout-builder' ); ?>
				</button>
			</div>

			<div class="wcsc-dashboard-body">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'wcsc_settings_group' );
					do_settings_sections( 'wcsc_settings_group' );
					?>

					<!-- Tab 1: General / Permalinks -->
					<div class="wcsc-tab-pane active" id="tab-general">
						<div class="wcsc-section-card">
							<h2 class="wcsc-card-heading"><?php esc_html_e( 'Landing Page URL Slug / Permalinks', 'wc-smart-checkout-builder' ); ?></h2>
							<p class="wcsc-card-desc"><?php esc_html_e( 'Configure the public URL structure for your custom landing pages. Changes update rewrite rules automatically without causing 404 errors.', 'wc-smart-checkout-builder' ); ?></p>
							
							<div class="wcsc-form-group">
								<label for="wcsc_cpt_slug" class="wcsc-form-label"><?php esc_html_e( 'Base Slug', 'wc-smart-checkout-builder' ); ?></label>
								<div class="wcsc-url-preview-box">
									<span><?php echo esc_html( home_url( '/' ) ); ?></span>
									<input type="text" name="wcsc_cpt_slug" id="wcsc_cpt_slug" value="<?php echo esc_attr( get_option( 'wcsc_cpt_slug', 'landing-page' ) ); ?>" placeholder="landing-page" />
									<span>/your-landing-page/</span>
								</div>
							</div>

							<div class="wcsc-alert-box">
								<strong><?php esc_html_e( 'Tip:', 'wc-smart-checkout-builder' ); ?></strong>
								<?php esc_html_e( 'Common conversion-focused slugs include "offer", "deal", "order", "special", or "checkout".', 'wc-smart-checkout-builder' ); ?>
							</div>
						</div>
					</div>

					<!-- Tab 2: Thank You Page -->
					<div class="wcsc-tab-pane" id="tab-thankyou">
						<div class="wcsc-section-card">
							<h2 class="wcsc-card-heading"><?php esc_html_e( 'Custom Thank You Page Redirection', 'wc-smart-checkout-builder' ); ?></h2>
							<p class="wcsc-card-desc"><?php esc_html_e( 'Seamlessly redirect customers after a successful order to a dedicated confirmation page built with Elementor.', 'wc-smart-checkout-builder' ); ?></p>

							<div class="wcsc-form-group">
								<label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
									<input type="checkbox" name="wcsc_enable_thank_you" value="1" <?php checked( 1, get_option( 'wcsc_enable_thank_you' ), true ); ?> style="border-radius: 0;" />
									<?php esc_html_e( 'Enable automatic redirection to a custom Thank You page upon order completion', 'wc-smart-checkout-builder' ); ?>
								</label>
							</div>

							<div class="wcsc-form-group" style="margin-top: 20px;">
								<label for="wcsc_thank_you_page_id" class="wcsc-form-label"><?php esc_html_e( 'Select Target Page', 'wc-smart-checkout-builder' ); ?></label>
								<div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
									<select name="wcsc_thank_you_page_id" id="wcsc_thank_you_page_id" class="wcsc-select-field">
										<option value=""><?php esc_html_e( '— Select a page —', 'wc-smart-checkout-builder' ); ?></option>
										<?php foreach ( $all_pages as $p ) : ?>
											<option value="<?php echo esc_attr( $p->ID ); ?>" <?php selected( $p->ID, $selected_id ); ?>>
												<?php echo esc_html( $p->post_title . ' (' . $p->post_type . ')' ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<a href="<?php echo esc_url( $edit_url ? $edit_url : '#' ); ?>" 
										id="wcsc-edit-page-btn" 
										class="wcsc-button-secondary" 
										target="_blank" 
										style="<?php echo $edit_url ? 'display: inline-flex;' : 'display: none;'; ?>">
										<span class="dashicons dashicons-edit"></span>
										<?php esc_html_e( 'Edit Page', 'wc-smart-checkout-builder' ); ?>
									</a>
								</div>
								<p class="description" style="margin-top: 8px;">
									<?php esc_html_e( 'Remember to place the "Thank You / Order Details" Elementor widget onto this page to display order summary and customer details.', 'wc-smart-checkout-builder' ); ?>
								</p>
							</div>
						</div>
					</div>

					<!-- Tab 3: Custom CSS -->
					<div class="wcsc-tab-pane" id="tab-custom-css">
						<div class="wcsc-section-card">
							<h2 class="wcsc-card-heading"><?php esc_html_e( 'Global Custom CSS', 'wc-smart-checkout-builder' ); ?></h2>
							<p class="wcsc-card-desc"><?php esc_html_e( 'Add custom CSS overrides that automatically load across all checkout and landing pages.', 'wc-smart-checkout-builder' ); ?></p>

							<div class="wcsc-form-group">
								<textarea name="wcsc_custom_css" id="wcsc_custom_css" rows="12" class="wcsc-code-textarea" placeholder="/* Add custom CSS rules here... */"><?php echo esc_textarea( get_option( 'wcsc_custom_css', '' ) ); ?></textarea>
							</div>
						</div>
					</div>

					<!-- Tab 4: License & Activation -->
					<div class="wcsc-tab-pane" id="tab-license">
						<div class="wcsc-section-card">
							<h2 class="wcsc-card-heading"><?php esc_html_e( 'License Management & Server Sync', 'wc-smart-checkout-builder' ); ?></h2>
							<p class="wcsc-card-desc"><?php esc_html_e( 'Enter your license key to activate full access and automatic updates.', 'wc-smart-checkout-builder' ); ?></p>

							<?php
							$license_key     = License_Manager::get_license_key();
							$license_status  = License_Manager::get_license_status();
							$is_active       = 'active' === $license_status;
							$last_error      = get_option( 'wcsc_last_license_error' );
							$last_error_time = get_option( 'wcsc_last_license_error_time' );
							$site_domain     = License_Manager::get_site_domain();
							?>

							<?php if ( ! $is_active && ! empty( $last_error ) ) : ?>
								<div class="wcsc-alert-box" style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; margin-bottom: 22px; max-width: 650px;">
									<div style="font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
										<span class="dashicons dashicons-warning" style="font-size: 18px; width: 18px; height: 18px;"></span>
										<?php esc_html_e( 'সর্বশেষ অ্যাক্টিভেশন ত্রুটি (Last Activation Error):', 'wc-smart-checkout-builder' ); ?>
									</div>
									<div style="font-size: 13px; line-height: 1.5;"><?php echo esc_html( $last_error ); ?></div>
									<?php if ( ! empty( $last_error_time ) ) : ?>
										<div style="font-size: 11px; margin-top: 6px; opacity: 0.75;"><?php echo esc_html( sprintf( __( 'রেকর্ড সময়: %s', 'wc-smart-checkout-builder' ), $last_error_time ) ); ?></div>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<div class="wcsc-form-group" style="max-width: 650px;">
								<label for="wcsc_license_key" class="wcsc-form-label"><?php esc_html_e( 'License Key', 'wc-smart-checkout-builder' ); ?></label>
								<div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
									<input type="text" name="wcsc_license_key" id="wcsc_license_key" value="<?php echo esc_attr( $license_key ); ?>" class="regular-text" style="flex: 1; min-width: 250px; height: 38px; line-height: 36px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-family: monospace; font-size: 14px; box-sizing: border-box;" placeholder="TP-XXXX-XXXX-XXXX-XXXX" />
									<button type="button" id="wcsc-activate-license-btn" class="button button-primary" style="height: 38px; line-height: 36px; padding: 0 16px; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box;">
										<?php echo $is_active ? esc_html__( 'Re-verify License', 'wc-smart-checkout-builder' ) : esc_html__( 'Activate License', 'wc-smart-checkout-builder' ); ?>
									</button>
									<?php if ( ! empty( $license_key ) ) : ?>
										<button type="button" id="wcsc-deactivate-license-btn" class="button" style="height: 38px; line-height: 36px; padding: 0 14px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px; font-weight: 500; color: #475569; background: #f8fafc; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box;">
											<?php esc_html_e( 'Deactivate', 'wc-smart-checkout-builder' ); ?>
										</button>
									<?php endif; ?>
								</div>
								<div id="wcsc-license-ajax-msg" style="margin-top: 14px; display: none;"></div>
							</div>

							<div style="margin-top: 25px; padding: 18px 22px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; max-width: 650px;">
								<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
									<span style="font-weight: 600; font-size: 14px; color: #475569;"><?php esc_html_e( 'License Status:', 'wc-smart-checkout-builder' ); ?></span>
									<?php if ( $is_active ) : ?>
										<span id="wcsc-status-pill" style="background: #10b981; color: #ffffff; padding: 4px 12px; font-size: 12px; font-weight: 700; border-radius: 20px; letter-spacing: 0.5px;">✓ <?php esc_html_e( 'ACTIVE & VERIFIED', 'wc-smart-checkout-builder' ); ?></span>
									<?php elseif ( 'unregistered' === $license_status ) : ?>
										<span id="wcsc-status-pill" style="background: #f59e0b; color: #ffffff; padding: 4px 12px; font-size: 12px; font-weight: 700; border-radius: 20px; letter-spacing: 0.5px;">⚠ <?php esc_html_e( 'UNREGISTERED', 'wc-smart-checkout-builder' ); ?></span>
									<?php else : ?>
										<span id="wcsc-status-pill" style="background: #ef4444; color: #ffffff; padding: 4px 12px; font-size: 12px; font-weight: 700; border-radius: 20px; letter-spacing: 0.5px;">✕ <?php esc_html_e( 'INACTIVE / BLOCKED', 'wc-smart-checkout-builder' ); ?></span>
									<?php endif; ?>
								</div>

								<div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #64748b; padding-top: 12px; border-top: 1px solid #e2e8f0;">
									<span><?php esc_html_e( 'Product:', 'wc-smart-checkout-builder' ); ?></span>
									<span style="font-weight: 600; color: #1e293b;">WC Smart Checkout Builder</span>
								</div>
							</div>

							<!-- Diagnostic & Server Connectivity Box -->
							<div style="margin-top: 20px; padding: 18px 22px; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 6px; max-width: 650px;">
								<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
									<div style="font-weight: 700; font-size: 13px; color: #334155;">
										<span class="dashicons dashicons-networking" style="font-size: 17px; width: 17px; height: 17px; vertical-align: text-top; margin-right: 4px; color: #64748b;"></span>
										<?php esc_html_e( 'সার্ভার সংযোগ ডায়াগনস্টিকস (Server Diagnostic)', 'wc-smart-checkout-builder' ); ?>
									</div>
									<button type="button" id="wcsc-test-connection-btn" class="button" style="height: 30px; line-height: 28px; font-size: 12px; padding: 0 12px; border-radius: 4px;">
										<?php esc_html_e( 'সার্ভার সংযোগ টেস্ট করুন', 'wc-smart-checkout-builder' ); ?>
									</button>
								</div>
								<div style="font-size: 12px; color: #64748b; line-height: 1.6;">
									<div><strong>আপনার সাইটের ডোমেইন:</strong> <code style="color: #0f172a;"><?php echo esc_html( $site_domain ); ?></code> (সার্ভারে এই ডোমেইনে লাইসেন্স রেজিস্টার থাকতে হবে)</div>
									<div><strong>লাইসেন্স সার্ভার এন্ডপয়েন্ট:</strong> <code style="color: #0f172a;"><?php echo esc_html( License_Manager::SERVER_URL ); ?></code></div>
								</div>
								<div id="wcsc-test-connection-msg" style="margin-top: 12px; display: none;"></div>
							</div>
						</div>
					</div>

					<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
						<?php submit_button( __( 'Save All Changes', 'wc-smart-checkout-builder' ), 'primary', 'submit', false, array( 'class' => 'wcsc-save-btn' ) ); ?>
					</div>
				</form>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			// Tab switching
			var tabButtons = document.querySelectorAll('.wcsc-tab-btn');
			var tabPanes = document.querySelectorAll('.wcsc-tab-pane');

			function switchTab(targetTab) {
				tabButtons.forEach(function(b) { b.classList.remove('active'); });
				tabPanes.forEach(function(p) { p.classList.remove('active'); });

				var btn = document.querySelector('.wcsc-tab-btn[data-tab="' + targetTab + '"]');
				var pane = document.getElementById(targetTab);
				if (btn && pane) {
					btn.classList.add('active');
					pane.classList.add('active');
				}
			}

			tabButtons.forEach(function(btn) {
				btn.addEventListener('click', function() {
					var targetTab = this.getAttribute('data-tab');
					switchTab(targetTab);
					if (history.replaceState) {
						history.replaceState(null, null, '#' + targetTab);
					}
				});
			});

			// Auto open tab from hash URL
			if (window.location.hash) {
				var hashTab = window.location.hash.replace('#', '');
				if (document.getElementById(hashTab)) {
					switchTab(hashTab);
				}
			}

			// Page selector edit button link
			var select = document.getElementById('wcsc_thank_you_page_id');
			var editBtn = document.getElementById('wcsc-edit-page-btn');
			var adminPostUrl = '<?php echo esc_url( admin_url( 'post.php?action=edit&post=' ) ); ?>';
			if (select && editBtn) {
				select.addEventListener('change', function() {
					var val = this.value;
					if (val) {
						editBtn.href = adminPostUrl + encodeURIComponent(val);
						editBtn.style.display = 'inline-flex';
					} else {
						editBtn.style.display = 'none';
					}
				});
			}

			// AJAX License Activation
			var activateBtn = document.getElementById('wcsc-activate-license-btn');
			var deactivateBtn = document.getElementById('wcsc-deactivate-license-btn');
			var keyInput = document.getElementById('wcsc_license_key');
			var msgBox = document.getElementById('wcsc-license-ajax-msg');
			var statusPill = document.getElementById('wcsc-status-pill');
			var licenseNonce = '<?php echo esc_js( wp_create_nonce( 'wcsc_license_nonce' ) ); ?>';
			var ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';

			if (activateBtn) {
				activateBtn.addEventListener('click', function(e) {
					if (e) {
						e.preventDefault();
						e.stopPropagation();
					}

					var key = keyInput.value.trim();
					if (!key) {
						alert('<?php echo esc_js( __( 'অনুগ্রহ করে একটি লাইসেন্স কি প্রবেশ করান।', 'wc-smart-checkout-builder' ) ); ?>');
						keyInput.focus();
						return;
					}

					var origText = activateBtn.textContent;
					activateBtn.disabled = true;

					var countdown = 15;
					activateBtn.textContent = '<?php echo esc_js( __( 'অ্যাক্টিভেট হচ্ছে...', 'wc-smart-checkout-builder' ) ); ?> (' + countdown + 's)';
					var countdownTimer = setInterval(function() {
						countdown--;
						if (countdown > 0) {
							activateBtn.textContent = '<?php echo esc_js( __( 'অ্যাক্টিভেট হচ্ছে...', 'wc-smart-checkout-builder' ) ); ?> (' + countdown + 's)';
						}
					}, 1000);

					msgBox.style.display = 'none';

					var formData = new FormData();
					formData.append('action', 'wcsc_activate_license');
					formData.append('license_key', key);
					formData.append('nonce', licenseNonce);

					// 18-second hard timeout controller so the button never stays stuck
					var controller = (typeof AbortController !== 'undefined') ? new AbortController() : null;
					var hardTimeout = setTimeout(function() {
						if (controller) {
							controller.abort();
						}
					}, 18000);

					fetch(ajaxUrl, {
						method: 'POST',
						body: formData,
						credentials: 'same-origin',
						headers: {
							'X-Requested-With': 'XMLHttpRequest'
						},
						signal: controller ? controller.signal : undefined
					})
					.then(function(res) {
						clearTimeout(hardTimeout);
						clearInterval(countdownTimer);
						return res.text().then(function(text) {
							try {
								return JSON.parse(text);
							} catch (e) {
								if (text === '-1') {
									throw new Error('<?php echo esc_js( __( 'নিরাপত্তা টোকেন (Nonce) মেয়াদোত্তীর্ণ হয়েছে। অনুগ্রহ করে পেজটি রিফ্রেশ করুন।', 'wc-smart-checkout-builder' ) ); ?>');
								} else if (text === '0') {
									throw new Error('<?php echo esc_js( __( 'AJAX হ্যান্ডলার পাওয়া যায়নি (Action not found)।', 'wc-smart-checkout-builder' ) ); ?>');
								}
								throw new Error(text ? text.substring(0, 180) : ('Invalid server response (HTTP ' + res.status + ')'));
							}
						});
					})
					.then(function(res) {
						clearTimeout(hardTimeout);
						clearInterval(countdownTimer);
						activateBtn.disabled = false;
						activateBtn.textContent = origText;
						msgBox.style.display = 'block';

						if (res && res.success) {
							if (statusPill) {
								statusPill.style.background = '#10b981';
								statusPill.innerHTML = '✓ <?php echo esc_js( __( 'ACTIVE & VERIFIED', 'wc-smart-checkout-builder' ) ); ?>';
							}
							msgBox.className = 'wcsc-alert-box wcsc-notice';
							msgBox.style.background = '#ecfdf5';
							msgBox.style.borderLeftColor = '#10b981';
							msgBox.style.color = '#065f46';
							msgBox.innerHTML = '✓ ' + ((res.data && res.data.message) ? res.data.message : '<?php echo esc_js( __( 'লাইসেন্স সফলভাবে অ্যাক্টিভ হয়েছে। পেজটি রিলোড হচ্ছে...', 'wc-smart-checkout-builder' ) ); ?>');
							setTimeout(function() { window.location.hash = '#tab-license'; window.location.reload(); }, 1200);
						} else {
							if (statusPill) {
								statusPill.style.background = '#ef4444';
								statusPill.innerHTML = '✕ <?php echo esc_js( __( 'INACTIVE / BLOCKED', 'wc-smart-checkout-builder' ) ); ?>';
							}
							msgBox.className = 'wcsc-alert-box wcsc-notice';
							msgBox.style.background = '#fef2f2';
							msgBox.style.borderLeftColor = '#ef4444';
							msgBox.style.color = '#991b1b';
							var errMsg = (res && res.data && res.data.message) ? res.data.message : '<?php echo esc_js( __( 'অ্যাক্টিভেশন ব্যর্থ হয়েছে।', 'wc-smart-checkout-builder' ) ); ?>';
							var debugHtml = '';
							if (res && res.data && res.data.debug) {
								debugHtml = '<div style="margin-top: 8px; font-size: 11px; opacity: 0.85; font-family: monospace;">' + JSON.stringify(res.data.debug) + '</div>';
							}
							msgBox.innerHTML = '✕ ' + errMsg + debugHtml;
						}
					})
					.catch(function(err) {
						clearTimeout(hardTimeout);
						clearInterval(countdownTimer);
						activateBtn.disabled = false;
						activateBtn.textContent = origText;
						msgBox.style.display = 'block';
						msgBox.className = 'wcsc-alert-box wcsc-notice';
						msgBox.style.background = '#fef2f2';
						msgBox.style.borderLeftColor = '#ef4444';
						msgBox.style.color = '#991b1b';

						if (err && err.name === 'AbortError') {
							msgBox.innerHTML = '✕ <strong><?php echo esc_js( __( 'সংযোগের সময় শেষ (Timeout)!', 'wc-smart-checkout-builder' ) ); ?></strong><br><?php echo esc_js( __( '১৮ সেকেন্ডের মধ্যে লাইসেন্স সার্ভার থেকে কোনো উত্তর আসেনি। হোস্টিং থেকে আউটগোয়িং কানেকশন ব্লক থাকতে পারে বা সার্ভার ফায়ারওয়ালে রিকোয়েস্ট আটকে আছে। নিচের "সার্ভার সংযোগ টেস্ট করুন" বাটনে ক্লিক করে কানেকশন যাচাই করুন।', 'wc-smart-checkout-builder' ) ); ?>';
						} else {
							msgBox.innerHTML = '✕ <strong>' + (err.message || 'Error connecting to server.') + '</strong>';
						}
					});
				});
			}

			if (deactivateBtn) {
				deactivateBtn.addEventListener('click', function(e) {
					if (e) {
						e.preventDefault();
					}
					if (!confirm('<?php echo esc_js( __( 'আপনি কি নিশ্চিত যে এই সাইট থেকে লাইসেন্স ডি-অ্যাক্টিভ করতে চান?', 'wc-smart-checkout-builder' ) ); ?>')) {
						return;
					}

					deactivateBtn.disabled = true;
					var formData = new FormData();
					formData.append('action', 'wcsc_deactivate_license');
					formData.append('nonce', licenseNonce);

					fetch(ajaxUrl, {
						method: 'POST',
						body: formData,
						credentials: 'same-origin',
						headers: {
							'X-Requested-With': 'XMLHttpRequest'
						}
					})
					.then(function(res) { return res.json(); })
					.then(function() {
						window.location.hash = '#tab-license';
						window.location.reload();
					})
					.catch(function() {
						deactivateBtn.disabled = false;
					});
				});
			}

			// Diagnostic connection test button
			var testConnBtn = document.getElementById('wcsc-test-connection-btn');
			var testConnMsg = document.getElementById('wcsc-test-connection-msg');
			if (testConnBtn && testConnMsg) {
				testConnBtn.addEventListener('click', function(e) {
					if (e) {
						e.preventDefault();
					}
					var origTestText = testConnBtn.textContent;
					var currentKey = keyInput ? keyInput.value.trim() : '';
					testConnBtn.disabled = true;
					testConnBtn.textContent = '<?php echo esc_js( __( 'যাচাই করা হচ্ছে...', 'wc-smart-checkout-builder' ) ); ?>';
					testConnMsg.style.display = 'block';
					testConnMsg.className = 'wcsc-alert-box';
					testConnMsg.style.background = '#f1f5f9';
					testConnMsg.style.borderLeftColor = '#64748b';
					testConnMsg.style.color = '#334155';
					testConnMsg.innerHTML = currentKey ? '<?php echo esc_js( __( 'লাইসেন্স কি এবং সার্ভার সংযোগ যাচাই করা হচ্ছে, অপেক্ষা করুন...', 'wc-smart-checkout-builder' ) ); ?>' : '<?php echo esc_js( __( 'লাইসেন্স সার্ভারে পিং পাঠানো হচ্ছে, অপেক্ষা করুন...', 'wc-smart-checkout-builder' ) ); ?>';

					var formData = new FormData();
					formData.append('action', 'wcsc_test_license_connection');
					formData.append('nonce', licenseNonce);
					if (currentKey) {
						formData.append('license_key', currentKey);
					}

					fetch(ajaxUrl, {
						method: 'POST',
						body: formData,
						credentials: 'same-origin',
						headers: {
							'X-Requested-With': 'XMLHttpRequest'
						}
					})
					.then(function(res) { return res.json(); })
					.then(function(res) {
						testConnBtn.disabled = false;
						testConnBtn.textContent = origTestText;
						if (res && res.success) {
							testConnMsg.style.background = '#ecfdf5';
							testConnMsg.style.borderLeftColor = '#10b981';
							testConnMsg.style.color = '#065f46';
							testConnMsg.innerHTML = '✓ <strong>' + res.data.message + '</strong><br>' +
								'<small style="font-family: monospace; font-size: 11px;">সার্ভার রেসপন্স প্রিভিউ: ' + (res.data.raw_sample || 'OK') + '</small>';
						} else {
							testConnMsg.style.background = '#fef2f2';
							testConnMsg.style.borderLeftColor = '#ef4444';
							testConnMsg.style.color = '#991b1b';
							testConnMsg.innerHTML = '✕ <strong>' + (res.data && res.data.message ? res.data.message : '<?php echo esc_js( __( 'সার্ভারে সংযোগ ব্যর্থ হয়েছে।', 'wc-smart-checkout-builder' ) ); ?>') + '</strong>';
						}
					})
					.catch(function(err) {
						testConnBtn.disabled = false;
						testConnBtn.textContent = origTestText;
						testConnMsg.style.background = '#fef2f2';
						testConnMsg.style.borderLeftColor = '#ef4444';
						testConnMsg.style.color = '#991b1b';
						testConnMsg.innerHTML = '✕ <strong><?php echo esc_js( __( 'ত্রুটি:', 'wc-smart-checkout-builder' ) ); ?></strong> ' + (err.message || 'Error');
					});
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
