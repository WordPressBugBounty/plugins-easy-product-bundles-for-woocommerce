<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Abstracts;

defined( 'ABSPATH' ) || exit;

interface ProductSelectorInterface {
	public function select_products( $item, array $args = array() );
}
