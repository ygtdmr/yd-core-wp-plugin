<?php
/**
 * Abstract Request Class for REST API
 *
 * This file defines the abstract `Request` class, which serves as a base class for handling REST API requests
 * within the project. The class includes abstract methods that must be implemented by subclasses to handle
 * authentication, endpoint retrieval, HTTP method specification, and request options. It also provides a method
 * for checking if an authentication key is included.
 *
 * @package YD\Core
 * @subpackage REST_API
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\REST_API;

defined( 'ABSPATH' ) || exit;

/**
 * Request class serves as a blueprint for all REST API request classes. It defines the structure
 * and the necessary methods that must be implemented in any subclass for handling authentication,
 * endpoint, HTTP method, and request options.
 */
abstract class Request {
	/**
	 * Checks if the request requires authentication.
	 *
	 * @return bool True if authentication is required, false otherwise.
	 */
	abstract public function is_authentication(): bool;

	/**
	 * Determines whether an authentication key is needed.
	 *
	 * @return bool Always returns false for this class; can be overridden by subclasses.
	 */
	public function with_auth_key(): bool {
		return false; }

	/**
	 * Retrieves the endpoint for the request.
	 *
	 * @return string The endpoint URL for the request.
	 */
	abstract public function get_endpoint(): string;

	/**
	 * Retrieves the HTTP method for the request.
	 *
	 * @return string The HTTP method (GET, POST, etc.) used for the request.
	 */
	abstract public function get_method(): string;

	/**
	 * Retrieves the options or parameters for the request.
	 *
	 * @return array The options or parameters associated with the request.
	 */
	abstract public function get_options(): array;
}
