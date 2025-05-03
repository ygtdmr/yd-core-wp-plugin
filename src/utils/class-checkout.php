<?php
/**
 * WooCommerce Checkout Management Utilities
 *
 * This file defines the `Checkout` class, which provides utility methods for managing the WooCommerce checkout process,
 * including handling purchases, validating cart items, calculating totals, and processing payments. It integrates with
 * the WooCommerce Checkout system and offers functions to create orders, set customer billing and shipping details,
 * and manage payment gateways.
 *
 * @package YD\Core
 * @subpackage Utils
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Utils\WC;

use YD\Utils;
use Automattic\WooCommerce\StoreApi\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout Class for WooCommerce Checkout Management
 *
 * This class provides utility methods for managing the WooCommerce checkout process,
 * including handling purchases, validating cart items, calculating totals, and processing payments.
 * It integrates with the WooCommerce Checkout system and offers functions to create orders,
 * set customer billing and shipping details, and manage payment gateways.
 */
final class Checkout {
	/**
	 * Customer object associated with the checkout process.
	 *
	 * @var Customer
	 */
	private $customer;

	/**
	 * WooCommerce Checkout instance.
	 *
	 * @var \WC_Checkout
	 */
	private $checkout;

	/**
	 * Constructor for the Checkout class.
	 *
	 * Initializes the customer and WooCommerce checkout instance.
	 *
	 * @param Customer $customer Customer object used for checkout.
	 */
	public function __construct( Customer $customer ) {
		$this->customer = $customer;
		$this->checkout = \WC_Checkout::instance();
	}

	/**
	 * Handles the purchase process by validating the cart, creating an order, and processing payment.
	 *
	 * Validates the cart, calculates totals, creates an order, and processes payment through the selected payment gateway.
	 * Returns the order on successful payment or an error on failure.
	 *
	 * @param array $props Properties for the purchase, including payment method and shipping information.
	 * @return \WC_Order|\WP_Error Order object on success, WP_Error object on failure.
	 */
	public function purchase( array $props ): \WC_Order|\WP_Error {
		try {
			$this->customer->get_cart()->validate();
			$this->customer->get_cart()->calculate_totals();
		} catch ( \Exception $e ) {
			return new \WP_Error( 'cart-error', __( 'There is a problem with your cart', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$order_id = $this->checkout->create_order(
			array(
				'payment_method' => $props['payment_method'],
			)
		);

		if ( $order_id instanceof \WP_Error ) {
			$order_id->add_data( array( 'status' => 400 ) );
			return $order_id;
		}

		$customer = $this->customer->get();
		$order    = wc_get_order( $order_id );

		$order->set_billing( $customer->get_billing() );
		$order->set_shipping( $props['send_to_shipping'] ? $customer->get_shipping() : $customer->get_billing() );

		$order->set_created_via( YD_MOBILE_APP );
		$order->update_meta_data( '_wc_order_attribution_utm_source', '(direct)' );
		$order->update_meta_data( '_wc_order_attribution_source_type', 'mobile_app' );
		$order->update_meta_data( '_wc_order_attribution_device_type', 'Mobile' );
		$order->update_meta_data( '_wc_order_attribution_user_agent', wc_get_user_agent() );

		$order->save();

		$payment_gateway = WC()->payment_gateways->get_available_payment_gateways()[ $props['payment_method'] ];
		$response        = $payment_gateway->process_payment( $order_id );

		if ( 'success' === $response['result'] ) {
			return $order;
		} else {
			$order->delete( true );
			return new \WP_Error(
				'payment-error',
				__( "Unfortunately, we couldn't complete your order due to an issue with your payment method.", 'woocommerce' ),
				array( 'status' => 400 )
			);
		}
	}
}
