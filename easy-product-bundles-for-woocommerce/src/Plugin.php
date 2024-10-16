<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

use AsanaPlugins\WooCommerce\ProductBundles\Registry\Container;
use AsanaPlugins\WooCommerce\ProductBundles\Admin\Admin;
use AsanaPlugins\WooCommerce\ProductBundles\API\RestApi;
use AsanaPlugins\WooCommerce\ProductBundles\Abstracts\ProductSelectorInterface;
use AsanaPlugins\WooCommerce\ProductBundles\ShortCode\ProductShortCode;
use AsanaPlugins\WooCommerce\ProductBundles\Compatibilities\Compatibility;
use AsanaPlugins\WooCommerce\ProductBundles\Models\SimpleBundleItemsModel;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	const PRODUCT_TYPE = 'easy_product_bundle';

	public $admin;

	public $settings;

	public $plugin_name;

	public $version;

    /**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	protected $container = null;

    /**
	 * Constructor
	 *
	 * @return void
	 */
    protected function __construct() {}

    /**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	public function container() {
		if ( ! $this->container instanceof Container ) {
			$this->container = new Container();
		}
		return $this->container;
	}

    public function init() {
        $this->define_constants();

        $this->plugin_name = 'easy-product-bundles';
        $this->version     = ASNP_WEPB_VERSION;

        register_activation_hook( ASNP_WEPB_PLUGIN_FILE, array( $this, 'on_activation' ) );
        register_deactivation_hook( ASNP_WEPB_PLUGIN_FILE, array( $this, 'on_deactivation' ) );
        if ( did_action( 'plugins_loaded' ) ) {
			$this->on_plugins_loaded();
		} else {
			add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), 1000 );
		}
    }

    /**
	 * Install DB and create cron events when activated.
	 *
	 * @return void
	 */
	public function on_activation() {

    }

    /**
	 * Remove WooCommerce Admin scheduled actions on deactivate.
	 *
	 * @return void
	 */
	public function on_deactivation() {

    }

    /**
	 * Setup plugin once all other plugins are loaded.
	 *
	 * @return void
	 */
	public function on_plugins_loaded() {
		$this->load_plugin_textdomain();

		if ( ! $this->has_satisfied_dependencies() ) {
			add_action( 'admin_init', array( $this, 'deactivate_self' ) );
			add_action( 'admin_notices', array( $this, 'render_dependencies_notice' ) );
			return;
		}

		// WooCommerce HPOS supported.
		add_action( 'before_woocommerce_init', function() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', ASNP_WEPB_PLUGIN_FILE, true );
			}
		} );

		$this->settings = new Settings();
		$this->register_dependencies();
		Install::init();
		Compatibility::init();
		add_action( 'init', array( $this, 'includes' ) );
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Package' ) && version_compare( \Automattic\WooCommerce\Blocks\Package::get_version(), '7.2.0', 'ge' ) ) {
			BlocksHooks::init();
		}
    }

    private function define_constants() {
        $this->define( 'ASNP_WEPB_ABSPATH', dirname( __DIR__ ) . '/' );
        $this->define( 'ASNP_WEPB_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
		$this->define( 'ASNP_WEPB_PLUGIN_FILE', ASNP_WEPB_ABSPATH . 'easy-product-bundles.php' );
    }

    /**
	 * Load Localisation files.
	 */
	protected function load_plugin_textdomain() {
		$locale = get_user_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'asnp-easy-product-bundles' ); // phpcs:ignore

		unload_textdomain( 'asnp-easy-product-bundles' );
		if ( false === load_textdomain( 'asnp-easy-product-bundles', WP_LANG_DIR . '/plugins/asnp-easy-product-bundles-' . $locale . '.mo' ) ) {
			load_textdomain( 'asnp-easy-product-bundles', WP_LANG_DIR . '/easy-product-bundles-for-woocommerce/asnp-easy-product-bundles-' . $locale . '.mo' );
		}
		load_plugin_textdomain( 'asnp-easy-product-bundles', false, basename( dirname( __DIR__ ) ) . '/languages' );
	}

	public function includes() {
		$this->container->get( RestApi::class );

		$this->admin = new Admin( $this->container );
		if ( is_admin() ) {
			$this->admin->init();
		}

		$this->container->get( Assets::class )->init();
		$this->container->get( ProductBundleHooks::class )->init();
		$this->container->get( SyncBundles::class );

		$this->add_shortcodes();
	}

	public function add_shortcodes() {
		add_shortcode( 'asnp_wepb_product', ProductShortCode::class . '::output' );
	}

	protected function register_dependencies() {
		$this->container()->register(
			SimpleBundleItemsModel::class,
			function( Container $container ) {
				return new SimpleBundleItemsModel();
			}
		);
		$this->container()->register(
			ProductSelectorInterface::class,
			function( Container $container ) {
				return new ProductSelector();
			}
		);
		$this->container()->register(
            RestApi::class,
            function ( Container $container ) {
                return new RestApi();
            }
		);
		$this->container()->register(
			ProductBundleHooks::class,
			function ( Container $container ) {
				return new ProductBundleHooks();
			}
		);
		$this->container()->register(
			Assets::class,
			function ( Container $container ) {
				return new Assets();
			}
		);
		$this->container()->register(
			SyncBundles::class,
			function ( Container $container ) {
				return new SyncBundles();
			}
		);
	}

	/**
	 * Get an array of dependency error messages.
	 *
	 * @return array
	 */
	protected function get_dependency_errors() {
		$errors                      = array();
		$wordpress_version           = get_bloginfo( 'version' );
		$minimum_wordpress_version   = '4.7';
		$minimum_woocommerce_version = '3.0';
		$woocommerce_minimum_met     = class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, $minimum_woocommerce_version, '>=' );
		$wordpress_minimum_met       = version_compare( $wordpress_version, $minimum_wordpress_version, '>=' );

		if ( ! $woocommerce_minimum_met ) {
			$errors[] = sprintf(
				/* translators: 1: URL of WooCommerce plugin, 2: The minimum WooCommerce version number */
				__( 'The Easy Product Bundles plugin requires <a href="%1$s">WooCommerce</a> %2$s or greater to be installed and active.', 'asnp-easy-product-bundles' ),
				'https://wordpress.org/plugins/woocommerce/',
				$minimum_woocommerce_version
			);
		}

		if ( ! $wordpress_minimum_met ) {
			$errors[] = sprintf(
				/* translators: 1: URL of WordPress.org, 2: The minimum WordPress version number */
				__( 'The Easy Product Bundles plugin requires <a href="%1$s">WordPress</a> %2$s or greater to be installed and active.', 'asnp-easy-product-bundles' ),
				'https://wordpress.org/',
				$minimum_wordpress_version
			);
		}

		return $errors;
	}

	/**
	 * Returns true if all dependencies for the wc-admin plugin are loaded.
	 *
	 * @return bool
	 */
	public function has_satisfied_dependencies() {
		$dependency_errors = $this->get_dependency_errors();
		return 0 === count( $dependency_errors );
	}

	/**
	 * Deactivates this plugin.
	 */
	public function deactivate_self() {
		deactivate_plugins( plugin_basename( ASNP_WEPB_PLUGIN_FILE ) );
		unset( $_GET['activate'] ); // phpcs:ignore CSRF ok.
	}

	/**
	 * Notify users of the plugin requirements.
	 */
	public function render_dependencies_notice() {
		$message = $this->get_dependency_errors();
		printf( '<div class="error"><p>%s</p></div>', implode( ' ', $message ) ); /* phpcs:ignore xss ok. */
	}

	/**
	 * What type of request is this?
	 *
	 * @since  1.0.0
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 *
	 * @return bool
	 */
	public function is_request( $type ) {
		switch ( $type ) {
			case 'admin' :
				return is_admin();
			case 'ajax' :
				return defined( 'DOING_AJAX' );
			case 'cron' :
				return defined( 'DOING_CRON' );
			case 'frontend' :
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	public function is_pro_active() {
		return defined( 'ASNP_WEPB_PRO_VERSION' );
	}

    /**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	protected function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
    }

    /**
	 * Prevent cloning.
	 */
	public function __clone() {}

	/**
	 * Prevent unserializing.
	 */
	public function __wakeup() {}
}
