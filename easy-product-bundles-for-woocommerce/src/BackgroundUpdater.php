<?php
namespace AsanaPlugins\WooCommerce\ProductBundles;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\WC_Background_Process', false ) ) {
	include_once WC_ABSPATH . 'includes/abstracts/class-wc-background-process.php';
}

/**
 * BackgroundUpdater Class.
 */
class BackgroundUpdater extends \WC_Background_Process {

	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		// Uses unique prefix per blog so each blog has separate queue.
		$this->prefix = 'wp_' . get_current_blog_id();
		$this->action = 'asnp_wepb_updater';

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
				sprintf( 'Unable to dispatch WooCommerce Product Bundle Builder updater: %s', $dispatched->get_error_message() ),
				array( 'source' => 'asnp_wepb_db_updates' )
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
	 * @param string $callback Update callback function.
	 * @return string|bool
	 */
	protected function task( $callback ) {
		if ( ! defined( 'ASNP_WEPB_UPDATING' ) ) {
			define( 'ASNP_WEPB_UPDATING', true );
		}

		$logger = wc_get_logger();

		include_once dirname( __FILE__ ) . '/Updates.php';

		$result = false;

		if ( is_callable( $callback ) ) {
			$logger->info( sprintf( 'Running %s callback', $callback ), array( 'source' => 'asnp_wepb_db_updates' ) );
			$result = (bool) call_user_func( $callback, $this );

			if ( $result ) {
				$logger->info( sprintf( '%s callback needs to run again', $callback ), array( 'source' => 'asnp_wepb_db_updates' ) );
			} else {
				$logger->info( sprintf( 'Finished running %s callback', $callback ), array( 'source' => 'asnp_wepb_db_updates' ) );
			}
		} else {
			$logger->notice( sprintf( 'Could not find %s callback', $callback ), array( 'source' => 'asnp_wepb_db_updates' ) );
		}

		return $result ? $callback : false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		$logger = wc_get_logger();
		$logger->info( 'Data update complete', array( 'source' => 'asnp_wepb_db_updates' ) );
		Install::update_db_version();
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
