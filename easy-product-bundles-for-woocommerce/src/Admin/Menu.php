<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Admin;

defined( 'ABSPATH' ) || exit;

class Menu
{

	protected $menus = array();

	public function init() {
		add_action( 'admin_menu', array( $this, 'menus' ) );
	}

	/**
	 * Getting all of admin-face menus of plugin.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_menus() {
		return $this->menus;
	}

	public function menus() {
		$this->menus['product_bundles'] = add_menu_page(
			__( 'Product Bundles', 'asnp-easy-product-bundles' ),
			__( 'Product Bundles', 'asnp-easy-product-bundles' ),
			apply_filters( 'asnp_wepb_product_bundles_menu_capability', 'manage_options' ),
			'asnp-product-bundles',
			array( $this, 'create_menu' ),
			ASNP_WEPB_PLUGIN_URL . 'assets/images/menu-icon.png',
			55.4
		);
	}

	public function create_menu() {
		?>
		<div id="asnp-product-bundles-wrapper" class="asnp-product-bundles-wrapper">
			<div id="asnp-product-bundles"></div>
		</div>
		<?php
	}

}
