<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\WC_Background_Process', false ) ) {
	include_once WC_ABSPATH . 'includes/abstracts/class-wc-background-process.php';
}

class BackgroundProductSync extends \WC_Background_Process {

	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		// Uses unique prefix per blog so each blog has separate queue.
		$this->prefix = 'wp_' . get_current_blog_id();
		$this->action = 'asnp_wepb_product_sync';

		parent::__construct();
	}

	/**
	 * Dispatch updater.
	 *
	 * Updater will still run via cron job if this fails for any reason.
	 */
	public function dispatch() {
		$dispatched = parent::dispatch();
		$logger     = wc_get_logger();

		if ( is_wp_error( $dispatched ) ) {
			$logger->error(
				sprintf( 'Unable to dispatch Product Bundle Builder price updater: %s', $dispatched->get_error_message() ),
				array( 'source' => 'asnp_wepb_product_sync' )
			);
		}
	}

	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck() {
		if ( $this->is_process_running() ) {
			// Background process already running.
			return;
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			$this->clear_scheduled_event();
			return;
		}

		$this->handle();
	}

	/**
	 * Schedule fallback event.
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Is the updater running?
	 *
	 * @return boolean
	 */
	public function is_updating() {
		return false === $this->is_queue_empty();
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param WC_Product|int $product Product object or ID for which you wish to sync.
	 * @return string|bool
	 */
	protected function task( $item ) {
		$item = array_merge( [ 'add_items' => 0 ], $item );
		if ( empty( $item['product'] ) || 0 >= (int) $item['product'] ) {
			return false;
		}

		$product = wc_get_product( $item['product'] );
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return;
		}

		if ( (int) $item['add_items'] ) {
			add_simple_bundle_items( $product );
		}

		$product->sync( $product );

		return false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		$logger = wc_get_logger();
		$logger->info( 'Product bundles price update complete', array( 'source' => 'asnp_wepb_product_sync' ) );
		parent::complete();
	}

	/**
	 * See if the batch limit has been exceeded.
	 *
	 * @return bool
	 */
	public function is_memory_exceeded() {
		return $this->memory_exceeded();
	}

}
