<?php
namespace WCSC;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Variation_Handler
 *
 * Handles WooCommerce variable product attributes, swatches (image/button),
 * fallback image logic, and variation data generation for client-side matching.
 */
class Variation_Handler {

	/**
	 * Get structured attributes data for a variable product.
	 *
	 * @param \WC_Product_Variable $product
	 * @param array                $widget_settings
	 * @return array
	 */
	public static function get_product_attributes_data( $product, $widget_settings = array() ) {
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return array();
		}

		$attributes           = $product->get_variation_attributes();
		$available_variations = $product->get_available_variations();
		$default_display_type = isset( $widget_settings['default_display_type'] ) ? $widget_settings['default_display_type'] : 'button';
		$attribute_overrides  = array();
		$label_overrides      = array();

		// Parse repeater overrides from widget settings if provided.
		if ( ! empty( $widget_settings['attribute_types'] ) && is_array( $widget_settings['attribute_types'] ) ) {
			foreach ( $widget_settings['attribute_types'] as $item ) {
				if ( ! empty( $item['attribute_name'] ) ) {
					$key = strtolower( trim( $item['attribute_name'] ) );
					if ( ! empty( $item['display_type'] ) ) {
						$attribute_overrides[ $key ] = $item['display_type'];
					}
					if ( ! empty( $item['custom_label'] ) ) {
						$label_overrides[ $key ] = trim( $item['custom_label'] );
					}
				}
			}
		}

		$parsed_attributes = array();

		foreach ( $attributes as $attribute_name => $options ) {
			$taxonomy_obj = taxonomy_exists( $attribute_name ) ? get_taxonomy( $attribute_name ) : null;
			$label        = wc_attribute_label( $attribute_name, $product );
			$attr_key     = strtolower( trim( $label ) );
			$attr_slug    = sanitize_title( $attribute_name );

			// Determine label (custom label override or WooCommerce default).
			if ( isset( $label_overrides[ $attr_key ] ) ) {
				$label = $label_overrides[ $attr_key ];
			} elseif ( isset( $label_overrides[ strtolower( $attribute_name ) ] ) ) {
				$label = $label_overrides[ strtolower( $attribute_name ) ];
			} elseif ( isset( $label_overrides[ $attr_slug ] ) ) {
				$label = $label_overrides[ $attr_slug ];
			}

			// Determine display type (override or default).
			$display_type = $default_display_type;
			if ( isset( $attribute_overrides[ $attr_key ] ) ) {
				$display_type = $attribute_overrides[ $attr_key ];
			} elseif ( isset( $attribute_overrides[ strtolower( $attribute_name ) ] ) ) {
				$display_type = $attribute_overrides[ strtolower( $attribute_name ) ];
			} elseif ( isset( $attribute_overrides[ $attr_slug ] ) ) {
				$display_type = $attribute_overrides[ $attr_slug ];
			}

			$parsed_options = array();

			if ( is_array( $options ) ) {
				foreach ( $options as $option ) {
					$option_data = self::resolve_option_data(
						$option,
						$attribute_name,
						$display_type,
						$product,
						$available_variations
					);
					$parsed_options[] = $option_data;
				}
			}

			// Get default value if set.
			$default_attributes = $product->get_default_attributes();
			$default_value      = isset( $default_attributes[ sanitize_title( $attribute_name ) ] ) ? $default_attributes[ sanitize_title( $attribute_name ) ] : '';

			$parsed_attributes[] = array(
				'name'          => $attribute_name,
				'label'         => $label,
				'display_type'  => $display_type,
				'default_value' => $default_value,
				'options'       => $parsed_options,
			);
		}

		return $parsed_attributes;
	}

	/**
	 * Resolve option label, slug, image, and fallback for button/image display.
	 *
	 * @param string                $option
	 * @param string                $attribute_name
	 * @param string                $display_type
	 * @param \WC_Product_Variable  $product
	 * @param array                 $available_variations
	 * @return array
	 */
	protected static function resolve_option_data( $option, $attribute_name, $display_type, $product, $available_variations ) {
		$name = $option;
		$slug = $option;

		// If taxonomy-based attribute (e.g. pa_color, pa_size).
		if ( taxonomy_exists( $attribute_name ) ) {
			$term = get_term_by( 'slug', $option, $attribute_name );
			if ( ! $term && is_numeric( $option ) ) {
				$term = get_term_by( 'id', $option, $attribute_name );
			}
			if ( $term && ! is_wp_error( $term ) ) {
				$name = $term->name;
				$slug = $term->slug;
			}
		}

		$image_url = '';
		$fallback_color = '';

		if ( 'image' === $display_type ) {
			// 1. Try to find a variation that specifically has this attribute value and an image.
			$attr_key = 'attribute_' . sanitize_title( $attribute_name );

			foreach ( $available_variations as $variation ) {
				if ( isset( $variation['attributes'][ $attr_key ] ) ) {
					$val = $variation['attributes'][ $attr_key ];
					if ( '' === $val || $val === $slug || sanitize_title( $val ) === sanitize_title( $slug ) ) {
						if ( ! empty( $variation['image']['thumb_src'] ) ) {
							$image_url = $variation['image']['thumb_src'];
							break;
						} elseif ( ! empty( $variation['image']['src'] ) ) {
							$image_url = $variation['image']['src'];
							break;
						}
					}
				}
			}

			// 2. Check for WooCommerce term thumbnail or swatch metadata (if standard term plugins exist).
			if ( empty( $image_url ) && taxonomy_exists( $attribute_name ) && ! empty( $term ) ) {
				$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
				if ( $thumbnail_id ) {
					$image_url = wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' );
				}
				if ( empty( $image_url ) ) {
					$fallback_color = get_term_meta( $term->term_id, 'product_attribute_color', true ) ?: '';
				}
			}

			// 3. If still empty, check if option name is a recognizable color name or hex.
			if ( empty( $image_url ) ) {
				$clean_name = strtolower( trim( $name ) );
				$common_colors = array(
					'black'  => '#111111',
					'white'  => '#ffffff',
					'red'    => '#e53e3e',
					'blue'   => '#3182ce',
					'green'  => '#38a169',
					'yellow' => '#d69e2e',
					'orange' => '#dd6b20',
					'purple' => '#805ad5',
					'pink'   => '#d53f8c',
					'gray'   => '#718096',
					'grey'   => '#718096',
					'brown'  => '#7b341e',
					'navy'   => '#1a365d',
					'silver' => '#cbd5e0',
					'gold'   => '#d4af37',
				);
				if ( isset( $common_colors[ $clean_name ] ) ) {
					$fallback_color = $common_colors[ $clean_name ];
				} elseif ( preg_match( '/^#([a-f0-9]{3}){1,2}\b/i', $name ) ) {
					$fallback_color = $name;
				}
			}
		}

		return array(
			'value'          => $slug,
			'label'          => $name,
			'image_url'      => $image_url,
			'fallback_color' => $fallback_color,
			'has_image'      => ! empty( $image_url ),
		);
	}

	/**
	 * Prepare clean JSON variation data for the frontend matching script.
	 *
	 * @param \WC_Product_Variable $product
	 * @return array
	 */
	public static function get_available_variations_json( $product ) {
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return array();
		}

		$variations = $product->get_available_variations();
		$clean_data = array();

		foreach ( $variations as $var ) {
			$clean_data[] = array(
				'variation_id'          => $var['variation_id'],
				'attributes'            => $var['attributes'],
				'display_price'         => $var['display_price'],
				'display_regular_price' => $var['display_regular_price'],
				'price_html'            => $var['price_html'],
				'is_in_stock'           => $var['is_in_stock'],
				'is_purchasable'        => $var['is_purchasable'],
				'max_qty'               => $var['max_qty'],
				'min_qty'               => $var['min_qty'],
				'image'                 => array(
					'src'    => ! empty( $var['image']['src'] ) ? $var['image']['src'] : '',
					'srcset' => ! empty( $var['image']['srcset'] ) ? $var['image']['srcset'] : '',
					'sizes'  => ! empty( $var['image']['sizes'] ) ? $var['image']['sizes'] : '',
					'alt'    => ! empty( $var['image']['alt'] ) ? $var['image']['alt'] : '',
					'title'  => ! empty( $var['image']['title'] ) ? $var['image']['title'] : '',
				),
				'availability_html'     => $var['availability_html'],
			);
		}

		return $clean_data;
	}
}
