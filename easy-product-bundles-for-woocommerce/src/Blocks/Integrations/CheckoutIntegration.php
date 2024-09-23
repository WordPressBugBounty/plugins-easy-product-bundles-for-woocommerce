<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Blocks\Integrations;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class CheckoutIntegration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wepb-checkout-integration';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		wp_enqueue_style(
			'wepb-checkout-integration',
			$this->get_url( 'checkout-integration/style', 'css' ),
			[],
			ASNP_WEPB_VERSION
		);

		wp_register_script(
			'wepb-checkout-integration',
			$this->get_url( 'checkout-integration/index', 'js' ),
			[ 'wc-blocks-checkout' ],
			ASNP_WEPB_VERSION,
			true
		);

		wp_set_script_translations(
			'wepb-checkout-integration',
			'asnp-easy-product-bundles',
			ASNP_WEPB_ABSPATH . 'languages'
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return [ 'wepb-checkout-integration' ];
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return [];
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
	    return [];
	}

	public function get_url( $file, $ext ) {
		return plugins_url( $this->get_path( $ext ) . $file . '.' . $ext, ASNP_WEPB_PLUGIN_FILE );
    }

    protected function get_path( $ext ) {
        return 'css' === $ext ? 'assets/css/' : 'assets/js/';
    }

}
