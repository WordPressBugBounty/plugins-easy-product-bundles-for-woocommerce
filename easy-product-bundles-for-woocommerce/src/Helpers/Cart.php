<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Helpers\Cart;

defined( 'ABSPATH' ) || exit;

/**
 * Return whether or not the cart is displaying prices including tax, rather than excluding tax.
 *
 * @since 1.0.0
 * @return bool
 */
function display_prices_including_tax() {
	if ( is_callable( [ WC()->cart, 'display_prices_including_tax' ] ) ) {
		return WC()->cart->display_prices_including_tax();
	}

	return apply_filters( 'woocommerce_cart_display_prices_including_tax', 'incl' === WC()->cart->get_tax_price_display_mode() );
}
