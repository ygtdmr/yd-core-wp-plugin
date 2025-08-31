<?php
/**
 * Admin Page Management for the Plugin
 *
 * This file defines the `Page` class, which manages the creation and handling of admin pages
 * within the WordPress dashboard. It includes functionality for loading pages, handling nonce verification,
 * processing AJAX actions, and managing page-specific assets.
 *
 * @package YD\Core
 * @subpackage Admin\Page
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Admin;

use YD\Utils;
use YD\Data_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Page class represents a page in the admin area of the WordPress dashboard. It handles various functionalities
 * such as loading assets, handling actions, verifying nonces, and displaying notices. The page can be either independent or
 * tied to a parent page and can be customized with specific rules, actions, and notices.
 */
abstract class Page {
	/**
	 * Holds the sanitized data for the page.
	 *
	 * @var mixed
	 */
	protected $data;

	/**
	 * Loads the page content and processes any actions, nonces, and assets.
	 *
	 * @return void
	 */
	public function load() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$callback = function () {
			// phpcs:ignore WordPress.Security.NonceVerification
			$this->data = ( new Data_Manager( $this->get_rules(), $_REQUEST ) )->sanitize();
			$nonce      = $this->get_wp_nonce();

			if ( ! empty( $_SERVER['REQUEST_METHOD'] ) ) {
				if ( ! empty( $nonce ) && 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
					Utils\Main::verify_nonce( $nonce );
				}

				if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
					$this->do_action_post();
				}
			}

			$this->load_assets();

			$actions = $this->get_actions();
			// phpcs:ignore WordPress.Security.NonceVerification
			if ( ! empty( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array_keys( $actions ), true ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
				$action_class = $actions[ $_REQUEST['action'] ];
				new $action_class();
			} else {
				$this->callback();
			}
		};

		$parent_slug = $this->get_parent_slug();

		$hook = empty( $parent_slug ) ?
			add_menu_page(
				$this->get_title(),
				$this->get_menu_title(),
				$this->get_capability(),
				$this->get_slug(),
				( ! method_exists( $this, 'callback' ) || $this->is_independent() ) ? null : $callback,
				$this->get_icon_url()
			) :
			add_submenu_page(
				$parent_slug,
				$this->get_title(),
				$this->get_menu_title(),
				$this->get_capability(),
				$this->is_independent() ? $this->get_slug() : self::get_root_slug( $this->get_slug() ),
				( ! method_exists( $this, 'callback' ) || $this->is_independent() ) ? null : $callback
			);

		Utils\Main::add_action(
			"load-$hook",
			function () {
				$this->on_load();
			}
		);

		$notice = $this->get_notice();
		if ( ! empty( $notice ) ) {
			$is_visible_all_page     = boolval( $notice['visible_all_page'] ?? false );
			$is_visible_current_page = boolval( $notice['visible_current_page'] ?? true );

			$is_visible  = $is_visible_all_page && ( $this->get_slug() !== self::get_current_slug() );
			$is_visible |= $is_visible_current_page && ( $this->get_slug() === self::get_current_slug() );

			if ( $is_visible ) {
				Utils\Main::add_action(
					'admin_notices',
					function () use ( $notice ) {
						wp_admin_notice( $notice['message'], $notice['args'] );
					}
				);
			}
		}
	}

	/**
	 * Determines if the page is independent (not part of a parent page).
	 *
	 * @return bool True if the page is independent, false otherwise.
	 */
	protected function is_independent(): bool {
		return false; }

	/**
	 * Determines if the page is enabled.
	 *
	 * @return bool True if the page is enabled, false otherwise.
	 */
	protected function is_enabled(): bool {
		return true; }

	/**
	 * Handles post actions for the page.
	 *
	 * @return void
	 */
	protected function do_action_post() {}

	/**
	 * Retrieves the rules for the page.
	 *
	 * @return array An array of rules for the page.
	 */
	protected function get_rules(): array {
		return array(); }

	/**
	 * Retrieves the WordPress nonce for the page.
	 *
	 * @return string The WordPress nonce.
	 */
	protected function get_wp_nonce(): string {
		return ''; }

	/**
	 * Retrieves the parent slug for the page, if any.
	 *
	 * @return string The parent slug.
	 */
	protected function get_parent_slug(): string {
		return ''; }

	/**
	 * Retrieves the icon URL for the page.
	 *
	 * @return string The icon URL.
	 */
	protected function get_icon_url(): string {
		return ''; }

	/**
	 * Retrieves the actions available for the page.
	 *
	 * @return array An array of available actions.
	 */
	protected function get_actions(): array {
		return array(); }

	/**
	 * Retrieves the notice for the page, if any.
	 *
	 * @return array The notice array.
	 */
	protected function get_notice(): array {
		return array(); }

	/**
	 * Executes any additional logic when the page is loaded.
	 *
	 * @return void
	 */
	protected function on_load() {}

	/**
	 * Retrieves the title of the page.
	 *
	 * @return string The page title.
	 */
	abstract protected function get_title(): string;

	/**
	 * Retrieves the menu title for the page.
	 *
	 * @return string The menu title.
	 */
	abstract protected function get_menu_title(): string;

	/**
	 * Retrieves the capability required to access the page.
	 *
	 * @return string The required capability.
	 */
	abstract protected function get_capability(): string;

	/**
	 * Retrieves the slug for the page.
	 *
	 * @return string The slug.
	 */
	abstract protected function get_slug(): string;

	/**
	 * Loads the assets (styles and scripts) for the page.
	 *
	 * @return void
	 */
	public static function load_assets() {
		wp_enqueue_media();

		self::enqueue_style( 'wp-color-picker', true );
		self::enqueue_style( 'main.css', false, YD_CORE );
		self::enqueue_style( 'ui-input.css', false, YD_CORE );

		self::enqueue_script( 'wp-color-picker', true );
		self::enqueue_script( 'lib/wp-color-picker-alpha.js', false, YD_CORE );
		self::enqueue_script( 'init.js', false, YD_CORE );
		self::enqueue_script( 'ui-input/selection.js', false, YD_CORE );
		self::enqueue_script( 'ui-input/selection-media.js', false, YD_CORE );
		self::enqueue_script( 'ui-input/dropdown.js', false, YD_CORE );
		self::enqueue_script( 'ui-input/color-picker.js', false, YD_CORE );

		global $_wp_admin_css_colors;

		$current_color_scheme = get_user_option( 'admin_color', get_current_user_id() );
		$colors               = $_wp_admin_css_colors[ $current_color_scheme ]->colors;
		?>
		<style>:root {
		<?php
		foreach ( $colors as $index => $color ) :
			?>
			--wp-admin-theme-color-<?php echo( esc_html( "$index:$color;" ) ); ?> <?php endforeach; ?>}</style>
		<script>
			window.yd_core = {
				language: { accepted: <?php echo wp_json_encode( \YD\Utils\Main::get_accepted_languages() ); ?>, text: <?php echo wp_json_encode( self::get_language_texts() ); ?> },
				page: { block: {} },
				url: { page: {} },
				wp_nonce: {},
				is_support: {wc: <?php echo ( Utils\WC::is_support() ? 'true' : 'false' ); ?> }
			};
		</script>
		<?php
	}

	/**
	 * Retrieves language texts for the page.
	 *
	 * @return array An array of language texts.
	 */
	public static function get_language_texts(): array {
		$json_path = sprintf( '%s/%s/assets/json/admin/language-texts.json', WP_PLUGIN_DIR, $GLOBALS['YD_CURRENT_PLUGIN'] );

		$data = json_decode( Data_Manager::read_file( $json_path ), true );
		foreach ( $data as $text => $domain ) {
			// phpcs:ignore WordPress.WP.I18n, Universal.Operators.DisallowShortTernary
			$data[ $text ] = __( $text, $domain ?: $GLOBALS['YD_CURRENT_PLUGIN'] );
		}

		return $data;
	}

	/**
	 * Retrieves the root slug for the page, optionally appending a provided slug.
	 *
	 * @param string|null $slug The slug to append, if any.
	 * @return string The root slug.
	 */
	private static function get_root_slug( ?string $slug = null ): string {
		return $GLOBALS['YD_CURRENT_PLUGIN'] . ( $slug ? '-' . $slug : '' );
	}

	/**
	 * Retrieves the current slug for the page, if any.
	 *
	 * @return string|null The current slug, or null if not applicable.
	 */
	public static function get_current_slug(): ?string {
		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		$is_any_yd_page = ( strpos( $_SERVER['REQUEST_URI'], '/wp-admin/admin.php' ) !== false ) &&
							( strpos( $_REQUEST['page'], $GLOBALS['YD_CURRENT_PLUGIN'] ) !== false );

		return $is_any_yd_page ? preg_replace( '/' . $GLOBALS['YD_CURRENT_PLUGIN'] . '(?:\-)?/', '', $_REQUEST['page'] ) : null;
		// phpcs:enable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	}

	/**
	 * Retrieves the URL for a page, optionally appending a provided slug.
	 *
	 * @param string|null $slug The slug to append, if any.
	 * @return string The URL for the page.
	 */
	public static function get_url( ?string $slug = null ): string {
		$url = add_query_arg( array( 'page' => self::get_root_slug( $slug ) ), 'admin.php' );
		$url = html_entity_decode( esc_url( $url ) );
		return $url;
	}

	/**
	 * Redirects the browser to a specific page URL, optionally appending a provided slug.
	 *
	 * @param string|null $slug The slug to append, if any.
	 * @return void
	 */
	public static function redirect( ?string $slug = null ) {
		header( 'Location: ' . self::get_url( $slug ) );
		exit;
	}

	/**
	 * Redirects the browser to a specific action location, optionally appending a nonce and additional arguments.
	 *
	 * @param string      $action The action to redirect to.
	 * @param string|null $nonce The nonce to include in the URL, if any.
	 * @param array       $arg Additional arguments to append to the URL.
	 * @return void
	 */
	public static function redirect_action( string $action, ?string $nonce = null, array $arg = array() ) {
		header( 'Location: ' . self::get_action_location( $action, $nonce, $arg ) );
		exit;
	}

	/**
	 * Retrieves the location URL for a specific action, optionally including a nonce and additional arguments.
	 *
	 * @param string      $action The action to include in the URL.
	 * @param string|null $nonce The nonce to include, if any.
	 * @param array       $arg Additional arguments to include in the URL.
	 * @return string The action location URL.
	 */
	public static function get_action_location( string $action, ?string $nonce = null, array $arg = array() ): string {
		$args = array(
			// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			'page'   => $_REQUEST['page'],
			'action' => $action,
		);
		if ( ! empty( $nonce ) ) {
			$args['_wpnonce'] = wp_create_nonce( $nonce );
		}
		$url = add_query_arg( $args + $arg, 'admin.php' );
		return html_entity_decode( esc_url( $url ) );
	}

	/**
	 * Enqueues a script for the page.
	 *
	 * @param string      $value The script file to enqueue.
	 * @param bool        $is_handle Whether the script is registered by handle.
	 * @param string|null $plugin_slug The plugin slug to use, if any.
	 * @return void
	 */
	public static function enqueue_script( string $value, bool $is_handle = false, ?string $plugin_slug = null ) {
		if ( $is_handle ) {
			wp_enqueue_script( $value );
		} else {
			$plugin_slug = $plugin_slug ?? $GLOBALS['YD_CURRENT_PLUGIN'];
			$handle      = str_replace( '.js', '', str_replace( '/', '-', $value ) );
			$value_min   = str_replace( '.js', '.min.js', $value );

			if ( file_exists( WP_PLUGIN_DIR . "/$plugin_slug/assets/js/admin/$value_min" ) ) {
				$value = $value_min;
			}
			wp_enqueue_script(
				sprintf( 'admin-%s-script-%s', $plugin_slug, $handle ),
				Utils\Main::get_plugin_file_url( "assets/js/admin/$value", $plugin_slug ),
				array( 'jquery' ),
				filemtime( WP_PLUGIN_DIR . "/$plugin_slug/assets/js/admin/$value" ),
				false
			);
		}
	}

	/**
	 * Enqueues a style for the page.
	 *
	 * @param string      $value The style file to enqueue.
	 * @param bool        $is_handle Whether the style is registered by handle.
	 * @param string|null $plugin_slug The plugin slug to use, if any.
	 * @return void
	 */
	public static function enqueue_style( string $value, bool $is_handle = false, ?string $plugin_slug = null ) {
		if ( $is_handle ) {
			wp_enqueue_style( $value );
		} else {
			$plugin_slug = $plugin_slug ?? $GLOBALS['YD_CURRENT_PLUGIN'];
			$handle      = str_replace( '.css', '', str_replace( '/', '-', $value ) );
			wp_enqueue_style(
				sprintf( 'admin-%s-style-%s', $plugin_slug, $handle ),
				Utils\Main::get_plugin_file_url( "assets/css/admin/$value", $plugin_slug ),
				false,
				filemtime( WP_PLUGIN_DIR . "/$plugin_slug/assets/css/admin/$value" ),
			);
		}
	}
}



?>