<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\API;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles;
use AsanaPlugins\WooCommerce\ProductBundles\Models\ItemsModel;

class Items extends BaseController {

	protected $rest_base = 'items';

	public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => array( $this, 'create_item_permissions_check' ),
                ),
            )
		);
	}

	/**
	 * Search items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function search_items( $request ) {
		if ( empty( $request['search'] ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_search_term_required', __( 'Search term is required.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		if ( empty( $request['type'] ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_type_required', __( 'Type is required.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		$search = wc_clean( wp_unslash( $request['search'] ) );
		if ( empty( $search ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_search_term_required', __( 'Search term is required.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		$items = [];

		if ( 'products' === $request['type'] ) {
			try {
				$items = ItemsModel::search_products( array( 'search' => $search, 'type' => ProductBundles\get_product_types_for_bundle() ) );
			} catch ( \Exception $e ) {
				return new \WP_Error( 'asnp_easy_product_bundles_error_in_searching_items', $e->getMessage(), array( 'status' => 400 ) );
			}
		} elseif ( 'default_product' === $request['type'] ) {
			try {
				$items = ItemsModel::search_products( array( 'search' => $search, 'type' => ProductBundles\get_product_types_for_bundle( ['variable'] ) ) );
			} catch ( \Exception $e ) {
				return new \WP_Error( 'asnp_easy_product_bundles_error_in_searching_items', $e->getMessage(), array( 'status' => 400 ) );
			}
		} else {
			$items = apply_filters( 'asnp_wepb_items_api_search_items', $items, $search, $request );
		}

		return new \WP_REST_Response( array(
            'items' => $items,
        ) );
	}

	/**
	 * Get items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		if ( empty( $request['items'] ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_items_required', __( 'Items is required.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		if ( empty( $request['type'] ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_type_required', __( 'Type is required.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		$items = $request['items'];
		if ( ! is_array( $items ) ) {
			$items = explode( ',', $items );
		}
		if ( ! empty( $items ) ) {
			$items = array_filter( array_map( 'absint', $items ) );
		}

		if ( 'products' === $request['type'] ) {
			try {
				$items = ItemsModel::get_products( array( 'include' => $items, 'type' => ProductBundles\get_product_types_for_bundle() ) );
			} catch ( \Exception $e ) {
				return new \WP_Error( 'asnp_easy_product_bundles_error_in_getting_items', $e->getMessage(), array( 'status' => 400 ) );
			}
		} elseif ( 'default_product' === $request['type'] ) {
			try {
				$items = ItemsModel::get_products( array( 'include' => $items, 'type' => ProductBundles\get_product_types_for_bundle( ['variable'] ) ) );
			} catch ( \Exception $e ) {
				return new \WP_Error( 'asnp_easy_product_bundles_error_in_getting_items', $e->getMessage(), array( 'status' => 400 ) );
			}
		} else {
			$items = apply_filters( 'asnp_wepb_items_api_get_items', [], $items, $request );
		}

		return new \WP_REST_Response( array(
            'items' => $items,
        ) );
	}

}
