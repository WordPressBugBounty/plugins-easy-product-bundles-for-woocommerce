<?php

namespace AsanaPlugins\WooCommerce\ProductBundles\Models;

defined( 'ABSPATH' ) || exit;

class SimpleBundleItemsModel extends BaseModel {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'asnp_wepb_simple_bundle_items';
		$this->primary_key = 'id';
		$this->version     = '1.0';
	}

	/**
	 * Get columns and formats
	 *
	 * @since   1.0.0
	 *
	 * @return  array
	 */
	public function get_columns() {
		return array(
			'id'         => '%d',
			'bundle_id'  => '%d',
			'product_id' => '%d',
			'quantity'   => '%d',
		);
	}

	/**
	 * Get default column values.
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_column_defaults() {
		return [ 'quantity' => 1 ];
	}

	public function add( array $args ) {
		$args['bundle_id']  = ! empty( $args['bundle_id'] ) ? absint( $args['bundle_id'] ) : 0;
		$args['product_id'] = ! empty( $args['product_id'] ) ? absint( $args['product_id'] ) : 0;
		$args['quantity']   = ! empty( $args['quantity'] ) ? absint( $args['quantity'] ) : 1;
		if ( 0 >= $args['bundle_id']   || 0 >= $args['product_id'] ) {
			return false;
		}

		$id = $this->insert( $args, 'simple_bundle_item' );

		return $id ? $id : false;
	}

	public function get_item( $bundle_id, $product_id, $output = OBJECT ) {
		$bundle_id  = absint( $bundle_id );
		$product_id = absint( $product_id );
		if ( 0 >= $bundle_id || 0 >= $product_id ) {
			return false;
		}

		global $wpdb;

		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE bundle_id = %d AND product_id = %d LIMIT 1", $bundle_id, $product_id ), $output );

		return $item ? $item : false;
	}

	public function delete_bundle( $bundle_id ) {
		$bundle_id = absint( $bundle_id );
		if ( 0 >= $bundle_id ) {
			return false;
		}

		global $wpdb;
		return $wpdb->delete( $this->table_name, array( 'bundle_id' => $bundle_id ), array( '%d' ) );
	}

	public function get_product_parents( $product_id ) {
		$product_id = absint( $product_id );
		if ( 0 >= $product_id ) {
			return false;
		}

		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT bundle_id FROM {$this->table_name} WHERE product_id = %d", $product_id ) );
	}

	public function get_bundle( $bundle_id ) {
		$bundle_id  = absint( $bundle_id );
		if ( 0 >= $bundle_id ) {
			return false;
		}

		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE bundle_id = %d", $bundle_id ) );
	}

	public function get_bundles() {
		global $wpdb;
		return $wpdb->get_col( "SELECT DISTINCT bundle_id FROM {$this->table_name}" );
	}

}
