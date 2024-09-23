<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\ShortCode;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles\Plugin;

class ProductShortCode {

	public static function output( $atts ) {
		global $product;
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return '';
		}

		$atts = shortcode_atts( [ 'show_add_to_cart' => 0 ], $atts, 'asnp_wepb_product' );

		ob_start();

		echo '<div id="asnp_easy_product_bundle"></div>';

		if ( (int) $atts['show_add_to_cart'] ) {
			wc_get_template( 'single-product/add-to-cart/simple.php' );
		}

		return ob_get_clean();
	}

}
