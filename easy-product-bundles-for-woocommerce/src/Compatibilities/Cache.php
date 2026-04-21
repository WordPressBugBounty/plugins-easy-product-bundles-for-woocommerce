<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Compatibilities;

defined( 'ABSPATH' ) || exit;

/**
 * Cache compatibility class.
 */
class Cache {

	/**
	 * Init compatibility.
	 */
	public static function init() {
		// WP Rocket
		add_filter( 'rocket_exclude_js', [ __CLASS__, 'exclude_js' ] );
		add_filter( 'rocket_exclude_css', [ __CLASS__, 'exclude_css' ] );

		// Perfmatters
		add_filter( 'perfmatters_delay_js_exclusions', [ __CLASS__, 'exclude_js' ] );
		add_filter( 'perfmatters_defer_js_exclusions', [ __CLASS__, 'exclude_js' ] );
		add_filter( 'perfmatters_rucss_excluded_stylesheets', [ __CLASS__, 'exclude_css' ] );

		// Autoptimize
		add_filter( 'autoptimize_filter_js_exclude', [ __CLASS__, 'exclude_js' ] );
		add_filter( 'autoptimize_filter_css_exclude', [ __CLASS__, 'exclude_css' ] );

		// LiteSpeed Cache
		add_filter( 'litespeed_optimize_js_excludes', [ __CLASS__, 'exclude_js' ] );
		add_filter( 'litespeed_optimize_css_excludes', [ __CLASS__, 'exclude_css' ] );

		// WP-Optimize
		add_filter( 'wp-optimize-minify-default-exclusions', [ __CLASS__, 'exclude_all' ] );

		// SG Optimizer
		add_filter( 'sgo_js_minify_exclude', [ __CLASS__, 'exclude_sg_optimizer_js' ] );
		add_filter( 'sgo_js_async_exclude', [ __CLASS__, 'exclude_sg_optimizer_js' ] );
		add_filter( 'sgo_javascript_combine_exclude', [ __CLASS__, 'exclude_sg_optimizer_js' ] );
		add_filter( 'sgo_css_minify_exclude', [ __CLASS__, 'exclude_sg_optimizer_css' ] );
		add_filter( 'sgo_css_combine_exclude', [ __CLASS__, 'exclude_sg_optimizer_css' ] );

		// W3 Total Cache
		// add_filter( 'w3tc_minify_js_do_tag_minification', [ __CLASS__, 'exclude_w3tc_tag_minification' ], 10, 3 );
		// add_filter( 'w3tc_minify_css_do_tag_minification', [ __CLASS__, 'exclude_w3tc_tag_minification' ], 10, 3 );
		// add_filter( 'w3tc_lazyload_excludes', [ __CLASS__, 'exclude_js' ] );
	}

	/**
	 * Exclude JS from cache plugins.
	 *
	 * @param array|string $exclusions Exclusions.
	 * @return array|string
	 */
	public static function exclude_js( $exclusions ) {
		$plugin_js_exclusions = [
			'wp-includes/js/dist/url.min.js',
			'wp-includes/js/dist/hooks.min.js',
			'wp-includes/js/dist/i18n.min.js',
			'wp-includes/js/dist/api-fetch.min.js',
			'wp-includes/js/dist/vendor/react-dom.min.js',
			'wp-includes/js/dist/vendor/react.min.js',
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
			'wp-hooks',
			'wp-i18n',
			'react',
			'react-dom',
			'wp-url',
			'wp-api-fetch',
			'asnp-easy-product-bundles-shared',
			'asnp-easy-product-bundles-product-bundle',
			'asnp-easy-product-bundles-pro-utils',
			'asnp-easy-product-bundles-pro-product-bundle',
			'wepb-checkout-integration',
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
			'wepb-checkout-integration',
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

}
