<?php
/**
 * YITH Wishlist Support for WooCommerce
 *
 * This file defines the `YITH` class, which handles integration with the YITH wishlist feature for WooCommerce.
 * It includes methods to check if the YITH wishlist plugin is active and to retrieve the wishlist functionality
 * based on the current user's session or logged-in status. The class allows interaction with the wishlist
 * functionality provided by YITH.
 *
 * @package YD\Core
 * @subpackage Utils
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * YITH class provides functionality to interact with the YITH Wishlist plugin for WooCommerce.
 * It includes methods to check if the YITH Wishlist plugin is active and to retrieve the wishlist
 * based on the user's session or logged-in status.
 */
final class YITH {

	/**
	 * Checks if the YITH Wishlist plugin is active.
	 *
	 * This method checks whether the YITH Wishlist plugin constant `YITH_WCWL` is defined,
	 * which indicates whether the plugin is active.
	 *
	 * @return bool True if the YITH Wishlist plugin is active, false otherwise.
	 */
	public static function is_support_wishlist(): bool {
		return defined( 'YITH_WCWL' );
	}

	/**
	 * Retrieves the wishlist for the current user or session.
	 *
	 * This method checks if the user is logged in. If logged in, it retrieves the wishlist
	 * associated with the current user. If not logged in, it retrieves the wishlist for the
	 * current session. If no wishlist exists, a new one is created.
	 *
	 * @return \YITH_WCWL_Wishlist The wishlist object.
	 */
	public static function get_wishlist(): \YITH_WCWL_Wishlist {
		$wishlist;
		$session = YITH_WCWL_Session();

		if ( is_user_logged_in() ) {
			$wishlist = current( \YITH_WCWL_Wishlist_Factory::get_wishlists( array( 'user_id' => get_current_user_id() ) ) );
			if ( $session->has_session() ) {
				$wishlist->set_session_id( $session->maybe_get_session_id() );
				$wishlist->save();
				$session->finalize_session();
			}
		} elseif ( $session->has_session() ) {
			$wishlist = current( \YITH_WCWL_Wishlist_Factory::get_wishlists( array( 'session_id' => $session->get_session_id() ) ) );
		} else {
			$wishlist = new \YITH_WCWL_Wishlist();
			$wishlist->set_session_id( $session->get_session_id() );
			$wishlist->save();
		}

		return $wishlist;
	}
}
