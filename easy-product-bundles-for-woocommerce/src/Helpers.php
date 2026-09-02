<?php

namespace AsanaPlugins\WooCommerce\ProductBundles;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WooCommerce\ProductBundles\Helpers\Products;
use AsanaPlugins\WooCommerce\ProductBundles\Models\SimpleBundleItemsModel;

function get_plugin() {
	return Plugin::instance();
}

function is_pro_active() {
	return defined( 'ASNP_WEPB_PRO_VERSION' );
}

function get_product_bundle_type() {
	return 'easy_product_bundle';
}

/**
 * Callback for array filter to get products the user can view only.
 *
 * @since  1.0.0
 *
 * @param  \WC_Product $product WC_Product object.
 *
 * @return bool
 */
function wc_products_array_filter_readable( $product ) {
	if ( function_exists( '\wc_products_array_filter_readable' ) ) {
		return \wc_products_array_filter_readable( $product );
	}

	return $product && is_a( $product, 'WC_Product' ) && current_user_can( 'read_product', $product->get_id() );
}

function get_product_image_src( $product, $size = 'woocommerce_single', $placeholder = true ) {
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product ) {
		return '';
	}

	$src = '';
	if ( $product->get_image_id() ) {
		$image = wp_get_attachment_image_src( $product->get_image_id(), $size );
		$src = ! empty( $image ) && ! empty( $image[0] ) ? $image[0] : '';
	} elseif ( $product->get_parent_id() ) {
		$parent_product = wc_get_product( $product->get_parent_id() );
		if ( $parent_product ) {
			$src = get_product_image_src( $parent_product, $size );
		}
	}

	if ( empty( $src ) && $placeholder ) {
		$image = wc_placeholder_img_src( $size );
		$src = ! empty( $image ) && ! empty( $image[0] ) ? $image[0] : '';
	}

	return apply_filters( 'asnp_wepb_get_product_image_src', $src, $product, $size, $placeholder );
}

function prepare_variable_prices( $product, $item, $extra_data = [] ) {
    // Ensure we have a valid WC_Product object instance
    $product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
    if ( ! $product ) {
        return [];
    }

    // Verify if the product type is indeed variable
    if ( ! $product->is_type( 'variable' ) ) {
        throw new \Exception( __( 'Invalid product type.', 'asnp-easy-product-bundles' ) );
    }

    // Check if either the bundle item or global bundle settings have a valid discount active
    if ( has_valid_discount( $item ) || has_valid_discount( $extra_data, 'total_discount_type', 'total_discount', 'percentage' ) ) {
        
        // 1. Force using Regular Prices as the base if 'use_regular_price' option is enabled
        if ( isset( $item['use_regular_price'] ) && 'true' === $item['use_regular_price'] ) {
            $min_price = $product->get_variation_regular_price( 'min' );
            $max_price = $product->get_variation_regular_price( 'max' );
        } else {
            // Default logic: Fetch min/max prices (which respects native WooCommerce sales)
            $min_price = $product->get_variation_price( 'min' );
            $max_price = $product->get_variation_price( 'max' );
        }

        // Fallback check if prices couldn't be loaded properly
        if ( '' === $min_price && '' === $max_price ) {
            return apply_filters(
                'asnp_wepb_prepare_variable_prices',
                [ 'display_price' => $product->get_price_html() ],
                $product,
                $item
            );
        }

        $has_item_discount = has_valid_discount( $item );
        $has_total_discount = has_valid_discount( $extra_data, 'total_discount_type', 'total_discount', 'percentage' );

        // 2. Process and apply the percentage/fixed bundle discounts onto the selected base prices
        if ( $has_item_discount && $has_total_discount && 'percentage' === $item['discount_type'] ) {
            $discount = (float) $item['discount'] + (float) $extra_data['total_discount'];
            $min_price -= DiscountCalculator::calculate( $min_price, $discount, 'percentage' );
            $max_price -= DiscountCalculator::calculate( $max_price, $discount, 'percentage' );
        } else {
            if ( $has_item_discount ) {
                $min_price -= DiscountCalculator::calculate( $min_price, $item['discount'], $item['discount_type'] );
                $max_price -= DiscountCalculator::calculate( $max_price, $item['discount'], $item['discount_type'] );
            }
            if ( $has_total_discount ) {
                $min_price -= DiscountCalculator::calculate( $min_price, $extra_data['total_discount'], 'percentage' );
                $max_price -= DiscountCalculator::calculate( $max_price, $extra_data['total_discount'], 'percentage' );
            }
        }

        // Format prices according to WooCommerce display currency setup
        $min_price = wc_get_price_to_display( $product, [ 'price' => $min_price ] );
        $max_price = wc_get_price_to_display( $product, [ 'price' => $max_price ] );

        // Fetch pristine min/max regular prices for strike-through layouts
        $min_reg_price = $product->get_variation_regular_price( 'min', true );
        $max_reg_price = $product->get_variation_regular_price( 'max', true );

        // If calculated final price matches original regular price, render without strikeout layouts
        if ( $min_price == $min_reg_price && $max_price == $max_reg_price ) {
            return apply_filters(
                'asnp_wepb_prepare_variable_prices',
                [
                    'display_price' => wc_format_price_range( $min_price, $max_price ) . $product->get_price_suffix(),
                ],
                $product,
                $item
            );
        }

        // Build the original price string (range or single price)
        if ( $min_reg_price !== $max_reg_price ) {
            $main_price = wc_format_price_range( $min_reg_price, $max_reg_price );
        } else {
            $main_price = wc_price( $min_reg_price );
        }

        // Build the new discounted price string (range or single price)
        if ( $min_price !== $max_price ) {
            $display_price = wc_format_price_range( $min_price, $max_price );
        } else {
            $display_price = wc_price( $min_price );
        }

        // 3. Render strikeout layout if any variation's price dropped below its regular price threshold
        if ( (float) $min_reg_price > (float) $min_price || (float) $max_reg_price > (float) $max_price ) {
            return apply_filters(
                'asnp_wepb_prepare_variable_prices',
                [
                    'display_price' => '<del aria-hidden="true">' . $main_price . '</del> <ins>' . $display_price . '</ins>' . $product->get_price_suffix(),
                ],
                $product,
                $item
            );
        }

        return apply_filters(
            'asnp_wepb_prepare_variable_prices',
            [
                'display_price' => $display_price . $product->get_price_suffix(),
            ],
            $product,
            $item
        );
    }

    // 4. Default Fallback layout when NO bundle discounts are active
    if ( isset( $item['use_regular_price'] ) && 'true' === $item['use_regular_price'] ) {
        // Force rendering the clean Regular Price Range only, hiding native WooCommerce sales
        $min_reg_price = $product->get_variation_regular_price( 'min', true );
        $max_reg_price = $product->get_variation_regular_price( 'max', true );
        
        if ( $min_reg_price !== $max_reg_price ) {
            $clean_display_price = wc_format_price_range( $min_reg_price, $max_reg_price );
        } else {
            $clean_display_price = wc_price( $min_reg_price );
        }
        
        return apply_filters(
            'asnp_wepb_prepare_variable_prices',
            [
                'display_price' => $clean_display_price . $product->get_price_suffix(),
            ],
            $product,
            $item
        );
    }

    // Standard plugin fallback logic if the option is disabled
    return apply_filters(
        'asnp_wepb_prepare_variable_prices',
        [
            'display_price' => $product->get_price_html(),
        ],
        $product,
        $item
    );
}

function prepare_product_prices( $product, $item, $extra_data = [] ) {
    // Ensure we have a valid WC_Product object instance
    $product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
    if ( ! $product ) {
        return [];
    }

    // Handle variable products separately using the dedicated function
    if ( $product->is_type( 'variable' ) ) {
        return prepare_variable_prices( $product, $item, $extra_data );
    }

    // Retrieve the display-formatted regular price of the product
    $regular_price = '' !== $product->get_regular_price() ? wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price() ] ) : '';
    
	$use_regular_price = isset( $item['use_regular_price'] ) && 'true' === $item['use_regular_price'];
	$has_bundle_discount = has_valid_discount( $item ) || has_valid_discount( $extra_data, 'total_discount_type', 'total_discount', 'percentage' );
	$sale_price = ! $use_regular_price && '' !== $product->get_sale_price() && $product->is_on_sale() ? $product->get_sale_price() : '';
	if ( $has_bundle_discount ) {
		$sale_price = get_bundle_item_price( $product, array_merge( $item, $extra_data ) );
	}

    // Format the price output HTML string for frontend display rendering
    if ( '' !== $sale_price ) {
        $sale_price = wc_get_price_to_display( $product, [ 'price' => $sale_price ] );
        
        // Render the crossed-out regular price with the newly calculated bundle price next to it
        if ( '' !== $regular_price ) {
            $display_price = wc_format_sale_price( $regular_price, $sale_price ) . $product->get_price_suffix();
        } else {
            $display_price = wc_price( $sale_price ) . $product->get_price_suffix();
        }
    } elseif ( $use_regular_price && '' !== $regular_price ) {
		$display_price = wc_price( $regular_price ) . $product->get_price_suffix();
    } else {
		$display_price = $product->get_price_html();
	}

    // Pass the prepared array through standard plugin filters
    return apply_filters(
        'asnp_wepb_prepare_product_prices',
        [
            'regular_price' => $regular_price,
            'sale_price'    => $sale_price,
            'display_price' => $display_price,
        ],
        $product,
        $item
    );
}

function prepare_product_data( $product, $item = [], $extra_data = [] ) {
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product ) {
		return array();
	}

	$data = array(
		'id' => $product->get_id(),
		'image' => get_product_image_src( $product ),
		'is_variable' => $product->is_type( 'variable' ) ? 'true' : 'false',
		'is_in_stock' => $product->is_in_stock() ? 'true' : 'false',
		'link' => $product->get_permalink(),
		'max_qty' => 0 < $product->get_max_purchase_quantity() ? $product->get_max_purchase_quantity() : '',
		'min_qty' => $product->get_min_purchase_quantity(),
	);

	if ( $product->is_type( 'variation' ) ) {
		$data['name'] = $product->get_title();
		if ( ! empty( $extra_data['attributes'] ) ) {
			$formatted_variation = [];
			foreach ( $extra_data['attributes'] as $attribute ) {
				if ( ! empty( $attribute['name'] ) && ! empty( $attribute['label'] ) ) {
					$formatted_variation[] = get_clean_attribute_label( $attribute['name'], $product ) . ': ' . "\xE2\x80\x8E" . wp_specialchars_decode( rawurldecode( $attribute['label'] ) );
				}
			}
			$formatted_variation = implode( ', ', $formatted_variation );
		} else {
			$formatted_variation = get_formatted_variation_attributes( $product );
		}

		if ( ! empty( $formatted_variation ) ) {
			$data['name'] .= ' ' . $formatted_variation;
		}
	} else {
		$data['name'] = $product->get_title();
	}

	$data['name'] = wp_specialchars_decode( $data['name'] );

	$data['description'] = Products\get_description( $product );

	if ( 'true' === get_plugin()->settings->get_setting( 'show_stock', 'false' ) ) {
		$data['stock'] = wc_get_stock_html( $product );
	}

	if ( 'true' === get_plugin()->settings->get_setting( 'show_rating', 'false' ) && 0 < $product->get_average_rating() ) {
		$data['rating'] = wc_get_rating_html( $product->get_average_rating() );
	}

	// Add product prices.
	$data = array_merge( $data, prepare_product_prices( $product, $item, $extra_data ) );

	if ( ! empty( $extra_data ) ) {
		$data = array_merge( $data, $extra_data );
	}

	return apply_filters( 'asnp_wepb_prepare_product_data', $data, $product, $item, $extra_data );
}

function get_clean_attribute_label( $name, $product = null ) {
	static $cache = [];
	$product_id   = $product && is_object( $product ) ? $product->get_id() : 0;
	$cache_key    = $name . '_' . $product_id;

	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$label = wc_attribute_label( $name, $product );
	if ( 0 === strpos( $label, 'pa_' ) ) {
		$label = substr( $label, 3 );
	}

	$clean_label = wp_specialchars_decode( $label );

	$cache[ $cache_key ] = $clean_label;
	return $clean_label;
}

function get_formatted_variation_attributes( $variation ) {
	if ( ! $variation ) {
		return '';
	}

	$variation = is_numeric( $variation ) ? wc_get_product( $variation ) : $variation;
	if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
		return '';
	}

	$variation_attributes = $variation->get_variation_attributes( false );
	if ( empty( $variation_attributes ) ) {
		return '';
	}

	$formatted = [];
	foreach ( $variation_attributes as $key => $value ) {
		if ( '' === $value || null === $value ) {
			continue;
		}

		$attr_data = get_attribute_data(
			[
				'attribute' => $key,
				'value'     => $value,
				'by'        => 'slug',
			]
		);

		if ( empty( $attr_data ) ) {
			continue;
		}

		$formatted[] = get_clean_attribute_label( $attr_data['name'], $variation ) . ': ' . "\xE2\x80\x8E" . wp_specialchars_decode( rawurldecode( ! empty( $attr_data['label'] ) ? $attr_data['label'] : $value ) );
	}

	return implode( ', ', $formatted );
}

function prepare_variation_data( $variation, $variable = null, $item = [], $extra_data = [] ) {
	if ( ! $variation ) {
		return [];
	}

	$variation = is_numeric( $variation ) ? wc_get_product( $variation ) : $variation;
	$variable = is_null( $variable ) ? $variation->get_parent_id() : $variable;
	$variable = is_numeric( $variable ) ? wc_get_product( $variable ) : $variable;
	if ( $variable->get_id() !== $variation->get_parent_id() ) {
		return [];
	}

	$products = [];
	$variation_attributes = $variation->get_variation_attributes( false );
	$any_attributes = get_any_value_attributes( $variation_attributes );
	$extra_data = array_merge( $extra_data, [ 'attributes' => [] ] );
	if ( empty( $any_attributes ) ) {
		if ( ! empty( $variation_attributes ) ) {
			foreach ( $variation_attributes as $key => $attribute ) {
				$attribute_data = get_attribute_data(
					[
						'attribute' => $key,
						'value'     => $attribute,
						'by'        => 'slug',
					]
				);
				if ( ! empty( $attribute_data ) ) {
					$extra_data['attributes'][] = $attribute_data;
				}
			}
		}
		$products[] = prepare_product_data( $variation, $item, $extra_data );
	} else {
		$attributes = $variable->get_attributes();
		$any_values = [];
		for ( $i = 0; $i < count( $any_attributes ); $i++ ) {
			$attr_key = $any_attributes[ $i ];
			$attr_obj = null;

			$slug     = wc_attribute_taxonomy_slug( $attr_key );
			$tax_name = wc_attribute_taxonomy_name( $slug );

			if ( isset( $attributes[ $attr_key ] ) ) {
				$attr_obj = $attributes[ $attr_key ];
			} elseif ( isset( $attributes[ $tax_name ] ) ) {
				$attr_obj = $attributes[ $tax_name ];
			} elseif ( isset( $attributes[ $slug ] ) ) {
				$attr_obj = $attributes[ $slug ];
			} elseif ( isset( $attributes[ urldecode( $attr_key ) ] ) ) {
				$attr_obj = $attributes[ urldecode( $attr_key ) ];
			} elseif ( isset( $attributes[ sanitize_title( $attr_key ) ] ) ) {
				$attr_obj = $attributes[ sanitize_title( $attr_key ) ];
			} else {
				foreach ( $attributes as $k => $attr ) {
					if (
						$k === $attr_key ||
						$k === $tax_name ||
						$k === $slug ||
						urldecode( $k ) === urldecode( $attr_key ) ||
						sanitize_title( $k ) === sanitize_title( $attr_key )
					) {
						$attr_obj = $attr;
						break;
					}
				}
			}

			if ( $attr_obj ) {
				$is_variation = is_object( $attr_obj ) ? $attr_obj->get_variation() : ! empty( $attr_obj['is_variation'] );
				$options = is_object( $attr_obj ) ? $attr_obj->get_options() : ( isset( $attr_obj['options'] ) ? $attr_obj['options'] : [] );
				$name = is_object( $attr_obj ) ? $attr_obj->get_name() : ( isset( $attr_obj['name'] ) ? $attr_obj['name'] : $attr_key );

				if ( $is_variation ) {
					$any_values[] = $options;
					$any_attributes[ $i ] = $name;
				}
			}
		}

		$any_values = 1 < count( $any_values ) ?
			combinations( $any_values ) :
			( 1 === count( $any_values ) ? $any_values[0] : [] );

		if ( ! empty( $any_values ) ) {
			$defined_attributes = [];
			foreach ( $variation_attributes as $key => $attribute ) {
				if ( empty( $attribute ) ) {
					continue;
				}

				$attribute_data = get_attribute_data(
					[
						'attribute' => $key,
						'value'     => $attribute,
						'by'        => 'slug',
					]
				);
				if ( ! empty( $attribute_data ) ) {
					$defined_attributes[] = $attribute_data;
				}
			}

			for ( $i = 0; $i < count( $any_values ); $i++ ) {
				$extra_data['attributes'] = $defined_attributes;
				if ( is_array( $any_values[ $i ] ) ) {
					for ( $j = 0; $j < count( $any_values[ $i ] ); $j++ ) {
						$attribute_data = get_attribute_data(
							[
								'attribute' => $any_attributes[ $j ],
								'value'     => $any_values[ $i ][ $j ],
								'by'        => 'id',
							]
						);
						if ( ! empty( $attribute_data ) ) {
							$extra_data['attributes'][] = $attribute_data;
						}
					}
				} else {
					$attribute_data = get_attribute_data(
						[
							'attribute' => $any_attributes[0],
							'value'     => $any_values[ $i ],
							'by'        => 'id',
						]
					);
					if ( ! empty( $attribute_data ) ) {
						$extra_data['attributes'][] = $attribute_data;
					}
				}

				$products[] = prepare_product_data( $variation, $item, $extra_data );
			}
		}
	}

	return $products;
}

function get_variation_attribute_options( array $args = [] ) {
	$args = wp_parse_args(
		apply_filters( 'asnp_wepb_get_variation_attribute_options_args', $args ),
		[
			'options'   => false,
			'attribute' => false,
			'product'   => false,
		]
	);

	$options   = $args['options'];
	$product   = $args['product'];
	$attribute = $args['attribute'];

	if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
		$attributes = $product->get_variation_attributes();
		$slug       = wc_attribute_taxonomy_slug( $attribute );
		$tax_name   = wc_attribute_taxonomy_name( $slug );

		if ( isset( $attributes[ $attribute ] ) ) {
			$options = $attributes[ $attribute ];
		} elseif ( isset( $attributes[ $tax_name ] ) ) {
			$options = $attributes[ $tax_name ];
		} elseif ( isset( $attributes[ $slug ] ) ) {
			$options = $attributes[ $slug ];
		} elseif ( isset( $attributes[ urldecode( $attribute ) ] ) ) {
			$options = $attributes[ urldecode( $attribute ) ];
		} elseif ( isset( $attributes[ sanitize_title( $attribute ) ] ) ) {
			$options = $attributes[ sanitize_title( $attribute ) ];
		}
	}

	static $options_cache = [];
	$product_id = $product && is_object( $product ) ? $product->get_id() : 0;
	$cache_key  = md5( $product_id . '_' . $attribute . '_' . ( is_array( $options ) ? implode( ',', $options ) : (string) $options ) );

	if ( isset( $options_cache[ $cache_key ] ) ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return apply_filters( 'asnp_wepb_get_variation_attribute_options', $options_cache[ $cache_key ], $args );
	}

	$select_options = [];

	if ( ! empty( $options ) ) {
		$taxonomy = wc_attribute_taxonomy_name( wc_attribute_taxonomy_slug( $attribute ) );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			$taxonomy = taxonomy_exists( $attribute ) ? $attribute : '';
		}

		if ( $product && ! empty( $taxonomy ) ) {
			// Get terms if this is a taxonomy - ordered. We need the names too.
			$terms = wc_get_product_terms(
				$product->get_id(),
				$taxonomy,
				[
					'fields' => 'all',
				]
			);

			foreach ( $terms as $term ) {
				if (
					in_array( $term->slug, $options, true ) ||
					in_array( urldecode( $term->slug ), $options, true ) ||
					in_array( (string) $term->term_id, $options, true ) ||
					in_array( (int) $term->term_id, $options, true )
				) {
					$name             = sanitize_text_field( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $taxonomy, $product ) );
					$select_options[] = [
						'name'  => wp_specialchars_decode( $name ),
						'value' => esc_attr( $term->slug ),
					];
				}
			}
		} else {
			foreach ( $options as $option ) {
				// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
				$name             = sanitize_text_field( apply_filters( 'woocommerce_variation_option_name', urldecode( $option ), null, $attribute, $product ) );
				$select_options[] = [
					'name'  => wp_specialchars_decode( $name ),
					'value' => esc_attr( $option ),
				];
			}
		}
	}

	$options_cache[ $cache_key ] = $select_options;

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return apply_filters( 'asnp_wepb_get_variation_attribute_options', $select_options, $args );
}

function get_product_type_ids( $types ) {
	if ( empty( $types ) ) {
		return [];
	}

	$types = is_string( $types ) ? explode( ',', $types ) : $types;

	$terms = get_terms( 'product_type', [ 'hide_empty' => 0, 'nicename' => false ] );

	$ids = [];
	foreach ( $types as $type ) {
		if ( 'variation' === $type || 'product_variation' === $type ) {
			continue;
		}
		foreach ( $terms as $term ) {
			if ( $type === $term->slug ) {
				$ids[] = (int) $term->term_id;
				break;
			}
		}
	}
	return $ids;
}

function get_product_types_for_bundle( $excludes = [] ) {
	static $defaults;
	if ( isset( $defaults ) ) {
		if ( ! empty( $excludes ) && ! empty( $defaults ) ) {
			return apply_filters( 'asnp_wepb_get_product_types_for_bundle', array_diff( $defaults, $excludes ), $excludes );
		}
		return $defaults;
	}

	$defaults = [ 'variation' ];
	$types = array_merge( array_keys( wc_get_product_types() ) );
	if ( empty( $types ) ) {
		if ( ! empty( $excludes ) ) {
			return apply_filters( 'asnp_wepb_get_product_types_for_bundle', array_diff( $defaults, $excludes ), $excludes );
		}
		return apply_filters( 'asnp_wepb_get_product_types_for_bundle', $defaults, $excludes );
	}

	foreach ( $types as $type ) {
		if (
			false === strpos( $type, 'bundle' )
			&& false === strpos( $type, 'group' )
			&& false === strpos( $type, 'composite' )
			&& false === strpos( $type, 'booking' )
		) {
			$defaults[] = $type;
		}
	}

	if ( ! empty( $excludes ) && ! empty( $defaults ) ) {
		return apply_filters( 'asnp_wepb_get_product_types_for_bundle', array_diff( $defaults, $excludes ), $excludes );
	}

	return apply_filters( 'asnp_wepb_get_product_types_for_bundle', $defaults, $excludes );
}

function is_in_cart( $product ) {
	if ( ! $product ) {
		return false;
	}

	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;

	$cart = WC()->cart->get_cart();
	foreach ( $cart as $cart_item_key => $cart_item ) {
		if ( $product->get_id() == $cart_item['product_id'] ) {
			return $cart_item_key;
		} elseif ( ! empty( $cart_item['variation_id'] ) && $product->get_id() == $cart_item['variation_id'] ) {
			return $cart_item_key;
		}
	}

	return false;
}

function get_any_value_attributes( array $variation_attributes ) {
	if ( empty( $variation_attributes ) ) {
		return [];
	}

	$attributes = [];
	foreach ( $variation_attributes as $key => $value ) {
		if ( empty( $value ) ) {
			$attributes[] = $key;
		}
	}

	return $attributes;
}

function get_attribute_data( array $args ) {
	if ( empty( $args ) || empty( $args['attribute'] ) || ! isset( $args['value'] ) ) {
		return [];
	}

	$args = wp_parse_args( $args, [ 'by' => 'slug' ] );

	$attribute = $args['attribute'];
	$value     = $args['value'];
	$by        = $args['by'];

	static $cache = [];
	$cache_key    = $attribute . '_' . $value . '_' . $by;
	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$taxonomy = wc_attribute_taxonomy_name( wc_attribute_taxonomy_slug( $attribute ) );
	if ( ! taxonomy_exists( $taxonomy ) ) {
		$taxonomy = taxonomy_exists( $attribute ) ? $attribute : '';
	}

	if ( ! empty( $taxonomy ) ) {
		$term = 'id' === $by ? get_term( absint( $value ), $taxonomy ) : get_term_by( 'slug', $value, $taxonomy );
		if ( ( ! $term || is_wp_error( $term ) ) && 'slug' === $by && false !== strpos( $value, '%' ) ) {
			$term = get_term_by( 'slug', urldecode( $value ), $taxonomy );
		}

		if ( ! is_wp_error( $term ) && is_object( $term ) && ! empty( $term->term_id ) ) {
			$data = [
				'name'  => $taxonomy,
				'id'    => sanitize_title( $attribute ),
				'label' => wp_specialchars_decode( $term->name ),
				'value' => $term->slug,
			];
		} else {
			$data = [
				'name'  => $taxonomy,
				'id'    => sanitize_title( $attribute ),
				'label' => wp_specialchars_decode( urldecode( $value ) ),
				'value' => $value,
			];
		}
	} else {
		$data = [
			'name'  => urldecode( $attribute ),
			'id'    => sanitize_title( $attribute ),
			'label' => wp_specialchars_decode( urldecode( $value ) ),
			'value' => $value,
		];
	}

	$cache[ $cache_key ] = $data;
	return $data;
}

function combinations( $arrays, $i = 0 ) {
	if ( ! isset( $arrays[ $i ] ) ) {
		return array();
	}
	if ( $i == count( $arrays ) - 1 ) {
		return $arrays[ $i ];
	}

	// get combinations from subsequent arrays
	$tmp = combinations( $arrays, $i + 1 );

	$result = array();

	// concat each array from tmp with each element from $arrays[$i]
	foreach ( $arrays[ $i ] as $v ) {
		foreach ( $tmp as $t ) {
			$result[] = is_array( $t ) ?
				array_merge( array( $v ), $t ) :
				array( $v, $t );
		}
	}

	return $result;
}

function get_product_ids_from_bundle_items( $items ) {
	if ( empty( $items ) ) {
		return [];
	}

	if ( is_json( $items ) ) {
		$items = json_decode( $items, true );
		return array_map( function ( $item ) {
			return isset( $item['id'] ) ? $item['id'] : 0;
		}, $items );
	}

	$items = is_string( $items ) ? explode( ',', $items ) : $items;

	return array_map( function ( $item ) {
		$item = explode( ':', $item );
		if ( 1 < count( $item ) && is_numeric( $item[0] ) ) {
			return 0 == $item[0] ? 0 : maybe_get_exact_product_id( absint( $item[0] ) );
		}
		return 0;
	}, $items );
}

function get_quantities_from_bundle_items( $items ) {
	if ( empty( $items ) ) {
		return [];
	}

	if ( is_json( $items ) ) {
		$items = json_decode( $items, true );
		return array_map( function ( $item ) {
			return isset( $item['qty'] ) ? wc_stock_amount( $item['qty'] ) : 0;
		}, $items );
	}

	$items = is_string( $items ) ? explode( ',', $items ) : $items;

	return array_map( function ( $item ) {
		$item = explode( ':', $item );
		if ( 1 < count( $item ) && is_numeric( $item[1] ) ) {
			return wc_stock_amount( $item[1] );
		}
		return 0;
	}, $items );
}

function get_attributes_from_bundle_items( $items ) {
	if ( empty( $items ) ) {
		return [];
	}

	if ( is_json( $items ) ) {
		$items = json_decode( $items, true );
		return array_map( function ( $item ) {
			$attributes = [];
			if ( ! empty( $item['attributes'] ) ) {
				foreach ( $item['attributes'] as $key => $value ) {
					$attributes[ 'attribute_' . sanitize_title( $key ) ] = $value;
				}
			}
			return $attributes;
		}, $items );
	}

	$items = is_string( $items ) ? explode( ',', $items ) : $items;

	return array_map( __NAMESPACE__ . '\get_attributes_of_bundle_item', $items );
}

function get_attributes_of_bundle_item( $item ) {
	if ( empty( $item ) ) {
		return [];
	}

	$item = is_string( $item ) ? explode( ':', $item ) : $item;
	if ( 2 < count( $item ) ) {
		$attributes = [];
		array_map( function ( $value ) use ( &$attributes ) {
			$value = explode( '=', $value );
			if ( 1 < count( $value ) ) {
				$attributes[ 'attribute_' . sanitize_title( $value[0] ) ] = $value[1];
			}
		}, explode( '&', $item[2] ) );
		return $attributes;
	}
	return [];
}

function get_bundle_item_price( $product, array $args ) {
	if ( ! $product || empty( $args ) ) {
		return 0;
	}

	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product ) {
		return 0;
	}

	$args = array_merge( [ 'exchange_price' => true ], $args );

	$use_regular_price = isset( $args['use_regular_price'] ) && 'true' === $args['use_regular_price'];

	if ( ! isset( $args['is_fixed_price'] ) || ! $args['is_fixed_price'] ) {
		if ( $use_regular_price && '' !== $product->get_regular_price( 'edit' ) ) {
			$price = $product->get_regular_price( 'edit' );
		} else {
			$price = $product->get_price( 'edit' );
		}

		$item_discount = has_valid_discount( $args );
		$total_discount = has_valid_discount( $args, 'total_discount_type', 'total_discount', 'percentage' );
		if ( $item_discount && $total_discount && 'percentage' === $args['discount_type'] ) {
			$price -= DiscountCalculator::calculate( $price, (float) $args['discount'] + (float) $args['total_discount'], 'percentage' );
		} else {
			if ( $item_discount ) {
				$price -= DiscountCalculator::calculate( $price, $args['discount'], $args['discount_type'] );
			}
			if ( $total_discount ) {
				$price -= DiscountCalculator::calculate( $price, $args['total_discount'], 'percentage' );
			}
		}

		return $args['exchange_price'] ? maybe_exchange_price( $price ) : $price;
	}

	if ( $use_regular_price && '' !== $product->get_regular_price( 'edit' ) ) {
		return $args['exchange_price'] ? $product->get_regular_price() : $product->get_regular_price( 'edit' );
	}

	return $args['exchange_price'] ? $product->get_price() : $product->get_price( 'edit' );
}

function is_cart_item_bundle( $cart_item ) {
	return isset( $cart_item['asnp_wepb_items'] );
}

function is_cart_item_bundle_item( $cart_item ) {
	return isset( $cart_item['asnp_wepb_parent_id'] );
}

function is_allowed_bundle_item_type( $type ) {
	if ( empty( $type ) ) {
		return false;
	}

	$types = apply_filters(
		'asnp_wepb_bundle_item_not_allowed_product_types',
		[
			'variable',
			'bundle',
			'group',
			'composite',
			'booking',
		]
	);

	foreach ( $types as $not_allowed ) {
		if (
			$type === $not_allowed ||
			false !== strpos( $type, $not_allowed )
		) {
			return false;
		}
	}

	return true;
}

function maybe_get_exact_product_id( $id ) {
	return 0 < $id ? apply_filters( 'asnp_wepb_exact_product_id', $id ) : $id;
}

function is_product_page() {
	if ( is_product() ) {
		return true;
	}

	global $post;
	if ( empty( $post ) || empty( $post->post_content ) ) {
		return false;
	}

	if (
		false !== strpos( $post->post_content, '[product_page' ) ||
		false !== strpos( $post->post_content, '[asnp_wepb_product' )
	) {
		return true;
	}

	return false;
}

function add_simple_bundle_items( $product ) {
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product || ! $product->is_type( Plugin::PRODUCT_TYPE ) ) {
		return;
	}

	$default_products = $product->get_default_products();
	if ( empty( $default_products ) ) {
		return;
	}

	$model = get_plugin()->container()->get( SimpleBundleItemsModel::class);
	$items = $model->get_bundle( $product->get_id() );
	if ( empty( $items ) ) {
		$quantities = get_quantities_from_bundle_items( $default_products );
		$default_products = get_product_ids_from_bundle_items( $default_products );
		if ( count( $default_products ) === count( $quantities ) ) {
			for ( $i = 0; $i < count( $default_products ); $i++ ) {
				$model->add( [
					'bundle_id' => $product->get_id(),
					'product_id' => (int) $default_products[ $i ],
					'quantity' => wc_stock_amount( $quantities[ $i ] ),
				] );
			}
		}
	}
}

function register_polyfills() {
	static $registered;
	if ( $registered ) {
		return;
	}

	global $wp_version;

	$handles = array(
		'react' => array( '17.0.2', array() ),
		'react-dom' => array( '17.0.2', array( 'react' ) ),
		'wp-i18n' => array( '6.0', array() ),
		'wp-hooks' => array( '6.0', array() ),
		'wp-api-fetch' => array( '6.0', array() ),
	);
	foreach ( $handles as $handle => $value ) {
		if ( ! version_compare( $wp_version, '5.9', '>=' ) && in_array( $handle, array( 'react', 'react-dom' ) ) ) {
			wp_deregister_script( $handle );
		}

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			wp_register_script(
				$handle,
				plugins_url( 'assets/js/vendor/' . $handle . '.js', ASNP_WEPB_PLUGIN_FILE ),
				$value[1],
				$value[0],
				true
			);
		}
	}

	$registered = true;
}

function added_product_bundle_type() {
	$ids = Products\get_products( [
		'type' => Plugin::PRODUCT_TYPE,
		'return' => 'ids',
	] );

	return ! empty( $ids );
}

/**
 * Maybe exchange price with multicurrency plugins.
 *
 * @param mixed  $price
 * @param string $type
 *
 * @return mixed
 */
function maybe_exchange_price( $price, $type = 'product' ) {
	if ( empty( $price ) ) {
		return $price;
	}

	return apply_filters( 'asnp_wepb_maybe_exchange_price', $price, $type );
}

function maybe_change_price( $price, $product, $price_type = 'price' ) {
	return apply_filters( 'asnp_wepb_maybe_change_price', $price, $product, $price_type );
}

function get_review() {
	return get_option( 'asnp_easy_product_bundle_review', array() );
}

function set_review( $review ) {
	return update_option( 'asnp_easy_product_bundle_review', $review );
}

function maybe_show_review() {
	$review = get_review();
	if ( isset( $review['dismissed'] ) ) {
		return false;
	}

	if ( ! added_product_bundle_type() ) {
		return false;
	}

	$schedule = strtotime( '+7 days' );
	if ( empty( $review['schedule'] ) ) {
		$review['schedule'] = $schedule;
		set_review( $review );
	} else {
		$schedule = (int) $review['schedule'];
	}

	if ( empty( $schedule ) || time() < $schedule ) {
		return false;
	}

	return true;
}

function is_json( $string ) {
	// Check if the string is empty or not a string
	if ( ! is_string( $string ) || empty( trim( $string ) ) ) {
		return false;
	}

	// Check if string starts with either { or [
	if ( ! in_array( $string[0], [ '{', '[' ] ) ) {
		return false;
	}

	// Attempt to decode
	json_decode( $string );

	return ( json_last_error() === JSON_ERROR_NONE );
}

function maybe_convert_items_to_json( $items ) {
	if ( empty( $items ) || ! is_string( $items ) ) {
		return $items;
	}

	if ( is_json( $items ) ) {
		return $items;
	}

	$items = explode( ',', $items );
	$items = array_map( function ( $item ) {
		$item = explode( ':', $item );
		if ( 3 === count( $item ) ) {
			$attributes = explode( '&', $item[2] );
			$attributes = array_reduce( $attributes, function ( $carry, $attribute ) {
				$attribute = explode( '=', $attribute );
				$carry[ $attribute[0] ] = $attribute[1];
				return $carry;
			}, [] );

			return [
				'id' => absint( $item[0] ),
				'qty' => ! empty( $item[1] ) ? wc_stock_amount( $item[1] ) : 0,
				'attributes' => $attributes,
			];
		}
		return [
			'id' => absint( $item[0] ),
			'qty' => ! empty( $item[1] ) ? wc_stock_amount( $item[1] ) : 0,
		];
	}, $items );

	$items = wp_json_encode( $items );

	return false !== $items ? $items : '';
}

function has_valid_discount( $item, $type_key = 'discount_type', $amount_key = 'discount', $discount_type = 'all' ) {
	return ! empty( $item[ $type_key ] ) &&
		'none' !== $item[ $type_key ] &&
		( 'all' === $discount_type || $item[ $type_key ] === $discount_type ) &&
		isset( $item[ $amount_key ] ) &&
		'' !== $item[ $amount_key ] &&
		0 <= (float) $item[ $amount_key ];
}
