<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements product features of S2 Subscription Order
 *
 * @class   S2_Subscription_Order
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Order' ) ) {

	class S2_Subscription_Order {

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Order
		 */
		protected static $instance;

		/**
		 * @var array
		 */
		private $cart_item_order_item = [];

		/**
		 * @var boolean
		 */
		private $payment_done = [];

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Subscription_Order
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

			// Save details of subscription
			add_action( 'woocommerce_new_order_item', [ $this, 'add_subscription_order_item_meta' ], 20, 3 );

			// Add subscriptions from orders
			add_action( 'woocommerce_checkout_order_processed', [ $this, 'check_order_for_subscription' ], 10, 2 );

			// If there's a subscription inside the order, even if the order total is $0, it still needs payment
			add_filter( 'woocommerce_order_needs_payment', [ $this, 'order_need_payment' ], 10, 3 );

		}

		/**
		 * Save the options of subscription in an array with order item id
		 *
		 * @access   public
		 *
		 * @param  $item_id
		 * @param  $item WC_Order_Item_Product
		 * @param  $order_id
		 *
		 * @return string
		 * @internal param int $cart_item_key
		 *
		 * @internal param int $item_id
		 * @internal param array $values
		 */
		public function add_subscription_order_item_meta( $item_id, $item, $order_id ) {
			if ( isset( $item->legacy_cart_item_key ) ) {
				$this->cart_item_order_item[ $item->legacy_cart_item_key ] = $item_id;
			}
		}

		/**
		 * Check in the order if there's a subscription and create it
		 *
		 * @access public
		 *
		 * @param  $order_id int
		 * @param  $posted   array
		 */
		public function check_order_for_subscription( $order_id, $posted ) {

			if ( ! s2_cart_has_subscriptions() ) {
				return;
			}

			// remove action added in cart which removes price of subscription from cart
			remove_action( 'woocommerce_before_calculate_totals', [ S2_Subscription_Cart(), 'add_change_prices_filter' ], 10 );

			$cart           = WC()->cart;
			$cart_items     = $cart->get_cart();
			$order          = wc_get_order( $order_id );
			$order_items    = $order->get_items();
			$order_args     = [];
			$user_id        = $order->get_customer_id();
			$order_currency = $order->get_currency();

			if ( empty( $cart_items ) ) {
				return;
			}

			$subscriptions = [];

			$common_args = [
				//order details
				'order_id'             => $order_id,
				'order_ids'            => [ $order_id ],
				'payment_method'       => $order->get_payment_method(),
				'payment_method_title' => $order->get_payment_method_title(),
				'order_currency'       => $order_currency,
				'prices_include_tax'   => $order->get_meta( 'prices_include_tax' ),

				//user details
				'user_id'              => $user_id,
				'customer_ip_address'  => $order->get_customer_ip_address(),
				'customer_user_agent'  => $order->get_customer_user_agent(),
			];

			$applied_coupons = WC()->cart->get_applied_coupons();

			foreach ( $cart_items as $cart_item_key => $cart_item ) {

				$product_id = $cart_item['variation_id'];
				if ( empty( $product_id ) ) {
					$product_id = $cart_item['product_id'];
				}

				if ( ! s2_is_subscription( $product_id ) ) {
					continue;
				}

				$product = wc_get_product( $product_id );

				$payment_type      = get_post_meta( $product_id, 's2_payment_type', true );
				$split_payment     = get_post_meta( $product_id, 's2_split_payment', true );
				$billing_frequency = get_post_meta( $product_id, 's2_billing_frequency', true );
				$max_length        = get_post_meta( $product_id, 's2_max_length', true );
				$trial_period      = get_post_meta( $product_id, 's2_trial_period', true );
				$sign_up_fee       = get_post_meta( $product_id, 's2_sign_up_fee', true );

				// create new_cart object to save subscription data in database and use woocommerce cart functions
				// to save original price in subscription data we are removing price of that subscription from cart becuase stripe create seperate payment intent for subscription
				$new_cart = new WC_Cart();

				// change price for payment type split_pay in cart
				add_filter( 'woocommerce_product_get_price', [ $this, 'change_prices_for_calculation' ], 100, 2 );
				add_filter( 'woocommerce_product_variation_get_price', [ $this, 'change_prices_for_calculation' ], 100, 2 );

				$new_cart_item_key = $new_cart->add_to_cart( $cart_item['product_id'], $cart_item['quantity'], ( isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : '' ), ( isset( $cart_item['variation'] ) ? $cart_item['variation'] : '' ), $cart_item );

				// coupons
				$coupon_codes = $coupons = [];
				foreach ( $applied_coupons as $coupon_code ) {

					$coupon        = new WC_Coupon( $coupon_code );
					$coupon_type   = $coupon->get_discount_type();
					$coupon_amount = $coupon->get_amount();
					$valid         = $coupon->is_valid();

					if ( $valid && in_array( $coupon_type, [ 'recurring_percent', 'recurring_fixed' ] ) ) {

						$price     = $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal'];
						$price_tax = $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal_tax'];

						switch ( $coupon_type ) {
							case 'recurring_percent':
								$discount_amount     = round( ( $price / 100 ) * $coupon_amount, WC()->cart->dp );
								$discount_amount_tax = round( ( $price_tax / 100 ) * $coupon_amount, WC()->cart->dp );
								break;
							case 'recurring_fixed':
								$discount_amount     = ( $price < $coupon_amount ) ? $price : $coupon_amount;
								$discount_amount_tax = 0;
								break;
						}

						$coupons[] = [
							'coupon_code'         => $coupon_code,
							'discount_amount'     => $discount_amount * $cart_item['quantity'],
							'discount_amount_tax' => $discount_amount_tax * $cart_item['quantity']
						];

						$coupon_codes[] = $coupon_code;

					}

				}

				if ( ! empty( $coupon_codes ) && $trial_period == 'none' ) {
					WC()->cart->discount_cart       = 0;
					WC()->cart->discount_cart_tax   = 0;
					WC()->cart->subscription_coupon = 1;
				
					/*foreach ( $coupon_codes as $coupon_code ) {
						$new_cart->apply_coupon( $coupon_code );
					}*/

					$new_cart->applied_coupons = $coupon_codes;
					$new_cart->calculate_totals();

				}

				remove_filter( 'woocommerce_product_get_price', [ $this, 'change_prices_for_calculation' ], 100 );
				remove_filter( 'woocommerce_product_variation_get_price', [ $this, 'change_prices_for_calculation' ], 100 );

				$order_item_id = $this->cart_item_order_item[ $cart_item_key ];

				$product_price = $product->is_on_sale() ? $product->get_sale_price() : $product->get_regular_price();

				$order_total = wc_format_decimal( $new_cart->total, get_option( 'woocommerce_price_num_decimals' ) );

				if ( $payment_type == 'split_pay' ) {

					$subscription_option = s2_get_split_payment_options( $split_payment );
					$max_length      	 = $subscription_option['period'];

					// for payment type split_pay charge shipping_total, shipping_tax_total once
					// so remove it from order_total
					if( ! empty( $new_cart->shipping_total ) ) {

						$order_total -= $new_cart->shipping_total;
						$order_total -= $new_cart->shipping_tax_total;

					}

				} else if ( $payment_type == 'one_time_fee' ) {

					$max_length = 1;

				}

				if ( $sign_up_fee ) {
					wc_add_order_item_meta( $order_item_id, '_fee', $sign_up_fee, true );
				}

				// fill the array for subscription creation
				$args = [
					'product_id'              => $cart_item['product_id'],
					'variation_id'            => $cart_item['variation_id'],
					'product_name'            => $product->get_name(),
					'product_price'           => $product_price,

					//order details
					'order_item_id'           => $order_item_id,
					'cart_discount'           => wc_format_decimal( $new_cart->get_cart_discount_total() ),
					'cart_discount_tax'       => wc_format_decimal( $new_cart->get_cart_discount_tax_total() ),
					'subscription_total'      => $order_total,
					'order_total'             => $order_total,
					'order_tax'               => wc_format_decimal( $new_cart->tax_total ),
					'order_subtotal'          => wc_format_decimal( $new_cart->subtotal, get_option( 'woocommerce_price_num_decimals' ) ),
					'order_discount'          => $cart->get_total_discount(),
					'order_shipping'          => wc_format_decimal( $new_cart->shipping_total ),
					'order_shipping_tax'      => wc_format_decimal( $new_cart->shipping_tax_total ),
					'line_subtotal'           => wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal'], get_option( 'woocommerce_price_num_decimals' ) ),
					'line_total'              => wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_total'], get_option( 'woocommerce_price_num_decimals' ) ),
					'line_subtotal_tax'       => wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal_tax'] ),
					'line_tax'                => wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_tax'] ),
					'line_tax_data'           => $new_cart->cart_contents[ $new_cart_item_key ]['line_tax_data'],
					'coupons' 				  => $coupons,
					'subscriptions_shippings' => '',
					'quantity'                => $cart_item['quantity'],

					//item subscription detail
					'payment_type'            => $payment_type,
					'split_payment'           => $split_payment,
					'billing_frequency'       => $billing_frequency,
					'max_length'              => $max_length,
					'total_billing_cycle'     => $max_length,
					'trial_period'            => $trial_period,
					'sign_up_fee'             => $sign_up_fee,
					'sign_up_fee_tax'         => 0,
				];

				// Get shipping details
				if ( $new_cart->needs_shipping() && $product->needs_shipping() ) {

					$method = null;
					foreach ( WC()->shipping->get_packages() as $key => $package ) {
						if ( isset( $package['rates'][ $posted['shipping_method'][ $key ] ] ) ) {
							$method = $package['rates'][ $posted['shipping_method'][ $key ] ];
							break;
						}
					}

					if ( ! is_null( $method ) ) {
						$args['subscriptions_shippings'] = [
							'name'      => $method->label,
							'method_id' => $method->id,
							'cost'      => wc_format_decimal( $method->cost ),
							'taxes'     => $method->taxes,
						];
					}
				}

				// add signup fee in cart item to calculate tax on signup fee to use it with stripe, paypal gateway functionality
				if( ! empty( $sign_up_fee ) ) {

					foreach ( $new_cart->get_cart() as $item ) {
				        $item['data']->set_price( $product_price + $sign_up_fee );
				    }

				    $new_cart->calculate_totals();

				    // remove product tax then we will get signup fee tax
				    $args['sign_up_fee_tax'] = $new_cart->tax_total - $args['line_tax'];
				    // $args['sign_up_fee'] += $args['sign_up_fee_tax'];
				}

				$args = array_merge( $common_args, $args );

				$subscription = new S2_Subscription( '', $args );

				if ( $subscription->id ) {
					// calculate payment_due, expired date etc.
					// date will be used in gateway(stripe etc.) functionality
					$subscription->start();

					$subscriptions[] = $subscription->id;
					$order->add_order_note( sprintf( __( 'A new subscription <a href="%s">#%s</a> has been created from this order', 's2-subscription' ), admin_url( 'post.php?post=' . $subscription->id . '&action=edit' ), $subscription->id ) );

					wc_add_order_item_meta( $order_item_id, '_subscription_id', $subscription->id, true );
				}

			}

			// save subscriptions id in order meta
			$order_args['subscriptions'] = $subscriptions;
			// $order_args['_new_order_email_sent'] = 'true';

			if ( ! empty( $order_args ) ) {
				foreach ( $order_args as $key => $value ) {
					$order->update_meta_data( $key, $value );
				}
				$order->save();
			}

		}

		/**
		 * Change price
		 *
		 * @param $price
		 * @param $product WC_Product
		 */
		public function change_prices_for_calculation( $price, $product ) {

			$product_id = $product->get_id();

			$s2_payment_type = get_post_meta( $product_id, 's2_payment_type', true );
			if ( empty( $s2_payment_type ) ) {
				return $price;
			}

			// split payment
			if ( $s2_payment_type == 'split_pay' ) {

				$s2_split_payment = get_post_meta( $product_id, 's2_split_payment', true );
				$s2_split_payment = intval( $s2_split_payment );

				if ( ! empty( $s2_split_payment ) && ! empty( $price ) ) {
					$price = $price / $s2_split_payment;
				}

			}

			return $price;

		}

		/**
		 * If there's a subscription inside the order, even if the order total is $0, it still needs payment
		 *
		 * @param $needs_payment        bool
		 * @param $order                WC_Order
		 * @param $valid_order_statuses array
		 *
		 * @return bool
		 */
		public function order_need_payment( $needs_payment, $order, $valid_order_statuses ) {

			if ( ! $needs_payment && s2_order_has_subscriptions( $order ) && 0 == $order->get_total() ) {
				return true;
			}

			return $needs_payment;

		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Order class
*/
S2_Subscription_Order::get_instance();
