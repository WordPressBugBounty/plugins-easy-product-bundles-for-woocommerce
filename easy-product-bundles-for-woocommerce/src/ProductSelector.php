<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles\Abstracts\ProductSelectorInterface;
use AsanaPlugins\WooCommerce\ProductBundles\Helpers\Products;

class ProductSelector implements ProductSelectorInterface {

	public function select_products( $item, array $args = array() ) {
		if ( empty( $item ) ) {
			throw new \Exception( __( 'Product selector item is empty.', 'asnp-easy-product-bundles' ) );
		}

		$product                     = ! empty( $item['product'] ) ? wc_get_product( $item['product'] ) : null;
		$args['products']            = ! empty( $item['products'] ) ? array_filter( array_map( 'absint', $item['products'] ) ) : array();
		$args['excluded_products']   = ! empty( $item['excluded_products'] ) ? array_filter( array_map( 'absint', $item['excluded_products'] ) ) : array();
		$args['categories']          = ! empty( $item['categories'] ) ? array_filter( array_map( 'absint', $item['categories'] ) ) : array();
		$args['excluded_categories'] = ! empty( $item['excluded_categories'] ) ? array_filter( array_map( 'absint', $item['excluded_categories'] ) ) : array();
		$args['tags']                = ! empty( $item['tags'] ) ? array_filter( array_map( 'absint', $item['tags'] ) ) : array();
		$args['excluded_tags']       = ! empty( $item['excluded_tags'] ) ? array_filter( array_map( 'absint', $item['excluded_tags'] ) ) : array();
		$args['query_relation']      = ! empty( $item['query_relation'] ) && in_array( strtoupper( $item['query_relation'] ), [ 'AND', 'OR' ] ) ? sanitize_text_field( strtoupper( $item['query_relation'] ) ) : 'AND';
		$args['orderby']             = ! empty( $item['orderby'] ) ? wc_clean( wp_unslash( $item['orderby'] ) ) : 'date';
		$args['order']               = ! empty( $item['order'] ) && in_array( strtoupper( $item['order'] ), [ 'ASC', 'DESC' ] ) ? strtoupper( sanitize_text_field( $item['order'] ) ) : 'DESC';

		if ( $product && ! in_array( $product->get_id(), $args['products'] ) ) {
			if ( Products\is_variation( $product ) ) {
				if ( is_pro_active() && ! in_array( $product->get_parent_id(), $args['products'] ) ) {
					$args['products'][] = $product->get_id();
				}
			} else {
				$args['products'][] = $product->get_id();
			}
		}

		return $this->query( $args );
	}

	protected function query( array $args ) {
		if ( empty( $args ) ) {
			throw new \Exception( __( 'Query args is required.', 'asnp-easy-product-bundles' ) );
		}

		if ( empty( $args['products'] ) ) {
			return (object) [
				'products' => [],
				'total'    => 0,
				'pages'    => 0,
			];
		}

		$args = wp_parse_args( $args, [
			'type'     => get_product_types_for_bundle(),
			'status'   => [ 'publish' ],
			'limit'    => 12,
			'orderby'  => 'date',
			'order'    => 'DESC',
			'paginate' => true,
		] );

		return Products\get_products( [
			'return'   => ! empty( $args['return'] ) ? $args['return'] : 'objects',
			'status'   => $args['status'],
			'type'     => $args['type'],
			'include'  => $args['products'],
			'limit'    => ! empty( $args['limit'] ) && 0 < absint( $args['limit'] ) ? absint( $args['limit'] ) : 12,
			'paginate' => $args['paginate'],
			'page'     => ! empty( $args['page'] ) && 0 < absint( $args['page'] ) ? absint( $args['page'] ) : 1,
			'orderby'  => $args['orderby'],
			'order'    => $args['order'],
		] );
	}

}
