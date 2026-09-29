<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Compatibilities;

defined( 'ABSPATH' ) || exit;

class PayPal {

	public static function init() {
		/**
         * Disable PayPal Payments buttons & messaging on the single product page for bundle products.
         */
        add_filter( 'woocommerce_paypal_payments_product_supports_payment_request_button', function( $supports, $product ) {
            if ( $product && $product->is_type( 'easy_product_bundle' ) ) {
                return false;
            }

            return $supports;
        }, 10, 2 );

        /**
         * Remove 'product' from active PayPal button locations for bundle products
         * to prevent the frontend script from attaching observers/listeners to form.cart.
         */
        add_filter( 'woocommerce_paypal_payments_selected_button_locations', function( $locations ) {
            if ( is_product() ) {
                $product = wc_get_product();
                if ( $product && $product->is_type( 'easy_product_bundle' ) ) {
                    return array_diff( $locations, array( 'product' ) );
                }
            }

            return $locations;
        } );
	}

}
