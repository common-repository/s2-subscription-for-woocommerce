<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Gateway Paypal
 *
 * @class   S2_Gateway_Paypal
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Gateway_Paypal' ) ) {

	class S2_Gateway_Paypal extends WC_Gateway_Paypal {

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Gateway_Paypal
		 */
		protected static $instance;

		protected $wclog = '';

		protected $debug;
		protected $testmode;
		protected $email;
		protected $receiver_email;

		protected $api_username;
		protected $api_password;
		protected $api_signature;
		protected $api_endpoint;

		protected $setting_options;

		/**
		 * Single instance of S2_PayPal_API_Handler
		 *
		 * @var \S2_PayPal_API_Handler
		 */
		protected $api_handler;


		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Gateway_Paypal
		 * @since 1.0.0
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
			parent::__construct();

			$this->supports = array(
	            'products',
	            'refunds',
	            's2_subscription',
	            's2_subscription_paused',
	            's2_subscription_resumed',
	            's2_subscription_cancelled',
	            's2_subscription_cancel_now',
	            's2_subscription_cancel_with_refund',
	        );

			$settings = get_option( 'woocommerce_paypal_settings' );

			if ( ! isset( $settings['enabled'] ) || $settings['enabled'] != 'yes' ) {
				return;
			}

			$this->setting_options = $settings;
			$this->debug           = ( isset( $settings['debug'] ) && $settings['debug'] == 'yes' ) ? true : false;
			$this->testmode        = ( isset( $settings['testmode'] ) && $settings['testmode'] == 'yes' ) ? true : false;
			$this->email           = ( isset( $settings['email'] ) ) ? $settings['email'] : '';
			$this->receiver_email  = ( isset( $settings['receiver_email'] ) ) ? $settings['receiver_email'] : $this->email;
			$option_suffix         = $this->testmode ? 'sandbox_' : '';
			if ( $this->debug ) {
				$this->wclog = new WC_Logger();
			}

			// When necessary, set the PayPal args to be for a subscription instead of shopping cart
			add_filter( 'woocommerce_paypal_args', array( $this, 'subscription_args' ) );

			require_once 'includes/class.s2-paypal-api-error.php';
			require_once 'includes/exceptions/class.s2-paypal-api-exception.php';

			// Check if there's a subcription in a valid PayPal IPN request
			include_once WC()->plugin_path() . '/includes/gateways/paypal/includes/class-wc-gateway-paypal-ipn-handler.php';
			include_once 'includes/class.s2-paypal-ipn-handler.php';

			new S2_PayPal_IPN_Handler( $this->testmode, $this->receiver_email );

			// Set API credentials
			if ( ! empty( $settings[ $option_suffix . 'api_username' ] ) && ! empty( $settings[ $option_suffix . 'api_password' ] ) && ! empty( $settings[ $option_suffix . 'api_signature' ] ) ) {

				$this->api_username  = $settings[ $option_suffix . 'api_username' ];
				$this->api_password  = $settings[ $option_suffix . 'api_password' ];
				$this->api_signature = $settings[ $option_suffix . 'api_signature' ];
				$this->api_endpoint  = ( $settings['testmode'] == 'yes' ) ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';
			}

			include_once 'includes/class.s2-paypal-api-handler.php';
			include_once 'includes/class.s2-paypal-request.php';
			include_once 'includes/class.s2-paypal-response.php';
			include_once 'includes/class.s2-paypal-response-payment.php';
			$this->api_handler = new S2_PayPal_API_Handler( $this->testmode, $this->api_username, $this->api_password, $this->api_signature );

		}

		/**
		 * @param $args
		 *
		 * @return array|mixed|object
		 */
		protected function get_order_info( $args ) {
			if ( isset( $args['custom'] ) ) {
				$order_info = json_decode( $args['custom'], true );
			}

			return $order_info;
		}

		/**
		 * @param $item_name
		 *
		 * @return string
		 */
		protected static function format_item_name( $item_name ) {
			if ( strlen( $item_name ) > 127 ) {
				$item_name = substr( $item_name, 0, 124 ) . '...';
			}

			return html_entity_decode( $item_name, ENT_NOQUOTES, 'UTF-8' );
		}

		/**
		 * @param $args
		 *
		 * @return mixed
		 */
		public function subscription_args( $args ) {

			$order_info = $this->get_order_info( $args );

			if ( empty( $order_info ) || ! isset( $order_info['order_id'] ) ) {
				return $args;
			}

			$order = wc_get_order( $order_info['order_id'] );

			$subscription_on_order = s2_order_has_subscriptions( $order );
			if ( empty( $subscription_on_order ) ) {
				return $args;
			}

			// order total
			$order_total = $order->get_total();

			// check if order has subscriptions
			$order_items = $order->get_items();

			if ( empty( $order_items ) ) {
				return $args;
			}

			$item_names       = array();
			$has_subscription = false;

			foreach ( $order_items as $key => $order_item ) {

				$product_id = $order_item->get_variation_id();
				if ( empty( $product_id ) ) {
					$product_id = $order_item->get_product_id();
				}

				$subscription_id 	= wc_get_order_item_meta( $key, '_subscription_id', true );
				$subscription 		= new S2_Subscription( $subscription_id );

				$product          = wc_get_product( $product_id );
				$product_price    = $product->is_on_sale() ? $product->get_sale_price() : $product->get_regular_price();
				$payment_type 	  = $subscription->payment_type;
				$sign_up_fee      = 0;

				if ( ! empty( $payment_type ) ) {
					// It's a subscription
					$args['cmd'] 	 = '_xclick-subscriptions';
					$args['invoice'] = $this->setting_options['invoice_prefix'].$subscription_id;

					// set paypal subscription api recurring payment options
					$subscription_option = array();
					if ( $payment_type == 'subscription' ) {// if payment type is subscription then get billing frequency option

						$billing_frequency_meta = $subscription->billing_frequency;
						$subscription_option    = s2_get_billing_frequency_options( $billing_frequency_meta );

					} else if ( $payment_type == 'split_pay' ) {// if payment type is split payment then get split payment option

						$split_payment_meta  = $subscription->split_payment;
						$subscription_option = s2_get_split_payment_options( $split_payment_meta );

					}

					$price_is_per      = $subscription_option['period'];
					$price_time_option = s2_get_price_time_option_paypal( $subscription_option['time'] );

					$trial_period_meta     = $subscription->trial_period;
					$trial_period_option   = s2_get_trial_period_options( $trial_period_meta );
					$trial_is_per          = $trial_period_option['period'];
					$trial_time_option     = s2_get_price_time_option_paypal( $trial_period_option['time'] );

					$max_length = $subscription->max_length;

					$sign_up_fee = $subscription->sign_up_fee;

					// check which payment type selected and unset options as per payment type
					if ( $payment_type == 'one_time_fee' ) {

						$price_is_per      = 1;
						$price_time_option = 'D';
						$max_length 	   = 1;

						// $sign_up_fee = '';

					} else if ( $payment_type == 'split_pay' ) {

						$max_length = $subscription_option['period'];

						// if payment type is split payment then divide product_price by price_is_per to create minimum price installment
	                    // then set price_is_per to 1, becuase split_pay works as monthly payment
	                    $price_is_per  = 1;

						// if split_payment option available unset other option so it wont be sent to paypal
						$trial_is_per = $trial_time_option = $sign_up_fee = '';

					}

					if ( ! empty( $price_is_per ) ) {

						$args['a3'] = wc_format_decimal( $subscription->subscription_total, 2 );
						$args['p3'] = $price_is_per;
						$args['t3'] = $price_time_option;
					}

					// set paypal subscription api trial options
					if ( ! empty( $trial_is_per ) ) {
						// if trial period available of subscription product then add args a1, p1, t1
						$args['a1'] = wc_format_decimal( $order_total, 2 );
						$args['p1'] = $trial_is_per;
						$args['t1'] = $trial_time_option;

						if ( $subscription->subscription_total == $order_total ) {
							$args['a1'] = 0;
						} else {
							$args['a1'] -= $args['a3']; // remove subscription amount
						
							// used to check paypal ipn payment is trial / signup fee ipn
							$subscription->set( 'trial_payment', true );
						}

					} else {
						if ( $subscription->subscription_total != $order_total ) {

							// if max_length is 1 then add all amount to a3 i.e. one time payment
							if( $max_length == 1 ) {

								$args['a3'] = wc_format_decimal( $order_total, 2 );
								$args['p3'] = $price_is_per;
								$args['t3'] = $price_time_option;

							} else {
							
								$args['a1'] = wc_format_decimal( $order_total, 2 );
								$args['p1'] = $price_is_per;
								$args['t1'] = $price_time_option;

								// decrease max length by 1 as we are adding first payment of subscription in a1 args
								if( ! empty( $max_length ) ) $max_length -= 1;
							
							}

						}

					}

					if( $max_length != 1 ) {

						// for unlimited recurring payment src is 1
						$args['src'] = 1;

						// for fixed length recurring payment srt must be greater than 1
						if ( ! empty( $max_length ) && $max_length > 1 ) {
							$args['srt'] = $max_length;
						}
					
					}

				}

				$sign_up_fee = $sign_up_fee ? " - Signup Fee : $sign_up_fee " : '';
				if ( $order_item['qty'] > 1 ) {
					$item_names[] = $order_item['qty'] . ' x ' . $this->format_item_name( $order_item['name'] ) . $sign_up_fee;
				} else {
					$item_names[] = $this->format_item_name( $order_item['name'] ) . $sign_up_fee;
				}
			}

			// added for localhost testing with ngrok.io, added ipn notification url in paypal's account settings
			// if notify_url not set, paypal uses url from paypal's ipn setting(notification url) to send ipn from paypal
			if ( $_SERVER['HTTP_HOST'] == 'localhost' ) {
				unset( $args['notify_url'] );
			}

			$args['item_name'] = $this->format_item_name( sprintf( __( 'Order %s', 's2-subscription' ), $order->get_order_number() . ' - ' . implode( ', ', $item_names ) ) );

			// Force return URL by using the POST method so that order description & instructions display
			$args['rm'] = 2;

			return $args;
		}

		/**
	     * Update paypal subscriptions
	     *
	     * @return array|mixed|object
	     */
	    public function update_paypal_subscription( $subscription, $status ) {

	        if( empty( $subscription ) && empty( $status ) ) {
	            return;
	        }

	        $args = array();
		    try {

		        switch ( $status ) {

		            case 'paused':
		                // pause paypal subscription
		                $args = array(
							'action' => 'Suspend',
							'note' 	 => 'Customer-requested pause',
						);

						$this->api_handler->call_manage_recurring_payments_profile_status( $subscription->_paypal_subscription_id, $args );
		                break;
		            
		            case 'resumed':
		                // resume paypal subscription
		            	$args = array(
							'action' => 'Reactivate',
							'note' 	 => 'Reactivating on customer request',
						);

						$this->api_handler->call_manage_recurring_payments_profile_status( $subscription->_paypal_subscription_id, $args );
		                break;
		        
		        }

	        } catch ( S2_PayPal_API_Exception $e ) {
			
				WC_Gateway_Paypal::log( sprintf( __( 'An error occurred %s', 's2-subscription' ), $e->getMessage() ) );
				
				return array( 'success' => false, 'error' => $e->getMessage() );
			
			}

			return array( 'success' => true );

	    }

	    /**
	     * Cancel paypal subscription
	     */
	    public function cancel_paypal_subscription( $subscription, $status ) {

	        if( empty( $subscription ) && empty( $status ) ) {
	            return;
	        }

	        $args = array();
	        try {

		        switch ( $status ) {

		            case 'cancelled':
		                // Cancel (with collecting outstanding amount)

		            	if( ! empty( $subscription->_paypal_outstanding_balance ) ) {
		                
			                $args = array(
								'note' 		=> 'Requested by customer',
								'amount' 	=> $subscription->_paypal_outstanding_balance
							);

							$result = $this->api_handler->call_bill_outstanding_amount( $subscription->_paypal_subscription_id, $args );
		                
		                }
		                break;
		            
		            case 'cancel-now':
		                // Cancel Now (without collecting outstanding amount)
		            	/*$args = array(
							'action' => 'Cancel',
							'note' 	 => 'Cancel on customer request',
						);

						$this->api_handler->call_manage_recurring_payments_profile_status( $subscription->_paypal_subscription_id, $args );*/
		                break;

		            case 'cancel-with-refund':
		                // Cancel With Refund (Cancel and refund last transaction amount to customer)

		                if( ! empty( $subscription->_paypal_paid_transaction_id ) ) {

		                    $args = array(
								'transaction_id' 	=> $subscription->_paypal_paid_transaction_id,
								'note'          	=> 'Requested by customer',
							);

							$refund_result = $this->api_handler->call_refund_transaction( $args );
		                
		                }
		                break;
		        
		        }

		        $args = array(
					'action' => 'Cancel',
					'note' 	 => 'Cancel on customer request',
				);

				$this->api_handler->call_manage_recurring_payments_profile_status( $subscription->_paypal_subscription_id, $args );

	        } catch ( S2_PayPal_API_Exception $e ) {
			
				WC_Gateway_Paypal::log( sprintf( __( 'An error occurred %s', 's2-subscription' ), $e->getMessage() ) );
			
			}
	    
	    }

	}

}
