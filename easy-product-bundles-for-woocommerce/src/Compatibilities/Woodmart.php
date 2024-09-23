<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Compatibilities;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles;

class Woodmart {

	public static function init() {
		add_filter( 'woodmart_show_widget_cart_item_quantity', [ __CLASS__, 'widget_cart_item_quantity' ], 100, 2 );
	}

	public static function widget_cart_item_quantity( $show, $cart_item_key ) {
		$cart_items = WC()->cart->get_cart();
		if ( empty( $cart_items ) || ! isset( $cart_items[ $cart_item_key ] ) ) {
			return $show;
		}

		if ( ProductBundles\is_cart_item_bundle_item( $cart_items[ $cart_item_key ] ) ) {
			return false;
		}

		return $show;
	}

}
