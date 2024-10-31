<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements product features of S2 Subscription Cron
 *
 * @class   S2_Subscription_Cron
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Cron' ) ) {

	class S2_Subscription_Cron {

		/**
		 * @var string post_type
		 */
		public $post_type = 's2_subscription';

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Cron
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Subscription_Cron
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
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			add_action( 'wp_loaded', [ $this, 'set_cron' ], 30 );

			add_action( 's2_check_subscription_expired', [ $this, 's2_check_subscription_expired' ] );

			add_action( 's2_check_subscription_trial_period', [ $this, 's2_check_subscription_trial_period' ] );
			
			add_action( 's2_check_subscription_payment', [ $this, 's2_check_subscription_payment' ] );

		}

		/**
		 * Set Cron
		 */
		public function set_cron() {

			// gateways cron
			if ( class_exists( 'WC_Gateway_Stripe' ) ) {
				require_once S2_WS_INC . 'gateways/stripe/class.s2-stripe-cron.php';
				// S2_Stripe_Cron()->s2_check_stripe_subscription();
			}

			$ve         = get_option( 'gmt_offset' ) > 0 ? '+' : '-';
			$time_start = strtotime( '00:00 ' . $ve . get_option( 'gmt_offset' ) . ' HOURS' );

			if ( ! wp_next_scheduled( 's2_check_subscription_expired' ) ) {
				wp_schedule_event( $time_start, 'hourly', 's2_check_subscription_expired' );
			}

			if ( ! wp_next_scheduled( 's2_check_subscription_trial_period' ) ) {
				wp_schedule_event( $time_start, 'hourly', 's2_check_subscription_trial_period' );
			}

			if ( ! wp_next_scheduled( 's2_check_subscription_payment' ) ) {
				wp_schedule_event( $time_start, 'hourly', 's2_check_subscription_payment' );
			}

		}

		/**
		 * Check if there are subscription expired
		 * update status of subscription
		 * paypal send expired ipn immediately before complete payment ipn of last payment
		 * so instead of updating through paypal ipn response update expired status on admin access
		 *
		 * @since  1.0.0
		 */
		public function s2_check_subscription_expired() {

			global $wpdb;

			$args = [
				'fields'		 	=> 'ids',
				'post_type'      	=> $this->post_type,
				'posts_per_page' 	=> -1,
				'suppress_filters' 	=> true,
				'meta_query' => [
					'relation' => 'AND',
			        [
			            'key'     => 'expired_date',
			            'value'   => ['', '0'],
			            'compare' => 'NOT IN',
			        ],
			        [
			            'key'     => 'expired_date',
			            'value'   => strtotime( 'now' ),
			            'compare' => '<=',
			        ],
			        [
			            'key'     => 'status',
			            'value'   => [ 'active' ],
			            'compare' => 'IN',
			        ],
			        [
			            'key'     => 'payment_method',
			            'value'   => [ 'paypal', 'cod' ],
			            'compare' => 'IN',
			        ],
			    ]
			];

			$subscriptions = get_posts( $args );
			
			if ( ! empty( $subscriptions ) ) {

				foreach ( $subscriptions as $subscription_id ) {
					
					$subscription = new S2_Subscription( $subscription_id );
					$subscription->change_status( 'expired', 'administrator' );
				
				}

			}

		}

		/**
		 * Check if there are subscription in trial period
		 * update status of subscription
		 *
		 * @since  1.0.2
		 */
		public function s2_check_subscription_trial_period() {

			global $wpdb;

			$args = [
				'fields'		 	=> 'ids',
				'post_type'      	=> $this->post_type,
				'posts_per_page' 	=> -1,
				'suppress_filters' 	=> true,
				'meta_query' => [
					'relation' => 'AND',
			        [
			            'key'     => 'payment_due_date',
			            'value'   => ['', '0'],
			            'compare' => 'NOT IN',
			        ],
			        [
			            'key'     => 'payment_due_date',
			            'value'   => strtotime( 'now' ),
			            'compare' => '<=',
			        ],
			        [
			            'key'     => 'status',
			            'value'   => [ 'trial' ],
			            'compare' => 'IN',
			        ],
			        [
			            'key'     => 'payment_method',
			            'value'   => [ 'cod' ],
			            'compare' => 'IN',
			        ],
			    ]
			];

			$subscriptions = get_posts( $args );
			if ( ! empty( $subscriptions ) ) {

				foreach ( $subscriptions as $subscription_id ) {
					
					$subscription = new S2_Subscription( $subscription_id );
					$subscription->change_status( 'active', 'administrator' );

				}

			}

		}

		/**
		 * Check if subscription payment overdue
		 * update status of subscription
		 *
		 * @since  1.0.2
		 */
		public function s2_check_subscription_payment() {

			global $wpdb;

			$args = [
				'fields'		 	=> 'ids',
				'post_type'      	=> $this->post_type,
				'posts_per_page' 	=> -1,
				'suppress_filters' 	=> true,
				'meta_query' => [
					'relation' => 'AND',
			        [
			            'key'     => 'payment_due_date',
			            'value'   => ['', '0'],
			            'compare' => 'NOT IN',
			        ],
			        [
			            'key'     => 'payment_due_date',
			            'value'   => strtotime( 'now' ),
			            'compare' => '<=',
			        ],
			        [
			            'key'     => 'status',
			            'value'   => [ 'active' ],
			            'compare' => 'IN',
			        ],
			        [
			            'key'     => 'payment_method',
			            'value'   => [ 'cod' ],
			            'compare' => 'IN',
			        ],
			    ]
			];

			$subscriptions = get_posts( $args );
			if ( ! empty( $subscriptions ) ) {

				foreach ( $subscriptions as $subscription_id ) {
					
					$subscription = new S2_Subscription( $subscription_id );
					$subscription->change_status( 'overdue', 'administrator' );

				}

			}

		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Cron class
 *
 * @return \S2_Subscription_Cron
 */
function S2_Subscription_Cron() {
	return S2_Subscription_Cron::get_instance();
}

S2_Subscription_Cron();
