<?php
/**
 * Admin Class for Managing Admin Pages and AJAX Actions
 *
 * This file defines the `Admin` class, which handles the management of WordPress admin pages
 * and AJAX actions for the plugin. The class is responsible for loading pages into the admin
 * interface and ensuring that any necessary AJAX actions are registered and processed.
 *
 * @package YD\Core
 * @subpackage Admin
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD;

defined( 'ABSPATH' ) || exit;

/**
 * Admin class handles the administration of plugin pages and AJAX actions.
 * It defines methods for loading pages into the admin menu and sending AJAX actions.
 * Derived classes must implement the methods for retrieving pages and AJAX actions.
 */
abstract class Admin {

	/**
	 * Initializes the admin class by adding a filter to the admin body class and
	 * loading pages and sending AJAX actions.
	 */
	public function __construct() {
		Utils\Main::check_updates();

		Utils\Main::add_filter(
			'admin_body_class',
			function ( $classes ) {
				return $classes . sprintf( ' %s ', $GLOBALS['YD_CURRENT_PLUGIN'] );
			}
		);

		$this->load_pages();
		$this->send_ajax_actions();
	}

	/**
	 * Retrieves an array of pages to be loaded into the admin menu.
	 * Must be implemented by the subclass.
	 *
	 * @return array Array of page class names
	 */
	abstract protected function get_pages(): array;

	/**
	 * Retrieves an array of AJAX actions to be sent.
	 * Must be implemented by the subclass.
	 *
	 * @return array Array of action class names
	 */
	abstract protected function get_ajax_actions(): array;

	/**
	 * Loads the pages defined by the `get_pages` method into the WordPress admin menu.
	 *
	 * @return void
	 */
	private function load_pages() {
		$pages = $this->get_pages();

		Utils\Main::add_action(
			'admin_menu',
			function () use ( $pages ) {
				foreach ( $pages as $page ) {
					( new $page() )->load();
				}
			}
		);
	}

	/**
	 * Sends the AJAX actions defined by the `get_ajax_actions` method.
	 *
	 * @return void
	 */
	private function send_ajax_actions() {
		foreach ( $this->get_ajax_actions() as $action ) {
			( new $action() )->send();
		}
	}
}
