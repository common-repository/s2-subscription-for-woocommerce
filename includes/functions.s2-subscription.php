<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements helper functions for S2 Subscription
 *
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */

if ( ! function_exists( 's2_get_time_options' ) ) {

	/**
	 * Return the list of time options to add in product editor panel.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	function s2_get_time_options() {
		$options = [
			'day'   => __( 'day', 's2-subscription' ),
			'week'  => __( 'week', 's2-subscription' ),
			'month' => __( 'month', 's2-subscription' ),
			'year'  => __( 'year', 's2-subscription' ),
		];

		// APPLY_FILTER: s2_time_options : Filtering the time options in recurring period
		return apply_filters( 's2_get_time_options', $options );
	}
}

if ( ! function_exists( 's2_get_payment_type_options' ) ) {

	/**
	 * Return the list of billing frequency to add in product editor panel.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	function s2_get_payment_type_options() {
		$options = [
			''             => __( 'Select', 's2-subscription' ),
			'one_time_fee' => __( 'One time fee', 's2-subscription' ),
			'subscription' => __( 'Subscription', 's2-subscription' ),
			'split_pay'    => __( 'Split Pay', 's2-subscription' ),
			// 'pay_your_own_price'    => __( 'Pay your own price', 's2-subscription' ),
		];

		// APPLY_FILTER: s2_get_payment_type_options : Filtering the time options in recurring period
		return apply_filters( 's2_get_payment_type_options', $options );
	}
}

if ( ! function_exists( 's2_get_billing_frequency_options' ) ) {

	/**
	 * Return the list of billing frequency to add in product editor panel.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	function s2_get_billing_frequency_options( $return_option = '' ) {
		$options = [
			'annually'       => [
				'name'   => __( 'Annually', 's2-subscription' ),
				'period' => '1',
				'time'   => 'year',
			],
			'every 6 months' => [
				'name'   => __( 'Every 6 Months', 's2-subscription' ),
				'period' => '6',
				'time'   => 'month',
			],
			'quarterly'      => [
				'name'   => __( 'Quarterly', 's2-subscription' ),
				'period' => '3',
				'time'   => 'month',
			],
			'monthly'        => [
				'name'   => __( 'Monthly', 's2-subscription' ),
				'period' => '1',
				'time'   => 'month',
			],
			'every 2 weeks'  => [
				'name'   => __( 'Every 2 Weeks', 's2-subscription' ),
				'period' => '2',
				'time'   => 'week',
			],
			'weekly'         => [
				'name'   => __( 'Weekly', 's2-subscription' ),
				'period' => '1',
				'time'   => 'week',
			],
			'daily'          => [
				'name'   => __( 'Daily', 's2-subscription' ),
				'period' => '1',
				'time'   => 'day',
			],
		];

		if ( ! empty( $return_option ) ) {
			$options = $options[ $return_option ];
		}

		// APPLY_FILTER: s2_get_billing_frequency_options : Filtering the time options in recurring period
		return apply_filters( 's2_get_billing_frequency_options', $options );
	}
}

if ( ! function_exists( 's2_get_trial_period_options' ) ) {

	/**
	 * Return the list of billing frequency to add in product editor panel.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	function s2_get_trial_period_options( $return_option = '' ) {
		$options = [
			'none'     => [
				'name'   => __( 'None', 's2-subscription' ),
				'period' => '',
				'time'   => '',
			],
			'1 day'    => [
				'name'   => __( '1 Day', 's2-subscription' ),
				'period' => '1',
				'time'   => 'day',
			],
			'3 days'   => [
				'name'   => __( '3 Days', 's2-subscription' ),
				'period' => '3',
				'time'   => 'day',
			],
			'5 days'   => [
				'name'   => __( '5 Days', 's2-subscription' ),
				'period' => '5',
				'time'   => 'day',
			],
			'7 days'   => [
				'name'   => __( '7 Days', 's2-subscription' ),
				'period' => '7',
				'time'   => 'day',
			],
			'14 days'  => [
				'name'   => __( '14 Days', 's2-subscription' ),
				'period' => '14',
				'time'   => 'day',
			],
			'30 days'  => [
				'name'   => __( '30 Days', 's2-subscription' ),
				'period' => '30',
				'time'   => 'day',
			],
			'60 days'  => [
				'name'   => __( '60 Days', 's2-subscription' ),
				'period' => '60',
				'time'   => 'day',
			],
			'90 days'  => [
				'name'   => __( '90 Days', 's2-subscription' ),
				'period' => '90',
				'time'   => 'day',
			],
			'6 months' => [
				'name'   => __( '6 Months', 's2-subscription' ),
				'period' => '6',
				'time'   => 'month',
			],
			'1 year'   => [
				'name'   => __( '1 Year', 's2-subscription' ),
				'period' => '1',
				'time'   => 'year',
			],

		];

		if ( ! empty( $return_option ) ) {
			$options = $options[ $return_option ];
		}

		// APPLY_FILTER: s2_get_trial_period_options : Filtering the time options in recurring period
		return apply_filters( 's2_get_trial_period_options', $options );
	}
}

if ( ! function_exists( 's2_get_split_payment_options' ) ) {

	/**
	 * Return the list of billing frequency to add in product editor panel.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	function s2_get_split_payment_options( $return_option = '' ) {
		$options = [
			'2' => [
				'name'   => __( '2 payments', 's2-subscription' ),
				'period' => '2',
				'time'   => 'month',
			],
			'3' => [
				'name'   => __( '3 payments', 's2-subscription' ),
				'period' => '3',
				'time'   => 'month',
			],
			'4' => [
				'name'   => __( '4 payments', 's2-subscription' ),
				'period' => '4',
				'time'   => 'month',
			],
			'5' => [
				'name'   => __( '5 payments', 's2-subscription' ),
				'period' => '5',
				'time'   => 'month',
			],
			'6' => [
				'name'   => __( '6 payments', 's2-subscription' ),
				'period' => '6',
				'time'   => 'month',
			],
		];

		if ( ! empty( $return_option ) ) {
			$options = $options[ $return_option ];
		}

		// APPLY_FILTER: s2_get_split_payment_options : Filtering the time options in recurring period
		return apply_filters( 's2_get_split_payment_options', $options );
	}
}

if ( ! function_exists( 's2_get_max_length_period' ) ) {

	/**
	 * Return the max length of period that can be accepted from paypal
	 *
	 * @return array
	 * @since  1.0.0
	 */

	function s2_get_max_length_period() {

		$max_length = [
			'day'   => 90,
			'week'  => 52,
			'month' => 24,
			'year'  => 5,
		];

		// APPLY_FILTER: s2_get_max_length_period: the time limit options for PayPal can be filtered
		return apply_filters( 's2_get_max_length_period', $max_length );

	}
}

if ( ! function_exists( 's2_validate_max_length' ) ) {

	/**
	 * Return the max length of period that can be accepted from PayPal.
	 *
	 * @param int $max_length
	 * @param string $time_opt
	 *
	 * @return int
	 * @since  1.0.0
	 */

	function s2_validate_max_length( $max_length, $time_opt ) {

		$max_lengths = s2_get_max_length_period();
		$max_length  = ( $max_length > $max_lengths[ $time_opt ] ) ? $max_lengths[ $time_opt ] : $max_length;

		return $max_length;
	}
}

if ( ! function_exists( 's2_get_order_fields_to_edit' ) ) {
	/**
	 * Return the list of fields that can be edited on a subscription.
	 *
	 * @param $type
	 *
	 * @return array|mixed|void
	 */
	function s2_get_order_fields_to_edit( $type ) {
		$fields = [];

		if ( 'billing' == $type ) {
			// APPLY_FILTER: s2_admin_billing_fields : filtering the admin billing fields
			$fields = apply_filters( 's2_admin_billing_fields', [
					'first_name' => [
						'label' => __( 'First name', 's2-subscription' ),
						'show'  => false,
					],
					'last_name'  => [
						'label' => __( 'Last name', 's2-subscription' ),
						'show'  => false,
					],
					'company'    => [
						'label' => __( 'Company', 's2-subscription' ),
						'show'  => false,
					],
					'address_1'  => [
						'label' => __( 'Address line 1', 's2-subscription' ),
						'show'  => false,
					],
					'address_2'  => [
						'label' => __( 'Address line 2', 's2-subscription' ),
						'show'  => false,
					],
					'city'       => [
						'label' => __( 'City', 's2-subscription' ),
						'show'  => false,
					],
					'postcode'   => [
						'label' => __( 'Postcode / ZIP', 's2-subscription' ),
						'show'  => false,
					],
					'country'    => [
						'label'   => __( 'Country', 's2-subscription' ),
						'show'    => false,
						'class'   => 'js_field-country select short',
						'type'    => 'select',
						'options' => [ '' => __( 'Select a country&hellip;', 's2-subscription' ) ] + WC()->countries->get_allowed_countries(),
					],
					'state'      => [
						'label' => __( 'State / County', 's2-subscription' ),
						'class' => 'js_field-state select short',
						'show'  => false,
					],
					'email'      => [
						'label' => __( 'Email address', 's2-subscription' ),
					],
					'phone'      => [
						'label' => __( 'Phone', 's2-subscription' ),
					],
				] );
		} elseif ( 'shipping' == $type ) {
			// APPLY_FILTER: s2_admin_shipping_fields : filtering the admin shipping fields
			$fields = apply_filters( 's2_admin_shipping_fields', [
					'first_name' => [
						'label' => __( 'First name', 's2-subscription' ),
						'show'  => false,
					],
					'last_name'  => [
						'label' => __( 'Last name', 's2-subscription' ),
						'show'  => false,
					],
					'company'    => [
						'label' => __( 'Company', 's2-subscription' ),
						'show'  => false,
					],
					'address_1'  => [
						'label' => __( 'Address line 1', 's2-subscription' ),
						'show'  => false,
					],
					'address_2'  => [
						'label' => __( 'Address line 2', 's2-subscription' ),
						'show'  => false,
					],
					'city'       => [
						'label' => __( 'City', 's2-subscription' ),
						'show'  => false,
					],
					'postcode'   => [
						'label' => __( 'Postcode / ZIP', 's2-subscription' ),
						'show'  => false,
					],
					'country'    => [
						'label'   => __( 'Country', 's2-subscription' ),
						'show'    => false,
						'type'    => 'select',
						'class'   => 'js_field-country select short',
						'options' => [ '' => __( 'Select a country&hellip;', 's2-subscription' ) ] + WC()->countries->get_shipping_countries(),
					],
					'state'      => [
						'label' => __( 'State / County', 's2-subscription' ),
						'class' => 'js_field-state select short',
						'show'  => false,
					],
				] );
		}

		return $fields;
	}
}

if ( ! function_exists( 's2_get_price_per_string' ) ) {

	/**
	 * Return the recurring period string.
	 *
	 * @param int $price_per
	 * @param string $time_option
	 * @param bool $show_one_number
	 *
	 * @return int
	 */

	function s2_get_price_per_string( $price_per, $time_option, $show_one_number = false ) {
		$price_html = ( ( $price_per == 1 && ! $show_one_number ) ? '' : $price_per ) . ' ';

		switch ( $time_option ) {
			case 'day':
				$price_html .= _n( 'day', 'days', $price_per, 's2-subscription' );
				break;
			case 'week':
				$price_html .= _n( 'week', 'weeks', $price_per, 's2-subscription' );
				break;
			case 'month':
				$price_html .= _n( 'month', 'months', $price_per, 's2-subscription' );
				break;
			case 'year':
				$price_html .= _n( 'year', 'years', $price_per, 's2-subscription' );
				break;
			default:
		}

		return $price_html;
	}
}

if ( ! function_exists( 's2_get_price_time_option_paypal' ) ) {

	/**
	 * Return the symbol used by PayPal Standard Payment for time options.
	 *
	 * @param string $time_option
	 *
	 * @return string
	 * @since 1.0.0
	 */

	function s2_get_price_time_option_paypal( $time_option ) {
		$options = [
			'day'   => 'D',
			'week'  => 'W',
			'month' => 'M',
			'year'  => 'Y',
		];

		return isset( $options[ $time_option ] ) ? $options[ $time_option ] : '';
	}
}

if ( ! function_exists( 's2_order_has_subscriptions' ) ) {
	/**
	 * Check if in the cart there are subscription
	 *
	 * @return bool/array
	 */
	function s2_order_has_subscriptions( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return false;
		}

		$order_items        = $order->get_items();
		$subscription_items = [];
		$count              = 0;
		if ( ! empty( $order_items ) ) {
			foreach ( $order_items as $order_item_key => $order_item ) {
				// variation_id or product_id
				$product_id = $order_item['variation_id'];
				if ( empty( $product_id ) ) {
					$product_id = $order_item['product_id'];
				}

				if ( s2_is_subscription( $product_id ) ) {
					$count = array_push( $subscription_items, $order_item_key );
				}
			}
		}

		return $count == 0 ? false : $subscription_items;
	}
}

if ( ! function_exists( 's2_cart_has_subscriptions' ) ) {
	/**
	 * Check if in the cart there are subscription
	 *
	 * @return bool/array
	 */
	function s2_cart_has_subscriptions() {
		if ( WC()->cart == null ) {
			return false;
		}

		$contents = WC()->cart->get_cart();
		$items    = [];
		$count    = 0;
		if ( ! empty( $contents ) ) {
			foreach ( $contents as $item_key => $item ) {
				// variation_id or product_id
				if ( ! empty( $item['variation_id'] ) ) {
					$product_id = $item['variation_id'];
				} else {
					$product_id = $item['product_id'];
				}

				if ( s2_is_subscription( $product_id ) ) {
					$count = array_push( $items, $item_key );
				}
			}
		}

		return $count == 0 ? false : $items;
	}
}

if ( ! function_exists( 's2_is_subscription' ) ) {
	/**
	 * Check if a product is a subscription
	 *
	 * @param $product
	 *
	 * @return bool
	 * @internal param int|WC_Product $product_id
	 */
	function s2_is_subscription( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product ) {
			return false;
		}

		$product_id      = $product->get_id();
		$s2_payment_type = get_post_meta( $product_id, 's2_payment_type', true );

		if ( empty( $s2_payment_type ) ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 's2_subscription_log' ) ) {
	/**
	 * Log message
	 */
	function s2_subscription_log( $message, $type = 'subscription_status' ) {

		$s2ws_settings = get_option('s2ws_settings'); // plugin settings

		if( empty( $s2ws_settings ) ) return;

		$debug_enabled = $s2ws_settings['enable_log'];

		if ( 'yes' === $debug_enabled ) {

			$debug = wc_get_logger();

			$debug->add( 's2_' . $type, $message );

		}

	}
}

if ( ! function_exists( 's2_get_user_subscriptions' ) ) {
	/**
	 * Get all subscriptions of a user
	 *
	 * @param int $user_id
	 *
	 * @return array
	 */

	function s2_get_user_subscriptions( $user_id = 0 ) {

		if ( 0 === $user_id || empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$args = [
			'fields'		 	=> 'ids',
			'post_type'      	=> 's2_subscription',
			'posts_per_page' 	=> -1,
			'suppress_filters' 	=> true,
			'meta_query' => [
				'relation' => 'AND',
		        [
		            'key'     => 'user_id',
		            'value'   => $user_id,
		        ],
		        [
		            'key'     => 'payment_type',
		            'value'   => 'subscription',
		        ],
		    ]
		];

		$subscriptions = get_posts( $args );

		return $subscriptions;
	}
}

if ( ! function_exists( 's2_get_subscriptions_by_meta' ) ) {
	/**
	 * Get all subscriptions by meta_key, meta_value
	 *
	 * @param array $meta_query
	 *
	 * @return array
	 */

	function s2_get_subscriptions_by_meta( $meta_query ) {

		$args = [
			'fields'		 	=> 'ids',
			'post_type'      	=> 's2_subscription',
			'posts_per_page' 	=> -1,
			'suppress_filters' 	=> true,
			'meta_query' 		=> $meta_query,
		];

		$subscriptions = get_posts( $args );

		return $subscriptions;
	}
}

if ( ! function_exists( 's2_get_status' ) ) {

	/**
	 * Return the list of status available.
	 *
	 * @return array
	 */

	function s2_get_status() {
	
		$options = [
			'active'    => __( 'Active', 's2-subscription' ),
			'paused'    => __( 'Paused', 's2-subscription' ),
			'pending'   => __( 'Pending', 's2-subscription' ),
			'overdue'   => __( 'Overdue', 's2-subscription' ),
			'trial'     => __( 'Trial', 's2-subscription' ),
			'cancelled' => __( 'Cancelled', 's2-subscription' ),
			'expired'   => __( 'Expired', 's2-subscription' ),
			'suspended' => __( 'Suspended', 's2-subscription' ),
		];

		// APPLY_FILTER: s2_status: the list of status of a subscription
		return apply_filters( 's2_status', $options );
	
	}

}
