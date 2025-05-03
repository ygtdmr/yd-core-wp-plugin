<?php
/**
 * Core Library Loader for Plugin Initialization
 *
 * This file defines the `Core` class, which extends the base `Library` class to load all
 * necessary core components of the plugin. It specifies the directory structure for loading
 * classes and utilities including admin pages, REST API handlers, and view inputs.
 *
 * @package YD\Core
 * @subpackage Library
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Library;

defined( 'ABSPATH' ) || exit;

/**
 * Core_Library class extends the `Library` class to load core components of the plugin
 * such as admin pages, REST API handlers, and view inputs. It specifies the
 * directory structure from which classes and utilities should be loaded.
 */
final class Core_Library extends \YD\Library {

	/**
	 * Returns the directory where the core components are located.
	 *
	 * @return string The directory path.
	 */
	protected function get_dir(): string {
		return __DIR__;
	}

	/**
	 * Returns an array of locations where the core components can be found.
	 *
	 * These locations include class files, utility files, REST API handlers,
	 * admin pages, views, and input components.
	 *
	 * @return array List of locations to load core components from.
	 */
	protected function get_locations(): array {
		return array(
			'class-*',
			'utils/class-*',
			'rest-api/class-*',
			'admin/class-*',
			'admin/page/*',
			'admin/page/view/*',
			'admin/page/view/input/*',
		);
	}
}
