<?php
/**
 * YD Core
 *
 * @package YD
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.1
 *
 * Plugin Name: YD Core
 * Plugin URI: https://github.com/ygtdmr/yd-core-wp-plugin
 * Description: Provides the requirements of YD based plugins.
 * Author: Yigit Demir
 * Author URI: https://github.com/ygtdmr
 * Version: 1.0.1
 * Text Domain: yd-core
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 10.1
 * WC tested up to: 10.1.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Updates the current plugin slug in the global scope.
 *
 * This function assigns the current plugin slug to the global variable
 * `$YD_CURRENT_PLUGIN` to be used across the plugin.
 *
 * @return void
 */
function yd_core_update_slug() {
	if ( empty( $GLOBALS['YD_CURRENT_PLUGIN'] ) ) {
		$GLOBALS['YD_CURRENT_PLUGIN'] = YD_CORE;
	}
}

/**
 * Initializes core functionality before WooCommerce initializes.
 *
 * This function hooks into the `before_woocommerce_init` action to declare
 * compatibility with WooCommerce features such as remote logging and custom order tables.
 * It also defines the YD_CORE constant if not already defined and includes necessary library files.
 *
 * @return void
 */
function yd_core_before_init() {
	add_action(
		'before_woocommerce_init',
		function () {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'remote_logging', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	);

	if ( ! defined( 'YD_CORE' ) ) {
		define( 'YD_CORE', 'yd-core' );
	}

	yd_core_update_slug();

	require_once __DIR__ . '/src/class-library.php';
	require_once __DIR__ . '/src/class-core-library.php';

	new \YD\Library\Core_Library();
	unset( $GLOBALS['YD_CURRENT_PLUGIN'] );
}

yd_core_before_init();

/**
 * Initializes core plugin functionality.
 *
 * This function is hooked into the `init` action and loads the plugin textdomain
 * for translation, adds custom body class to the admin panel, and ensures that
 * the plugin slug is updated.
 *
 * @return void
 */
function yd_core_init() {
	yd_core_update_slug();

	get_translations_for_domain( YD_CORE );

	if ( is_admin() ) {
		\YD\Utils\Main::check_updates();
		add_filter(
			'admin_body_class',
			function ( $classes ) {
				return $classes . sprintf( ' %s ', YD_CORE );
			}
		);
	}
	unset( $GLOBALS['YD_CURRENT_PLUGIN'] );
}

add_action( 'init', 'yd_core_init' );
