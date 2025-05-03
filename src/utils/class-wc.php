<?php
/**
 * WooCommerce Utility Functions
 *
 * This file defines the `WC` class, which provides utility methods for interacting with WooCommerce features,
 * such as validating WooCommerce support, managing address fields, handling prices, and dealing with product reviews.
 * It also includes methods for exception handling and error conversions.
 *
 * @package YD\Core
 * @subpackage Utils
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Utils;

use YD\Utils\THWCFD;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Utility Class
 *
 * This class provides a set of utility methods for interacting with WooCommerce features,
 * such as checking WooCommerce support, retrieving address fields, formatting prices,
 * handling product reviews, and converting exceptions to WordPress errors.
 * It includes both static methods for WooCommerce-related functionalities and helper functions
 * that are commonly used throughout the WooCommerce integration.
 */
final class WC {

	/**
	 * Check if WooCommerce is supported.
	 *
	 * This method checks whether WooCommerce is installed and activated by verifying if the
	 * `WOOCOMMERCE_VERSION` constant is defined.
	 *
	 * @return bool True if WooCommerce is supported, false otherwise.
	 */
	public static function is_support(): bool {
		return defined( 'WOOCOMMERCE_VERSION' );
	}

	/**
	 * Get the address fields for a specific address type (billing or shipping).
	 *
	 * This method retrieves an array of address fields for either billing or shipping addresses
	 * based on the provided type. It includes field labels, types, and whether each field is required.
	 *
	 * @param string $type The address type ('billing' or 'shipping').
	 * @return array An associative array of address fields for the specified type.
	 */
	public static function get_address_fields( string $type ) {
		$address = array(
			'first_name' => array(
				'label'    => __( 'First name', 'woocommerce' ),
				'type'     => 'text',
				'required' => true,
			),
			'last_name'  => array(
				'label'    => __( 'Last name', 'woocommerce' ),
				'type'     => 'text',
				'required' => true,
			),
			'company'    => array(
				'label'    => __( 'Company name', 'woocommerce' ),
				'type'     => 'text',
				'required' => true,
			),
			'address_1'  => array(
				'label'    => __( 'Street address', 'woocommerce' ),
				'type'     => 'text',
				'required' => true,
			),
			'address_2'  => array(
				'label'    => __( 'Apartment, suite, unit, etc.', 'woocommerce' ),
				'type'     => 'text',
				'required' => false,
			),
			'city'       => array(
				'label'    => __( 'Town / City', 'woocommerce' ),
				'type'     => 'text',
				'required' => true,
			),
			'postcode'   => array(
				'label'    => __( 'Postcode / ZIP', 'woocommerce' ),
				'type'     => 'text',
				'required' => true,
			),
			'country'    => array(
				'label'    => __( 'Country / Region', 'woocommerce' ),
				'type'     => 'text',
				'required' => true,
			),
			'phone'      => array(
				'label'    => __( 'Phone', 'woocommerce' ),
				'type'     => 'text',
				'required' => true,
			),
			'state'      => array(
				'label'    => __( 'State / County', 'woocommerce' ),
				'type'     => 'text',
				'required' => true,
			),
		);

		if ( 'billing' === $type ) {
			$address += array(
				'email' => array(
					'label'    => __( 'Email address', 'woocommerce' ),
					'type'     => 'text',
					'required' => true,
				),
			);
		}

		return $address;
	}

	/**
	 * Get validation rules for address fields.
	 *
	 * This method returns an associative array of validation rules for the given address type (billing or shipping).
	 * It sanitizes the field names and checks if each field is required.
	 *
	 * @param string $type The address type ('billing' or 'shipping').
	 * @return array An associative array of validation rules for the specified address type.
	 */
	public static function get_address_rules( $type ): array {
		$address        = array();
		$address_fields = THWCFD::is_support() ? THWCFD::get_fields( $type ) : self::get_address_fields( $type );

		foreach ( $address_fields as $key => $props ) {
			$sanitized_key = THWCFD::is_support() ? preg_replace( '/^(?:' . preg_quote( $type, '/' ) . '_)/', '', $key ) : $key;

			$address[ $sanitized_key ] = array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			);

			if ( $props['required'] ) {
				$address[ $sanitized_key ] += array( 'required' => true );
			}
		}
		return $address;
	}

	/**
	 * Format a price for display.
	 *
	 * This method formats a given price by sanitizing and applying the WooCommerce `wc_price` function.
	 * It ensures the price is returned as a clean, display-ready string.
	 *
	 * @param mixed $price The price to format.
	 * @return string The formatted price string.
	 */
	public static function price( mixed $price ): string {
		return html_entity_decode(
			wp_strip_all_tags(
				wc_price(
					self::sanitize_price( $price )
				)
			)
		);
	}

	/**
	 * Sanitize a price value.
	 *
	 * This method ensures the provided price is formatted to two decimal places and returned as a float.
	 *
	 * @param mixed $price The price to sanitize.
	 * @return float The sanitized price.
	 */
	public static function sanitize_price( mixed $price ): float {
		return (float) sprintf( '%0.2f', $price );
	}

	/**
	 * Check if product reviews are enabled.
	 *
	 * This method checks if WooCommerce reviews are enabled by verifying the `woocommerce_enable_reviews` option.
	 *
	 * @return bool True if reviews are enabled, false otherwise.
	 */
	public static function is_review_enabled(): bool {
		return 'yes' === get_option( 'woocommerce_enable_reviews' );
	}

	/**
	 * Convert an exception to a WP_Error object.
	 *
	 * This method takes an exception and converts it into a `WP_Error` object with the exception's
	 * error code and message, making it easier to handle errors in WordPress.
	 *
	 * @param \Exception $e The exception to convert.
	 * @return \WP_Error The converted WP_Error object.
	 */
	public static function exception_to_error( \Exception $e ): \WP_Error {
		$error_code = is_callable( array( $e, 'getErrorCode' ) ) ? $e->getErrorCode() : 'error';
		$error_code = str_replace( 'woocommerce_rest_', '', $error_code );

		return new \WP_Error( $error_code, $e->getMessage(), array( 'status' => $e->getCode() ) );
	}
}
