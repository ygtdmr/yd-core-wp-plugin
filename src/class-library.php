<?php
/**
 * Abstract Library Loader for Mobile App
 *
 * This file defines the abstract `Library` class, which dynamically loads PHP files
 * from specified locations within a given directory. It is designed to streamline
 * the inclusion of library files and supports post-load custom logic via `on_load()`.
 *
 * @package YD\Core
 * @subpackage Library
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Library Class
 *
 * This abstract class is responsible for loading PHP library files from specified
 * directories. It includes a method to load the files and a hook for performing
 * custom actions after loading the libraries.
 */
abstract class Library {

	/**
	 * Library constructor.
	 *
	 * Iterates through specified locations and includes the PHP files found in the
	 * defined directory. It also invokes the `on_load()` method for any custom logic.
	 */
	public function __construct() {
		foreach ( $this->get_locations() as $location ) {
			foreach ( glob( $this->get_dir() . "/$location.php" ) as $filename ) {
				require_once $filename;
			}
		}

		$this->on_load();
	}

	/**
	 * Returns the directory where libraries are located.
	 *
	 * @return string The directory path for the libraries.
	 */
	abstract protected function get_dir(): string;

	/**
	 * Returns an array of locations (library names) to search for PHP files.
	 *
	 * @return array List of location strings (e.g., library names).
	 */
	abstract protected function get_locations(): array; // * for name.

	/**
	 * Custom logic to be executed after libraries are loaded.
	 *
	 * This method is empty by default, but can be overridden by subclasses
	 * to implement specific actions after the libraries are loaded.
	 *
	 * @return void
	 */
	protected function on_load() {}
}
