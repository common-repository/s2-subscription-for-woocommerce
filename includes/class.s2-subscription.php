<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements features of S2 Subscription
 *
 * @class   S2_Subscription
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription' ) ) {

	class S2_Subscription {

		/**
		 * The subscription (post) ID.
		 *
		 * @var int
		 */
		public $id = 0;

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		public function __construct( $subscription_id = 0, $args = [] ) {
			// initialize the subscription if $subscription_id is defined
			if ( $subscription_id ) {
				$this->set( 'id', $subscription_id );
				$this->populate();
			}

			// create a new subscription if $args is passed
			if ( $subscription_id == '' && ! empty( $args ) ) {
				$this->add_subscription( $args );
			}
		}

		/**
		 * Returns the unique ID for this object.
		 *
		 * @return int
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * set function.
		 *
		 * @param string $property
		 * @param mixed $value
		 *
		 * @return bool|int
		 */
		public function set( $property, $value ) {
			$this->$property = $value;

			return update_post_meta( $this->id, $property, $value );
		}

		/**
		 * Get function.
		 *
		 * @param string $prop
		 * @param string $context change this string if you want the value stored in database
		 *
		 * @return mixed
		 */
		public function get( $prop, $context = 'view' ) {

			$value = '';
			if( ! empty( $this->$prop ) ) $value = $this->$prop;

			if ( 'view' === $context ) {
				// APPLY_FILTER : s2_subscription_{$key}: filtering the post meta of a subscription
				$value = apply_filters( 's2_subscription_' . $prop, $value, $this );
			}

			return $value;
		}

		/**
		 * Populate the subscription
		 *
		 * @return void
		 */
		public function populate() {
			foreach ( $this->get_subscription_meta() as $key => $value ) {
				$this->set( $key, $value );
			}

			do_action( 's2_subscription_loaded', $this );
		}

		/**
		 * Add new subscription.
		 *
		 * @param array $args
		 *
		 * @return void
		 */
		public function add_subscription( $args ) {

			$subscription_id = wp_insert_post( [
					'post_status' => 'publish',
					'post_type'   => 's2_subscription',
				] );

			if ( $subscription_id ) {

				//update subscription post title
				$post_array = [
					'ID'         => $subscription_id,
					'post_title' => '#' . $subscription_id,
				];
				wp_update_post( $post_array );

				$this->set( 'id', $subscription_id );
				$this->set( 'status', 'pending' );
				// APPLY_FILTER: s2_add_subscription_args : to filter the meta data of a subscription before the creation
				$meta = apply_filters( 's2_add_subscription_args', wp_parse_args( $args, $this->get_default_meta_data() ), $this );
				$this->update_subscription_meta( $meta );

				S2_Subscription_Activity()->add_activity( $subscription_id, 'new', $this->get( 'order_id' ), __( 'Subscription successfully created.', 's2-subscription' ) );
			}
		}

		/**
		 * Update post meta in subscription
		 *
		 * @param array $meta
		 *
		 * @return void
		 */
		function update_subscription_meta( $meta ) {
			foreach ( $meta as $key => $value ) {
				$this->set( $key, $value );
			}
		}

		/**
		 * Fill the default metadata with the post meta stored in db
		 *
		 * @return array
		 */
		function get_subscription_meta() {
			$subscription_meta = [];
			foreach ( $this->get_default_meta_data() as $key => $value ) {
				$subscription_meta[ $key ] = get_post_meta( $this->id, $key, true );
			}

			return $subscription_meta;
		}

		/**
		 * Return an array of all custom fields subscription
		 *
		 * @return array
		 */
		private function get_default_meta_data() {
			$subscription_meta_data = [
				'status'                          => 'pending',
				'start_date'                      => '',
				'payment_due_date'                => '',
				'expired_date'                    => '',
				'cancelled_date'                  => '',
				'end_date'                        => '',
				// pauses
				'num_of_pauses'                   => 0,
				'date_of_pauses'                  => [],
				'expired_pause_date'              => '',
				'sum_of_pauses'                   => '',
				// product
				'product_id'                      => '',
				'product_price'                   => '',
				'variation_id'                    => '',
				'variation'                       => '',
				'product_name'                    => '',
				'quantity'                        => '',
				'line_subtotal'                   => '',
				'line_total'                      => '',
				'line_subtotal_tax'               => '',
				'line_tax'                        => '',
				'line_tax_data'                   => '',
				'cart_discount'                   => '',
				'cart_discount_tax'               => '',
				'coupons'                         => '',
				'order_total'                     => '',
				'order_subtotal'                  => '',
				'order_tax'                       => '',
				'order_discount'                  => '',
				'order_shipping'                  => '',
				'order_shipping_tax'              => '',
				'order_currency'                  => '',
				'renew_order'                     => 0,
				'prices_include_tax'              => '',
				'payment_method'                  => '',
				'payment_method_title'            => '',
				'transaction_id'                  => '',
				'subscriptions_shippings'         => '',
				'subscription_total'              => '',
				'price_is_per'                    => '',
				'price_time_option'               => '',
				'trial_per'                       => '',
				'trial_time_option'               => '',
				'num_of_rates'                    => '',
				'rates_payed'                     => '',
				'order_ids'                       => [],
				'order_id'                        => '',
				'order_item_id'                   => '',
				'user_id'                         => 0,
				'customer_ip_address'             => '',
				'customer_user_agent'             => '',
				'trial_payment'					  => '', // used to check first ipn payment is trial / signup fee ipn
				//item subscription detail
				'payment_type'                    => '',
				'split_payment'                   => '',
				'billing_frequency'               => '',
				'max_length'                      => '',
				'total_billing_cycle'			  => '',
				'completed_billing_cycle' 		  => 0,
				'trial_period'                    => '',
				'sign_up_fee'                     => '',
				'sign_up_fee_tax'                 => '',
				//paypal subscription detail
				'_paypal_subscription_id'         => '',
				'_paypal_billing_agreement_id'    => '',
				'_paypal_latest_transaction_id'   => '',
				'_paypal_outstanding_balance'     => '',
				'_paypal_paid_transaction_id'     => '',
				'_paypal_paid_transaction_amount' => '',
				'_paypal_refund_reason'           => '',
				'_paypal_refund_amount'           => '',
				//stripe subscription detail
				'_stripe_subscription_id'         => '',
				'_stripe_subscription_item_id'    => '',
				'_stripe_product_id'              => '',
				'_stripe_plan_id'                 => '',
				'_stripe_plan_price'              => '',
				'_stripe_latest_invoice'          => '',
				'_stripe_paid_invoice'            => '',
				'_stripe_paid_payment_intent'     => '',
				'_stripe_paid_payment_charge'     => '',
				'_stripe_paid_payment_amount'     => '',
				'_stripe_refund_reason'           => '',
				'_stripe_refund_amount'           => '',
			];

			return $subscription_meta_data;
		}

		/**
		 * Get method of payment
		 *
		 * @return mixed|string
		 */
		public function get_payment_method() {
			return apply_filters( 's2_get_payment_method', $this->payment_method, $this );
		}

		/**
		 * Check the gateway available
		 *
		 * @param $subscription S2_Subscription
		 *
		 * @return WC_Payment_Gateway|bool
		 */
		public function check_payment_gateway_available() {

			$payment_method = $this->get_payment_method();

			if ( empty( $payment_method ) ) {
				return false;
			}

			if ( WC()->payment_gateways() ) {
				$payment_gateways = WC()->payment_gateways()->payment_gateways();
			} else {
				$payment_gateways = [];
			}

			return isset( $payment_gateways[ $payment_method ] ) ? $payment_gateways[ $payment_method ] : false;
		}

		/**
		 * Return the product meta of a variation product.
		 *
		 * @param array $attributes
		 * @param bool $echo
		 *
		 * @return string
		 */
		function get_product_meta( $attributes = [], $echo = true ) {

			$item_data = [];

			if ( ! empty( $this->variation_id ) ) {
				$variation = wc_get_product( $this->variation_id );

				if ( empty( $attributes ) ) {
					$attributes = $variation->get_attributes();
				}

				foreach ( $attributes as $name => $value ) {
					if ( '' === $value ) {
						continue;
					}

					$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

					// If this is a term slug, get the term's nice name
					if ( taxonomy_exists( $taxonomy ) ) {
						$term = get_term_by( 'slug', $value, $taxonomy );
						if ( ! is_wp_error( $term ) && $term && $term->name ) {
							$value = $term->name;
						}
						$label = wc_attribute_label( $taxonomy );

					} else {
						if ( strpos( $name, 'attribute_' ) !== false ) {
							$custom_att = str_replace( 'attribute_', '', $name );
							if ( $custom_att != '' ) {
								$label = wc_attribute_label( $custom_att );
							} else {
								$label = apply_filters( 'woocommerce_attribute_label', wc_attribute_label( $name ), $name );
							}
						}
					}

					$item_data[] = [
						'key'   => $label,
						'value' => $value,
					];
				}
			}

			// APPLY_FILTER: s2_item_data: the meta data of a variation product can be filtered : S2_Subscription is passed as argument
			$item_data = apply_filters( 's2_item_data', $item_data, $this );
			$out       = '';
			// Output flat or in list format
			if ( sizeof( $item_data ) > 0 ) {
				foreach ( $item_data as $data ) {
					if ( $echo ) {
						echo esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . "\n";
					} else {
						$out .= ' - ' . esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . ' ';
					}
				}
			}

			return $out;

		}

		/**
		 * Get subscription customer billing or shipping fields.
		 *
		 * @param string $type
		 * @param boolean $no_type
		 *
		 * @return array
		 */
		public function get_address_fields( $type = 'billing', $no_type = false, $prefix = '' ) {

			$indentation = '--------';
			$message     = $indentation . 'Check for ' . $type;
			// s2_subscription_log( $message, 'subscription_payment' );

			$fields         = [];
			$value_to_check = $this->get( '_' . $type . '_first_name' );

			if ( empty( $value_to_check ) || apply_filters( 's2_subscription_get_address_by_order', false ) ) {
				$fields = $this->get_address_fields_from_order( $type, $no_type, $prefix );
			} else {
				$meta_fields = s2_get_order_fields_to_edit( $type );
				$order       = $this->get_order();
				if ( $order instanceof WC_Order ) {
					$meta_fields = $order->get_address( $type );
				}

				$message = $indentation . ' Get the information from subscription #' . $this->get_id() . ' with user ' . $this->user_id . '( Order customer: ' . $order->get_user_id() . ' )';
				// s2_subscription_log( $message, 'subscription_payment' );

				foreach ( $meta_fields as $key => $value ) {
					$field_key = $no_type ? $key : $type . '_' . $key;

					$fields[ $prefix . $field_key ] = $this->get( '_' . $type . '_' . $key );
					$message                        = $indentation . $indentation . $fields[ $prefix . $field_key ] . ' ' . $this->get( $field_key );
					// s2_subscription_log( $message, 'subscription_payment' );
				}
			}

			return $fields;
		}

		/**
		 * Return the fields billing or shipping of the parent order
		 *
		 * @param string $type
		 * @param bool $no_type
		 *
		 * @return array
		 */
		public function get_address_fields_from_order( $type = 'billing', $no_type = false, $prefix = '' ) {
			$fields = [];
			$order  = $this->get_order();

			if ( ! $order ) {
				return $fields;
			}

			if ( $order ) {
				$meta_fields = $order->get_address( $type );

				if ( is_array( $meta_fields ) ) {
					foreach ( $meta_fields as $key => $value ) {
						$field_key                      = $no_type ? $key : $type . '_' . $key;
						$fields[ $prefix . $field_key ] = $value;
					}
				}
			}

			return $fields;
		}

		/**
		 * Get the order object.
		 *
		 * @return
		 */
		public function get_order() {
			$this->order = ! empty( $this->order ) ? $this->order : wc_get_order( $this->get( 'order_id' ) );

			return $this->order;
		}

		/**
		 * Return the subscription recurring price formatted
		 *
		 * @param string $tax_display
		 *
		 * @return  string
		 */
		public function get_formatted_recurring( $tax_display = '', $show_time_option = true ) {

			$tax_inc = get_option( 'woocommerce_prices_include_tax' ) === 'yes';

			if ( wc_tax_enabled() && $tax_inc ) {
				$sbs_price = $this->get( 'line_total' ) + $this->get( 'line_tax' );
			} else {
				$sbs_price = $this->get( 'line_total' );
			}

			$payment_type = $this->get( 'payment_type' );

			$subscription_option = [];
			if ( $payment_type == 'subscription' ) {// if payment type is subscription then get billing frequency option

				$billing_frequency   = $this->get( 'billing_frequency' );
				$subscription_option = s2_get_billing_frequency_options( $billing_frequency );
				$price_is_per        = $subscription_option['period'];

			} else if ( $payment_type == 'split_pay' ) {// if payment type is split payment then get split payment option

				$split_payment       = $this->get( 'split_payment' );
				$subscription_option = s2_get_split_payment_options( $split_payment );
				$price_is_per        = 1;
				// $sbs_price				= $sbs_price / $subscription_option['period'];

			} else if ( $payment_type == 'one_time_fee' ) {// if payment type is split payment then get split payment option

				$show_time_option = false;

			}

			$price_time_option = $subscription_option['time'];

			$price_time_option_string = s2_get_price_per_string( $price_is_per, $price_time_option );

			$recurring = wc_price( $sbs_price, [ 'currency' => $this->get( 'order_currency' ) ] );
			$recurring .= $show_time_option ? ' / ' . $price_time_option_string : '';

			return apply_filters( 's2-recurring-price', $recurring, $this );

		}

		/**
		 * Return the customer order note of subscription or parent order.
		 *
		 * @return mixed
		 */
		public function get_customer_order_note() {
			$order         = wc_get_order( $this->order_id );

			if( ! empty( $customer_note ) ) $customer_note = $this->customer_note;
			
			if ( $order && empty( $customer_note ) ) {
				$customer_note = $order->get_customer_note();
			}

			return $customer_note;
		}

		/**
		 * Get billing customer email
		 *
		 * @return string
		 */
		public function get_billing_email() {
			$billing_email = ! empty( $this->billing_email ) ? $this->billing_email : get_post_meta( $this->order_id, '_billing_email', true );

			return $billing_email;
		}

		/**
		 * Get billing customer phone
		 *
		 * @return string
		 */
		public function get_billing_phone() {
			$billing_phone = ! empty( $this->billing_phone ) ? $this->billing_phone : get_post_meta( $this->order_id, 'billing_phone', true );

			return $billing_phone;
		}

		/**
		 * Change the total amount meta on a subscription after a change without
		 * recalculate taxes.
		 */
		public function calculate_totals_from_changes() {
			$changes = [];

			$changes['order_subtotal']     = floatval( $this->get( 'line_total' ) ) + floatval( $this->get( 'line_tax' ) );
			$changes['subscription_total'] = floatval( $this->get( 'order_shipping' ) ) + floatval( $this->get( 'order_shipping_tax' ) ) + $changes['order_subtotal'];
			$changes['order_total']        = $changes['subscription_total'];
			$changes['line_subtotal']      = round( floatval( $this->get( 'line_total' ) ) / $this->get( 'quantity' ), wc_get_price_decimals() );

			$changes['line_subtotal_tax'] = round( floatval( $this->get( 'line_tax' ) ) / $this->get( 'quantity' ), wc_get_price_decimals() );

			$changes['line_tax_data'] = [
				'subtotal' => [ $changes['line_subtotal_tax'] ],
				'total'    => [ $this->get( 'line_tax' ) ],
			];

			$this->update_subscription_meta( $changes );
		}

		/**
		 * Change the status of subscription manually
		 *
		 * @param string $new_status
		 * @param string $from ( user_role )
		 */
		public function change_status( $new_status, $from = '' ) {
			$order = wc_get_order( $this->order_id );

			$gateway = $this->check_payment_gateway_available();
			if ( ! $gateway ) {
				add_action( 'admin_notices', function () {
					// echo '<div class="error"><p><strong>Payment gateway ' . $gateway . ' not available.</strong></p></div>';
				} );

				return;
			}

			switch ( $new_status ) {
				case 'payment-received':
					$payment_due_date = $this->get_next_payment_due_date();
					$this->set( 'payment_due_date', $payment_due_date );

					$this->set( 'status', 'active' );

					S2_Subscription_Activity()->add_activity( $this->id, 'payment-received', $this->order_id, sprintf( __( 'The subscription payment received. %s', 's2-subscription' ), $from ) );
					break;

				case 'trial':
					if ( $this->status == 'trial' ) {
						return;
					}

					$this->set( 'status', $new_status );

					S2_Subscription_Activity()->add_activity( $this->id, 'trial', $this->order_id, sprintf( __( 'The subscription is in trial period. %s', 's2-subscription' ), $from ) );
					break;

				case 'active':
					// change payment_due_date
					// update payment_due_date only if previous invoice is paid
					if( ( $gateway->id == 'stripe' && $this->_stripe_paid_invoice ) 
						|| ( ( $gateway->id == 's2-paypal-ec' || $gateway->id == 'paypal' ) && $this->_paypal_paid_transaction_id ) ) {
						
						$payment_due_date = $this->get_next_payment_due_date();
						$this->set( 'payment_due_date', $payment_due_date );
					
					}

					if ( $this->status == 'active' ) {
						return;
					}

					$this->set( 'status', $new_status );

					S2_Subscription_Activity()->add_activity( $this->id, 'activated', $this->order_id, sprintf( __( 'The subscription has been active. %s', 's2-subscription' ), $from ) );

					do_action( 's2_customer_subscription_activated_mail', $this );
					break;

				case 'overdue':
					if ( $this->status == 'overdue' ) {
						return;
					}

					// Update the subscription status
					$this->set( 'status', $new_status );

					S2_Subscription_Activity()->add_activity( $this->id, 'overdue', $this->order_id, sprintf( __( 'The subscription has been overdue. Last payment not received. %s', 's2-subscription' ), $from ) );

					do_action( 's2_customer_subscription_overdue_mail', $this );
					break;

				case 'suspended':
					if ( $this->status == 'suspended' ) {
						return;
					}

					// Update the subscription status
					$this->set( 'status', $new_status );

					S2_Subscription_Activity()->add_activity( $this->id, 'suspended', $this->order_id, sprintf( __( 'The subscription has been suspended. %s', 's2-subscription' ), $from ) );

					do_action( 's2_customer_subscription_suspended_mail', $this );
					break;

				case 'expired':
					if ( $this->status == 'expired' ) {
						return;
					}

					$this->set( 'end_date', $this->expired_date );
					$this->set( 'payment_due_date', '' );
					$this->set( 'status', $new_status );

					S2_Subscription_Activity()->add_activity( $this->id, 'expired', $this->order_id, sprintf( __( 'The subscription has been expired. %s', 's2-subscription' ), $from ) );

					do_action( 's2_customer_subscription_expired_mail', $this );
					break;

				case 'cancelled':
					if ( $this->status == 'cancelled' ) {
						return;
					}

					// if the subscription is cancelled the payment_due_date become the expired_date
					// the subscription will be cancelled immediately and next payment will be collected, if invoice is already created / draft by gateway

					$this->set( 'end_date', $this->payment_due_date );
					$this->set( 'payment_due_date', '' );
					$this->set( 'cancelled_date', strtotime( 'now' ) );

					if ( $gateway->id == 'stripe' && $from != 'stripe-ipn' ) {

						$gateway->cancel_stripe_subscription( $this, 'cancelled' );
					
					} else if( ( $gateway->id == 's2-paypal-ec' || $gateway->id == 'paypal' ) && $from != 'paypal-ipn' ) {

						$gateway->cancel_paypal_subscription( $this, 'cancelled' );

					}

					$this->set( 'status', $new_status );

					S2_Subscription_Activity()->add_activity( $this->id, 'cancelled', $this->order_id, sprintf( __( 'The subscription has been cancelled. %s', 's2-subscription' ), $from ) );

					do_action( 's2_customer_subscription_cancelled_mail', $this );
					break;

				case 'cancel-now':
					if ( $this->status == 'cancelled' ) {
						return;
					}

					// if the subscription is cancelled now the end_date is the current timestamp
					// the subscription will be cancelled immediately and next payment will not be collected

					$new_status   = 'cancelled';
					$current_time = strtotime( 'now' );
					$this->set( 'end_date', $current_time );
					$this->set( 'payment_due_date', '' );
					$this->set( 'cancelled_date', $current_time );

					if ( $gateway->id == 'stripe' && $from != 'stripe-ipn' ) {

						$gateway->cancel_stripe_subscription( $this, 'cancel-now' );
					
					} else if( ( $gateway->id == 's2-paypal-ec' || $gateway->id == 'paypal' ) && $from != 'paypal-ipn' ) {

						$gateway->cancel_paypal_subscription( $this, 'cancel-now' );

					}

					$this->set( 'status', $new_status );

					S2_Subscription_Activity()->add_activity( $this->id, 'cancelled', $this->order_id, sprintf( __( 'The subscription has been NOW cancelled. %s', 's2-subscription' ), $from ) );

					do_action( 's2_customer_subscription_cancelled_mail', $this );
					break;

				case 'cancel-with-refund':
					if ( $this->status == 'cancelled' ) {
						return;
					}

					// if the subscription is cancelled now the end_date is the current timestamp
					// the subscription will be cancelled immediately and last invoice amount refunded to customer

					$new_status   = 'cancelled';
					$current_time = strtotime( 'now' );
					$this->set( 'end_date', $current_time );
					$this->set( 'payment_due_date', '' );
					$this->set( 'cancelled_date', $current_time );

					if ( $gateway->id == 'stripe' && $from != 'stripe-ipn' ) {

						$this->set( '_stripe_refund_reason', 'requested_by_customer' );
						$this->set( '_stripe_refund_amount', $this->_stripe_paid_payment_amount );

						$gateway->cancel_stripe_subscription( $this, 'cancel-with-refund' );
					
					} else if( ( $gateway->id == 's2-paypal-ec' || $gateway->id == 'paypal' ) && $from != 'paypal-ipn' ) {

						$this->set( '_paypal_refund_reason', 'requested_by_customer' );
						$this->set( '_paypal_refund_amount', $this->_paypal_paid_transaction_amount );

						$gateway->cancel_paypal_subscription( $this, 'cancel-with-refund' );

					}

					$this->set( 'status', $new_status );

					S2_Subscription_Activity()->add_activity( $this->id, 'cancelled', $this->order_id, sprintf( __( 'The subscription has been  cancelled and refunded. %s', 's2-subscription' ), $from ) );

					do_action( 's2_customer_subscription_cancelled_mail', $this );
					break;

				case 'paused':
					WC_Stripe_Logger::log( 'S2 - Subscription status - ' . print_r( $this->status, true ) );
					if ( 'active' != $this->status ) {
						return;
					}

					// add the date of pause
					$date_of_pauses = $this->date_of_pauses;
					$date_of_pauses = ! empty( $date_of_pauses ) ? $date_of_pauses : [];
					$date_of_pauses[] = strtotime( 'now' );
					$this->set( 'date_of_pauses', $date_of_pauses );

					// increase the num of pauses done
					$this->set( 'num_of_pauses', $this->num_of_pauses + 1 );


					if ( $gateway->id == 'stripe' && $from != 'stripe-ipn' ) {

						$gateway->update_stripe_subscription( $this, $new_status );
					
					} else if( ( $gateway->id == 's2-paypal-ec' || $gateway->id == 'paypal' ) && $from != 'paypal-ipn' ) {
					
						$gateway->update_paypal_subscription( $this, $new_status );

					}

					$this->set( 'status', $new_status );
					
					S2_Subscription_Activity()->add_activity( $this->id, 'paused', $this->order_id, sprintf( __( 'Subscription paused. %s', 's2-subscription' ), $from ) );

					do_action( 's2_customer_subscription_paused_mail', $this );
					break;

				case 'resumed':
					WC_Stripe_Logger::log( 'S2 - Subscription status - ' . print_r( $this->status, true ) );
					if ( 'paused' != $this->status ) {
						return;
					}

					// change payment_due_date
					$offset = $this->get_payment_due_date_paused_offset();

					// stripe create proration if hours and minute different from start date, so calculate hours and minute from start_date add it to the payment_due_date and expired date
					$start_time = date( "H:i:s", $this->start_date );

					$this->set( 'sum_of_pauses', $this->sum_of_pauses + $offset );

					// for stripe gateway we are updating payment_due_date on receiving webhook invoice.finalized
					if( $this->payment_due_date > 0 && $gateway->id != 'stripe' ) {
						$payment_due_date = $this->payment_due_date + $offset;
						$payment_due_date = date( "m/d/y", $payment_due_date );
						$payment_due_date = strtotime( $payment_due_date . ' ' . $start_time );

						$this->set( 'payment_due_date', $payment_due_date );
					}

					// for stripe gateway we are updating expired_date on receiving webhook invoice.finalized
					if ( $this->expired_date && $gateway->id != 'stripe' ) {
						// shift expiry date
						$expired_date = $this->expired_date + $offset;
						$expired_date = date( "m/d/y", $expired_date );
						$expired_date = strtotime( $expired_date . ' ' . $start_time );

						$this->set( 'expired_date', $expired_date );
					}

					// Update the subscription status
					// S2_Subscription_Activity()->add_activity( $this->id, 'resumed', $this->order_id, sprintf( __( 'Subscription resumed. Payment due on %1$s. %2$s', 's2-subscription' ), date_i18n( wc_date_format(), $payment_due_date ), $from ) );

					if ( $gateway->id == 'stripe' && $from != 'stripe-ipn' ) {

						$gateway->update_stripe_subscription( $this, $new_status );
					
					} else if( ( $gateway->id == 's2-paypal-ec' || $gateway->id == 'paypal' ) && $from != 'paypal-ipn' ) {
					
						$gateway->update_paypal_subscription( $this, $new_status );

					}

					$this->set( 'status', 'active' );

					S2_Subscription_Activity()->add_activity( $this->id, 'resumed', $this->order_id, sprintf( __( 'Subscription resumed. %s', 's2-subscription' ), $from ) );

					do_action( 's2_customer_subscription_resumed_mail', $this );
					break;

				default:
			}

		}

		/**
		 * Start the subscription, calculate dates after a first payment is done
		 * Run this function only once
		 *
		 * @return void
		 */
		public function start() {

			if ( $this->start_date != '' ) {
				return;
			}

			$new_status = 'pending';

			// start_date
			$this->set( 'start_date', strtotime( 'now' ) );

			// if there's a trial period shift the date of payment due
			$trial_period = 0;
			if ( ! empty( $this->trial_period ) && $this->trial_period != 'none' && ( $this->payment_type == 'subscription' || $this->payment_type == 'one_time_fee' ) ) {

				$trial_period_option = s2_get_trial_period_options( $this->trial_period );
				$trial_period        = strtotime( '+' . $trial_period_option['period'] . ' ' . $trial_period_option['time'], $this->start_date );

				$new_status = 'trial';

			}

			// payment_due_date
			if ( $trial_period ) {
				$payment_due_date = $trial_period;
			} else {
				$payment_due_date = $this->start_date;
			}

			// Change the next payment_due_date
			$this->set( 'payment_due_date', $payment_due_date );

			// expired_date
			$billing_frequency = 0;
			if ( $this->payment_type == 'subscription' ) {// if payment type is subscription then get subscription option

				if ( $this->max_length ) {
					$max_length = $this->max_length;
				
					$subscription_option = s2_get_billing_frequency_options( $this->billing_frequency );
					$billing_frequency   = strtotime( '+' . ( $subscription_option['period'] * $max_length ) . ' ' . $subscription_option['time'], $this->start_date );
					$billing_frequency   = $billing_frequency + ( $trial_period ? $trial_period - $this->start_date : 0 );

					$this->set( 'expired_date', $billing_frequency );
				} else {
					$this->set( 'expired_date', '' );
				}

			} else if ( $this->payment_type == 'split_pay' ) {// if payment type is split payment then get split payment option

				$subscription_option = s2_get_split_payment_options( $this->split_payment );
				$billing_frequency   = strtotime( '+' . $subscription_option['period'] . ' ' . $subscription_option['time'], $this->start_date );

				$this->set( 'expired_date', $billing_frequency );

			} else if ( $this->payment_type == 'one_time_fee' ) {

				// if no trial period then one_time_fee payment not considered as subscription
				// added +1 day for stripe subscription becuase stripe create invoice after trial period end
				$billing_frequency = strtotime( '+1 day', $this->start_date );
				$billing_frequency = $billing_frequency + ( $trial_period ? ( $trial_period - $this->start_date ) : 0 );

				// added for testing
				// $billing_frequency 	   = $billing_frequency + 300;

				$this->set( 'expired_date', $billing_frequency );
			}

			$this->set( 'status', $new_status );

		}

		/**
		 * Get the next payment due date.
		 *
		 * If paused, calculate the next date for payment, checking
		 */
		public function get_payment_due_date_paused_offset() {
			if ( 'paused' != $this->status ) {
				return 0;
			}

			$date_pause = $this->date_of_pauses;
			$last       = ( $date_pause[ count( $date_pause ) - 1 ] );
			$offset     = current_time( 'timestamp' ) - $last;

			return $offset;
		}

		/**
		 * Return the next payment due date
		 *
		 * @return bool|int|string
		 */
		public function get_next_payment_due_date() {

			$next_payment_due_date = 0;
			if ( $this->payment_type == 'subscription' ) {// if payment type is subscription then get subscription option

				$subscription_option   = s2_get_billing_frequency_options( $this->billing_frequency );
				$next_payment_due_date = strtotime( '+' . $subscription_option['period'] . ' ' . $subscription_option['time'], $this->payment_due_date );

			} else if ( $this->payment_type == 'split_pay' ) {// if payment type is split payment then get split payment option

				$subscription_option   = s2_get_split_payment_options( $this->split_payment );
				$next_payment_due_date = strtotime( '+1 ' . $subscription_option['time'], $this->payment_due_date );

			}

			if ( ! empty( $this->expired_date ) && $next_payment_due_date >= $this->expired_date ) {
				$next_payment_due_date = 0;
			}

			return $next_payment_due_date;

		}

		/**
		 * Return the subscription detail page url
		 *
		 * @param bool $admin
		 *
		 * @return  string
		 */
		public function get_view_subscription_url( $admin = false ) {

			if ( $admin ) {
				$view_subscription_url = admin_url( 'post.php?post=' . $this->id . '&action=edit' );
			} else {
				$view_subscription_url = wc_get_endpoint_url( 'view-subscription', $this->id, wc_get_page_permalink( 'myaccount' ) );
			}

			return apply_filters( 's2_get_subscription_url', $view_subscription_url, $this->id, $admin );
		}

		/**
		 * Return the a link for change the status of subscription
		 *
		 * @param string $status
		 *
		 * @return string
		 */
		public function get_change_status_link( $status ) {

			$action_link = add_query_arg(
				[
					'subscription'  => $this->id,
					'change_status' => $status,
				]
			);
			$action_link = wp_nonce_url( $action_link, $this->id );

			return apply_filters( 's2_change_status_link', $action_link, $this, $status );
		}

	}

}

/**
 * return instance of S2_Subscription class
 *
 * @param int $subscription_id
 * @param array $args
 *
 * @return S2_Subscription
 */
function s2_get_subscription( $subscription_id = 0, $args = [] ) {
	return new S2_Subscription( $subscription_id, $args );
}
