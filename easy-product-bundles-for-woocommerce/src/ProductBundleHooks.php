<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles\Helpers\Cart;

class ProductBundleHooks {

	const CART_ITEM_ITEMS     = 'asnp_wepb_items';
	const CART_ITEM_ITEMS_KEY = 'asnp_wepb_items_key';

	public function init() {
		// Product type hooks.
		add_action( 'woocommerce_product_class', array( $this, 'bundle_product_class' ), 99, 2 );
		add_filter( 'product_type_selector', array( $this, 'product_type_selector' ) );

		// Product page hooks.
		add_action( 'woocommerce_' . Plugin::PRODUCT_TYPE . '_add_to_cart', array( $this, 'add_to_cart_template' ) );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_items_field' ) );
		$this->product_bundle_position_hooks();

		// Price hooks.
		add_filter( 'woocommerce_get_price_html', array( $this, 'get_price_html' ), 999, 2 );

		// Cart hooks.
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'add_to_cart_validation' ), 10, 6 );
		add_filter( 'woocommerce_update_cart_validation', array( $this, 'update_cart_validation' ), 10, 4 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'add_to_cart' ), 10, 6 );
		add_filter( 'woocommerce_cart_contents_count', array( $this, 'cart_contents_count' ) );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );

		// Mini Cart hooks.
		add_filter( 'woocommerce_mini_cart_item_class', array( $this, 'cart_item_class' ), 10, 2 );

		// Cart item hooks.
		if ( 'true' === get_plugin()->settings->get_setting( 'show_parent_name', 'false' ) ) {
			add_filter( 'woocommerce_cart_item_name', array( $this, 'cart_item_name' ), 10, 2 );
		}
		add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'cart_item_remove_link' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'cart_item_quantity' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'cart_item_price' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'cart_item_subtotal' ), 10, 2 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'cart_item_removed' ), 10, 2 );
		add_action( 'woocommerce_cart_item_restored', array( $this, 'cart_item_restored' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_class', array( $this, 'cart_item_class' ), 10, 2 );

		// Checkout hooks.
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'checkout_create_order_line_item' ), 10, 3 );

		// Order hooks.
		if ( 'true' === get_plugin()->settings->get_setting( 'show_parent_name', 'false' ) ) {
			add_filter( 'woocommerce_order_item_name', array( $this, 'cart_item_name' ), 10, 2 );
		}
		add_filter( 'woocommerce_get_item_count', array( $this, 'get_item_count' ), 10, 3 );
		add_filter( 'woocommerce_order_item_class', array( $this, 'cart_item_class' ), 10, 2 );
		add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'formatted_line_subtotal' ), 10, 2 );
		add_action( 'woocommerce_order_item_meta_start', array( $this, 'before_order_item_meta' ), 10, 2 );

		// Admin order hooks.
		add_action( 'woocommerce_ajax_add_order_item_meta', array( $this, 'ajax_add_order_item_meta' ), 10, 3 );
		add_action( 'woocommerce_before_order_itemmeta', array( $this, 'before_order_item_meta' ), 10, 2 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hidden_order_itemmeta' ), 10, 1 );

		// Calculate totals hooks.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'before_calculate_totals' ), 99999 );
		add_action( 'woocommerce_before_mini_cart_contents', array( $this, 'before_mini_cart_contents' ) );

		// Loop add to cart hooks.
		add_action( 'woocommerce_loop_add_to_cart_link', array( $this, 'loop_add_to_cart_link' ), 99, 2 );

		// Coupon hooks.
		add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'coupon_is_valid_for_product' ), 10, 4 );

		// Shipping hooks.
		add_filter( 'woocommerce_cart_contents_weight', array( $this, 'cart_contents_weight' ), 999 );
		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'cart_shipping_packages' ), 999 );

		// Order again hooks.
		add_filter( 'woocommerce_order_again_cart_item_data', array( $this, 'order_again_cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'cart_loaded_from_session' ) );
	}

	/**
	 * Add support for the 'easy_product_bundles' product type.
	 *
	 * @param  array  $types
	 *
	 * @return array
	 */
	public function product_type_selector( $types ) {
		$types[ Plugin::PRODUCT_TYPE ] = __( 'Product bundle', 'asnp-easy-product-bundles' );
		return $types;
	}

	public function bundle_product_class( $classname, $product_type ) {
		if ( Plugin::PRODUCT_TYPE === $product_type ) {
			$classname = ProductBundle::class;
		}
		return $classname;
	}

	public function display_product_bundle() {
		global $product;
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return;
		}

		echo '<div id="asnp_easy_product_bundle" class="asnp_easy_product_bundle"></div>';
	}

	public function add_to_cart_template() {
		wc_get_template( 'single-product/add-to-cart/simple.php' );
	}

	public function add_items_field() {
		global $product;
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return;
		}

		$default_products = $product->get_default_products();
		$value            = ! empty( $default_products ) ? $default_products : '';
		if ( ! empty( $default_products ) ) {
			$default_products = get_product_ids_from_bundle_items( $default_products );
			foreach ( $default_products as $default_product ) {
				$default_product = wc_get_product( $default_product );
				// If one of items is not a product or not purchasable then the value should be empty.
				if ( ! $default_product || ! $default_product->is_purchasable() ) {
					$value = '';
					break;
				}
			}
		}

		echo '<input type="hidden" id="asnp_wepb_items" name="asnp_wepb_items" value="' . esc_attr( $value ) . '" />';
	}

	public function get_price_html( $price, $product ) {
		if ( ! $product->is_type( Plugin::PRODUCT_TYPE ) || $product->is_fixed_price() ) {
			return $price;
		}

		if ( ! apply_filters( 'asnp_wepb_apply_get_price_html', true, $price, $product ) ) {
			return $price;
		}

		$custom_price = $product->get_custom_display_price();
		if ( ! empty( $custom_price ) ) {
			return apply_filters( 'asnp_wepb_get_price_html', wp_kses_post( $custom_price ), $price, $product );
		}

		$prices = $product->get_default_products_price_for( 'display' );
		if ( empty( $prices ) || ! isset( $prices['min'] ) ) {
			return $price;
		}

		if ( ! empty( $prices['min'] ) ) {
			$prices['min'] = maybe_exchange_price( $prices['min'] );
		}

		if ( ! empty( $prices['regular'] ) ) {
			$prices['regular'] = maybe_exchange_price( $prices['regular'] );
		}

		if ( ! empty( $prices['total'] ) ) {
			$prices['total'] = maybe_exchange_price( $prices['total'] );
		}

		$price_type = get_plugin()->settings->get_setting( 'auto_calculate_price_type', 'total' );

		$show_regular = true;
		if ( is_pro_active() ) {
			if (
				'yes' === $product->get_hide_items_price() ||
				'only_regular_price' === $product->get_hide_items_price()
			) {
				$show_regular = false;
			}
		}

		if ( 'from_min' === $price_type ) {
			$content = __( 'From', 'asnp-easy-product-bundles' ) . ' ' . wc_price( $prices['min'] ) . $product->get_price_suffix();
		} elseif ( 'from_total' === $price_type ) {
			if ( $show_regular && $prices['regular'] > $prices['total'] ) {
				$content = __( 'From', 'asnp-easy-product-bundles' ) . ' ' . wc_format_sale_price( $prices['regular'], $prices['total'] ) . $product->get_price_suffix();
			} else {
				$content = __( 'From', 'asnp-easy-product-bundles' ) . ' ' . wc_price( $prices['total'] ) . $product->get_price_suffix();
			}
		} else {
			if ( $show_regular && $prices['regular'] > $prices['total'] ) {
				$content = wc_format_sale_price( $prices['regular'], $prices['total'] ) . $product->get_price_suffix();
			} else {
				$content = wc_price( $prices['total'] ) . $product->get_price_suffix();
			}
		}

		return apply_filters(
			'asnp_wepb_get_price_html',
			$content,
			$price,
			$product,
			$prices['min'],
			$prices['total']
		);
	}

	public function add_to_cart_validation( $passed, $product_id, $product_quantity, $variation_id = null, $variations = null, $cart_item_data = null ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return $passed;
		}

		try {
			$req_items = ! empty( $_REQUEST['asnp_wepb_items'] ) ? explode( ',', sanitize_text_field( $_REQUEST['asnp_wepb_items'] ) ) : '';
			if ( empty( $req_items ) && ! empty( $cart_item_data[ self::CART_ITEM_ITEMS ] ) ) {
				$req_items = explode( ',', sanitize_text_field( $cart_item_data[ self::CART_ITEM_ITEMS ] ) );
			}

			$ids        = ! empty( $req_items ) ? get_product_ids_from_bundle_items( $req_items ) : get_product_ids_from_bundle_items( $product->get_default_products() );
			$quantities = ! empty( $req_items ) ? get_quantities_from_bundle_items( $req_items ) : [];

			if ( empty( $ids ) ) {
				throw new \Exception( __( 'Please select a product for each of the required bundle items.', 'asnp-easy-product-bundles' ) );
			}

			if ( ! empty( $req_items ) && count( $ids ) !== count( $quantities ) ) {
				throw new \Exception( __( 'Invalid product bundle.', 'asnp-easy-product-bundles' ) );
			}

			$items = $product->get_items();
			if ( empty( $items ) ) {
				throw new \Exception( __( 'Bundle product is unavailable.', 'asnp-easy-product-bundles' ) );
			}

			if ( count( $ids ) !== count( $items ) ) {
				throw new \Exception( __( 'Please select a product for each of the required bundle items.', 'asnp-easy-product-bundles' ) );
			}

			for ( $i = 0; $i < count( $ids ); $i++ ) {
				$id   = (int) $ids[ $i ];
				$item = $items[ $i ];

				try {
					$item_is_valid = apply_filters( 'asnp_wepb_add_to_cart_validation_item_is_valid', true, $item, $id );
					if ( 'continue' === $item_is_valid ) {
						continue;
					} elseif ( ! $item_is_valid ) {
						return false;
					}
				} catch ( \Exception $e ) {
					throw $e;
				}

				$item_product = wc_get_product( $id );
				if ( ! $item_product ) {
					throw new \Exception( sprintf( __( 'Selected product for the bundle item %d is invalid.', 'asnp-easy-product-bundles' ), $i + 1 ) );
				}

				if ( post_password_required( $id ) ) {
					throw new \Exception( __( 'This product is protected and cannot be purchased.', 'asnp-easy-product-bundles' ) );
				}

				if ( ! $item_product->is_purchasable() ) {
					throw new \Exception( sprintf( __( 'Product &quot;%s&quot; is not purchasable.', 'asnp-easy-product-bundles' ), $item_product->get_name() ) );
				}

				$item_quantity = isset( $quantities[ $i ] ) ? absint( $quantities[ $i ] ) : ( ! empty( $item['quantity'] ) ? (int) $item['quantity'] : 0 );
				if ( empty( $item_quantity ) ) {
					throw new \Exception( sprintf( __( 'Please select a valid quantity for the bundle item &quot;%s&quot;.', 'asnp-easy-product-bundles' ), $item_product->get_name() ) );
				}

				$quantity = $item_quantity * $product_quantity;

				// Force quantity to 1 if sold individually and check for existing item in cart.
				if ( $item_product->is_sold_individually() ) {
					$quantity      = apply_filters( 'asnp_wepb_add_to_cart_sold_individually_quantity', 1, $quantity, $item_product );
					$found_in_cart = is_in_cart( $item_product );

					if ( $found_in_cart ) {
						/* translators: %s: product name */
						$message = sprintf( __( 'You cannot add another "%s" to your cart.', 'asnp-easy-product-bundles' ), $item_product->get_name() );

						/**
						 * Filters message about more than 1 product being added to cart.
						 *
						 * @param string     $message Message.
						 * @param WC_Product $item_product Product data.
						 */
						$message = apply_filters( 'asnp_wepb_cart_product_cannot_add_another_message', $message, $item_product );

						throw new \Exception( sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', wc_get_cart_url(), __( 'View cart', 'asnp-easy-product-bundles' ), $message ) );
					}
				} else {
					if ( ! empty( $item['edit_quantity'] ) && 'true' === $item['edit_quantity'] ) {
						if ( ! empty( $item['min_quantity'] ) && $item_quantity < (int) $item['min_quantity'] ) {
							throw new \Exception( sprintf( __( 'Please select a valid quantity for the bundle item &quot;%s&quot;.', 'asnp-easy-product-bundles' ), $item_product->get_name() ) );
						}
						if ( ! empty( $item['max_quantity'] ) && $item_quantity > (int) $item['max_quantity'] ) {
							throw new \Exception( sprintf( __( 'Please select a valid quantity for the bundle item &quot;%s&quot;.', 'asnp-easy-product-bundles' ), $item_product->get_name() ) );
						}
						if ( empty( $item['min_quantity'] ) && empty( $item['max_quantity'] ) && $item_quantity !== $item['quantity'] ) {
							throw new \Exception( sprintf( __( 'Please select a valid quantity for the bundle item &quot;%s&quot;.', 'asnp-easy-product-bundles' ), $item_product->get_name() ) );
						}
					} elseif ( $item_quantity !== (int) $item['quantity'] ) {
						throw new \Exception( sprintf( __( 'Please select a valid quantity for the bundle item &quot;%s&quot;.', 'asnp-easy-product-bundles' ), $item_product->get_name() ) );
					}
				}

				// Stock check - only check if we're managing stock and backorders are not allowed.
				if ( ! $item_product->is_in_stock() ) {
					/* translators: %s: product name */
					$message = sprintf( __( 'You cannot add &quot;%s&quot; to the bundle item because the product is out of stock.', 'asnp-easy-product-bundles' ), $item_product->get_name() );

					/**
					 * Filters message about product being out of stock.
					 *
					 * @since 1.0.0
					 * @param string     $message Message.
					 * @param WC_Product $item_product Product data.
					 */
					$message = apply_filters( 'asnp_wepb_cart_product_out_of_stock_message', $message, $item_product );
					throw new \Exception( $message );
				}

				if ( ! $item_product->has_enough_stock( $quantity ) ) {
					$stock_quantity = $item_product->get_stock_quantity();

					/* translators: 1: product name 2: quantity in stock */
					$message = sprintf( __( 'You cannot add that amount of &quot;%1$s&quot; to the cart because there is not enough stock (%2$s remaining).', 'asnp-easy-product-bundles' ), $item_product->get_name(), wc_format_stock_quantity_for_display( $stock_quantity, $item_product ) );

					/**
					 * Filters message about product not having enough stock.
					 *
					 * @since 4.5.0
					 * @param string     $message Message.
					 * @param WC_Product $item_product Product data.
					 * @param int        $stock_quantity Quantity remaining.
					 */
					$message = apply_filters( 'asnp_wepb_cart_product_not_enough_stock_message', $message, $item_product, $stock_quantity );

					throw new \Exception( $message );
				}

				if ( $item_product->managing_stock() ) {
					$products_qty_in_cart = WC()->cart->get_cart_item_quantities();

					if ( isset( $products_qty_in_cart[ $item_product->get_stock_managed_by_id() ] ) && ! $item_product->has_enough_stock( $products_qty_in_cart[ $item_product->get_stock_managed_by_id() ] + $quantity ) ) {
						$stock_quantity         = $item_product->get_stock_quantity();
						$stock_quantity_in_cart = $products_qty_in_cart[ $item_product->get_stock_managed_by_id() ];

						$message = sprintf(
							'<a href="%s" class="button wc-forward">%s</a> %s',
							wc_get_cart_url(),
							__( 'View cart', 'asnp-easy-product-bundles' ),
							/* translators: 1: quantity in stock 2: current quantity */
							sprintf( __( 'You cannot add that amount of &quot;%1$s&quot; to the cart &mdash; we have %2$s in stock and you already have %3$s in your cart.', 'asnp-easy-product-bundles' ), $item_product->get_name(), wc_format_stock_quantity_for_display( $stock_quantity, $item_product ), wc_format_stock_quantity_for_display( $stock_quantity_in_cart, $item_product ) )
						);

						/**
						 * Filters message about product not having enough stock accounting for what's already in the cart.
						 *
						 * @param string $message Message.
						 * @param WC_Product $item_product Product data.
						 * @param int $stock_quantity Quantity remaining.
						 * @param int $stock_quantity_in_cart
						 */
						$message = apply_filters( 'asnp_wepb_cart_product_not_enough_stock_already_in_cart_message', $message, $item_product, $stock_quantity, $stock_quantity_in_cart );

						throw new \Exception( $message );
					}
				}

				if ( ! ProductValidator::is_valid_product( $item_product, $item ) ) {
					throw new \Exception( sprintf( __( 'You cannot add &quot;%s&quot; to the bundle item because it is an invalid product.', 'asnp-easy-product-bundles' ), $item_product->get_name() ) );
				}
			}
		} catch ( \Exception $e ) {
			if ( $e->getMessage() ) {
				wc_add_notice( $e->getMessage(), 'error' );
			}
			return false;
		}

		return $passed;
	}

	public function update_cart_validation( $passed, $cart_item_key, $cart_item, $quantity ) {
		if ( ! $passed || empty( $cart_item ) ) {
			return $passed;
		}

		if ( is_cart_item_bundle( $cart_item ) ) {
			return $this->update_cart_validation_bundle( $passed, $cart_item, $quantity );
		}

		return $passed;
	}

	protected function update_cart_validation_bundle( $passed, $cart_item, $quantity ) {
		$items = isset( $cart_item['asnp_wepb_items'] ) ? $cart_item['asnp_wepb_items'] : '';
		if ( empty( $items ) ) {
			return $passed;
		}

		$ids           = get_product_ids_from_bundle_items( $items );
		$quantities    = get_quantities_from_bundle_items( $items );
		$product_items = $cart_item['data']->get_items();
		if ( empty( $ids ) || empty( $quantities ) || count( $ids ) !== count( $product_items ) ) {
			return false;
		}

		$i = 0;
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( ! is_cart_item_bundle_item( $item ) ) {
				continue;
			}

			// Is it item of the updated product.
			if (
				! isset( $item['asnp_wepb_parent_key'] ) ||
				$cart_item['key'] != $item['asnp_wepb_parent_key']
			) {
				continue;
			}

			$item_quantity = $quantity * $quantities[ $i ];

			if ( 1 < $item_quantity && $item['data']->is_sold_individually() ) {
				/* Translators: %s Product title. */
				wc_add_notice( sprintf( __( 'You can only have 1 %s in your cart.', 'asnp-easy-product-bundles' ), $item['data']->get_name() ), 'error' );
				return false;
			} else {
				if ( ! empty( $product_items[ $i ]['edit_quantity'] ) && 'true' === $product_items[ $i ]['edit_quantity'] ) {
					$min_quantity = ! empty( $product_items[ $i ]['min_quantity'] ) ? (int) $product_items[ $i ]['min_quantity'] * $quantity : '';
					$max_quantity = ! empty( $product_items[ $i ]['max_quantity'] ) ? (int) $product_items[ $i ]['max_quantity'] * $quantity : '';

					if ( $min_quantity && $item_quantity < $min_quantity ) {
						wc_add_notice( sprintf( __( 'Cart update failed. The quantity of &quot;%1$s&quot; must be at least %2$d.', 'asnp-easy-product-bundles' ), $item['data']->get_name(), $min_quantity ), 'error' );
						return false;
					}
					if ( $max_quantity && $item_quantity > $max_quantity ) {
						wc_add_notice( sprintf( __( 'Cart update failed. The quantity of &quot;%1$s&quot; cannot be higher than %2$d.', 'asnp-easy-product-bundles' ), $item['data']->get_name(), $max_quantity ), 'error' );
						return false;
					}
					if ( ! $min_quantity && ! $max_quantity && $item_quantity !== (int) $product_items[ $i ]['quantity'] * $quantity ) {
						wc_add_notice( sprintf( __( 'Cart update failed. The quantity of &quot;%1$s&quot; must be equal to %2$d.', 'asnp-easy-product-bundles' ), $item['data']->get_name(), (int) $product_items[ $i ]['quantity'] * $quantity ), 'error' );
						return false;
					}
				} elseif ( $item_quantity !== (int) $product_items[ $i ]['quantity'] * $quantity ) {
					wc_add_notice( sprintf( __( 'Cart update failed. The quantity of &quot;%1$s&quot; must be equal to %2$d.', 'asnp-easy-product-bundles' ), $item['data']->get_name(), (int) $product_items[ $i ]['quantity'] * $quantity ), 'error' );
					return false;
				}
			}

			if ( ! $item['data']->has_enough_stock( $item_quantity ) ) {
				$stock_quantity = $item['data']->get_stock_quantity();
				/* translators: 1: product name 2: quantity in stock */
				wc_add_notice( sprintf( __( 'You cannot add that amount of &quot;%1$s&quot; to the cart because there is not enough stock (%2$s remaining).', 'asnp-easy-product-bundles' ), $item['data']->get_name(), wc_format_stock_quantity_for_display( $stock_quantity, $item['data'] ) ), 'error' );
				return false;
			}

			++$i;
		}

		return $passed;
	}

	public function add_cart_item_data( $cart_item_data, $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return $cart_item_data;
		}

		$items     = $product->get_items();
		$req_items = ! empty( $_REQUEST['asnp_wepb_items'] ) ? explode( ',', sanitize_text_field( $_REQUEST['asnp_wepb_items'] ) ) : '';
		$ids       = ! empty( $req_items ) ? get_product_ids_from_bundle_items( $req_items ) : get_product_ids_from_bundle_items( $product->get_default_products() );
		if ( empty( $ids ) || count( $ids ) !== count( $items ) ) {
			return $cart_item_data;
		}

		if ( ! empty( $_REQUEST['asnp_wepb_items'] ) ) {
			$cart_item_data[ self::CART_ITEM_ITEMS ] = sanitize_text_field( $_REQUEST['asnp_wepb_items'] );
		} else {
			$cart_item_data[ self::CART_ITEM_ITEMS ] = $product->get_default_products();
		}

		return $cart_item_data;
	}

	public function add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		if ( empty( $cart_item_data[ self::CART_ITEM_ITEMS ] ) ) {
			return;
		}

		// It is required to get valid prices for the bundle using get_price methods inside the cart.
		$cart_content = WC()->cart->get_cart();
		$cart_content[ $cart_item_key ]['data']->set_is_cart_item( true );

		$this->add_bundle_items_to_cart( $cart_item_key, $product_id, $quantity, $cart_item_data[ self::CART_ITEM_ITEMS ] );
	}

	protected function add_bundle_items_to_cart( $cart_item_key, $product_id, $quantity, $bundle_items, $cart = null ) {
		if ( empty( $bundle_items ) ) {
			return false;
		}

		$cart       = $cart && is_a( $cart, 'WC_Cart' ) ? $cart : WC()->cart;
		$items      = explode( ',', $bundle_items );
		$ids        = ! empty( $items ) ? get_product_ids_from_bundle_items( $items ) : [];
		$quantities = ! empty( $items ) ? get_quantities_from_bundle_items( $items ) : [];
		$attributes = ! empty( $items ) ? get_attributes_from_bundle_items( $items ) : [];
		if ( empty( $ids ) || empty( $quantities ) || count( $ids ) !== count( $quantities ) ) {
			$this->remove_bundle_from_cart( $cart_item_key, $cart );
			return false;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return false;
		}

		$items = $product->get_items();
		if ( count( $ids ) !== count( $items ) ) {
			$this->remove_bundle_from_cart( $cart_item_key, $cart );
			return false;
		}

		$is_fixed_price = $cart->cart_contents[ $cart_item_key ]['data']->is_fixed_price();
		$hide_price     = $cart->cart_contents[ $cart_item_key ]['data']->get_hide_items_price();

		$cart->cart_contents[ $cart_item_key ]['asnp_wepb_key']            = $cart_item_key;
		$cart->cart_contents[ $cart_item_key ]['asnp_wepb_is_fixed_price'] = $is_fixed_price;
		$cart->cart_contents[ $cart_item_key ]['asnp_wepb_hide_price']     = ! empty( $hide_price ) ? sanitize_text_field( $hide_price ) : 'no';

		$cart_keys = [];
		for ( $i = 0; $i < count( $ids ); $i++ ) {
			if ( 0 >= $ids[ $i ] || 0 >= $quantities[ $i ] ) {
				continue;
			}

			$cart_key = $this->add_bundle_item_to_cart( [
				'index'          => $i,
				'item'           => $items[ $i ],
				'cart_item_key'  => $cart_item_key,
				'product_id'     => $product_id,
				'quantity'       => $quantity,
				'item_id'        => (int) $ids[ $i ],
				'item_quantity'  => (int) $quantities[ $i ],
				'variations'     => isset( $attributes[ $i ] ) ? $attributes[ $i ] : [],
				'is_fixed_price' => $is_fixed_price,
				'hide_price'     => $hide_price,
			], $cart );

			if ( empty( $cart_key ) ) {
				$this->remove_bundle_from_cart( $cart_item_key, $cart );
				return false;
			}

			// Search in array is required to make sure that the cart_key is not already in the item.
			if ( ! in_array( $cart_key, $cart_keys ) ) {
				$cart_keys[] = $cart_key;
			}
		}

		if ( ! empty( $cart_keys ) ) {
			$cart->cart_contents[ $cart_item_key ][ self::CART_ITEM_ITEMS_KEY ] = $cart_keys;
			return true;
		}

		return false;
	}

	protected function add_bundle_item_to_cart( array $args, $cart = null ) {
		if ( empty( $args ) ) {
			return false;
		}

		$cart = $cart && is_a( $cart, 'WC_Cart' ) ? $cart : WC()->cart;

		$product = wc_get_product( (int) $args['item_id'] );
		if ( ! $product || ! $product->is_purchasable() ) {
			return false;
		}

		$product_id   = (int) $args['item_id'];
		$variation_id = 0;
		$variation    = [];
		$quantity     = $args['quantity'] * $args['item_quantity'];

		if ( $product->is_type( 'variation' ) ) {
			$variation_id = $product_id;
			$product_id   = $product->get_parent_id();
			$variation    = ! empty( $args['variations'] ) ? $args['variations'] : [];
		}

		$price = apply_filters(
			'asnp_wepb_bundle_item_cart_item_price',
			get_bundle_item_price(
				$product,
				[
					'discount_type'  => ! empty( $args['item']['discount_type'] ) ? $args['item']['discount_type'] : '',
					'discount'       => isset( $args['item']['discount'] ) && '' !== $args['item']['discount'] ? (float) $args['item']['discount'] : null,
					'exchange_price' => false,
				]
			),
			$product, $args
		);

		$cart_data = apply_filters( 'asnp_wepb_bundle_item_cart_item_data', [
			'asnp_wepb_item_index'            => (int) $args['index'],
			'asnp_wepb_parent_id'             => (int) $args['product_id'],
			'asnp_wepb_parent_key'            => $args['cart_item_key'],
			'asnp_wepb_parent_is_fixed_price' => isset( $args['is_fixed_price'] ) ? $args['is_fixed_price'] : false,
			'asnp_wepb_price'                 => $price,
			'asnp_wepb_reg_price'             => $product->get_regular_price( 'edit' ),
			'asnp_wepb_item_quantity'         => $args['item_quantity'],
			'asnp_wepb_hide_price'            => ! empty( $args['hide_price'] ) ? sanitize_text_field( $args['hide_price'] ) : 'no',
		], $product, $args );

		$cart_key = $cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_data );

		return ! empty( $cart_key ) ? $cart_key : false;
	}

	public function get_cart_item_from_session( $cart_item, $session_values ) {
		$keys = [
			static::CART_ITEM_ITEMS,
			static::CART_ITEM_ITEMS_KEY,
			'asnp_wepb_is_fixed_price',
			'asnp_wepb_item_index',
			'asnp_wepb_parent_id',
			'asnp_wepb_parent_key',
			'asnp_wepb_parent_is_fixed_price',
			'asnp_wepb_price',
			'asnp_wepb_reg_price',
			'asnp_wepb_item_quantity',
			'asnp_wepb_hide_price',
		];

		foreach ( $keys as $key ) {
			if ( isset( $session_values[ $key ] ) && ! isset( $cart_item[ $key ] ) ) {
				$cart_item[ $key ] = $session_values[ $key ];
			}
		}

		return $cart_item;
	}

	public function before_calculate_totals( $cart = null ) {
		if ( ! defined( 'DOING_AJAX' ) && is_admin() ) {
			return;
		}

		$cart = $cart && is_a( $cart, 'WC_Cart' ) ? $cart : WC()->cart;
		if ( $cart->is_empty() ) {
			return;
		}

		$cart_contents = $cart->get_cart();

		// Find updated keys.
		$old_new_keys = [];
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( ! empty( $cart_item['asnp_wepb_key' ] ) ) {
				$old_new_keys[ $cart_item['asnp_wepb_key' ] ] = $cart_item_key;
			}
		}

		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			// Bundle item.
			if ( is_cart_item_bundle_item( $cart_item ) ) {
				$parent_key = isset( $cart_item['asnp_wepb_parent_key'], $old_new_keys[ $cart_item['asnp_wepb_parent_key'] ] ) ? $old_new_keys[ $cart_item['asnp_wepb_parent_key'] ] : null;

				if (
					! $parent_key || ! isset( $cart_contents[ $parent_key ] ) ||
					( isset( $cart_contents[ $parent_key ][ static::CART_ITEM_ITEMS_KEY ] ) && ! in_array( $cart_item_key, $cart_contents[ $parent_key ][ static::CART_ITEM_ITEMS_KEY ] ) )
				) {
					unset( $cart_contents[ $cart_item_key ] );
					continue;
				}

				if ( ! empty( $cart_item['asnp_wepb_item_quantity'] ) ) {
					WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = $cart_item['asnp_wepb_item_quantity'] * $cart_contents[ $parent_key ]['quantity'];
				}

				if ( isset( $cart_contents[ $parent_key ]['asnp_wepb_is_fixed_price'] ) && $cart_contents[ $parent_key ]['asnp_wepb_is_fixed_price'] ) {
					$cart_item['data']->set_price( 0 );
					if ( isset( $cart_item['_wccs_discounted_price'] ) ) {
						WC()->cart->cart_contents[ $cart_item_key ]['asnp_wepb_price'] = $cart_item['_wccs_discounted_price'];
					}
				} elseif ( isset( $cart_item['asnp_wepb_price'] ) ) {
					if ( isset( $cart_item['_wccs_discounted_price'] ) ) {
						WC()->cart->cart_contents[ $cart_item_key ]['asnp_wepb_price'] = $cart_item['data']->get_price( 'edit' );
					} else {
						$cart_item['data']->set_price( (float) $cart_item['asnp_wepb_price'] );
					}
				}
			} // Bundle product.
			elseif ( is_cart_item_bundle( $cart_item ) ) {
				if ( empty( $cart_item[ self::CART_ITEM_ITEMS_KEY ] ) ) {
					continue;
				}

				if ( ! isset( $cart_item['asnp_wepb_is_fixed_price'] ) || ! $cart_item['asnp_wepb_is_fixed_price'] ) {
					if ( 'true' !== $cart_item['data']->get_include_parent_price() ) {
						$cart_item['data']->set_price( 0 );
					}

					// Calculate and save the bundle price.
					$bundle_price = $this->calculate_bundle_price( $cart_item );
					if ( isset( $bundle_price['price'] ) ) {
						WC()->cart->cart_contents[ $cart_item_key ]['asnp_wepb_price'] = $bundle_price['price'];
					}
				}
			}
		}
	}

	public function before_mini_cart_contents() {
		$cart_contents = WC()->cart->get_cart();
		foreach ( $cart_contents as $cart_item ) {
			if ( is_cart_item_bundle( $cart_item ) || is_cart_item_bundle_item( $cart_item ) ) {
				return WC()->cart->calculate_totals();
			}
		}
	}

	public function cart_item_remove_link( $link, $cart_item_key ) {
		if (
			! empty( $cart_item_key ) &&
			isset( WC()->cart->cart_contents[ $cart_item_key ] ) &&
			is_cart_item_bundle_item( WC()->cart->cart_contents[ $cart_item_key ] )
		) {
			$cart_item = WC()->cart->cart_contents[ $cart_item_key ];
			if ( isset( WC()->cart->cart_contents[ $cart_item['asnp_wepb_parent_key'] ] ) ) {
				return '';
			}
		}

		return $link;
	}

	public function cart_item_name( $content, $cart_item ) {
		if ( ! is_cart_item_bundle_item( $cart_item ) ) {
			return $content;
		}

		$parent_id = maybe_get_exact_product_id( $cart_item['asnp_wepb_parent_id'] );

		if ( false === strpos( $content, '</a>' ) ) {
			$content = get_the_title( $parent_id ) . ' &rarr; ' . $content;
		} else {
			$content = '<a href="' . get_permalink( $parent_id ) . '">' . get_the_title( $cart_item[ 'asnp_wepb_parent_id' ] ) . '</a> &rarr; ' . $content;
		}

		return apply_filters( 'asnp_wepb_cart_item_name', $content, $cart_item );
	}

	public function cart_item_quantity( $quantity, $cart_item_key, $cart_item ) {
		if ( ! is_cart_item_bundle_item( $cart_item ) ) {
			return $quantity;
		}

		return $cart_item['quantity'];
	}

	public function cart_item_price( $price, $cart_item ) {
		if ( is_cart_item_bundle( $cart_item ) ) {
			return $this->cart_item_price_bundle( $price, $cart_item );
		}

		if ( ! is_cart_item_bundle_item( $cart_item ) ) {
			return $price;
		}

		if ( isset( $cart_item['asnp_wepb_price'] ) ) {
			if ( Cart\display_prices_including_tax() ) {
				$price = wc_get_price_including_tax( $cart_item['data'], [ 'price' => maybe_exchange_price( $cart_item['asnp_wepb_price'] ) ] );
			} else {
				$price = wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => maybe_exchange_price( $cart_item['asnp_wepb_price'] ) ] );
			}

			if (
				isset( $cart_item['asnp_wepb_reg_price'] ) &&
				(float) $cart_item['asnp_wepb_reg_price'] > (float) $cart_item['asnp_wepb_price']
			) {
				if ( Cart\display_prices_including_tax() ) {
					$regular_price = wc_get_price_including_tax( $cart_item['data'], [ 'price' => maybe_exchange_price( $cart_item['asnp_wepb_reg_price'] ) ] );
				} else {
					$regular_price = wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => maybe_exchange_price( $cart_item['asnp_wepb_reg_price'] ) ] );
				}

				return '<del>' . wc_price( $regular_price ) . '</del> <ins>' . wc_price( $price ) . '</ins>';
			}
			return wc_price( $price );
		}

		return $price;
	}

	protected function cart_item_price_bundle( $price, $cart_item ) {
		if ( empty( $cart_item[ self::CART_ITEM_ITEMS_KEY ] ) ) {
			return $price;
		}

		$cart_contents = WC()->cart->get_cart();

		if ( isset( $cart_item['asnp_wepb_is_fixed_price'] ) && $cart_item['asnp_wepb_is_fixed_price'] ) {
			if (
				is_pro_active() &&
				'only_regular_price' === $cart_item['data']->get_hide_items_price()
			) {
				return $price;
			}

			$sale_price    = Cart\display_prices_including_tax() ?
				wc_get_price_including_tax( $cart_item['data'], [ 'price' => maybe_exchange_price( (float) $cart_item['data']->get_price( 'edit' ) ) ] ) :
				wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => maybe_exchange_price( (float) $cart_item['data']->get_price( 'edit' ) ) ] );
			$regular_price = 0;

			if ( 'true' === $cart_item['data']->get_include_parent_price() ) {
				if ( '' !== $cart_item['data']->get_regular_price( 'edit' ) ) {
					$regular_price = Cart\display_prices_including_tax() ?
						wc_get_price_including_tax( $cart_item['data'], [ 'price' => maybe_exchange_price( (float) $cart_item['data']->get_regular_price( 'edit' ) ) ] ) :
						wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => maybe_exchange_price( (float) $cart_item['data']->get_regular_price( 'edit' ) ) ] );
				}
			}

			foreach ( $cart_item[ self::CART_ITEM_ITEMS_KEY ] as $item_key ) {
				if ( ! isset( $cart_contents[ $item_key ] ) ) {
					return $price;
				}

				if ( ! empty( $cart_contents[ $item_key ]['asnp_wepb_reg_price'] ) ) {
					$regular_price += Cart\display_prices_including_tax() ?
						wc_get_price_including_tax( $cart_contents[ $item_key ]['data'], [ 'price' => maybe_exchange_price( (float) $cart_contents[ $item_key ]['asnp_wepb_reg_price'] ), 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] ) :
						wc_get_price_excluding_tax( $cart_contents[ $item_key ]['data'], [ 'price' => maybe_exchange_price( (float) $cart_contents[ $item_key ]['asnp_wepb_reg_price'] ), 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] );
				}
			}

			if ( $regular_price > $sale_price ) {
				return '<del>' . wc_price( $regular_price ) . '</del> <ins>' . $price . '</ins>';
			}

			return $price;
		}

		$show_regular = true;
		if ( is_pro_active() ) {
			if (
				'yes' === $cart_item['data']->get_hide_items_price() ||
				'only_regular_price' === $cart_item['data']->get_hide_items_price()
			) {
				$show_regular = false;
			}
		}

		$bundle_price = $this->calculate_bundle_price( $cart_item, true );
		if ( empty( $bundle_price ) ) {
			return $price;
		}

		if ( $show_regular && $bundle_price['regular_price'] > $bundle_price['price'] ) {
			return '<del>' . wc_price( $bundle_price['regular_price'] ) . '</del> <ins>' . wc_price( $bundle_price['price'] ) . '</ins>';
		}

		return wc_price( $bundle_price['price'] );
	}

	protected function calculate_bundle_price( $cart_item, $exchange_price = false ) {
		$cart_contents = WC()->cart->get_cart();
		$regular_price = 0;
		$price         = 0;

		if ( 'true' === $cart_item['data']->get_include_parent_price() ) {
			if ( '' !== $cart_item['data']->get_price( 'edit' ) ) {
				$price = Cart\display_prices_including_tax() ?
					wc_get_price_including_tax( $cart_item['data'], [ 'price' => $exchange_price ? maybe_exchange_price( (float) $cart_item['data']->get_price( 'edit' ) ) : (float) $cart_item['data']->get_price( 'edit' ) ] ) :
					wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => $exchange_price ? maybe_exchange_price( (float) $cart_item['data']->get_price( 'edit' ) ) : (float) $cart_item['data']->get_price( 'edit' ) ] );
			}

			if ( '' !== $cart_item['data']->get_regular_price( 'edit' ) ) {
				$regular_price = Cart\display_prices_including_tax() ?
					wc_get_price_including_tax( $cart_item['data'], [ 'price' => $exchange_price ? maybe_exchange_price( (float) $cart_item['data']->get_regular_price( 'edit' ) ) : (float) $cart_item['data']->get_regular_price( 'edit' ) ] ) :
					wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => $exchange_price ? maybe_exchange_price( (float) $cart_item['data']->get_regular_price( 'edit' ) ) : (float) $cart_item['data']->get_regular_price( 'edit' ) ] );
			}
		}

		foreach ( $cart_item[ self::CART_ITEM_ITEMS_KEY ] as $item_key ) {
			if ( ! isset( $cart_contents[ $item_key ] ) ) {
				return [];
			}

			if ( ! empty( $cart_contents[ $item_key ]['asnp_wepb_price'] ) ) {
				$price += Cart\display_prices_including_tax() ?
					wc_get_price_including_tax( $cart_contents[ $item_key ]['data'], [ 'price' => $exchange_price ? maybe_exchange_price( (float) $cart_contents[ $item_key ]['asnp_wepb_price'] ) : (float) $cart_contents[ $item_key ]['asnp_wepb_price'], 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] ) :
					wc_get_price_excluding_tax( $cart_contents[ $item_key ]['data'], [ 'price' => $exchange_price ? maybe_exchange_price( (float) $cart_contents[ $item_key ]['asnp_wepb_price'] ) : (float) $cart_contents[ $item_key ]['asnp_wepb_price'], 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] );
			}

			if ( ! empty( $cart_contents[ $item_key ]['asnp_wepb_reg_price'] ) ) {
				$regular_price += Cart\display_prices_including_tax() ?
					wc_get_price_including_tax( $cart_contents[ $item_key ]['data'], [ 'price' => $exchange_price ? maybe_exchange_price( (float) $cart_contents[ $item_key ]['asnp_wepb_reg_price'] ) : (float) $cart_contents[ $item_key ]['asnp_wepb_reg_price'], 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] ) :
					wc_get_price_excluding_tax( $cart_contents[ $item_key ]['data'], [ 'price' => $exchange_price ? maybe_exchange_price( (float) $cart_contents[ $item_key ]['asnp_wepb_reg_price'] ) : (float) $cart_contents[ $item_key ]['asnp_wepb_reg_price'], 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] );
			}
		}

		return [ 'regular_price' => $regular_price, 'price' => $price ];
	}

	public function cart_item_subtotal( $subtotal, $cart_item ) {
		if ( is_cart_item_bundle( $cart_item ) ) {
			return $this->cart_item_subtotal_bundle( $subtotal, $cart_item );
		}

		if ( ! is_cart_item_bundle_item( $cart_item ) ) {
			return $subtotal;
		}

		if ( isset( $cart_item['asnp_wepb_price'] ) ) {
			if ( Cart\display_prices_including_tax() ) {
				$price = wc_get_price_including_tax( $cart_item['data'], [ 'price' => maybe_exchange_price( $cart_item['asnp_wepb_price'] ) ] );
			} else {
				$price = wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => maybe_exchange_price( $cart_item['asnp_wepb_price'] ) ] );
			}

			$subtotal = wc_price( $price * $cart_item['quantity'] );
			if ( $cart_item['data']->is_taxable() ) {
				if ( Cart\display_prices_including_tax() ) {
					if ( ! wc_prices_include_tax() && 0 < WC()->cart->get_subtotal_tax() ) {
						$subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
					}
				} elseif ( wc_prices_include_tax() && 0 < WC()->cart->get_subtotal_tax() ) {
					$subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
				}
			}
		}

		return $subtotal;
	}

	protected function cart_item_subtotal_bundle( $subtotal, $cart_item ) {
		if ( isset( $cart_item['asnp_wepb_is_fixed_price'] ) && $cart_item['asnp_wepb_is_fixed_price'] ) {
			return $subtotal;
		}

		if ( empty( $cart_item[ self::CART_ITEM_ITEMS_KEY ] ) ) {
			return $subtotal;
		}

		$cart_contents = WC()->cart->get_cart();

		$price = 0;
		if ( 'true' === $cart_item['data']->get_include_parent_price() ) {
			if ( '' !== $cart_item['data']->get_price( 'edit' ) ) {
				$price = Cart\display_prices_including_tax() ?
					wc_get_price_including_tax( $cart_item['data'], [ 'price' => maybe_exchange_price( (float) $cart_item['data']->get_price( 'edit' ) ) ] ) :
					wc_get_price_excluding_tax( $cart_item['data'], [ 'price' => maybe_exchange_price( (float) $cart_item['data']->get_price( 'edit' ) ) ] );
			}
		}

		foreach ( $cart_item[ self::CART_ITEM_ITEMS_KEY ] as $item_key ) {
			if ( ! isset( $cart_contents[ $item_key ] ) ) {
				return $subtotal;
			}

			if ( isset( $cart_contents[ $item_key ]['asnp_wepb_price'] ) ) {
				$price += Cart\display_prices_including_tax() ?
					wc_get_price_including_tax( $cart_contents[ $item_key ]['data'], [ 'price' => maybe_exchange_price( (float) $cart_contents[ $item_key ]['asnp_wepb_price'] ), 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] ) :
					wc_get_price_excluding_tax( $cart_contents[ $item_key ]['data'], [ 'price' => maybe_exchange_price( (float) $cart_contents[ $item_key ]['asnp_wepb_price'] ), 'qty' => $cart_contents[ $item_key ]['asnp_wepb_item_quantity'] ] );
			}
		}

		$subtotal = wc_price( $price * $cart_item['quantity'] );

		if ( $cart_item['data']->is_taxable() ) {
			if ( Cart\display_prices_including_tax() ) {
				if ( ! wc_prices_include_tax() && 0 < WC()->cart->get_subtotal_tax() ) {
					$subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
				}
			} elseif ( wc_prices_include_tax() && 0 < WC()->cart->get_subtotal_tax() ) {
				$subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
			}
		}

		return $subtotal;
	}

	public function cart_item_removed( $cart_item_key, $cart ) {
		if ( empty( $cart->removed_cart_contents[ $cart_item_key ][ self::CART_ITEM_ITEMS_KEY ] ) ) {
			return;
		}

		foreach ( $cart->removed_cart_contents[ $cart_item_key ][ self::CART_ITEM_ITEMS_KEY ] as $item_key ) {
			$cart->remove_cart_item( $item_key );
		}
	}

	public function cart_item_restored( $cart_item_key, $cart ) {
		if ( empty( $cart->cart_contents[ $cart_item_key ][ self::CART_ITEM_ITEMS_KEY ] ) ) {
			return;
		}

		foreach ( $cart->cart_contents[ $cart_item_key ][ self::CART_ITEM_ITEMS_KEY ] as $item_key ) {
			$cart->restore_cart_item( $item_key );
		}
	}

	public function cart_contents_count( $count ) {
		$count_option = get_plugin()->settings->get_setting( 'cart_contents_count', 'bundle' );
		if ( 'both' === $count_option ) {
			return apply_filters( 'asnp_wepb_cart_contents_count', $count );
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( 'bundle' === $count_option && is_cart_item_bundle_item( $cart_item ) ) {
				$count -= $cart_item['quantity'];
			} elseif ( 'bundle_items' === $count_option && is_cart_item_bundle( $cart_item ) ) {
				$count -= $cart_item['quantity'];
			}
		}

		return apply_filters( 'asnp_wepb_cart_contents_count', $count );
	}

	public function get_item_count( $count, $type, $order ) {
		$count_option = get_plugin()->settings->get_setting( 'cart_contents_count', 'bundle' );
		if ( 'both' === $count_option ) {
			return apply_filters( 'asnp_wepb_get_item_count', $count );
		}

		foreach ( $order->get_items( $type ) as $item ) {
			if ( 'bundle' === $count_option && $item->get_meta( '_asnp_wepb_parent_id' ) ) {
				$count -= $item['quantity'];
			} elseif ( 'bundle_items' === $count_option && $item->get_meta( '_asnp_wepb_items' ) ) {
				$count -= $item['quantity'];
			}
		}

		return apply_filters( 'asnp_wepb_get_item_count', $count );
	}

	public function cart_item_class( $class, $cart_item ) {
		if ( is_cart_item_bundle_item( $cart_item ) ) {
			$class .= ' asnp-wepb-cart-item asnp-wepb-cart-bundle-item';
		} elseif ( is_cart_item_bundle( $cart_item ) ) {
			$class .= ' asnp-wepb-cart-item asnp-wepb-cart-bundle';
		}

		return $class;
	}

	public function checkout_create_order_line_item( $order_item, $cart_item_key, $values ) {
		if ( is_cart_item_bundle_item( $values ) ) {
			$order_item->add_meta_data( '_asnp_wepb_parent_id', $values['asnp_wepb_parent_id'] );
			if ( isset( $values['asnp_wepb_parent_is_fixed_price'] ) ) {
				$order_item->add_meta_data( '_asnp_wepb_parent_is_fixed_price', $values['asnp_wepb_parent_is_fixed_price'] );
			}
		} elseif ( is_cart_item_bundle( $values ) ) {
			$order_item->add_meta_data( '_asnp_wepb_items', $values[ self::CART_ITEM_ITEMS ] );
			if ( isset( $values['asnp_wepb_is_fixed_price'] ) ) {
				$order_item->add_meta_data( '_asnp_wepb_is_fixed_price', $values['asnp_wepb_is_fixed_price'] );
			}
		}

		if ( isset( $values['asnp_wepb_price'] ) ) {
			$order_item->add_meta_data( '_asnp_wepb_price', $values['asnp_wepb_price'] );
		}

		if ( isset( $values['asnp_wepb_hide_price'] ) ) {
			$order_item->add_meta_data( '_asnp_wepb_hide_price', $values['asnp_wepb_hide_price'] );
		}
	}

	public function before_order_item_meta( $order_item_id, $order_item ) {
		if ( isset( $order_item['_asnp_wepb_parent_id'] ) ) {
			$content = sprintf( esc_html__( '(Bundled in %s)', 'asnp-easy-product-bundles' ), get_the_title( $order_item['_asnp_wepb_parent_id'] ) );
			$content = apply_filters( 'asnp_wepb_before_order_item_meta', $content, $order_item_id, $order_item );
			echo $content;
		}
	}

	public function hidden_order_itemmeta( $items ) {
		return array_merge( $items, [
			'_asnp_wepb_items',
			'_asnp_wepb_parent_id',
			'_asnp_wepb_price',
			'_asnp_wepb_parent_is_fixed_price',
			'_asnp_wepb_hide_price',
			'_asnp_wepb_is_fixed_price',
			'asnp_wepb_items',
			'asnp_wepb_parent_id',
			'asnp_wepb_price',
			'asnp_wepb_parent_is_fixed_price',
			'asnp_wepb_hide_price',
			'asnp_wepb_is_fixed_price',
		] );
	}

	public function formatted_line_subtotal( $subtotal, $order_item ) {
		if ( isset( $order_item['_asnp_wepb_parent_id'] ) ) {
			if (
				isset( $order_item['_asnp_wepb_parent_is_fixed_price'] ) &&
				wc_string_to_bool( $order_item['_asnp_wepb_parent_is_fixed_price'] )
			) {
				return '';
			}

			if ( is_pro_active() ) {
				if (
					isset( $order_item['_asnp_wepb_hide_price'] ) &&
					'yes' === $order_item['_asnp_wepb_hide_price']
				) {
					return '';
				}
			}
		} elseif ( isset( $order_item['_asnp_wepb_items'] ) ) {
			if (
				! isset( $order_item['_asnp_wepb_is_fixed_price'] ) ||
				! wc_string_to_bool( $order_item['_asnp_wepb_is_fixed_price'] )
			) {
				if ( is_pro_active() ) {
					if (
						isset( $order_item['_asnp_wepb_hide_price'] ) &&
						'yes' !== $order_item['_asnp_wepb_hide_price']
					) {
						return '';
					}
				}
			}
		}

		if ( ! isset( $order_item['_asnp_wepb_price'] ) ) {
			return $subtotal;
		}

		return wc_price( maybe_exchange_price( $order_item['_asnp_wepb_price'] ) * $order_item['quantity'] );
	}

	public function ajax_add_order_item_meta( $item_id, $item, $order) {
		if ( 'line_item' !== $item->get_type() ) {
			return;
		}

		$product = $item->get_product();
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return;
		}

		try {
			$items = $product->get_items();
			if ( empty( $items ) ) {
				throw new \Exception( __( 'Invalid bundle product.', 'asnp-easy-product-bundles' ) );
			}

			$default_products = $product->get_default_products();
			if ( empty( $default_products ) ) {
				throw new \Exception( __( 'Bundle product has not default items to add it to the order.', 'asnp-easy-product-bundles' ) );
			}

			$ids = get_product_ids_from_bundle_items( $default_products );
			if ( empty( $ids ) ) {
				throw new \Exception( __( 'Bundle product has not default items to add it to the order.', 'asnp-easy-product-bundles' ) );
			} elseif ( count( $ids ) !== count( $items ) ) {
				throw new \Exception( __( 'Invalid bundle product.', 'asnp-easy-product-bundles' ) );
			}

			$is_fixed_price = $product->is_fixed_price();
			$quantity       = $item->get_quantity();
			$order_items    = [];
			$price          = 0;

			if ( ! $is_fixed_price && 'true' === $product->get_include_parent_price() ) {
				$price = '' !== $product->get_price( 'edit' ) ? (float) $product->get_price( 'edit' ) : 0;
			}

			for ( $i = 0; $i < count( $ids ); $i++ ) {
				if ( empty( $ids[ $i ] ) || 0 >= (int) $ids[ $i ] ) {
					continue;
				}

				$item_product = wc_get_product( $ids[ $i ] );
				if ( ! $item_product || ! is_allowed_bundle_item_type( $item_product->get_type() ) ) {
					continue;
				}

				if ( ! $is_fixed_price ) {
					$item_price = get_bundle_item_price(
						$item_product,
						[
							'discount_type'  => ! empty( $items[ $i ]['discount_type'] ) ? $items[ $i ]['discount_type'] : '',
							'discount'       => isset( $items[ $i ]['discount'] ) && '' !== $items[ $i ]['discount'] ? (float) $items[ $i ]['discount'] : null,
							'is_fixed_price' => $is_fixed_price,
						]
					);
					$item_price = wc_get_price_to_display( $item_product, [ 'price' => $item_price, 'display_context' => 'cart' ] );
					$price += $item_price;
				}

				$order_items[] = [ $item_product, absint( $items[ $i ]['quantity'] ) * $quantity ];
				$item_product->set_price( 0 );
			}

			if ( ! $is_fixed_price ) {
				$product->set_price( $price );
			}

			$order_item_id = $order->add_product( $product, $quantity );
			if ( ! $order_item_id ) {
				throw new \Exception( __( 'Can not add bundle product to the order.', 'asnp-easy-product-bundles' ) );
			}

			$order_item = $order->get_item( $order_item_id );
			$order_item->update_meta_data( '_asnp_wepb_items', $default_products, true );
			$order_item->save();

			foreach ( $order_items as $values ) {
				$order_item_id = $order->add_product( $values[0], $values[1] );
				if ( ! $order_item_id ) {
					throw new \Exception( __( 'Can not add bundle item to the order.', 'asnp-easy-product-bundles' ) );
				}

				$order_item = $order->get_item( $order_item_id );
				$order_item->update_meta_data( '_asnp_wepb_parent_id', $product->get_id(), true );
				$order_item->save();
			}

			// Remove old bundle product.
			$order->remove_item( $item_id );

			$order->save();
		} catch ( \Exception $e ) {
			// Remove old bundle product.
			$order->remove_item( $item_id );
			$order->save();
			throw $e;
		}
	}

	public function coupon_is_valid_for_product( $valid, $product, $coupon, $cart_item ) {
		if ( ! $valid ) {
			return $valid;
		}

		if ( ! is_cart_item_bundle_item( $cart_item ) && ! is_cart_item_bundle( $cart_item ) ) {
			return $valid;
		}

		$apply_coupon = get_plugin()->settings->get_setting( 'apply_coupon', 'both' );

		switch ( $apply_coupon ) {
			case 'both':
				return $valid;

			case 'none':
				return false;

			case 'bundle':
				return is_cart_item_bundle( $cart_item );

			case 'bundle_item':
				return is_cart_item_bundle_item( $cart_item );
		}

		return $valid;
	}

	public function cart_contents_weight( $weight ) {
		$weight = 0.0;
		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			if ( ! $values['data']->has_weight() ) {
				continue;
			}

			if ( is_cart_item_bundle_item( $values ) ) {
				$bundle = wc_get_product( (int) $values['asnp_wepb_parent_id'] );
				if (
					! $bundle ||
					'per_item' !== $bundle->get_shipping_fee_calculation()
				) {
					continue;
				}
			} elseif ( is_cart_item_bundle( $values ) ) {
				if ( 'per_bundle' !== $values['data']->get_shipping_fee_calculation() ) {
					continue;
				}
			}

			$weight += (float) $values['data']->get_weight() * $values['quantity'];
		}
		return $weight;
	}

	public function cart_shipping_packages( $packages ) {
		if ( empty( $packages  ) ) {
			return $packages;
		}

		foreach ( $packages as $key => $value ) {
			if ( empty( $value['contents'] ) ) {
				continue;
			}

			foreach ( $value['contents'] as $item_key => $item_value ) {
				if ( is_cart_item_bundle_item( $item_value ) ) {
					$bundle = wc_get_product( (int) $item_value['asnp_wepb_parent_id'] );
					if (
						$bundle &&
						$bundle->is_type( Plugin::PRODUCT_TYPE ) &&
						'per_item' !== $bundle->get_shipping_fee_calculation()
					) {
						unset( $packages[ $key ]['contents'][ $item_key ] );
					}
				} elseif ( is_cart_item_bundle( $item_value ) ) {
					if ( 'per_bundle' !== $item_value['data']->get_shipping_fee_calculation() ) {
						unset( $packages[ $key ]['contents'][ $item_key ] );
					}
				}
			}
		}

		return $packages;
	}

	public function loop_add_to_cart_link( $link, $product ) {
		if (
			! $product->is_type( Plugin::PRODUCT_TYPE ) ||
			! empty( $product->get_default_products() )
		) {
			return $link;
		}

		return str_replace( 'ajax_add_to_cart', '', $link );
	}

	public function order_again_cart_item_data( $data, $item ) {
		if ( $parent = $item->get_meta( '_asnp_wepb_parent_id' ) ) {
			$data['asnp_wepb_parent_id']   = $parent;
			$data['asnp_wepb_order_again'] = 1;
		} elseif ( $items = $item->get_meta( '_asnp_wepb_items') ) {
			$data['asnp_wepb_items']       = $items;
			$data['asnp_wepb_order_again'] = 1;
		}

		return $data;
	}

	public function cart_loaded_from_session( $cart ) {
		foreach ( $cart->cart_contents as $cart_item_key => $cart_item ) {
			// Removes the bundle item from the cart when its parent is not present.
			if ( isset( $cart_item['asnp_wepb_parent_key'] ) && ! isset( $cart->cart_contents[ $cart_item['asnp_wepb_parent_key'] ] ) ) {
				$cart->remove_cart_item( $cart_item_key );
			}

			// Remove bundle items when order again is set.
			if ( isset( $cart_item['asnp_wepb_order_again'], $cart_item['asnp_wepb_parent_id'] ) ) {
				$cart->remove_cart_item( $cart_item_key );
			}

			// If the product bundle is in the cart set it to true, to use valid prices inside the cart by get_price methods.
			if ( isset( $cart_item[ static::CART_ITEM_ITEMS ] ) ) {
				$cart_item['data']->set_is_cart_item( true );
			}
		}

		foreach ( $cart->cart_contents as $cart_item_key => $cart_item ) {
			// Add bundle items again when order again is set.
			if ( isset( $cart_item['asnp_wepb_order_again'], $cart_item['asnp_wepb_items'] ) ) {
				unset( $cart->cart_contents[ $cart_item_key ]['asnp_wepb_order_again'] );
				$added = $this->add_bundle_items_to_cart( $cart_item_key, $cart_item['product_id'], $cart_item['quantity'], $cart_item['asnp_wepb_items'], $cart );
				if ( $added ) {
					// It is required to save the items key in the parent cart item.
					$cart->set_session();
				}
			}
		}
	}

	public function product_bundle_position_hooks() {
		$position = get_plugin()->settings->get_setting( 'product_bundle_position', 'before_css_selector' );

		if ( 'before_css_selector' === $position || 'after_css_selector' === $position ) {
			return;
		}

		switch ( $position ) {
			case 'before_add_to_cart_button' :
			case 'after_add_to_cart_button' :
				$add_to_cart_priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' );
				if ( 'before_add_to_cart_button' === $position ) {
					$add_to_cart_priority ?
						add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_bundle' ), $add_to_cart_priority - 1 ) :
						add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_bundle' ), 29 );
				} elseif ( 'after_add_to_cart_button' === $position ) {
					$add_to_cart_priority ?
						add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_bundle' ), $add_to_cart_priority + 1 ) :
						add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_bundle' ), 31 );
				}
				break;

			case 'before_add_to_cart_form':
				add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'display_product_bundle' ) );
				break;

			case 'after_add_to_cart_form':
				add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'display_product_bundle' ) );
				break;

			case 'before_excerpt' :
			case 'after_excerpt' :
				$excerpt_priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt' );
				if ( 'before_excerpt' === $position ) {
					$excerpt_priority ?
						add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_bundle' ), $excerpt_priority - 1 ) :
						add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_bundle' ), 19 );
				} elseif ( 'after_excerpt' === $position ) {
					$excerpt_priority ?
						add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_bundle' ), $excerpt_priority + 1 ) :
						add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_bundle' ), 21 );
				}
				break;

			case 'after_product_meta' :
				$meta_priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta' );
				$meta_priority ?
					add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_bundle' ), $meta_priority + 1 ) :
					add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_bundle' ), 41 );
				break;

			default :
				break;
		}
	}

	protected function remove_bundle_from_cart( $cart_item_key, $cart = null ) {
		$cart = $cart && is_a( $cart, 'WC_Cart' ) ? $cart : WC()->cart;

		if ( ! isset( $cart->cart_contents[ $cart_item_key ] ) ) {
			return;
		}

		if ( ! empty( $cart->cart_contents[ $cart_item_key ][ self::CART_ITEM_ITEMS_KEY ] ) ) {
			foreach ( $cart->cart_contents[ $cart_item_key ][ self::CART_ITEM_ITEMS_KEY ] as $item_key ) {
				$cart->remove_cart_item( $item_key );
			}
		}

		$cart->remove_cart_item( $cart_item_key );
	}

}
