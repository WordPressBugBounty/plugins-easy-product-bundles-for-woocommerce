<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Compatibilities;

defined( 'ABSPATH' ) || exit;

class WooPayments {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'exchange_price' ), 999 );
        add_filter( 'asnp_wepb_currency_code', [ __CLASS__, 'get_currency_code' ], 999 );
        // add_filter( 'wcpay_multi_currency_should_convert_product_price', [ __CLASS__, 'should_convert_product_price' ], 999 );
    }

    public static function exchange_price() {
        $multi_currency = \WC_Payments_Multi_Currency();
        $enabled_currencies = $multi_currency->get_enabled_currencies();
        if ( 1 < count( $enabled_currencies ) ) {
            add_filter( 'asnp_wepb_maybe_exchange_price', array( __CLASS__, 'maybe_exchange_price' ), 10, 2 );
        }
    }

    public static function maybe_exchange_price( $price, $type = 'product' ) {
        $multi_currency = \WC_Payments_Multi_Currency();
        return $multi_currency->get_price( $price, $type );
    }

    public static function get_currency_code() {
        $multi_currency = \WC_Payments_Multi_Currency();
        return $multi_currency->get_selected_currency()->get_code();
    }

    public static function should_convert_product_price( $convert ) {
        if ( $convert ) {
            return $convert;
        }

        if ( WC()->is_rest_api_request() ) {
            return false !== strpos( $_SERVER['REQUEST_URI'], trailingslashit( rest_get_url_prefix() ) . 'asnp-easy-product-bundles/' );
        }

        return $convert;
    }

}
