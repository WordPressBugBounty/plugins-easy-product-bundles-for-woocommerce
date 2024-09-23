<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\API;

defined( 'ABSPATH' ) || exit;

class Settings extends BaseController {

	protected $rest_base = 'settings';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Get the plugin settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_settings() {
		return new \WP_REST_Response(
			array(
				'settings' => get_option( 'asnp_easy_product_bundle_settings', array() ),
			)
		);
	}

	/**
	 * Save settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function save( $request ) {
		if ( ! $request ) {
			return new \WP_Error( 'asnp_wepb_settings_required', __( 'Settings data is required.', 'asnp-easy-product-bundles' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$data = [];

		foreach ( $request->get_params() as $key => $value ) {
			if ( in_array( $key, array( '_locale' ) ) ) {
				continue;
			}

			switch ( $key ) {
				default:
					if ( isset( $value ) ) {
						$data[ sanitize_text_field( $key ) ] = wp_kses_post_deep( $value );
					}
					break;
			}
		}

		if ( empty( $data ) ) {
			return new \WP_Error( 'asnp_wepb_settings_required', __( 'Settings data is required.', 'asnp-easy-product-bundles' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$data = apply_filters( 'asnp_wepb_settings_save', $data, $request );

		update_option( 'asnp_easy_product_bundle_settings', $data );

		do_action( 'asnp_wepb_settings_saved', $data, $request );

		return new \WP_REST_Response(
			array(
				'settings' => $data,
			)
		);
	}

}
