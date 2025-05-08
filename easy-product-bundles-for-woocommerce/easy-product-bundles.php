<?php
/**
 * @wordpress-plugin
 * Plugin Name: Easy Product Bundles for WooCommerce
 * Plugin URI: https://www.asanaplugins.com/product/woocommerce-product-bundles/?utm_source=easy-product-bundles-woocommerce-plugin&utm_campaign=easy-product-bundles-woocommerce&utm_medium=link
 * Description: Create product bundles in WooCommerce easily
 * Tags: woocommerce, product bundles, bundled products
 * Version: 6.3.4
 * Author: Product Bundles Team
 * Author URI: https://www.asanaplugins.com/
 * License: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: asnp-easy-product-bundles
 * Domain Path: /languages
 * WC requires at least: 3.0
 * WC tested up to: 9.8.4
 *
 * Copyright 2024 Asana Plugins (https://www.asanaplugins.com/)
 */

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles\Plugin;

// Plugin version.
if ( ! defined( 'ASNP_WEPB_VERSION' ) ) {
	define( 'ASNP_WEPB_VERSION', '6.3.4' );
}

/**
 * Autoload packages.
 *
 * We want to fail gracefully if `composer install` has not been executed yet, so we are checking for the autoloader.
 * If the autoloader is not present, let's log the failure and display a nice admin notice.
 */
$autoloader = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $autoloader ) ) {
	require $autoloader;
} else {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log(  // phpcs:ignore
			sprintf(
				/* translators: 1: composer command. 2: plugin directory */
				esc_html__( 'Your installation of the Easy Product Bundles plugin is incomplete. Please run %1$s within the %2$s directory.', 'asnp-easy-product-bundles' ),
				'`composer install`',
				'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
			)
		);
	}
	/**
	 * Outputs an admin notice if composer install has not been ran.
	 */
	add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: 1: composer command. 2: plugin directory */
						esc_html__( 'Your installation of the Easy Product Bundles plugin is incomplete. Please run %1$s within the %2$s directory.', 'asnp-easy-product-bundles' ),
						'<code>composer install</code>',
						'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

/**
 * The main function for that returns Plugin
 *
 * The main function responsible for returning the one true Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = ASNP_WEPB(); ?>
 *
 * @since  1.0.0
 * @return object|Plugin The one true Plugin Instance.
 */
function ASNP_WEPB() {
	return Plugin::instance();
}
ASNP_WEPB()->init();
