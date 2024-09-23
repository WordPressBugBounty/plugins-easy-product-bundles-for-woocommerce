<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

defined( 'ABSPATH' ) || exit;

function maybe_sync_simple_bundles() {
	$query = new \WP_Query(
		[
			'post_type'  => 'product',
			'fields'     => 'ids', // Only return product IDs
			'posts_per_page' => -1, // Retrieve all matching products
			'tax_query'  => [
				[
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'easy_product_bundle', // Filter by product type
				],
			],
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'     => '_default_products',
					'compare' => 'EXISTS', // Ensure the meta field exists
				],
				[
					'key'     => '_default_products',
					'value'   => '',
					'compare' => '!=', // Ensure the field is not empty
				],
			],
		]
	);

	if ( empty( $query->posts ) ) {
		return;
	}

	$background_sync = new BackgroundProductSync();
	// First lets cancel existing running queue to avoid running it more than once.
	$background_sync->kill_process();

	foreach ( $query->posts as $product ) {
		$background_sync->push_to_queue( [ 'product' => $product, 'add_items' => 1 ] );
	}

	// Lets dispatch the queue to start processing.
	$background_sync->save()->dispatch();
}
