<?php
/**
 * WooCommerce Customer Management Utilities
 *
 * This file defines the `Customer` class, which provides utility methods for managing WooCommerce customers,
 * including retrieving, editing, creating new customers, managing billing and shipping information, handling orders,
 * and interacting with the wishlist and cart systems. The class allows for updating customer details, managing order messages,
 * and processing customer orders through various methods.
 *
 * @package YD\Core
 * @subpackage Utils
 * @author Yigit Demir
 * @since 1.0.0
 * @version 1.0.0
 */

namespace YD\Utils\WC;

use YD\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Customer class provides utility methods for managing WooCommerce customers.
 * It allows retrieving, editing, and creating customers, as well as managing billing and shipping information, handling orders,
 * interacting with the wishlist and cart systems, and processing customer orders.
 */
final class Customer {
	/**
	 * The WooCommerce customer object.
	 *
	 * @var \WC_Customer
	 */
	private $customer;

	/**
	 * The customer ID.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * The customer's wishlist.
	 *
	 * @var Customer\Wishlist
	 */
	private $wishlist;

	/**
	 * The customer's cart.
	 *
	 * @var Cart
	 */
	private $cart;

	/**
	 * The checkout instance for the customer.
	 *
	 * @var Checkout
	 */
	private $checkout;

	/**
	 * Constructor for the Customer class.
	 *
	 * @param int|null $id Customer ID. If null, retrieves the current logged-in user.
	 */
	public function __construct( ?int $id = null ) {
		$this->customer = $this->find_customer( $id );
		$this->id       = $this->customer->get_id();
		$this->wishlist = new Customer\Wishlist();
		$this->cart     = new Cart();
		$this->checkout = new Checkout( $this );
	}

	/**
	 * Finds a WooCommerce customer by ID.
	 *
	 * @param int|null $customer_id The customer ID to search for.
	 * @return \WC_Customer
	 */
	private function find_customer( ?int $customer_id = null ): \WC_Customer {
		$id;
		if ( ! is_null( $customer_id ) ) {
			$id = $customer_id;
		} elseif ( is_user_logged_in() ) {
			$id = wp_get_current_user()->ID;
		} else {
			$wc = WC();
			if ( empty( $wc->session ) ) {
				$wc->initialize_session();
			}
			$id = $wc->session->get_customer_id();
			wp_set_current_user( $id );
		}
		return new \WC_Customer( $id );
	}

	/**
	 * Creates a new customer.
	 *
	 * @param array $props Customer properties such as email, password, etc.
	 * @return self|\WP_Error
	 */
	public static function create( array $props ): self|\WP_Error {
		$user_id = wc_create_new_customer( $props['email'], '', $props['password'] );

		if ( $user_id instanceof \WP_Error ) {
			return $user_id;
		}

		$customer = new self( $user_id );

		unset( $props['password'] );
		$customer->edit( $props );

		update_user_meta( $user_id, 'yd_mobile_app', true );

		wp_logout();
		wc_set_customer_auth_cookie( $user_id );

		return $customer;
	}

	/**
	 * Gets the customer object.
	 *
	 * @return \WC_Customer
	 */
	public function get(): \WC_Customer {
		return $this->customer;
	}

	/**
	 * Edits the customer details.
	 *
	 * @param array $props Customer properties to update.
	 * @throws \WC_Data_Exception If there is an issue with the customer data.
	 * @return array|\WP_Error
	 */
	public function edit( array $props ): array|\WP_Error {
		try {
			if ( ! empty( $props['first_name'] ) ) {
				$this->customer->set_first_name( $props['first_name'] );
			}
			if ( ! empty( $props['last_name'] ) ) {
				$this->customer->set_last_name( $props['last_name'] );
			}
			if ( ! empty( $props['email'] ) ) {
				$this->customer->set_email( $props['email'] );
			}

			$billing = $props['billing'] ?? false;
			if ( $billing ) {
				$this->customer->set_billing_first_name( $billing['first_name'] );
				$this->customer->set_billing_last_name( $billing['last_name'] );
				$this->customer->set_billing_company( $billing['company'] );
				$this->customer->set_billing_address_1( $billing['address_1'] );
				$this->customer->set_billing_address_2( $billing['address_2'] );
				$this->customer->set_billing_city( $billing['city'] );
				$this->customer->set_billing_postcode( $billing['postcode'] );
				$this->customer->set_billing_country( $billing['country'] );
				$this->customer->set_billing_state( $billing['state'] );
				$this->customer->set_billing_email( $billing['email'] );
				$this->customer->set_billing_phone( $billing['phone'] );
				$this->customer->set_billing_state( $billing['state'] );
				if ( Utils\THWCFD::is_support() ) {
					Utils\THWCFD::update_user_custom_address( $this->id, 'billing', $billing );
				}
			}

			$shipping = $props['shipping'] ?? false;
			if ( $shipping ) {
				$this->customer->set_shipping_first_name( $shipping['first_name'] );
				$this->customer->set_shipping_last_name( $shipping['last_name'] );
				$this->customer->set_shipping_company( $shipping['company'] );
				$this->customer->set_shipping_address_1( $shipping['address_1'] );
				$this->customer->set_shipping_address_2( $shipping['address_2'] );
				$this->customer->set_shipping_city( $shipping['city'] );
				$this->customer->set_shipping_postcode( $shipping['postcode'] );
				$this->customer->set_shipping_country( $shipping['country'] );
				$this->customer->set_shipping_phone( $shipping['phone'] );
				$this->customer->set_shipping_state( $shipping['state'] );
				if ( Utils\THWCFD::is_support() ) {
					Utils\THWCFD::update_user_custom_address( $this->id, 'shipping', $shipping );
				}
			}

			$password = $props['password'] ?? false;
			if ( $password ) {
				$user = get_user_by( 'ID', $this->id );
				if ( wp_check_password( $password['current'], $user->user_pass, $user->ID ) ) {
					$this->customer->set_password( $password['new'] );
				} else {
					throw new \WC_Data_Exception( 'incorrect_current_password', __( 'Your current password is incorrect.', 'woocommerce' ) );
				}
			}
		} catch ( \WC_Data_Exception $e ) {
			return Utils\WC::exception_to_error( $e );
		}

		$this->customer->save();

		return $this->get_info();
	}

	/**
	 * Gets the customer ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Gets the customer information (name, email, addresses).
	 *
	 * @return array
	 */
	public function get_info(): array {
		return array(
			'first_name' => $this->customer->get_first_name(),
			'last_name'  => $this->customer->get_last_name(),
			'email'      => $this->customer->get_email(),
			'billing'    => $this->get_address( 'billing' ),
			'shipping'   => $this->get_address( 'shipping' ),
		);
	}

	/**
	 * Gets the customer's wishlist.
	 *
	 * @return Customer\Wishlist
	 */
	public function get_wishlist(): Customer\Wishlist {
		return $this->wishlist;
	}

	/**
	 * Gets the customer's cart.
	 *
	 * @return Cart
	 */
	public function get_cart(): Cart {
		return $this->cart;
	}

	/**
	 * Gets the checkout instance for the customer.
	 *
	 * @return Checkout
	 */
	public function get_checkout(): Checkout {
		return $this->checkout;
	}

	/**
	 * Sends an order message for the customer.
	 *
	 * @param int    $order_id The order ID.
	 * @param string $message The message to send.
	 * @return array|\WP_Error
	 */
	public function send_order_message( int $order_id, string $message ): array|\WP_Error {
		$order = wc_get_order( $order_id );

		if ( ! $order || $order->get_customer_id() !== $this->id ) {
			return new \WP_Error( 'invalid_order_id', __( 'Invalid order ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		add_filter(
			'woocommerce_new_order_note_data',
			function ( array $data ) {
				$data['comment_author']       = 'customer';
				$data['comment_author_email'] = '';
				$data['comment_agent']        = $GLOBALS['YD_CURRENT_PLUGIN'];
				return $data;
			}
		);
		$comment_id = $order->add_order_note( $message, 1, true );

		return $this->get_order_messages( $order_id );
	}

	/**
	 * Gets the order messages for a specific order.
	 *
	 * @param int $order_id The order ID.
	 * @return array|\WP_Error
	 */
	public function get_order_messages( int $order_id ): array|\WP_Error {
		$order = wc_get_order( $order_id );

		if ( ! $order || $order->get_customer_id() !== $this->id ) {
			return new \WP_Error( 'invalid_order_id', __( 'Invalid order ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$notes = $order->get_customer_order_notes();
		return array_map(
			function ( \WP_Comment $note ) {
				$is_user = $GLOBALS['YD_CURRENT_PLUGIN'] === $note->comment_agent;

				if ( $is_user ) {
					$note->comment_author = __( 'Customer', 'woocommerce' );
				}
				return array(
					'is_user' => $is_user,
				) + \YD\Utils\Post::get_comment_info( $note );
			},
			$notes
		);
	}

	/**
	 * Gets the order details for a specific order.
	 *
	 * @param int $order_id The order ID.
	 * @return array|\WP_Error
	 */
	public function get_order( int $order_id ): array|\WP_Error {
		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_customer_id() !== $this->id ) {
			return new \WP_Error( 'invalid_order_id', __( 'Invalid order ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		$order_items = array_map(
			function ( \WC_Order_Item_Product $order_item ) use ( $order ) {
				// phpcs:ignore Universal.Operators.DisallowShortTernary
				$product_id = $order_item->get_variation_id() ?: $order_item->get_product_id();
				$product    = wc_get_product( $product_id );
				return array(
					'subtotal'  => Utils\WC::price( $order->get_item_subtotal( $order_item ) ),
					'total_tax' => Utils\WC::price( $order_item->get_total_tax() ),
					'total'     => Utils\WC::price( $order_item->get_total() ),
					'quantity'  => $order_item->get_quantity(),
					'product'   => array(
						'id'        => $product ? $product_id : 0,
						'name'      => $order_item->get_name(),
						// phpcs:ignore Universal.Operators.DisallowShortTernary
						'image_url' => ( $product ? wp_get_attachment_image_url( $product->get_image_id(), 'medium' ) : false ) ?: wc_placeholder_img_src( 'medium' ),
					),
					'meta'      => $order_item->get_variation_id()
					? array_map(
						function ( \stdClass $meta ) {
							if ( ! empty( $meta->display_value ) ) {
								$meta->display_value = sanitize_text_field( $meta->display_value );
							}
							return ( $meta );
						},
						array_values( $order_item->get_all_formatted_meta_data() )
					) : array(),
				);
			},
			array_values( $order->get_items() )
		);
		return array(
			'id'             => $order->get_id(),
			'status'         => $order->get_status(),
			'payment_method' => $order->get_payment_method(),
			'subtotal'       => Utils\WC::price( $order->get_subtotal() ),
			'total_fees'     => Utils\WC::price( $order->get_total_fees() ),
			'shipping_total' => Utils\WC::price( $order->get_shipping_total() ),
			'total_tax'      => Utils\WC::price( $order->get_total_tax() ),
			'total'          => Utils\WC::price( $order->get_total() ),
			'date_created'   => $order->get_date_created()->getTimestamp(),
			'items'          => $order_items,
		);
	}

	/**
	 * Gets the orders for the customer.
	 *
	 * @return array
	 */
	public function get_orders(): array {
		$orders = wc_get_orders(
			array(
				'customer_id' => $this->id,
				'status'      => array_filter(
					array_keys( wc_get_order_statuses() ),
					function ( string $status ) {
						return 'wc-checkout-draft' !== $status;
					}
				),
			)
		);

		return array_map(
			function ( \WC_Order $order ) {
				$image_url = '';
				$item      = current( $order->get_items() );
				if ( $item ) {
					$image_url = wp_get_attachment_image_url( $item->get_product()->get_image_id(), 'medium' );
				}
				return array(
					'id'             => $order->get_id(),
					'status'         => $order->get_status(),
					'payment_method' => $order->get_payment_method(),
					'total'          => Utils\WC::price( $order->get_total() ),
					'date_created'   => $order->get_date_created()->getTimestamp(),
					'item_count'     => count( $order->get_items() ),
					// phpcs:ignore Universal.Operators.DisallowShortTernary
					'image_url'      => $image_url ?: wc_placeholder_img_src( 'medium' ),
				);
			},
			$orders
		);
	}

	/**
	 * Retrieves the address fields for billing or shipping.
	 *
	 * @param string   $type The address type ('billing' or 'shipping').
	 * @param int|null $order_id The order ID (optional).
	 * @return array
	 */
	private function get_address( string $type, ?int $order_id = null ): array {
		$address = Utils\WC::get_address_fields( $type );
		foreach ( $address as $key => $props ) {
			$meta_key                 = sprintf( '%s_%s', $type, $key );
			$address[ $key ]['value'] = $order_id ? get_post_meta( $order_id, "_$meta_key", true ) : get_user_meta( $this->id, $meta_key, true );
		}
		if ( Utils\THWCFD::is_support() ) {
			$custom_fields = array_filter(
				\THWCFD_Utils::get_fields( $type ),
				function ( $props ) {
					return \THWCFD_Utils::is_custom_field( $props );
				}
			);
			foreach ( $custom_fields as $key => $props ) {
				$address[ $key ] = array(
					// phpcs:ignore WordPress.WP.I18n
					'label'    => __( $props['label'], $GLOBALS['YD_CURRENT_PLUGIN'] ),
					'type'     => $props['type'],
					'required' => boolval( $props['required'] ),
					'value'    => $order_id ? get_post_meta( $order_id, "_$key", true ) : get_user_meta( $this->id, $key, true ),
				);
			}
		}
		return $address;
	}
}
