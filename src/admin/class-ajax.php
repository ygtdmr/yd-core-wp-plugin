<?php
/**
 * AJAX Request Handling for the Plugin
 *
 * This file defines the `Ajax` class, which manages the handling of AJAX requests within the WordPress admin.
 * It includes functionality for verifying nonces, retrieving data, and processing actions with success or error responses.
 *
 * @package YD\Core
 * @subpackage Admin\Ajax
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Admin;

use YD\Utils;
use YD\Data_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Ajax class handles the AJAX request process for WordPress admin.
 * It verifies nonces, sanitizes incoming data, and processes actions.
 */
abstract class Ajax {
	/**
	 * Contains the sanitized data for processing the AJAX request.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Determines if the AJAX action is private or public.
	 *
	 * @var bool
	 */
	private $is_private;

	/**
	 * Initializes the AJAX request handler with the specified privacy setting.
	 *
	 * @param bool $is_private Whether the action is private (default: true).
	 */
	public function __construct( bool $is_private = true ) {
		$this->is_private = $is_private;
	}

	/**
	 * Get the action name for the AJAX request.
	 *
	 * This method should be implemented in the child class to return the specific action name.
	 *
	 * @return string The action name for the AJAX request.
	 */
	abstract protected function get_action_name(): string;

	/**
	 * Get the action associated with the AJAX request.
	 *
	 * This method should be implemented in the child class to define the specific action to be taken.
	 *
	 * @return mixed The result of the action.
	 */
	abstract protected function get_action();

	/**
	 * Sends the AJAX request by hooking into the appropriate WordPress action.
	 *
	 * Verifies the nonce, processes the action, and returns the response (success or error).
	 *
	 * @return void
	 */
	final public function send() {
		$action_name = 'yd-' . $this->get_action_name();
		$action      = function () {
			$nonce = $this->get_wp_nonce();
			if ( ! empty( $nonce ) ) {
				Utils\Main::verify_nonce( $nonce );
			}

			try {
				$this->get_data();
				$this->get_action();
			} catch ( \Exception $e ) {
				self::send_error( array( 'message' => $e->getMessage() ) );
			}
		};

		Utils\Main::add_action( ( $this->is_private ? 'wp_ajax_' : 'wp_ajax_nopriv_' ) . $action_name, $action );
	}

	/**
	 * Retrieves and sanitizes the incoming data for the AJAX request.
	 *
	 * Uses the defined rules to sanitize the data from the request.
	 *
	 * @return void
	 */
	private function get_data() {
		$rules = $this->get_rules();
		// phpcs:ignore WordPress.Security.NonceVerification
		$this->data = ! empty( $rules ) ? ( new Data_Manager( $rules, $_REQUEST ) )->sanitize() : array();
	}

	/**
	 * Get the validation rules for the incoming data.
	 *
	 * This method can be overridden in the child class to provide custom validation rules.
	 *
	 * @return array An array of validation rules.
	 */
	protected function get_rules(): array {
		return array(); }

	/**
	 * Retrieves the nonce from the request.
	 *
	 * This method can be overridden in the child class to return a custom nonce.
	 *
	 * @return string The nonce string.
	 */
	protected function get_wp_nonce(): string {
		return ''; }

	/**
	 * Sends a success response with the provided data.
	 *
	 * @param mixed $data The data to be included in the success response.
	 * @return void
	 */
	protected function send_success( $data ) {
		wp_send_json_success( $data );
	}

	/**
	 * Sends an error response with the provided data.
	 *
	 * @param mixed $data The data to be included in the error response.
	 * @return void
	 */
	protected function send_error( $data ) {
		wp_send_json_error( $data );
	}
}
