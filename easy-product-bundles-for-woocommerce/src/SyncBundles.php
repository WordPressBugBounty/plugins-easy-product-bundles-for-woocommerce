<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles\Models\SimpleBundleItemsModel;

class SyncBundles {

	protected $background_sync;

	protected $bundles_to_sync = [];

	public function __construct() {
		// Sync simple bundle with childs.
		$this->background_sync = new BackgroundProductSync();
		add_action( 'woocommerce_update_product', [ $this, 'sync_bundle_on_child_update' ], 10, 2 );
		add_action( 'woocommerce_delete_product', [ $this, 'sync_bundle_on_child_delete' ] );
		add_action( 'woocommerce_delete_product_variation', [ $this, 'sync_bundle_on_child_delete' ] );
		add_action( 'woocommerce_settings_save_general', [ $this, 'general_settings_updated' ], 5 );
		add_action( 'woocommerce_settings_save_tax', [ $this, 'tax_settings_updated' ], 5 );
		add_action( 'wp_ajax_woocommerce_tax_rates_save_changes', [ $this, 'tax_rates_save_changes' ], 9 );
		add_action( 'shutdown', [ $this, 'maybe_sync_bundles' ] );
		add_action( 'before_delete_post', [ $this, 'delete_simple_bundle' ] );
	}

	public function sync_bundle_on_child_update( $product_id, $product ) {
		if ( $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return;
		}

		if ( $product->is_type( 'variable' ) ) {
			return;
		}

		$type = $product->get_type();
		if (
			false !== strpos( $type, 'bundle' )
			|| false !== strpos( $type, 'group' )
			|| false !== strpos( $type, 'composite' )
			|| false !== strpos( $type, 'booking' )
		) {
			return;
		}

		$model = get_plugin()->container()->get( SimpleBundleItemsModel::class );
		$bundles = $model->get_product_parents( $product_id );

		if ( empty( $bundles ) ) {
			return;
		}

		foreach ( $bundles as $bundle ) {
			if ( ! in_array( $bundle, $this->bundles_to_sync ) ) {
				$this->bundles_to_sync[] = $bundle;
			}
		}
	}

	public function sync_bundle_on_child_delete( $product_id ) {
		$model = get_plugin()->container()->get( SimpleBundleItemsModel::class );
		$bundles = $model->get_product_parents( $product_id );

		if ( empty( $bundles ) ) {
			return;
		}

		foreach ( $bundles as $bundle ) {
			if ( ! in_array( $bundle, $this->bundles_to_sync ) ) {
				$this->bundles_to_sync[] = $bundle;
			}
		}
	}

	public function maybe_sync_all_bundles() {
		$model = get_plugin()->container()->get( SimpleBundleItemsModel::class );
		$bundles = $model->get_bundles();
		if ( empty( $bundles ) ) {
			return;
		}

		// First lets cancel existing running queue to avoid running it more than once.
		$this->background_sync->kill_process();

		foreach ( $bundles as $bundle ) {
			$this->background_sync->push_to_queue( [ 'product' => $bundle ] );
		}

		// Lets dispatch the queue to start processing.
		$this->background_sync->save()->dispatch();
	}

	public function tax_rates_save_changes() {
		if ( ! isset( $_POST['wc_tax_nonce'], $_POST['changes'] ) ) {
			return;
		}

		$current_class = ! empty( $_POST['current_class'] ) ? wp_unslash( $_POST['current_class'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! wp_verify_nonce( wp_unslash( $_POST['wc_tax_nonce'] ), 'wc_tax_nonce-class:' . $current_class ) ) {
			return;
		}

		// Check User Caps.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Delay syncing bundles until after the AJAX request completes.
		add_action( 'shutdown', [ $this, 'maybe_sync_all_bundles' ], 999 );
	}

	public function general_settings_updated() {
		$new_tax_setting   = isset( $_POST['woocommerce_calc_taxes'] ) ? 'yes' : 'no';
		$saved_tax_setting = get_option( 'woocommerce_calc_taxes' );

		if ( $new_tax_setting !== $saved_tax_setting ) {
			add_action( 'shutdown', [ $this, 'maybe_sync_all_bundles' ], 999 );
		}
	}

	public function tax_settings_updated() {
		$settings = [
			'woocommerce_prices_include_tax',
			'woocommerce_tax_based_on',
			'woocommerce_tax_display_shop',
			'woocommerce_price_display_suffix',
			'woocommerce_tax_round_at_subtotal',
		];

		$chnaged = false;
		foreach ( $settings as $option_name ) {
			if ( 'woocommerce_tax_round_at_subtotal' === $option_name ) {
				$new_value = isset( $_POST[ $option_name ] ) ? 'yes' : 'no';
			} else {
				$new_value = isset( $_POST[ $option_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $option_name ] ) ) : null;
			}

            $saved_value = get_option( $option_name );

            if ( $new_value !== $saved_value ) {
                $chnaged = true;
                break;
            }
        }

		if ( $chnaged ) {
			add_action( 'shutdown', [ $this, 'maybe_sync_all_bundles' ], 999 );
		}
	}

	public function maybe_sync_bundles() {
		if ( empty( $this->bundles_to_sync ) ) {
			return;
		}

		foreach ( $this->bundles_to_sync as $bundle ) {
			$this->background_sync->push_to_queue( [ 'product' => $bundle ] );
		}

		$this->background_sync->save()->dispatch();
	}

	public function delete_simple_bundle( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return;
		}

		$model = get_plugin()->container()->get( SimpleBundleItemsModel::class );
		$model->delete_bundle( $product_id );
	}

}
