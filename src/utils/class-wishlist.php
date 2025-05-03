<?php
/**
 * Wishlist Management for WooCommerce Customers
 *
 * This file defines the `Wishlist` class, which provides functionality for managing customer wishlists.
 * It supports both the XTS and YITH wishlist plugins. The class includes methods for retrieving wishlist items,
 * adding or removing products from the wishlist, and saving changes. It also checks for support of each wishlist plugin.
 *
 * @package YD\Core
 * @subpackage Utils
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Utils\WC\Customer;

use YD\Utils\WC;
use YD\Utils\YITH;
use YD\Utils\XTS;

defined( 'ABSPATH' ) || exit;

/**
 * Wishlist class handles the management of customer wishlists, supporting both the XTS and YITH wishlist plugins.
 * It includes methods for retrieving wishlist items, adding/removing products, and saving changes.
 */
final class Wishlist {
	/**
	 * Core type constant for XTS wishlist
	 */
	const CORE_TYPE_XTS = 1;

	/**
	 * Core type constant for YITH wishlist
	 */
	const CORE_TYPE_YITH = 2;

	/**
	 * The current wishlist core instance (either XTS or YITH)
	 *
	 * @var object
	 */
	private $core;

	/**
	 * The type of the wishlist core being used (XTS or YITH)
	 *
	 * @var int
	 */
	private $core_type;

	/**
	 * Wishlist constructor.
	 *
	 * Checks for support for either the XTS or YITH wishlist plugins and initializes the corresponding core.
	 */
	public function __construct() {
		if ( XTS::is_support_wishlist() ) {
			$this->core_type = self::CORE_TYPE_XTS;
			$this->core      = XTS::get_wishlist();
		} elseif ( YITH::is_support_wishlist() ) {
			$this->core_type = self::CORE_TYPE_YITH;
			$this->core      = YITH::get_wishlist();
		}
	}

	/**
	 * Checks if either XTS or YITH wishlist plugin is supported.
	 *
	 * @return bool True if supported, false otherwise.
	 */
	public static function is_support(): bool {
		return XTS::is_support_wishlist() || YITH::is_support_wishlist();
	}

	/**
	 * Retrieves all items in the wishlist.
	 *
	 * @return array List of product information for all items in the wishlist.
	 */
	public function get_items(): array {
		switch ( $this->core_type ) {
			case self::CORE_TYPE_XTS:
				return array_map(
					function ( array $item ) {
						return WC\Product::get_info( $item['product_id'] );
					},
					$this->core->get_all()
				);
			case self::CORE_TYPE_YITH:
				return array_map(
					function ( \YITH_WCWL_Wishlist_Item $item ) {
						return WC\Product::get_info( $item['product_id'] );
					},
					array_values( $this->core->get_items() )
				);
			default:
				return array();
		}
	}

	/**
	 * Edits the wishlist by adding or removing an item.
	 *
	 * @param array $item The item data to edit (contains product_id and is_remove flag).
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function edit( array $item ): bool|\WP_Error {
		try {
			$id = $item['product_id'];
			if ( empty( $item['is_remove'] ) ) {
				$this->add_item( $id );
			} else {
				$this->remove_item( $id );
			}
		} catch ( \Excepton $e ) {
			return new \WP_Error( 'wishlist-error-' . $e->getCode(), $e->getMessage(), array( 'status' => 400 ) );
		}
		$this->save();
		return true;
	}

	/**
	 * Adds a product to the wishlist.
	 *
	 * @param int $product_id The ID of the product to add to the wishlist.
	 * @return void
	 */
	private function add_item( int $product_id ) {
		switch ( $this->core_type ) {
			case self::CORE_TYPE_XTS:
				$this->core->add( $product_id, 0 );
				break;
			case self::CORE_TYPE_YITH:
				$this->core->add_product( $product_id );
				break;
		}
	}

	/**
	 * Removes a product from the wishlist.
	 *
	 * @param int $product_id The ID of the product to remove from the wishlist.
	 * @return void
	 */
	private function remove_item( int $product_id ) {
		switch ( $this->core_type ) {
			case self::CORE_TYPE_XTS:
				$this->core->remove( $product_id, 0 );
				break;
			case self::CORE_TYPE_YITH:
				$this->core->remove_product( $product_id );
				break;
		}
	}

	/**
	 * Saves the changes to the wishlist.
	 *
	 * @return void
	 */
	private function save() {
		switch ( $this->core_type ) {
			case self::CORE_TYPE_YITH:
				$this->core->save();
				break;
		}
	}
}
