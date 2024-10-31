<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Paypal IPN Handler
 *
 * @class   S2_PayPal_IPN_Handler
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_PayPal_IPN_Handler' ) ) {

	class S2_PayPal_IPN_Handler extends WC_Gateway_Paypal_IPN_Handler {

		/** @var Array transaction types this class can handle */
		protected $transaction_types = array(
			'recurring_payment_profile_created',
			// Subscription started
			'subscr_signup',
			// Subscription started

			'recurring_payment',
			// Subscription payment received
			'recurring_payment_outstanding_payment',
			// Subscription payment received
			'subscr_payment',
			// Subscription payment received

			'recurring_payment_profile_cancel',
			// Subscription canceled
			'subscr_cancel',
			// Subscription canceled

			'recurring_payment_expired',
			// Subscription expired
			'subscr_eot',
			// Subscription expired

			'subscr_failed',
			// Subscription payment failed
			'subscr_modify',
			// Subscription modified

			// The PayPal docs say these are for Express Checkout recurring payments but they are also sent for PayPal Standard subscriptions
			'recurring_payment_skipped',
			// Recurring payment skipped; it will be retried up to 3 times, 5 days apart
			'recurring_payment_suspended',
			// Recurring payment suspended. This transaction type is sent if PayPal tried to collect a recurring payment, but the related recurring payments profile has been suspended.
			'recurring_payment_suspended_due_to_max_failed_payment',
			// Recurring payment failed and the related recurring payment profile has been suspended
		);

		/**
		 * Constructor.
		 *
		 * @param bool $sandbox Use sandbox or not.
		 * @param string $receiver_email Email to receive IPN from.
		 */
		public function __construct( $sandbox = false, $receiver_email = '' ) {
			add_action( 'valid-paypal-standard-ipn-request', array( $this, 'valid_response' ), 0 );

			$this->sandbox        = $sandbox;
			$this->receiver_email = $receiver_email;
		}

		/**
		 * There was a valid response.
		 *
		 * @param array $posted Post data after wp_unslash.
		 */
		public function valid_response( $posted ) {
			if ( ! $this->validate_transaction_type( $posted['txn_type'] ) ) {
				return;
			}

			$posted['txn_type'] = strtolower( $posted['txn_type'] );

			$this->process_ipn_request( $posted );
			exit;
		}

		/**
		 * Check for a valid transaction type.
		 *
		 * @param string $txn_type Transaction type.
		 */
		protected function validate_transaction_type( $txn_type ) {
			if ( ! in_array( strtolower( $txn_type ), $this->transaction_types ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Check payment amount from IPN matches the order.
		 *
		 * @param WC_Order $order
		 * @param int $amount
		 */
		protected function validate_amount( $order, $amount ) {
			if ( number_format( $order->get_total(), 2, '.', '' ) != number_format( $amount, 2, '.', '' ) ) {

				// Put this order on-hold for manual checking.
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal amounts do not match (gross %s).', 's2-subscription' ), $amount ) );
				exit;
			}
		}

		/**
		 * Save important data from the IPN to the order.
		 *
		 * @param WC_Order $order Order object.
		 * @param array $ipn_args Posted data.
		 */
		protected function save_paypal_data( $order, $ipn_args ) {

			$sub_id = '';
			if ( isset( $ipn_args['subscr_id'] ) ) {
				$sub_id = $ipn_args['subscr_id'];
			} elseif ( isset( $ipn_args['recurring_payment_id'] ) ) {
				$sub_id = $ipn_args['recurring_payment_id'];
			}

			$args = array(
				'subscriber_id'             => $sub_id,
				'subscriber_first_name'     => $ipn_args['first_name'],
				'subscriber_last_name'      => $ipn_args['last_name'],
				'subscriber_email'          => $ipn_args['payer_email'],
				'subscriber_payment_type'   => isset( $ipn_args['payment_type'] ) ? $ipn_args['payment_type'] : '',
				'subscriber_payment_status' => isset( $ipn_args['payment_status'] ) ? $ipn_args['payment_status'] : '',
			);

			foreach ( $args as $key => $value ) {
				update_post_meta( $order_id, $key, $value );
			}
		}

		/**
		 * Checks a set of args and derives an subscription ID
		 *
		 * @param array $args Post data.
		 */
		public function get_subscription_id( $ipn_args ) {
			$subscription_id = $paypal_subscription_id = '';

			if ( isset( $ipn_args['subscr_id'] ) ) {
				$paypal_subscription_id = $ipn_args['subscr_id'];
			} else if ( isset( $ipn_args['recurring_payment_id'] ) ) {
				$paypal_subscription_id = $ipn_args['recurring_payment_id'];
			}

			// First try and get the subscription ID by the paypal subscription ID
			if ( ! empty( $paypal_subscription_id ) ) {

				$posts = get_posts( array(
					'numberposts'      => 1,
					'orderby'          => 'ID',
					'order'            => 'ASC',
					'post_type'        => 's2_subscription',
					'post_status'      => 'any',
					'suppress_filters' => true,
					'meta_key'         => '_paypal_subscription_id',
					'meta_value'       => $paypal_subscription_id,
					'meta_compare'     => 'LIKE'
				) );

				if ( ! empty( $posts ) ) {
					$subscription_id = $posts[0]->ID;
				}
			}

			return $subscription_id;
		}

		/**
		 * Checks a set of args and derives an Order ID
		 *
		 * @param array $args Post data.
		 */
		protected function get_order_info( $args ) {
			if ( isset( $args['custom'] ) ) {
				$order_info = json_decode( $args['custom'], true );
			}

			return $order_info;
		}

		/**
		 * check if the ipn request as been processed
		 *
		 * @param string $transaction_ids .
		 * @param array $args Post data.
		 */
		protected function is_a_valid_transaction( $transaction_ids, $ipn_args ) {

			$transaction_ids = empty( $transaction_ids ) ? array() : $transaction_ids;

			if ( isset( $ipn_args['txn_id'] ) ) {
				$transaction_id = $ipn_args['txn_id'] . '-' . $ipn_args['txn_type'];

				if ( isset( $ipn_args['payment_status'] ) ) {
					$transaction_id .= '-' . $ipn_args['payment_status'];
				}

				if ( empty( $transaction_ids ) || ! in_array( $transaction_id, $transaction_ids ) ) {
					$transaction_ids[] = $transaction_id;
				} else {
					if ( $this->debug ) {
						$this->wclog->add( 'paypal', 's2 - Subscription IPN Error: IPN ' . $transaction_id . ' message has already been correctly handled.' );
					}

					return false;
				}
			} elseif ( isset( $ipn_args['ipn_track_id'] ) ) {
				$track_id = $ipn_args['txn_type'] . '-' . $ipn_args['ipn_track_id'];
				if ( empty( $transaction_ids ) || ! in_array( $track_id, $transaction_ids ) ) {
					$transaction_ids[] = $track_id;
				} else {
					if ( $this->debug ) {
						$this->wclog->add( 'paypal', 's2 - Subscription IPN Error: IPN ' . $track_id . ' message has already been correctly handled.' );
					}

					return false;
				}
			}

			return $transaction_ids;

		}

		/**
		 * Process a PayPal Standard Subscription IPN request
		 *
		 * @param array $ipn_args Post data after wp_unslash
		 */
		protected function process_ipn_request( $ipn_args ) {

			WC_Gateway_Paypal::log( 'Paypal s2 - Paypal IPN Request Start' );

			// get subscription_id by paypal subscription id
			$subscription_id = $this->get_subscription_id( $ipn_args );

			if ( ! empty( $subscription_id ) ) {

				$subscription = new S2_Subscription( $subscription_id );

				$order_id = $subscription->order_id;
				$order    = wc_get_order( $order_id );

			} else {

				// check if the order has the same order_key
				$order_info = $this->get_order_info( $ipn_args );
				$order_id   = $order_info['order_id'];
				$order      = wc_get_order( $order_id );

				if ( $order->get_order_key() != $order_info['order_key'] ) {
					WC_Gateway_Paypal::log( 's2 - Order keys do not match' );

					return;
				}

				if ( isset( $ipn_args['subscr_id'] ) ) {
					$paypal_subscription_id = $ipn_args['subscr_id'];
				} else if ( isset( $ipn_args['recurring_payment_id'] ) ) {
					$paypal_subscription_id = $ipn_args['recurring_payment_id'];
				}

				// get the subscriptions of the order
				$subscriptions = get_post_meta( $order_id, 'subscriptions', true );
				WC_Gateway_Paypal::log( 'process_ipn_request - ' . print_r( $subscriptions, true ) );
				$subscription = new S2_Subscription( $subscriptions[0] );
				$subscription->set( '_paypal_subscription_id', $paypal_subscription_id );

			}

			// check subscription payment method
			if( $subscription->payment_method != 'paypal' ) {
				return;
			}

			// check if the transaction has been processed
			$transaction_ids = get_post_meta( $subscription_id, '_paypal_transaction_ids', true );
			$transactions    = $this->is_a_valid_transaction( $transaction_ids, $ipn_args );
			if ( $transactions ) {
				update_post_meta( $subscription_id, '_paypal_transaction_ids', $transactions );
			} else {
				WC_Gateway_Paypal::log( 's2 - Transaction ID already processed' );

				return;
			}

			$valid_order_statuses = array( 'on-hold', 'pending', 'failed', 'cancelled' );
			
			// if order exist update order status
			if ( $order && $subscription ) {

				switch ( $ipn_args['txn_type'] ) {
					case 'recurring_payment_profile_created':
					case 'subscr_signup':
						$this->save_paypal_data( $order, $ipn_args );

						$order->add_order_note( __( 'Paypal subscription id - ' . $subscription->_paypal_subscription_id, 's2-subscription' ) );

						S2_Subscription_Activity()->add_activity( $subscription_id, 'new', $order_id, __( 'Paypal subscription id - ' . $subscription->_paypal_subscription_id, 's2-subscription' ) );

						if( ! empty( $ipn_args['period_type'] ) && $ipn_args['period_type'] == 'Trial' ) {
							$subscription->change_status( 'trial', 'paypal-ipn' );
						}
						break;

					case 'recurring_payment_outstanding_payment':
					case 'recurring_payment':
					case 'subscr_payment':
						$this->save_paypal_data( $order, $ipn_args );

						isset( $ipn_args['txn_id'] ) && $subscription->set( '_paypal_latest_transaction_id', $ipn_args['txn_id'] );

						if ( ! empty( $ipn_args['outstanding_balance'] ) ) {
							$subscription->set( '_paypal_outstanding_balance', $ipn_args['outstanding_balance'] );
						}

						if ( 'completed' == strtolower( $ipn_args['payment_status'] ) ) {

							// if current subscription status is paused then execute resumed status functionality
							// if trial_payment is true then dont update status of subscription on first payment
							if( empty( $subscription->trial_payment ) ) {

								$subscription->set( '_paypal_paid_transaction_id', $ipn_args['txn_id'] );

								// save paid amount, so if subscription cancelled, api may use amount to refund to customer
								$subscription->set( '_paypal_paid_transaction_amount', $ipn_args['mc_gross'] );

								$subscription->set( 'completed_billing_cycle', $subscription->completed_billing_cycle + 1 );
								
								if( $subscription->status == 'paused' ) {
									$subscription->change_status( 'resumed', 'paypal-ipn' );
								} else {
									$subscription->change_status( 'active', 'paypal-ipn' );
								}

								$order->add_order_note( __( 'IPN paypal subscription - ' . $subscription->_paypal_subscription_id . ' payment completed (Payment ID: ' . $ipn_args['txn_id'] . ')', 's2-subscription' ) );

								S2_Subscription_Activity()->add_activity( $subscription_id, 'activated', $order_id, __( 'IPN paypal subscription - ' . $subscription->_paypal_subscription_id . ' payment completed (Payment ID: ' . $ipn_args['txn_id'] . ')', 's2-subscription' ) );

								if( $order->has_status( $valid_order_statuses ) ) {
									$order->payment_complete();
								}

							} else {

								// update trial_payment so on receive next payment will update subscription, order status 
								$subscription->set( 'trial_payment', false );

								$order->add_order_note( __( 'IPN paypal subscription - ' . $subscription->_paypal_subscription_id . ' trial / signup fee payment completed (Payment ID: ' . $ipn_args['txn_id'] . ')', 's2-subscription' ) );

								S2_Subscription_Activity()->add_activity( $subscription_id, 'activated', $order_id, __( 'IPN paypal subscription - ' . $subscription->_paypal_subscription_id . ' trial / signup fee payment completed (Payment ID: ' . $ipn_args['txn_id'] . ')', 's2-subscription' ) );

							}

						}
						break;

					case 'recurring_payment_suspended':
						$subscription->change_status( 'paused', 'paypal-ipn' );
						break;

					case 'subscr_modify':
						break;

					case 'recurring_payment_expired':
					case 'subscr_eot':
						// $subscription->change_status( 'expired', 'paypal-ipn' );
						break;

					case 'subscr_failed':
					case 'recurring_payment_skipped':
					case 'recurring_payment_suspended_due_to_max_failed_payment':
						$subscription->change_status( 'suspended', 'paypal-ipn' );
						break;

					case 'recurring_payment_profile_cancel':
					case 'subscr_cancel':
						$subscription->change_status( 'cancel-now', 'paypal-ipn' );
						break;

					default:
				}

			}

		}

	}

}
