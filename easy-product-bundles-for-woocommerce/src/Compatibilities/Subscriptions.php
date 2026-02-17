<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Compatibilities;

defined( 'ABSPATH' ) || exit;

class Subscriptions {

	public static function init() {
		add_filter( 'wcsatt_supported_product_types', function ( $types ) {
			$types[] = 'easy_product_bundle';
			return $types;
		} );
	}

}
