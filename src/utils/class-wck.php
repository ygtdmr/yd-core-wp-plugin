<?php
/**
 * WCK Utility Functions
 *
 * This file defines the `WCK` class, which provides utility methods for interacting with the WCK plugin,
 * including checking plugin support, retrieving field data for products, and calculating product prices based on
 * field inputs. It also handles error reporting for calculation issues.
 *
 * @package YD\Core
 * @subpackage Utils
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Utils;

use YD\Utils;
use WCKalkulator\FieldsetProduct;

defined( 'ABSPATH' ) || exit;

/**
 * WCK class provides utility functions to interact with the WCK plugin.
 * It includes methods for checking plugin support, retrieving field data for products, and calculating prices
 * based on field inputs. Error handling is also implemented for calculation-related issues.
 */
final class WCK {

	/**
	 * Checks if the WCK plugin is supported.
	 *
	 * This method checks whether the WCK plugin version is available in the WordPress options.
	 *
	 * @return bool Returns true if the plugin is supported, false otherwise.
	 */
	public static function is_support(): bool {
		return get_option( 'wck_version', false );
	}

	/**
	 * Retrieves field data for a specific product.
	 *
	 * This method gets the fieldset associated with a product and maps the field data to an array format.
	 * If no fieldset exists, an empty array is returned.
	 *
	 * @param int $product_id The ID of the product for which to retrieve fields.
	 * @return array Returns an array of field data for the product.
	 */
	public static function get_fields( int $product_id ): array {
		$fieldset = FieldsetProduct::getInstance();
		$fieldset->init( $product_id );
		if ( ! $fieldset->has_fieldset() ) {
			return array();
		}
		return array_map(
			function ( $field ) {
				return array(
					'name'          => $field->data( 'name' ),
					'title'         => $field->data( 'title' ),
					'type'          => $field->data( 'type' ),
					'default_value' => $field->data( 'default_value' ),
					'min'           => $field->data( 'min' ),
					'max'           => $field->data( 'max' ),
					'is_required'   => boolval( $field->data( 'required' ) ),
				);
			},
			array_values( $fieldset->fields() )
		);
	}

	/**
	 * Calculates the price for a product based on its fields and quantity.
	 *
	 * This method computes the price for a product by considering the specified fields and quantity.
	 * If any error occurs during the calculation, a WP_Error is returned.
	 *
	 * @param int   $product_id The ID of the product.
	 * @param int   $quantity The quantity of the product.
	 * @param array $fields The fields for the product used in the price calculation.
	 * @return string|\WP_Error Returns the calculated price as a string or a WP_Error if an error occurs.
	 */
	public static function get_price( int $product_id, int $quantity, array $fields ): string|\WP_Error {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$_POST['quantity'] = $quantity;
			$_POST['wck']      = $fields;
			$fieldset          = FieldsetProduct::getInstance();

			if ( $product instanceof \WC_Product_Variation ) {
				$fieldset->init( $product->get_parent_id(), $product_id );
			} else {
				$fieldset->init( $product_id );
			}
			if ( ! $fieldset->has_fieldset() ) {
				return Utils\WC::price( wc_get_product( $product_id )->get_id() * $quantity );
			}

			$fieldset->get_user_input();
			$fieldset->validate( true );
			$result = $fieldset->calculate();

			if ( $result['is_error'] ) {
				return new \WP_Error( 'calculate_error', $result['value'], array( 'status' => 400 ) );
			} else {
				return Utils\WC::price( $result['value'] * $quantity );
			}
		}
		return new \WP_Error( 'product_invalid_id', __( 'Invalid product ID.', 'woocommerce' ), array( 'status' => 404 ) );
	}
}
