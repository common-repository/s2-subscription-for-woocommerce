<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Gateway Stripe
 *
 * @class   S2_Gateway_Stripe
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Gateway_Stripe' ) ) {

    class S2_Gateway_Stripe extends WC_Gateway_Stripe {

        /**
         * Single instance of the class
         *
         * @var \S2_Gateway_Stripe
         */
        protected static $instance;

        /**
         * Returns single instance of the class
         *
         * @return \S2_Gateway_Stripe
         */
        public static function get_instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Constructor
         */
        public function __construct() {
            parent::__construct();

            $this->supports = array(
                'products',
                'refunds',
                'tokenization',
                's2_subscription',
                's2_subscription_paused',
                's2_subscription_resumed',
                's2_subscription_cancelled',
                's2_subscription_cancel_now',
                's2_subscription_cancel_with_refund',
                's2_subscription_multiple',
            );

            // Check a valid Stripe Webhook request to see if it's a subscription
            add_action( 'woocommerce_api_wc_stripe', array( $this, 'check_for_subscription_webhook' ), 1 );

            require_once 'includes/class.s2-stripe-product.php';
            require_once 'includes/class.s2-stripe-plan.php';
            require_once 'includes/class.s2-stripe-subscription.php';
            require_once 'includes/class.s2-stripe-subscription-item.php';
            require_once 'includes/class.s2-stripe-invoice.php';
            require_once 'includes/class.s2-stripe-refund.php';
            require_once 'includes/class.s2-stripe-event.php';

        }

        /**
         * When a Stripe webhook messaged is received for a subscription transaction,
         * check the transaction details
         */
        public function check_for_subscription_webhook() {
            include_once 'includes/class.s2-stripe-custom-webhook-handler.php';
            $wc_stripe_custom_webhook_handler = new S2_Stripe_Custom_Webhook_Handler();

            $wc_stripe_custom_webhook_handler->check_for_subscription_webhook();
        }

    	/**
    	 * Process the payment
    	 *
    	 * @return array|void
    	 */
    	public function process_payment( $order_id, $retry = true, $force_save_source = false, $previous_error = false, $use_order_source = false ) {

    		// if cart does not contain subscription product then complete normal process_payment else create subscription
    		$subscription_on_order = s2_order_has_subscriptions( $order_id );
    		if ( empty( $subscription_on_order ) ) {

    			return parent::process_payment( $order_id, $retry, $force_save_source, $previous_error );

    		} else {

    			try {
    				$order = wc_get_order( $order_id );

    				// ToDo: `process_pre_order` saves the source to the order for a later payment.
    				// This might not work well with PaymentIntents.
    				if ( $this->maybe_process_pre_orders( $order_id ) ) {
    					return $this->pre_orders->process_pre_order( $order_id );
    				}

    				// Check whether there is an existing intent.
    				$intent = $this->get_intent_from_order( $order );
    				if ( isset( $intent->object ) && 'setup_intent' === $intent->object ) {
    					$intent = false; // This function can only deal with *payment* intents
    				}

    				$stripe_customer_id = null;
    				if ( $intent && ! empty( $intent->customer ) ) {
    					$stripe_customer_id = $intent->customer;
    				}

    				// For some payments the source should already be present in the order.
    				if ( $use_order_source ) {
    					$prepared_source = $this->prepare_order_source( $order );
    				} else {
    					$prepared_source = $this->prepare_source( get_current_user_id(), true, $stripe_customer_id );
    				}

    				$this->maybe_disallow_prepaid_card( $prepared_source );
    				$this->check_source( $prepared_source );
    				$this->save_source_to_order( $order, $prepared_source );

    				// Create stripe subscriptions
    				$this->create_stripe_subscriptions( $order_id );

                    // Remove cart.
                    WC()->cart->empty_cart();

    				// Return thank you page redirect.
    				return array(
    					'result'   => 'success',
    					'redirect' => $this->get_return_url( $order ),
    				);

    			} catch ( WC_Stripe_Exception $e ) {
    				wc_add_notice( $e->getLocalizedMessage(), 'error' );

    				// translators: error message
    				$order->update_status( 'failed' );

    				return array(
    					'result'   => 'fail',
    					'redirect' => '',
    				);
    			}

    		}

    	}

    	/**
    	 * Create stripe subscriptions product data
    	 */
        public function create_price_data( $args ) {

            $product_id = 0;
            if( ! empty( $product_id ) ) $product_id = $args['product_id'];

            //create stripe details of product
            $product_args = array( 'name' => $args['product_name'] );

            $stripe_product = new S2_Stripe_Product( $product_id );
            $stripe_product->create_product( $product_args );

            $price_data = array(
                'unit_amount' => WC_Stripe_Helper::get_stripe_amount( $args['product_price'] ),
                'currency'    => strtolower( get_woocommerce_currency() ),
                'product'     => $stripe_product->get_id(),
            );

            return $price_data;

        }

        /**
         * Create stripe subscriptions on checkout
         */
        public function create_non_subscription_product_price_data( $order ) {

            $order_items = $order->get_items();

            // for non-subscription product create product on stripe and price_data array
            // which will be added in first subscription's first invoice to charge once
            $non_subscription_product_price_data = array();
            if ( ! empty( $order_items ) ) {

                $order_total_tax        = $order->get_total_tax();
                $order_total_shipping   = $order->get_shipping_total();

                foreach ( $order_items as $key => $order_item ) {

                    $product_id = $order_item->get_variation_id();
                    if ( empty( $product_id ) ) {
                        $product_id = $order_item->get_product_id();
                    }

                    $product          = wc_get_product( $product_id );
                    $product_price    = $order_item->get_total();
                    $s2_payment_type = get_post_meta( $product_id, 's2_payment_type', true );

                    if ( empty( $s2_payment_type ) ) {

                        //create stripe details of product
                        $price_data_args = array(
                                            'product_id'    => $product_id,
                                            'product_name'  => $product->get_name(),
                                            'product_price' => $product_price
                                        );

                        $price_data = $this->create_price_data( $price_data_args );

                        $count                                                       = count( $non_subscription_product_price_data );
                        $non_subscription_product_price_data[ $count ]['price_data'] = $price_data;

                    } else {
                        
                        $subscription_id    = wc_get_order_item_meta( $key, '_subscription_id', true );
                        $subscription       = new S2_Subscription( $subscription_id );

                        // remove subscription tax from total order tax
                        if( ! empty( $subscription->order_tax ) && ! empty( $order_total_tax ) ) $order_total_tax -= $subscription->order_tax;
                        if( ! empty( $subscription->sign_up_fee_tax ) && ! empty( $order_total_tax ) ) $order_total_tax -= $subscription->sign_up_fee_tax;

                        // for payment type split_pay charge shipping_total, shipping_tax once
                        // so if payment type is not split_pay remove it from order_total_tax, order_total_shipping
                        if( $subscription->payment_type != 'split_pay' && $subscription->order_shipping ) {

                            if( ! empty( $order_total_tax ) ) $order_total_tax -= $subscription->order_shipping_tax;

                            if( ! empty( $order_total_shipping ) ) $order_total_shipping -= $subscription->order_shipping;

                        }

                    }

                }

                // Adds fees inside the request
                $fees = $order->get_fees();
                if ( $fees ) {
                    foreach ( $fees as $fee ) {

                        //create stripe details of product
                        $price_data_args = array(
                                            'product_id'    => 0,
                                            'product_name'  => $fee['name'],
                                            'product_price' => $fee['line_total']
                                        );

                        $price_data = $this->create_price_data( $price_data_args );

                        $count = count( $non_subscription_product_price_data );
                        
                        $non_subscription_product_price_data[ $count ]['price_data'] = $price_data;
                    }
                }

                // Adds taxes inside the request
                if ( ! empty ( $order_total_tax ) ) {

                    //create stripe details of product
                    $price_data_args = array(
                                        'product_id'    => 0,
                                        'product_name'  => 'Tax',
                                        'product_price' => $order_total_tax
                                    );

                    $price_data = $this->create_price_data( $price_data_args );

                    $count = count( $non_subscription_product_price_data );
                    
                    $non_subscription_product_price_data[ $count ]['price_data'] = $price_data;
                }

                // Adds shipping total inside the request
                if ( ! empty ( $order_total_shipping ) ) {

                    //create stripe details of product
                    $price_data_args = array(
                                        'product_id'    => 0,
                                        'product_name'  => 'Shipping',
                                        'product_price' => $order_total_shipping
                                    );

                    $price_data = $this->create_price_data( $price_data_args );

                    $count = count( $non_subscription_product_price_data );
                    
                    $non_subscription_product_price_data[ $count ]['price_data'] = $price_data;
                }

            }

            return $non_subscription_product_price_data;

        }

        /**
         * Create stripe subscriptions on checkout
         */
    	public function create_stripe_subscriptions( $order_id ) {

            $order = wc_get_order( $order_id );

    		$current_user       = wp_get_current_user();
    		$user_id            = $current_user->ID;
    		$stripe_customer_id = $this->get_stripe_customer_id( $order );

    		$non_subscription_product_price_data = $this->create_non_subscription_product_price_data( $order );

            // created susbcriptions on action woocommerce_checkout_order_processed
            $subscriptions = $order->get_meta( 'subscriptions' );

    		// loop through subscriptions and create stripe subscription
    		foreach ( $subscriptions as $key => $subscription_id ) {

    			$subscription = new S2_Subscription( $subscription_id );
    			$product_id   = $subscription->variation_id;
    			if ( empty( $product_id ) ) {
    				$product_id = $subscription->product_id;
    			}

    			$product       = wc_get_product( $product_id );
    			$product_price = $subscription->subscription_total;

    			$payment_type = $subscription->payment_type;

    			if ( ! empty( $payment_type ) ) {

    				$subscription_option = array();
    				if ( $payment_type == 'subscription' ) {// if payment type is subscription then get billing frequency option

    					$billing_frequency_meta = $subscription->billing_frequency;
    					$subscription_option    = s2_get_billing_frequency_options( $billing_frequency_meta );

    				} else if ( $payment_type == 'split_pay' ) {// if payment type is split payment then get split payment option

    					$split_payment_meta  = $subscription->split_payment;
    					$subscription_option = s2_get_split_payment_options( $split_payment_meta );

    				}

    				$price_is_per      = $subscription_option['period'];
    				$price_time_option = $subscription_option['time'];

    				$trial_period_meta   = $subscription->trial_period;
    				$trial_period_option = s2_get_trial_period_options( $trial_period_meta );
    				$trial_is_per        = $trial_period_option['period'];
    				$trial_time_option   = $trial_period_option['time'];

    				$max_length = $subscription->max_length;

    				$sign_up_fee     = $subscription->sign_up_fee;
                    $sign_up_fee_tax = $subscription->sign_up_fee_tax;

    				// check which payment type selected and unset options as per payment type
    				if ( $payment_type == 'one_time_fee' ) {

    					// if trial period empty then continue becuase it is not a subscription payment will be done immediately
    					/*if ( empty( $trial_is_per ) ) {
    						continue;
    					}*/

    					$price_is_per      = 1;
    					$price_time_option = 'day';
    					$max_length        = 1;

    					// if trial option available unset other option so it wont be sent to stripe
    					// $sign_up_fee = '';

    				} else if ( $payment_type == 'split_pay' ) {

    					$max_length = $subscription_option['period'];

                        // if payment type is split payment then divide product_price by price_is_per to create minimum price installment
                        // then set price_is_per to 1, becuase split_pay works as monthly payment
                        $price_is_per  = 1;

    					// if split_payment option available unset other option so it wont be sent to stripe
    					$trial_is_per = $trial_time_option = $sign_up_fee = '';

    				}

    				//create stripe details of product
    				$args         = array();
    				$args['name'] = $product->get_name();

    				$stripe_product = new S2_Stripe_Product( $product_id );
    				$stripe_product->create_product( $args );

    				// update product id in s2_subscription post meta
    				$subscription->set( '_stripe_product_id', $stripe_product->get_id() );

    				//create stripe details of plan
    				$args                   = array();
    				$args['amount']         = WC_Stripe_Helper::get_stripe_amount( $subscription->subscription_total );
    				$args['currency']       = strtolower( get_woocommerce_currency() );
    				$args['interval']       = $price_time_option;
    				$args['interval_count'] = $price_is_per;
    				$args['product']        = $stripe_product->get_id();

    				$stripe_plan = new S2_Stripe_Plan( $order_id );
    				$stripe_plan->create_plan( $args );

    				// update plan price in s2_subscription post meta
    				$subscription->set( '_stripe_plan_price', $subscription->subscription_total );

    				// update plan id in s2_subscription post meta
    				$subscription->set( '_stripe_plan_id', $stripe_plan->get_id() );

    				//create stripe details of subscription
    				$args = array();

    				// if signup fee available create product with name "Sing up fee" add it in first invoice_item
    				$args['add_invoice_items'] = array();

                    // add non subscription product price data in first subscription's first invoice
                    if ( $key == 0 ) {

                        // add non subscription product price data
                        if ( ! empty( $non_subscription_product_price_data ) ) {
                           $args['add_invoice_items'] = array_merge( $args['add_invoice_items'], $non_subscription_product_price_data );
                        }

                    }

    				if ( ! empty( $sign_up_fee ) ) {

                        //create stripe details of product
                        $price_data_args = array(
                                    'product_id'    => 0,
                                    'product_name'  => 'Sign up fee',
                                    'product_price' => ( $sign_up_fee * $subscription->quantity ) + $sign_up_fee_tax
                                );

                        $price_data = $this->create_price_data( $price_data_args );

    					$count = count( $args['add_invoice_items'] );

    					$args['add_invoice_items'][ $count ]['price_data'] = $price_data;

    				}

    				$args['customer']           = $stripe_customer_id;
    				$args['proration_behavior'] = 'none';

    				// $args['billing_cycle_anchor'] = strtotime( "+1 month" );
    				$args['items']    = array(
    					array( 
                            'price'     => $stripe_plan->get_id(),
                            // 'quantity'  => $subscription->quantity,
                        ),
    				);
    				
                    $args['metadata'] = array(
    					'order_id'        => $order->get_id(),
    					'order_key'       => $order->get_order_key(),
    					'subscription_id' => $subscription_id,
    				);

    				// set stripe subscription cancel_date
    				if ( ! empty( $max_length ) ) {

    					// if trial period is available increase max_length by 1 because stripe charge subscription immeditely with 0$ and create 1st invoice
    					if ( ! empty( $trial_is_per ) ) {
    						$max_length = $max_length + 1;
    					}

    					// $args['cancel_at']          = strtotime( "+" . $max_length . " " . $price_time_option );
    					$args['cancel_at'] = $subscription->expired_date;

    				}

    				// set paypal subscription api trial options
    				if ( ! empty( $trial_is_per ) ) {
    					// if trial period available of subscription product then add args trial_end timestamp
    					// $args['trial_end']          = strtotime( "+" . $trial_is_per . " " . $trial_time_option, $subscription->start_date );
    					$args['trial_end'] = $subscription->payment_due_date;

                        // used to check stripe ipn payment is trial / signup fee ipn
                        // if ( ! empty( $args['add_invoice_items'] ) ) { // if amount available with trial
                        $subscription->set( 'trial_payment', true );
    				    // }
                    }

    				// added for testing
    				// $args['trial_end']          = strtotime( "+300 seconds" );

    				$stripe_subscription = new S2_Stripe_Subscription( $order_id );
    				$stripe_subscription->create_subscription( $args );

    				// update subscription id in s2_subscription post meta
    				$subscription->set( '_stripe_subscription_id', $stripe_subscription->get_id() );

                    // update wp subscription details
                    $stripe_subscription = $stripe_subscription->retrieve_subscription();
                    // $order->add_order_note( __( 'Stripe subscription id - ' . $stripe_subscription->id, 's2-subscription' ) );

                    S2_Subscription_Activity()->add_activity( $subscription_id, 'new', $order_id, __( 'Stripe subscription id - ' . $stripe_subscription->id, 's2-subscription' ) );

                    // save subscription item id, which will be use for upgrade or downgrade subscription
                    $subscription->set( '_stripe_subscription_item_id', $stripe_subscription->items->data[0]->id );

                    $new_status = '';
                    if ( $stripe_subscription->status == 'incomplete' ) { // if immediate payment failed stripe send status as incomplete

                        $subscription->change_status( 'suspended', 'stripe-ipn' );

                    } else if ( $stripe_subscription->status == 'trialing' ) { // if trial stripe send status as trialing

                        $subscription->change_status( 'trial', 'stripe-ipn' );

                    }

                    // update wp subscription details using stripe invoice details
                    $subscription->set( '_stripe_latest_invoice', $stripe_invoice->latest_invoice );

                    $stripe_invoice = new S2_Stripe_Invoice( $subscription_id, $stripe_subscription->latest_invoice );
                    $stripe_invoice = $stripe_invoice->retrieve_invoice();
                    if ( $stripe_invoice->status == 'paid' ) {// stripe create paid invoice with 0 amount for trial subscription)

                        // if trial_payment is true then dont update status of subscription on first payment
                        if( empty( $subscription->trial_payment ) ) {

                            $subscription->set( '_stripe_paid_invoice', $stripe_invoice->latest_invoice );
                            
                            if( $stripe_invoice->payment_intent ) $subscription->set( '_stripe_paid_payment_intent', $stripe_invoice->payment_intent );
                            if( $stripe_invoice->charge ) $subscription->set( '_stripe_paid_payment_charge', $stripe_invoice->charge );

                            // save paid amount, so if subscription cancelled, api may use amount to refund to customer
                            $subscription->set( '_stripe_paid_payment_amount', $stripe_invoice->amount_paid );

                            $subscription->set( 'completed_billing_cycle', $subscription->completed_billing_cycle + 1 );

                            $subscription->change_status( 'active', 'stripe-ipn' );

                            // sometime stripe does not charge invoice and add that amount in next invoice so amount_paid is 0 in invoice
                            if( $stripe_invoice->amount_paid > 0 ) {
                                
                                $order->add_order_note( __( 'IPN stripe subscription - ' . $stripe_invoice->subscription . ' payment completed (Charge ID: ' . $stripe_invoice->charge . ')', 's2-subscription' ) );

                                S2_Subscription_Activity()->add_activity( $subscription_id, 'activated', $order_id, sprintf( __( 'IPN stripe subscription - payment completed (Charge ID: ' . $stripe_invoice->charge . ')', 's2-subscription' ), 'stripe-ipn' ) );
                            
                            }

                            $valid_order_statuses = array( 'on-hold', 'pending', 'failed', 'cancelled' );
                            if( $order->has_status( $valid_order_statuses ) ) {
                                $order->payment_complete();
                            }

                            // }
                        } else {
                            // update trial_payment so on receive next payment will update subscription, order status 
                            // if status in stripe subscription is not trialing
                            if( $stripe_subscription->status != 'trialing' ) $subscription->set( 'trial_payment', false );

                            // sometime stripe does not charge invoice and add that amount in next invoice so amount_paid is 0 in invoice
                            if( $stripe_invoice->amount_paid > 0 ) {

                                $order->add_order_note( __( 'IPN stripe subscription - ' . $stripe_invoice->subscription . ' trial / signup fee payment completed (Charge ID: ' . $stripe_invoice->charge . ')', 's2-subscription' ) );

                                S2_Subscription_Activity()->add_activity( $subscription_id, 'activated', $order_id, sprintf( __( 'IPN stripe subscription - trial / signup fee payment completed (Charge ID: ' . $stripe_invoice->charge . ')', 's2-subscription' ), 'stripe-ipn' ) );
                            }

                        }

                    }

    			}

    		}

    	}

    	/**
    	 * Update stripe subscriptions
    	 */
    	public function update_stripe_subscription( $subscription, $status ) {

    		if ( empty( $subscription ) && empty( $status ) ) {
    			return;
    		}

    		$args = array();
    		switch ( $status ) {

    			case 'paused':
    				//pause stripe subscription
    				$args['proration_behavior']   = 'none';
    				$args['cancel_at_period_end'] = 'false';
    				$args['pause_collection']     = array(
    					'behavior' => 'mark_uncollectible',
    				);
    				break;

    			case 'resumed':
    				//resume stripe subscription
    				$args['proration_behavior'] = 'none';
    				$args['pause_collection']   = "";
    				$args['cancel_at']          = $subscription->expired_date;
    				break;

    		}

    		if ( ! empty( $args ) ) {

    			$stripe_subscription = new S2_Stripe_Subscription( 0, $subscription->_stripe_subscription_id );
    			$stripe_subscription->update_subscription( $args );

    		}

    	}

    	/**
    	 * Cancel stripe subscription
    	 */
    	public function cancel_stripe_subscription( $subscription, $status ) {

    		if ( empty( $subscription ) && empty( $status ) ) {
    			return;
    		}

    		$args = array();
    		switch ( $status ) {

    			case 'cancelled':
    				//Cancel (with collecting payment for created / draft invoices)
    				// $args['invoice_now']   = 'true';

    				if ( ! empty( $subscription->_stripe_latest_invoice ) && $subscription->_stripe_latest_invoice != $subscription->_stripe_paid_invoice ) {

    					$stripe_invoice = new S2_Stripe_Invoice( $subscription->id, $subscription->_stripe_latest_invoice );
    					$stripe_invoice->pay_invoice();

    				}
    				break;
    			case 'cancel-now':
    				//Cancel Now (without collecting payment for created / draft invoices)
    				// $args['invoice_now']   = 'false';
    				break;
    			case 'cancel-with-refund':
    				//Cancel With Refund (Cancel and refund last invoice amount to customer)
    				// $args['invoice_now']   = 'false';

    				if ( ! empty( $subscription->_stripe_paid_payment_charge ) ) {

    					$refund_args           = array();
    					$refund_args['charge'] = $subscription->_stripe_paid_payment_charge;
    					// $args['payment_intent'] = $subscription->_stripe_paid_payment_intent;
    					$refund_args['amount'] = $subscription->_stripe_refund_amount;
    					$refund_args['reason'] = 'requested_by_customer';

    					$stripe_refund = new S2_Stripe_Refund( $subscription->id );
    					$stripe_refund->create_refund( $refund_args );

    				}
    				break;

    		}

    		$stripe_subscription = new S2_Stripe_Subscription( 0, $subscription->_stripe_subscription_id );
    		$stripe_subscription->cancel_subscription( $args );

    	}

    }

}
