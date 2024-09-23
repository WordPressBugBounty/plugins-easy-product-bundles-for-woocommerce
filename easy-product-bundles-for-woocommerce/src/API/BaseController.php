<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\API;

defined( 'ABSPATH' ) || exit;

abstract class BaseController extends \WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'asnp-easy-product-bundles/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'asnp-easy-product-bundles' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$id = isset( $request['id'] ) ? (int) $request['id'] : 0;

		if ( 0 >= $id || ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'asnp-easy-product-bundles' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_rest_cannot_create', __( 'Sorry, you cannot create an item.', 'asnp-easy-product-bundles' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to update a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_rest_cannot_update', __( 'Sorry, you cannot update the item.', 'asnp-easy-product-bundles' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'asnp-easy-product-bundles' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to duplicate an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function duplicate_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_rest_cannot_duplicate', __( 'Sorry, you are not allowed to duplicate this resource.', 'asnp-easy-product-bundles' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to reorder items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function reorder_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_rest_cannot_reorder', __( 'Sorry, you are not allowed to reorder items.', 'asnp-easy-product-bundles' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

}
