<?php
namespace WCSC\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use WCSC\Elementor_Widget;

/**
 * Class Widget_Bundle_Checkout
 *
 * Dedicated All-in-One Bundle Smart Checkout Widget.
 * Inherits all 5,000+ lines of comprehensive checkout layout, styling,
 * order bump, and payment controls from Elementor_Widget with default bundle mode.
 */
class Widget_Bundle_Checkout extends Elementor_Widget {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wcsc_bundle_checkout';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Bundle Smart Checkout', 'wc-smart-checkout-builder' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-cart-medium';
	}
}
