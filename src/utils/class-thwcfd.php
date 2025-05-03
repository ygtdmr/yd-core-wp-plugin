<?php
/**
 * THWCFD (Checkout Field Editor) Integration Utilities
 *
 * This file defines the `THWCFD` class, which provides helper functions for working with
 * the "Checkout Field Editor" (THWCFD) plugin. It includes methods to detect plugin support,
 * retrieve custom checkout fields, and update user address metadata based on custom fields.
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
 * Class THWCFD
 *
 * This class contains utility methods for interacting with the THWCFD plugin, including checking plugin support,
 * retrieving custom fields, and updating user custom address data.
 */
final class THWCFD {

	/**
	 * Check if the plugin is supported based on THWCFD_VERSION constant.
	 *
	 * @return bool True if supported, false otherwise.
	 */
	public static function is_support(): bool {
		return defined( 'THWCFD_VERSION' );
	}

	/**
	 * Get the fields for a specific type.
	 *
	 * @param string $type The type of fields to retrieve.
	 *
	 * @return array The array of fields for the specified type.
	 */
	public static function get_fields( $type ): array {
		return \THWCFD_Utils::get_fields( $type );
	}

	/**
	 * Update the user's custom address based on the provided new address data.
	 *
	 * @param int    $user_id     The user ID.
	 * @param string $type       The type of address (e.g., 'billing', 'shipping').
	 * @param array  $new_address An associative array of new address data.
	 * @return void
	 */
	public static function update_user_custom_address( int $user_id, string $type, array $new_address ) {
		$custom_fields = array_keys(
			array_filter(
				\THWCFD_Utils::get_fields( $type ),
				function ( $props ) {
					return boolval( $props['custom'] ?? 0 );
				}
			)
		);

		foreach ( $new_address as $key => $value ) {
			$raw_key = $type . '_' . $key;
			if ( in_array( $raw_key, $custom_fields, true ) ) {
				update_user_meta( $user_id, $raw_key, $value );
			}
		}
	}
}
