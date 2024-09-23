<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\API;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles;
use AsanaPlugins\WooCommerce\ProductBundles\Plugin;

class FilterProducts extends BaseController {

	protected $rest_base = 'filter-products';

	public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'filter' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				),
            )
		);
	}

	/**
	 * Filter products.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function filter( $request ) {
		if ( empty( $request['filter'] ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_filter_required', __( 'Filter is required.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		// Required to get products price with a tax.
		if ( ! WC()->customer ) {
			wc_load_cart();
		}

		if ( 'products' === $request['filter'] ) {
			return $this->filter_products( $request );
		} elseif ( 'childs' === $request['filter'] ) {
			return $this->filter_childs( $request );
		}

		return new \WP_Error( 'asnp_easy_product_bundles_filter_invalid', __( 'Filter is invalid.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
	}

	public function filter_products( $request ) {
		$product = ! empty( $request['product'] ) ? absint( $request['product'] ) : 0;
		if ( ! $product ) {
			return new \WP_Error( 'asnp_easy_product_bundles_product_required', __( 'Product ID is required.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		$product = wc_get_product( $product );
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_product_invalid', __( 'Product is invalid.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		$index = isset( $request['index'] ) && is_numeric( $request['index'] ) ? absint( $request['index'] ) : null;
		if ( null === $index ) {
			return new \WP_Error( 'asnp_easy_product_bundles_index_required', __( 'Index is required.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		$page = ! empty( $request['page'] ) ? absint( $request['page'] ) : 1;

		try {
			$data = $product->get_item_products( array(
				'index' => $index,
				'page'  => $page,
				'limit' => ProductBundles\get_plugin()->settings->get_setting( 'modal_products_limit', 12 ),
			) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_easy_product_bundles_filter_failed', $e->getMessage(), array( 'status' => 500 ) );
		}

		return new \WP_REST_Response( $data );
	}

	public function filter_childs( $request ) {
		$index = isset( $request['index'] ) && is_numeric( $request['index'] ) ? absint( $request['index'] ) : null;
		if ( null === $index ) {
			return new \WP_Error( 'asnp_easy_product_bundles_index_required', __( 'Index is required.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		$product = ! empty( $request['product'] ) ? absint( $request['product'] ) : 0;
		if ( 0 >= $product ) {
			return new \WP_Error( 'asnp_easy_product_bundles_product_required', __( 'Product ID is required.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		$product = wc_get_product( $product );
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return new \WP_Error( 'asnp_easy_product_bundles_product_invalid', __( 'Product is invalid.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		$items = $product->get_items();
		if ( empty( $items ) || empty( $items[ (int) $index ] )) {
			return new \WP_Error( 'asnp_easy_product_bundles_product_invalid', __( 'Product is invalid.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}
		$item = $items[ (int) $index ];

		$parent = ! empty( $request['parent'] ) ? absint( $request['parent'] ) : 0;
		if ( 0 >= $parent ) {
			return new \WP_Error( 'asnp_easy_product_bundles_parent_required', __( 'Parent product ID is required.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		$parent = wc_get_product( $parent );
		if ( ! $parent ) {
			return new \WP_Error( 'asnp_easy_product_bundles_parent_invalid', __( 'Parent product is invalid.', 'asnp-easy-product-bundles' ), array( 'status' => 400 ) );
		}

		try {
			switch ( $parent->get_type() ) {
				case 'variable':
					$data = $this->get_variations( $parent, $item );
					break;

				case 'variation':
					$data = $this->get_variation_childs( $parent, $item );
					break;

				default:
					throw new \Exception( __( 'Parent product is not a valid type.', 'asnp-easy-product-bundles' ) );
			}
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_easy_product_bundles_childs_error', $e->getMessage(), array( 'status' => 400 ) );
		}

		return new \WP_REST_Response( $data );
	}

	protected function get_variations( $variable, $item ) {
		if ( ! $variable ) {
			throw new \Exception( __( 'Variable is required.', 'asnp-easy-product-bundles' ) );
		}

		$variations = $variable->get_available_variations( 'objects' );
		if ( empty( $variations ) ) {
			return [];
		}

		$hide_out_of_stock = false;
		if ( ProductBundles\is_pro_active() ) {
			$hide_out_of_stock = 'true' === ProductBundles\get_plugin()->settings->get_setting( 'hide_out_of_stock', 'false' );
		}

		$data = [ 'products' => [], 'attribute_options' => [] ];
		foreach ( $variations as $variation ) {
			if ( ! $variation || ! $variation->is_purchasable() ) {
				continue;
			}

			if ( $hide_out_of_stock && ! $variation->is_in_stock() ) {
				continue;
			}

			if ( ! empty( $item['excluded_products'] ) && in_array( $variation->get_id(), $item['excluded_products'] ) ) {
				continue;
			}

			$data['products'] = array_merge( $data['products'], ProductBundles\prepare_variation_data( $variation, $variable, $item ) );
		}

		if ( empty( $data['products'] ) ) {
			return [];
		}

		$attributes = $variable->get_variation_attributes();
		foreach ( $attributes as $attribute_name => $options ) {
			$data['attribute_options'][] = [
				'name'    => apply_filters( 'asnp_wepb_variation_attribute_options_attribute_name', sprintf( __( 'Filter by %s', 'asnp-easy-product-bundles' ), wc_attribute_label( $attribute_name ) ), $attribute_name ),
				'id'      => esc_attr( sanitize_title( $attribute_name ) ),
				'options' => ProductBundles\get_variation_attribute_options(
					[
						'options'   => $options,
						'attribute' => $attribute_name,
						'product'   => $variable,
					]
				),
			];
		}

		return $data;
	}

	protected function get_variation_childs( $variation, $item ) {
		if ( ! $variation ) {
			throw new \Exception( __( 'Variation is required.', 'asnp-easy-product-bundles' ) );
		}

		$variable = wc_get_product( $variation->get_parent_id() );

		$data = [
			'products'          => ProductBundles\prepare_variation_data( $variation, $variable, $item ),
			'attribute_options' => [],
		];

		if ( empty( $data['products'] ) ) {
			return [];
		}

		$variation_attributes = $variation->get_variation_attributes( false );
		$attributes           = $variable->get_variation_attributes();
		foreach ( $attributes as $attribute_name => $options ) {
			if ( ! empty( $variation_attributes[ sanitize_title( $attribute_name ) ] ) ) {
				continue;
			}

			$data['attribute_options'][] = [
				'name'    => apply_filters( 'asnp_wepb_variation_attribute_options_attribute_name', sprintf( __( 'Filter by %s', 'asnp-easy-product-bundles' ), wc_attribute_label( $attribute_name ) ), $attribute_name ),
				'id'      => esc_attr( sanitize_title( $attribute_name ) ),
				'options' => ProductBundles\get_variation_attribute_options(
					[
						'options'   => $options,
						'attribute' => $attribute_name,
						'product'   => $variable,
					]
				),
			];
		}

		return $data;
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @since 1.0.0
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['filter'] = array(
			'description'       => __( 'Filter variable product variations or all products.', 'asnp-easy-product-bundles' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

}
