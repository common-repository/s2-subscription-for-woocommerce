<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements product features of S2 Subscription Cart
 *
 * @class   S2_Subscription_Cart
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Cart' ) ) {

	class S2_Subscription_Cart {

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Cart
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Subscription_Cart
		 *
		 * @since  1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @param array $args
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			// change prices in calculation totals to add the fee amount
			add_action( 'woocommerce_before_calculate_totals', [ $this, 'add_change_prices_filter' ], 10 );
			add_action( 'woocommerce_calculate_totals', [ $this, 'remove_change_prices_filter' ], 10 );
			add_action( 'woocommerce_after_calculate_totals', [ $this, 'remove_change_prices_filter' ], 10 );

			// if cart contain subscription with trial and cart price is 0, then return true to show payment gateways on checkout
			add_filter( 'woocommerce_cart_needs_payment', [ $this, 'cart_needs_payment' ], 10, 2 );

			// change cart name html for different payment type(subscription, one_time_fee, split_payment)
			add_filter( 'woocommerce_cart_item_name', [ $this, 'change_name_in_cart_html' ], 99, 3 );

			// change cart price html for different payment type(subscription, one_time_fee, split_payment)
			add_filter( 'woocommerce_cart_item_price', [ $this, 'change_price_in_cart_html' ], 99, 3 );
			add_filter( 'woocommerce_cart_item_subtotal', [ $this, 'change_subtotal_in_cart_html' ], 99, 3 );

		}

		/**
		 * Add change prices filter.
		 */
		public function add_change_prices_filter() {
			add_filter( 'woocommerce_product_get_price', [ $this, 'change_prices_for_calculation' ], 100, 2 );
			add_filter( 'woocommerce_product_variation_get_price', [ $this, 'change_prices_for_calculation' ], 100, 2 );
		}

		/**
		 * Remove the change price filter.
		 */
		public function remove_change_prices_filter() {
			remove_filter( 'woocommerce_product_get_price', [ $this, 'change_prices_for_calculation' ], 100 );
			remove_filter( 'woocommerce_product_variation_get_price', [ $this, 'change_prices_for_calculation' ], 100 );
		}


		/**
		 * Change price
		 *
		 * @param $price
		 * @param $product WC_Product
		 */
		public function change_prices_for_calculation( $price, $product ) {

			$product_id = $product->get_id();

			$payment_type = get_post_meta( $product_id, 's2_payment_type', true );
			if ( empty( $payment_type ) ) {
				return $price;
			}

			// if trial period available for subscription, one_time_fee payment then remove price from immediate charge
			if ( $payment_type == 'subscription' || $payment_type == 'one_time_fee' ) {

				$trial_period = get_post_meta( $product_id, 's2_trial_period', true );
				if ( ! empty( $trial_period ) && $trial_period != 'none' ) {
					// $price = 0;
				}

			}

			// subscription
			if ( $payment_type == 'subscription' || $payment_type == 'one_time_fee' ) {

				// $price = 0;

				// singup fee
				$signup_fee = get_post_meta( $product_id, 's2_sign_up_fee', true );
				if ( ! empty( $signup_fee ) ) {
					$price = floatval( $signup_fee ) + $price;
				}

			}

			// split payment
			if ( $payment_type == 'split_pay' ) {

				$split_payment = get_post_meta( $product_id, 's2_split_payment', true );
				$split_payment = intval( $split_payment );

				if ( ! empty( $split_payment ) && ! empty( $price ) ) {
					$price = $price / $split_payment;
					// $price = 0;
				}

			}

			return $price;

		}

		/**
		 * Check whether the cart needs payment even if the order total is $0
		 * if cart contain subscription with trial and cart price is 0, then return true to show payment gateways on checkout
		 *
		 * @param bool $needs_payment
		 * @param WC_Cart
		 *
		 * @return bool
		 */
		public function cart_needs_payment( $needs_payment, $cart ) {

			if ( false === $needs_payment && s2_cart_has_subscriptions() && $cart->total == 0 ) {
				return true;
			}

			return $needs_payment;
		}

		/**
		 * @param $price_html
		 * @param $cart_item
		 * @param $cart_item_key
		 *
		 * @return mixed|void
		 */
		public function change_name_in_cart_html( $name_html, $cart_item, $cart_item_key ) {

			if ( isset( $cart_item['data'] ) ) {

				$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
				$product    = $cart_item['data'];

				$payment_type = get_post_meta( $product_id, 's2_payment_type', true );

				if ( ! empty( $payment_type ) ) {

					$product_price = $product->get_price();

					// subscription
					if ( $payment_type == 'subscription' || $payment_type == 'one_time_fee' ) {

						// signup fee
						$sign_up_fee = get_post_meta( $product_id, 's2_sign_up_fee', true );
						if ( ! empty( $sign_up_fee ) ) {
							$name_html .= " (<small>Fee : " . wc_price( $sign_up_fee ) . "</small>)";
						}

					}

					if ( $payment_type == 'subscription' || $payment_type == 'one_time_fee' ) {

						$trial_period = get_post_meta( $product_id, 's2_trial_period', true );
						if ( ! empty( $trial_period ) && $trial_period != 'none' ) {
							$name_html .= " (<small>Trial : $trial_period</small>)";
						}

					}

				}

				$name_html = apply_filters( 's2_name_in_cart_html', $name_html, $cart_item, $cart_item_key );

			}

			return $name_html;

		}

		/**
		 * @param $price_html
		 * @param $cart_item
		 * @param $cart_item_key
		 *
		 * @return mixed|void
		 */
		public function change_price_in_cart_html( $price_html, $cart_item, $cart_item_key ) {

			if ( isset( $cart_item['data'] ) ) {

				$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
				$product    = $cart_item['data'];

				$payment_type = get_post_meta( $product_id, 's2_payment_type', true );

				if ( ! empty( $payment_type ) ) {

					$product_price = $product->get_price();

					// split payment
					if ( $payment_type == 'split_pay' ) {

						$split_payment = get_post_meta( $product_id, 's2_split_payment', true );
						$split_payment = intval( $split_payment );
						if ( ! empty( $split_payment ) && ! empty( $product_price ) ) {
							$product_price = $product_price / $split_payment;
						}

					}

					// subscription
					/*if( $payment_type == 'subscription' ) {

						// signup fee
						$sign_up_fee = get_post_meta( $product_id, 's2_sign_up_fee', true );
						if( ! empty( $sign_up_fee ) ) {

							$product_price += $sign_up_fee;

						}

					}*/

					$price_html = wc_price( $product_price );

				}

				$price_html = apply_filters( 's2_price_in_cart_html', $price_html, $cart_item, $cart_item_key );

			}

			return $price_html;

		}

		/**
		 * @param $price_html
		 * @param $cart_item
		 * @param $cart_item_key
		 *
		 * @return mixed|void
		 */
		public function change_subtotal_in_cart_html( $price_html, $cart_item, $cart_item_key ) {

			if ( isset( $cart_item['data'] ) ) {

				$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
				$product    = $cart_item['data'];

				$payment_type = get_post_meta( $product_id, 's2_payment_type', true );

				if ( ! empty( $payment_type ) ) {

					$product_price = $product->get_price();

					// split payment
					if ( $payment_type == 'split_pay' ) {

						$split_payment = get_post_meta( $product_id, 's2_split_payment', true );
						$split_payment = intval( $split_payment );
						if ( ! empty( $split_payment ) && ! empty( $product_price ) ) {
							$product_price = $product_price / $split_payment;
						}

						$product_price = $product_price * $cart_item['quantity'];

					}

					// subscription
					if ( $payment_type == 'subscription' || $payment_type == 'one_time_fee' ) {

						// signup fee
						$sign_up_fee = get_post_meta( $product_id, 's2_sign_up_fee', true );
						if ( ! empty( $sign_up_fee ) ) {

							$product_price += $sign_up_fee;
							$product_price = $product_price * $cart_item['quantity'];

						}

					}

					$price_html = wc_price( $product_price );

				}

				$price_html = apply_filters( 's2_subtotal_in_cart_html', $price_html, $cart_item, $cart_item_key );

			}

			return $price_html;

		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Cart class
 *
 * @return \S2_Subscription_Cart
 */
function S2_Subscription_Cart() {
	return S2_Subscription_Cart::get_instance();
}

S2_Subscription_Cart();
