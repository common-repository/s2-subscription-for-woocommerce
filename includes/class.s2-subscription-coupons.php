<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements product features of S2 Subscription Coupons
 *
 * @class   S2_Subscription_Coupons
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Coupons' ) ) {

	class S2_Subscription_Coupons {

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Coupons
		 */
		protected static $instance;

		/**
		 * Single instance of the class
		 *
		 * @var array coupon_types
		 */
		protected $coupon_types = [];
		
		/**
		 * Single instance of the class
		 *
		 * @var string coupon_error
		 */
		protected $coupon_error = '';

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Subscription_Coupons
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

			$this->coupon_types = [ 'recurring_percent', 'recurring_fixed' ];
			// Add new coupons type to administrator
			add_filter( 'woocommerce_coupon_discount_types', [ $this, 'add_coupon_discount_types' ] );
			add_filter( 'woocommerce_product_coupon_types', [ $this, 'add_coupon_discount_types_list' ] );

			// Apply discounts to a product and get the discounted price (before tax is applied).
			add_filter( 'woocommerce_coupon_get_discount_amount', [ $this, 'coupon_get_discount_amount' ], 10, 5 );

			// Validate coupons
			add_filter( 'woocommerce_coupon_is_valid', [ $this, 'validate_coupon' ], 10, 2 );

		}

		/**
		 * Add discount types on coupon system
		 *
		 * @param $coupons_type
		 *
		 * @return mixed
		 */
		public function add_coupon_discount_types( $coupons_type ) {

			$coupons_type['recurring_percent'] = __( 'Subscription Recurring % Discount', 's2-subscription' );
			$coupons_type['recurring_fixed']   = __( 'Subscription Recurring Fixed Discount', 's2-subscription' );

			return $coupons_type;
		}

		/**
		 * @param $coupons_type
		 *
		 * @return array
		 */
		public function add_coupon_discount_types_list( $coupons_type ) {
			return array_merge(
				$coupons_type,
				[
					'recurring_percent',
					'recurring_fixed',
				]
			);
		}

		/**
		 * @param $discount
		 * @param $discounting_amount
		 * @param $cart_item
		 * @param $single
		 * @param $coupon
		 *
		 * @return float|int|mixed
		 * @throws Exception
		 */
		public function coupon_get_discount_amount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {

			$product    = $cart_item['data'];
			// s2_subscription_log( "product -- " . print_r( $product, true ), 'subscription_coupon' );
			$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
			if ( ! s2_is_subscription( $product_id ) ) {
				// s2_subscription_log( "discount -- " . $product_id . " -- " . $discount, 'subscription_coupon' );
				return $discount;
			}
			
			$trial_period = get_post_meta( $product_id, 's2_trial_period', true );
			$signup_fee   = get_post_meta( $product_id, 's2_sign_up_fee', true );
			
			$recurring_price = $product->get_price();
			$regular_price = $product->get_regular_price();
			
			// check if recurring_price is greater than regular_price, then remove signup_fee from recurring_price because signup_fee was added using change price filter
			if( ! empty( $signup_fee ) && $recurring_price > $regular_price) {
				$recurring_price -= $signup_fee;
			}

			$valid = $coupon->is_valid();

			if ( ! empty( $coupon ) && $valid ) {

				$coupon_type   = method_exists( $coupon, 'get_discount_type' ) ? $coupon->get_discount_type() : $coupon->type;
				$coupon_amount = method_exists( $coupon, 'get_amount' ) ? $coupon->get_amount() : $coupon->amount;

				switch ( $coupon_type ) {
					case 'recurring_percent':
						if ( $trial_period == 'none' || isset( WC()->cart->subscription_coupon ) ) {
							$discount = round( ( $recurring_price / 100 ) * $coupon_amount, WC()->cart->dp );
						}
						break;
					case 'recurring_fixed':
						if ( $trial_period == 'none' || isset( WC()->cart->subscription_coupon ) ) {
							$discount = ( $recurring_price < $coupon_amount ) ? $recurring_price : $coupon_amount;
						}
						break;
					default:
						$discount = 0;
				}
			}

			// s2_subscription_log( "coupon_type -- " . $coupon_type, 'subscription_coupon' );
			// s2_subscription_log( "discount -- " . $discount, 'subscription_coupon' );
			return $discount;

		}

		/**
		 * Check if coupon is valid
		 *
		 * @param $is_valid
		 * @param $coupon
		 *
		 * @return bool
		 */
		public function validate_coupon( $is_valid, $coupon ) {

			$total_product = count( WC()->cart->get_cart() );
			if ( ! $total_product ) {
				return false;
			}

			$this->coupon_error = '';
			$coupon_type        = method_exists( $coupon, 'get_discount_type' ) ? $coupon->get_discount_type() : $coupon->type;
			
			$subscription_product = s2_cart_has_subscriptions();
			$subscription_product = is_array( $subscription_product ) ? count( $subscription_product ) : 0;

			$cart_has_only_subscription = $total_product == $subscription_product;

			// return true if cart has not a subscription & coupon type is not recurring, so coupon apply on non-subscription product
			if ( ! in_array( $coupon_type, $this->coupon_types ) && ! $cart_has_only_subscription ) {
				return $is_valid;
			}

			// return false if cart has subscription & coupon type is not recurring, so coupon wont apply on subscription product
			if ( ! in_array( $coupon_type, $this->coupon_types ) && $cart_has_only_subscription ) {
				
				$this->coupon_error = __( 'Sorry, this coupon ' . $coupon->code . ' can be used only if there is a non-subscription product in the cart', 's2-subscription' );

			}

			// return false if cart has not subscription & coupon type is recurring, so coupon wont apply on non-subscription product
			if ( in_array( $coupon_type, $this->coupon_types ) && ! $subscription_product ) {
				
				$this->coupon_error = __( 'Sorry, this coupon ' . $coupon->code . ' can be used only if there is a subscription product in the cart', 's2-subscription' );

			}

			if ( ! empty( $this->coupon_error ) ) {
				$is_valid = false;
				add_filter( 'woocommerce_coupon_error', [ $this, 'add_coupon_error' ], 10 );
			}

			return $is_valid;
		}


		/**
		 * Add coupon error if the coupon is not valid
		 *
		 * @param $error
		 *
		 * @return string
		 */
		public function add_coupon_error( $error ) {
			if ( ! empty( $this->coupon_error ) ) {
				$errors = $this->coupon_error;
			}

			return $errors;
		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Coupons class
*/
S2_Subscription_Coupons::get_instance();
