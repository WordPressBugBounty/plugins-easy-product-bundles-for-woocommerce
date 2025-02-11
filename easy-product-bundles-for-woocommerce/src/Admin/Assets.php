<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Admin;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles;
use AsanaPlugins\WooCommerce\ProductBundles\Plugin;
use AsanaPlugins\WooCommerce\ProductBundles\Models\ItemsModel;

class Assets
{
    public function init() {
        add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
    }

    public function load_scripts() {
        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if ( 'product' === $screen_id ) {
			ProductBundles\register_polyfills();

            wp_enqueue_style(
                'asnp-easy-product-bundles-product',
                $this->get_url( 'admin/product/style', 'css' )
            );
            wp_enqueue_script(
                'asnp-easy-product-bundles-product',
                $this->get_url( 'admin/product/index', 'js' ),
                array(
					'react-dom',
					'wp-hooks',
					'wp-i18n',
					'wp-api-fetch',
					'jquery-tiptip',
				),
                ASNP_WEPB_VERSION,
                true
            );

			wp_localize_script(
				'asnp-easy-product-bundles-product',
				'easyProductBundlesData',
				array(
					'bundle'      => $this->get_bundle(),
					'pro_active'  => Plugin::instance()->is_pro_active(),
					'show_review' => ProductBundles\maybe_show_review(),
					'plugin_url'  => ASNP_WEPB_PLUGIN_URL,
				)
			);

			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'asnp-easy-product-bundles-product', 'asnp-easy-product-bundles', ASNP_WEPB_ABSPATH . 'languages' );
			}
        } elseif ( 'toplevel_page_asnp-product-bundles' === $screen_id ) {
			ProductBundles\register_polyfills();

			wp_enqueue_style(
				'asnp-easy-product-bundles-admin',
				$this->get_url( 'admin/admin/style', 'css' )
			);
			wp_enqueue_script(
				'asnp-easy-product-bundles-admin',
				$this->get_url( 'admin/admin/index', 'js' ),
				array(
					'react-dom',
					'wp-hooks',
					'wp-i18n',
					'wp-api-fetch',
				),
				ASNP_WEPB_VERSION,
				true
			);

			wp_localize_script(
				'asnp-easy-product-bundles-admin',
				'easyProductBundlesData',
				array(
					'pro_active'  => Plugin::instance()->is_pro_active(),
					'show_review' => ProductBundles\maybe_show_review(),
					'plugin_url'  => ASNP_WEPB_PLUGIN_URL,
				)
			);

			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'asnp-easy-product-bundles-admin', 'asnp-easy-product-bundles', ASNP_WEPB_ABSPATH . 'languages' );
			}
		} elseif ( 'dashboard' === $screen_id ) {
			$this->show_review();
		}
    }

    public function get_url( $file, $ext ) {
		return plugins_url( $this->get_path( $ext ) . $file . '.' . $ext, ASNP_WEPB_PLUGIN_FILE );
    }

    protected function get_path( $ext ) {
        return 'css' === $ext ? 'assets/css/' : 'assets/js/';
    }

	protected function get_bundle() {
		global $post;

		if ( ! $post || 0 >= $post->ID ) {
			return null;
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return null;
		}

		$items = $product->get_items();
		if ( ! empty( $items ) ) {
			foreach ( $items as &$item ) {
				if ( ! empty( $item['products'] ) ) {
					$item['products'] = ItemsModel::get_products( array( 'include' => array_map( 'absint', $item['products'] ) ) );
				}

				if ( ! empty( $item['excluded_products'] ) ) {
					$item['excluded_products'] = ItemsModel::get_products( array( 'include' => array_map( 'absint', $item['excluded_products'] ) ) );
				}

				if ( ! empty( $item['product'] ) ) {
					$item['product'] = ItemsModel::get_products( array( 'type' => array( 'simple', 'variation' ), 'include' => array( absint( $item['product'] ) ) ) );
					$item['product'] = ! empty( $item['product'] ) ? $item['product'][0] : '';
				}

				$item = apply_filters( 'asnp_wepb_get_bundle_item_data', $item, $product );
			}
		}

		return [
			'individual_theme'         => $product->get_individual_theme(),
			'theme'                    => $product->get_theme(),
			'theme_size'               => $product->get_theme_size(),
			'fixed_price'              => $product->get_fixed_price(),
			'include_parent_price'     => $product->get_include_parent_price(),
			// 'edit_in_cart'             => $product->get_edit_in_cart(),
			'shipping_fee_calculation' => $product->get_shipping_fee_calculation(),
			'custom_display_price'     => $product->get_custom_display_price(),
			'min_items_quantity'       => $product->get_min_items_quantity(),
			'max_items_quantity'       => $product->get_max_items_quantity(),
			'bundle_title'             => $product->get_bundle_title(),
			'bundle_description'       => $product->get_bundle_description(),
			'hide_items_price'         => $product->get_hide_items_price(),
			'sync_stock_quantity'      => $product->get_sync_stock_quantity(),
			'bundles'                  => ! empty( $items ) ? $items : [],
		];
	}

	protected function show_review() {
		if ( ! ProductBundles\maybe_show_review() ) {
			return;
		}

		ProductBundles\register_polyfills();
		wp_enqueue_style(
			'asnp-easy-product-bundles-review',
			$this->get_url( 'admin/review/style', 'css' )
		);
		wp_enqueue_script(
			'asnp-easy-product-bundles-review',
			$this->get_url( 'admin/review/index', 'js' ),
			array(
				'react-dom',
				'wp-i18n',
				'wp-api-fetch',
			),
			ASNP_WEPB_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'asnp-easy-product-bundles-review', 'asnp-easy-product-bundles', ASNP_WEPB_ABSPATH . 'languages' );
		}
	}

}
