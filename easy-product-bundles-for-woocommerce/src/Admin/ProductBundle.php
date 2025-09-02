<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Admin;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles;
use AsanaPlugins\WooCommerce\ProductBundles\Plugin;
use AsanaPlugins\WooCommerce\ProductBundles\Models\SimpleBundleItemsModel;

class ProductBundle {

	public function init() {
		// Add product bundles tab.
		add_action( 'woocommerce_product_data_tabs', array( $this, 'product_data_tabs' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'product_data_panels' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'maybe_delete_product_meta' ) );
		add_action( 'woocommerce_process_product_meta_' . Plugin::PRODUCT_TYPE, array( $this, 'save_product_data' ) );
	}

	/**
	 * Product data tabs filter
	 *
	 * Adds a new Extensions tab to the product data meta box.
	 *
	 * @param array $tabs Existing tabs.
	 *
	 * @return array
	 */
	public function product_data_tabs( $tabs ) {
		$tabs[ Plugin::PRODUCT_TYPE ] = array(
			'label'    => __( 'Product Bundles', 'asnp-easy-product-bundles' ),
			'target'   => 'asnp_product_bundles_panel',
			'class'    => array( 'show_if_' . Plugin::PRODUCT_TYPE ),
			'priority' => 49,
		);
		return $tabs;
	}

	/**
	 * Render product data panel.
	 *
	 * @return void
	 */
	public function product_data_panels() {
		echo '<div id="asnp_product_bundles_panel" class="panel woocommerce_options_panel hidden"></div>';
	}

	/**
	 * Maybe delte product data when type is mismatch.
	 *
	 * @param  int $post_id
	 *
	 * @return void
	 */
	public function maybe_delete_product_meta( $post_id ) {
		if ( ! isset( $_POST['product-type'] ) || Plugin::PRODUCT_TYPE === $_POST['product-type'] ) {
			return;
		}

		$fields = array(
			'individual_theme',
			'theme',
			'theme_size',
			'fixed_price',
			'include_parent_price',
			// 'edit_in_cart',
			'shipping_fee_calculation',
			'min_items_quantity',
			'max_items_quantity',
			'custom_display_price',
			'bundle_title',
			'bundle_description',
			'items',
			'default_products',
			'hide_items_price',
			'sync_stock_quantity'
		);
		foreach ( $fields as $field ) {
			delete_post_meta( $post_id, '_' . $field );
		}
	}

	public function save_product_data( $product ) {
		$product = wc_get_product( $product );
		if ( ! $product || Plugin::PRODUCT_TYPE !== $product->get_type() ) {
			return;
		}

		$items = $this->get_items();
		$config = $this->get_default_products( $items );
		$default_products = ! empty( $config['products'] ) ? $config['products'] : [];
		$errors = $product->set_props(
			[
				'individual_theme'         => isset( $_POST['asnp_wepb_individual_theme'] ) && 'true' === $_POST['asnp_wepb_individual_theme'] ? 'true' : 'false',
				'theme'                    => isset( $_POST['asnp_wepb_theme'] ) ? wc_clean( wp_unslash( $_POST['asnp_wepb_theme'] ) ) : '',
				'theme_size'               => isset( $_POST['asnp_wepb_theme_size'] ) ? wc_clean( wp_unslash( $_POST['asnp_wepb_theme_size'] ) ) : '',
				'fixed_price'              => isset( $_POST['asnp_wepb_fixed_price'] ) && 'true' === $_POST['asnp_wepb_fixed_price'] ? 'true' : 'false',
				'include_parent_price'     => isset( $_POST['asnp_wepb_include_parent_price'] ) && 'true' === $_POST['asnp_wepb_include_parent_price'] ? 'true' : 'false',
				'sync_stock_quantity'      => isset( $_POST['asnp_wepb_sync_stock_quantity'] ) && 'true' === $_POST['asnp_wepb_sync_stock_quantity'] ? 'true' : 'false',
				// 'edit_in_cart'             => isset( $_POST['asnp_wepb_edit_in_cart'] ) && 'true' === $_POST['asnp_wepb_edit_in_cart'] ? 'true' : 'false',
				'shipping_fee_calculation' => isset( $_POST['asnp_wepb_shipping_fee_calculation'] ) ? wc_clean( wp_unslash( $_POST['asnp_wepb_shipping_fee_calculation'] ) ) : '',
				'min_items_quantity'       => isset( $_POST['asnp_wepb_min_items_quantity'] ) && 0 < absint( $_POST['asnp_wepb_min_items_quantity'] ) ? absint( $_POST['asnp_wepb_min_items_quantity'] ) : '',
				'max_items_quantity'       => isset( $_POST['asnp_wepb_max_items_quantity'] ) && 0 < absint( $_POST['asnp_wepb_max_items_quantity'] ) ? absint( $_POST['asnp_wepb_max_items_quantity'] ) : '',
				'custom_display_price'     => ! empty( $_POST['asnp_wepb_custom_display_price'] ) ? wp_kses_post( $_POST['asnp_wepb_custom_display_price'] ) : '',
				'bundle_title'             => ! empty( $_POST['asnp_wepb_bundle_title'] ) ? wc_clean( wp_unslash( $_POST['asnp_wepb_bundle_title'] ) ) : '',
				'bundle_description'       => ! empty( $_POST['asnp_wepb_bundle_description'] ) ? wc_clean( wp_unslash( $_POST['asnp_wepb_bundle_description'] ) ) : '',
				'hide_items_price'         => isset( $_POST['asnp_wepb_hide_items_price'] ) ? wc_clean( wp_unslash( $_POST['asnp_wepb_hide_items_price'] ) ) : 'no',
				'items'                    => $items,
				'default_products'         => ! empty( $default_products ) ? json_encode( $default_products ) : '',
				'loop_add_to_cart'         => ! empty( $config['loop_add_to_cart'] ) ? 'true' : 'false',
			]
		);

		if ( is_wp_error( $errors ) ) {
			\WC_Admin_Meta_Boxes::add_error( $errors->get_error_message() );
		}

		$model = ProductBundles\get_plugin()->container()->get( SimpleBundleItemsModel::class );
		$model->delete_bundle( $product->get_id() );
		if ( ! empty( $default_products ) ) {
			foreach ( $default_products as $default ) {
				$model->add( [
					'bundle_id'  => $product->get_id(),
					'product_id' => (int) $default['id'],
					'quantity'   => (int) $default['qty']
				] );
			}
		}

		/**
		 * Set props before save.
		 */
		do_action( 'asnp_wepb_admin_process_product_object', $product );

		$product = $product->sync( $product, false );
		$product->save();
	}

	protected function get_items() {
		if ( empty( $_POST['asnp_wepb_bundle'] ) ) {
			return [];
		}

		$items = [];
		foreach ( $_POST['asnp_wepb_bundle'] as $item ) {
			$bundle_item = $this->get_item( $item );
			if ( $bundle_item ) {
				$items[] = $bundle_item;
			}
		}
		return $items;
	}

	protected function get_item( $item ) {
		if ( empty( $item ) ) {
			return false;
		}

		$bundle_item = [];
		$defaults    = [
			'optional' => 'false',
			'selected' => 'false',
			'products' => [],
			'excluded_products' => [],
			'categories' => [],
			'excluded_categories' => [],
			'tags' => [],
			'excluded_tags' => [],
			'query_relation' => 'OR',
			'edit_quantity' => 'false',
			'discount_type' => 'percentage',
			'discount' => '',
			'product' => '',
			'min_quantity' => 1,
			'max_quantity' => '',
			'quantity' => 1,
			'orderby' => 'date',
			'order' => 'DESC',
			'title' => '',
			'description' => '',
			'select_product_title' => __( 'Please select a product!', 'asnp-easy-product-bundles' ),
			'product_list_title' => __( 'Please select your product!', 'asnp-easy-product-bundles' ),
			'modal_header_title' => __( 'Please select your product', 'asnp-easy-product-bundles' ),
			'image_url' => '',
		];

		foreach ( $item as $key => $value ) {
			switch ( $key ) {
				case 'products':
				case 'excluded_products':
				case 'categories':
				case 'excluded_categories':
				case 'tags':
				case 'excluded_tags':
					if ( ! empty( $value ) ) {
						$bundle_item[ $key ] = array_filter( array_map( 'absint', $value ) );
					} elseif ( isset( $defaults[ $key ] ) ) {
						$bundle_item[ $key ] = $defaults[ $key ];
					}
					break;

				case 'product':
					if ( ! empty( $value ) ) {
						$product = wc_get_product( absint( $value ) );
						$bundle_item[ $key ] = $product ? $product->get_id() : $defaults[ $key ];

						// if ( ! $product ) {
						// 	$bundle_item[ $key ] = $defaults[ $key ];
						// } elseif ( $product->is_type( 'variation' ) ) {
						// 	// Do not set variation to the default product when it has any value attributes.
						// 	$variation_attributes = $product->get_variation_attributes( false );
						// 	$any_attributes       = ProductBundles\get_any_value_attributes( $variation_attributes );
						// 	$bundle_item[ $key ]  = empty( $any_attributes ) ? absint( $value ) : $defaults[ $key ];
						// } else {
						// 	$bundle_item[ $key ] = absint( $value );
						// }
					} elseif ( isset( $defaults[ $key ] ) ) {
						$bundle_item[ $key ] = $defaults[ $key ];
					}
					break;

				case 'optional':
				case 'selected':
				case 'edit_quantity':
					$bundle_item[ $key ] = 'true' === $value ? 'true' : 'false';
					break;

				case 'title':
				case 'select_product_title':
				case 'product_list_title':
				case 'modal_header_title':
				case 'discount_type':
				case 'orderby':
				case 'order':
					$bundle_item[ $key ] = sanitize_text_field( $value );
					break;

				case 'image_url':
					$value = trim( $value );
					$bundle_item[ $key ] = ! empty( $value ) ? esc_url_raw( $value ) : '';
					break;

				case 'query_relation':
					$bundle_item[ $key ] = 'AND' === strtoupper( $value ) ? 'AND' : 'OR';
					break;

				case 'quantity':
				case 'min_quantity':
				case 'max_quantity':
					if ( ! empty( $value ) ) {
						$bundle_item[ $key ] = absint( $value );
					} elseif ( isset( $defaults[ $key ] ) ) {
						$bundle_item[ $key ] = $defaults[ $key ];
					}
					break;

				case 'description':
					if ( isset( $value ) ) {
						$bundle_item[ $key ] = wp_kses_post( $value );
					} elseif ( isset( $defaults[ $key ] ) ) {
						$bundle_item[ $key ] = $defaults[ $key ];
					}
					break;

				case 'discount':
					if ( isset( $value ) && '' !== trim( $value ) ) {
						$bundle_item[ $key ] = floatval( $value );
					} elseif ( isset( $defaults[ $key ] ) ) {
						$bundle_item[ $key ] = $defaults[ $key ];
					}
					break;

				default:
					break;
			}
		}

		foreach ( $defaults as $key => $value ) {
			if ( ! isset( $bundle_item[ $key ] ) ) {
				$bundle_item[ $key ] = $value;
			}
		}

		return $bundle_item;
	}

	protected function get_default_products( $items ) {
		if ( empty( $items ) ) {
			return [];
		}

		$products = [];
		$loop_add_to_cart = true;
		foreach ( $items as $item ) {
			if ( empty( $item['quantity'] ) || 0 >= absint( $item['quantity'] ) ) {
				return [];
			}

			$product = $this->get_item_default_product( $item );
			if ( ! $product ) {
				return [];
			}

			// Disable loop add to cart logic.
			if ( $product->is_type( 'variable' ) ) {
				$loop_add_to_cart = false;
			} elseif ( $loop_add_to_cart && $product->is_type( 'variation' ) ) {
				$variation_attributes = $product->get_variation_attributes( false );
				$any_attributes       = ProductBundles\get_any_value_attributes( $variation_attributes );
				if ( ! empty( $any_attributes ) ) {
					$loop_add_to_cart = false;
				}
			}

			// Disable loop add to cart for not selected optional item.
			if ( $loop_add_to_cart && isset( $item['optional'] ) && 'true' === $item['optional'] ) {
				if ( ! isset( $item['selected'] ) || 'true' !== $item['selected'] ) {
					$loop_add_to_cart = false;
				}
			}

			$products[] = [
				'id'  => $product->get_id(),
				'qty' => absint( $item['quantity'] ),
			];
		}

		return [
			'products'         => $products,
			'loop_add_to_cart' => $loop_add_to_cart,
		];
	}

	protected function get_item_default_product( $item ) {
		if ( ! empty( $item['product'] ) ) {
			$product = wc_get_product( absint( $item['product'] ) );
			if ( ! $product || ! $product->is_purchasable() ) {
				return false;
			}

			/* if ( $product->is_type( 'variation' ) ) {
				$variation_attributes = $product->get_variation_attributes( false );
				$any_attributes       = ProductBundles\get_any_value_attributes( $variation_attributes );
				if ( ! empty( $any_attributes ) ) {
					return false;
				}
			} */

			return $product;
		}

		return false;
	}

}
