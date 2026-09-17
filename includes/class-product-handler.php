<?php
namespace WCSC;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Product_Handler
 *
 * Handles WooCommerce product detection, queries, and contextual product resolution.
 */
class Product_Handler {

	/**
	 * Get the contextual product based on widget settings.
	 *
	 * @param array $settings Widget settings.
	 * @return \WC_Product|false
	 */
	public static function get_product_from_settings( $settings ) {
		$use_current = isset( $settings['use_current_product'] ) && 'yes' === $settings['use_current_product'];
		$product_id   = isset( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;

		if ( ! $use_current && $product_id > 0 ) {
			$product = wc_get_product( $product_id );
			if ( $product && is_a( $product, 'WC_Product' ) ) {
				return $product;
			}
		}

		// Otherwise detect current product.
		return self::get_current_product();
	}

	/**
	 * Detect current WooCommerce product across frontend and Elementor editor.
	 *
	 * @return \WC_Product|false
	 */
	public static function get_current_product() {
		global $product;

		// 1. If global $product is valid.
		if ( $product && is_a( $product, 'WC_Product' ) ) {
			return $product;
		}

		// 2. Queried object or current post ID.
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			$post_id = get_queried_object_id();
		}

		if ( $post_id ) {
			$p = wc_get_product( $post_id );
			if ( $p && is_a( $p, 'WC_Product' ) ) {
				return $p;
			}
		}

		// 3. Fallback in Elementor Editor / preview mode: fetch the latest published product so preview works immediately.
		if ( class_exists( '\Elementor\Plugin' ) && ( ( isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) || ( isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) ) ) {
			$sample_products = wc_get_products( array(
				'limit'   => 1,
				'status'  => 'publish',
				'orderby' => 'date',
				'order'   => 'DESC',
			) );

			if ( ! empty( $sample_products ) ) {
				return $sample_products[0];
			}
		}

		return false;
	}

	/**
	 * Search products for Elementor manual product selector control.
	 *
	 * @param string $keyword Search keyword.
	 * @param int    $limit   Max results.
	 * @return array
	 */
	public static function search_products( $keyword = '', $limit = 20 ) {
		$results = array();

		$args = array(
			'limit'   => $limit,
			'status'  => 'publish',
			'orderby' => 'title',
			'order'   => 'ASC',
		);

		if ( ! empty( $keyword ) ) {
			$args['s'] = sanitize_text_field( $keyword );
		}

		$products = wc_get_products( $args );

		foreach ( $products as $product ) {
			if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
				continue;
			}

			$title = $product->get_name();
			$sku   = $product->get_sku();
			$type  = $product->get_type();
			$price = $product->get_price_html();

			$label = sprintf(
				'#%1$d - %2$s (%3$s)%4$s',
				$product->get_id(),
				$title,
				ucfirst( $type ),
				$sku ? ' [SKU: ' . $sku . ']' : ''
			);

			$results[] = array(
				'id'         => $product->get_id(),
				'text'       => wp_strip_all_tags( $label ),
				'title'      => $title,
				'type'       => $type,
				'price_html' => $price,
				'image'      => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) ?: '',
			);
		}

		return $results;
	}

	/**
	 * Get product options array for Elementor select control.
	 *
	 * @return array
	 */
	public static function get_product_options() {
		$options = array(
			'' => esc_html__( '— Select a Product —', 'wc-smart-checkout-builder' ),
		);

		$products = wc_get_products( array(
			'limit'   => 60,
			'status'  => 'publish',
			'orderby' => 'date',
			'order'   => 'DESC',
		) );

		foreach ( $products as $product ) {
			if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
				continue;
			}
			$options[ (string) $product->get_id() ] = sprintf(
				'#%1$d - %2$s (%3$s)',
				$product->get_id(),
				$product->get_name(),
				ucfirst( $product->get_type() )
			);
		}

		return $options;
	}
}
