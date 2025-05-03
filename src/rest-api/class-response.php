<?php
/**
 * Abstract Response Class for REST API
 *
 * This file defines the abstract `Response` class, which is used as a base for handling responses
 * in the REST API. It provides methods for managing errors, including adding and checking for errors,
 * and it contains functionality for retrieving the associated user and customer objects. Subclasses
 * are expected to implement the `get_callback` method for handling the request's callback.
 *
 * @package YD\Core
 * @subpackage REST_API
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\REST_API;

use YD\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Response class is used to handle the structure of a response for API requests, including
 * error handling and user/customer retrieval. Subclasses are expected to implement the `get_callback`
 * method to provide specific response behavior.
 */
abstract class Response {
	/**
	 * Holds the error object for general errors.
	 *
	 * @var \WP_Error
	 */
	public $error;

	/**
	 * Holds error groups for specific items or sections.
	 *
	 * @var array
	 */
	public $error_group;

	/**
	 * Current response object.
	 *
	 * @var mixed
	 */
	public static $current_response;

	/**
	 * Cached user object.
	 *
	 * @var Utils\User
	 */
	private $customer;

	/**
	 * Cached customer object.
	 *
	 * @var Utils\WC\Customer
	 */
	private $user;

	/**
	 * Response constructor to initialize error and error group properties.
	 */
	public function __construct() {
		$this->error       = new \WP_Error();
		$this->error_group = array();
	}

	/**
	 * Abstract method to get the callback for the response.
	 *
	 * @param \WP_REST_Request|null $request The REST request object.
	 * @return mixed The callback response.
	 */
	abstract public function get_callback( ?\WP_REST_Request $request = null );

	/**
	 * Retrieves the user object, instantiating it if necessary.
	 *
	 * @param int|null $id User ID.
	 * @return Utils\User The user object.
	 */
	final protected function get_user( ?int $id = null ): Utils\User {
		if ( empty( $this->user ) ) {
			$this->user = new Utils\User( $id );
		}
		return $this->user;
	}

	/**
	 * Retrieves the customer object, instantiating it if necessary.
	 *
	 * @param int|null $id Customer ID.
	 * @return Utils\WC\Customer The customer object.
	 */
	final protected function get_customer( ?int $id = null ): Utils\WC\Customer {
		if ( empty( $this->customer ) ) {
			$this->customer = new Utils\WC\Customer( $id );
		}
		return $this->customer;
	}

	/**
	 * Checks if there are any errors in the response.
	 *
	 * @return bool True if there are errors, false otherwise.
	 */
	final public function has_errors(): bool {
		$has_error_group = false;

		foreach ( $this->error_group as $group => $group_items ) {
			foreach ( $group_items as $item_name => $item_error ) {
				if ( $item_error instanceof \WP_Error ) {
					$has_error_group = true;
					break;
				}
			}
		}

		return $this->error->has_errors() || $has_error_group;
	}

	/**
	 * Adds an error to the response.
	 *
	 * @param \WP_Error   $error The error object to add.
	 * @param string|null $group The error group (optional).
	 * @param string|null $group_item The specific group item (optional).
	 * @return void
	 */
	final public function add_error( \WP_Error $error, ?string $group = null, ?string $group_item = null ) {
		if ( ! empty( $group ) && ! empty( $group_item ) ) {
			$current_error = $this->error_group[ $group . '_errors' ][ $group_item ] ?? false;

			if ( $current_error ) {
				$error->merge_from( $current_error );
			}

			$this->error_group[ $group . '_errors' ][ $group_item ] = $error;
		} else {
			switch ( $error->get_error_code() ) {
				case 'rest_forbidden':
					$error = new \WP_Error( 'unauthorized', 'Unauthorized. You are not allowed to do that.', array( 'status' => 401 ) );
					break;
			}
			$this->error->merge_from( $error );
		}
	}
}
