<?php
/**
 * Admin Utilities for Plugin Action Status Management
 *
 * This file defines the `Admin` class, which provides static utility methods for managing
 * plugin-specific action statuses via WordPress options. It supports reading, updating,
 * and deleting serialized status data based on the plugin context.
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
 * Admin Class for Action Status Management
 *
 * This class provides utility methods for managing the status of plugin-specific
 * actions. It allows retrieving, setting, and deleting serialized status data
 * associated with a specific plugin action type in the WordPress options table.
 */
final class Admin {

	/**
	 * Retrieves the status of a specific plugin action type.
	 *
	 * This method fetches the serialized status data for a given action type
	 * from the WordPress options table. If no status exists, it returns an empty array.
	 *
	 * @param string $type The action type to retrieve the status for.
	 * @return array The status data of the specified action type.
	 */
	public static function get_action_status( string $type ): array {
		// phpcs:ignore Universal.Operators.DisallowShortTernary
		return json_decode( get_option( self::get_action_status_name( $type ) ) ?: '{}', true );
	}

	/**
	 * Sets the status for a specific plugin action type.
	 *
	 * This method updates or deletes the status of a given action type in the
	 * WordPress options table. If the status is `false`, the status will be deleted.
	 * Otherwise, the status will be updated with the given data.
	 *
	 * @param string     $type   The action type to set the status for.
	 * @param array|bool $status The status data or `false` to delete the status.
	 * @return void
	 */
	public static function set_action_status( string $type, array|bool $status ) {
		if ( false === $status ) {
			delete_option( self::get_action_status_name( $type ) );
		} else {
			update_option( self::get_action_status_name( $type ), wp_json_encode( $status ), false );
		}
	}

	/**
	 * Generates the option name for storing action status.
	 *
	 * This method generates a unique option name based on the current plugin
	 * slug and the specified action type.
	 *
	 * @param string $type The action type to generate the option name for.
	 * @return string The generated option name for the action status.
	 */
	private static function get_action_status_name( string $type ): string {
		return sprintf( '%s-status-%s', $GLOBALS['YD_CURRENT_PLUGIN'], $type );
	}
}
