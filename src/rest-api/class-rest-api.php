<?php
/**
 * REST API Class for Handling API Requests and Responses
 *
 * This file defines the `REST_API` class, which is responsible for initializing and managing the
 * REST API routes within the plugin. It registers routes, handles authentication, processes
 * request parameters, and formats error responses. The class ensures that all API interactions
 * are properly validated, and it manages the lifecycle of API requests and responses.
 *
 * @package YD\Core
 * @subpackage REST_API
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD;

use YD\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * REST_API class defines methods for registering custom REST API routes, handling authentication, and managing error responses.
 */
final class REST_API {
	/**
	 * Stores the API path for the current plugin.
	 *
	 * @var string|null
	 */
	private static $api_path;

	/**
	 * Constructor for initializing the REST_API class.
	 *
	 * @param string $api_path The API path for the plugin.
	 * @param array  $routes Array of routes to register.
	 */
	public function __construct( string $api_path, array $routes ) {
		self::$api_path[ $GLOBALS['YD_CURRENT_PLUGIN'] ] = $api_path;

		switch_to_locale( Utils\Main::get_header( 'x-yd-locale' ) );

		Utils\Main::add_action(
			'rest_api_init',
			function () use ( $routes ) {
				foreach ( $routes as $route ) {
					$this->register_route( $route );
				}
			}
		);
	}

	/**
	 * Returns the full API path
	 *
	 * @return string Full API path.
	 */
	public static function get_api_path(): string {
		return 'yd/' . self::$api_path[ $GLOBALS['YD_CURRENT_PLUGIN'] ];
	}

	/**
	 * Creates an error response for the API
	 *
	 * @param \YD\REST_API\Response $response The response object containing error data.
	 * @return \WP_REST_Response The WP_REST_Response object with error data.
	 */
	private function get_error_response( \YD\REST_API\Response $response ): \WP_REST_Response {
		$error = $response->error;

		$all_status = array_map(
			function ( $error_data ) {
				return (int) $error_data['status'];
			},
			$error->get_all_error_data()
		);

		$error_response = function ( \WP_Error $error ): array|object {
			$response = array();
			foreach ( $error->get_error_codes() as $error_code ) {
				$response[ $error_code ] = array(
					'message' => wp_strip_all_tags( $error->get_error_message( $error_code ) ),
					'data'    => $error->get_error_data( $error_code ),
				);
			}
			// phpcs:ignore Universal.Operators.DisallowShortTernary
			return $response ?: (object) array();
		};

		$callback_response = array(
			'status' => false,
			'errors' => $error_response( $error ),
		);

		$error_group = $response->error_group;
		if ( $error_group ) {
			foreach ( $error_group as $group => $group_items ) {
				if ( empty( $group_items ) ) {
					$callback_response[ $group ] = (object) array();
					continue;
				}
				foreach ( $group_items as $item_name => $item_error ) {
					$all_status[]                              = $item_error->get_error_data()['status'];
					$callback_response[ $group ][ $item_name ] = $error_response( $item_error );
				}
			}
		}

		$status_internal_server_error = current(
			array_filter(
				$all_status,
				function ( $status ) {
					return $status >= 500;
				}
			)
		);

		// phpcs:ignore Universal.Operators.DisallowShortTernary
		$status = $status_internal_server_error ?: ( count( $all_status ) === 1 ? $all_status[0] : 400 );

		return new \WP_REST_Response( $callback_response, $status );
	}

	/**
	 * Validates the request based on authentication conditions
	 *
	 * @param \YD\REST_API\Request $request The request object to validate.
	 * @return bool Whether the request is valid or not.
	 */
	private function is_valid( \YD\REST_API\Request $request ): bool {
		if ( $request->with_auth_key() ) {
			return Utils\Main::validate_auth_key_with_basic_auth();
		} elseif ( $request->is_authentication() ) {
			return is_user_logged_in();
		}
		return true;
	}

	/**
	 * Registers a REST route for the API
	 *
	 * @param string $route The route to register.
	 * @return void
	 */
	private function register_route( string $route ) {
		$class_root_name = $GLOBALS['YD_CURRENT_PLUGIN_CLASS_NAME'];
		$api_path        = self::get_api_path();

		$request  = new ( sprintf( 'YD\\%s\\REST_API\\Request\\%s', $class_root_name, $route ) );
		$response = new ( sprintf( 'YD\\%s\\REST_API\\Response\\%s', $class_root_name, $route ) );

		$current_route = sprintf( '/%s/%s', rest_get_url_prefix(), $api_path );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$is_same_route = strpos( $_SERVER['REQUEST_URI'] ?? '', $current_route ) === 0;

		if ( $is_same_route ) {
			add_filter(
				'rest_request_after_callbacks',
				function ( mixed $rest_response ) use ( $response ) {
					if ( $rest_response instanceof \WP_Error ) {
						$response->add_error( $rest_response );
					}

					\YD\REST_API\Response::$current_response = null;

					return $response->has_errors() ? $this->get_error_response( $response ) : $rest_response;
				},
				10
			);
		}

		$request_endpoint = $request->get_endpoint();
		$request_options  = $request->get_options();

		register_rest_route(
			self::get_api_path(),
			$request_endpoint,
			$request_options +
			array(
				'methods'             => $request->get_method(),
				'permission_callback' => function () use ( $request ) {
					do_action( 'yd_rest_api_on_permission_callback', self::get_api_path() );
					return $this->is_valid( $request );
				},
				'callback'            => function ( \WP_REST_Request $rest_request ) use ( $response ) {
					foreach ( array_keys( $rest_request->get_json_params() ?? array() ) as $key ) {
						if ( ! in_array( $key, array_keys( $rest_request->get_attributes()['args'] ), true ) ) {
							unset( $rest_request[ $key ] );
						}
					}

					$callback_response;

					$check_params = $rest_request->has_valid_params();
					if ( true === $check_params ) {
						$check_params = $rest_request->sanitize_params();
					}

					if ( $check_params instanceof \WP_Error ) {
						$callback_response = $check_params;
					} else {
						try {
							\YD\REST_API\Response::$current_response = $response;
							$callback_response = $response->get_callback( $rest_request ) ?? array();
						} catch ( \Throwable $e ) {
							// phpcs:ignore WordPress.PHP.DevelopmentFunctions
							error_log( $e->__toString() );
							$callback_response = new \WP_Error( 'internal-server', 'Server related errors.', array( 'status' => 500 ) );
						}
					}

					if ( $callback_response instanceof \WP_Error ) {
						$response->add_error( $callback_response );
					} else {
						// phpcs:ignore Universal.Operators.DisallowShortTernary
						$callback_response = array( 'status' => true ) + ( $callback_response ?: array() );
					}

					return new \WP_REST_Response( $callback_response );
				},
			)
		);
	}
}
