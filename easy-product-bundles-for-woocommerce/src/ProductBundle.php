<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

use AsanaPlugins\WooCommerce\ProductBundles\Abstracts\ProductSelectorInterface;

defined( 'ABSPATH' ) || exit;

class ProductBundle extends \WC_Product {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'individual_theme' => 'false',
		'theme' => 'grid_1',
		'theme_size' => 'medium',
		'fixed_price' => 'false',
		'include_parent_price' => 'false',
		// 'edit_in_cart' => 'false',
		'items' => array(),
		'default_products' => '',
		'hide_items_price' => 'no',
		'shipping_fee_calculation' => 'per_bundle',
		'custom_display_price' => '',
		'bundle_title' => '',
		'bundle_description' => '',
		'min_items_quantity' => '',
		'max_items_quantity' => '',
		'items_is_in_stock' => 'true',
		'default_products_price' => [],
		'sync_stock_quantity' => 'false'
	);

	protected $is_cart_item = false;

	/**
	 * Initialize bundle product.
	 *
	 * @param WC_Product|int $product Product instance or ID.
	 */
	public function __construct( $product = 0 ) {
		parent::__construct( $product );
		if ( $this->supports_ajax_add_to_cart() ) {
			$this->supports[] = 'ajax_add_to_cart';
		}
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return Plugin::PRODUCT_TYPE;
	}

	/**
	 * Returns whether or not the product can be purchased.
	 * This returns true for 'instock' and 'onbackorder' stock statuses.
	 *
	 * @return bool
	 */
	public function is_in_stock() {
		// Check the stock status of the bundle itself first.
		if ( 'outofstock' === $this->get_stock_status() ) {
			return apply_filters( 'woocommerce_product_is_in_stock', false, $this );
		}

		return apply_filters( 'woocommerce_product_is_in_stock', 'false' !== $this->get_items_is_in_stock(), $this );
	}

	/**
	 * Get bundle items of this product.
	 *
	 * @return array
	 */
	public function get_items( $context = 'view' ) {
		return $this->get_prop( 'items', $context );
	}

	/**
	 * Get bundle items default products.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_default_products( $context = 'view' ) {
		return $this->get_prop( 'default_products', $context );
	}

	/**
	 * Get individual theme.
	 *
	 * @return bool
	 */
	public function get_individual_theme( $context = 'view' ) {
		return $this->get_prop( 'individual_theme', $context );
	}

	/**
	 * Get theme.
	 *
	 * @return string
	 */
	public function get_theme( $context = 'view' ) {
		return $this->get_prop( 'theme', $context );
	}

	/**
	 * Get theme size.
	 *
	 * @return string
	 */
	public function get_theme_size( $context = 'view' ) {
		return $this->get_prop( 'theme_size', $context );
	}

	/**
	 * Get is fixed price.
	 *
	 * @return bool
	 */
	public function get_fixed_price( $context = 'view' ) {
		return $this->get_prop( 'fixed_price', $context );
	}

	/**
	 * Get include parent price.
	 *
	 * @return string 'true'|'false'
	 */
	public function get_include_parent_price( $context = 'view' ) {
		return $this->get_prop( 'include_parent_price', $context );
	}

	/**
	 * Get edit in cart.
	 *
	 * @return bool
	 */
	// public function get_edit_in_cart( $context = 'view' ) {
	// 	return $this->get_prop( 'edit_in_cart', $context );
	// }

	public function get_shipping_fee_calculation( $context = 'view' ) {
		return $this->get_prop( 'shipping_fee_calculation', $context );
	}

	public function get_custom_display_price( $context = 'view' ) {
		return $this->get_prop( 'custom_display_price', $context );
	}

	public function get_min_items_quantity( $context = 'view' ) {
		return $this->get_prop( 'min_items_quantity', $context );
	}

	public function get_max_items_quantity( $context = 'view' ) {
		return $this->get_prop( 'max_items_quantity', $context );
	}

	public function get_bundle_title( $context = 'view' ) {
		return $this->get_prop( 'bundle_title', $context );
	}

	public function get_bundle_description( $context = 'view' ) {
		return $this->get_prop( 'bundle_description', $context );
	}

	/**
	 * Get hide items price.
     *
 	 * @return string
 	 */
 	public function get_hide_items_price( $context = 'view' ) {
		return $this->get_prop( 'hide_items_price', $context );
	}

	/**
	 * Get items is in stock.
     *
 	 * @return string
 	 */
	public function get_items_is_in_stock( $context = 'view' ) {
		return $this->get_prop( 'items_is_in_stock', $context );
	}

	/**
	 * Get default products price.
     *
 	 * @return array
 	 */
	public function get_default_products_price( $context = 'view' ) {
		return $this->get_prop( 'default_products_price', $context );
	}

	/**
	 * Get default products price for display or view.
     *
 	 * @return array
 	 */
	public function get_default_products_price_for( $type = 'view' ) {
		$prices = $this->get_default_products_price();
		if ( empty( $prices ) ) {
			return [];
		}

		if ( 'display' === $type ) {
			return isset( $prices['display'] ) ? $prices['display'] : [];
		}

		return isset( $prices['raw'] ) ? $prices['raw'] : [];
	}

	/**
	 * Get sync stock quantity.
	 *
	 * @return string 'true'|'false'
	 */
	public function get_sync_stock_quantity( $context = 'view' ) {
		return $this->get_prop( 'sync_stock_quantity', $context );
	}

	public function get_initial_data( $context = 'view' ) {
		$data = array(
			'product'            => [
				'id'                   => $this->get_id(),
				'is_fixed_price'       => $this->is_fixed_price(),
				'regular_price'        => '' !== $this->get_regular_price( 'edit' ) ? wc_get_price_to_display( $this, [ 'price' => maybe_exchange_price( $this->get_regular_price( 'edit' ) ) ] ) : '',
				'sale_price'           => '' !== maybe_change_price( $this->get_sale_price( 'edit' ), $this, 'sale_price' ) && $this->is_on_sale( $context ) ? wc_get_price_to_display( $this, [ 'price' => maybe_exchange_price( maybe_change_price( $this->get_sale_price( 'edit' ), $this, 'sale_price' ) ) ] ) : '',
				'display_price'        => $this->get_price_html(),
				'include_parent_price' => $this->get_include_parent_price( $context ),
			],
			'individual_theme'   => $this->get_individual_theme( $context ),
			'theme'              => $this->get_theme( $context ),
			'theme_size'         => $this->get_theme_size( $context ),
			'bundle_title'       => $this->get_bundle_title( $context ),
			'bundle_description' => $this->get_bundle_description( $context ),
			'hide_items_price'   => $this->get_hide_items_price( $context ),
			'min_items_quantity' => $this->get_min_items_quantity(),
			'max_items_quantity' => $this->get_max_items_quantity(),
			'bundles'            => array(),
			'sync_stock_quantity'=> $this->get_sync_stock_quantity( $context ),
		);

		$items = $this->get_items();
		if ( empty( $items ) ) {
			return $data;
		}

		foreach ( $items as $item ) {
			$item_data = $this->get_item_default_data( $item );
			if ( ! empty( $item_data ) ) {
				$data['bundles'][] = $item_data;
			}
		}

		return $data;
	}

	public function get_item_default_data( $item ) {
		if ( empty( $item ) ) {
			return array();
		}

		$data = array(
			'product'              => ! empty( $item['product'] ) ? absint( $item['product'] ) : null,
			'can_change_product'   => 'false',
			'edit_quantity'        => isset( $item['edit_quantity'] ) && 'true' === $item['edit_quantity'] ? 'true' : 'false',
			'quantity'             => ! empty( $item['quantity'] ) ? absint( $item['quantity'] ) : 1,
			'min_quantity'         => ! empty( $item['min_quantity'] ) ? absint( $item['min_quantity'] ) : 1,
			'max_quantity'         => ! empty( $item['max_quantity'] ) ? absint( $item['max_quantity'] ) : null,
			'optional'             => isset( $item['optional'] ) && 'true' === $item['optional'] ? 'true' : 'false',
			'selected'             => isset( $item['selected'] ) && 'false' === $item['selected'] ? 'false' : 'true',
			'title'                => ! empty( $item['title'] ) ? sanitize_text_field( __( $item['title'], 'asnp-easy-product-bundles' ) ) : '',
			'description'          => ! empty( $item['description'] ) ? wp_kses_post( $item['description'] ) : '',
			'select_product_title' => ! empty( $item['select_product_title'] ) ? sanitize_text_field( __( $item['select_product_title'], 'asnp-easy-product-bundles' ) ) : __( 'Please select a product!', 'asnp-easy-product-bundles' ),
			'product_list_title'   => ! empty( $item['product_list_title'] ) ? sanitize_text_field( __( $item['product_list_title'], 'asnp-easy-product-bundles' ) ) : __( 'Please select your product!', 'asnp-easy-product-bundles' ),
			'modal_header_title'   => ! empty( $item['modal_header_title'] ) ? sanitize_text_field( __( $item['modal_header_title'], 'asnp-easy-product-bundles' ) ) : __( 'Please select your product', 'asnp-easy-product-bundles' ),
			'image_url'            => ! empty( $item['image_url'] ) ? esc_url( $item['image_url'] ) : '',
		);

		$args = [
			'return'            => 'ids',
			'hide_out_of_stock' => 'true' === get_plugin()->settings->get_setting( 'hide_out_of_stock', 'false' ),
		];

		// TODO: Use valid values for type and limit.
		// TODO: Add sort and ordering support.
		$product_selector = get_plugin()->container()->get( ProductSelectorInterface::class );
		$query            = $product_selector->select_products( $item, $args );
		if ( empty( $query->products ) ) {
			return $data;
		}

		if ( 1 === $query->total && empty( $data['product'] ) && 'false' === $data['optional'] ) {
			$data['product'] = (int) $query->products[0];
		}

		if ( 1 < $query->total || ( 1 == $query->total && 'true' === $data['optional'] ) ) {
			$data['can_change_product'] = 'true';
		}

		if ( ! empty( $data['product'] ) ) {
			$product = wc_get_product( $data['product'] );
			if ( $product && ! $product->is_type( 'variable' ) && $product->is_purchasable() ) {
				if ( $product->is_type( 'variation' ) ) {
					// Do not set variation to the default product when it has any value attributes.
					$variation_attributes = $product->get_variation_attributes( false );
					$any_attributes       = get_any_value_attributes( $variation_attributes );
					if ( empty( $any_attributes ) ) {
						$data['product'] = prepare_product_data( $product, $item );
					} else {
						$data['product']            = null;
						$data['can_change_product'] = 'true';
					}
				} else {
					$data['product'] = prepare_product_data( $product, $item );
				}
			} else {
				$data['product']            = null;
				$data['can_change_product'] = 'true';
			}
		}

		return $data;
	}

	public function get_item_products( array $args = array() ) {
		if ( ! isset( $args['index'] ) || 0 > (int) $args['index'] ) {
			throw new \Exception( __( 'Item index is required.', 'asnp-easy-product-bundles' ) );
		}

		$data = array(
			'products' => array(),
			'pages'    => 0,
			'total'    => 0,
		);

		$items = $this->get_items();
		if ( empty( $items ) || empty( $items[ (int) $args['index'] ] )) {
			return $data;
		}

		$item = $items[ (int) $args['index'] ];

		$args['hide_out_of_stock'] = 'true' === get_plugin()->settings->get_setting( 'hide_out_of_stock', 'false' );

		// TODO: Use valid values for type and limit.
		// TODO: Add sort and ordering support.
		$product_selector = get_plugin()->container()->get( ProductSelectorInterface::class );
		$query = $product_selector->select_products( $item, $args );
		$data['pages'] = $query->pages;
		$data['total'] = $query->total;
		if ( empty( $query->products ) ) {
			return $data;
		}

		foreach ( $query->products as $product ) {
			if ( ! $product->is_purchasable() ) {
				continue;
			}

			$extra_data = [];
			if ( $product->is_type( 'variation' ) ) {
				$variation_attributes = $product->get_variation_attributes( false );
				$any_attributes       = get_any_value_attributes( $variation_attributes );
				if ( ! empty( $any_attributes ) ) {
					$extra_data['is_parent'] = 'true';
				}
			}

			$data['products'][] = prepare_product_data( $product, $item, $extra_data );
		}

		return $data;
	}

	/**
	 * Returns the product's active price.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string price
	 */
	public function get_price( $context = 'view' ) {
		if ( 'edit' === $context || $this->is_fixed_price() ) {
			return parent::get_price( $context );
		}

		// It is required to consider price changes inside the cart.
		if ( $this->is_cart_item || array_key_exists( 'price', $this->changes ) ) {
			return parent::get_price( $context );
		}

		$prices = $this->get_default_products_price_for( $context );

		if ( empty( $prices ) || ! isset( $prices['total'] ) ) {
			return parent::get_price( $context );
		}

		return $prices['total'];
	}

	/**
	 * Returns the product's regular price.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string price
	 */
	public function get_regular_price( $context = 'view' ) {
		if ( 'edit' === $context || $this->is_fixed_price() ) {
			return parent::get_regular_price( $context );
		}

		// It is required to consider price changes inside the cart.
		if ( $this->is_cart_item || array_key_exists( 'regular_price', $this->changes ) ) {
			return parent::get_regular_price( $context );
		}

		$prices = $this->get_default_products_price_for( $context );
		if ( empty( $prices ) || ! isset( $prices['regular'] ) ) {
			return parent::get_regular_price( $context );
		}

		return $prices['regular'];
	}

	/**
	 * Returns the product's sale price.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string price
	 */
	public function get_sale_price( $context = 'view' ) {
		if ( 'edit' === $context || $this->is_fixed_price() ) {
			return parent::get_sale_price( $context );
		}

		// It is required to consider price changes inside the cart.
		if ( $this->is_cart_item || array_key_exists( 'sale_price', $this->changes ) ) {
			return parent::get_sale_price( $context );
		}

		$prices = $this->get_default_products_price_for( $context );
		if ( empty( $prices ) || ! isset( $prices['total'] ) ) {
			return parent::get_sale_price( $context );
		}

		return $prices['total'];
	}

	public function get_is_cart_item() {
		return $this->is_cart_item;
	}

	/**
	 * Set bundle items of this product.
	 *
	 * @param array $items
	 */
	public function set_items( $items ) {
		$this->set_prop( 'items', $items );
	}

	/**
	 * Set default products for items.
	 *
	 * @param string $default_products
	 */
	public function set_default_products( $default_products ) {
		$this->set_prop( 'default_products', $default_products );
	}

	/**
	 * Set individual theme use.
	 *
	 * @param bool $individual_theme
	 */
	public function set_individual_theme( $individual_theme ) {
		$this->set_prop( 'individual_theme', $individual_theme );
	}

	/**
	 * Set theme.
	 *
	 * @param string $theme
	 */
	public function set_theme( $theme ) {
		$this->set_prop( 'theme', $theme );
	}

	/**
	 * Set theme size.
	 *
	 * @param string $theme_size
	 */
	public function set_theme_size( $theme_size ) {
		$this->set_prop( 'theme_size', $theme_size );
	}

	/**
	 * Set is fixed price.
	 *
	 * @param bool $fixed_price
	 */
	public function set_fixed_price( $fixed_price ) {
		$this->set_prop( 'fixed_price', $fixed_price );
	}

	/**
	 * Set is parent price included.
	 *
	 * @param string $include_parent_price 'true'|'false'
	 */
	public function set_include_parent_price( $include_parent_price ) {
		$this->set_prop( 'include_parent_price', $include_parent_price );
	}

	/**
	 * Set sync stock quantity..
	 *
	 * @param string $sync_stock_quantity 'true'|'false'
	 */
	public function set_sync_stock_quantity( $sync_stock_quantity ) {
		$this->set_prop( 'sync_stock_quantity', $sync_stock_quantity );
	}

	/**
	 * Set edit in cart.
	 *
	 * @param bool $edit_in_cart
	 */
	// public function set_edit_in_cart( $edit_in_cart ) {
	// 	$this->set_prop( 'edit_in_cart', $edit_in_cart );
	// }

	public function set_shipping_fee_calculation( $shipping_fee_calculation ) {
		$this->set_prop( 'shipping_fee_calculation', $shipping_fee_calculation );
	}

	public function set_custom_display_price( $custom_display_price ) {
		$this->set_prop( 'custom_display_price', $custom_display_price );
	}

	public function set_min_items_quantity( $min_items_quantity ) {
		$this->set_prop( 'min_items_quantity', $min_items_quantity );
	}

	public function set_max_items_quantity( $max_items_quantity ) {
		$this->set_prop( 'max_items_quantity', $max_items_quantity );
	}

	public function set_bundle_title( $bundle_title ) {
		$this->set_prop( 'bundle_title', $bundle_title );
	}

	public function set_bundle_description( $bundle_description ) {
		$this->set_prop( 'bundle_description', $bundle_description );
	}

	public function set_hide_items_price( $hide_items_price ) {
		$this->set_prop( 'hide_items_price', $hide_items_price );
	}

	public function set_items_is_in_stock( $is_in_stock ) {
		$this->set_prop( 'items_is_in_stock', '' === $is_in_stock ? 'true' : $is_in_stock );
	}

	public function set_default_products_price( $default_products_price ) {
		$this->set_prop( 'default_products_price', '' === $default_products_price ? [] : $default_products_price );
	}

	public function set_is_cart_item( $is_cart_item ) {
		$this->is_cart_item = (boolean) $is_cart_item;
	}

	/**
	 * Returns false if the product cannot be bought.
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		return apply_filters( 'woocommerce_is_purchasable', $this->exists() && ( 'publish' === $this->get_status() || current_user_can( 'edit_post', $this->get_id() ) ), $this );
	}

	/**
	 * Product has a price or no.
	 *
	 * @return boolean
	 */
	public function has_price() {
		$regular_price = $this->get_regular_price( 'edit' );
		$sale_price    = $this->get_sale_price( 'edit' );
		return ( '' !== $regular_price && 0 <= (float) $regular_price) ||
			( '' !== $sale_price && 0 <= (float) $sale_price );
	}

	/**
	 * Is fixed price enabled.
	 *
	 * @return boolean
	 */
	public function is_fixed_price() {
		if ( 'false' === $this->get_fixed_price() ) {
			return false;
		}

		return $this->has_price();
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		$url = $this->is_purchasable() && $this->is_in_stock() && ! empty( $this->get_default_products() ) ? remove_query_arg(
			'added-to-cart',
			add_query_arg(
				array(
					'add-to-cart' => $this->get_id(),
				),
				( function_exists( 'is_feed' ) && is_feed() ) || ( function_exists( 'is_404' ) && is_404() ) ? $this->get_permalink() : ''
			)
		) : $this->get_permalink();
		return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
	}

	/**
	 * Get the add to cart button text.
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		$text = $this->is_purchasable() && $this->is_in_stock() && ! empty( $this->get_default_products() ) ? __( 'Add to cart', 'woocommerce' ) : __( 'Read more', 'woocommerce' );

		return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );
	}

	public function supports_ajax_add_to_cart() {
		$default_products = $this->get_default_products();
		return ! empty( $default_products );
	}

	public static function calculate_default_products_price( $product ) {
		if ( $product->is_fixed_price() ) {
			return [];
		}

		$default_products = $product->get_default_products();
		if ( empty( $default_products ) ) {
			return [];
		}

		$quantities       = get_quantities_from_bundle_items( $default_products );
		$default_products = get_product_ids_from_bundle_items( $default_products );
		$items            = $product->get_items();
		if ( empty( $items ) || count( $items ) !== count( $default_products ) ) {
			return [];
		}

		$min_price         = null;
		$min_price_display = null;
		$total             = 0;
		$total_display     = 0;
		$regular           = 0;
		$regular_display   = 0;

		if ( 'true' === $product->get_include_parent_price() ) {
			if ( '' !== $product->get_price( 'edit' ) ) {
				$total = (float) $product->get_price( 'edit' );
				$total_display = wc_get_price_to_display( $product, [ 'price' => $product->get_price( 'edit' ) ] );
			}

			if ( '' !== $product->get_regular_price( 'edit' ) ) {
				$regular = (float) $product->get_regular_price( 'edit' );
				$regular_display = wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price( 'edit' ) ] );
			}
		}

		for ( $i = 0; $i < count( $default_products ); $i++ ) {
			if ( 0 >= (int) $default_products[ $i ] ) {
				continue;
			}

			$item_product = wc_get_product( (int) $default_products[ $i ] );
			if ( ! $item_product || ! $item_product->is_purchasable() ) {
				continue;
			}

			$product_price = get_bundle_item_price(
				$item_product,
				[
					'discount_type'  => ! empty( $items[ $i ]['discount_type'] ) ? $items[ $i ]['discount_type'] : '',
					'discount'       => isset( $items[ $i ]['discount'] ) && '' !== $items[ $i ]['discount'] ? (float) $items[ $i ]['discount'] : null,
					'is_fixed_price' => false,
					'exchange_price' => false,
				]
			);

			$product_price_display = wc_get_price_to_display( $item_product, [ 'price' => $product_price ] );

			$quantity = isset( $quantities[ $i ] ) && 0 < (int) $quantities[ $i ] ? (int) $quantities[ $i ] : 1;
			$min_price = null === $min_price ? $product_price * $quantity : min( $min_price, $product_price * $quantity );
			$min_price_display = null === $min_price_display ? $product_price_display * $quantity : min( $min_price_display, $product_price_display * $quantity );
			$total += (float) $product_price * $quantity;
			$total_display += (float) $product_price_display * $quantity;

			$regular += (float) $item_product->get_regular_price( 'edit' ) * $quantity;
			$regular_display += wc_get_price_to_display( $item_product, [ 'price' => $item_product->get_regular_price( 'edit' ), 'qty' => $quantity ] );
		}

		if ( null === $min_price ) {
			return [];
		}

		return [
			'display' => [
				'min'     => $min_price_display,
				'total'   => $total_display,
				'regular' => $regular_display,
			],
			'raw'     => [
				'min'     => $min_price,
				'total'   => $total,
				'regular' => $regular,
			],
		];
	}

	public static function is_items_in_stock( $product ) {
		// Retrieve bundle items.
		$items = $product->get_items();
		if ( empty( $items ) ) {
			return true;
		}

		foreach ( $items as $item ) {
			if ( isset( $item['optional'] ) && 'true' === $item['optional'] ) {
				continue;
			}

			if ( ! empty( $item['products'] ) && 1 < count( $item['products'] ) ) {
				continue;
			}

			if ( ! empty( $item['categories'] ) || ! empty( $item['excluded_categories'] ) ) {
				continue;
			}

			if ( ! empty( $item['tags'] ) || ! empty( $item['excluded_tags'] ) ) {
				continue;
			}

			$default_product = ! empty( $item['product'] ) ? wc_get_product( (int) $item['product'] ) : null;
			// Do not check stock status of default product when more products are available.
			if (
				$default_product &&
				! empty( $item['products'] ) &&
				$default_product->get_id() != $item['products'][0]
			) {
				continue;
			}

			// Use the product inside products field as a default product.
			if ( ! $default_product && ! empty( $item['products'] ) && 1 === count( $item['products'] ) ) {
				$default_product = wc_get_product( (int) $item['products'][0] );
			}

			$quantity = ! empty( $item['quantity'] ) && 0 < (int) $item['quantity'] ? absint( $item['quantity'] ) : 1;
			if (
				$default_product &&
				( ! $default_product->is_in_stock() || ! $default_product->has_enough_stock( $quantity ) ) &&
				! $default_product->backorders_allowed()
			) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sync a variable product with its children. These sync functions sync
	 * upwards (from child to parent) when the variation is saved.
	 *
	 * @param WC_Product|int $product Product object or ID for which you wish to sync.
	 * @param bool           $save If true, the product object will be saved to the DB before returning it.
	 * @return WC_Product Synced product object.
	 */
	public static function sync( $product, $save = true ) {
		$product = is_a( $product, '\WC_Product' ) ? $product : wc_get_product( $product );
		if ( ! $product || Plugin::PRODUCT_TYPE !== $product->get_type() ) {
			return $product;
		}

		static::sync_prices( $product, $save );
		static::sync_items_is_in_stock( $product, $save );
		static::sync_bundle_stock_quantity( $product, $save );

		return $product;
	}

	/**
	 * Sync parent stock status with the status of all children and save.
	 *
	 * @param WC_Product|int $product Product object or ID for which you wish to sync.
	 * @param bool           $save If true, the product object will be saved to the DB before returning it.
	 * @return WC_Product Synced product object.
	 */
	public static function sync_items_is_in_stock( $product, $save = true ) {
		$product = is_a( $product, '\WC_Product' ) ? $product : wc_get_product( $product );
		if ( ! $product || Plugin::PRODUCT_TYPE !== $product->get_type() ) {
			return $product;
		}

		$items_is_in_stock = static::is_items_in_stock( $product );
		$product->set_items_is_in_stock( $items_is_in_stock ? 'true' : 'false' );

		if ( $save ) {
			$product->save();
		}

		return $product;
	}

	public static function sync_prices( $product, $save = true ) {
		$product = is_a( $product, '\WC_Product' ) ? $product : wc_get_product( $product );
		if ( ! $product || Plugin::PRODUCT_TYPE !== $product->get_type() ) {
			return $product;
		}

		$default_products_price = static::calculate_default_products_price( $product );
		$product->set_default_products_price( $default_products_price );

		if ( $save ) {
			$product->save();
		}

		return $product;
	}

	public static function sync_bundle_stock_quantity( $product, $save = true ) {
		// If sync_stock_quantity is disabled, no stock syncing will occur
		if ( 'true' !== $product->get_sync_stock_quantity() ) {
			return $product;
		}

		// Sync stock based on the stock quantity of the bundle's items
		$items = $product->get_items();
		if ( empty( $items ) ) {
			return $product;
		}

		$min_stock = null;

		foreach ( $items as $item ) {
			if ( isset( $item['optional'] ) && 'true' === $item['optional'] ) {
				continue;
			}

			if ( ! empty( $item['products'] ) && 1 < count( $item['products'] ) ) {
				continue;
			}

			if ( ! empty( $item['categories'] ) || ! empty( $item['excluded_categories'] ) ) {
				continue;
			}

			if ( ! empty( $item['tags'] ) || ! empty( $item['excluded_tags'] ) ) {
				continue;
			}

			$default_product = ! empty( $item['product'] ) ? wc_get_product( (int) $item['product'] ) : null;
			// Do not check stock status of default product when more products are available.
			if (
				$default_product &&
				! empty( $item['products'] ) &&
				$default_product->get_id() != $item['products'][0]
			) {
				continue;
			}

			// Use the product inside products field as a default product.
			if ( ! $default_product && ! empty( $item['products'] ) && 1 === count( $item['products'] ) ) {
				$default_product = wc_get_product( (int) $item['products'][0] );
			}

			if ( ! $default_product || ! $default_product->managing_stock() ) {
				continue;
			}

			$quantity = ! empty( $item['quantity'] ) && 0 < (int) $item['quantity'] ? absint( $item['quantity'] ) : 1;
			$stock    = floor( $default_product->get_stock_quantity() / $quantity );

			if ( null === $min_stock || $stock < $min_stock ) {
				$min_stock = $stock;
			}
		}

		if ( null !== $min_stock ) {
			$product->set_stock_quantity( $min_stock );
		}

		// Save the product if needed
		if ( $save ) {
			$product->save();
		}

		return $product;
	}

}
