<?php
/**
 * XTS Wishlist Support for WooCommerce
 *
 * This file defines the `XTS` class, which handles integration with the XTS wishlist feature for WooCommerce.
 * It includes methods to check for compatibility with the WoodMart theme and to retrieve the wishlist functionality
 * when supported. The class provides a mechanism to interact with the wishlist functionality.
 *
 * @package YD\Core
 * @subpackage Utils
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Utils;

use XTS\WC_Wishlist;

defined( 'ABSPATH' ) || exit;

/**
 * XTS Class for WooCommerce Wishlist Integration
 *
 * This class handles the interaction with the XTS wishlist feature for WooCommerce. It provides methods to check
 * if the WoodMart theme is active, whether the wishlist feature is enabled, and retrieves the wishlist object
 * for further operations. The class ensures compatibility with the WoodMart theme and wishlist settings.
 */
final class XTS {

	/**
	 * Checks if WoodMart theme is supported.
	 *
	 * This method determines if the WoodMart theme is active by checking
	 * for the existence of the WOODMART_VERSION constant.
	 *
	 * @return bool True if WoodMart theme is supported, false otherwise.
	 */
	public static function is_support_woodmart(): bool {
		return defined( 'WOODMART_VERSION' );
	}

	/**
	 * Checks if the wishlist functionality is supported.
	 *
	 * This method checks if both the WoodMart theme is active and the wishlist
	 * option is enabled within the theme settings.
	 *
	 * @return bool True if wishlist is supported, false otherwise.
	 */
	public static function is_support_wishlist(): bool {
		return self::is_support_woodmart() && woodmart_get_opt( 'wishlist', 1 );
	}

	/**
	 * Retrieves the wishlist object.
	 *
	 * This method creates a new instance of the WC_Wishlist\Wishlist class
	 * to interact with the wishlist functionality.
	 *
	 * @return WC_Wishlist\Wishlist A new wishlist object.
	 */
	public static function get_wishlist(): WC_Wishlist\Wishlist {
		return new WC_Wishlist\Wishlist();
	}
}
