<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Compatibilities;

defined( 'ABSPATH' ) || exit;

class Square {

	public static function init() {
		add_filter( 'wc_square_digital_wallets_supported_product_types', [ __CLASS__, 'digital_wallets_supported_product_types' ], 999 );
		add_filter( 'wc_square_display_digital_wallet_on_pages', [ __CLASS__, 'display_digital_wallet_on_pages' ], 999 );
	}

	public static function digital_wallets_supported_product_types( $types ) {
		$types[] = 'easy_product_bundle';
		return $types;
	}

	public static function display_digital_wallet_on_pages( $pages ) {
		// Return early if not on a product page or does not contain the 'product_page' shortcode
		if ( ! is_product() && ! wc_post_content_has_shortcode( 'product_page' ) ) {
			return $pages;
		}

		global $post;

		// Return early if $post is not available or if no product is found
		if ( empty( $post ) || empty( $post->ID ) || ! ( $product = wc_get_product( $post->ID ) ) ) {
			return $pages;
		}

		// Remove 'product' from the $pages array if the product is of type 'easy_product_bundle'
		if ( $product->is_type( 'easy_product_bundle' ) ) {
			$pages = array_diff( $pages, [ 'product' ] );
		}

		return $pages;
	}

}
