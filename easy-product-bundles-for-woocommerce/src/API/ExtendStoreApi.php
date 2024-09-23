<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\API;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use AsanaPlugins\WooCommerce\ProductBundles;
use AsanaPlugins\WooCommerce\ProductBundles\Helpers\Cart;

class ExtendStoreApi {

	/**
	 * Stores Rest Extending instance.
	 *
	 * @var ExtendSchema
	 */
	private static $extend;

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'easy_product_bundle';

	const CART_ITEM_ITEMS_KEY = 'asnp_wepb_items_key';

	/**
	 * Bootstraps the class and hooks required data.
	 *
	 * @param ExtendSchema $extend_rest_api An instance of the ExtendSchema class.
	 */
	public static function init( ExtendSchema $extend_rest_api ) {
		self::$extend = $extend_rest_api;
		self::extend_store();
		add_filter( 'rest_request_after_callbacks', [ __CLASS__, 'edit_cart_items_data' ], 10, 3 );
		add_filter( 'woocommerce_hydration_request_after_callbacks', [ __CLASS__, 'edit_cart_items_data' ], 10, 3 );
	}

	/**
	 * Registers the actual data into each endpoint.
	 */
	public static function extend_store() {
		if ( is_callable( [ self::$extend, 'register_endpoint_data' ] ) ) {
			self::$extend->register_endpoint_data(
				[
					'endpoint'        => CartItemSchema::IDENTIFIER,
					'namespace'       => self::IDENTIFIER,
					'data_callback'   => [ __CLASS__, 'extend_cart_item_data' ],
					'schema_callback' => [ __CLASS__, 'extend_cart_item_schema' ],
					'schema_type'     => ARRAY_A,
				]
			);
		}
	}

	/**
	 * Register subscription product data into cart/items endpoint.
	 *
	 * @param array $cart_item Current cart item data.
	 *
	 * @return array $item_data Registered data or empty array if condition is not satisfied.
	 */
	public static function extend_cart_item_data( $cart_item ) {
		$item_data = [];

		if ( ProductBundles\is_cart_item_bundle( $cart_item ) ) {
			$item_data['data'] = [
				'is_bundle' => true,
			];
		} elseif ( ProductBundles\is_cart_item_bundle_item( $cart_item ) ) {
			$hide_price = 'no';

			if ( ProductBundles\is_pro_active() ) {
				$hide_price = 'false' === ProductBundles\get_plugin()->settings->get_setting( 'show_item_price', 'true' ) ? 'yes' : 'no';
				if ( 'yes' !== $hide_price && ! empty( $cart_item['asnp_wepb_hide_price'] ) ) {
					if ( 'yes' === $cart_item['asnp_wepb_hide_price'] ) {
						$hide_price = 'yes';
					} elseif ( 'only_regular_price' === $cart_item['asnp_wepb_hide_price'] ) {
						$hide_price = 'regular';
					}
				}
			}


			$item_data['data'] = [
				'is_bundle_item' => true,
				'hide_price'     => $hide_price,
			];
		}

		return $item_data;
	}

	/**
	 * Register subscription product schema into cart/items endpoint.
	 *
	 * @return array Registered schema.
	 */
	public static function extend_cart_item_schema() {
		return [
			'data' => [
				'description' => __( 'Bundle data', 'asnp-easy-product-bundles' ),
				'type'        => [ 'object', 'null' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
			],
		];
	}

	public static function edit_cart_items_data( $response, $server, $request ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( false === strpos( $request->get_route(), 'wc/store' ) ) {
			return $response;
		}

		$data = $response->get_data();
		if ( empty( $data['items'] ) ) {
			return $response;
		}

		$cart_contents = WC()->cart->get_cart();
		$decimals      = wc_get_price_decimals();
		$raw_decimals  = wc_get_rounding_precision();

		foreach ( $data['items'] as &$item_data ) {
			$cart_item_key = $item_data['key'];
			$cart_item     = isset( $cart_contents[ $cart_item_key ] ) ? $cart_contents[ $cart_item_key ] : null;

			if ( ! $cart_item ) {
				continue;
			}

			if ( ProductBundles\is_cart_item_bundle( $cart_item ) ) {
				$item_data['prices'] = static::bundle_prices( $cart_item, $item_data['prices'], $decimals, $raw_decimals );
				$item_data['totals'] = static::bundle_totals( $cart_item, $item_data['totals'] );
			} elseif ( ProductBundles\is_cart_item_bundle_item( $cart_item ) ) {
				$item_data['prices']          = static::bundle_item_prices( $cart_item, $item_data['prices'], $decimals, $raw_decimals );
				$item_data['totals']          = static::bundle_item_totals( $cart_item, $item_data['totals'] );
				$item_data['quantity_limits'] = static::bundled_item_quantity_limits( $cart_item, $item_data['quantity_limits'] );
			}
		}

		$response->set_data( $data );

		return $response;
	}

	protected static function bundle_prices( $cart_item, $prices, $decimals = 2, $raw_decimals = 2 ) {
		if ( empty( $cart_item[ static::CART_ITEM_ITEMS_KEY ] ) ) {
			return $prices;
		}

		$prices                = is_array( $prices ) ? (object) $prices : $prices;
		$cart_contents         = WC()->cart->get_cart();
		$product_regular_price = Cart\display_prices_including_tax() ?
			wc_get_price_including_tax( $cart_item['data'], [ 'price' => (float) $cart_item['data']->get_regular_price( 'edit' ) ] ) :
			wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => (float) $cart_item['data']->get_regular_price( 'edit' ) ] );

		if ( isset( $cart_item['asnp_wepb_is_fixed_price'] ) && $cart_item['asnp_wepb_is_fixed_price'] ) {
			if (
				ProductBundles\is_pro_active() &&
				'only_regular_price' === $cart_item['data']->get_hide_items_price()
			) {
				return $prices;
			}

			$sale_price    = Cart\display_prices_including_tax() ?
				wc_get_price_including_tax( $cart_item['data'], [ 'price' => (float) $cart_item['data']->get_price( 'edit' ) ] ) :
				wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => (float) $cart_item['data']->get_price( 'edit' ) ] );
			$regular_price = 0;

			$prices->regular_price               = static::prepare_money_response( $sale_price, $decimals );
			$prices->raw_prices['regular_price'] = static::prepare_money_response( $sale_price, $raw_decimals );

			if ( 'true' === $cart_item['data']->get_include_parent_price() ) {
				if ( '' !== $cart_item['data']->get_regular_price( 'edit' ) ) {
					$regular_price = Cart\display_prices_including_tax() ?
						wc_get_price_including_tax( $cart_item['data'], [ 'price' => (float) $cart_item['data']->get_regular_price( 'edit' ) ] ) :
						wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => (float) $cart_item['data']->get_regular_price( 'edit' ) ] );
				}
			}

			foreach ( $cart_item[ static::CART_ITEM_ITEMS_KEY ] as $item_key ) {
				if ( ! isset( $cart_contents[ $item_key ] ) ) {
					return $prices;
				}

				if ( ! empty( $cart_contents[ $item_key ]['asnp_wepb_reg_price'] ) ) {
					$regular_price += Cart\display_prices_including_tax() ?
						wc_get_price_including_tax( $cart_contents[ $item_key ]['data'], [ 'price' => (float) $cart_contents[ $item_key ]['asnp_wepb_reg_price'], 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] ) :
						wc_get_price_excluding_tax( $cart_contents[ $item_key ]['data'], [ 'price' => (float) $cart_contents[ $item_key ]['asnp_wepb_reg_price'], 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] );
				}
			}

			$regular_price = $regular_price > $product_regular_price ? $regular_price : $product_regular_price;

			if ( $regular_price > $sale_price ) {
				$prices->regular_price               = static::prepare_money_response( $regular_price, $decimals );
				$prices->raw_prices['regular_price'] = static::prepare_money_response( $regular_price, $raw_decimals );
			}
			return $prices;
		}

		$price         = 0;
		$regular_price = 0;
		$show_regular = true;
		if ( ProductBundles\is_pro_active() ) {
			if (
				'yes' === $cart_item['data']->get_hide_items_price() ||
				'only_regular_price' === $cart_item['data']->get_hide_items_price()
			) {
				$show_regular = false;
			}
		}

		if ( 'true' === $cart_item['data']->get_include_parent_price() ) {
			if ( '' !== $cart_item['data']->get_price( 'edit' ) ) {
				$price = Cart\display_prices_including_tax() ?
					wc_get_price_including_tax( $cart_item['data'], [ 'price' => (float) $cart_item['data']->get_price( 'edit' ) ] ) :
					wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => (float) $cart_item['data']->get_price( 'edit' ) ] );
			}

			if ( $show_regular && '' !== $cart_item['data']->get_regular_price( 'edit' ) ) {
				$regular_price = Cart\display_prices_including_tax() ?
					wc_get_price_including_tax( $cart_item['data'], [ 'price' => (float) $cart_item['data']->get_regular_price( 'edit' ) ] ) :
					wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => (float) $cart_item['data']->get_regular_price( 'edit' ) ] );
			}
		}

		foreach ( $cart_item[ self::CART_ITEM_ITEMS_KEY ] as $item_key ) {
			if ( ! isset( $cart_contents[ $item_key ] ) ) {
				return $price;
			}

			if ( ! empty( $cart_contents[ $item_key ]['asnp_wepb_price'] ) ) {
				$price += Cart\display_prices_including_tax() ?
					wc_get_price_including_tax( $cart_contents[ $item_key ]['data'], [ 'price' => (float) $cart_contents[ $item_key ]['asnp_wepb_price'], 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] ) :
					wc_get_price_excluding_tax( $cart_contents[ $item_key ]['data'], [ 'price' => (float) $cart_contents[ $item_key ]['asnp_wepb_price'], 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] );
			}

			if ( $show_regular && ! empty( $cart_contents[ $item_key ]['asnp_wepb_reg_price'] ) ) {
				$regular_price += Cart\display_prices_including_tax() ?
					wc_get_price_including_tax( $cart_contents[ $item_key ]['data'], [ 'price' => (float) $cart_contents[ $item_key ]['asnp_wepb_reg_price'], 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] ) :
					wc_get_price_excluding_tax( $cart_contents[ $item_key ]['data'], [ 'price' => (float) $cart_contents[ $item_key ]['asnp_wepb_reg_price'], 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] );
			}
		}

		if ( $show_regular ) {
			$regular_price = $regular_price > $product_regular_price ? $regular_price : $product_regular_price;

			$prices->regular_price               = static::prepare_money_response( $regular_price, $decimals );
			$prices->raw_prices['regular_price'] = static::prepare_money_response( $regular_price, $raw_decimals );
		}

		$prices->price                       = $prices->sale_price = static::prepare_money_response( $price, $decimals );
		$prices->raw_prices['price']         = $prices->raw_prices['sale_price'] = static::prepare_money_response( $price, $raw_decimals );

		return $prices;
	}

	protected static function bundle_item_prices( $cart_item, $prices, $decimals = 2, $raw_decimals = 2 ) {
		if ( ! isset( $cart_item['asnp_wepb_price'] ) ) {
			return $prices;
		}

		$prices = is_array( $prices ) ? (object) $prices : $prices;

		if ( Cart\display_prices_including_tax() ) {
			$price = wc_get_price_including_tax( $cart_item['data'], [ 'price' => $cart_item['asnp_wepb_price'] ] );
		} else {
			$price = wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => $cart_item['asnp_wepb_price'] ] );
		}

		$prices->price               = $prices->regular_price = $prices->sale_price = static::prepare_money_response( $price, $decimals );
		$prices->raw_prices['price'] = $prices->raw_prices['regular_price'] = $prices->raw_prices['sale_price'] = static::prepare_money_response( $price, $raw_decimals );

		$show_regular = true;
		if ( ProductBundles\is_pro_active() ) {
			if (
				'yes' === $cart_item['asnp_wepb_hide_price'] ||
				'only_regular_price' === $cart_item['asnp_wepb_hide_price']
			) {
				$show_regular = false;
			}
		}

		if (
			$show_regular &&
			isset( $cart_item['asnp_wepb_reg_price'] ) &&
			(float) $cart_item['asnp_wepb_reg_price'] > (float) $cart_item['asnp_wepb_price']
		) {
			if ( Cart\display_prices_including_tax() ) {
				$regular_price = wc_get_price_including_tax( $cart_item['data'], [ 'price' => $cart_item['asnp_wepb_reg_price'] ] );
			} else {
				$regular_price = wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => $cart_item['asnp_wepb_reg_price'] ] );
			}
			$prices->regular_price               = static::prepare_money_response( $regular_price, $decimals );
			$prices->raw_prices['regular_price'] = static::prepare_money_response( $regular_price, $raw_decimals );
		}

		return $prices;
	}

	protected static function bundle_totals( $cart_item, $totals ) {
		if ( isset( $cart_item['asnp_wepb_is_fixed_price'] ) && $cart_item['asnp_wepb_is_fixed_price'] ) {
			return $totals;
		}

		if ( empty( $cart_item[ self::CART_ITEM_ITEMS_KEY ] ) ) {
			return $totals;
		}

		$totals   = is_array( $totals ) ? (object) $totals : $totals;
		$decimals = isset( $totals->currency_minor_unit ) ? $totals->currency_minor_unit : wc_get_price_decimals();

		$cart_contents = WC()->cart->get_cart();

		$price = 0;
		if ( 'true' === $cart_item['data']->get_include_parent_price() ) {
			if ( '' !== $cart_item['data']->get_price( 'edit' ) ) {
				$price = Cart\display_prices_including_tax() ?
					wc_get_price_including_tax( $cart_item['data'], [ 'price' => (float) $cart_item['data']->get_price( 'edit' ) ] ) :
					wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => (float) $cart_item['data']->get_price( 'edit' ) ] );
			}
		}

		foreach ( $cart_item[ self::CART_ITEM_ITEMS_KEY ] as $item_key ) {
			if ( ! isset( $cart_contents[ $item_key ] ) ) {
				return $totals;
			}

			if ( isset( $cart_contents[ $item_key ]['asnp_wepb_price'] ) ) {
				$price += Cart\display_prices_including_tax() ?
					wc_get_price_including_tax( $cart_contents[ $item_key ]['data'], [ 'price' => (float) $cart_contents[ $item_key ]['asnp_wepb_price'], 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] ) :
					wc_get_price_excluding_tax( $cart_contents[ $item_key ]['data'], [ 'price' => (float) $cart_contents[ $item_key ]['asnp_wepb_price'], 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] );
			}
		}

		$totals->line_total        = static::prepare_money_response( $price * $cart_item['quantity'], $decimals );
		$totals->line_total_tax    = static::prepare_money_response( 0, $decimals );
		$totals->line_subtotal     = static::prepare_money_response( $price * $cart_item['quantity'], $decimals );
		$totals->line_subtotal_tax = static::prepare_money_response( 0, $decimals );

		return $totals;
	}

	protected static function bundle_item_totals( $cart_item, $totals ) {
		if ( ! isset( $cart_item['asnp_wepb_price'] ) ) {
			return $totals;
		}

		$totals   = is_array( $totals ) ? (object) $totals : $totals;
		$decimals = isset( $totals->currency_minor_unit ) ? $totals->currency_minor_unit : wc_get_price_decimals();

		if ( Cart\display_prices_including_tax() ) {
			$price = wc_get_price_including_tax( $cart_item['data'], [ 'price' => $cart_item['asnp_wepb_price'] ] );
		} else {
			$price = wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => $cart_item['asnp_wepb_price'] ] );
		}

		$totals->line_total        = static::prepare_money_response( $price * $cart_item['quantity'], $decimals );
		$totals->line_total_tax    = static::prepare_money_response( 0, $decimals );
		$totals->line_subtotal     = static::prepare_money_response( $price * $cart_item['quantity'], $decimals );
		$totals->line_subtotal_tax = static::prepare_money_response( 0, $decimals );

		return $totals;
	}

	protected static function bundled_item_quantity_limits( $cart_item, $quantity_limits ) {
		$quantity_limits           = is_array( $quantity_limits ) ? (object) $quantity_limits : $quantity_limits;
		$quantity_limits->minimum  = $quantity_limits->maximum = $cart_item['quantity'];
		// $quantity_limits->editable = true;
		return $quantity_limits;
	}

	/**
	 * Convert monetary values from WooCommerce to string based integers, using
	 * the smallest unit of a currency.
	 *
	 * @param string|float $amount Monetary amount with decimals.
	 * @param int          $decimals Number of decimals the amount is formatted with.
	 * @param int          $rounding_mode Defaults to the PHP_ROUND_HALF_UP constant.
	 * @return string      The new amount.
	 */
	protected static function prepare_money_response( $amount, $decimals = 2, $rounding_mode = PHP_ROUND_HALF_UP ) {
		return static::$extend->get_formatter( 'money' )->format(
			$amount,
			[
				'decimals'      => $decimals,
				'rounding_mode' => $rounding_mode,
			]
		);
	}

}
