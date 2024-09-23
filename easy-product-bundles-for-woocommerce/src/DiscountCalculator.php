<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

defined( 'ABSPATH' ) || exit;

class DiscountCalculator {

	public static function calculate( $price, $discount, $discount_type = 'percentage' ) {
		$value = 0;
		switch ( $discount_type ) {
			case 'percentage':
				if (
					'' !== $discount &&
					0 < (float) $discount &&
					0 <= (float) $discount / 100 * $price
				) {
					$value = (float) $discount / 100 * $price;
				}
				break;

			case 'price':
				if (
					'' !== $discount &&
					0 <= (float) $discount
				) {
					$value = (float) $discount;
					$value = $value <= $price ? $value : 0;
				}
				break;
		}

		return apply_filters(
			'asnp_wepb_discount_calculator_calculate',
			$value,
			$price,
			$discount,
			$discount_type
		);
	}

}
