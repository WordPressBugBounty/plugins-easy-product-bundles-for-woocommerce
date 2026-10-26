<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Compatibilities;

defined( 'ABSPATH' ) || exit;

/**
 * Cache and Optimization plugin compatibility class.
 *
 * Provides compatibility with WP Rocket, LiteSpeed Cache, Perfmatters,
 * FlyingPress, Autoptimize, WP-Optimize, and SG Optimizer.
 * Ensures Used CSS (UCSS) generators do not strip dynamic React modal/bundle styles.
 */
class Cache {

	/**
	 * Init compatibility.
	 */
	public static function init() {
		// WP Rocket
		// add_filter( 'rocket_exclude_js', [ __CLASS__, 'exclude_js' ] );
		// add_filter( 'rocket_exclude_css', [ __CLASS__, 'exclude_css' ] );
		add_filter( 'rocket_rucss_safelist', [ __CLASS__, 'rocket_rucss_safelist' ] );
		add_filter( 'rocket_rucss_inline_styles', [ __CLASS__, 'rocket_rucss_inline_styles' ] );

		// LiteSpeed Cache
		// add_filter( 'litespeed_optimize_js_excludes', [ __CLASS__, 'exclude_js' ] );
		// add_filter( 'litespeed_optimize_css_excludes', [ __CLASS__, 'exclude_css' ] );
		add_filter( 'litespeed_ucss_whitelist', [ __CLASS__, 'litespeed_ucss_whitelist' ] );

		// Perfmatters
		// add_filter( 'perfmatters_delay_js_exclusions', [ __CLASS__, 'exclude_js' ] );
		// add_filter( 'perfmatters_defer_js_exclusions', [ __CLASS__, 'exclude_js' ] );
		// add_filter( 'perfmatters_rucss_excluded_stylesheets', [ __CLASS__, 'exclude_css' ] );
		add_filter( 'perfmatters_used_css_exclusions', [ __CLASS__, 'perfmatters_used_css_exclusions' ] );

		// FlyingPress
		add_filter( 'flying_press_exclude_from_used_css', [ __CLASS__, 'flying_press_used_css_exclusions' ] );

		// Autoptimize
		// add_filter( 'autoptimize_filter_js_exclude', [ __CLASS__, 'exclude_js' ] );
		// add_filter( 'autoptimize_filter_css_exclude', [ __CLASS__, 'exclude_css' ] );

		// WP-Optimize
		// add_filter( 'wp-optimize-minify-default-exclusions', [ __CLASS__, 'exclude_all' ] );

		// SG Optimizer
		// add_filter( 'sgo_js_minify_exclude', [ __CLASS__, 'exclude_sg_optimizer_js' ] );
		// add_filter( 'sgo_js_async_exclude', [ __CLASS__, 'exclude_sg_optimizer_js' ] );
		// add_filter( 'sgo_javascript_combine_exclude', [ __CLASS__, 'exclude_sg_optimizer_js' ] );
		// add_filter( 'sgo_css_minify_exclude', [ __CLASS__, 'exclude_sg_optimizer_css' ] );
		// add_filter( 'sgo_css_combine_exclude', [ __CLASS__, 'exclude_sg_optimizer_css' ] );

		// Purge caches on plugin update or settings save.
		add_action( 'asnp_wepb_settings_saved', [ __CLASS__, 'purge_cache' ] );
		add_action( 'asnp_wepb_updated', [ __CLASS__, 'purge_cache' ] );
	}

	/**
	 * Safelist CSS selectors from WP Rocket Remove Unused CSS (RUCSS).
	 *
	 * Protects dynamic React components, modals, popups, and tooltips from being stripped.
	 *
	 * @param array $safelist Safelist patterns.
	 * @return array
	 */
	public static function rocket_rucss_safelist( $safelist ) {
		$safelist = is_array( $safelist ) ? $safelist : [];

		$plugin_selectors = [
			'^.asnp-.*',
			'^.tippy-.*',
		];

		return array_unique( array_merge( $safelist, $plugin_selectors ) );
	}

	/**
	 * Safelist inline styles from WP Rocket Remove Unused CSS (RUCSS).
	 *
	 * @param array $inline_styles Inline style identifiers.
	 * @return array
	 */
	public static function rocket_rucss_inline_styles( $inline_styles ) {
		$inline_styles = is_array( $inline_styles ) ? $inline_styles : [];

		$plugin_inline_styles = [
			'asnp-wepb-inline-style',
			'asnp-easy-product-bundles-shared',
			'asnp-easy-product-bundles-product-bundle',
			'asnp-easy-product-bundles-pro-product-bundle',
		];

		return array_unique( array_merge( $inline_styles, $plugin_inline_styles ) );
	}

	/**
	 * Whitelist CSS selectors from LiteSpeed Cache UCSS.
	 *
	 * @param array $whitelist Whitelist selectors.
	 * @return array
	 */
	public static function litespeed_ucss_whitelist( $whitelist ) {
		$whitelist = is_array( $whitelist ) ? $whitelist : [];

		$plugin_selectors = [
			'asnp-',
			'tippy-',
		];

		return array_unique( array_merge( $whitelist, $plugin_selectors ) );
	}

	/**
	 * Exclude CSS selectors from Perfmatters Used CSS.
	 *
	 * @param array $exclusions Excluded selectors.
	 * @return array
	 */
	public static function perfmatters_used_css_exclusions( $exclusions ) {
		$exclusions = is_array( $exclusions ) ? $exclusions : [];

		$plugin_selectors = [
			'.asnp-',
			'.tippy-',
		];

		return array_unique( array_merge( $exclusions, $plugin_selectors ) );
	}

	/**
	 * Exclude CSS selectors from FlyingPress Used CSS.
	 *
	 * @param array $exclusions Excluded selectors.
	 * @return array
	 */
	public static function flying_press_used_css_exclusions( $exclusions ) {
		$exclusions = is_array( $exclusions ) ? $exclusions : [];

		$plugin_selectors = [
			'.asnp-',
			'.tippy-',
		];

		return array_unique( array_merge( $exclusions, $plugin_selectors ) );
	}

	/**
	 * Exclude JS from cache plugins.
	 *
	 * @param array|string $exclusions Exclusions.
	 * @return array|string
	 */
	public static function exclude_js( $exclusions ) {
		$plugin_js_exclusions = [
			'easy-product-bundles-for-woocommerce',
			'easy-product-bundles-for-woocommerce-pro',
			'easyProductBundlesData',
		];

		if ( \is_array( $exclusions ) ) {
			return array_merge( $exclusions, $plugin_js_exclusions );
		}

		if ( \is_string( $exclusions ) ) {
			if ( ! empty( $exclusions ) ) {
				$exclusions .= ', ';
			}

			return $exclusions . implode( ', ', $plugin_js_exclusions );
		}

		return $exclusions;
	}

	/**
	 * Exclude CSS from cache plugins.
	 *
	 * @param array|string $exclusions Exclusions.
	 * @return array|string
	 */
	public static function exclude_css( $exclusions ) {
		$plugin_css_exclusions = [
			'easy-product-bundles-for-woocommerce',
			'easy-product-bundles-for-woocommerce-pro',
		];

		if ( \is_array( $exclusions ) ) {
			return array_merge( $exclusions, $plugin_css_exclusions );
		}

		if ( \is_string( $exclusions ) ) {
			if ( ! empty( $exclusions ) ) {
				$exclusions .= ', ';
			}

			return $exclusions . implode( ', ', $plugin_css_exclusions );
		}

		return $exclusions;
	}

	/**
	 * Exclude all assets.
	 *
	 * @param array|string $exclusions Exclusions.
	 * @return array|string
	 */
	public static function exclude_all( $exclusions ) {
		$exclusions = self::exclude_js( $exclusions );
		$exclusions = self::exclude_css( $exclusions );
		return $exclusions;
	}

	/**
	 * Exclude JS from SG Optimizer.
	 *
	 * @param array $exclusions Exclusions.
	 * @return array
	 */
	public static function exclude_sg_optimizer_js( $exclusions ) {
		$plugin_js_exclusions = [
			'asnp-easy-product-bundles-shared',
			'asnp-easy-product-bundles-product-bundle',
			'asnp-easy-product-bundles-pro-utils',
			'asnp-easy-product-bundles-pro-product-bundle',
			'asnp-wepb-checkout-integration',
		];

		return array_unique( array_merge( $exclusions, $plugin_js_exclusions ) );
	}

	/**
	 * Exclude CSS from SG Optimizer.
	 *
	 * @param array $exclusions Exclusions.
	 * @return array
	 */
	public static function exclude_sg_optimizer_css( $exclusions ) {
		$plugin_css_exclusions = [
			'asnp-easy-product-bundles-shared',
			'asnp-easy-product-bundles-product-bundle',
			'asnp-easy-product-bundles-pro-product-bundle',
			'asnp-wepb-checkout-integration',
		];

		return array_unique( array_merge( $exclusions, $plugin_css_exclusions ) );
	}

	/**
	 * Exclude from W3TC tag minification.
	 *
	 * @param bool   $do_tag_minification Do minification.
	 * @param string $tag                 Tag content.
	 * @param string $file                File path.
	 * @return bool
	 */
	public static function exclude_w3tc_tag_minification( $do_tag_minification, $tag, $file ) {
		if ( false === $do_tag_minification ) {
			return $do_tag_minification;
		}

		$plugin_exclusions = [
			'easy-product-bundles-for-woocommerce',
			'easy-product-bundles-for-woocommerce-pro',
			'easyProductBundlesData',
		];

		foreach ( $plugin_exclusions as $exclusion ) {
			if ( false !== strpos( $tag, $exclusion ) || false !== strpos( $file, $exclusion ) ) {
				return false;
			}
		}

		return $do_tag_minification;
	}

	/**
	 * Purge all caches across supported optimization plugins.
	 *
	 * Clears minified asset caches and page caches when settings change or plugin updates.
	 *
	 * @return void
	 */
	public static function purge_cache() {
		// WP Rocket
		if ( function_exists( 'rocket_clean_minify' ) ) {
			rocket_clean_minify();
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		// LiteSpeed Cache
		if ( defined( 'LSCWP_V' ) || class_exists( '\LiteSpeed\Purge' ) ) {
			do_action( 'litespeed_purge_all' );
		}

		// Autoptimize
		if ( class_exists( 'autoptimizeCache' ) && is_callable( [ 'autoptimizeCache', 'clearall' ] ) ) {
			\autoptimizeCache::clearall();
		}

		// WP-Optimize
		if ( function_exists( 'WP_Optimize' ) && method_exists( \WP_Optimize(), 'get_page_cache' ) ) {
			$page_cache = \WP_Optimize()->get_page_cache();
			if ( $page_cache && method_exists( $page_cache, 'purge' ) ) {
				$page_cache->purge();
			}
		}

		// Perfmatters
		if ( class_exists( 'Perfmatters\CSS' ) && method_exists( 'Perfmatters\CSS', 'clear_used_css' ) ) {
			\Perfmatters\CSS::clear_used_css();
		}

		// FlyingPress
		if ( class_exists( 'FlyingPress\Purge' ) && method_exists( 'FlyingPress\Purge', 'purge_everything' ) ) {
			\FlyingPress\Purge::purge_everything();
		}
	}

}
