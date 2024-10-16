<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Compatibilities;

defined( 'ABSPATH' ) || exit;

class Curcy {

	public static function init() {
		add_filter( 'asnp_wepb_maybe_exchange_price', [ __CLASS__, 'maybe_exchange_price' ], 100 );
	}

	public static function maybe_exchange_price( $price ) {
        if ( empty( $price ) ) {
            return $price;
        }

		$settings = static::get_settings();
		if ( ! $settings ) {
			return $price;
		}

        $default_currency = $settings->get_default_currency();
		$current_currency = $settings->get_current_currency();

        if ( ! $current_currency || $default_currency === $current_currency ) {
            return $price;
        }

        return wmc_get_price( $price );
    }

	protected static function get_settings() {
		if ( is_callable( [ '\WOOMULTI_CURRENCY_F_Data', 'get_ins' ] ) ) {
			return \WOOMULTI_CURRENCY_F_Data::get_ins();
		}

		if ( is_callable( [ '\WOOMULTI_CURRENCY_Data', 'get_ins' ] ) ) {
			return \WOOMULTI_CURRENCY_Data::get_ins();
		}

		return null;
	}

}
