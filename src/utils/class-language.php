<?php
/**
 * Language Utilities for Admin Display
 *
 * This file defines the `Language` class, which provides helper methods for retrieving
 * and translating language names used in the admin interface. It supports loading language
 * data from a JSON file and applying translations to display-friendly names.
 *
 * @package YD\Core
 * @subpackage Utils
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Utils;

use YD\Data_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Language Class for Admin Interface
 *
 * This class provides methods for retrieving and translating language names
 * used in the admin interface. It allows loading language data from a JSON
 * file and applying translations to make the display names more user-friendly.
 */
final class Language {

	/**
	 * Retrieves the display name of a language by its locale.
	 *
	 * This method fetches the language name associated with the provided locale.
	 * It optionally translates the name if required.
	 *
	 * @param string $target_locale The locale of the language to fetch.
	 * @param bool   $is_translated Whether to return the translated name or not.
	 * @return string The display name of the language.
	 */
	public static function get_display_name_by_locale( string $target_locale, bool $is_translated = true ): string {
		$language_names = self::get_language_names();
		return $is_translated ? self::translate_language_name( $language_names[ $target_locale ] ) : $language_names[ $target_locale ];
	}

	/**
	 * Loads the language names from the languages JSON file.
	 *
	 * This method loads the language names data from a JSON file located
	 * in the plugin directory. It decodes the JSON content into an associative array.
	 *
	 * @return array An associative array of language names indexed by locale.
	 */
	public static function get_language_names(): array {
		$json_path = sprintf( '%s/%s/assets/json/admin/languages.json', WP_PLUGIN_DIR, YD_CORE );
		return json_decode( Data_Manager::read_file( $json_path ), true );
	}

	/**
	 * Translates a language name to its translated version.
	 *
	 * This method checks if the language name contains a specific format (e.g., `English (US)`),
	 * and applies translations accordingly. It uses the `__()` function to return the translated
	 * name for display.
	 *
	 * @param string $name The language name to translate.
	 * @return string The translated language name.
	 */
	public static function translate_language_name( string $name ): string {
		if ( preg_match( '/(.+)\s\((.+)\)/', $name, $matches ) ) {
			// phpcs:ignore WordPress.WP.I18n
			return sprintf( '%s (%s)', __( $matches[1] ), __( $matches[2] ) );
		} else {
			// phpcs:ignore WordPress.WP.I18n
			return __( $name );
		}
	}
}
