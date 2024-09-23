<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

defined( 'ABSPATH' ) || exit;

class Install {

	/**
	 * Plugin version option name.
	 */
	const VERSION_OPTION = 'asnp_wepb_version';

	/**
	 * Background update class.
	 *
	 * @var object
	 */
	private static $background_updater;

	private static $db_updates = [
		'5.0.0' => [ __NAMESPACE__ . '\\maybe_sync_simple_bundles' ],
	];

	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'init', array( __CLASS__, 'init_background_updater' ), 5 );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
	}

	/**
	 * Init background updates.
	 *
	 * @since  5.0.0
	 *
	 * @return void
	 */
	public static function init_background_updater() {
		self::$background_updater = new BackgroundUpdater();
	}

	/**
	 * Check URL Coupons version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( defined( 'IFRAME_REQUEST' ) ) {
			return;
		}

		$version_option  = get_option( self::VERSION_OPTION );
		$requires_update = version_compare( get_option( self::VERSION_OPTION ), get_plugin()->version, '<' );

		if ( ! $version_option || $requires_update ) {
			self::install();
			do_action( 'asnp_wepb_updated' );
		}
	}

	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'asnp_wepb_installing' ) ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'asnp_wepb_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		if ( ! defined( 'ASNP_WEPB_INSTALLING' ) ) {
			define( 'ASNP_WEPB_INSTALLING', true );
		}

		self::create_tables();
		self::update_version();
		self::maybe_update_db_version();

		delete_transient( 'asnp_wepb_installing' );

		do_action( 'asnp_wepb_installed' );
	}

	public static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( self::get_schema() );
	}

	protected static function get_schema() {
		global $wpdb;

		$collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
CREATE TABLE {$wpdb->prefix}asnp_wepb_simple_bundle_items (
	id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	bundle_id BIGINT(20) UNSIGNED NOT NULL,
	product_id BIGINT(20) UNSIGNED NOT NULL,
	quantity BIGINT(20) UNSIGNED NOT NULL DEFAULT 1,
	PRIMARY KEY (id),
	KEY bundle_id (bundle_id),
    KEY product_id (product_id)
) $collate;";

		return $tables;
	}

	/**
	 * Update plugin version to current.
	 */
	private static function update_version() {
		update_option( self::VERSION_OPTION, get_plugin()->version );
	}

	private static function update() {
		$current_db_version = get_option( 'asnp_wepb_db_version' );
		$logger             = wc_get_logger();
		$update_queued      = false;

		foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					$logger->info(
						sprintf( 'Queuing %s - %s', $version, $update_callback ),
						array( 'source' => 'asnp_wepb_db_updates' )
					);
					self::$background_updater->push_to_queue( $update_callback );
					$update_queued = true;
				}
			}
		}

		if ( $update_queued ) {
			self::$background_updater->save()->dispatch();
		}
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since  5.0.0
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since  5.0.0
	 * @return boolean
	 */
	public static function needs_db_update() {
		$current_db_version = get_option( 'asnp_wepb_db_version', null );
		$updates            = self::get_db_update_callbacks();
		$update_versions    = array_keys( $updates );
		usort( $update_versions, 'version_compare' );

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, end( $update_versions ), '<' );
	}

	/**
	 * See if we need to show or run database updates during install.
	 *
	 * @since 1.0.0
	 */
	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			self::init_background_updater();
			self::update();
		} else {
			self::update_db_version();
		}
	}

	/**
	 * Update DB version to current.
	 *
	 * @param string|null $version New URL Coupons DB version or null.
	 */
	public static function update_db_version( $version = null ) {
		update_option( 'asnp_wepb_db_version', is_null( $version ) ? get_plugin()->version : $version );
	}

	/**
	 * Return a list of WooCommerce tables. Used to make sure all WC tables are dropped when uninstalling the plugin
	 * in a single site or multi site environment.
	 *
	 * @return array WC tables.
	 */
	public static function get_tables() {
		global $wpdb;

		$tables = array(
			"{$wpdb->prefix}asnp_wepb_simple_bundle_items",
		);

		/**
		 * Filter the list of known WooCommerce tables.
		 *
		 * If WooCommerce plugins need to add new tables, they can inject them here.
		 *
		 * @param array $tables An array of WooCommerce-specific database table names.
		 */
		$tables = apply_filters( 'asnp_wepb_install_get_tables', $tables );

		return $tables;
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 *
	 * @param array $tables List of tables that will be deleted by WP.
	 *
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		return array_merge( $tables, self::get_tables() );
	}
}
