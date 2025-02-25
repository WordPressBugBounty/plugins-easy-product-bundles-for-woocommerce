<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Compatibilities;

defined( 'ABSPATH' ) || exit;

class Compatibility {

	public static function init() {
		if ( 'woodmart' === get_stylesheet() || 'woodmart' === get_template() ) {
			Woodmart::init();
		}

		// CURCY - Multi Currency for WooCommerce compatibility.
		if (
			is_callable( [ '\WOOMULTI_CURRENCY_F_Data', 'get_ins' ] ) ||
			is_callable( [ '\WOOMULTI_CURRENCY_Data', 'get_ins' ] )
		) {
			Curcy::init();
		}

		// FOX - WooCommerce Currency Switcher(WOOCS) compatibility.
		if ( class_exists( '\WOOCS_STARTER' ) ) {
			WOOCS::init();
		}

		if ( class_exists( '\WooCommerce_Square_Loader' ) ) {
			Square::init();
		}

		SideCart::init();
	}

}
