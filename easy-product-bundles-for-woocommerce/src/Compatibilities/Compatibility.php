<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Compatibilities;

defined( 'ABSPATH' ) || exit;

class Compatibility {

	public static function init() {
		if ( 'woodmart' === get_stylesheet() || 'woodmart' === get_template() ) {
			Woodmart::init();
		}
	}

}
