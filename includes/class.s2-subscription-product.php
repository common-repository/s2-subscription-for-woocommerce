<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements product features of S2 Subscription Product
 *
 * @class   S2_Subscription_Product
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Product' ) ) {

	class S2_Subscription_Product {

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Product
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Subscription_Product
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

			// Override the WooCommerce "Add to cart" text with "Subscribe"
			add_filter( 'woocommerce_product_add_to_cart_text', [ $this, 'add_to_cart_text' ], 10, 2 );
			add_filter( 'woocommerce_product_single_add_to_cart_text', [ $this, 'add_to_cart_text' ], 10, 2 );

			// Add text after price
			add_filter( 'woocommerce_get_price_html', [ $this, 'custom_price_html' ], 10, 2 );

		}

		/**
		 * Override the WooCommerce "Add to cart" text with "Subscribe"
		 *
		 * @param string $button_text
		 * @param object $product
		 *
		 * @since  1.0.0
		 */
		function add_to_cart_text( $button_text, $product ) {

			$s2_payment_type = get_post_meta( $product->get_id(), 's2_payment_type', true );
			if ( ! empty( $s2_payment_type ) ) {
				$button_text = 'Subscribe';
			}
		 
			return $button_text;

		}

		/**
		 * Add text after price
		 *
		 * @param string $price
		 * @param object $product
		 *
		 * @since  1.0.0
		 */
		function custom_price_html( $price, $product ) {

			$product_id = $product->get_id();
			s2_subscription_log( "product_id -- " . $product_id, 'subscription_price_html' );

			$s2_payment_type = get_post_meta( $product_id, 's2_payment_type', true );
			if ( ! empty( $s2_payment_type ) ) {
				//$price .= "<p>Payment 50% now and 50% in next month.</p>";

				if ( $s2_payment_type == 'split_pay' ) {

					$s2_split_payment = get_post_meta( $product_id, 's2_split_payment', true );
					$s2_split_payment = intval( $s2_split_payment );

					if ( ! empty( $s2_split_payment ) ) {

						$product_price = $product->is_on_sale() ? $product->get_sale_price() : $product->get_regular_price();
						$product_price = round( $product_price / $s2_split_payment, 2 );

						$s2_split_payment_meta = get_post_meta( $product_id, 's2_split_payment', true );
						$subscription_option    = s2_get_split_payment_options( $s2_split_payment_meta );

						$price_is_per      = $subscription_option['period'];
						$price_time_option = $subscription_option['time'];

						$price .= "<p><small>Payment will be split in $s2_split_payment payments, so you will be charged $product_price every month for the next $s2_split_payment months.</small></p>";
					}

				}

				if ( $s2_payment_type == 'subscription' || $s2_payment_type == 'one_time_fee' ) {

					$s2_billing_frequency_meta = get_post_meta( $product_id, 's2_billing_frequency', true );
					if( ! empty( $s2_billing_frequency_meta ) ) {
						$subscription_option        = s2_get_billing_frequency_options( $s2_billing_frequency_meta );
						$price_is_per      			= $subscription_option['period'];
						$price_time_option 			= $subscription_option['time'];
					}

					$s2_trial_period_meta = get_post_meta( $product_id, 's2_trial_period', true );
					$trial_time = 0;
					if( ! empty( $s2_trial_period_meta ) ) {
						$trial_period_option   = s2_get_trial_period_options( $s2_trial_period_meta );
						$trial_is_per          = $trial_period_option['period'];
						$trial_time_option     = $trial_period_option['time'];

						$trial_time = strtotime( $trial_is_per . ' ' . $trial_time_option ) - strtotime( 'now' );
					}

					if ( $s2_payment_type == 'subscription' ) {

						// $price .= " / " . $price_time_option;
						$price .= "<p><small> / " . ucwords( $s2_billing_frequency_meta ) . "</small></p>";

						$s2_max_length = get_post_meta( $product_id, 's2_max_length', true );
						if ( ! empty( $s2_max_length ) ) {

							$price .= "<p><small>Ends on - " . date( "F d, Y", ( strtotime( $s2_max_length . " " . $price_time_option ) + $trial_time ) ) . "</small></p>";

						} else {

							$price .= "<p><small>Renews Until You Cancel</small></p>";

						}

					}

					if ( $s2_payment_type == 'one_time_fee' ) {

						$price .= "<p><small>Ends on - " . date( "F d, Y", ( strtotime( "1 day" ) + $trial_time ) ) . "</small></p>";

					}

					$sign_up_fee = get_post_meta( $product_id, 's2_sign_up_fee', true );
					if ( ! empty( $sign_up_fee ) ) {

						$price .= "<p><small>Sign up fee : " . get_woocommerce_currency_symbol() . "$sign_up_fee (will be charged once)</small></p>";

					}

					if ( ! empty( $s2_trial_period_meta ) && $s2_trial_period_meta != 'none' ) {
						$price .= "<p><small>Trial period : $s2_trial_period_meta.</small></p>";
					}

				}

			}

			return $price;

		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Product class
*/
S2_Subscription_Product::get_instance();
