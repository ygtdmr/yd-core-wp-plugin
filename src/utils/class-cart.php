<?php
/**
 * WooCommerce Cart Management Utilities
 *
 * This file defines the `Cart` class, which provides utility methods for managing the WooCommerce cart,
 * including retrieving cart items, applying coupons, calculating totals, and managing shipping methods. It
 * integrates with the WooCommerce CartController and offers functions to manipulate cart data and handle errors.
 *
 * @package YD\Core
 * @subpackage Utils
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Utils\WC;

use YD\Utils;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;

defined( 'ABSPATH' ) || exit;

/**
 * Cart class provides utility methods for managing the WooCommerce cart.
 *
 * This class includes methods for retrieving cart items, applying coupons, calculating totals,
 * and managing shipping methods. It integrates with the WooCommerce CartController to handle
 * cart data and errors.
 */
final class Cart {
	/**
	 * Handles the interaction with WooCommerce's cart.
	 *
	 * @var CartController
	 */
	private $cart_controller;

	/**
	 * The WooCommerce cart instance.
	 *
	 * @var object
	 */
	private $cart;

	/**
	 * Constructor method initializes the cart controller and retrieves the cart instance.
	 */
	public function __construct() {
		$this->cart_controller = new CartController();
		$this->cart            = $this->cart_controller->get_cart_instance();
	}

	/**
	 * Retrieves the items in the cart.
	 *
	 * @return array The cart items formatted with product data and quantities.
	 */
	public function get_items(): array {
		$cart_contents = array_values( $this->cart_controller->get_cart_items() );
		$cart_contents = array_map(
			function ( array $item ) {
				$data = array(
					'key'      => $item['key'],
					'quantity' => $item['quantity'],
					'subtotal' => Utils\WC::price( $item['line_subtotal'] ),
					// phpcs:ignore Universal.Operators.DisallowShortTernary
					'product'  => Utils\WC\Product::get_info( $item['variation_id'] ?: $item['product_id'] ),
				);
				if ( Utils\WCK::is_support() ) {
					$data += array(
						'fields' => array_filter(
							$item['wckalkulator_fields'] ?? array(),
							function ( $key ) {
								return 0 !== strpos( $key, '_' );
							},
							ARRAY_FILTER_USE_KEY
						),
					);
				}
				return $data;
			},
			$cart_contents
		);
		return $cart_contents;
	}

	/**
	 * Retrieves any errors in the cart.
	 *
	 * @return \WP_Error The cart errors, if any.
	 */
	public function get_errors(): \WP_Error {
		return $this->cart_controller->get_cart_errors();
	}

	/**
	 * Retrieves the cart totals, including subtotal, coupon discounts, fees, and shipping.
	 *
	 * @return array The total values for the cart.
	 */
	public function get_totals(): array {
		$this->calculate_totals();

		$fees       = $this->cart->get_fees();
		$fees_total = array();
		foreach ( $fees as $fee ) {
			$fees_total[ $fee->name ] = Utils\WC::price( $fee->amount );
		}

		$coupon_discounts_total = $this->cart->get_coupon_discount_totals();
		foreach ( $coupon_discounts_total as $key => $value ) {
			$coupon_discounts_total[ $key ] = Utils\WC::price( $value );
		}

		$shipping         = array();
		$shipping_package = current(
			$this->cart_controller->get_shipping_packages()
		);

		if ( ! empty( $shipping_package['rates'] ) ) {
			$shipping = array(
				'package_id'      => $shipping_package['package_id'],
				'methods'         => array(),
				'selected_method' => WC()->session->get( 'chosen_shipping_methods' )[0],
			);
			foreach ( $shipping_package['rates'] as $rate_id => $rate ) {
				$shipping['methods'] += array(
					$rate_id => array(
						'label' => $rate->get_label(),
						'cost'  => Utils\WC::price( $rate->get_cost() ),
					),
				);
			}
		}

		return array(
			'subtotal'               => Utils\WC::price( $this->cart->get_subtotal() ),
			'coupon_discounts_total' => $coupon_discounts_total,
			'fees_total'             => $fees_total,
			// phpcs:ignore Universal.Operators.DisallowShortTernary
			'shipping'               => $shipping ?: false,
			'tax_total'              => Utils\WC::price( $this->cart->get_taxes_total() ),
			'total'                  => Utils\WC::price( $this->cart->get_total( '' ) ),
		);
	}

	/**
	 * Retrieves cross-sell products from the cart.
	 *
	 * @return array List of cross-sell products.
	 */
	public function get_cross_sells(): array {
		return array_map(
			function ( int $id ) {
				return Product::get_info( $id );
			},
			$this->cart->get_cross_sells()
		);
	}

	/**
	 * Edits an item in the cart, either by changing its quantity or removing it.
	 *
	 * @param array $item The cart item to be edited, including product ID and quantity.
	 * @return bool|\WP_Error True on success, or a \WP_Error on failure.
	 */
	public function edit( array $item ): bool|\WP_Error {
		try {
			$quantity = $item['quantity'];
			$key      = $item['key'] ?? null;
			if ( $key ) {
				if ( 0 === $quantity ) {
					$this->cart->remove_cart_item( $key );
				} else {
					$this->cart_controller->set_cart_item_quantity( $key, $quantity );
				}
			} else {
				$this->cart_controller->add_to_cart(
					array(
						'id'       => $item['product_id'],
						'quantity' => $quantity,
					)
				);
			}
		} catch ( \Exception $e ) {
			return Utils\WC::exception_to_error( $e );
		}
		return true;
	}

	/**
	 * Applies a coupon code to the cart.
	 *
	 * @param string $coupon_code The coupon code to be applied.
	 * @return void|\WP_Error Returns \WP_Error if there is an issue applying the coupon.
	 */
	public function apply_coupon( string $coupon_code ) {
		try {
			$this->cart_controller->apply_coupon( $coupon_code );
		} catch ( \Exception $e ) {
			return Utils\WC::exception_to_error( $e );
		}
	}

	/**
	 * Selects a shipping rate for the cart.
	 *
	 * @param int|string $package_id The ID of the shipping package.
	 * @param string     $rate_id The ID of the shipping rate.
	 * @return void
	 */
	public function select_shipping_rate( int|string $package_id, string $rate_id ) {
		$this->cart_controller->select_shipping_rate( $package_id, $rate_id );
	}

	/**
	 * Removes a coupon from the cart.
	 *
	 * @param string $coupon_code The coupon code to be removed.
	 * @return bool True if the coupon was removed, false otherwise.
	 */
	public function remove_coupon( string $coupon_code ): bool {
		return $this->cart->remove_coupon( $coupon_code );
	}

	/**
	 * Validates the cart, ensuring it is not empty and complies with necessary rules.
	 *
	 * @return void
	 */
	public function validate() {
		$this->cart_controller->validate_cart_not_empty();
		$this->cart_controller->validate_cart();
	}

	/**
	 * Calculates the totals for the cart, including fees and shipping.
	 *
	 * @return void
	 */
	public function calculate_totals() {
		$this->cart_controller->calculate_totals();
	}

	/**
	 * Empties the cart.
	 *
	 * @return void
	 */
	public function empty() {
		$this->cart_controller->empty_cart();
	}
}
