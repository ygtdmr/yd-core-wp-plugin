<?php
/**
 * Abstract Task Scheduler for Mobile App
 *
 * This file defines the abstract `Task` class, which provides a framework for managing
 * scheduled tasks using WordPress hooks. Tasks can specify required plugin libraries,
 * execution logic, and scheduling frequency. Logging is supported when WP_DEBUG is enabled.
 *
 * @package YD\Core
 * @subpackage Task
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD;

use YD\Data_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Task Class
 *
 * This abstract class defines the structure for tasks that are scheduled or executed at specified times.
 * It includes methods for scheduling, logging, and defining task libraries and actions.
 */
abstract class Task {

	/**
	 * Task constructor.
	 *
	 * Registers the task's hook action to include required libraries and execute the task's specific action.
	 */
	public function __construct() {
		Utils\Main::add_action(
			self::get_hook( $this->get_type() ),
			function () {
				foreach ( $this->get_libraries() as $library => $plugin_slug ) {
					require_once sprintf( '%s/%s/src/%s.php', WP_PLUGIN_DIR, $plugin_slug ?? $GLOBALS['YD_CURRENT_PLUGIN'], $library );
				}
				$this->get_action();
			}
		);
	}

	/**
	 * Returns the libraries needed for the task.
	 *
	 * @return array Associative array of library file names and corresponding plugin slugs.
	 */
	abstract protected function get_libraries(): array;

	/**
	 * Returns the type of the task.
	 *
	 * @return string The task type.
	 */
	abstract protected function get_type(): string;

	/**
	 * Executes the task's specific action.
	 *
	 * This method should be implemented by child classes to define the task's behavior.
	 *
	 * @return void.
	 */
	abstract protected function get_action();

	/**
	 * Logs data to a file if WP_DEBUG is enabled.
	 *
	 * @param mixed $data The data to be logged.
	 * @return void
	 */
	protected function log( mixed $data ) {
		if ( WP_DEBUG === true ) {
			$time = wp_date( 'Y-m-d H:i:s' );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions
			$data = var_export( $data, true );
			Data_Manager::write_file( WP_CONTENT_DIR . '/yd-task.log', "[$time] $data\n", true );
		}
	}

	/**
	 * Returns the hook name for a specific task type.
	 *
	 * @param string $type The task type.
	 * @return string The hook name.
	 */
	private static function get_hook( string $type ): string {
		return "yd_task_$type";
	}

	/**
	 * Schedules a recurring event for a task.
	 *
	 * @param string   $type The task type.
	 * @param string   $recurrence The recurrence interval.
	 * @param int|null $timestamp The timestamp for scheduling (defaults to current time if null).
	 * @return void
	 */
	public static function run( string $type, string $recurrence, ?int $timestamp = null ) {
		if ( self::exists( self::get_hook( $type ) ) ) {
			return;
		}

		if ( null === $timestamp ) {
			$timestamp = time();
		}
		wp_schedule_event( $timestamp, $recurrence, self::get_hook( $type ) );
	}

	/**
	 * Schedules a one-time event for a task.
	 *
	 * @param string   $type The task type.
	 * @param int|null $timestamp The timestamp for scheduling (defaults to current time if null).
	 * @return void
	 */
	public static function run_once( string $type, ?int $timestamp = null ) {
		if ( self::exists( self::get_hook( $type ) ) ) {
			return;
		}

		if ( null === $timestamp ) {
			$timestamp = time();
		}
		$result = wp_schedule_single_event( $timestamp, self::get_hook( $type ) );
	}

	/**
	 * Checks if a task's scheduled event already exists.
	 *
	 * @param string $type The task type.
	 * @return bool True if the event exists, false otherwise.
	 */
	public static function exists( string $type ): bool {
		return wp_next_scheduled( self::get_hook( $type ) ) !== false;
	}

	/**
	 * Clears the scheduled hook for a task.
	 *
	 * @param string $type The task type.
	 * @return void
	 */
	public static function stop( string $type ) {
		wp_clear_scheduled_hook( self::get_hook( $type ) );
	}
}
