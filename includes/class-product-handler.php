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
		$is_elementor_editor = false;
		if ( class_exists( '\Elementor\Plugin' ) ) {
			if ( ( isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) || ( isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) ) {
				$is_elementor_editor = true;
			} elseif ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'elementor_ajax' === $_REQUEST['action'] ) {
				$is_elementor_editor = true;
			}
		}

		if ( $is_elementor_editor ) {
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

	/**
	 * Get WooCommerce products list formatted for multi-select Order Bump controls.
	 *
	 * @return array
	 */
	public static function get_bump_product_options() {
		$options  = array();
		$products = wc_get_products( array(
			'limit'   => 100,
			'status'  => 'publish',
			'orderby' => 'date',
			'order'   => 'DESC',
		) );

		foreach ( $products as $product ) {
			if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
				continue;
			}
			$price_str = $product->get_price() ? ' — ' . wp_strip_all_tags( wc_price( $product->get_price() ) ) : '';
			$options[ (string) $product->get_id() ] = sprintf(
				'#%1$d - %2$s%3$s',
				$product->get_id(),
				$product->get_name(),
				$price_str
			);
		}

		return $options;
	}

	/**
	 * Get product IDs for Order Bump based on widget settings (Manual, Related, or Best Selling).
	 *
	 * @param array $settings Widget settings.
	 * @return array Array of product IDs.
	 */
	public static function get_order_bump_product_ids( $settings ) {
		if ( empty( $settings ) || empty( $settings['enable_order_bump'] ) || 'yes' !== $settings['enable_order_bump'] ) {
			return array();
		}

		$limit      = ! empty( $settings['order_bump_product_limit'] ) ? absint( $settings['order_bump_product_limit'] ) : 2;
		$limit      = max( 1, min( 4, $limit ) );
		$query_type = ! empty( $settings['order_bump_query_type'] ) ? $settings['order_bump_query_type'] : 'manual';

		// 1. Manual selection
		if ( 'manual' === $query_type ) {
			$product_ids = ! empty( $settings['order_bump_products'] ) ? (array) $settings['order_bump_products'] : array();
			return array_slice( array_filter( array_map( 'absint', $product_ids ) ), 0, $limit );
		}

		// Collect base IDs (items in cart or current product) to find relations or exclude duplicates
		$base_ids = array();
		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( ! empty( $cart_item['product_id'] ) ) {
					$base_ids[] = absint( $cart_item['product_id'] );
				}
			}
		}
		if ( empty( $base_ids ) ) {
			$current_p = self::get_product_from_settings( $settings );
			if ( $current_p && is_a( $current_p, 'WC_Product' ) ) {
				$base_ids[] = $current_p->get_id();
			}
		}

		// 2. Related Products
		if ( 'related' === $query_type ) {
			$related_ids = array();
			if ( ! empty( $base_ids ) ) {
				foreach ( $base_ids as $bid ) {
					if ( function_exists( 'wc_get_related_products' ) ) {
						$rel = wc_get_related_products( $bid, $limit * 2, $base_ids );
						if ( ! empty( $rel ) ) {
							foreach ( $rel as $rid ) {
								$rid = absint( $rid );
								if ( ! in_array( $rid, $related_ids, true ) && ! in_array( $rid, $base_ids, true ) ) {
									$related_ids[] = $rid;
								}
							}
						}
					}
				}
			}

			// If not enough related products found, fill with popular published products
			if ( count( $related_ids ) < $limit && function_exists( 'wc_get_products' ) ) {
				$fallback_products = wc_get_products( array(
					'limit'        => $limit,
					'status'       => 'publish',
					'orderby'      => 'popularity',
					'order'        => 'DESC',
					'exclude'      => array_merge( $base_ids, $related_ids ),
					'stock_status' => 'instock',
				) );
				foreach ( $fallback_products as $fp ) {
					if ( $fp && is_a( $fp, 'WC_Product' ) ) {
						$related_ids[] = $fp->get_id();
					}
				}
			}

			return array_slice( $related_ids, 0, $limit );
		}

		// 3. Best Selling Products
		if ( 'bestselling' === $query_type ) {
			$bestselling_ids = array();
			if ( function_exists( 'wc_get_products' ) ) {
				$bestselling = wc_get_products( array(
					'limit'        => $limit,
					'status'       => 'publish',
					'orderby'      => 'total_sales',
					'order'        => 'DESC',
					'exclude'      => $base_ids,
					'stock_status' => 'instock',
				) );

				foreach ( $bestselling as $bp ) {
					if ( $bp && is_a( $bp, 'WC_Product' ) ) {
						$bestselling_ids[] = $bp->get_id();
					}
				}

				// If total_sales query returned nothing (e.g. brand new store), fallback to popularity
				if ( empty( $bestselling_ids ) ) {
					$popular = wc_get_products( array(
						'limit'        => $limit,
						'status'       => 'publish',
						'orderby'      => 'popularity',
						'order'        => 'DESC',
						'exclude'      => $base_ids,
						'stock_status' => 'instock',
					) );
					foreach ( $popular as $pp ) {
						if ( $pp && is_a( $pp, 'WC_Product' ) ) {
							$bestselling_ids[] = $pp->get_id();
						}
					}
				}
			}

			return array_slice( $bestselling_ids, 0, $limit );
		}

		return array();
	}
}
