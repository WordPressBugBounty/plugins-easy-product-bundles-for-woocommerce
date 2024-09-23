<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

defined( 'ABSPATH' ) || exit;

class ProductValidator {

	public static function is_valid_product( $product, $item ) {
		if ( ! $product || empty( $item ) ) {
			return false;
		}

		$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
		if ( ! $product ) {
			return false;
		}

		if ( ! is_allowed_bundle_item_type( $product->get_type() ) ) {
			return false;
		}

		$default_product     = ! empty( $item['product'] ) ? absint( $item['product'] ) : 0;
		$products            = ! empty( $item['products'] ) ? array_filter( array_map( 'absint', $item['products'] ) ) : array();
		$excluded_products   = ! empty( $item['excluded_products'] ) ? array_filter( array_map( 'absint', $item['excluded_products'] ) ) : array();
		$categories          = ! empty( $item['categories'] ) ? array_filter( array_map( 'absint', $item['categories'] ) ) : array();
		$excluded_categories = ! empty( $item['excluded_categories'] ) ? array_filter( array_map( 'absint', $item['excluded_categories'] ) ) : array();
		$tags                = ! empty( $item['tags'] ) ? array_filter( array_map( 'absint', $item['tags'] ) ) : array();
		$excluded_tags       = ! empty( $item['excluded_tags'] ) ? array_filter( array_map( 'absint', $item['excluded_tags'] ) ) : array();
		$product_categories  = $product->is_type( 'variation' ) ? wc_get_product_cat_ids( $product->get_parent_id() ) : wc_get_product_cat_ids( $product->get_id() );
		$product_tags        = $product->is_type( 'variation' ) ? wc_get_product_term_ids( $product->get_parent_id(), 'product_tag' ) : wc_get_product_term_ids( $product->get_id(), 'product_tag' );

		if ( $default_product ) {
			$products[] = $default_product;
		}

		if (
			empty( $products ) &&
			empty( $categories ) &&
			empty( $tags ) &&
			empty( $excluded_products )	&&
			empty( $excluded_categories ) &&
			empty( $excluded_tags )
		) {
			return false;
		}

		if ( ! empty( $excluded_products ) ) {
			if ( in_array( $product->get_id(), $excluded_products ) ) {
				return false;
			}
			if ( $product->is_type( 'variation' ) && in_array( $product->get_parent_id(), $excluded_products ) ) {
				return false;
			}
		}

		if ( ! empty( $excluded_categories ) ) {
			if ( array_intersect( $product_categories, $excluded_categories ) ) {
				return false;
			}
		}

		if ( ! empty( $excluded_tags ) ) {
			if ( array_intersect( $product_tags, $excluded_tags ) ) {
				return false;
			}
		}

		if ( empty( $products ) && empty( $categories ) && empty( $tags ) ) {
			return true;
		}

		if ( ! empty( $products ) ) {
			if ( in_array( $product->get_id(), $products ) ) {
				return true;
			} elseif ( $product->is_type( 'variation' ) && in_array( $product->get_parent_id(), $products ) ) {
				return true;
			}
		}

		if ( ! empty( $categories ) ) {
			if ( array_intersect( $product_categories, $categories ) ) {
				return true;
			}
		}

		if ( ! empty( $tags ) ) {
			if ( array_intersect( $product_tags, $tags ) ) {
				return true;
			}
		}

		return false;
	}

}
