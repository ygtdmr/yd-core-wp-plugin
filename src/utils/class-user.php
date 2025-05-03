<?php
/**
 * User Management Utilities
 *
 * This file defines the `User` class, which provides methods for managing
 * user data including creating new users, editing user profiles,
 * setting and retrieving user announcements, and managing authentication.
 *
 * @package YD\Core
 * @subpackage Utils
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Utils;

use YD\Data_Manager;
use YD\Mobile_App\Widget\Action;

defined( 'ABSPATH' ) || exit;

/**
 * User class for managing user-related operations.
 *
 * This class handles user data management, including creating, editing,
 * and retrieving user information, as well as managing announcements
 * and authentication.
 */
final class User {
	/**
	 * User ID
	 *
	 * @var int
	 */
	private $id;

	/**
	 * User object
	 *
	 * @var \WP_User
	 */
	private $user;

	/**
	 * User constructor.
	 *
	 * Initializes the user with the given ID, or the current logged-in user.
	 *
	 * @param int|null $id User ID. If null, the current logged-in user will be used.
	 */
	public function __construct( ?int $id = null ) {
		if ( null === $id ) {
			$id = get_current_user_id();
		}
		$this->id   = $id;
		$this->user = get_user( $id );
	}

	/**
	 * Sets an announcement for the user.
	 *
	 * This method allows setting a title and text for the user's announcement,
	 * optionally including an action.
	 *
	 * @param string      $title The title of the announcement.
	 * @param string      $text The text of the announcement.
	 * @param Action|null $action Optional action for the announcement.
	 * @return void
	 */
	public function set_announcement( string $title, string $text, ?Action $action = null ) {
		update_user_meta(
			$this->id,
			'yd_announcement_data',
			Data_Manager::encode(
				array(
					'uuid'  => wp_generate_uuid4(),
					'title' => $title,
					'text'  => $text,
				) + (
					$action ? $action->get_data_for_rest() : array()
				)
			)
		);
	}

	/**
	 * Retrieves the user's announcement.
	 *
	 * @return array|bool The announcement data as an array, or false if no data exists.
	 */
	public function get_announcement(): array|bool {
		$data = Data_Manager::decode(
			get_user_meta(
				$this->id,
				'yd_announcement_data',
				true
			)
		);
		// phpcs:ignore Universal.Operators.DisallowShortTernary
		return $data ?: false;
	}

	/**
	 * Generates a unique username based on the user's email.
	 *
	 * @param string $email The user's email address.
	 * @return string The generated username.
	 */
	public static function generate_username( string $email ): string {
		$username      = explode( '@', $email )[0];
		$temp_username = $username;

		$id = 0;
		while ( username_exists( $temp_username ) ) {
			$temp_username = $username . '_' . $id;
			++$id;
		}

		return strtolower( $username );
	}

	/**
	 * Creates a new user with the given properties.
	 *
	 * @param array $props An associative array of user properties (email, password, etc.).
	 * @return self|\WP_Error The newly created user object or a WP_Error on failure.
	 */
	public static function create( array $props ): self|\WP_Error {
		$user_id = wp_create_user( self::generate_username( $props['email'] ), $props['password'], $props['email'] );

		if ( $user_id instanceof \WP_Error ) {
			return $user_id;
		}

		update_user_meta( $user_id, 'yd_mobile_app', true );

		$user = new self( $user_id );
		unset( $props['password'] );
		$user->edit( $props );

		wp_logout();
		wp_set_auth_cookie( $user_id );

		return $user;
	}

	/**
	 * Retrieves the user object.
	 *
	 * @return \WP_User The user object.
	 */
	public function get(): \WP_User {
		return $this->user;
	}

	/**
	 * Edits the user's properties.
	 *
	 * Updates the user's details such as name, email, and password if provided.
	 *
	 * @param array $props An associative array of user properties to update.
	 * @return array|\WP_Error The updated user information, or a WP_Error on failure.
	 */
	public function edit( array $props ): array|\WP_Error {
		$data = array();

		$data['ID'] = $this->id;

		if ( ! empty( $props['first_name'] ) ) {
			$data['first_name'] = $props['first_name'];
		}
		if ( ! empty( $props['last_name'] ) ) {
			$data['last_name'] = $props['last_name'];
		}
		if ( ! empty( $props['email'] ) ) {
			$data['email'] = $props['email'];
		}

		$password = $props['password'] ?? false;
		if ( $password ) {
			if ( wp_check_password( $password['current'], $this->user->user_pass, $this->id ) ) {
				$data['user_pass'] = $password['new'];
			} else {
				return new \WP_Error(
					'incorrect_current_password',
					__( 'Your current password is incorrect.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}
		}

		$user_id = wp_update_user( $data );

		if ( $user_id instanceof \WP_Error ) {
			return $user_id;
		}

		$this->user = get_user( $user_id );

		if ( ! empty( $data['user_pass'] ) ) {
			wp_destroy_all_sessions();
			wp_set_auth_cookie( $user_id );
		}

		return $this->get_info();
	}

	/**
	 * Gets the user ID.
	 *
	 * @return int The user ID.
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Retrieves the user's basic information.
	 *
	 * @return array An array containing the user's first name, last name, and email.
	 */
	public function get_info(): array {
		return array(
			'first_name' => $this->user->user_firstname,
			'last_name'  => $this->user->user_lastname,
			'email'      => $this->user->user_email,
		);
	}
}
