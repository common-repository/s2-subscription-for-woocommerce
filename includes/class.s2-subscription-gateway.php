<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements product features of S2 Subscription Gateway
 *
 * @class   S2_Subscription_Gateway
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Gateway' ) ) {

	class S2_Subscription_Gateway {

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Gateway
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Subscription_Gateway
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
		 * Initialize plugin and registers actions and filters to be used
		 */
		public function __construct() {

			if ( class_exists( 'WC_Gateway_Stripe' ) ) require_once S2_WS_INC . 'gateways/stripe/class.s2-gateway-stripe.php';
			require_once S2_WS_INC . 'gateways/paypal-standard/class.s2-gateway-paypal.php';
			require_once S2_WS_INC . 'gateways/cod/class.s2-gateway-cod.php';

			// Add the gateways to WooCommerce.
			add_filter( 'woocommerce_payment_gateways', [ $this, 'add_gateways' ] );

			// Disable the gateways if support not available.
			add_action( 'woocommerce_available_payment_gateways', [ $this, 'disable_gateways' ] );

		}

		/**
		 * Add the gateways to WooCommerce.
		 */
		function add_gateways( $methods ) {

		    foreach ( $methods as $key => $method ) {

		        if ( 'WC_Gateway_Stripe' == $method && class_exists( 'S2_Gateway_Stripe' ) ) {
		
		            $methods[ $key ] = 'S2_Gateway_Stripe';
		
		        } else if ( 'WC_Gateway_Paypal' == $method && class_exists( 'S2_Gateway_Paypal' ) ) {
		
		            $methods[ $key ] = 'S2_Gateway_Paypal';
		
		        } else if ( 'WC_Gateway_COD' == $method && class_exists( 'S2_Gateway_COD' ) ) {
		
		            $methods[ $key ] = 'S2_Gateway_COD';
		
		        }
		
		    }

		    return $methods;
		
		}

		/**
		 * Disable gateways that don't support multiple subscription on cart
		 */
		function disable_gateways( $gateways ) {

			if ( WC()->cart && is_checkout() ) {

				$subscription_on_cart = s2_cart_has_subscriptions();
				if ( is_array( $subscription_on_cart ) && count( $subscription_on_cart ) >= 2 && WC()->payment_gateways() ) {

					foreach ( $gateways as $gateway_id => $gateway ) {

						if ( ! $gateway->supports( 's2_subscription_multiple' ) ) {
							unset( $gateways[ $gateway_id ] );
						}

					}

				}

			}

			return $gateways;
		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Gateway class
 *
 * @return \S2_Subscription_Gateway
 */
S2_Subscription_Gateway::get_instance();
