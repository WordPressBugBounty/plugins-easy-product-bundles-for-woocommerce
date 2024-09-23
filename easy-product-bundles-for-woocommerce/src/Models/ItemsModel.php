<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Models;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles;

class ItemsModel {

	public static function search_products( array $args = array() ) {
		if ( empty( $args['search'] ) ) {
			throw new \Exception( 'Search term is required to search products.' );
		}

		$data_store = \WC_Data_Store::load( 'product' );

		if ( version_compare( WC_VERSION, '3.5.0', '>=' ) ) {
			$products = $data_store->search_products( wc_clean( wp_unslash( $args['search'] ) ), '', true, true, 50 );
		} else {
			$products = $data_store->search_products( wc_clean( wp_unslash( $args['search'] ) ), '', true, true );
		}

		return ! empty( $products ) ? self::prepare_product_items( $products, ! empty( $args['type'] ) ? $args['type'] : array() ) : array();
	}

	public static function get_products( array $args = array() ) {
		$args = wp_parse_args( $args, array(
			'status'   => array( 'private', 'publish' ),
			'type'     => ProductBundles\get_product_types_for_bundle(),
			'limit'    => -1,
			'orderby'  => array(
				'menu_order' => 'ASC',
				'ID'         => 'DESC',
			),
			'paginate' => false,
		) );

		$products = wc_get_products( $args );
		return ! empty( $products ) ? self::prepare_product_items( $products, $args['type'] ) : array();
	}

	protected static function prepare_product_items( array $products, $allowed_types = array( 'simple', 'variation' ) ) {
		if ( empty( $products ) ) {
			return array();
		}

		$pro_active      = ProductBundles\get_plugin()->is_pro_active();
		$products_select = array();
		foreach ( $products as $product ) {
			if ( is_numeric( $product ) ) {
				$product = wc_get_product( $product );
			}

			if ( ! ProductBundles\wc_products_array_filter_readable( $product ) ) {
				continue;
			}

			if ( ! empty( $allowed_types ) && ! in_array( $product->get_type(), $allowed_types ) ) {
				continue;
			}

			if ( $product->get_sku() ) {
				$identifier = $product->get_sku();
			} else {
				$identifier = '#' . $product->get_id();
			}

			$disabled = false;
			if ( $product->is_type( 'variation' ) ) {
				$formatted_variation_list = wc_get_formatted_variation( $product, true );
				$text = sprintf( '%2$s (%1$s)', $identifier, $product->get_title() ) . ' ' . $formatted_variation_list;
				$disabled = ! $pro_active;
				$text .= $disabled ? ' - ' . __( 'PRO Version', 'asnp-easy-product-bundles' ) : '';
			} else {
				$text = sprintf( '%2$s (%1$s)', $identifier, $product->get_title() );
				$disabled = ! $pro_active && ! $product->is_type( 'simple' ) && ! $product->is_type( 'variable' );
				$text .= $disabled ? ' - ' . __( 'PRO Version', 'asnp-easy-product-bundles' ) : '';
			}

			$products_select[] = (object) array(
				'value'      => $product->get_id(),
				'label'      => $text,
				'isDisabled' => $disabled,
			);
		}

		return $products_select;
	}

}
