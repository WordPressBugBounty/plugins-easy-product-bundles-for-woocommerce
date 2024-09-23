<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Helpers\Products;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles;

function get_products( array $args = [] ) {
	$args = wp_parse_args( $args, [
		'status'             => [ 'publish' ],
		'type'               => ProductBundles\get_product_types_for_bundle(),
		'parent'             => null,
		'sku'                => '',
		'category'           => [],
		'category_args'      => [
			'field'    => 'term_id',
			'operator' => 'IN',
		],
		'tag'                => [],
		'tag_args'           => [
			'field'    => 'term_id',
			'operator' => 'IN',
		],
		'limit'              => get_option( 'posts_per_page' ),
		'offset'             => null,
		'page'               => 1,
		'include'            => [],
		'exclude'            => [],
		'orderby'            => 'date',
		'order'              => 'DESC',
		'return'             => 'objects',
		'paginate'           => false,
		'shipping_class'     => [],
		'meta_query'         => [],
		'tax_query'          => [],
		'tax_query_relation' => 'OR',
		'date_query'         => [],
		'post_title'         => '',
		'post_id'            => '',
	] );

	/**
	 * Generate WP_Query args.
	 */
	$wp_query_args = array(
		'post_status'    => $args['status'],
		'posts_per_page' => $args['limit'],
		'meta_query'     => $args['meta_query'],
		'tax_query'      => $args['tax_query'],
		'date_query'     => $args['date_query'],
	);
	// Do not load unnecessary post data if the user only wants IDs.
	if ( 'ids' === $args['return'] ) {
		$wp_query_args['fields'] = 'ids';
	}

	// Handle product types.
	if ( 'variation' === $args['type'] ) {
		$wp_query_args['post_type'] = 'product_variation';
	} elseif ( is_array( $args['type'] ) && in_array( 'variation', $args['type'], true ) ) {
		$wp_query_args['post_type']   = array( 'product_variation', 'product' );
		$wp_query_args['tax_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'relation' => 'OR',
			array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $args['type'],
			),
			array(
				'taxonomy' => 'product_type',
				'field'    => 'id',
				'operator' => 'NOT EXISTS',
			),
		);
	} else {
		$wp_query_args['post_type']   = 'product';
		$wp_query_args['tax_query'][] = array(
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => $args['type'],
		);
	}

	$tax_qeury = [];

	// Product categories.
	if ( ! empty( $args['category'] ) ) {
		$tax_qeury[] = [
			'taxonomy' => 'product_cat',
			'field'    => isset( $args['category_args']['field'] ) ? $args['category_args']['field'] : 'term_id',
			'terms'    => $args['category'],
			'operator' => isset( $args['category_args']['operator'] ) ? $args['category_args']['operator'] : 'IN',
		];
	}

	// Product tags.
	if ( ! empty( $args['tag'] ) ) {
		$tax_qeury[] = [
			'taxonomy' => 'product_tag',
			'field'    => isset( $args['tag_args']['field'] ) ? $args['tag_args']['field'] : 'term_id',
			'terms'    => $args['tag'],
			'operator' => isset( $args['tag_args']['operator'] ) ? $args['tag_args']['operator'] : 'IN',
		];
	}

	// Custom product taxonomies.
	if ( ! empty( $args['taxonomy'] ) && ! empty( $args['taxonomies'] ) ) {
		$tax_qeury[] = [
			'taxonomy' => sanitize_text_field( $args['taxonomy'] ),
			'field'    => 'term_id',
			'terms'    => $args['taxonomies'],
			'operator' => isset( $args['tax_args']['operator'] ) ? $args['tax_args']['operator'] : 'IN',
		];
	}

	if ( ! empty( $tax_qeury ) ) {
		if ( 1 < count( $tax_qeury ) ) {
			$tax_qeury['relation'] = ! empty( $args['tax_query_relation'] ) ? sanitize_text_field( $args['tax_query_relation'] ) : 'OR';
		}
		$wp_query_args['tax_query'][] = $tax_qeury;
	}

	if ( ! is_null( $args['parent'] ) ) {
		$wp_query_args['post_parent'] = absint( $args['parent'] );
	}

	if ( ! is_null( $args['offset'] ) ) {
		$wp_query_args['offset'] = absint( $args['offset'] );
	} else {
		$wp_query_args['paged'] = absint( $args['page'] );
	}

	if ( ! empty( $args['include'] ) ) {
		$wp_query_args['post__in'] = array_map( 'absint', $args['include'] );
	}

	if ( ! empty( $args['exclude'] ) ) {
		$wp_query_args['post__not_in'] = array_map( 'absint', $args['exclude'] );
	}

	if ( ! $args['paginate'] ) {
		$wp_query_args['no_found_rows'] = true;
	}

	if ( ! empty( $args['meta_key'] ) ) {
		$wp_query_args['meta_key'] = $args['meta_key'];
	}

	// Ordering args.
	$ordering_args            = WC()->query->get_catalog_ordering_args( $args['orderby'], $args['order'] );
	$wp_query_args['orderby'] = $ordering_args['orderby'];
	$wp_query_args['order']   = $ordering_args['order'];
	if ( $ordering_args['meta_key'] ) {
		$wp_query_args['meta_key'] = $ordering_args['meta_key']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	}

	$wp_query_args = apply_filters( 'asnp_wepb_get_products_query', $wp_query_args, $args );

	// Get results.
	$query = new \WP_Query( $wp_query_args );

	$products = ( isset( $args['return'] ) && 'ids' === $args['return'] ) ? $query->posts : array_filter( array_map( 'wc_get_product', $query->posts ) );

	if ( isset( $args['paginate'] ) && $args['paginate'] ) {
		$products = (object) array(
			'products' => $products,
			'total'    => $query->found_posts,
			'pages'    => $query->max_num_pages,
		);
	}

	// Remove ordering query arguments which may have been added by get_catalog_ordering_args.
	WC()->query->remove_ordering_args();

	return $products;
}

function is_variation( $product ) {
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product ) {
		return false;
	}

	return false !== strpos( $product->get_type(), 'variation' );
}

function get_description( $product ) {
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product ) {
		return '';
	}

	$description = $product->get_short_description();
	$description = empty( $description ) ? $product->get_description() : $description;

	if ( empty( $description ) && is_variation( $product ) ) {
		$parent = wc_get_product( $product->get_parent_id() );
		if ( $parent ) {
			$description = $parent->get_short_description();
			$description = empty( $description ) ? $parent->get_description() : $description;
		}
	}

	return $description;
}
