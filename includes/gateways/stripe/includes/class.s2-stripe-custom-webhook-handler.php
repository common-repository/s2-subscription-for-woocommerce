<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Stripe Custom Webhook Handler
 *
 * @class   S2_Stripe_Custom_Webhook_Handler
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
class S2_Stripe_Custom_Webhook_Handler extends WC_Stripe_Webhook_Handler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Check incoming requests for Stripe Webhook data and process them.
	 */
	public function check_for_subscription_webhook() {
		if ( ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) || ! isset( $_GET['wc-api'] ) || ( 'wc_stripe' !== $_GET['wc-api'] ) ) {
			return;
		}

		$request_body    = file_get_contents( 'php://input' );
		$request_headers = array_change_key_case( $this->get_request_headers(), CASE_UPPER );

		// Validate it to make sure it is legit.
		if ( $this->is_valid_request( $request_headers, $request_body ) ) {
			$request_body = json_decode( $request_body );
			$this->process_subscription_webhook( $request_body );
		} else {
			status_header( 400 );
			exit;
		}
	}

	/**
	 * Checks a set of args and derives an Order ID
	 *
	 * @param array $args Post data.
	 */
	public function get_order_id( $ipn_args ) {
		$order_id = '';

		if ( isset( $ipn_args->data->object->id ) ) {

			if ( $ipn_args->type == 'invoice.payment_succeeded' || $ipn_args->type == 'invoice.created' || $ipn_args->type == 'invoice.payment_action_required' ) {
				$subscription_id = $ipn_args->data->object->subscription;
			} else {
				$subscription_id = $ipn_args->data->object->id;
			}

		} else {

			$subscription_id = '';

		}

		// First try and get the order ID by the stripe subscription ID
		if ( ! empty( $subscription_id ) ) {

			$posts = get_posts( array(
				'numberposts'      => 1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'post_type'        => 'shop_order',
				'post_status'      => 'any',
				'suppress_filters' => true,
				'meta_key'         => '_stripe_subscription_id',
				// 'meta_key'         => '_stripe_subscription_schedule_id',
				'meta_value'       => $subscription_id,
				'meta_compare'     => 'LIKE'
			) );

			if ( ! empty( $posts ) ) {
				$order_id = $posts[0]->ID;
			}
		}

		return $order_id;
	}

	/**
	 * Checks a set of args and derives an subscription ID
	 *
	 * @param array $args Post data.
	 */
	public function get_subscription_id( $ipn_args ) {
		$subscription_id = $stripe_subscription_id = '';

		if ( isset( $ipn_args->data->object->id ) ) {

			if ( $ipn_args->type == 'invoice.payment_succeeded' || $ipn_args->type == 'invoice.created' || $ipn_args->type == 'invoice.payment_action_required' || $ipn_args->type == 'invoice.finalized' ) {
				$stripe_subscription_id = $ipn_args->data->object->subscription;
			} else {
				$stripe_subscription_id = $ipn_args->data->object->id;
			}

		}

		// First try and get the subscription ID by the stripe subscription ID
		if ( ! empty( $stripe_subscription_id ) ) {

			$posts = get_posts( array(
				'numberposts'      => 1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'post_type'        => 's2_subscription',
				'post_status'      => 'any',
				'suppress_filters' => true,
				'meta_key'         => '_stripe_subscription_id',
				// 'meta_key'         => '_stripe_subscription_schedule_id',
				'meta_value'       => $stripe_subscription_id,
				'meta_compare'     => 'LIKE'
			) );

			if ( ! empty( $posts ) ) {
				$subscription_id = $posts[0]->ID;
			}
		}

		return $subscription_id;
	}

	/**
	 * Checks a set of args and derives an subscription ID
	 *
	 * @param array $args Post data.
	 */
	public function get_subscription_id_by_invoice_id( $ipn_args ) {
		$subscription_id = $invoice_id = '';

		if ( isset( $ipn_args->data->object->invoice ) ) {

			$invoice_id = $ipn_args->data->object->invoice;

		}

		// First try and get the subscription ID by the stripe subscription ID
		if ( ! empty( $invoice_id ) ) {

			$posts = get_posts( array(
				'numberposts'      => 1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'post_type'        => 's2_subscription',
				'post_status'      => 'any',
				'suppress_filters' => true,
				'meta_key'         => '_stripe_latest_invoice',
				// 'meta_key'         => '_stripe_subscription_schedule_id',
				'meta_value'       => $invoice_id,
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
		if ( isset( $args->data->object->metadata ) ) {
			$order_info = $args->data->object->metadata;
		}

		return $order_info;
	}

	/**
	 * check if the webhook request as been processed
	 *
	 * @param string $transaction_ids .
	 * @param array $args Post data.
	 */
	protected function is_a_valid_transaction( $transaction_ids, $ipn_args ) {

		$transaction_ids = empty( $transaction_ids ) ? array() : $transaction_ids;

		if ( isset( $ipn_args->id ) ) {
			$transaction_id = $ipn_args->id . '-' . $ipn_args->type;

			if ( isset( $ipn_args->paid ) ) {
				$transaction_id .= '-' . $ipn_args->paid;
			}

			if ( empty( $transaction_ids ) || ! in_array( $transaction_id, $transaction_ids ) ) {
				$transaction_ids[] = $transaction_id;
			} else {
				WC_Stripe_Logger::log( 'stripe - s2 - Subscription IPN Error: IPN ' . $transaction_id . ' message has already been correctly handled.' );
				return false;
			}
		}

		return $transaction_ids;

	}

	/**
	 * Processes the incoming webhook.
	 *
	 * @param string $request_body
	 */
	public function process_subscription_webhook( $ipn_args ) {

		// get subscription_id by stripe subscription id
		if ( $ipn_args->type == 'charge.failed' ) {
			$subscription_id = $this->get_subscription_id_by_invoice_id( $ipn_args );
		} else {
			$subscription_id = $this->get_subscription_id( $ipn_args );
		}

		if ( empty( $subscription_id ) ) {
			return;
		} else {
			$subscription = new S2_Subscription( $subscription_id );
		}

		$transaction_ids = get_post_meta( $subscription_id, '_stripe_transaction_ids', true );
		$transactions    = $this->is_a_valid_transaction( $transaction_ids, $ipn_args );
		if ( $transactions ) {
			update_post_meta( $subscription_id, '_stripe_transaction_ids', $transactions );
		} else {
			return;
		}

		// get order
		$order_id = $subscription->order_id;
		$order    = wc_get_order( $order_id );

		// save ipn event id in options table to use it in cron to fetch ending_before events
		update_option( '_stripe_last_transation_id', $ipn_args->id );

		$valid_order_statuses = array( 'on-hold', 'pending', 'failed', 'cancelled' );

		// if order exist update order status
		if ( $order && $subscription ) {

			switch ( $ipn_args->type ) {

				case 'customer.subscription.created':
					$order->add_order_note( __( 'Stripe subscription id - ' . $ipn_args->data->object->id, 's2-subscription' ) );

					S2_Subscription_Activity()->add_activity( $subscription_id, 'new', $order_id, __( 'Stripe subscription id - ' . $ipn_args->data->object->id, 's2-subscription' ) );

					// save subscription item id, which will be use for upgrade or downgrade subscription
					$subscription->set( '_stripe_subscription_item_id', $ipn_args->data->object->items->data[0]->id );

					$new_status = '';
					if ( $ipn_args->data->object->status == 'incomplete' ) { // if immediate payment failed stripe send status as incomplete
						$new_status = "suspended";
					} else if ( $ipn_args->data->object->status == 'trialing' ) { // if trial stripe send status as trialing
						$new_status = "trial";
					}

					switch ( $new_status ) {

						case 'suspended':
							$subscription->change_status( $new_status, 'stripe-ipn' );
							break;
						case 'trial':
							$subscription->change_status( $new_status, 'stripe-ipn' );
							break;

					}
					break;

				case 'customer.subscription.updated':
					// foreach ( $subscriptions as $subscription_id ) {

					// $subscription = new S2_Subscription( $subscription_id );

					// if( $ipn_args->data->object->id != $subscription->_stripe_subscription_id ) continue;

					// if $ipn_args->data->object->pause_collection is null then subscription is resumed otherwise paused

					$new_status = "";

					if ( $ipn_args->data->object->status == 'active' ) {// if current status active then check pause, resume subscription

						$new_status = "active";
						if ( ! empty( $ipn_args->data->object->pause_collection ) ) {

							$new_status = "paused";

						} else if ( empty( $ipn_args->data->object->pause_collection ) && ! empty( $ipn_args->data->previous_attributes->pause_collection ) ) {// check previous_attributes from ipn with current changed value

							$new_status = "resumed";

						}

					} else if ( $ipn_args->data->object->status == 'past_due' ) {

						$new_status = "overdue";

					} else if ( $ipn_args->data->object->status == 'incomplete' || $ipn_args->data->object->status == 'incomplete_expired' || $ipn_args->data->object->status == 'unpaid' ) {

						$new_status = "suspended";

					}

					switch ( $new_status ) {

						/*case 'active':
							$subscription->change_status( $new_status, 'stripe-ipn' );
							break;*/
						case 'paused':
							$subscription->change_status( $new_status, 'stripe-ipn' );
							break;
						case 'resumed':
							$subscription->change_status( $new_status, 'stripe-ipn' );
							break;
						case 'overdue':
							$subscription->change_status( $new_status, 'stripe-ipn' );
							break;
						case 'suspended':
							$subscription->change_status( $new_status, 'stripe-ipn' );
							break;

					}

					// }
					break;

				case 'customer.subscription.deleted':
					// foreach ( $subscriptions as $subscription_id ) {

					// $subscription = new S2_Subscription( $subscription_id );

					// if( $ipn_args->data->object->id != $subscription->_stripe_subscription_id ) continue;

					$current_time = strtotime( 'now' );
					if ( $current_time >= $subscription->expired_date ) {
						$subscription->change_status( 'expired', 'stripe-ipn' );
					} else {
						$subscription->change_status( 'cancel-now', 'stripe-ipn' );
					}

					// }
					break;

				case 'invoice.created':
					if ( $ipn_args->data->object->amount_due > 0 ) { // stripe create invoice with 0 amount for trial subscription

						$subscription->set( '_stripe_latest_invoice', $ipn_args->data->object->id );

					}
					break;

				// stripe always send invoice.finalized webhook after stripe is invoice finalized then immediately send invoice.payment_succeeded(if active) / invoice.marked_uncollectible(if paused)
				// but sometime stripe makes invoice is paid with 0 amount even it is paused and it wont send invoice.marked_uncollectible
				// so check status is paused / active on receiving webhook, if it is paused increase payment_due_date and expired_date using selected subscription's billing_frequency / payment type to get all remaining billing cycle payment after subsription resumed / active
				case 'invoice.finalized':
				
					if( $subscription->status == 'paused' ) {

						$payment_due_date = $expired_date = '';
						// check which payment type
						if ( $subscription->payment_type == 'subscription' ) {

							$billing_frequency_meta = $subscription->billing_frequency;
							$subscription_option    = s2_get_billing_frequency_options( $billing_frequency_meta );
							
							$price_is_per      = $subscription_option['period'];
							$price_time_option = $subscription_option['time'];

			                $payment_due_date = strtotime( "+".$price_is_per.' '.$price_time_option, $subscription->payment_due_date );
			                $expired_date = strtotime( "+".$price_is_per.' '.$price_time_option, $subscription->expired_date );

						} else if ( $subscription->payment_type == 'one_time_fee' ) {

			                $payment_due_date = strtotime( '+1 day', $subscription->payment_due_date );
			                $expired_date = strtotime( '+1 day', $subscription->expired_date );

						} else if ( $subscription->payment_type == 'split_pay' ) {

			                $payment_due_date = strtotime( '+1 Month', $subscription->payment_due_date );
			                $expired_date = strtotime( '+1 Month', $subscription->expired_date );

						}

						$subscription->set( 'payment_due_date', $payment_due_date );
						$subscription->set( 'expired_date', $expired_date );
					
					}
					break;

				case 'invoice.payment_succeeded':
					if ( $subscription->_stripe_paid_invoice != $ipn_args->data->object->id && $ipn_args->data->object->status == 'paid' ) {
						// stripe create paid invoice with 0 amount for trial subscription

						// if trial_payment is true then dont update status of subscription on first payment
						// sometime stripe does not charge invoice and add that amount in next invoice so, check status of subscription in stripe if it is not in trial then update payment status

						// if( empty( $subscription->trial_payment ) || $stripe_subscription->status != 'trialing' ) {
						if( empty( $subscription->trial_payment ) ) {

							$subscription->set( '_stripe_paid_invoice', $ipn_args->data->object->id );
							
							if( $ipn_args->data->object->payment_intent ) $subscription->set( '_stripe_paid_payment_intent', $ipn_args->data->object->payment_intent );
							if( $ipn_args->data->object->charge ) $subscription->set( '_stripe_paid_payment_charge', $ipn_args->data->object->charge );

							// save paid amount, so if subscription cancelled, api may use amount to refund to customer
							$subscription->set( '_stripe_paid_payment_amount', $ipn_args->data->object->amount_paid );

							$subscription->set( 'completed_billing_cycle', $subscription->completed_billing_cycle + 1 );

							$subscription->change_status( 'active', 'stripe-ipn' );

							// sometime stripe does not charge invoice and add that amount in next invoice so amount_paid is 0 in invoice
							if( $ipn_args->data->object->amount_paid > 0 ) {
								
								$order->add_order_note( __( 'IPN stripe subscription - ' . $ipn_args->data->object->subscription . ' payment completed (Charge ID: ' . $ipn_args->data->object->charge . ')', 's2-subscription' ) );

								S2_Subscription_Activity()->add_activity( $subscription_id, 'activated', $order_id, sprintf( __( 'IPN stripe subscription - payment completed (Charge ID: ' . $ipn_args->data->object->charge . ')', 's2-subscription' ), 'stripe-ipn' ) );
							
							}

							if( $order->has_status( $valid_order_statuses ) ) {
								$order->payment_complete();
							}

							// }
						} else {
							// update trial_payment so on receive next payment will update subscription, order status 
							$subscription->set( 'trial_payment', false );

							// sometime stripe does not charge invoice and add that amount in next invoice so amount_paid is 0 in invoice
							if( $ipn_args->data->object->amount_paid > 0 ) {

								$order->add_order_note( __( 'IPN stripe subscription - ' . $ipn_args->data->object->subscription . ' trial / signup fee payment completed (Charge ID: ' . $ipn_args->data->object->charge . ')', 's2-subscription' ) );

								S2_Subscription_Activity()->add_activity( $subscription_id, 'activated', $order_id, sprintf( __( 'IPN stripe subscription - trial / signup fee payment completed (Charge ID: ' . $ipn_args->data->object->charge . ')', 's2-subscription' ), 'stripe-ipn' ) );
							}

						}

					}
					break;

				case 'charge.failed':
					$order->add_order_note( __( 'IPN stripe subscription - ' . $subscription->_stripe_subscription_id . ' - ' . $ipn_args->data->object->failure_code ) );

					S2_Subscription_Activity()->add_activity( $subscription_id, 'overdue', $order_id, __( 'IPN stripe subscription - ' . $ipn_args->data->object->failure_code ) );
					break;

			}

		}

	}

}
