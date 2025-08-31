<?php
/**
 * General Utility Functions for Admin and Core Operations
 *
 * This file defines the `Main` class, which provides a collection of static utility functions
 * for common tasks such as hook registration, authentication handling, nonce verification,
 * client IP detection, language filtering, and media attachment processing. These utilities
 * are designed to support both the admin UI and backend core logic.
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
 * Main Class for General Utility Functions
 *
 * This class provides a set of utility functions that perform common tasks, including
 * hook registration, authentication, nonce validation, and media file management,
 * along with various other helper functions to support plugin operations.
 */
final class Main {

	/**
	 * Registers a filter hook with a callback.
	 *
	 * This method allows you to add a filter to a specific WordPress hook. It supports
	 * dynamic plugin context, ensuring that the correct class and plugin information
	 * are passed along with the hook.
	 *
	 * @param string   $hook_name     The name of the hook to register.
	 * @param callable $callback      The callback function to run when the hook is fired.
	 * @param int      $priority      The priority at which the callback function should run.
	 * @param int      $accepted_args The number of arguments the callback function accepts.
	 * @return bool   True on success, false on failure.
	 */
	public static function add_filter( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		$current_plugin     = $GLOBALS['YD_CURRENT_PLUGIN'];
		$current_class_name = $GLOBALS['YD_CURRENT_PLUGIN_CLASS_NAME'];
		return add_filter(
			$hook_name,
			function () use ( $current_plugin, $current_class_name, $callback ) {
				$GLOBALS['YD_CURRENT_PLUGIN']            = $current_plugin;
				$GLOBALS['YD_CURRENT_PLUGIN_CLASS_NAME'] = $current_class_name;
				return call_user_func_array( $callback, func_get_args() );
			},
			$priority,
			$accepted_args
		);
	}

	/**
	 * Registers a filter hook with a callback.
	 *
	 * This method allows you to add a filter to a specific WordPress hook. It supports
	 * dynamic plugin context, ensuring that the correct class and plugin information
	 * are passed along with the hook.
	 *
	 * @param string   $hook_name     The name of the hook to register.
	 * @param callable $callback      The callback function to run when the hook is fired.
	 * @param int      $priority      The priority at which the callback function should run.
	 * @param int      $accepted_args The number of arguments the callback function accepts.
	 * @return bool   True on success, false on failure.
	 */
	public static function add_action( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		return self::add_filter( $hook_name, $callback, $priority, $accepted_args );
	}

	/**
	 * Sanitizes an array by only keeping keys that are in the accepted keys list.
	 *
	 * This method filters the provided array to only include elements with keys that
	 * are present in the `accepted_keys` array.
	 *
	 * @param array $source The source array to sanitize.
	 * @param array $accepted_keys The keys that should be kept in the sanitized array.
	 * @return array The sanitized array.
	 */
	public static function sanitize_array_by_keys( array $source, array $accepted_keys ): array {
		$sanitized = array();
		foreach ( $accepted_keys as $key ) {
			if ( isset( $source[ $key ] ) ) {
				$sanitized[ $key ] = $source[ $key ];
			}
		}
		return $sanitized;
	}

	/**
	 * Generates a new authentication key.
	 *
	 * This method generates a new UUID v4 authentication key, hashes it with SHA-256,
	 * and stores it in the WordPress options table. The raw key is returned for use.
	 *
	 * @return string The generated authentication key.
	 */
	public static function generate_auth_key(): string {
		$auth_key        = self::uuidv4();
		$hashed_auth_key = hash( 'sha256', $auth_key );
		update_option( $GLOBALS['YD_CURRENT_PLUGIN'] . '_auth_key', $hashed_auth_key, false );
		return $auth_key;
	}

	/**
	 * Validates an authentication key.
	 *
	 * This method checks if the provided authentication key matches the one stored in
	 * the WordPress options table.
	 *
	 * @param string $auth_key The authentication key to validate.
	 * @return bool True if the authentication key is valid, false otherwise.
	 */
	public static function validate_auth_key( string $auth_key ): bool {
		$hashed_target_auth_key  = hash( 'sha256', $auth_key );
		$hashed_current_auth_key = get_option( $GLOBALS['YD_CURRENT_PLUGIN'] . '_auth_key' );
		return $hashed_target_auth_key === $hashed_current_auth_key;
	}

	/**
	 * Validates the authentication key with basic authentication.
	 *
	 * This method checks the `Authorization` header for basic authentication and
	 * validates the provided authentication key.
	 *
	 * @return bool True if the authentication key is valid, false otherwise.
	 */
	public static function validate_auth_key_with_basic_auth(): bool {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$header_auth = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
		if ( $header_auth ) {
			if ( strtolower( substr( $header_auth, 0, 6 ) ) === 'basic ' ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
				$auth_key = base64_decode( substr( $header_auth, 6 ) );
				return self::validate_auth_key( $auth_key );
			}
		}
		return false;
	}

	/**
	 * Generates a UUID v4 string.
	 *
	 * This method generates a random UUID v4 value based on random bytes and returns it.
	 *
	 * @return string The generated UUID v4.
	 */
	public static function uuidv4(): string {
		$data = random_bytes( 16 );

		$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 ); // Set version to 0100.
		$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 ); // Set bits 6-7 to 10.

		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}

	/**
	 * Retrieves a specific header value.
	 *
	 * This method fetches the value of a specific HTTP header.
	 *
	 * @param string $field The name of the header field to retrieve.
	 * @return string|null The header value, or null if the field does not exist.
	 */
	public static function get_header( string $field ): ?string {
		$headers = getallheaders();
		foreach ( $headers as $key => $value ) {
			if ( strtolower( $key ) === strtolower( $field ) ) {
				return $value;
			}
		}
		return null;
	}

	/**
	 * Retrieves the accepted locales for the plugin.
	 *
	 * This method retrieves an array of accepted locales, as defined in the plugin options.
	 *
	 * @return array An array of accepted locales.
	 */
	public static function get_accepted_locales(): array {
		return json_decode( get_option( $GLOBALS['YD_CURRENT_PLUGIN'] . '_accepted_locales', '[]' ), true );
	}

	/**
	 * Retrieves the accepted languages for the plugin.
	 *
	 * This method returns a list of language names associated with the accepted locales.
	 *
	 * @return array An array of language names indexed by locale.
	 */
	public static function get_accepted_languages(): array {
		$languages = array();
		foreach ( self::get_accepted_locales() as $locale ) {
			$languages[ $locale ] = Language::get_display_name_by_locale( $locale );
		}
		return $languages;
	}

	/**
	 * Retrieves the URL for a file in the plugin directory.
	 *
	 * This method generates the URL for a file located in the plugin directory,
	 * based on the provided path and plugin slug.
	 *
	 * @param string $path The path to the file within the plugin directory.
	 * @param string $plugin_slug The slug of the plugin.
	 * @return string The URL of the file.
	 */
	public static function get_plugin_file_url( string $path, string $plugin_slug ): string {
		return plugins_url( $plugin_slug ) . '/' . $path;
	}

	/**
	 * Verifies the nonce for a specific action.
	 *
	 * This method verifies that the nonce passed in the request is valid for the
	 * specified action, preventing CSRF attacks.
	 *
	 * @param string $action The action for which the nonce is verified.
	 * @param bool   $exit_on_failure Whether to exit the script on failure.
	 * @return mixed The result of the nonce verification, or exits the script if failure occurs.
	 */
	public static function verify_nonce( string $action, bool $exit_on_failure = true ): mixed {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return wp_verify_nonce( $_REQUEST['_wpnonce'] ?? null, $action ) || ( $exit_on_failure && exit );
	}

	/**
	 * Returns the meta key for a specific key.
	 *
	 * This method prefixes the provided key with `_yd_meta_` for consistency in meta key usage.
	 *
	 * @param string $key The key for which the meta key is generated.
	 * @return string The generated meta key.
	 */
	public static function meta_key( string $key ): string {
		return '_yd_meta_' . $key;
	}

	/**
	 * Retrieves the client's IP address.
	 *
	 * This method attempts to determine the client's IP address, first checking
	 * the `X-Forwarded-For` header, then the `HTTP_CLIENT_IP`, and finally the
	 * `REMOTE_ADDR` server variable.
	 *
	 * @return string The client's IP address.
	 */
	public static function get_client_ip(): string {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return $_SERVER['HTTP_CLIENT_IP'];
		} else {
			return $_SERVER['REMOTE_ADDR'];
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput
	}

	/**
	 * Checks for plugin updates from GitHub and hooks into the WordPress
	 * update system.
	 *
	 * Compares the installed version with the latest version in the GitHub
	 * repository. If a newer version is available, it adds the update info
	 * to the `site_transient_update_plugins` filter.
	 *
	 * @global string $YD_CURRENT_PLUGIN Holds the name of the current plugin.
	 * @return void
	 */
	public static function check_updates() {
		$plugin_slug = $GLOBALS['YD_CURRENT_PLUGIN'];
		$plugin_file = sprintf( '%s/%s.php', $plugin_slug, $plugin_slug );
		add_action(
			"in_plugin_update_message-$plugin_file",
			function () use ( $plugin_file ) {
				?>
			<script>
				(() => {
					const buttonPluginDetails = document.querySelector(
						'tr[data-plugin="<?php echo esc_html( $plugin_file ); ?>"] .open-plugin-details-modal'
					);
					buttonPluginDetails.classList.remove('thickbox');
					buttonPluginDetails.href = buttonPluginDetails.href.split('?')[0];
				})();
			</script>
				<?php
			}
		);
		$new_header = get_plugin_data(
			"https://raw.githubusercontent.com/ygtdmr/$plugin_slug-wp-plugin/refs/heads/main/$plugin_slug.php"
		);
		if ( empty( $new_header ) ) {
			return;
		}
		$header      = get_plugin_data( sprintf( '%s/%s', WP_PLUGIN_DIR, $plugin_file ) );
		$new_version = $new_header['Version'];
		$version     = $header['Version'];
		add_filter(
			'site_transient_update_plugins',
			function ( $transient ) use ( $plugin_slug, $new_version, $version ) {
				if ( ! empty( $transient ) && version_compare( $new_version, $version, '>' ) ) {
					$transient->response[ "$plugin_slug/$plugin_slug.php" ] = ( (object) array(
						'id'          => $plugin_slug,
						'url'         => "https://github.com/ygtdmr/$plugin_slug-wp-plugin/releases/tag/v$new_version",
						'new_version' => $new_version,
						'package'     => "https://github.com/ygtdmr/$plugin_slug-wp-plugin/releases/download/v$new_version/$plugin_slug-$new_version.zip",
					) );
				}
				return $transient;
			}
		);
	}

	/**
	 * Inserts an attachment into WordPress.
	 *
	 * This method handles the process of inserting an attachment (file or URL) into
	 * the WordPress media library. It checks for existing attachments, handles
	 * file uploads, and processes the attachment metadata.
	 *
	 * @param mixed       $value           The file or URL to insert.
	 * @param bool        $is_url          Whether the value is a URL or not.
	 * @param string|null $accepted_type The accepted MIME type for the attachment.
	 * @param int|null    $parent_post_id The post ID to attach the media to.
	 * @return int|bool The attachment ID if successful, or false on failure.
	 */
	public static function insert_attachment( $value, bool $is_url = false, ?string $accepted_type = null, int $parent_post_id = null ): int|bool {
		$exists_query = new \WP_Query(
			array(
				'posts_per_page' => 1,
				'post_type'      => 'attachment',
				// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
				'name'           => sanitize_title( pathinfo( $is_url ? basename( $value ) : $_FILES[ $value ]['name'] ?? '', PATHINFO_FILENAME ) ),
			)
		);

		if ( $exists_query->have_posts() ) {
			return $exists_query->posts[0]->ID;
		}

		$upload;

		if ( $is_url ) {
			$http     = new \WP_Http();
			$response = $http->request( $value );

			$is_error = ( $response instanceof \WP_Error ) || ( 200 !== $response['response']['code'] );
			if ( $is_error ) {
				return false;
			}

			$upload = wp_upload_bits( basename( $value ), null, $response['body'] );
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			$file   = $_FILES[ $value ];
			$data   = Data_Manager::read_file_from_request( $value );
			$upload = wp_upload_bits( $file['name'], null, $data );
		}

		if ( ! empty( $upload['error'] ) ) {
			return false;
		}

		$file_path = $upload['file'];

		$file_name        = basename( $file_path );
		$file_type        = wp_check_filetype( $file_name, null );
		$attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
		$wp_upload_dir    = wp_upload_dir();

		if ( ! empty( $accepted_type ) && strpos( $file_type['type'], $accepted_type ) !== 0 ) {
			return false;
		}

		$post_info = array(
			'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
			'post_mime_type' => $file_type['type'],
			'post_title'     => $attachment_title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );

		require_once ABSPATH . 'wp-admin/src/image.php';

		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );

		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
	}
}
