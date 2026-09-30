<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

defined( 'ABSPATH' ) || exit;

class Assets {

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_shared_scripts' ), 11 );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), 15 );
		add_action( 'wp_head', array( $this, 'custom_styles' ) );
	}

	public function load_shared_scripts() {
		if ( is_product_page() ) {
			$this->load_product_shared_scripts( get_the_ID() );
		}
	}

	public function load_scripts() {
		if ( is_product_page() ) {
			$this->load_product_scripts( get_the_ID() );
		}
	}

	public function load_product_shared_scripts( $product ) {
		$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return;
		}

		register_polyfills();

		do_action( 'asnp_wepb_before_' . __FUNCTION__ );

		wp_enqueue_style(
			'asnp-easy-product-bundles-shared',
			$this->get_url( 'shared/style', 'css' ),
			[],
			ASNP_WEPB_VERSION
		);

		$script_args = function_exists( 'wp_enqueue_script_module' ) || version_compare( get_bloginfo( 'version' ), '6.3', '>=' )
			? [ 'in_footer' => true, 'strategy' => 'defer' ]
			: true;

		wp_register_script(
			'asnp-easy-product-bundles-shared',
			$this->get_url( 'shared/index', 'js' ),
			[
				'react-dom',
				'wp-hooks',
			],
			ASNP_WEPB_VERSION,
			$script_args
		);

		$settings = get_plugin()->settings;
		wp_localize_script(
			'asnp-easy-product-bundles-shared',
			'easyProductBundlesData',
			apply_filters( 'asnp_wepb_localize_product_bundles_shared', array(
				'rest_url' => esc_url_raw( rest_url( 'asnp-easy-product-bundles/v1/' ) ),
				'rest_nonce' => wp_create_nonce( 'wp_rest' ),
				'i18n' => $this->get_i18n_strings(),
				'cssSelector' => $settings->get_setting( 'css_selector', 'form.cart' ),
				'cssSelectorPosition' => 'before_css_selector' === $settings->get_setting( 'product_bundle_position', 'before_css_selector' ) ? 'before' : 'after',
				'currency' => get_woocommerce_currency_symbol(),
				'currency_code' => apply_filters( 'asnp_wepb_currency_code', get_woocommerce_currency() ),
				'price_format' => get_woocommerce_price_format(),
				'number_of_decimals' => wc_get_price_decimals(),
				'thousand_separator' => wc_get_price_thousand_separator(),
				'decimal_separator' => wc_get_price_decimal_separator(),
				'bundles' => $product->get_initial_data(),
				'theme' => $settings->get_setting( 'theme', 'grid_1' ),
				'size' => $settings->get_setting( 'size', 'medium' ),
				'product_link' => $settings->get_setting( 'product_link', 'new_tab' ),
				'show_description' => $settings->get_setting( 'show_description', 'true' ),
				'show_description_inside_item' => $settings->get_setting( 'show_description_inside_item', 'false' ),
				'show_products_list' => is_pro_active() ? $settings->get_setting( 'show_products_list', 'true' ) : 'true',
				'show_total_price' => is_pro_active() ? $settings->get_setting( 'show_total_price', 'true' ) : 'true',
				'show_saved_price' => $settings->get_setting( 'show_saved_price', 'true' ),
				'show_discount_badge' => $settings->get_setting( 'show_discount_badge', 'true' ),
				'styles' => $settings->get_setting( 'styles', [] ),
				'quick_view' => $settings->get_setting( 'quick_view', 'true' ),
				'show_modal_quick_view' => $settings->get_setting( 'show_modal_quick_view', 'true' ),
				'show_selected_product_quick_view' => $settings->get_setting( 'show_selected_product_quick_view', 'true' ),
				'product_list_price' => $settings->get_setting( 'product_list_price', 'product_subtotal' ),
				'item_price' => $settings->get_setting( 'item_price', 'product_price' ),
				'product_price_selector' => $settings->get_setting( 'product_price_selector', '.product .summary .price' ),
				'add_to_cart_button_selector' => $settings->get_setting( 'add_to_cart_button_selector', 'button[type="submit"][name="add-to-cart"]' ),
				'quantity_field_on_item' => $settings->get_setting( 'quantity_field_on_item', 'true' ),
				'show_plus_icon' => $settings->get_setting( 'show_plus_icon', 'true' ),
				'click_on_popup_product' => $settings->get_setting( 'click_on_popup_product', 'product_selection' ),
				'disable_popup' => $settings->get_setting( 'disable_popup', 'false' ),
				'optional_item_mode' => $settings->get_setting( 'optional_item_mode', 'check_box' ),
				'popup_variable_dropdown' => $settings->get_setting( 'popup_variable_dropdown', 'true' ),
				'popup_show_option_button' => $settings->get_setting( 'popup_show_option_button', 'true' ),
				'popup_search_field' => $settings->get_setting( 'popup_search_field', 'true' ),
				'pro_active' => is_pro_active(),
			) )
		);
	}

	public function get_i18n_strings() {
		return apply_filters(
			'asnp_wepb_frontend_i18n_strings',
			[
				'OUT OF STOCK' => __( 'OUT OF STOCK', 'asnp-easy-product-bundles' ),
				'Total:' => __( 'Total:', 'asnp-easy-product-bundles' ),
				'Save' => __( 'Save', 'asnp-easy-product-bundles' ),
				'Buy all for:' => __( 'Buy all for:', 'asnp-easy-product-bundles' ),
				'% OFF' => __( '% OFF', 'asnp-easy-product-bundles' ),
				'%s is out of stock.' => __( '%s is out of stock.', 'asnp-easy-product-bundles' ),
				'Add' => __( 'Add', 'asnp-easy-product-bundles' ),
				'Add%s' => __( 'Add%s', 'asnp-easy-product-bundles' ),
				'View' => __( 'View', 'asnp-easy-product-bundles' ),
				'Change' => __( 'Change', 'asnp-easy-product-bundles' ),
				'Empty bundle!' => __( 'Empty bundle!', 'asnp-easy-product-bundles' ),
				'Please select a product!' => __( 'Please select a product!', 'asnp-easy-product-bundles' ),
				'Please select a product for all items.' => __( 'Please select a product for all items.', 'asnp-easy-product-bundles' ),
				'Please select a purchasable product for %s before adding bundle to the cart.'   => __( 'Please select a purchasable product for %s before adding bundle to the cart.', 'asnp-easy-product-bundles' ),
				'Please select a purchasable variation for %s before adding bundle to the cart.' => __( 'Please select a purchasable variation for %s before adding bundle to the cart.', 'asnp-easy-product-bundles' ),
				'Product information' => __( 'Product information', 'asnp-easy-product-bundles' ),
				'Product quantity' => __( 'Product quantity', 'asnp-easy-product-bundles' ),
				'Bundle item image' => __( 'Bundle item image', 'asnp-easy-product-bundles' ),
				'Search...' => __( 'Search...', 'asnp-easy-product-bundles' ),
				'No products found' => __( 'No products found', 'asnp-easy-product-bundles' ),
				'Args is required.' => __( 'Args is required.', 'asnp-easy-product-bundles' ),
				'Item index is required.' => __( 'Item index is required.', 'asnp-easy-product-bundles' ),
				'Product ID is required.' => __( 'Product ID is required.', 'asnp-easy-product-bundles' ),
				'Parent product ID is required.' => __( 'Parent product ID is required.', 'asnp-easy-product-bundles' ),
				'There was an error on getting items.' => __( 'There was an error on getting items.', 'asnp-easy-product-bundles' ),
				'Select options' => __( 'Select options', 'asnp-easy-product-bundles' ),
				'Clear selection' => __( 'Clear selection', 'asnp-easy-product-bundles' ),
				'Optional' => __( 'Optional', 'asnp-easy-product-bundles' ),
				'Delete' => __( 'Delete', 'asnp-easy-product-bundles' ),
				'Back' => __( 'Back', 'asnp-easy-product-bundles' ),
				'Close' => __( 'Close', 'asnp-easy-product-bundles' ),
				'Decrease quantity' => __( 'Decrease quantity', 'asnp-easy-product-bundles' ),
				'Increase quantity' => __( 'Increase quantity', 'asnp-easy-product-bundles' ),
				'Edit product' => __( 'Edit product', 'asnp-easy-product-bundles' ),
				'More pictures' => __( 'More pictures', 'asnp-easy-product-bundles' ),
				'Next' => __( 'Next', 'asnp-easy-product-bundles' ),
				'Prev' => __( 'Prev', 'asnp-easy-product-bundles' ),
				'Show Options' => __( 'Show Options', 'asnp-easy-product-bundles' ),
			]
		);
	}

	public function load_product_scripts( $product ) {
		$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
		if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
			return;
		}

		do_action( 'asnp_wepb_before_' . __FUNCTION__ );

		wp_enqueue_style(
			'asnp-easy-product-bundles-product-bundle',
			$this->get_url( 'product/style', 'css' ),
			[ 'asnp-easy-product-bundles-shared' ],
			ASNP_WEPB_VERSION
		);

		$script_args = function_exists( 'wp_enqueue_script_module' ) || version_compare( get_bloginfo( 'version' ), '6.3', '>=' )
			? [ 'in_footer' => true, 'strategy' => 'defer' ]
			: true;

		wp_enqueue_script(
			'asnp-easy-product-bundles-product-bundle',
			$this->get_url( 'product/index', 'js' ),
			[
				'asnp-easy-product-bundles-shared',
			],
			ASNP_WEPB_VERSION,
			$script_args
		);
	}

	public function custom_styles() {
		if ( is_product_page() ) {
			$this->add_custom_styles();
		}
	}

	public function add_custom_styles() {
		$custom_styles = '';
		$styles = get_plugin()->settings->get_setting( 'styles', [] );

		if ( ! empty( $styles['product_crossed_out_price_color'] ) && '#ababab' !== $styles['product_crossed_out_price_color'] ) {
			$custom_styles .= '.asnp-product-Price del, .asnp-product-Price del bdi, .asnp-product-Price .asnp-selectedProduct-regularPrice, .asnp-modal-grid-price del, .asnp-modal-grid-price del bdi, .asnp-productList-price del, .asnp-productList-price del bdi, .asnp-modal-quickView-wrapper .asnp-productInfo .asnp-productInfo-price del, .asnp-modal-quickView-wrapper .asnp-productInfo .asnp-productInfo-price del bdi, .asnp-productList-price .asnp-selectedProduct-regularPrice, .asnp-productList-price .asnp-selectedProduct-regularPrice .woocommerce-Price-amount.amount, .asnp-product-Price .asnp-selectedProduct-regularPrice .woocommerce-Price-amount.amount {';
			$custom_styles .= ' color: ' . esc_html( $styles['product_crossed_out_price_color'] ) . ';';
			$custom_styles .= '}';
		}

		if ( ! empty( $styles['product_sale_price_color'] ) && '#606060' !== $styles['product_sale_price_color'] ) {
			$custom_styles .= '.asnp-product-Price bdi, .asnp-product-Price ins, .asnp-product-Price ins bdi, .asnp-product-Price .asnp-selectedProduct-salePrice, .asnp-modal-grid-price bdi, .asnp-modal-grid-price ins, .asnp-modal-grid-price ins bdi, .asnp-productInfo-price bdi, .asnp-modal-quickView-wrapper .asnp-productInfo .asnp-productInfo-price ins bdi, .asnp-productList-price bdi, .asnp-productList-price ins, .asnp-productList-price ins bdi, .asnp-productList-price .asnp-selectedProduct-salePrice, .asnp-productList-price .woocommerce-Price-amount.amount, .asnp-product-Price .woocommerce-Price-amount.amount, .asnp-productList-price .asnp-selectedProduct-salePrice .woocommerce-Price-amount.amount, .asnp-product-Price .asnp-selectedProduct-salePrice .woocommerce-Price-amount.amount {';
			$custom_styles .= ' color: ' . esc_html( $styles['product_sale_price_color'] ) . ';';
			$custom_styles .= '}';
		}

		if ( ! empty( $styles['bundle_title_color'] ) && '#d4af37' !== $styles['bundle_title_color'] ) {
			$custom_styles .= '.asnp-bundle-title:before, .asnp-bundle-title:after {';
			$custom_styles .= ' color: ' . esc_html( $styles['bundle_title_color'] ) . ';';
			$custom_styles .= '}';
		}

		if ( ! empty( $styles['empty_item_title_hover_color'] ) && '#407729' !== $styles['empty_item_title_hover_color'] ) {
			$custom_styles .= '.asnp-productBox-hover:hover .asnp-emptyList-productSelect, .asnp-productInfo-wrapper .asnp-productList-selectProduct:hover {';
			$custom_styles .= ' color: ' . esc_html( $styles['empty_item_title_hover_color'] ) . '!important;';
			$custom_styles .= '}';
		}
		if ( ! empty( $styles['quantity_buttons_color'] ) && '#1abc9c' !== $styles['quantity_buttons_color'] ) {
			$custom_styles .= '.asnp-product-quantity-field .asnp-product-quantity-button:hover {';
			$custom_styles .= ' background-color: ' . esc_html( $styles['quantity_buttons_color'] ) . '!important;';
			$custom_styles .= '}';
		}
		if ( ! empty( $styles['total_price_sale_price_color'] ) && '#606060' !== $styles['total_price_sale_price_color'] ) {
			$custom_styles .= '.asnp-totalPrice-wrapper .asnp-totalPrice-section .woocommerce-Price-amount.amount, .asnp-mainPrice .asnp-totalPrice-section .woocommerce-Price-amount.amount {';
			$custom_styles .= ' color: ' . esc_html( $styles['total_price_sale_price_color'] ) . ';';
			$custom_styles .= '}';
		}
		if ( ! empty( $styles['total_price_crossed_out_price_color'] ) && '#ababab' !== $styles['total_price_crossed_out_price_color'] ) {
			$custom_styles .= '.asnp-totalPrice-wrapper .asnp-totalPrice-section s, .asnp-mainPrice .asnp-totalPrice-section s, .asnp-totalPrice-wrapper .asnp-totalPrice-section s .woocommerce-Price-amount.amount, .asnp-mainPrice .asnp-totalPrice-section s .woocommerce-Price-amount.amount {';
			$custom_styles .= ' color: ' . esc_html( $styles['total_price_crossed_out_price_color'] ) . ';';
			$custom_styles .= '}';
		}
		if ( ! empty( $styles['total_price_saved_amount_color'] ) && '#ffffff' !== $styles['total_price_saved_amount_color'] ) {
			$custom_styles .= '.asnp-totalPrice-wrapper .asnp-totalPrice-section .asnp-savedPrice, .asnp-mainPrice .asnp-totalPrice-section .asnp-savedPrice, .asnp-totalPrice-wrapper .asnp-totalPrice-section .asnp-savedPrice .woocommerce-Price-amount.amount, .asnp-mainPrice .asnp-totalPrice-section .asnp-savedPrice .woocommerce-Price-amount.amount {';
			$custom_styles .= ' color: ' . esc_html( $styles['total_price_saved_amount_color'] ) . ';';
			$custom_styles .= '}';
		}
		if ( ! empty( $styles['total_price_saved_amount_background_color'] ) && '#019267' !== $styles['total_price_saved_amount_background_color'] ) {
			$custom_styles .= '.asnp-totalPrice-wrapper .asnp-totalPrice-section .asnp-savedPrice, .asnp-mainPrice .asnp-totalPrice-section .asnp-savedPrice {';
			$custom_styles .= ' background-color: ' . esc_html( $styles['total_price_saved_amount_background_color'] ) . ';';
			$custom_styles .= '}';
		}

		$custom_styles = apply_filters( 'asnp_wepb_custom_styles', $custom_styles, $styles );

		if ( ! empty( $custom_styles ) ) {
			echo "\n<style id='asnp-wepb-inline-style'>\n" . $custom_styles . "\n</style>\n";
		}
	}

	public function get_url( $file, $ext ) {
		return plugins_url( $this->get_path( $ext ) . $file . '.' . $ext, ASNP_WEPB_PLUGIN_FILE );
	}

	protected function get_path( $ext ) {
		return 'css' === $ext ? 'assets/css/' : 'assets/js/';
	}

}
