<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Compatibilities;

defined( 'ABSPATH' ) || exit;

class WOOCS {

	public static function init() {
		add_filter( 'asnp_wepb_maybe_exchange_price', [ __CLASS__, 'maybe_exchange_price' ], 100 );
	}

	public static function maybe_exchange_price( $price ) {
		global $WOOCS;
		if ( ! $WOOCS ) {
			return $price;
		}

        if ( $WOOCS->current_currency == $WOOCS->default_currency ) {
            return $price;
        }

        return $WOOCS->woocs_exchange_value( $price );
    }

}
