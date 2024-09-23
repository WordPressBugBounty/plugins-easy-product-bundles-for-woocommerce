<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\API;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles;

class Review extends BaseController {

	protected $rest_base = 'review';

	public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'review' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
            )
		);
	}

	public function review( $request ) {
		try {
			if ( empty( $request['action'] ) ) {
				throw new \Exception( __( 'Action is required.', 'asnp-easy-product-bundles' ) );
			}

			switch ( $request['action'] ) {
				case 'later':
					return $this->later( $request );
					break;

				case 'dismiss':
					return $this->dismiss( $request );
					break;
			}

			throw new \Exception( __( 'Action not found.', 'asnp-easy-product-bundles' ) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_easy_product_bundles_rest_review_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	protected function later() {
		$review = ProductBundles\get_review();
		$review['schedule'] = strtotime( '+30 days' );
		ProductBundles\set_review( $review );

		return rest_ensure_response( array( 'success' => 1 ) );
	}

	protected function dismiss() {
		$review = ProductBundles\get_review();
		$review['dismissed'] = true;
		ProductBundles\set_review( $review );

		return rest_ensure_response( array( 'success' => 1 ) );
	}

}
