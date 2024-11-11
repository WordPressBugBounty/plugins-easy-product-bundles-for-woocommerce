<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Compatibilities;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles;

class SideCart {

	public static function init() {
		if ( defined( 'XOO_WSC_PLUGIN_FILE' ) ) {
			add_filter( 'xoo_wsc_product_args', [ __CLASS__, 'xoo_wsc_product_args' ], 99, 3 );
		}
	}

	public static function xoo_wsc_product_args( $args, $_product, $cart_item ) {
		if ( ProductBundles\is_cart_item_bundle_item( $cart_item ) ) {
			$args['showPdel'] = false;
		}

		return $args;
	}

}
