<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles\Blocks\Integrations\CheckoutIntegration;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use AsanaPlugins\WooCommerce\ProductBundles\API\ExtendStoreApi;

class BlocksHooks {

	public static function init() {
		$extend = StoreApi::container()->get( ExtendSchema::class );
		ExtendStoreApi::init( $extend );

		add_action(
			'woocommerce_blocks_mini-cart_block_registration',
			function( $registry ) {
				$registry->register( new CheckoutIntegration() );
			}
		);
		add_action(
			'woocommerce_blocks_cart_block_registration',
			function( $registry ) {
				$registry->register( new CheckoutIntegration() );
			}
		);
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function( $registry ) {
				$registry->register( new CheckoutIntegration() );
			}
		);
	}

}
