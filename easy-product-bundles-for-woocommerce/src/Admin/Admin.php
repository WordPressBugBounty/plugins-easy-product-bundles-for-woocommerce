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
		$this->handle_offers();

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

	protected function add_offer_notice( $offer_name, $start_date, $end_date, $message, $button_label, $button_url, $button_color = '#0071a1' ) {
		if ( ProductBundles\is_pro_active() ) {
			return;
		}

		$name = 'asnp_wepb_' . $offer_name . '_' . date( 'Y' );
		if ( (int) get_option( $name . '_added' ) ) {
			// Is the offer expired.
			if ( time() > strtotime( $end_date . ' 23:59:59' ) ) {
				\WC_Admin_Notices::remove_notice( $name );
				delete_option( $name . '_added' );
			}
			return;
		}

		if ( \WC_Admin_Notices::has_notice( $name ) ) {
			return;
		}

		// Is the offer applicable.
		if (
			time() < strtotime( $start_date . ' 00:00:00' ) ||
			time() > strtotime( $end_date . ' 23:59:59' )
		) {
			return;
		}

		\WC_Admin_Notices::add_custom_notice(
			$name,
			'<p>' . $message . '<a class="button button-primary" style="margin-left: 10px; background: ' . esc_attr( $button_color ) . '; border-color: ' . esc_attr( $button_color ) . ';" target="_blank" href="' . esc_url( $button_url ) . '">' .
				esc_html( $button_label ) .
				'</a></p>'
		);

		update_option( $name . '_added', 1 );
	}

	protected function handle_offers() {
		$this->add_offer_notice(
			'black_friday',
			date( 'Y' ) . '-11-20',
			date( 'Y' ) . '-11-30',
			'<strong>Black Friday Exclusive:</strong> SAVE up to 75% & get access to <strong>WooCommerce Product Bundles Pro</strong> features.',
			'Grab The Offer',
			'https://asanaplugins.com/product/woocommerce-product-bundles/?utm_source=easy-product-bundles-woocommerce-plugin&utm_campaign=black-friday&utm_medium=link',
			'#5614d5'
		);

		$this->add_offer_notice(
			'cyber_monday',
			date( 'Y' ) . '-12-01',
			date( 'Y' ) . '-12-10',
			'<strong>Cyber Monday Exclusive:</strong> Save up to 75% on <strong>WooCommerce Product Bundles Pro</strong>. Limited Time Only!',
			'Claim Your Deal',
			'https://asanaplugins.com/product/woocommerce-product-bundles/?utm_source=easy-product-bundles-woocommerce-plugin&utm_campaign=cyber-monday&utm_medium=link',
			'#00aaff'
		);

		$this->add_offer_notice(
			'holiday_discount',
			date( 'Y' ) . '-12-11',
			date( 'Y' ) . '-12-31',
			'<strong>Holiday Cheer:</strong> Get up to 75% OFF <strong>WooCommerce Product Bundles Pro</strong> this festive season.',
			'Shop Now',
			'https://asanaplugins.com/product/woocommerce-product-bundles/?utm_source=easy-product-bundles-woocommerce-plugin&utm_campaign=holiday-discount&utm_medium=link',
			'#28a745'
		);

		$this->add_offer_notice(
			'new_year_sale',
			date( 'Y' ) . '-01-01',
			date( 'Y' ) . '-01-10',
			'<strong>New Year Sale:</strong> Kickstart your projects with up to 75% OFF <strong>WooCommerce Product Bundles Pro</strong>!',
			'Get The Deal',
			'https://asanaplugins.com/product/woocommerce-product-bundles/?utm_source=easy-product-bundles-woocommerce-plugin&utm_campaign=new-year-sale&utm_medium=link',
			'#ff5733'
		);

		$this->add_offer_notice(
			'spring_sale',
			date( 'Y' ) . '-03-20',
			date( 'Y' ) . '-03-30',
			'<strong>Spring Into Savings:</strong> Get up to 75% OFF <strong>WooCommerce Product Bundles Pro</strong>. Refresh your store this season!',
			'Spring Deals',
			'https://asanaplugins.com/product/woocommerce-product-bundles/?utm_source=easy-product-bundles-woocommerce-plugin&utm_campaign=spring-sale&utm_medium=link',
			'#5cb85c'
		);

		$this->add_offer_notice(
			'summer_sale',
			date( 'Y' ) . '-06-15',
			date( 'Y' ) . '-06-25',
			'<strong>Sizzling Summer Sale:</strong> Save up to 75% on <strong>WooCommerce Product Bundles Pro</strong>. Limited time only!',
			'Cool Deals',
			'https://asanaplugins.com/product/woocommerce-product-bundles/?utm_source=easy-product-bundles-woocommerce-plugin&utm_campaign=summer-sale&utm_medium=link',
			'#ff5733'
		);

		$this->add_offer_notice(
			'halloween_sale',
			date( 'Y' ) . '-10-25',
			date( 'Y' ) . '-10-31',
			'<strong>Halloween Spooktacular:</strong> Scare away high prices! Get up to 75% OFF <strong>WooCommerce Product Bundles Pro</strong>. No tricks, just treats!',
			'Shop Spooky Deals',
			'https://asanaplugins.com/product/woocommerce-product-bundles/?utm_source=easy-product-bundles-woocommerce-plugin&utm_campaign=halloween-sale&utm_medium=link',
			'#ff4500'
		);
	}

}
