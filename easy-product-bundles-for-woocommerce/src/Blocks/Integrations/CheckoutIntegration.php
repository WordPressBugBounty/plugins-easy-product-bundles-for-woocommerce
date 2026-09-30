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
		return 'asnp-wepb-checkout-integration';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		static $registered = false;
		if ( $registered ) {
			return;
		}
		$registered = true;

		$script_args = function_exists( 'wp_enqueue_script_module' ) || version_compare( get_bloginfo( 'version' ), '6.3', '>=' )
			? [ 'in_footer' => true, 'strategy' => 'defer' ]
			: true;

		wp_register_script(
			'asnp-wepb-checkout-integration',
			$this->get_url( 'checkout-integration/index', 'js' ),
			[ 'wc-blocks-checkout' ],
			ASNP_WEPB_VERSION,
			$script_args
		);


		add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after', [ $this, 'enqueue_block_styles' ] );
		add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after', [ $this, 'enqueue_block_styles' ] );
	}

	/**
	 * Enqueue styles when cart or checkout block is rendered.
	 */
	public function enqueue_block_styles() {
		if ( ! is_admin() ) {
			wp_enqueue_style(
				'asnp-wepb-checkout-integration',
				$this->get_url( 'checkout-integration/style', 'css' ),
				[],
				ASNP_WEPB_VERSION
			);
		}
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return [ 'asnp-wepb-checkout-integration' ];
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
