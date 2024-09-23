<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Admin;

use AsanaPlugins\WooCommerce\ProductBundles;
use AsanaPlugins\WooCommerce\ProductBundles\Registry\Container;

defined( 'ABSPATH' ) || exit;

class Admin
{
    protected $container;

    public function __construct( Container $container ) {
        $this->container = $container;
    }

    public function init() {
        $this->register_dependencies();
		$this->black_friday();

        $this->container->get( Assets::class )->init();
		$this->container->get( Menu::class )->init();
		$this->container->get( ProductBundle::class )->init();

		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		add_filter( 'display_post_states', [ $this, 'display_post_states' ], 10, 2 );
    }

    protected function register_dependencies() {
		$this->container->register(
            Menu::class,
            function ( Container $container ) {
                return new Menu();
            }
        );
        $this->container->register(
            Assets::class,
            function ( Container $container ) {
                return new Assets();
            }
        );
		$this->container->register(
			ProductBundle::class,
			function ( Container $container ) {
				return new ProductBundle();
			}
		);
    }

	/**
	 * Plugin action links
	 * This function adds additional links below the plugin in admin plugins page.
	 *
	 * @since  1.0.0
	 *
	 * @param  array  $links    The array having default links for the plugin.
	 * @param  string $file     The name of the plugin file.
	 *
	 * @return array  $links    Plugin default links and specific links.
	 */
	public function plugin_action_links( $links, $file ) {
		if ( false === strpos( $file, 'easy-product-bundles.php' ) ) {
			return $links;
		}

		$extra = [ '<a href="' . admin_url( 'admin.php?page=asnp-product-bundles' ) . '">' . esc_html__( 'Settings', 'asnp-easy-product-bundles' ) . '</a>' ];

		if ( ! ProductBundles\is_pro_active() ) {
			$extra[] = '<a href="https://www.asanaplugins.com/product/woocommerce-product-bundles/?utm_source=easy-product-bundles-woocommerce-plugin&utm_campaign=go-pro&utm_medium=link" target="_blank" onMouseOver="this.style.color=\'#55ce5a\'" onMouseOut="this.style.color=\'#39b54a\'" style="color: #39b54a; font-weight: bold;">' . esc_html__( 'Go Pro', 'asnp-easy-product-bundles' ) . '</a>';
		}

		return array_merge( $links, $extra );
	}

	public function display_post_states( $states, $post ) {
		if ( 'product' !== get_post_type( $post->ID ) ) {
			return $states;
		}

		$product = wc_get_product( $post->ID );

		if ( $product && $product->is_type( ProductBundles\get_product_bundle_type() ) ) {
			$items = $product->get_items();
			$count = ! empty( $items ) ? count( $items ) : 0;

			$states[] = apply_filters( 'asnp_wepb_post_states', '<span class="asnp-wepb-post-state">' . sprintf( esc_html__( 'Bundle (%d)', 'asnp-easy-product-bundles' ), $count ) . '</span>', $count, $product );
		}

		return $states;
	}

	protected function black_friday() {
		if ( ProductBundles\is_pro_active() ) {
			return;
		}

		$name = 'asnp_wepb_black_friday_' . date( 'Y' );
		if ( (int) get_option( $name . '_added' ) ) {
			// Is Black Friday expired.
			if ( time() > strtotime( date( 'Y' ) . '-11-30' ) ) {
				\WC_Admin_Notices::remove_notice( $name );
				delete_option( $name . '_added' );
			}
			return;
		}

		if ( \WC_Admin_Notices::has_notice( $name ) ) {
			return;
		}

		// Is Black Friday applicable.
		if (
			time() < strtotime( date( 'Y' ) . '-11-20' ) ||
			time() > strtotime( date( 'Y' ) . '-11-30' )
		) {
			return;
		}

		\WC_Admin_Notices::add_custom_notice(
			$name,
			'<p>' . __( '<strong>Black Friday Exclusive:</strong> SAVE up to 50% & access to <strong>WooCommerce Product Bundles Pro</strong> features.', 'asnp-easy-product-bundles' ) . '<a class="button button-primary" style="margin-left: 10px; background: #5614d5; border-color: #5614d5;" target="_blank" href="https://asanaplugins.com/product/woocommerce-product-bundles/?utm_source=easy-product-bundles-woocommerce-plugin&utm_campaign=black-friday&utm_medium=link">' . __('Grab The Offer', 'asnp-easy-product-bundles') . '</a></p>'
		);

		update_option( $name . '_added', 1 );
	}
}
